<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/*EXTRA FUNCTIONS: apache\_.+*/

/**
 * Standard code module initialisation function.
 */
function init__users()
{
    global $MEMBERS_BLOCKED_CACHE, $MEMBERS_BLOCKING_US_CACHE;
    $MEMBERS_BLOCKED_CACHE = null;
    $MEMBERS_BLOCKING_US_CACHE = null;
    global $SESSION_CACHE, $MEMBER_CACHED, $ADMIN_GROUP_CACHE, $MODERATOR_GROUP_CACHE, $USERGROUP_LIST_CACHE;
    global $USER_NAME_CACHE, $MEMBER_EMAIL_CACHE, $USERS_GROUPS_CACHE;
    global $SESSION_CONFIRMED_CACHE, $GETTING_MEMBER, $USER_THEME_CACHE, $EMOTICON_LEVELS, $EMOTICON_SET_DIR;
    $EMOTICON_LEVELS = null;
    $USER_NAME_CACHE = array();
    $MEMBER_EMAIL_CACHE = array();
    $USERGROUP_LIST_CACHE = null;
    $USERS_GROUPS_CACHE = array();
    $ADMIN_GROUP_CACHE = null;
    $MODERATOR_GROUP_CACHE = null;
    $MEMBER_CACHED = null;
    $SESSION_CONFIRMED_CACHE = 0;
    $GETTING_MEMBER = false;
    $USER_THEME_CACHE = null;
    $EMOTICON_SET_DIR = null;
    global $IS_ACTUALLY;
    global $IS_ACTUALLY_ADMIN;
    /** Find whether ocPortal is running in SU mode, and therefore the real user is an admin
     *
     * @global boolean $IS_ACTUALLY_ADMIN
     */
    $IS_ACTUALLY_ADMIN = false;
    $IS_ACTUALLY = null;
    global $IS_A_COOKIE_LOGIN;
    $IS_A_COOKIE_LOGIN = false;
    global $DOING_USERS_INIT;
    $DOING_USERS_INIT = true;
    global $IS_VIA_BACKDOOR;
    $IS_VIA_BACKDOOR = false;

    // Load all sessions into memory, if possible
    if (get_option('session_prudence') != '1') {
        $SESSION_CACHE = persistent_cache_get('SESSION_CACHE');
    } else {
        $SESSION_CACHE = null;
    }
    global $IN_MINIKERNEL_VERSION;
    if ((!is_array($SESSION_CACHE)) && (!$IN_MINIKERNEL_VERSION)) {
        if (get_option('session_prudence') != '1') {
            $where = '';
        } else {
            $where = ' WHERE ' . db_string_equal_to('the_session', get_session_id()) . ' OR ' . db_string_equal_to('ip', get_ip_address(3));
        }
        $SESSION_CACHE = array();
        if ((get_forum_type() == 'ocf') && (get_db_site() == get_db_forums()) && (get_db_site_host() == get_db_forums_host())) {
            $GLOBALS['NO_DB_SCOPE_CHECK'] = true;
            $_s = $GLOBALS['SITE_DB']->query('SELECT s.*,m.m_primary_group FROM ' . get_table_prefix() . 'sessions s LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'f_members m ON m.id=s.member_id' . $where, null, null, false, true);
            $SESSION_CACHE = list_to_map('the_session', $_s);
            $GLOBALS['NO_DB_SCOPE_CHECK'] = false;
        } else {
            $SESSION_CACHE = list_to_map('the_session', $GLOBALS['SITE_DB']->query('SELECT * FROM ' . get_table_prefix() . 'sessions' . $where));
        }
        if (get_option('session_prudence') != '1') {
            persistent_cache_set('SESSION_CACHE', $SESSION_CACHE);
        }
    }

    // Canonicalise various disparities in how HTTP auth environment variables are set
    if (array_key_exists('REDIRECT_REMOTE_USER', $_SERVER)) {
        $_SERVER['PHP_AUTH_USER'] = preg_replace('#@.*$#', '', $_SERVER['REDIRECT_REMOTE_USER']);
    }
    if (array_key_exists('PHP_AUTH_USER', $_SERVER)) {
        $_SERVER['PHP_AUTH_USER'] = preg_replace('#@.*$#', '', $_SERVER['PHP_AUTH_USER']);
    }
    if (array_key_exists('REMOTE_USER', $_SERVER)) {
        $_SERVER['PHP_AUTH_USER'] = preg_replace('#@.*$#', '', $_SERVER['REMOTE_USER']);
    }

    $DOING_USERS_INIT = null;
}

/**
 * Handles an attempted login or logout, and take care of all the sessions and cookies etc.
 */
function handle_logins()
{
    if (get_param_integer('httpauth', 0) == 1) {
        require_code('users_inactive_occasionals');
        force_httpauth();
    }
    $username = trim(post_param('login_username', ''));
    if (($username != '') && ($username != do_lang('GUEST'))) {
        require_code('users_active_actions');
        handle_active_login($username);
    }

    // If it was a log out
    if ((get_page_name() == 'login') && (get_param('type', '', true) == 'logout')) {
        require_code('users_active_actions');
        handle_active_logout();
    }
}

/**
 * Find whether the current member is a guest.
 *
 * @param  ?MEMBER                      Member ID to check (NULL: current user)
 * @param  boolean                      Whether to just do a quick check, don't establish new sessions
 * @return boolean                      Whether the current member is a guest
 */
function is_guest($member_id = null, $quick_only = false)
{
    if (!isset($GLOBALS['FORUM_DRIVER'])) {
        return true;
    }
    if ($member_id === null) {
        $member_id = get_member($quick_only);
    }
    return ($GLOBALS['FORUM_DRIVER']->get_guest_id() == $member_id);
}

/**
 * Get the ID of the currently active member.
 * It see's if the session exists / cookie is valid -- and gets the member ID accordingly
 *
 * @param  boolean                      Whether to just do a quick check, don't establish new sessions
 * @return MEMBER                       The member requesting this web page (possibly the guest member - which strictly speaking, is not a member)
 */
function get_member($quick_only = false)
{
    global $SESSION_CACHE, $MEMBER_CACHED, $GETTING_MEMBER, $SITE_INFO;

    if ($MEMBER_CACHED !== null) {
        $GETTING_MEMBER = false;
        return $MEMBER_CACHED;
    }

    if (is_null($GLOBALS['FORUM_DRIVER'])) {
        load_user_stuff();
    }

    // If lots of aging sessions, clean out
    reset($SESSION_CACHE);
    if ((count($SESSION_CACHE) > 50) && ($SESSION_CACHE[key($SESSION_CACHE)]['last_activity'] < time() - intval(60.0 * 60.0 * max(0.017, floatval(get_option('session_expiry_time')))))) {
        delete_expired_sessions_or_recover();
    }

    // Try via backdoor that someone with full server access can place
    $backdoor_ip_address = mixed(); // Enable to a real IP address to force login from FTP access (if lost admin password)
    if (array_key_exists('backdoor_ip', $SITE_INFO)) {
        $backdoor_ip_address = $SITE_INFO['backdoor_ip'];
    }
    if ((is_string($backdoor_ip_address)) && ($backdoor_ip_address != '') && (get_ip_address() == $backdoor_ip_address)) {
        require_code('users_active_actions');
        if (function_exists('restricted_manually_enabled_backdoor')) { // May be trying to check in safe mode when doing above require_code, so recurse
            $MEMBER_CACHED = restricted_manually_enabled_backdoor();
            // Will have created a session in here already
            return $MEMBER_CACHED;
        }
    }

    if ($GETTING_MEMBER) {
        if (!isset($GLOBALS['FORUM_DRIVER'])) {
            return db_get_first_id(); // :S
        }
        return $GLOBALS['FORUM_DRIVER']->get_guest_id();
    }
    $GETTING_MEMBER = true;

    global $FORCE_INVISIBLE_GUEST;
    if ($FORCE_INVISIBLE_GUEST) {
        $GETTING_MEMBER = false;
        if (!isset($GLOBALS['FORUM_DRIVER'])) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
        $MEMBER_CACHED = $GLOBALS['FORUM_DRIVER']->get_guest_id();
        return $MEMBER_CACHED;
    }

    $member = null;

    $cookie_bits = explode(':', str_replace('|', ':', get_member_cookie()));
    $base = $cookie_bits[0];

    // Try by session
    $session = get_session_id();
    if (($session != '') && (get_param_integer('keep_force_htaccess', 0) == 0)) {
        $ip = get_ip_address(3); // I hope AOL can cope with this
        $allow_unbound_guest = true; // Note: Guest sessions are not IP bound
        $member_row = null;

        if (
            ($SESSION_CACHE !== null) &&
            (array_key_exists($session, $SESSION_CACHE)) &&
            ($SESSION_CACHE[$session] !== null) &&
            (array_key_exists('member_id', $SESSION_CACHE[$session])) &&
            ((get_option('ip_strict_for_sessions') == '0') || ($SESSION_CACHE[$session]['ip'] == $ip) || ((is_guest($SESSION_CACHE[$session]['member_id'])) && ($allow_unbound_guest)) || (($SESSION_CACHE[$session]['session_confirmed'] == 0) && (!is_guest($SESSION_CACHE[$session]['member_id'])))) &&
            ($SESSION_CACHE[$session]['last_activity'] > time() - intval(60.0 * 60.0 * max(0.017, floatval(get_option('session_expiry_time')))))
        ) {
            $member_row = $SESSION_CACHE[$session];
        }
        if (($member_row !== null) && ((!array_key_exists($base, $_COOKIE)) || (!is_guest($member_row['member_id'])))) {
            $member = $member_row['member_id'];

            if (($member !== null) && ((time() - $member_row['last_activity']) > 10)) { // Performance optimisation. Pointless re-storing the last_activity if less than 3 seconds have passed!
                //$GLOBALS['SITE_DB']->query_update('sessions',array('last_activity'=>time(),'the_zone'=>get_zone_name(),'the_page'=>get_page_name()),array('the_session'=>$session),'',1);  Done in get_screen_title now
                $SESSION_CACHE[$session]['last_activity'] = time();
                if (get_option('session_prudence') != '1') {
                    persistent_cache_set('SESSION_CACHE', $SESSION_CACHE);
                }
            }
            global $SESSION_CONFIRMED_CACHE;
            $SESSION_CONFIRMED_CACHE = $member_row['session_confirmed'];

            if ((!is_guest($member)) && ($GLOBALS['FORUM_DRIVER']->is_banned($member)) && (!$GLOBALS['IS_VIA_BACKDOOR'])) { // All hands to the guns
                warn_exit(do_lang_tempcode('MEMBER_BANNED'));
            }

            // Test this member still exists
            if ($GLOBALS['FORUM_DRIVER']->get_username($member) === null) {
                $member = $GLOBALS['FORUM_DRIVER']->get_guest_id();
            }

            if (array_key_exists($base, $_COOKIE)) {
                global $IS_A_COOKIE_LOGIN;
                $IS_A_COOKIE_LOGIN = true;
            }
        } else {
            require_code('users_inactive_occasionals');
            set_session_id('');
        }
    }

    if (($member === null) && (get_session_id() == '') && (get_param_integer('keep_force_htaccess', 0) == 0)) {
        // Try by cookie (will defer to forum driver to authorise against detected cookie)
        require_code('users_inactive_occasionals');
        $member = try_cookie_login();

        // Can forum driver help more directly?
        if (method_exists($GLOBALS['FORUM_DRIVER'], 'get_member')) {
            $member = $GLOBALS['FORUM_DRIVER']->get_member();
        }
    }

    // Try via additional login providers. They can choose whether to respect existing $member of get_session_id() settings. Some may do an account linkage, so we need to let them decide what to do.
    $hooks = find_all_hooks('systems', 'login_providers');
    foreach (array_keys($hooks) as $hook) {
        require_code('hooks/systems/login_providers/' . $hook);
        $ob = object_factory('Hook_login_provider_' . $hook);
        $member = $ob->try_login($member);
    }

    // Try via GAE Console
    if (GOOGLE_APPENGINE) {
        if (gae_is_admin()) {
            require_code('users_active_actions');
            if (function_exists('restricted_manually_enabled_backdoor')) { // May be trying to check in safe mode when doing above require_code, so recurse
                $MEMBER_CACHED = restricted_manually_enabled_backdoor();
                // Will have created a session in here already
                return $MEMBER_CACHED;
            }
        }
    }

    // Guest or banned
    if ($member === null) {
        $member = $GLOBALS['FORUM_DRIVER']->get_guest_id();
        $is_guest = true;
    } else {
        $is_guest = is_guest($member);
    }

    // If we are doing a very quick init, bomb out now - no need to establish session etc
    global $SITE_INFO;
    if ($quick_only) {
        $GETTING_MEMBER = false;
        return $member;
    }

    // If one of the try_* functions hasn't actually created the session, call it here
    $session = get_session_id();
    if ($session == '') {
        require_code('users_inactive_occasionals');
        create_session($member);
    }

    // If we are logged in, maybe do some further processing
    if (!$is_guest) {
        // Is there a su operation?
        $ks = get_param('keep_su', '');
        if ($ks != '') {
            require_code('users_inactive_occasionals');
            $member = try_su_login($member);
        }

        // Run hooks, if any exist
        $hooks = find_all_hooks('systems', 'upon_login');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/upon_login/' . filter_naughty($hook));
            $ob = object_factory('Hook_upon_login_' . filter_naughty($hook), true);
            if ($ob === null) {
                continue;
            }
            $ob->run(false, null, $member); // false means "not a new login attempt"
        }
    }

    // Ok we have our answer
    $MEMBER_CACHED = $member;
    $GETTING_MEMBER = false;

    // We call this to ensure any HTTP-auth specific code has a chance to run
    is_httpauth_login();

    if ($member !== null) {
        enforce_temporary_passwords($member);

        if (get_forum_type() == 'ocf') {
            $GLOBALS['FORUM_DRIVER']->ocf_flood_control($member);
        }
    }

    return $member;
}

/**
 * Make sure temporary passwords restrict you to the edit account page. May not return, if it needs to do a redirect.
 *
 * @param  MEMBER                       The current member
 */
function enforce_temporary_passwords($member)
{
    if ((get_forum_type() == 'ocf') && (running_script('index')) && ($member != db_get_first_id()) && (!$GLOBALS['IS_ACTUALLY_ADMIN']) && ($GLOBALS['FORUM_DRIVER']->get_member_row_field($member, 'm_password_compat_scheme') == 'temporary') && (get_page_name() != 'lost_password') && ((get_page_name() != 'members') || (get_param('type', 'misc') != 'view'))) {
        require_code('users_active_actions');
        _enforce_temporary_passwords($member);
    }
}

/**
 * Get the display name of a username.
 * If no display name generator is configured, this will be the same as the username.
 *
 * @param  ID_TEXT                      The username
 * @return SHORT_TEXT                   The display name
 */
function get_displayname($username)
{
    if ($username == do_lang('UNKNOWN')) {
        return $username;
    }
    if ($username == do_lang('GUEST')) {
        return $username;
    }
    if ($username == do_lang('DELETED')) {
        return $username;
    }

    if (method_exists($GLOBALS['FORUM_DRIVER'], 'get_displayname')) {
        $displayname = $GLOBALS['FORUM_DRIVER']->get_displayname($username);
        return ($displayname === null) ? $username : $displayname;
    }

    return $username;
}

/**
 * Apply hashing to some input. To this date, all forum drivers use md5, but some use it differently.
 * This function will pass through the parameters to an equivalent forum_md5 function if it is defined.
 *
 * @param  string                       The data to hash (the password in actuality)
 * @param  string                       The string converted member-ID in actuality, although this function is more general
 * @return string                       The hashed data
 */
function apply_forum_driver_md5_variant($data, $key)
{
    if (method_exists($GLOBALS['FORUM_DRIVER'], 'forum_md5')) {
        return $GLOBALS['FORUM_DRIVER']->forum_md5($data, $key);
    }
    return md5($data);
}

/**
 * Get the current session ID.
 *
 * @return ID_TEXT                      The current session ID (blank: none)
 */
function get_session_id()
{
    $cookie_var = get_session_cookie();

    if ((!isset($_COOKIE[$cookie_var])) || (/*To work around OcCLE's development mode trick*/
            $GLOBALS['DEV_MODE'] && running_script('occle'))
    ) {
        if (array_key_exists('keep_session', $_GET)) {
            return get_param('keep_session');
        }
        return '';
    }
    return isset($_COOKIE[$cookie_var]) ? $_COOKIE[$cookie_var] : '';
}

/**
 * Find whether the current member is logged in via httpauth.
 *
 * @return boolean                      Whether the current member is logged in via httpauth
 */
function is_httpauth_login()
{
    if (get_forum_type() != 'ocf') {
        return false;
    }
    if (is_guest()) {
        return false;
    }

    require_code('ocf_members');
    return ((array_key_exists('PHP_AUTH_USER', $_SERVER)) && (!is_null(ocf_authusername_is_bound_via_httpauth($_SERVER['PHP_AUTH_USER']))));
}

/**
 * Make sure that the given URL contains a session if cookies are disabled.
 * NB: This is used for login redirection. It had to add the session ID into the redirect url.
 *
 * @param  URLPATH                      The URL to enforce results in session persistence for the user
 * @return URLPATH                      The fixed URL (potentially nothing was done, depending on cookies)
 */
function enforce_sessioned_url($url)
{
    if ((!has_cookies()) && (is_null(get_bot_type()))) {
        require_code('users_inactive_occasionals');
        return _enforce_sessioned_url($url);
    }
    return $url;
}

/**
 * Find what sessions are expired and delete them, and recover an existing one for $member if there is one.
 *
 * @param  ?MEMBER                      User to get a current session for (NULL: do not try, which guarantees a return result of NULL also)
 * @return ?AUTO_LINK                   The session ID we rebound to (NULL: did not rebind)
 */
function delete_expired_sessions_or_recover($member = null)
{
    $new_session = null;

    $ip = get_ip_address(3);

    // Delete expired sessions; it's important we do this routinely, not randomly, as the session table is loaded up and can get large -- unless we aren't tracking online users, in which case the table is never loaded up
    if ((get_value('disable_user_online_counting') !== '1') || (get_option('session_prudence') != '1') || (mt_rand(0, 1000) == 123)) {
        if (!$GLOBALS['SITE_DB']->table_is_locked('sessions')) {
            $GLOBALS['SITE_DB']->query('DELETE FROM ' . get_table_prefix() . 'sessions WHERE last_activity<' . strval(time() - intval(60.0 * 60.0 * max(0.017, floatval(get_option('session_expiry_time'))))));
        }
    }

    // Look through sessions
    $new_session = null;
    $dirty_session_cache = false;
    global $SESSION_CACHE;
    foreach ($SESSION_CACHE as $_session => $row) {
        if (!array_key_exists('member_id', $row)) {
            continue; // Workaround to HipHop PHP weird bug
        }

        // Delete expiry from cache
        if ($row['last_activity'] < time() - intval(60.0 * 60.0 * max(0.017, floatval(get_option('session_expiry_time'))))) {
            $dirty_session_cache = true;
            unset($SESSION_CACHE[$_session]);
            continue;
        }

        // Get back to prior session if there was one
        if ($member !== null) {
            if (($row['member_id'] == $member) && (((get_option('ip_strict_for_sessions') == '0') && ($member != $GLOBALS['FORUM_DRIVER']->get_guest_id())) || ($row['ip'] == $ip)) && ($row['last_activity'] > time() - intval(60.0 * 60.0 * max(0.017, floatval(get_option('session_expiry_time')))))) {
                $new_session = $_session;
            }
        }
    }
    if ($dirty_session_cache) {
        if (get_option('session_prudence') != '1') {
            persistent_cache_set('SESSION_CACHE', $SESSION_CACHE);
        }
    }

    return $new_session;
}

/**
 * Get the member cookie's name.
 *
 * @return string                       The member username/ID (depending on forum driver) cookie's name
 */
function get_member_cookie()
{
    global $SITE_INFO;
    if (!array_key_exists('user_cookie', $SITE_INFO)) {
        $SITE_INFO['user_cookie'] = 'ocp_member_id';
    }
    return $SITE_INFO['user_cookie'];
}

/**
 * Get the session cookie's name.
 *
 * @return string                       The session ID cookie's name
 */
function get_session_cookie()
{
    global $SITE_INFO;
    if (!array_key_exists('session_cookie', $SITE_INFO)) {
        $SITE_INFO['session_cookie'] = 'ocp_session';
    }
    return $SITE_INFO['session_cookie'];
}

/**
 * Get the member password cookie's name.
 *
 * @return string                       The member password cookie's name
 */
function get_pass_cookie()
{
    global $SITE_INFO;
    if (!array_key_exists('pass_cookie', $SITE_INFO)) {
        $SITE_INFO['pass_cookie'] = 'ocp_member_hash';
    }
    return $SITE_INFO['pass_cookie'];
}

/**
 * Get a cookie value.
 *
 * @param  string                       The name of the cookie
 * @param  ?string                      The default value (NULL: just use the value NULL)
 * @return ?string                      The value stored in the cookie (NULL: the default default)
 */
function ocp_admirecookie($name, $default = null)
{
    if (!isset($_COOKIE[$name])) {
        return $default;
    }
    $the_cookie = $_COOKIE[$name];
    if (get_magic_quotes_gpc()) {
        $the_cookie = stripslashes($the_cookie);
    }
    return $the_cookie;
}

/**
 * Get the value of a special 'ocp_' custom profile field. For OCF it can also do it for a pure field title, e.g. "Example Field".
 *
 * @param  ID_TEXT                      The CPF name stem
 * @param  ?MEMBER                      Member to lookup for (NULL: current member)
 * @return string                       The value (blank: has a blank value, or does not exist)
 */
function get_ocp_cpf($cpf, $member = null)
{
    if (is_null($member)) {
        $member = get_member();
    }

    $values = $GLOBALS['FORUM_DRIVER']->get_custom_fields($member);
    if (is_null($values)) {
        return '';
    }
    if (array_key_exists($cpf, $values)) {
        return $values[$cpf];
    }

    if (get_forum_type() == 'ocf') {
        $values = ocf_get_all_custom_fields_match_member($member);
        if (array_key_exists($cpf, $values)) {
            return $values[$cpf]['RAW'];
        }
    }

    return '';
}

/**
 * Get the name of the default theme, assuming it exists. This is based on the site name.
 *
 * @return string                       Theme name
 */
function get_default_theme_name()
{
    return substr(preg_replace('#[^A-Za-z\d]#', '_', get_site_name()), 0, 80);
}
