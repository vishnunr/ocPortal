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
 * @package    ocf_forum
 */

/**
 * Hook class.
 */
class Hook_config_forced_preview_option
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'FORCED_PREVIEW_OPTION',
            'type' => 'tick',
            'category' => 'FEATURE',
            'group' => 'PREVIEW',
            'explanation' => 'CONFIG_OPTION_forced_preview_option',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 3,

            'addon' => 'ocf_forum',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (null: option is disabled)
     */
    public function get_default()
    {
        return (get_forum_type() != 'ocf') ? null : '0';
    }
}
