<?php

$PluginInfo['OneallSocialLogin'] = array(
    'Name' => 'OneAll Social Login',
    'Description' => 'Social Login for Vanilla allows your users to login and register with 35+ Social Networks like for example Twitter, Facebook, LinkedIn and Google+.',
    'Version' => '3.7.0',
    'RequiredApplications' => array('Vanilla' => '2.5.1'),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => true,
    'SettingsUrl' => '/plugin/oneallsociallogin',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'Author' => "OneAll",
    'AuthorEmail' => 'support@oneall.com',
    'AuthorUrl' => 'http://www.oneall.com/',
    'License' => 'GNU GPL2'
);

class OneallSocialLoginPlugin extends Gdn_Plugin
{
    // Prefix for provider fields on settings form:
    const CONFIG_PREFIX = 'Plugin.OASocialLogin.';
    const PROVIDER_PREFIX = 'Plugin.OASocialLogin.OASocialLogin.Provider__';

    private $useNewFunctions = false;

    /**
     * Plugin constructor
     *
     * This fires once per page load, during execution of bootstrap.php. It is a decent place to perform
     * one-time-per-page setup of the plugin object. Be careful not to put anything too strenuous in here
     * as it runs every page load and could slow down your forum.
     */
    public function __construct()
    {
        if (version_compare(APPLICATION_VERSION, '2.3', '>='))
        {
            $Definition = &gdn::locale()->LocaleContainer->Data;
            if (empty($Definition['OA_SOCIAL_LOGIN_SAVE']))
            {
                require_once __DIR__ . '/locale/en-CA/definitions.php';
            }
            if (version_compare(APPLICATION_VERSION, '2.5', '>='))
            {
                $this->useNewFunctions = true;
            }
        }
    }

    /*
     * Includes our library to each page.
     */
    public function Base_Render_Before($Sender)
    {
        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1')
        {
            return;
        }
        $subdomain = C(self::CONFIG_PREFIX . 'ApiSubdomain', '');
        $protocol = C(self::CONFIG_PREFIX . 'SSL', 1) == '1' ? 'https://' : 'http://';

        if (!empty($subdomain))
        {
            // Can't seem to pass parameters to AddJSFile(), so:
            $Sender->Head->AddString("
                    <script type='text/javascript'>
                    (function() { /* OneAll */
                        var oa = document.createElement('script');
                        oa.type = 'text/javascript'; oa.async = true;
                        oa.src = '${protocol}${subdomain}.api.oneall.com/socialize/library.js';
                        var s = document.getElementsByTagName('script')[0];
                        s.parentNode.insertBefore(oa, s);
                    })();
                    </script>");
        }
    }

    /*
     * Helper function to show OneAll icons.
     */
    private function insert_oa_login($caption, $element, $callback_uri)
    {
        $providers = implode(',', array_map(function ($p)
        {
            return "'" . $p . "'";
        }, C(self::CONFIG_PREFIX . 'Providers', array())));
        $host = Gdn_Url::webRoot(true);
        $host .= substr($host, -1, 1) === "/" ? "" : "/";

        return "<h4 class='login-title'>${caption}</h4>
				<div class='oneall_social_login_providers' id='${element}'></div>
				<!-- OneAll Social Login : http://www.oneall.com //-->
				<script type='text/javascript'>
					// <![CDATA[
					var _oneall = _oneall || [];
					_oneall.push(['social_login', 'set_providers', [${providers}]]);
					_oneall.push(['social_login', 'set_callback_uri', '${host}${callback_uri}']);
					_oneall.push(['social_login', 'set_custom_css_uri', (('https:' == document.location.protocol) ? 'https://secure' : 'http://public') + '.oneallcdn.com/css/api/socialize/themes/wordpress/default.css']);
					_oneall.push(['social_login', 'do_render_ui', '${element}']);
					// ]]>
				</script>";
    }

    /*
     * Display social login on main page.
     */
    public function Base_AfterSignInButton_Handler($Sender, $Args)
    {
        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1' || C(self::CONFIG_PREFIX . 'IndexPageEnable', 1) != '1')
        {
            return;
        }
        $caption = T(C(self::CONFIG_PREFIX . 'IndexPageCaption', ''));

        $redirect_uri = !empty(Gdn::Request()->PathAndQuery()) && Gdn::Request()->PathAndQuery() != "?" ? Gdn::Request()->PathAndQuery() : '';
        $callback_uri = 'index.php?p=/plugin/oneallsociallogin/signin&Target=' . $redirect_uri;
        echo $this->insert_oa_login($caption, 'oneall_social_login_signin_index', $callback_uri);
    }

    /*
     * Display social login on signin popup.
     */
    public function EntryController_SignIn_Handler($Sender, $Args)
    {
        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1' || C(self::CONFIG_PREFIX . 'LoginPageEnable', 1) != '1')
        {
            return;
        }
        $caption = T(C(self::CONFIG_PREFIX . 'LoginPageCaption', ''));
        $callback_uri = 'index.php?p=/plugin/oneallsociallogin/signin&Target=' . $Sender->RedirectTo();
        echo $this->insert_oa_login($caption, 'oneall_social_login_signin_popup', $callback_uri);
    }

    /*
     * Display social login on registration form.
     */
    public function EntryController_RegisterBeforePassword_Handler($Sender, $Args)
    {
        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1' || C(self::CONFIG_PREFIX . 'RegisterPageEnable', 1) != '1')
        {
            return;
        }
        $caption = T(C(self::CONFIG_PREFIX . 'RegistrationPageCaption', ''));
        $callback_uri = 'index.php?p=/plugin/oneallsociallogin/signin&Target=' . $Sender->RedirectTo();
        echo '<li>' . $this->insert_oa_login($caption, 'oneall_social_login_register', $callback_uri) . '</li>';
    }

    public function PluginController_OneallSocialLogin_Create($Sender)
    {
        if (APPLICATION_VERSION < "2.3")
        {
            $Sender->AddCssFile($this->GetResource('design/settings.css', false, false));
            $Sender->AddJsFile($this->GetResource('js/settings.js', false, false));
        }
        else
        {
            $Sender->AddCssFile('settings.css', 'plugins/OneallSocialLogin');
            $Sender->AddJsFile('settings.js', 'plugins/OneallSocialLogin');
        }

        $Sender->Title(T('OA_SOCIAL_LOGIN_TITLE'));
        $this->AddLinkToSideMenu($Sender, 'plugin/oneallsociallogin');
        $Sender->Form = new Gdn_Form();

        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    public function Controller_Index($Sender)
    {
        // Prevent non-admins from accessing this page
        $Sender->Permission('Garden.Settings.Manage');

        $oa_settings = array(
            self::CONFIG_PREFIX . 'Enable' => C(self::CONFIG_PREFIX . 'Enable', 1),
            self::CONFIG_PREFIX . 'Curl' => C(self::CONFIG_PREFIX . 'Curl', 1),
            self::CONFIG_PREFIX . 'SSL' => C(self::CONFIG_PREFIX . 'SSL', 1),
            self::CONFIG_PREFIX . 'ApiSubdomain' => C(self::CONFIG_PREFIX . 'ApiSubdomain', ''),
            self::CONFIG_PREFIX . 'ApiKey' => C(self::CONFIG_PREFIX . 'ApiKey', ''),
            self::CONFIG_PREFIX . 'ApiSecret' => C(self::CONFIG_PREFIX . 'ApiSecret', ''),
            self::CONFIG_PREFIX . 'IndexPageEnable' => C(self::CONFIG_PREFIX . 'IndexPageEnable', 1),
            self::CONFIG_PREFIX . 'IndexPageCaption' => T(C(self::CONFIG_PREFIX . 'IndexPageCaption')),
            self::CONFIG_PREFIX . 'LoginPageEnable' => C(self::CONFIG_PREFIX . 'LoginPageEnable', 1),
            self::CONFIG_PREFIX . 'LoginPageCaption' => T(C(self::CONFIG_PREFIX . 'LoginPageCaption')),
            self::CONFIG_PREFIX . 'RegistrationPageEnable' => C(self::CONFIG_PREFIX . 'RegistrationPageEnable', 1),
            self::CONFIG_PREFIX . 'RegistrationPageCaption' => T(C(self::CONFIG_PREFIX . 'RegistrationPageCaption')),
            self::CONFIG_PREFIX . 'AvatarsEnable' => C(self::CONFIG_PREFIX . 'AvatarsEnable', 1),
            self::CONFIG_PREFIX . 'Validate' => C(self::CONFIG_PREFIX . 'Validate', 0),
            self::CONFIG_PREFIX . 'LinkingEnable' => C(self::CONFIG_PREFIX . 'LinkingEnable', 1),
            self::CONFIG_PREFIX . 'Redirect' => C(self::CONFIG_PREFIX . 'Redirect', '')
        );
        foreach (SocialLogin::all_providers() as $id => $name)
        {
            $oa_settings[self::PROVIDER_PREFIX . $id] = in_array($id, C(self::CONFIG_PREFIX . 'Providers', array()));
        }
        // Load the configuration settings (or default values):
        $Sender->Form->SetData($oa_settings);

        if ($Sender->Form->AuthenticatedPostBack() === true)
        {
            $oa_settings_to_save = array();
            $providers_to_save = array();
            foreach ($Sender->Form->FormValues() as $k => $v)
            {
                // The form values contain vanilla data, so we filter them out:
                if (strpos($k, self::CONFIG_PREFIX) !== false)
                {
                    // we store the chosen providers in $providers_tosave:
                    $is_provider = (strpos($k, self::PROVIDER_PREFIX) !== false);
                    if ($is_provider && $v == 1)
                    {
                        $providers_to_save[] = substr($k, strlen(self::PROVIDER_PREFIX));
                    }
                    elseif (!$is_provider)
                    {
                        $oa_settings_to_save[$k] = $v;
                    }
                }
            }
            $oa_settings_to_save[self::CONFIG_PREFIX . 'Providers'] = $providers_to_save;
            SaveToConfig($oa_settings_to_save);
            $Sender->InformMessage(T('OA_SOCIAL_LOGIN_SETTINGS_UPDATED'));
        }
        $this->renderView($Sender, 'social-login-settings');
    }

    /*
     * Add social link to user profile.
     * The next 3 functions control the user profile.
     */

    public function ProfileController_AddProfileTabs_Handler($Sender)
    {
        $Sender->AddProfileTab('link', '/profile/link');
    }

    public function ProfileController_AfterAddSideMenu_Handler($Sender)
    {
        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1' || C(self::CONFIG_PREFIX . 'LinkingEnable', 1) != '1')
        {
            return;
        }
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Options', T('OA_SOCIAL_LOGIN_LINK_SIDEMENU'), 'profile/link', 'Garden.SignIn.Allow', array('class' => 'Popup'));
    }

    public function ProfileController_Link_Create($Sender, $Args)
    {
        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1' || C(self::CONFIG_PREFIX . 'LinkingEnable', 1) != '1')
        {
            return;
        }

        $Sender->Title(T('OA_SOCIAL_LOGIN_LINK_SIDEMENU'));
        $Sender->Permission('Garden.SignIn.Allow');
        // this is required to correctly display the profile template:
        $Sender->GetUserInfo();

        $providers = implode(',', array_map(function ($p)
        {
            return "'" . $p . "'";
        }, C(self::CONFIG_PREFIX . 'Providers', array())));

        $oasl = new SocialLogin();
        $user_token = $oasl->get_user_token_for_user_id(Gdn::Session()->UserID);
        $callback_uri = Url('index.php?p=/plugin/oneallsociallogin/signin&Target=' . Gdn::Request()->PathAndQuery(), true);

        $error = Gdn::Request()->Get('error');

        $Sender->SetData(array(
            'providers' => $providers,
            'user_token' => $user_token,
            'callback_uri' => $callback_uri,
            'error' => $error
        ));

        $this->renderView($Sender, 'social-login-linking');
    }

    public function ProfileController_Link_error_Create($Sender, $Args)
    {
        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1' || C(self::CONFIG_PREFIX . 'LinkingEnable', 1) != '1')
        {
            return;
        }

        $Sender->Title(T('OA_SOCIAL_LOGIN_LINK_SIDEMENU'));
        $Sender->Permission('Garden.SignIn.Allow');
        // this is required to correctly display the profile template:
        $Sender->GetUserInfo();

        $providers = implode(',', array_map(function ($p)
        {
            return "'" . $p . "'";
        }, C(self::CONFIG_PREFIX . 'Providers', array())));

        $oasl = new SocialLogin();
        $user_token = $oasl->get_user_token_for_user_id(Gdn::Session()->UserID);

        $callback_uri = Url('index.php?p=/plugin/oneallsociallogin/signin&Target=' . preg_replace('/\_error\?error_message\=[a-zA-Z\+]*/', '', Gdn::Request()->PathAndQuery()), true);

        $error = Gdn::Request()->Get('error_message');

        $Sender->SetData(array(
            'providers' => $providers,
            'user_token' => $user_token,
            'callback_uri' => $callback_uri,
            'error' => $error
        ));

        $this->renderView($Sender, 'social-login-linking');
    }

    /*
     * Add a link to the dashboard menu
     */
    public function Base_GetAppSettingsMenuItems_Handler($Sender)
    {
        $Menu = &$Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Add-ons', T('OA_SOCIAL_LOGIN_TITLE'), 'plugin/oneallsociallogin', 'Garden.AdminUser.Only');
    }

    /*
     * Check API Settings - Ajax Call
     */
    public function Controller_Autodetect($Sender)
    {
        $oasl = new SocialLogin();

        // Check CURL HTTPS - Port 443.
        if ($oasl->check_curl(true) === true)
        {
            $status_message = 'success|curl_443|' . sprintf(T('OA_SOCIAL_LOGIN_API_DETECT_CURL'), 443);
        }
        // Check CURL HTTP - Port 80.
        elseif ($oasl->check_curl(false) === true)
        {
            $status_message = 'success|curl_80|' . sprintf(T('OA_SOCIAL_LOGIN_API_DETECT_CURL'), 80);
        }
        // Check FSOCKOPEN HTTPS - Port 443.
        elseif ($oasl->check_fsockopen(true) == true)
        {
            $status_message = 'success|fsockopen_443|' . sprintf(T('OA_SOCIAL_LOGIN_API_DETECT_FSOCKOPEN'), 443);
        }
        // Check FSOCKOPEN HTTP - Port 80.
        elseif ($oasl->check_fsockopen(false) == true)
        {
            $status_message = 'success|fsockopen_80|' . sprintf(T('OA_SOCIAL_LOGIN_API_DETECT_FSOCKOPEN'), 443);
        }
        // No working handler found.
        else
        {
            $status_message = 'error|none|' . T('OA_SOCIAL_LOGIN_API_DETECT_NONE');
        }
        // Output for AJAX.
        die($status_message);
    }

    /*
     * Check API Settings - Ajax Call
     */
    public function Controller_VerifyApi($Sender)
    {
        $oasl = new SocialLogin();

        // Read arguments, plus some parsing.
        $api_subdomain = trim(strtolower(Gdn::request()->Post('api_subdomain', '')));
        $api_key = trim(Gdn::request()->Post('api_key', ''));
        $api_secret = trim(Gdn::request()->Post('api_secret', ''));
        $api_connection_handler = Gdn::request()->Post('api_connection_handler', '');
        $api_connection_use_https = (Gdn::request()->Post('api_connection_use_https', '0') == '1' ? true : false);

        // Init status message.
        $status_message = null;

        // Check if all fields have been filled out.
        if (strlen($api_subdomain) == 0 || strlen($api_key) == 0 || strlen($api_secret) == 0)
        {
            $status_message = 'error_|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_FILL_OUT');
        }
        else
        {
            // Check the handler
            $api_connection_handler = ($api_connection_handler != 'fsockopen' ? 'curl' : 'fsockopen');

            // FSOCKOPEN
            if ($api_connection_handler == 'fsockopen')
            {
                if (!$oasl->check_fsockopen($api_connection_use_https))
                {
                    $status_message = 'error|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_USE_AUTO');
                }
            }
            // CURL
            else
            {
                if (!$oasl->check_curl($api_connection_use_https))
                {
                    $status_message = 'error|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_USE_AUTO');
                }
            }
            // No errors until now.
            if (empty($status_message))
            {
                // The full domain has been entered.
                if (preg_match("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
                {
                    $api_subdomain = $matches[1];
                }
                // Check format of the subdomain.
                if (!preg_match("/^[a-z0-9\-]+$/i", $api_subdomain))
                {
                    $status_message = 'error|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_SUBDOMAIN_WRONG');
                }
                else
                {
                    // Construct full API Domain.
                    $api_domain = $api_subdomain . '.api.oneall.com';
                    $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_domain . '/tools/ping.json';

                    // API Credentialls.
                    $api_credentials = array();
                    $api_credentials['api_key'] = $api_key;
                    $api_credentials['api_secret'] = $api_secret;

                    // Try to establish a connection.
                    $result = $oasl->do_api_request($api_connection_handler, $api_resource_url, $api_credentials);

                    // Parse result.
                    if (is_object($result) && property_exists($result, 'http_code') && property_exists($result, 'http_data'))
                    {
                        switch ($result->http_code)
                        {
                            // Connection successfull.
                            case 200:
                                $status_message = 'success|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_OK');
                                break;

                            // Authentication Error.
                            case 401:
                                $status_message = 'error|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_KEYS_WRONG');
                                break;

                            // Wrong Subdomain.
                            case 404:
                                $status_message = 'error|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_SUBDOMAIN_WRONG');
                                break;

                            // Other error.
                            default:
                                $status_message = 'error|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_CHECK_COM');
                                break;
                        }
                    }
                    else
                    {
                        $status_message = 'error|' . T('OA_SOCIAL_LOGIN_API_CREDENTIALS_CHECK_COM');
                    }
                }
            }
        }
        // Output for Ajax.
        die($status_message);
    }

    /*
     * Social Login callback handler.
     */
    public function Controller_Signin($Sender)
    {
        $subdomain = C(self::CONFIG_PREFIX . 'ApiSubdomain', '');
        $api_credentials = array();
        $api_credentials['api_key'] = C(self::CONFIG_PREFIX . 'ApiKey', '');
        $api_credentials['api_secret'] = C(self::CONFIG_PREFIX . 'ApiSecret', '');

        if (empty($subdomain) || empty($api_credentials['api_key']) || empty($api_credentials['api_secret']))
        {
            // TODO figure out best action to take here:
            return;
        }

        $oa_action = Gdn::Request()->Post('oa_action');
        $oa_login_token = Gdn::Request()->Post('oa_social_login_token');
        $oa_connection_token = Gdn::Request()->Post('connection_token');

        if (C(self::CONFIG_PREFIX . 'Enable', 1) != '1' || empty($oa_action) || empty($oa_connection_token))
        {
            // TODO figure out best action to take here: should not happen, as checked when rendering pages.
            return;
        }
        $use_curl = C(self::CONFIG_PREFIX . 'Curl', 1);
        $use_ssl = C(self::CONFIG_PREFIX . 'SSL', 1);
        $linking = C(self::CONFIG_PREFIX . 'LinkingEnable', 1);
        $validation = C(self::CONFIG_PREFIX . 'Validate', 0);
        $avatar = C(self::CONFIG_PREFIX . 'AvatarsEnable', 1);
        $redirect = C(self::CONFIG_PREFIX . 'Redirect', '');

        $oasl = new SocialLogin();
        $to_validate = $oasl->handle_callback(
            $oa_action,
            $oa_login_token,
            $oa_connection_token,
            $use_curl,
            $use_ssl,
            $subdomain,
            $api_credentials,
            $linking,
            $validation,
            $avatar,
            $redirect);
        if (is_array($to_validate))
        {
            $Sender->Form = new Gdn_Form(); // TODO maybe not needed.
            $to_validate['val_id'] = $oasl->set_validation_data($to_validate);
            $Sender = $this->set_validation_fields($Sender, $to_validate);
            $this->renderView($Sender, 'oa_social_login_validate');
        }
    }

    protected function set_validation_fields($sender, $fields)
    {
        $sender->Form->SetData($fields);
        $sender->Form->SetFormValue('user_email', $fields['user_email']);
        $sender->Form->SetFormValue('user_login', $fields['user_login']);
        $sender->Form->AddHidden('val_id', $fields['val_id']);

        return $sender;
    }

    /*
     * Validation Form
     */
    public function Controller_Validate($Sender)
    {
        $form_values = array(
            'user_email' => $Sender->Form->GetValue('user_email'),
            'user_login' => $Sender->Form->GetValue('user_login'),
            'val_id' => $Sender->Form->GetValue('val_id')
        );
        $oasl = new SocialLogin();
        $oa_profile = $oasl->get_validation_data($form_values['val_id']);
        if ($oa_profile === false)
        {
            SafeRedirect(Url(Gdn::Router()->GetDestination('DefaultController'), true));
        }
        $to_validate = array_merge($form_values, $oa_profile);
        if ($Sender->Form->IsPostBack() == true)
        {
            // Verify new user submitted data:
            // TODO explore vanilla validation: as in $Valid = Gdn_Validation::ValidateRule ($to_validate ['user_email'], 'Email', 'function:ValidateEmail');

            $valid = true;
            if (empty($to_validate['user_login']))
            {
                $to_validate['user_login'] = $to_validate['identity_provider'] . 'User';
                $valid = false;
            }
            if ($oasl->get_user_id_by_username($to_validate['user_login']) !== false)
            {
                $i = 1;
                $user_login_tmp = $to_validate['user_login'] . ($i);
                while ($oasl->get_user_id_by_username($user_login_tmp) !== false)
                {
                    $user_login_tmp = $to_validate['user_login'] . ($i++);
                }
                $to_validate['user_login'] = $user_login_tmp;
                $valid = false;
            }
            if (empty($to_validate['user_email']))
            {
                $Sender->Form->AddError('OA_SOCIAL_LOGIN_VALIDATION_FORM_EMAIL_NONE_EXPLAIN', 'user_email');
                $valid = false;
            }
            if ($oasl->get_user_id_by_email($to_validate['user_email']) !== false)
            {
                $to_validate['user_email'] = '';
                $Sender->Form->AddError('OA_SOCIAL_LOGIN_VALIDATION_FORM_EMAIL_EXISTS_EXPLAIN', 'user_email');
                $valid = false;
            }
            if ($valid)
            {
                $avatar = C(self::CONFIG_PREFIX . 'AvatarsEnable', 1);
                $redirect = C(self::CONFIG_PREFIX . 'Redirect', '');
                $to_validate['redirect'] = empty($redirect) ? Url($to_validate['redirect'], true) : $redirect;
                $oasl->delete_validation_data($to_validate['val_id']);
                $oasl->social_login_resume_handle_callback($to_validate, $avatar);
            }
        }
        $Sender = $this->set_validation_fields($Sender, $to_validate);
        $this->renderView($Sender, 'oa_social_login_validate');
    }

    /*
     * Plugin setup
     */
    public function Setup()
    {
        SaveToConfig(array(
            self::CONFIG_PREFIX . 'Enable' => '1',
            self::CONFIG_PREFIX . 'Curl' => '1',
            self::CONFIG_PREFIX . 'SSL' => '1',
            self::CONFIG_PREFIX . 'IndexPageEnable' => '1',
            self::CONFIG_PREFIX . 'IndexPageCaption' => 'OA_SOCIAL_LOGIN_INDEX_PAGE_CAPTION_VALUE',
            self::CONFIG_PREFIX . 'LoginPageEnable' => '1',
            self::CONFIG_PREFIX . 'LoginPageCaption' => 'OA_SOCIAL_LOGIN_LOGIN_PAGE_CAPTION_VALUE',
            self::CONFIG_PREFIX . 'RegistrationPageEnable' => '1',
            self::CONFIG_PREFIX . 'RegistrationPageCaption' => 'OA_SOCIAL_LOGIN_REGISTRATION_PAGE_CAPTION_VALUE',
            self::CONFIG_PREFIX . 'AvatarsEnable' => '1',
            self::CONFIG_PREFIX . 'Validate' => '0',
            self::CONFIG_PREFIX . 'LinkingEnable' => '1',
            self::CONFIG_PREFIX . 'Redirect' => '',
            self::CONFIG_PREFIX . 'Providers' => array()
        ));

        Gdn::Structure()
            ->Table('oasl_identity')
            ->PrimaryKey('oasl_identity_id')
            ->Column('oasl_user_id', 'uint', 0)
            ->Column('identity_token', 'varchar(255)', '')
            ->Column('identity_provider', 'varchar(255)', '')
            ->Column('num_logins', 'uint', 0)
            ->Column('date_added', 'timestamp', 0)
            ->Column('date_updated', 'timestamp', 0)
            ->Set();

        Gdn::Structure()
            ->Table('oasl_user')
            ->PrimaryKey('oasl_user_id')
            ->Column('user_id', 'uint', 0)
            ->Column('user_token', 'varchar(255)', '')
            ->Column('date_added', 'timestamp', 0)
            ->Set();

        Gdn::Structure()
            ->Table('oasl_validation')
            ->PrimaryKey('oasl_validation_id')
            ->Column('validation_id', 'char(32)', '')
            ->Column('user_token', 'varchar(255)', '')
            ->Column('identity_token', 'varchar(255)', '')
            ->Column('identity_provider', 'varchar(255)', '')
            ->Column('redirect', 'varchar(255)', '')
            ->Column('user_thumbnail', 'varchar(255)', '')
            ->Column('date_creation', 'timestamp', 0)
            ->Set();
    }

    /*
     * Social Login Plugin cleanup
     */
    public function OnDisable()
    {
        // Keep configuration values, and tables in case plugin is re-enabled later.
    }

    public function renderView($Sender, $Viewname)
    {
        if ($this->useNewFunctions)
        {
            // New method (only in 2.5+)
            $Sender->render($Viewname, '', 'plugins/OneallSocialLogin/'); //Note: Viewname specified without ".php"
        }
        else
        {
            $Sender->render($this->getView($Viewname . 'php')); //For 2.3 and below add the php extention
        }
    }

    private function addLinkToSideMenu($Sender, $Path)
    {
        if ($this->useNewFunctions)
        {
            $Sender->setHighlightRoute($Path);
        }
        else
        {
            $Sender->addSideMenu($Path);
        }
    }
}
