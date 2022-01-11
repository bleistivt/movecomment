<?php if (!defined('APPLICATION')) exit();

echo wrap($this->title(), 'h1');

echo $this->Form->open();
echo $this->Form->errors();

?>

<ul>
    <li>
        <p><?php echo Gdn::translate('MoveComment.MoveNewDiscussionDesc'); ?></p>
    </li>
    <li>
        <div class="TextBoxWrapper">
        <?php
            echo $this->Form->label('Category', 'CategoryID');
            echo $this->Form->categoryDropDown('CategoryID', ['IncludeNull' => true]);
        ?>
        </div>
    </li>
    <li>
        <?php echo $this->Form->label('Discussion Title', 'Name'); ?>
        <div class="TextBoxWrapper">
        <?php
            echo $this->Form->textBox(
                'Name',
                ['maxlength' => 100, 'class' => 'InputBox BigInput', 'spellcheck' => 'true']
            );
        ?>
        </div>
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
