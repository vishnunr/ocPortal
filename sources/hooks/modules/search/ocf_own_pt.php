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
class Hook_search_ocf_own_pt
{
    /**
     * Find details for this search hook.
     *
     * @param  boolean                  Whether to check permissions.
     * @return ?array                   Map of search hook details (null: hook is disabled).
     */
    public function info($check_permissions = true)
    {
        if (get_forum_type() != 'ocf') {
            return null;
        }

        if ($check_permissions) {
            if (!has_actual_page_access(get_member(), 'topicview')) {
                return null;
            }

            if (get_member() == $GLOBALS['OCF_DRIVER']->get_guest_id()) {
                return null;
            }
        }

        if ($GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics WHERE t_pt_from=' . strval(get_member()) . ' OR ' . 't_pt_to=' . strval(get_member())) == 0) {
            return null;
        }

        require_lang('ocf');

        $info = array();
        $info['lang'] = do_lang_tempcode('PRIVATE_TOPICS');
        $info['default'] = false;
        $info['special_on'] = array();
        $info['special_off'] = array('starter' => do_lang_tempcode('POST_SEARCH_STARTER'));

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('topicview'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('topicview'),
                'page_name' => 'topicview',
            ),
            array(
                'type' => 'non_guests',
            ),
        );

        return $info;
    }

    /**
     * Run function for search results.
     *
     * @param  string                   Search string
     * @param  boolean                  Whether to only do a META (tags) search
     * @param  ID_TEXT                  Order direction
     * @param  integer                  Start position in total results
     * @param  integer                  Maximum results to return in total
     * @param  boolean                  Whether only to search titles (as opposed to both titles and content)
     * @param  string                   Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
     * @param  SHORT_TEXT               Username/Author to match for
     * @param  ?MEMBER                  Member-ID to match for (null: unknown)
     * @param  TIME                     Cutoff date
     * @param  string                   The sort type (gets remapped to a field in this function)
     * @set    title add_date
     * @param  integer                  Limit to this number of results
     * @param  string                   What kind of boolean search to do
     * @set    or and
     * @param  string                   Where constraints known by the main search code (SQL query fragment)
     * @param  string                   Comma-separated list of categories to search under
     * @param  boolean                  Whether it is a boolean search
     * @return array                    List of maps (template, orderer)
     */
    public function run($content, $only_search_meta, $direction, $max, $start, $only_titles, $content_where, $author, $author_id, $cutoff, $sort, $limit_to, $boolean_operator, $where_clause, $search_under, $boolean_search)
    {
        if (get_forum_type() != 'ocf') {
            return array();
        }
        if (get_member() == $GLOBALS['OCF_DRIVER']->get_guest_id()) {
            return array();
        }
        require_code('ocf_forums');
        require_code('ocf_posts');
        require_css('ocf');

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'p_title';
                break;

            case 'add_date':
                $remapped_orderer = 'p_time';
                break;
        }

        require_lang('ocf');

        // Calculate our where clause (search)
        $where_clause .= ' AND ';
        $where_clause .= 't_forum_id IS NULL AND (t_pt_from=' . strval(get_member()) . ' OR t_pt_to=' . strval(get_member()) . ')';
        $sq = build_search_submitter_clauses('p_poster', $author_id, $author);
        if (is_null($sq)) {
            return array();
        } else {
            $where_clause .= $sq;
        }
        if (!is_null($cutoff)) {
            $where_clause .= ' AND ';
            $where_clause .= 'p_time>' . strval($cutoff);
        }
        if (get_param_integer('option_ocf_posts_starter', 0) == 1) {
            $where_clause .= ' AND ';
            $where_clause .= 's.t_cache_first_post_id=r.id';
        }

        if ((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) {
            $where_clause .= ' AND ';
            $where_clause .= 'p_validated=1';
        }

        // Calculate and perform query
        $rows = get_search_rows(null, null, $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, 'f_posts r JOIN ' . get_table_prefix() . 'f_topics s ON r.p_topic_id=s.id', array('!' => '!', 'r.p_post' => 'LONG_TRANS__COMCODE'), $where_clause, $content_where, $remapped_orderer, 'r.*', array('r.p_title'));

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer, $row))) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            } elseif (strpos($remapped_orderer, '_rating:') !== false) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            }
        }

        return $out;
    }

    /**
     * Run function for rendering a search result.
     *
     * @param  array                    The data row stored when we retrieved the result
     * @return tempcode                 The output
     */
    public function render($row)
    {
        require_code('ocf_posts2');
        return render_post_box($row);
    }
}
