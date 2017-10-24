<?php if (!defined('APPLICATION')) exit(); ?>

<h2><?php echo T('OA_SOCIAL_LOGIN_LINK_SIDEMENU'); ?></h2>

<?php

echo 
	'<p>' . T('OA_SOCIAL_LOGIN_LINK_DESC1') . '<br />' . T('OA_SOCIAL_LOGIN_LINK_DESC2') . '</p>
	<h4>' . T('OA_SOCIAL_LOGIN_LINK_NETWORKS') . ':</h4>
	<span>' . T('OA_SOCIAL_LOGIN_LINK_ACTION') . '</span><br /><br />
	<div class="oneall_social_login" id="oneall_social_login_link_profile"></div>
		
	<!-- OneAll Social Login : http://www.oneall.com //-->
	<script type="text/javascript">
	// <![CDATA[
		var _oneall = _oneall || [];
		_oneall.push(["social_link", "set_providers", [' . $this->Data['providers'] . ']]);
		_oneall.push(["social_link", "set_user_token", "' . $this->Data['user_token'] . '"]);
		_oneall.push(["social_link", "set_callback_uri", "' . $this->Data['callback_uri'] . '"]);
		_oneall.push(["social_link", "do_render_ui", "oneall_social_login_link_profile"]);
	// ]]>
	</script>';
