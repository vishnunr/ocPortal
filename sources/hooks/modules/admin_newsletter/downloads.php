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
 * @package    downloads
 */

/**
 * Hook class.
 */
class Hook_whatsnew_downloads
{
    /**
     * Find selectable (filterable) categories.
     *
     * @param  TIME                     The time that there must be entries found newer than
     * @return ?array                   Tuple of result details: HTML list of all types that can be choosed, title for selection list (null: disabled)
     */
    public function choose_categories($updated_since)
    {
        if (!addon_installed('downloads')) {
            return null;
        }

        require_lang('downloads');

        require_code('downloads');
        $cats = create_selection_list_download_category_tree(null, false, false, $updated_since);

        return array($cats, do_lang('SECTION_DOWNLOADS'));
    }

    /**
     * Run function for newsletter hooks.
     *
     * @param  TIME                     The time that the entries found must be newer than
     * @param  LANGUAGE_NAME            The language the entries found must be in
     * @param  string                   Category filter to apply
     * @return array                    Tuple of result details
     */
    public function run($cutoff_time, $lang, $filter)
    {
        if (!addon_installed('downloads')) {
            return array();
        }

        require_lang('downloads');

        $max = intval(get_option('max_newsletter_whatsnew'));

        $new = new Tempcode();

        require_code('ocfiltering');
        $or_list = ocfilter_to_sqlfragment($filter, 'category_id');

        $privacy_join = '';
        $privacy_where = '';
        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            list($privacy_join, $privacy_where) = get_privacy_where_clause('download', 'r', $GLOBALS['FORUM_DRIVER']->get_guest_id());
        }

        $rows = $GLOBALS['SITE_DB']->query('SELECT name,description,id,add_date,submitter FROM ' . get_table_prefix() . 'download_downloads r' . $privacy_join . ' WHERE validated=1 AND add_date>' . strval($cutoff_time) . ' AND (' . $or_list . ')' . $privacy_where . ' ORDER BY add_date DESC', $max);

        if (count($rows) == $max) {
            return array();
        }

        foreach ($rows as $row) {
            $id = $row['id'];
            $_url = build_url(array('page' => 'downloads', 'type' => 'entry', 'id' => $row['id']), get_module_zone('downloads'), null, false, false, true);
            $url = $_url->evaluate();
            $name = get_translated_text($row['name'], null, $lang);
            $description = get_translated_text($row['description'], null, $lang);
            $member_id = (is_guest($row['submitter'])) ? null : strval($row['submitter']);
            $thumb_url = mixed();
            if (addon_installed('galleries')) {
                $thumbnail = $GLOBALS['SITE_DB']->query_select_value_if_there('images', 'thumb_url', array('cat' => 'download_' . strval($row['id'])), 'ORDER BY add_date ASC');
                if (!is_null($thumbnail)) {
                    if ($thumbnail != '') {
                        if (url_is_local($thumbnail)) {
                            $thumbnail = get_custom_base_url() . '/' . $thumbnail;
                        }
                    } else {
                        $thumbnail = mixed();
                    }
                }
            }
            $new->attach(do_template('NEWSLETTER_WHATSNEW_RESOURCE_FCOMCODE', array('_GUID' => 'bbd85ed54500b9d6df998e3c835b45e9', 'MEMBER_ID' => $member_id, 'URL' => $url, 'NAME' => $name, 'DESCRIPTION' => $description, 'CONTENT_TYPE' => 'download', 'CONTENT_ID' => strval($id))));
        }

        return array($new, do_lang('SECTION_DOWNLOADS', '', '', '', $lang));
    }
}
