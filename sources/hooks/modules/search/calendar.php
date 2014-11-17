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
 * @package    calendar
 */

/**
 * Hook class.
 */
class Hook_search_calendar
{
    /**
     * Find details for this search hook.
     *
     * @param  boolean                  Whether to check permissions.
     * @return ?array                   Map of search hook details (null: hook is disabled).
     */
    public function info($check_permissions = true)
    {
        if (!module_installed('calendar')) {
            return null;
        }

        if ($check_permissions) {
            if (!has_actual_page_access(get_member(), 'calendar')) {
                return null;
            }
        }

        if ($GLOBALS['SITE_DB']->query_select_value('calendar_events', 'COUNT(*)') == 0) {
            return null;
        }

        require_lang('calendar');

        $info = array();
        $info['lang'] = do_lang_tempcode('CALENDAR');
        $info['default'] = false;

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('calendar'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('calendar'),
                'page_name' => 'calendar',
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
        require_lang('calendar');

        $table = 'calendar_events r';

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'e_title';
                break;

            case 'add_date':
                $remapped_orderer = 'e_add_date';
                break;
        }

        // Calculate our where clause (search)
        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            list($privacy_join, $privacy_where) = get_privacy_where_clause('event', 'r', null, 'r.e_member_calendar=' . strval(get_member()));
            $table .= $privacy_join;
            $where_clause .= $privacy_where;
        }
        $where_clause .= ' AND ';
        $where_clause .= '(e_member_calendar IS NULL'; // Not a privacy thing, more of a relevance thing
        if (!is_guest()) {
            $where_clause .= ' OR e_submitter=' . strval(get_member());
            $where_clause .= ' OR e_member_calendar=' . strval(get_member());
        }
        $where_clause .= ')';
        $sq = build_search_submitter_clauses('e_submitter', $author_id, $author);
        if (is_null($sq)) {
            return array();
        } else {
            $where_clause .= $sq;
        }
        if (!is_null($cutoff)) {
            $where_clause .= ' AND ';
            $where_clause .= 'e_add_date>' . strval(intval($cutoff));
        }
        $where_clause .= ' AND ';
        $where_clause .= 'e_type<>' . strval(db_get_first_id());
        if ((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) {
            $where_clause .= ' AND ';
            $where_clause .= 'validated=1';
        }

        // Calculate and perform query
        $rows = get_search_rows('event', 'id', $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, $table, array('r.e_title' => 'SHORT_TRANS', 'r.e_content' => 'LONG_TRANS__COMCODE'), $where_clause, $content_where, $remapped_orderer, 'r.*', null, 'calendar', 'e_type');

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
        require_code('calendar');
        return render_event_box($row);
    }
}
