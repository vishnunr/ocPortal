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
class Hook_unvalidated_ocf_posts
{
    /**
     * Find details on the unvalidated hook.
     *
     * @return ?array                   Map of hook info (null: hook is disabled).
     */
    public function info()
    {
        if (get_forum_type() != 'ocf') {
            return null;
        }

        require_lang('ocf');

        $info = array();
        $info['db_table'] = 'f_posts';
        $info['db_identifier'] = 'id';
        $info['db_validated'] = 'p_validated';
        $info['db_title'] = 'p_title';
        $info['db_title_dereference'] = false;
        $info['db_add_date'] = 'p_time';
        $info['db_edit_date'] = 'p_last_edit_time';
        $info['edit_module'] = 'topics';
        $info['edit_type'] = 'edit_post';
        $info['edit_identifier'] = 'id';
        $info['title'] = do_lang_tempcode('FORUM_POSTS');
        $info['is_minor'] = true;
        $info['db'] = $GLOBALS['FORUM_DB'];

        return $info;
    }
}
