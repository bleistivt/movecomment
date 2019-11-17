<?php if (!defined('APPLICATION')) exit();

echo wrap($this->title(), 'h1');

echo $this->Form->open();
echo $this->Form->errors();

?>

<ul>
    <li>
        <div class="TextBoxWrapper">
        <?php
            echo $this->Form->label('MoveComment.TargetDiscussionID', 'TargetDiscussionID');
            echo $this->Form->textBox('TargetDiscussionID');
        ?>
        </div>
    </li>
    <li style="min-width:480px;">
        <a href="" id="DiscussionPeek"></a>
        <i id="NoDiscussionPeek" style="visibility:hidden;"><?php echo Gdn::translate('MoveComment.NoDiscussionFound'); ?></i>
    </li>
    <li>
        <?php echo $this->Form->checkBox('RedirectToTarget', 'MoveComment.RedirectMe'); ?>
    </li>
</ul>

<div class="Buttons Buttons-Confirm">
<?php
    echo $this->Form->button('OK', ['class' => 'Button Primary', 'id' => 'MoveCommentSubmit']);
    echo $this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
?>
</div>

<?php echo $this->Form->close(); ?>

<script type="text/javascript">
jQuery(function ($) {
    var update = function () {
        var input = $('#Form_TargetDiscussionID');
        var submit = $('#MoveCommentSubmit');
        var peekLink = $('#DiscussionPeek');
        var notFound = $('#NoDiscussionPeek').css('visibility', 'visible');

        var peek = function () {
            $.getJSON(
                gdn.url('/discussion/movecommentpeek'),
                {discussionID: input.val()},
                function (data) {
                    if (data.title) {
                        peekLink
                            .show()
                            .text(data.title)
                            .attr('href', gdn.url('/discussion/' + data.id));
                        gdn.enable(submit);
                        notFound.hide();
                    } else {
                        peekLink.hide();
                        gdn.disable(submit);
                        notFound.show();
                    }
                }
            );
        };

        var timeout;

        return function () {
            gdn.disable(submit);
            clearTimeout(timeout);
            timeout = setTimeout(peek, 500);
        };
    }();

    update();
    $('#Form_TargetDiscussionID')
        .on('focus', update)
        .on('input', update);
});
</script>
