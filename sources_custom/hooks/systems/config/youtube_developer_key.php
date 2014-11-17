<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    gallery_syndication
 */

/**
 * Hook class.
 */
class Hook_config_youtube_developer_key
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'YOUTUBE_DEVELOPER_KEY',
            'type' => 'line',
            'category' => 'GALLERY',
            'group' => 'GALLERY_SYNDICATION',
            'explanation' => 'CONFIG_OPTION_youtube_developer_key',
            'shared_hosting_restricted' => '0',
            'list_options' => '',

            'addon' => 'gallery_syndication',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (null: option is disabled)
     */
    public function get_default()
    {
        return '';
    }
}
