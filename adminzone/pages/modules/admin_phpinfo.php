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
 * @package    phpinfo
 */

/**
 * Module page class.
 */
class Module_admin_phpinfo
{
    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  Whether to check permissions.
     * @param  ?MEMBER                  The member to check permissions as (null: current user).
     * @param  boolean                  Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array(
            '!' => array('PHPINFO', 'menu/adminzone/tools/phpinfo'),
        );
    }

    /**
     * Execute the module.
     *
     * @return tempcode                 The result of execution.
     */
    public function run()
    {
        if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) {
            warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
        }

        // Various checks
        $hooks = find_all_hooks('systems', 'checks');
        $found_issues = false;
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/checks/' . filter_naughty($hook));
            $ob = object_factory('Hook_check_' . $hook);
            $warning = $ob->run();
            foreach ($warning as $_warning) {
                attach_message($_warning, 'warn');
                $found_issues = true;
            }
        }
        if (!$found_issues) {
            attach_message(do_lang_tempcode('NO_SERVER_ISSUES_FOUND'), 'inform', true);
        }

        require_lang('menus');

        get_screen_title('PHPINFO');

        require_css('phpinfo');

        $GLOBALS['SCREEN_TEMPLATE_CALLED'] = '';
        $GLOBALS['TITLE_CALLED'] = true;

        require_lang('menus');
        set_helper_panel_text(comcode_lang_string('DOC_PHPINFO'));

        ob_start();
        if ((function_exists('phpinfo')) && (strpos(@ini_get('disable_functions'), 'phpinfo') === false)) {
            phpinfo();
        } else {
            var_dump(PHP_VERSION);
            var_dump($_SERVER);
            var_dump($_ENV);
            var_dump($_COOKIE);
            if (function_exists('ini_get_all')) {
                var_dump(ini_get_all());
            }
            if (function_exists('get_loaded_extensions')) {
                var_dump(get_loaded_extensions());
            }
            if (function_exists('phpcredits')) {
                var_dump(phpcredits());
            }
        }
        require_code('xhtml');
        $out = xhtmlise_html(ob_get_contents());
        ob_end_clean();

        $out = preg_replace('#<!DOCTYPE[^>]*>#s', '', preg_replace('#</body[^>]*>#', '', preg_replace('#<body[^>]*>#', '', preg_replace('#</html[^>]*>#', '', preg_replace('#<html[^>]*>#', '', $out)))));
        $matches = array();
        if (preg_match('#<style[^>]*>#', $out, $matches) != 0) {
            $offset = strpos($out, $matches[0]) + strlen($matches[0]);
            $end = strpos($out, '</style>', $offset);
            if ($end !== false) {
                $style = substr($out, $offset - strlen($matches[0]), $end - $offset + strlen('</style>') + strlen($matches[0]));
                //attach_to_screen_header(make_string_tempcode($style));      Actually this just makes an unnecessary mess

                $out = substr($out, 0, $offset) . substr($out, $end);
            }
        }
        $out = preg_replace('#<head[^>]*>.*</head[^>]*>#s', '', $out);

        $out = str_replace(' width="600"', ' width="100%"', $out);
        $out = preg_replace('#([^\s<>"\']{65}&[^;]+;)#', '${1}<br />', $out);
        $out = preg_replace('#([^\s<>"\']{95})#', '${1}<br />', $out);
        $url_parts = parse_url(get_base_url());
        $out = str_replace('<img border="0" src="/', '<img border="0" style="padding-top: 20px" src="http://' . escape_html($url_parts['host']) . '/', $out);

        require_code('xhtml');
        $ret = make_string_tempcode(xhtmlise_html($out));
        return $ret;
    }
}
