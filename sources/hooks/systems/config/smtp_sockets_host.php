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
 * @package    core_configuration
 */

/**
 * Hook class.
 */
class Hook_config_smtp_sockets_host
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'HOST',
            'type' => 'line',
            'category' => 'SERVER',
            'group' => 'SMTP',
            'explanation' => 'CONFIG_OPTION_smtp_sockets_host',
            'shared_hosting_restricted' => '1',
            'list_options' => '',
            'order_in_category_group' => 2,

            'addon' => 'core_configuration',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (null: option is disabled)
     */
    public function get_default()
    {
        if (!function_exists('fsockopen')) {
            return null;
        }
        if (strpos(@ini_get('disable_functions'), 'shell_exec') !== false) {
            return null;
        }
        return 'mail.yourispwhateveritis.net';
    }
}
