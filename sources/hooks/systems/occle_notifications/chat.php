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
 * @package    chat
 */

class Hook_occle_notification_chat
{
    /**
    * Run function for OcCLE notification hooks.
    *
    * @param  ?integer                  The "current" time on which to base queries (NULL: now)
    * @return ~array                    Array of section, type and message responses (false: nothing)
    */
    public function run($timestamp = null)
    {
        if (!addon_installed('chat')) {
            return false;
        }

        if (!is_null(get_value('occle_watched_chatroom'))) {
            require_lang('chat');

            if (is_null($timestamp)) {
                $timestamp = time();
            }
            $room = intval(get_value('occle_watched_chatroom'));
            $room_messages = $GLOBALS['SITE_DB']->query('SELECT COUNT(*) AS cnt FROM ' . get_table_prefix() . 'chat_messages WHERE room_id=' . strval($room) . ' AND date_and_time>=' . strval($timestamp));
            if (!array_key_exists(0,$room_messages)) {
                return false;
            }

            if ($room_messages[0]['cnt']>0) {
                $rooms = array();
                $messages = $room_messages[0]['cnt'];

                $room_data = $GLOBALS['SITE_DB']->query_select_value_if_there('chat_rooms','room_name',array('id' => $room));
                if (is_null($room_data)) {
                    return false; // Selected room deleted
                }
                $rooms[$room_data] = build_url(array('page' => 'chat','type' => 'room','id' => $room),get_module_zone('chat'));

                return array(do_lang('SECTION_CHAT'),do_lang('NEW_MESSAGES'),do_template('OCCLE_CHAT_NOTIFICATION',array('_GUID' => '2c63d91d1e3c88d5620b2122a73a8e1f','MESSAGE_COUNT' => integer_format($messages),'CHATROOMS' => $rooms)));
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
