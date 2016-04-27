<?php
/**
 * @package   	OneAll Social Login
 * @copyright 	Copyright 2013-2015 http://www.oneall.com - All rights reserved.
 * @license   	GNU/GPL 2 or later
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 */

class SocialLogin
{
	const USER_AGENT = 'SocialLogin/1.4 Vanilla/2.1.x (+http://www.oneall.com/)';

	/*
	* Helper function that returns a non-deleted Vanilla user
	* returns FALSE or the User array or object (DATASET_TYPE_OBJECT)
	*/
	public function getValidUser ($user_id, $ret_type = DATASET_TYPE_ARRAY)
	{
		$UserModel = new UserModel();
		$result = $UserModel->getID ($user_id, $ret_type);
		
		return GetValue ('Deleted', $result) === '1' ? FALSE : $result;  // $result can still be false.
	}

	
	/*
	 * Counts a login for the identity token
	 */
	public function incr_login_count_identity_token ($identity_token)
	{
		// Update the counter for the given identity_token.
		Gdn::Sql ()
		->Update ('oasl_identity')
		->Set ('date_updated', 'NOW()', FALSE)
		->Set ('num_logins', 'num_logins+1', FALSE)
		->Where ('identity_token', $identity_token)
		->Put ();
	}


	/*
	 * Unlinks the identity token.
	 * Eventually clean the oasl_user table.
	 */
	public function unlink_identity_token ($identity_token)
	{
		// Retrieve the oasl_user_id from the identity_token, using the oasl_identity table.
		// Delete superfluous user_token.
		$result = Gdn::Sql ()
		->Select ('oasl_user_id')
		->From ('oasl_identity')
		->Where ('identity_token', $identity_token)
		->Get ()
		->Result (DATASET_TYPE_ARRAY);
		
		if (empty ($result))
		{
			return FALSE;
		}
		foreach ($result as $row) 
		{
			$user_id = GetValue ('oasl_user_id', $row);
		}

		Gdn::Sql ()
		->Delete ('oasl_identity', array ('identity_token' => $identity_token));
		
		$result = Gdn::Sql ()
		->Select ('oasl_user_id')
		->From ('oasl_identity')
		->Where ('oasl_user_id', intval ($user_id))
		->Get ()
		->Result (DATASET_TYPE_ARRAY);
		
		if (empty ($result))
		{
			Gdn::Sql ()
			->Delete ('oasl_user', array ('oasl_user_id' => intval ($user_id)));
		}
		return TRUE;
	}


	/*
	 * Links the user/identity tokens to a user
	 */
	public function link_tokens_to_user_id ($user_id, $user_token, $identity_token, $identity_provider)
	{
		// Make sure that that the user exists.
		$van_user = $this->getValidUser ($user_id);

		// The user does not exist.
		if ($van_user === FALSE)
		{
			return FALSE;
		}
		
		$oasl_user_id = FALSE;

		// Delete superfluous user_token.
		$result = Gdn::Sql ()
		->Select ('oasl_user_id')
		->From ('oasl_user')
		->Where ('user_id', intval ($user_id))
		->Where ('user_token <>', $user_token)
		->Get ()
		->Result (DATASET_TYPE_ARRAY);

		foreach ($result as $row) {
			// Delete the wrongly linked user_token.
			Gdn::Sql ()
			->Delete ('oasl_user', array ('oasl_user_id' => GetValue ('oasl_user_id', $row)));

			// Delete the wrongly linked identity_token.
			Gdn::Sql ()
			->Delete ('oasl_identity', array ('oasl_user_id' => GetValue ('oasl_user_id', $row)));
		}

		// Read the entry for the given user_token.
		$result = Gdn::Sql ()
		->Select ('oasl_user_id, user_id')
		->From ('oasl_user')
		->Where ('user_token', $user_token)
		->Get ()
		->Result (DATASET_TYPE_ARRAY);

		foreach ($result as $row)
		{
			$oasl_user_id = GetValue ('oasl_user_id', $row);
		}

		if ($oasl_user_id === FALSE)
		{
			// The user_token either does not exist or has been reset.
			// Add new link.
			$result = Gdn::Sql ()
			->Set('date_added', 'NOW()', FALSE)
			->Insert ('oasl_user', array ('user_id' => intval ($user_id),'user_token' => $user_token));

			// Identifier of the newly created user_token entry.
			$result = Gdn::Sql ()
			->Select ('oasl_user_id')
			->From ('oasl_user')
			->Where ('user_id', $user_id)
			->Where ('user_token', $user_token)
			->Get ()
			->Result (DATASET_TYPE_ARRAY);

			foreach ($result as $row)
			{
				$oasl_user_id = GetValue ('oasl_user_id', $row);
			}
		}

		// Read the entry for the given identity_token.
		$result = Gdn::Sql ()
		->Select ('oasl_identity_id, oasl_user_id, identity_token')
		->From ('oasl_identity')
		->Where ('identity_token', $identity_token)
		->Get ()
		->Result (DATASET_TYPE_ARRAY);

		$oasl_identity_id = FALSE;

		foreach ($result as $row)
		{
			$oasl_tmp_identity_id = GetValue ('oasl_identity_id', $row);
			$oasl_tmp_user_id = GetValue('oasl_user_id', $row);
			// Delete the wrongly linked identity_token.
			if ($oasl_tmp_user_id != $oasl_user_id)
			{
				Gdn::Sql ()
				->Delete ('oasl_identity', array (
						'oasl_identity_id' => intval($oasl_tmp_identity_id),
						'oasl_user_id' => intval($oasl_tmp_user_id))
				);
			}
			else
			{
				$oasl_identity_id = $oasl_tmp_identity_id;
			}
		}

		// The identity_token either does not exist or has been reset.
		if ($oasl_identity_id === FALSE)
		{
			// Add new link.
			$result = Gdn::Sql ()
			->Set('date_added', 'NOW()', FALSE)
			->Set('date_updated', 'NOW()', FALSE)
			->Insert ('oasl_identity', array (
					'oasl_user_id' => intval ($oasl_user_id),
					'identity_token' => $identity_token,
					'identity_provider' => $identity_provider,
					'num_logins' => 0
			));

			// Identifier of the newly created identity_token entry.
			$result = Gdn::Sql ()
			->Select ('oasl_identity_id')
			->From ('oasl_identity')
			->Where ('oasl_user_id', intval ($oasl_user_id))
			->Where ('identity_token', $identity_token)
			->Get ()
			->Result (DATASET_TYPE_ARRAY);

			foreach ($result as $row)
			{
				$oasl_user_id = GetValue ('oasl_identity_id', $row);
			}
		}
		// Done.
		return TRUE;
	}

	
	/*
	 * Insert temporary login and email for validation in oasl_validation table
	 */
	public function set_validation_data ($validation)
	{
		$val_key = md5 ($validation ['user_token'] . strval (rand ()));
		$this->delete_validation_data ($val_key);
		
		$result = Gdn::Sql ()
		->Set('date_creation', 'NOW()', FALSE)
		->Insert ('oasl_validation', array (
				'validation_id' => $val_key,
				'user_token' => $validation['user_token'],
				'identity_token' => $validation['identity_token'],
				'identity_provider' => $validation['identity_provider'],
				'redirect' => $validation['redirect'],
				'user_thumbnail' => $validation['user_thumbnail']
		));
		return $val_key;
	}

	
	/*
	 * Retrieve temporary login, email for validation
	 */
	public function get_validation_data ($validation_id)
	{
		$result = Gdn::Sql ()
		->Select ('*')
		->From ('oasl_validation')
		->Where ('validation_id', $validation_id)
		->Get ()
		->Result (DATASET_TYPE_ARRAY);

		if (count ($result) !== 1) 
		{
			 return FALSE;
		}
		return $result[0];
	}

	
	/*
	 * Delete temporary login, email for validation
	 */
	public function delete_validation_data ($validation_id)
	{
		Gdn::Sql ()
		->Where ('validation_id', $validation_id)
		->OrWhere ('date_creation <', 'NOW() - INTERVAL 120 MINUTE')
		->Delete ('oasl_validation');
	}


	/*
	 * Get the user_id for a given email address.
	 */
	public function get_user_id_by_email ($email)
	{
		// Read the user_id for this email address.
		$UserModel = new UserModel();
		$result = $UserModel->GetByEmail($email);

		return GetValue ('Deleted', $result) === '1' ? FALSE : GetValue ('UserID', $result);
	}
	
	
	/*
	 * Get the user_id for a given a username.
	 */
	public function get_user_id_by_username ($user_login)
	{
		// Read the user_id for this login
		$UserModel = new UserModel();
		$result = $UserModel->GetByUsername($user_login, DATASET_TYPE_ARRAY);

		return GetValue ('Deleted', $result) === '1' ? FALSE : GetValue ('UserID', $result);
	}

	
	/*
	 * Returns the user_id for a given token.
	 */
	protected function get_user_id_for_user_token ($user_token)
	{
		// Make sure it is not empty.
		$user_token = trim ($user_token);
		if (empty ($user_token))
		{
			return FALSE;
		}

		// Read the user_id for this user_token.
		$result = Gdn::Sql ()
		->Select ('oasl_user_id, user_id')
		->From ('oasl_user')
		->Where ('user_token', $user_token)
		->Get ()
		->Result (DATASET_TYPE_ARRAY);

		$user_id = FALSE;
		$oasl_user_id = FALSE;
		foreach ($result as $users) {
			$user_id = GetValue ('user_id', $users);  // defaults to FALSE
			$oasl_user_id = GetValue ('oasl_user_id', $users);
		}

		// The user_token exists
		if ($user_id !== FALSE && $oasl_user_id !== FALSE)
		{
			// If the user account exists, return it's identifier.
			if ($this->getValidUser ($user_id) !== FALSE)
			{
				return $user_id;
			}

			// Delete the wrongly linked user_token.
			Gdn::Sql ()
			->Delete ('oasl_user', array ('user_token' => $user_token));

			// Delete the wrongly linked identity_token.
			Gdn::Sql ()
			->Delete ('oasl_identity', array ('oasl_user_id' => intval ($oasl_user_id)));
		}

		// No entry found.
		return FALSE;
	}


	/*
	 * Get the user_token from a user_id
	 */
	public function get_user_token_for_user_id ($user_id)
	{
		// Read the user_id for this login_token
		$result = Gdn::Sql()
		->Select('user_token')
		->From('oasl_user')
		->Where('user_id', intval ($user_id))
		->Get()
		->Result (DATASET_TYPE_ARRAY);
		
		foreach ($result as $row)
		{
			$user_token = GetValue ('user_token', $row);
			if ($user_token !== FALSE)
			{
				return $user_token;
			}
		}
		// Not found
		return FALSE;
	}


	/*
	 * Get the user data for a user_id
	 */
	protected function get_user_data_by_user_id ($user_id)
	{
		// Read the user data.
		$vanuser = $this->getValidUser ($user_id);
		return $vanuser !== FALSE ? $vanuser : FALSE;
	}


	/*
	 * Returns the list of available social networks.
	 */
	public static function all_providers()
	{
		return array (
				'amazon' => 'Amazon',
				'blogger' => 'Blogger',
				'disqus' => 'Disqus',
				'draugiem' => 'Draugiem',
				'facebook' => 'Facebook',
				'foursquare' => 'Foursquare',
				'github' => 'Github.com',
				'google' => 'Google',
				'instagram' => 'Instagram',
				'linkedin' => 'LinkedIn',
				'livejournal' => 'LiveJournal',
				'mailru' => 'Mail.ru',
				'odnoklassniki' => 'Odnoklassniki',
				'openid' => 'OpenID',
				'paypal' => 'PayPal',
				'reddit' => 'Reddit',
				'skyrock' => 'Skyrock.com',
				'stackexchange' => 'StackExchange',
				'steam' => 'Steam',
				'twitch' => 'Twitch.tv',
				'twitter' => 'Twitter',
				'vimeo' => 'Vimeo',
				'vkontakte' => 'VKontakte',
				'windowslive' => 'Windows Live',
				'wordpress' => 'WordPress.com',
				'yahoo' => 'Yahoo',
				'youtube' => 'YouTube',
				'battlenet' => 'BattleNet',
		);
	}
	

	/*
	 * Returns a list of disabled PHP functions.
	 */
	public static function get_php_disabled_functions ()
	{
		$disabled_functions = trim (ini_get ('disable_functions'));
		if (strlen ($disabled_functions) == 0)
		{
			$disabled_functions = array ();
		}
		else
		{
			$disabled_functions = explode (',', $disabled_functions);
			$disabled_functions = array_map ('trim', $disabled_functions);
		}
		return $disabled_functions;
	}


	/*
	 * Sends an API request by using the given handler.
	 */
	public function do_api_request ($handler, $url, $options = array(), $timeout = 30)
	{
		// FSOCKOPEN
		if ($handler == 'fsockopen')
		{
			return $this->fsockopen_request ($url, $options, $timeout);
		}
		// CURL
		else
		{

			return $this->curl_request ($url, $options, $timeout);
		}
	}


	/*
	 * Checks if CURL can be used.
	 */
	public function check_curl ($secure = TRUE)
	{
		if (in_array ('curl', get_loaded_extensions ()) && function_exists ('curl_exec') && ! in_array ('curl_exec', self::get_php_disabled_functions ()))
		{
			$result = $this->curl_request (($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
			if (is_object ($result) && property_exists ($result, 'http_code') && $result->http_code == 200)
			{
				if (property_exists ($result, 'http_data'))
				{
					if (strtolower ($result->http_data) == 'ok')
					{
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}


	/*
	 * Checks if fsockopen can be used.
	 */
	public function check_fsockopen ($secure = TRUE)
	{
		if (function_exists ('fsockopen') && ! in_array ('fsockopen', $this->get_php_disabled_functions ()))
		{
			$result = $this->fsockopen_request (($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
			if (is_object ($result) && property_exists ($result, 'http_code') && $result->http_code == 200)
			{
				if (property_exists ($result, 'http_data'))
				{
					if (strtolower ($result->http_data) == 'ok')
					{
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}


	/*
	 * Sends a CURL request.
	 */
	protected function curl_request ($url, $options = array(), $timeout = 30, $num_redirects = 0)
	{
		// Store the result
		$result = new \stdClass ();

		// Send request
		$curl = curl_init ();
		curl_setopt ($curl, CURLOPT_URL, $url);
		curl_setopt ($curl, CURLOPT_HEADER, 1);
		curl_setopt ($curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt ($curl, CURLOPT_REFERER, $url);
		curl_setopt ($curl, CURLOPT_VERBOSE, 0);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($curl, CURLOPT_USERAGENT, self::USER_AGENT);

		// Does not work in PHP Safe Mode, we manually follow the locations if necessary.
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 0);

		// BASIC AUTH?
		if (isset ($options ['api_key']) && isset ($options ['api_secret']))
		{
			curl_setopt ($curl, CURLOPT_USERPWD, $options ['api_key'] . ":" . $options ['api_secret']);
		}

		// Make request
		if (($response = curl_exec ($curl)) !== FALSE)
		{
			// Get Information
			$curl_info = curl_getinfo ($curl);

			// Save result
			$result->http_code = $curl_info ['http_code'];
			$result->http_headers = preg_split ('/\r\n|\n|\r/', trim (substr ($response, 0, $curl_info ['header_size'])));
			$result->http_data = trim (substr ($response, $curl_info ['header_size']));
			$result->http_error = null;

			// Check if we have a redirection header
			if (in_array ($result->http_code, array (301, 302)) && $num_redirects < 4)
			{
				// Make sure we have http headers
				if (is_array ($result->http_headers))
				{
					// Header found ?
					$header_found = FALSE;

					// Loop through headers.
					while (! $header_found && (list (, $header) = each ($result->http_headers)))
					{
						// Try to parse a redirection header.
						if (preg_match ("/(Location:|URI:)[^(\n)]*/", $header, $matches))
						{
							// Sanitize redirection url.
							$url_tmp = trim (str_replace ($matches [1], "", $matches [0]));
							$url_parsed = parse_url ($url_tmp);
							if (! empty ($url_parsed))
							{
								// Header found!
								$header_found = TRUE;

								// Follow redirection url.
								$result = $this->curl_request ($url_tmp, $options, $timeout, $num_redirects + 1);
							}
						}
					}
				}
			}
		}
		else
		{
			$result->http_code = - 1;
			$result->http_data = null;
			$result->http_error = curl_error ($curl);
		}

		// Done
		return $result;
	}


	/*
	 * Sends an fsockopen request.
	 */
	protected function fsockopen_request ($url, $options = array(), $timeout = 30, $num_redirects = 0)
	{
		// Store the result
		$result = new \stdClass ();

		// Make that this is a valid URL
		if (($uri = parse_url ($url)) == FALSE)
		{
			$result->http_code = - 1;
			$result->http_data = null;
			$result->http_error = 'invalid_uri';
			return $result;
		}

		// Make sure we can handle the schema
		switch ($uri ['scheme'])
		{
			case 'http':
				$port = (isset ($uri ['port']) ? $uri ['port'] : 80);
				$host = ($uri ['host'] . ($port != 80 ? ':' . $port : ''));
				$fp = @fsockopen ($uri ['host'], $port, $errno, $errstr, $timeout);
				break;

			case 'https':
				$port = (isset ($uri ['port']) ? $uri ['port'] : 443);
				$host = ($uri ['host'] . ($port != 443 ? ':' . $port : ''));
				$fp = @fsockopen ('ssl://' . $uri ['host'], $port, $errno, $errstr, $timeout);
				break;

			default:
				$result->http_code = - 1;
				$result->http_data = null;
				$result->http_error = 'invalid_schema';
				return $result;
				break;
		}

		// Make sure the socket opened properly
		if (! $fp)
		{
			$result->http_code = - $errno;
			$result->http_data = null;
			$result->http_error = trim ($errstr);
			return $result;
		}

		// Construct the path to act on
		$path = (isset ($uri ['path']) ? $uri ['path'] : '/');
		if (isset ($uri ['query']))
		{
			$path .= '?' . $uri ['query'];
		}

		// Create HTTP request
		$defaults = array ();
		$defaults ['Host'] = 'Host: ' . $host;
		$defaults ['User-Agent'] = 'User-Agent: ' . self::USER_AGENT;

		// BASIC AUTH?
		if (isset ($options ['api_key']) && isset ($options ['api_secret']))
		{
			$defaults ['Authorization'] = 'Authorization: Basic ' . base64_encode ($options ['api_key'] . ":" . $options ['api_secret']);
		}

		// Build and send request
		$request = 'GET ' . $path . " HTTP/1.0\r\n";
		$request .= implode ("\r\n", $defaults);
		$request .= "\r\n\r\n";
		fwrite ($fp, $request);

		// Fetch response
		$response = '';
		while (! feof ($fp))
		{
			$response .= fread ($fp, 1024);
		}

		// Close connection
		fclose ($fp);

		// Parse response
		list ($response_header, $response_body) = explode ("\r\n\r\n", $response, 2);

		// Parse header
		$response_header = preg_split ("/\r\n|\n|\r/", $response_header);
		list ($header_protocol, $header_code, $header_status_message) = explode (' ', trim (array_shift ($response_header)), 3);

		// Set result
		$result->http_code = $header_code;
		$result->http_headers = $response_header;
		$result->http_data = $response_body;

		// Make sure we we have a redirection status code
		if (in_array ($result->http_code, array (301, 302)) && $num_redirects <= 4)
		{
			// Make sure we have http headers
			if (is_array ($result->http_headers))
			{
				// Header found?
				$header_found = FALSE;

				// Loop through headers.
				while (! $header_found && (list (, $header) = each ($result->http_headers)))
				{
					// Check for location header
					if (preg_match ("/(Location:|URI:)[^(\n)]*/", $header, $matches))
					{
						// Found
						$header_found = TRUE;

						// Clean url
						$url_tmp = trim (str_replace ($matches [1], "", $matches [0]));
						$url_parsed = parse_url ($url_tmp);

						// Found
						if (! empty ($url_parsed))
						{
							$result = $this->fsockopen_request ($url_tmp, $options, $timeout, $num_redirects + 1);
						}
					}
				}
			}
		}

		// Done
		return $result;
	}


	/*
	 * Inverts CamelCase -> camel_case.
	 */
	public static function undo_camel_case ($input)
	{
		$result = $input;

		if (preg_match_all ('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches))
		{
			$ret = $matches [0];

			foreach ($ret as &$match)
			{
				$match = ($match == strtoupper ($match) ? strtolower ($match) : lcfirst ($match));
			}

			$result = implode ('_', $ret);
		}

		return $result;
	}


	/*
	 * Extracts the social network data from a result-set returned by the OneAll API.
	 */
	protected function extract_social_network_profile ($reply)
	{
		// Check API result.
		//if (is_object ($reply) && property_exists ($reply, 'http_code') && $reply->http_code == 200 && property_exists ($reply, 'http_data'))
		// TODO check if this is sufficient:
		if (property_exists ($reply, 'http_data'))
		{
			// Decode the social network profile Data.
			$social_data = json_decode ($reply->http_data);

			// Make sur that the data has beeen decoded properly
			if (is_object ($social_data))
			{
				// Provider may report an error inside message:
				if (! empty ($social_data->response->result->status->flag) && $social_data->response->result->status->code >= 400) {
					LogMessage(__FILE__, __LINE__, __CLASS__, __METHOD__,
							$social_data->response->result->status->info . ' (' . $social_data->response->result->status->code . ')');
					return FALSE;
				}

				// Container for user data
				$data = array ();

				// Parse plugin data.
				if (isset ($social_data->response->result->data->plugin))
				{
					// Plugin.
					$plugin = $social_data->response->result->data->plugin;

					//Add plugin data.
					$data ['plugin_key'] = $plugin->key;
					$data ['plugin_action'] = (isset ($plugin->data->action) ? $plugin->data->action : null);
					$data ['plugin_operation'] = (isset ($plugin->data->operation) ? $plugin->data->operation : null);
					$data ['plugin_reason'] = (isset ($plugin->data->reason) ? $plugin->data->reason : null);
					$data ['plugin_status'] = (isset ($plugin->data->status) ? $plugin->data->status : null);
				}

				// Do we have a user?
				if (isset ($social_data->response->result->data->user) && is_object ($social_data->response->result->data->user))
				{
					// User.
					$user = $social_data->response->result->data->user;

					//Add user data.
					$data ['user_token'] = $user->user_token;

					// Do we have an identity ?
					if (isset ($user->identity) && is_object ($user->identity))
					{
						// Identity.
						$identity = $user->identity;

						// Add identity data.
						$data ['identity_token'] = $identity->identity_token;
						$data ['identity_provider'] = ! empty ($identity->source->name) ? $identity->source->name : '';

						$data ['user_first_name'] = ! empty ($identity->name->givenName) ? $identity->name->givenName : '';
						$data ['user_last_name'] = ! empty ($identity->name->familyName) ? $identity->name->familyName : '';
						$data ['user_formatted_name'] = ! empty ($identity->name->formatted) ? $identity->name->formatted : '';
						$data ['user_location'] = ! empty ($identity->currentLocation) ? $identity->currentLocation : '';
						$data ['user_constructed_name'] = trim ($data ['user_first_name'] . ' ' . $data ['user_last_name']);
						$data ['user_picture'] = ! empty ($identity->pictureUrl) ? $identity->pictureUrl : '';
						$data ['user_thumbnail'] = ! empty ($identity->thumbnailUrl) ? $identity->thumbnailUrl : '';
						$data ['user_current_location'] = ! empty ($identity->currentLocation) ? $identity->currentLocation : '';
						$data ['user_about_me'] = ! empty ($identity->aboutMe) ? $identity->aboutMe : '';
						$data ['user_note'] = ! empty ($identity->note) ? $identity->note : '';

						// Birthdate - MM/DD/YYYY
						if (! empty ($identity->birthday) && preg_match ('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $identity->birthday, $matches))
						{
							$data ['user_birthdate'] = str_pad ($matches [2], 2, '0', STR_PAD_LEFT);
							$data ['user_birthdate'] .= '/' . str_pad ($matches [1], 2, '0', STR_PAD_LEFT);
							$data ['user_birthdate'] .= '/' . str_pad ($matches [3], 4, '0', STR_PAD_LEFT);
						}
						else
						{
							$data ['user_birthdate'] = '';
						}

						// Fullname.
						if (! empty ($identity->name->formatted))
						{
							$data ['user_full_name'] = $identity->name->formatted;
						}
						elseif (! empty ($identity->name->displayName))
						{
							$data ['user_full_name'] = $identity->name->displayName;
						}
						else
						{
							$data ['user_full_name'] = $data ['user_constructed_name'];
						}

						// Preferred Username.
						if (! empty ($identity->preferredUsername))
						{
							$data ['user_login'] = $identity->preferredUsername;
						}
						elseif (! empty ($identity->displayName))
						{
							$data ['user_login'] = $identity->displayName;
						}
						else
						{
							$data ['user_login'] = $data ['user_full_name'];
						}

						$data ['user_login'] = str_replace (' ', '', trim ($data ['user_login']));

						// Website/Homepage.
						$data ['user_website'] = '';
						if (! empty ($identity->profileUrl))
						{
							$data ['user_website'] = $identity->profileUrl;
						}
						elseif (! empty ($identity->urls [0]->value))
						{
							$data ['user_website'] = $identity->urls [0]->value;
						}

						// Gender.
						$data ['user_gender'] = '';
						if (! empty ($identity->gender))
						{
							switch ($identity->gender)
							{
								case 'male':
									$data ['user_gender'] = 'm';
									break;

								case 'female':
									$data ['user_gender'] = 'f';
									break;
							}
						}

						// Email Addresses.
						$data ['user_emails'] = array ();
						$data ['user_emails_simple'] = array ();

						// Email Address.
						$data ['user_email'] = '';
						$data ['user_email_is_verified'] = FALSE;

						// Extract emails.
						if (property_exists ($identity, 'emails') && is_array ($identity->emails))
						{
							// Loop through emails.
							foreach ($identity->emails as $email)
							{
								// Add to simple list.
								$data ['user_emails_simple'] [] = $email->value;

								// Add to list.
								$data ['user_emails'] [] = array (
										'user_email' => $email->value,
										'user_email_is_verified' => $email->is_verified
								);

								// Keep one, if possible a verified one.
								if (empty ($data ['user_email']) || $email->is_verified)
								{
									$data ['user_email'] = $email->value;
									$data ['user_email_is_verified'] = $email->is_verified;
								}
							}
						}

						// Addresses.
						$data ['user_addresses'] = array ();
						$data ['user_addresses_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'addresses') && is_array ($identity->addresses))
						{
							// Loop through entries.
							foreach ($identity->addresses as $address)
							{
								// Add to simple list.
								$data ['user_addresses_simple'] [] = $address->formatted;

								// Add to list.
								$data ['user_addresses'] [] = array (
										'formatted' => $address->formatted
								);
							}
						}

						// Phone Number.
						$data ['user_phone_numbers'] = array ();
						$data ['user_phone_numbers_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'phoneNumbers') && is_array ($identity->phoneNumbers))
						{
							// Loop through entries.
							foreach ($identity->phoneNumbers as $phone_number)
							{
								// Add to simple list.
								$data ['user_phone_numbers_simple'] [] = $phone_number->value;

								// Add to list.
								$data ['user_phone_numbers'] [] = array (
										'value' => $phone_number->value,
										'type' => (isset ($phone_number->type) ? $phone_number->type : null)
								);
							}
						}

						// URLs.
						$data ['user_interests'] = array ();
						$data ['user_interests_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'interests') && is_array ($identity->interests))
						{
							// Loop through entries.
							foreach ($identity->interests as $interest)
							{
								// Add to simple list.
								$data ['user_interests_simple'] [] = $interest->value;

								// Add to list.
								$data ['users_interests'] [] = array (
										'value' => $interest->value,
										'category' => (isset ($interest->category) ? $interest->category : null)
								);
							}
						}

						// URLs.
						$data ['user_urls'] = array ();
						$data ['user_urls_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'urls') && is_array ($identity->urls))
						{
							// Loop through entries.
							foreach ($identity->urls as $url)
							{
								// Add to simple list.
								$data ['user_urls_simple'] [] = $url->value;

								// Add to list.
								$data ['user_urls'] [] = array (
										'value' => $url->value,
										'type' => (isset ($url->type) ? $url->type : null)
								);
							}
						}

						// Certifications.
						$data ['user_certifications'] = array ();
						$data ['user_certifications_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'certifications') && is_array ($identity->certifications))
						{
							// Loop through entries.
							foreach ($identity->certifications as $certification)
							{
								// Add to simple list.
								$data ['user_certifications_simple'] [] = $certification->name;

								// Add to list.
								$data ['user_certifications'] [] = array (
										'name' => $certification->name,
										'number' => (isset ($certification->number) ? $certification->number : null),
										'authority' => (isset ($certification->authority) ? $certification->authority : null),
										'start_date' => (isset ($certification->startDate) ? $certification->startDate : null)
								);
							}
						}

						// Recommendations.
						$data ['user_recommendations'] = array ();
						$data ['user_recommendations_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'recommendations') && is_array ($identity->recommendations))
						{
							// Loop through entries.
							foreach ($identity->recommendations as $recommendation)
							{
								// Add to simple list.
								$data ['user_recommendations_simple'] [] = $recommendation->value;

								// Build data.
								$data_entry = array (
										'value' => $recommendation->value
								);

								// Add recommender
								if (property_exists ($recommendation, 'recommender') && is_object ($recommendation->recommender))
								{
									$data_entry ['recommender'] = array ();

									// Add recommender details
									foreach (get_object_vars ($recommendation->recommender) as $field => $value)
									{
										$data_entry ['recommender'] [self::undo_camel_case ($field)] = $value;
									}
								}

								// Add to list.
								$data ['user_recommendations'] [] = $data_entry;
							}
						}

						// Accounts.
						$data ['user_accounts'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'accounts') && is_array ($identity->accounts))
						{
							// Loop through entries.
							foreach ($identity->accounts as $account)
							{
								// Add to list.
								$data ['user_accounts'] [] = array (
										'domain' => (isset ($account->domain) ? $account->domain : null),
										'userid' => (isset ($account->userid) ? $account->userid : null),
										'username' => (isset ($account->username) ? $account->username : null)
								);
							}
						}

						// Photos.
						$data ['user_photos'] = array ();
						$data ['user_photos_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'photos') && is_array ($identity->photos))
						{
							// Loop through entries.
							foreach ($identity->photos as $photo)
							{
								// Add to simple list.
								$data ['user_photos_simple'] [] = $photo->value;

								// Add to list.
								$data ['user_photos'] [] = array (
										'value' => $photo->value,
										'size' => $photo->size
								);
							}
						}

						// Languages.
						$data ['user_languages'] = array ();
						$data ['user_languages_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'languages') && is_array ($identity->languages))
						{
							// Loop through entries.
							foreach ($identity->languages as $language)
							{
								// Add to simple list
								$data ['user_languages_simple'] [] = $language->value;

								// Add to list.
								$data ['user_languages'] [] = array (
										'value' => $language->value,
										'type' => $language->type
								);
							}
						}

						// Educations.
						$data ['user_educations'] = array ();
						$data ['user_educations_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'educations') && is_array ($identity->educations))
						{
							// Loop through entries.
							foreach ($identity->educations as $education)
							{
								// Add to simple list.
								$data ['user_educations_simple'] [] = $education->value;

								// Add to list.
								$data ['user_educations'] [] = array (
										'value' => $education->value,
										'type' => $education->type
								);
							}
						}

						// Organizations.
						$data ['user_organizations'] = array ();
						$data ['user_organizations_simple'] = array ();

						// Extract entries.
						if (property_exists ($identity, 'organizations') && is_array ($identity->organizations))
						{
							// Loop through entries.
							foreach ($identity->organizations as $organization)
							{
								// At least the name is required.
								if (! empty ($organization->name))
								{
									// Add to simple list.
									$data ['user_organizations_simple'] [] = $organization->name;

									// Build entry.
									$data_entry = array ();

									// Add all fields.
									foreach (get_object_vars ($organization) as $field => $value)
									{
										$data_entry [self::undo_camel_case ($field)] = $value;
									}

									// Add to list.
									$data ['user_organizations'] [] = $data_entry;
								}
							}
						}
					}
				}
				return $data;
			}
		}
		return FALSE;
	}


	/*
	 * Callback Handler.
	 */
	public function handle_callback (
			$oa_action,
			$login_token,
			$connection_token,
			$use_curl,
			$use_ssl,
			$subdomain,
			$api_credentials,
			$linking,
			$validation,
			$avatar,
			$custom_redirect)
	{
		// API Settings.
		$api_connection_handler = $use_curl == '1' ? 'curl' : 'fsockopen';
		$api_connection_url = ($use_ssl == '1' ? 'https' : 'http') . '://' . $subdomain . '.api.oneall.com/connections/' . $connection_token . '.json';

		// Make Request.
		$result = $this->do_api_request ($api_connection_handler, $api_connection_url, $api_credentials);

		// Parse result
		if (is_object ($result) && property_exists ($result, 'http_code') && $result->http_code == 200)
		{
			// Extract data
			
			if (($user_data = $this->extract_social_network_profile ($result)) !== FALSE)
			{
				// Social Login
				if ($oa_action == 'social_login')
				{
					return $this->social_login_handle_callback ($user_data, $linking, $validation, $avatar, $api_connection_handler, $custom_redirect);
				}
				// Social Link
				elseif ($oa_action == 'social_link')
				{
					// Read the user_id for this user_token, or from the session:
					$user_id_user_token = $this->get_user_id_for_user_token ($user_data ['user_token']);
					$user_id = $user_id_user_token === FALSE ? Gdn::Session()->UserID : $user_id_user_token; 
					
					if (! empty ($user_data ['plugin_action']) && $user_data ['plugin_action'] == 'link_identity')
					{
						$this->link_tokens_to_user_id ($user_id, $user_data ['user_token'], $user_data ['identity_token'], $user_data ['identity_provider']);
					}
					else
					{
						$this->unlink_identity_token ($user_data ['identity_token']);
					}
					// redirect to profile/link (to show the link result):
					$target = Gdn::Request ()->Get ('Target');
					SafeRedirect (Url ($target, TRUE));
				}
			}
		}
	}


	/**
	 * Handle callback for social login
	 */
	protected function social_login_handle_callback ($user_data, $linking, $validation, $avatar, $api_connection_handler, $custom_redirect)
	{
		$error_message = NULL;
		$user_id = FALSE;

		// Get user_id by token.
		$user_id_tmp = $this->get_user_id_for_user_token ($user_data['user_token']);

		// We already have a valid user for this token.
		if ($user_id_tmp !== FALSE)
		{
			$user_id = $user_id_tmp;
			$this->social_login_redirect ($error_message, $user_id, $user_data, $custom_redirect, FALSE);
		}
		// No user found for this token.
		// Make sure that account linking is enabled.
		if ($linking == '1')
		{
			// Make sure that the email has been verified.
			if (! empty ($user_data ['user_email']) && isset ($user_data ['user_email_is_verified']) && $user_data ['user_email_is_verified'] === TRUE)
			{
				// Read existing user
				$user_id_tmp = $this->get_user_id_by_email ($user_data ['user_email']);

				// Existing user found
				if ($user_id_tmp !== FALSE)
				{
					// Link the user to this social network.
					if ($this->link_tokens_to_user_id ($user_id_tmp, $user_data ['user_token'], $user_data ['identity_token'], $user_data ['identity_provider']) !== FALSE)
					{
						$user_id = $user_id_tmp;
						$this->social_login_redirect ($error_message, $user_id, $user_data, $custom_redirect, FALSE);
					}
				}
			}
		}
		// No user has been linked to this token yet.
		// Will validation be required ('1' means always).
		$do_validation = $validation == '1' ? TRUE : FALSE;

		// Username is mandatory.
		if (empty ($user_data ['user_login']))
		{
			$user_data ['user_login'] = $user_data ['identity_provider'] . 'User';
		}
		// Username must be unique.
		if ($this->get_user_id_by_username ($user_data ['user_login']) !== FALSE)
		{
			$i = 1;
			$user_login_tmp = $user_data ['user_login'] . ($i);
			while ($this->get_user_id_by_username ($user_login_tmp) !== FALSE)
			{
				$user_login_tmp = $user_data ['user_login'] . ($i++);
			}
			$user_data ['user_login'] = $user_login_tmp;
			if (! $do_validation && $validation != '0')
			{
				$do_validation = TRUE;
			}
		}

		// Vanilla requires email, and the other condition is a configuration option.
		if (empty ($user_data ['user_email']) ||
				(! $do_validation && $validation != '0' && $this->get_user_id_by_email ($user_data ['user_email']) !== FALSE && $linking === '1'))
		{
			$do_validation = TRUE;
		}

		// Return to controller for user confirmation.
		if ($do_validation === TRUE)
		{
			return array (
					'user_login' => $user_data['user_login'],
					'user_email' => $user_data['user_email'],
					'user_thumbnail' => empty ($user_data['user_thumbnail']) ? '' : $user_data['user_thumbnail'],
					'user_token' => $user_data['user_token'],
					'identity_token' => $user_data['identity_token'],
					'identity_provider' => $user_data['identity_provider'],
					'redirect' => Gdn::Request ()->Get ('Target'),
			);
		}
		// credentials are correct at this point:
		list ($error_message, $user_id) = $this->social_login_user_add ($user_data, $avatar);
		$this->social_login_redirect ($error_message, $user_id, $user_data, $custom_redirect, TRUE);
	}


	/**
	 * Complete social login callback once credentials are validated.
	 */
	public function social_login_resume_handle_callback ($val_user_data, $avatar)
	{
		list ($error_message, $user_id) = $this->social_login_user_add ($val_user_data, $avatar);
		$this->social_login_redirect ($error_message, $user_id, $val_user_data, $val_user_data['redirect'], TRUE);
	}


	/**
	 * Continue social login callback once credentials validated.
	 */
	protected function social_login_user_add ($user_data, $avatar)
	{
		$NewUser = array (
				'Name' => $user_data ['user_login'],
				'Password' => md5 (microtime ()),
				'Email' => $user_data ['user_email'],
				'ShowEmail' => '0',
		);
		if ($avatar == '1' && ! empty ($user_data ['user_thumbnail']))
		{
			$NewUser ['Photo'] = $user_data ['user_thumbnail'];
		}

		$user_id = FALSE;
		$UserModel = new UserModel ();
		$user_id = $UserModel->Register ($NewUser, array ('CheckCaptcha' => FALSE));

		if ($user_id === FALSE)
		{
			// TODO other validation rules may apply in vanilla... 
			$error_message = 'NO_USER';
			trigger_error ('NO_USER', E_USER_ERROR);
			return array ($error_message, FALSE);
		}
		// Link the user to this social network.
		$this->link_tokens_to_user_id (
				$user_id,
				$user_data ['user_token'],
				$user_data ['identity_token'],
				$user_data ['identity_provider']);
		return array (NULL, $user_id);
	}


	/**
	 * Complete callback once credentials validated.
	 */
	protected function social_login_redirect ($error_message, $user_id, $user_data, $custom_redirect, $registration)
	{
		// Display an error message
		if (isset ($error_message))
		{
			trigger_error ($error_message);
		}
		// Process
		else
		{
			if (is_numeric ($user_id))
			{
				// Update statistics:
				$this->incr_login_count_identity_token ($user_data['identity_token']);

				// Login:
				Gdn::Session ()->Start ($user_id, TRUE);
				if (!Gdn::Session ()->CheckPermission ('Garden.SignIn.Allow')) {
					//$this->Form->AddError('ErrorPermission');
					Gdn::Session ()->End();
				}

				if ($registration === TRUE)
				{
					Gdn::UserModel ()->FireEvent ('RegistrationSuccessful');
				}
				else
				{
					Gdn::UserModel ()->FireEvent ('AfterSignIn');
				}

				// Redirection:
				if (! empty ($custom_redirect))
				{
					SafeRedirect ($custom_redirect);
				}
				// This was set in the callback_uri (JS):
				$target = Gdn::Request ()->Get ('Target');
				$target = empty ($target) ? Gdn::Router ()->GetDestination ('DefaultController') : $target;
				SafeRedirect (Url ($target, TRUE));
			}
		}
	}


	/**
	 * Check if the current connection is being made over https
	 */
	private static function is_https_on ()
	{
		global $request;

		if ($request->server ('SERVER_PORT') == 443)
		{
			return TRUE;
		}

		if ($request->server ('HTTP_X_FORWARDED_PROTO') == 'https')
		{
			return TRUE;
		}

		if (in_array (strtolower(trim($request->server ('HTTPS'))), array ('on', '1')))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Return the current url
	 */
	function get_current_url ($remove_vars = array ('oa_social_login_login_token', 'sid'))
	{
		global $request;

		// Extract Uri
		if (strlen (trim ($request->server ('REQUEST_URI'))) > 0)
		{
			$request_uri = trim ($request->server ('REQUEST_URI'));
		}
		else
		{
			$request_uri = trim ($request->server ('PHP_SELF'));
		}
		$request_uri = htmlspecialchars_decode ($request_uri);

		// Extract Protocol
		if (self::is_https_on ())
		{
			$request_protocol = 'https';
		}
		else
		{
			$request_protocol = 'http';
		}

		// Extract Host
		if (strlen (trim ($request->server ('HTTP_X_FORWARDED_HOST'))) > 0)
		{
			$request_host = trim ($request->server ('HTTP_X_FORWARDED_HOST'));
		}
		elseif (strlen (trim ($request->server ('HTTP_HOST'))) > 0)
		{
			$request_host = trim ($request->server ('HTTP_HOST'));
		}
		else
		{
			$request_host = trim ($request->server ('SERVER_NAME'));
		}

		// Port of this request
		$request_port = '';

		// We are using a proxy
		if (strlen(trim ($request->server ('HTTP_X_FORWARDED_PORT'))) > 0)
		{
			// SERVER_PORT is usually wrong on proxies, don't use it!
			$request_port = intval ($request->server ('HTTP_X_FORWARDED_PORT'));
		}
		// Does not seem like a proxy
		else	if (strlen(trim ($request->server ('SERVER_PORT'))) > 0)
		{
			$request_port = intval ($request->server ('SERVER_PORT'));
		}

		// Remove standard ports
		$request_port = (! in_array ($request_port, array (80, 443)) ? $request_port : '');

		// Build url
		$current_url = $request_protocol . '://' . $request_host . (! empty ($request_port) ? (':' . $request_port) : '') . $request_uri;

		// Remove query arguments.
		if (is_array ($remove_vars) && count ($remove_vars) > 0)
		{
			// Break up url
			list ($url_part, $query_part) = array_pad (explode ('?', $current_url), 2, '');
			parse_str ($query_part, $query_vars);

			// Remove argument.
			if (is_array ($query_vars))
			{
				foreach ($remove_vars as $var)
				{
					if (isset ($query_vars [$var]))
					{
						unset ($query_vars [$var]);
					}
				}

				// Build new url
				$current_url = $url_part . ((is_array ($query_vars) and count ($query_vars) > 0) ? ('?' . http_build_query ($query_vars)) : '');
			}
		}

		// Done
		return $current_url;
	}

}
