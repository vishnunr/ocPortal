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
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_fields_content_link_multi
{
    /**
     * Find what field types this hook can serve. This method only needs to be defined if it is not serving a single field type with a name corresponding to the hook itself.
     *
     * @return array                    Map of field type to field type title
     */
    public function get_field_types()
    {
        $hooks = find_all_hooks('systems', 'content_meta_aware');
        $ret = array();
        foreach (array_keys($hooks) as $hook) {
            if ($hook != 'catalogue_entry'/*got a better field hook specifically for catalogue entries*/) {
                // FUDGE: imperfect content type naming schemes
                $declared_hook = $hook;
                if ($hook == 'topic') {
                    $declared_hook = 'forum_topic';
                }

                $ret['ax_' . $declared_hook] = do_lang_tempcode('FIELD_TYPE_content_link_multi_x', escape_html($hook));
            }
        }
        return $ret;
    }

    // ==============
    // Module: search
    // ==============

    /**
     * Get special Tempcode for inputting this field.
     *
     * @param  array                    The row for the field to input
     * @return ?array                   List of specially encoded input detail rows (null: nothing special)
     */
    public function get_search_inputter($row)
    {
        return null;
    }

    /**
     * Get special SQL from POSTed parameters for this field.
     *
     * @param  array                    The row for the field to input
     * @param  integer                  We're processing for the ith row
     * @return ?array                   Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (null: nothing special)
     */
    public function inputted_to_sql_for_search($row, $i)
    {
        return exact_match_sql($row, $i);
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
     * Get some info bits relating to our field type, that helps us look it up / set defaults.
     *
     * @param  ?array                   The field details (null: new field)
     * @param  ?boolean                 Whether a default value cannot be blank (null: don't "lock in" a new default value)
     * @param  ?string                  The given default value as a string (null: don't "lock in" a new default value)
     * @return array                    Tuple of details (row-type,default-value-to-use,db row-type)
     */
    public function get_field_value_row_bits($field, $required = null, $default = null)
    {
        unset($field);
        /*if (!is_null($required))
        {
            Nothing special for this hook
        }*/
        return array('long_unescaped', $default, 'long');
    }

    /**
     * Convert a field value to something renderable.
     *
     * @param  array                    The field details
     * @param  mixed                    The raw value
     * @return mixed                    Rendered field (tempcode or string)
     */
    public function render_field_value($field, $ev)
    {
        if (is_object($ev)) {
            return $ev;
        }

        if ($ev == '') {
            return new Tempcode();
        }

        $type = preg_replace('#^choose\_#', '', substr($field['cf_type'], 3));

        // FUDGE: imperfect content type naming schemes
        if ($type == 'forum_topic') {
            $type = 'topic';
        }

        $ret = new Tempcode();
        $evs = explode("\n", $ev);
        foreach ($evs as $ev) {
            require_code('content');
            list($title, , $info) = content_get_details($type, $ev);

            $page_link = str_replace('_WILD', $ev, $info['view_page_link_pattern']);
            list($zone, $map) = page_link_decode($page_link);

            $ret->attach(paragraph(hyperlink(build_url($map, $zone), $title, false, true)));
        }
        return $ret;
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
     * Get form inputter.
     *
     * @param  string                   The field name
     * @param  string                   The field description
     * @param  array                    The field details
     * @param  ?string                  The actual current value of the field (null: none)
     * @param  boolean                  Whether this is for a new entry
     * @return ?tempcode                The Tempcode for the input field (null: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value, $new)
    {
        $options = array();
        $type = substr($field['cf_type'], 3);

        // Nice tree list selection
        if ((is_file(get_file_base() . '/sources/hooks/systems/ajax_tree/choose_' . $type . '.php')) || (is_file(get_file_base() . '/sources_custom/hooks/systems/ajax_tree/choose_' . $type . '.php'))) {
            return form_input_tree_list($_cf_name, $_cf_description, 'field_' . strval($field['id']), null, 'choose_' . $type, $options, $field['cf_required'] == 1, str_replace("\n", ',', $actual_value), false, null, true);
        }

        // Simple list selection
        require_code('content');
        $ob = get_content_object($type);
        $info = $ob->info();
        $db = $GLOBALS[(substr($type, 0, 4) == 'ocf_') ? 'FORUM_DB' : 'SITE_DB'];
        $select = array();
        append_content_select_for_id($select, $info);
        if ($type == 'comcode_page') {
            $select[] = 'the_zone';
        }
        if (!is_null($info['title_field'])) {
            $select[] = $info['title_field'];
        }
        $rows = $db->query_select($info['table'], $select, null, is_null($info['add_time_field']) ? '' : ('ORDER BY ' . $info['add_time_field'] . ' DESC'), 2000/*reasonable limit*/);
        $list = new Tempcode();
        $_list = array();
        foreach ($rows as $row) {
            $id = extract_content_str_id_from_data($row, $info);
            if (is_null($info['title_field'])) {
                $text = $id;
            } else {
                $text = $info['title_field_dereference'] ? get_translated_text($row[$info['title_field']]) : $row[$info['title_field']];
            }
            $_list[$id] = $text;
        }
        if (count($_list) < 2000) {
            asort($_list);
        }
        foreach ($_list as $id => $text) {
            if (!is_string($id)) {
                $id = strval($id);
            }
            $list->attach(form_input_list_entry($id, is_null($actual_value) ? false : (strpos("\n" . $actual_value . "\n", $id) !== false), $text));
        }
        return form_input_multi_list($_cf_name, $_cf_description, 'field_' . strval($field['id']), $list, null, 5, $field['cf_required'] == 1);
    }

    /**
     * Find the posted value from the get_field_inputter field
     *
     * @param  boolean                  Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array                    The field details
     * @param  ?string                  Where the files will be uploaded to (null: do not store an upload, return NULL if we would need to do so)
     * @param  ?array                   Former value of field (null: none)
     * @return ?string                  The value (null: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $id = $field['id'];
        $i = 0;
        $value = '';
        $tmp_name = 'field_' . strval($id);
        if (!array_key_exists($tmp_name, $_POST)) {
            return $editing ? STRING_MAGIC_NULL : '';
        }
        foreach (is_array($_POST[$tmp_name]) ? $_POST[$tmp_name] : explode(',', $_POST[$tmp_name]) as $_value) {
            if ($_value != '') {
                if ($value != '') {
                    $value .= "\n";
                }
                $value .= $_value;
            }
        }
        return $value;
    }
}
