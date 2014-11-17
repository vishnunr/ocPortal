<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    oc_jester
 */

/**
 * Hook class.
 */
class Hook_config_ocjester_name_changes
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'OCJESTER_NAME_CHANGES',
            'type' => 'text',
            'category' => 'FEATURE',
            'group' => 'OCJESTER_TITLE',
            'explanation' => 'CONFIG_OPTION_ocjester_name_changes',
            'shared_hosting_restricted' => '0',
            'list_options' => '',

            'addon' => 'oc_jester',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (null: option is disabled)
     */
    public function get_default()
    {
        return "Angelic\nBaubles\nChristmas\nDasher\nEvergreen\nFestive\nGifted\nHoliday\nIcicles\nJolly\nKingly\nEnlightened\nMerry\nNoel\nOrnamental\nParty\nKingly\nRudolph\nSeasonal\nTinsel\nYuletide\nVisionary\nWiseman\nXmas\nYuletide\nXmas";
    }
}
