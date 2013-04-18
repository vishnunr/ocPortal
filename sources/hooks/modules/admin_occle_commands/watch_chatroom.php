<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		chat
 */

class Hook_watch_chatroom
{
	/**
	 * Standard modular run function for OcCLE hooks.
	 *
	 * @param  array	The options with which the command was called
	 * @param  array	The parameters with which the command was called
	 * @param  array	A reference to the OcCLE filesystem object
	 * @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	 */
	function run($options,$parameters,&$occle_fs)
	{
		if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) return array('',do_command_help('watch_chatroom',array('h','u'),array(true)),'','');
		else
		{
			require_code('chat');

			if ((array_key_exists('u',$options)) || (array_key_exists('unwatch',$options)))
			{
				delete_value('occle_watched_chatroom');
				$_chatroom=do_lang('SUCCESS');
			}
			elseif (array_key_exists(0,$parameters))
			{
				if (is_numeric($parameters[0])) $chatroom=$parameters[0];
				else $chatroom=get_chatroom_id($parameters[0]);

				if (is_null($chatroom)) return array('','','',do_lang('MISSING_RESOURCE'));

				set_value('occle_watched_chatroom',$chatroom);

				$_chatroom=get_chatroom_name($chatroom);
			} else
			{
				$_chatroom=get_chatroom_name(intval(get_value('occle_watched_chatroom')),true);
				if (is_null($_chatroom)) return array('','','',do_lang('MISSING_RESOURCE'));
			}

			return array('','',$_chatroom,'');
		}
	}

}
