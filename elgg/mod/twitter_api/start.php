<?php
/**
 * Elgg Twitter Service
 * This service plugin allows users to authenticate their Elgg account with Twitter.
 *
 * @package TwitterAPI
 */

elgg_register_event_handler('init', 'system', 'twitter_api_init');

function twitter_api_init() {

	// require libraries
	$base = __DIR__;
	elgg_register_class('TwitterOAuth', "$base/vendors/twitteroauth/twitterOAuth.php");
	elgg_register_library('twitter_api', "$base/lib/twitter_api.php");
	elgg_load_library('twitter_api');

	// extend site views
	//elgg_extend_view('metatags', 'twitter_api/metatags');
	elgg_extend_view('elgg.css', 'twitter_api/css');
	elgg_extend_view('admin.css', 'twitter_api/css');
	elgg_extend_view('elgg.js', 'twitter_api/js');

	// sign on with twitter
	if (twitter_api_allow_sign_on_with_twitter()) {
		elgg_extend_view('login/extend', 'twitter_api/login');
	}

	// register page handler
	elgg_register_page_handler('twitter_api', 'twitter_api_pagehandler');
	
	// register Walled Garden public pages
	elgg_register_plugin_hook_handler('public_pages', 'walled_garden', 'twitter_api_public_pages');

	// push wire post messages to twitter
	if (elgg_get_plugin_setting('wire_posts', 'twitter_api') == 'yes') {
		elgg_register_plugin_hook_handler('status', 'user', 'twitter_api_tweet');
	}
}

/**
 * Serves pages for twitter.
 *
 * @param array $page
 * @return bool
 */
function twitter_api_pagehandler($page) {
	if (!isset($page[0])) {
		return false;
	}

	switch ($page[0]) {
		case 'authorize':
			twitter_api_authorize();
			break;
		case 'revoke':
			twitter_api_revoke();
			break;
		case 'forward':
			twitter_api_forward();
			break;
		case 'login':
			twitter_api_login();
			break;
		case 'interstitial':
			elgg_gatekeeper();
			// only let twitter users do this.
			$guid = elgg_get_logged_in_user_guid();
			$twitter_name = elgg_get_plugin_user_setting('twitter_name', $guid, 'twitter_api');
			if (!$twitter_name) {
				register_error(elgg_echo('twitter_api:invalid_page'));
				forward();
			}
			echo elgg_view_resource('twitter_api/interstitial');
			break;
		default:
			return false;
	}
	return true;
}

/**
 * Push a status update to twitter.
 *
 * @param string $hook
 * @param string $type
 * @param null   $returnvalue
 * @param array  $params
 */
function twitter_api_tweet($hook, $type, $returnvalue, $params) {

	if (!$params['user'] instanceof ElggUser) {
		return;
	}

	// @todo - allow admin to select origins?

	// check user settings
	$user_guid = $params['user']->getGUID();
	$access_key = elgg_get_plugin_user_setting('access_key', $user_guid, 'twitter_api');
	$access_secret = elgg_get_plugin_user_setting('access_secret', $user_guid, 'twitter_api');
	if (!($access_key && $access_secret)) {
		return;
	}

	$api = twitter_api_get_api_object($access_key, $access_secret);
	if (!$api) {
		return;
	}

	$api->post('statuses/update', ['status' => $params['message']]);
}

/**
 * Get tweets for a user.
 *
 * @param int   $user_guid The Elgg user GUID
 * @param array $options
 * @return array
 */
function twitter_api_fetch_tweets($user_guid, $options = []) {

	// check user settings
	$access_key = elgg_get_plugin_user_setting('access_key', $user_guid, 'twitter_api');
	$access_secret = elgg_get_plugin_user_setting('access_secret', $user_guid, 'twitter_api');
	if (!($access_key && $access_secret)) {
		return false;
	}

	$api = twitter_api_get_api_object($access_key, $access_secret);
	if (!$api) {
		return false;
	}

	return $api->get('statuses/user_timeline', $options);
}

/**
 * Register as public pages for walled garden.
 *
 * @param string $hook
 * @param string $type
 * @param array  $return_value
 * @param array  $params
 * @return array
 */
function twitter_api_public_pages($hook, $type, $return_value, $params) {
	$return_value[] = 'twitter_api/forward';
	$return_value[] = 'twitter_api/login';

	return $return_value;
}