<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Help Aside">
	<?php
	echo Wrap(T('OA_SOCIAL_LOGIN_TITLE_HELP'), 'h2');
	echo '<ul>';
	echo Wrap(T('OA_SOCIAL_LOGIN_FOLLOW_US_TWITTER'),'li');
	echo Wrap(T('OA_SOCIAL_LOGIN_READ_DOCS'), 'li');
	echo Wrap(T('OA_SOCIAL_LOGIN_DISCOVER_PLUGINS'), 'li');
	echo Wrap(T('OA_SOCIAL_LOGIN_GET_HELP'), 'li');
	echo '</ul>';
	?>
</div>

<h1>
	<?php echo T('OA_SOCIAL_LOGIN_TITLE'); ?>
</h1>

<div class="Info">
	<?php echo T('OA_SOCIAL_LOGIN_INTRO'); ?>
</div>


<div class="Info">
	<?php 
	echo T('OA_SOCIAL_LOGIN_CREATE_ACCOUNT_FIRST');
	?>
</div>
<div class="Info">
	<?php
	echo Anchor(T('OA_SOCIAL_LOGIN_SETUP_FREE_ACCOUNT'), T('OA_SOCIAL_LOGIN_SETUP_FREE_ACCOUNT_HREF'), array('class' => 'external SmallButton'));
	echo Anchor(T('OA_SOCIAL_LOGIN_VIEW_CREDENTIALS'), T('OA_SOCIAL_LOGIN_VIEW_CREDENTIALS_HREF'), array('class' => 'external SmallButton'));
	?>
</div>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();
 
?>
<div id="oneall_sociallogin">
	<ul>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_DO_ENABLE'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo T('OA_SOCIAL_LOGIN_DO_ENABLE_DESC'); ?></td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.Enable', T('OA_SOCIAL_LOGIN_DO_ENABLE_YES') . ' (' . Wrap(T('OA_SOCIAL_LOGIN_DEFAULT'), 'strong') . ')', 
								array('value' => 1));
						echo $this->Form->Radio('Plugin.OASocialLogin.Enable', T('OA_SOCIAL_LOGIN_DO_ENABLE_NO'), array('value' => 0));
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_API_CONNECTION'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_API_CONNECTION_HANDLER'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_API_CONNECTION_HANDLER_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.Curl', T('OA_SOCIAL_LOGIN_CURL'), array('Value' => 1, 'class' => 'oa_social_login_api_curl'));
						echo Wrap(T('OA_SOCIAL_LOGIN_CURL_DESC') . ' (' . Anchor(T('OA_SOCIAL_LOGIN_CURL_DOCS'), T('OA_SOCIAL_LOGIN_CURL_DOCS_HREF'), array('class' => 'external')) . ')');
						echo $this->Form->Radio('Plugin.OASocialLogin.Curl', T('OA_SOCIAL_LOGIN_FSOCKOPEN'), array('Value' => 0, 'class' => 'oa_social_login_api_fsockopen'));
						echo Wrap(T('OA_SOCIAL_LOGIN_FSOCKOPEN_DESC') . ' (' . Anchor(T('OA_SOCIAL_LOGIN_FSOCKOPEN_DOCS'), T('OA_SOCIAL_LOGIN_FSOCKOPEN_DOCS_HREF'), array('class' => 'external')) . ')');
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_API_PORT'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_API_PORT_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.SSL', T('OA_SOCIAL_LOGIN_PORT_443'), array('value' => 1, 'class' => 'oa_social_login_api_443'));
						echo Wrap(T('OA_SOCIAL_LOGIN_PORT_443_DESC'));
						echo $this->Form->Radio('Plugin.OASocialLogin.SSL', T('OA_SOCIAL_LOGIN_PORT_80'), array('value' => 0, 'class' => 'oa_social_login_api_80'));
						echo Wrap(T('OA_SOCIAL_LOGIN_PORT_80_DESC'));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo $this->Form->Button(T('OA_SOCIAL_LOGIN_API_AUTODETECT'), 
								array('class' => 'SmallButton', 'type' => 'button', 'id' => 'oa_social_login_autodetect_api_connection_handler'));
						?>
						</td>
						<td>
							<span id="oa_social_login_api_connection_handler_result"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_API_CREDENTIALS_TITLE'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_API_SUBDOMAIN'));
						?>
						</td>
						<td><?php
						echo $this->Form->TextBox('Plugin.OASocialLogin.ApiSubdomain', array ('maxlength' => '30', 'class' => 'InputBox oa_social_login_api_domain'));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo T('OA_SOCIAL_LOGIN_API_PUBLIC_KEY');
						?>
						</td>
						<td><?php
						echo $this->Form->TextBox('Plugin.OASocialLogin.ApiKey', array ('maxlength' => '60', 'class' => 'InputBox oa_social_login_api_key'));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_API_PRIVATE_KEY'));
						?>
						</td>
						<td><?php
						echo $this->Form->TextBox('Plugin.OASocialLogin.ApiSecret', array ('maxlength' => '60', 'class' => 'InputBox oa_social_login_api_secret'));
						?>
						</td>
					</tr>
					<tr>
						<td><?php
						echo $this->Form->Button(T('OA_SOCIAL_LOGIN_API_VERIFY'),
								array('class' => 'SmallButton', 'type' => 'button', 'id' => 'oa_social_login_test_api_settings'));
						?>
						</td>
						<td>
							<span id="oa_social_login_api_test_result"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_DISPLAY_LOC'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_INDEX_PAGE_ENABLE'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_INDEX_PAGE_ENABLE_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.IndexPageEnable', T('OA_SOCIAL_LOGIN_INDEX_PAGE_YES') . ' (' . Wrap(T('OA_SOCIAL_LOGIN_DEFAULT'), 'strong') . ')', array('value' => 1));
						echo $this->Form->Radio('Plugin.OASocialLogin.IndexPageEnable', T('OA_SOCIAL_LOGIN_INDEX_PAGE_NO'), array('value' => 0));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_INDEX_PAGE_CAPTION'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_INDEX_PAGE_CAPTION_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->TextBox('Plugin.OASocialLogin.IndexPageCaption', 'text', array ('maxlength' => '60'));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_LOGIN_PAGE_ENABLE'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_LOGIN_PAGE_ENABLE_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.LoginPageEnable', T('OA_SOCIAL_LOGIN_LOGIN_PAGE_YES') . ' (' . Wrap(T('OA_SOCIAL_LOGIN_DEFAULT'), 'strong') . ')', array('value' => 1));
						echo $this->Form->Radio('Plugin.OASocialLogin.LoginPageEnable', T('OA_SOCIAL_LOGIN_LOGIN_PAGE_NO'), array('value' => 0));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_LOGIN_PAGE_CAPTION'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_LOGIN_PAGE_CAPTION_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->TextBox('Plugin.OASocialLogin.LoginPageCaption', 'text', array ('maxlength' => '60'));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_REGISTRATION_PAGE_ENABLE'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_REGISTRATION_PAGE_ENABLE_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.RegistrationPageEnable', T('OA_SOCIAL_LOGIN_REGISTRATION_PAGE_YES') . ' (' . Wrap(T('OA_SOCIAL_LOGIN_DEFAULT'), 'strong') . ')', array('value' => 1));
						echo $this->Form->Radio('Plugin.OASocialLogin.RegistrationPageEnable', T('OA_SOCIAL_LOGIN_REGISTRATION_PAGE_NO'), array('value' => 0));
						?>
						</td>
					</tr>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_REGISTRATION_PAGE_CAPTION'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_REGISTRATION_PAGE_CAPTION_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->TextBox('Plugin.OASocialLogin.RegistrationPageCaption', 'text', array ('maxlength' => '60'));
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_DO_AVATARS'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo T('OA_SOCIAL_LOGIN_DO_AVATARS_DESC'); ?></td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.AvatarsEnable', T('OA_SOCIAL_LOGIN_DO_AVATARS_ENABLE_YES') . ' (' . Wrap(T('OA_SOCIAL_LOGIN_DEFAULT'), 'strong') . ')', array('value' => 1));
						echo $this->Form->Radio('Plugin.OASocialLogin.AvatarsEnable', T('OA_SOCIAL_LOGIN_DO_AVATARS_ENABLE_NO'), array('value' => 0));
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_DO_VALIDATION'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_DO_VALIDATION_ASK'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_DO_VALIDATION_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.Validate', T('OA_SOCIAL_LOGIN_DO_VALIDATION_NEVER') . ' (' . Wrap(T('OA_SOCIAL_LOGIN_DEFAULT'), 'strong') . ')', array('value' => 0));
						echo $this->Form->Radio('Plugin.OASocialLogin.Validate', T('OA_SOCIAL_LOGIN_DO_VALIDATION_ALWAYS'), array('value' => 1));
						echo $this->Form->Radio('Plugin.OASocialLogin.Validate', T('OA_SOCIAL_LOGIN_DO_VALIDATION_DEPENDS'), array('value' => 2));
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_DO_LINKING'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_DO_LINKING_ASK'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_DO_LINKING_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->Radio('Plugin.OASocialLogin.LinkingEnable', T('OA_SOCIAL_LOGIN_DO_LINKING_YES') . ' (' . Wrap(T('OA_SOCIAL_LOGIN_DEFAULT'), 'strong') . ')', array('value' => 1));
						echo $this->Form->Radio('Plugin.OASocialLogin.LinkingEnable', T('OA_SOCIAL_LOGIN_DO_LINKING_NO'), array('value' => 0));
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_DO_REDIRECT'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php 
						echo Wrap(T('OA_SOCIAL_LOGIN_DO_REDIRECT_ASK'), 'Strong');
						echo Wrap(T('OA_SOCIAL_LOGIN_DO_REDIRECT_DESC'));
						?>
						</td>
						<td><?php
						echo $this->Form->TextBox('Plugin.OASocialLogin.Redirect', 'text', array ('maxlength' => '60'));
						?>
						</td>
					</tr>
				</tbody>
			</table>
		</li>
		<li>
			<table class="Label AltColumns">
				<thead>
					<tr>
						<th colspan="2"><?php echo T('OA_SOCIAL_LOGIN_ENABLE_NETWORKS'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php
						foreach (SocialLogin::all_providers() as $id => $name) 
						{
							echo '<label for="' . $this->Form->EscapeID(OneallSocialLogin::PROVIDER_PREFIX . $id, FALSE) . '" class="CheckBoxLabel oa_social_login_provider" >';
							echo '<span class="oa_social_login_provider_' . $id . '" title="' . $name . '">' . $name . '</span>';
							echo $this->Form->CheckBox (OneallSocialLogin::PROVIDER_PREFIX . $id);
							echo $name;
							echo '</label>';
						}
						?></td>
					</tr>
				</tbody>
			</table>
		</li>
	</ul>
</div>

<?php
echo $this->Form->Close(T('OA_SOCIAL_LOGIN_SAVE'));
?>
