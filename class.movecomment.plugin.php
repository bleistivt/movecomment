<?php

class MoveCommentPlugin extends Gdn_Plugin {

    // Add the "Move" Option to the dropdown.
    public function base_commentOptions_handler($sender) {
        $args = &$sender->EventArguments;

        $isModerator = CategoryModel::checkPermission(
            Gdn::controller()->data('Discussion')->CategoryID,
            'Vanilla.Discussions.Edit'
        );

        if (!$isModerator) {
            return;
        }

        $args['CommentOptions']['MoveComment'] = [
            'Label' => Gdn::translate('MoveComment.Move'),
            'Url' => '/discussion/movecomment/'.$args['Comment']->CommentID,
            'Class' => 'MoveComment Popup'
        ];
    }


    // The endpoint
    public function discussionController_movecomment_create($sender, $commentID) {
        $session = Gdn::session();

        $comment = $sender->CommentModel->getID($commentID);
        if (!$comment) {
            throw notFoundException('Comment');
        }

        $discussion = $sender->DiscussionModel->getID($comment->DiscussionID);
        if (!$discussion) {
            throw notFoundException('Discussion');
        }

        // Check the permissions on the category we are moving from.
        $sender->permission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID);

        if ($sender->Form->authenticatedPostBack()) {
            // Fetch the target discussion.
            $target = $sender->DiscussionModel->getID($sender->Form->getValue('TargetDiscussionID'));
            if (!$target) {
                throw notFoundException('Discussion');
            }

            // Source and target discussions can not be the same.
            if ($discussion->DiscussionID === $target->DiscussionID) {
                throw new Gdn_UserException(Gdn::translate('MoveComment.SameSourceAndTarget'));
            }

            // Calculate the current offset before moving so we can use it later.
            $offset = $sender->CommentModel->getOffset($comment);

            // Escalate permissions to fit the target discussion.
            $sender->permission('Vanilla.Discussions.Edit', true, 'Category', $target->PermissionCategoryID);

            // Move the comment.
            $sender->CommentModel->setField($comment->CommentID, 'DiscussionID', $target->DiscussionID);

            // Clear the page cache.
            $sender->CommentModel->removePageCache($comment->DiscussionID);

            // Update the comment counts.
            $sender->CommentModel->updateCommentCount($discussion->DiscussionID);
            $sender->CommentModel->updateCommentCount($target->DiscussionID);

            // Update the categories.
            $categoryModel = CategoryModel::instance();
            $categoryModel->setRecentPost($discussion->CategoryID);

            // Was the comment moved between categories?
            if ($discussion->CategoryID !== $target->CategoryID) {
                $categoryModel->setRecentPost($target->CategoryID);

                // Correct CountAllComments for the category and its parents.
                CategoryModel::decrementAggregateCount($discussion->CategoryID, CategoryModel::AGGREGATE_COMMENT);
                CategoryModel::incrementAggregateCount($target->CategoryID, CategoryModel::AGGREGATE_COMMENT);
            }

            // Save the target discussion ID with the user so the form can be pre-filled.
            $this->setUserMeta($session->UserID, 'LastDiscussion', $target->DiscussionID);

            // Redirect to the target discussion or stay here?
            $redirect = (bool)$sender->Form->getValue('RedirectToTarget');

            if ($redirect) {
                $redirectUrl = '/discussion/comment/'.$comment->CommentID.'#Comment_'.$comment->CommentID;
            } else {
                $pageNumber = pageNumber($offset, Gdn::config('Vanilla.Comments.PerPage'), true);
                $redirectUrl = discussionUrl($discussion, $pageNumber);
            }

            // Not in a popup, always redirect.
            if ($sender->deliveryType() === DELIVERY_TYPE_ALL) {
                redirectTo($redirectUrl);
            }

            // For JS (popup) users, only redirect if we want a page change.
            if ($redirect) {
                $sender->setRedirectTo($redirectUrl);
            }

            // Remove the comment from the UI.
            $sender->jsonTarget('#Comment_'.$comment->CommentID, '', 'SlideUp');
        }

        $sender->title(Gdn::translate('MoveComment.TitleMoveToDiscussion'));

        // Pre-fill with the last discussion this user has moved a comment to.
        $lastDiscussion = $this->getUserMeta($session->UserID, 'LastDiscussion', null, true);
        if ($lastDiscussion) {
            $sender->Form->setValue('TargetDiscussionID', $lastDiscussion);
        }

        $sender->render('movecomment', '', 'plugins/movecomment');
    }


    // Endpoint for JS to get a discussion title that helps the user validate that the ID is correct.
    public function discussionController_movecommentpeek_create($sender, $discussionID) {
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);

        $discussion = $sender->DiscussionModel->getID($discussionID);

        $canView = $discussion ? CategoryModel::checkPermission($discussion, 'Vanilla.Discussions.View') : false;

        $sender->renderData($canView ? [
            'title' => htmlspecialchars_decode($discussion->Name),
            'id' => $discussion->DiscussionID
        ] : []);
    }

}
