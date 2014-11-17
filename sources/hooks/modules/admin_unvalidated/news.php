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
 * @package    news
 */

/**
 * Hook class.
 */
class Hook_unvalidated_news
{
    /**
     * Find details on the unvalidated hook.
     *
     * @return ?array                   Map of hook info (null: hook is disabled).
     */
    public function info()
    {
        if (!module_installed('news')) {
            return null;
        }

        require_lang('news');

        $info = array();
        $info['db_table'] = 'news';
        $info['db_identifier'] = 'id';
        $info['db_validated'] = 'validated';
        $info['db_title'] = 'title';
        $info['db_title_dereference'] = true;
        $info['db_add_date'] = 'date_and_time';
        $info['db_edit_date'] = 'edit_date';
        $info['edit_module'] = 'cms_news';
        $info['edit_type'] = '_ed';
        $info['edit_identifier'] = 'id';
        $info['title'] = do_lang_tempcode('NEWS');

        return $info;
    }
}
