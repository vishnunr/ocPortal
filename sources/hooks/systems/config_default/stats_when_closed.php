<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_configuration
 */

class Hook_config_default_stats_when_closed
{

	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'STATS_WHEN_CLOSED',
			'the_type'=>'tick',
			'c_category'=>'SITE',
			'c_group'=>'CLOSED_SITE',
			'explanation'=>'CONFIG_OPTION_stats_when_closed',
			'shared_hosting_restricted'=>'0',
			'c_data'=>'',

			'addon'=>'core_configuration',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		if ((substr(ocp_srv('HTTP_HOST'),0,8)=='192.168.') || (substr(ocp_srv('HTTP_HOST'),0,7)=='10.0.0.') || (in_array(ocp_srv('HTTP_HOST'),array('localhost')))) return '1'; return '0';
	}

}

