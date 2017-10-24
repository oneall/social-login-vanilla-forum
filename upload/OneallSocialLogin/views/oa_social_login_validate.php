<?php if (!defined('APPLICATION')) exit(); ?>

<h1>
	<?php echo T('OA_SOCIAL_LOGIN_VALIDATION_FORM_HEADER'); ?>
</h1>

<div class="Info">
   <?php echo T('OA_SOCIAL_LOGIN_VALIDATION_FORM_DESC'); ?>
</div>

<?php
   echo $this->Form->Open(array ('action' => 'index.php?p=/plugin/oneallsociallogin/validate'));
   echo $this->Form->Errors();
?>

<ul>
   <li><?php
      echo $this->Form->Label('OA_SOCIAL_LOGIN_VALIDATION_FORM_LOGIN_EXPLAIN', 'user_login');
      echo $this->Form->Textbox('user_login');
   ?></li>
   <li><?php
      echo $this->Form->Label('OA_SOCIAL_LOGIN_VALIDATION_FORM_EMAIL_EXPLAIN', 'user_email');
      echo $this->Form->Textbox('user_email');
   ?></li>
</ul>

<?php
   echo $this->Form->Close('OA_SOCIAL_LOGIN_SAVE');
?>
