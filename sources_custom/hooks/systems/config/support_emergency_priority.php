<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    ocportalcom_support_credits
 */

/**
 * Hook class.
 */
class Hook_config_support_emergency_priority
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'SUPPORT_PRIORITY_EMERGENCY_MINUTES',
            'type' => 'float',
            'category' => 'FEATURE',
            'group' => 'SECTION_CUSTOMERS',
            'explanation' => 'CONFIG_OPTION_support_emergency_priority',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 9,

            'addon' => 'ocportalcom_support_credits',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (null: option is disabled)
     */
    public function get_default()
    {
        return '3';
    }
}
