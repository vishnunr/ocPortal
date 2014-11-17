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
class Hook_page_groupings_ocf_forum
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER                  Member ID to run as (null: current member)
     * @param  boolean                  Whether to use extensive documentation tooltips, rather than short summaries
     * @return array                    List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null, $extensive_docs = false)
    {
        return array(
            (get_forum_type() != 'ocf' || !addon_installed('ocf_clubs')) ? null : array('cms', 'menu/cms/clubs', array('cms_ocf_groups', array('type' => 'misc'), get_module_zone('cms_ocf_groups')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('ocf:CLUBS'), make_string_tempcode(escape_html(integer_format($GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups', 'COUNT(*)', array('g_is_private_club' => 1), '', true))))), 'ocf:DOC_CLUBS'),
            (get_forum_type() != 'ocf') ? null : array('structure', 'menu/social/forum/forums', array('admin_ocf_forums', array('type' => 'misc'), get_module_zone('admin_ocf_forums')), do_lang_tempcode('SECTION_FORUMS'), 'ocf:DOC_FORUMS'),
            array('social', 'menu/social/forum/forums', array('forumview', array(), get_module_zone('forumview')), do_lang_tempcode('SECTION_FORUMS')),
        );
    }
}
