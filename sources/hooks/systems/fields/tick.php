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
class Hook_fields_tick
{
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
        $fields = array();
        $type = '_LIST';
        $special = new Tempcode();
        $special->attach(form_input_list_entry('', get_param('option_' . strval($row['id']), '') == '', do_lang_tempcode('NA_EM')));
        $special->attach(form_input_list_entry('0', get_param('option_' . strval($row['id']), '') == '0', do_lang_tempcode('NO')));
        $special->attach(form_input_list_entry('1', get_param('option_' . strval($row['id']), '') == '1', do_lang_tempcode('YES')));
        $display = array_key_exists('trans_name', $row) ? $row['trans_name'] : get_translated_text($row['cf_name']); // 'trans_name' may have been set in CPF retrieval API, might not correspond to DB lookup if is an internal field
        $fields[] = array('NAME' => strval($row['id']), 'DISPLAY' => $display, 'TYPE' => $type, 'SPECIAL' => $special);
        return $fields;
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
        return null;
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
        if ($required !== null) {
            if (($required) && ($default == '')) {
                $default = '0';
            }
        }
        return array('integer_unescaped', $default, 'integer');
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
            return do_lang_tempcode('NA_EM');
        }
        return ($ev == '1') ? do_lang_tempcode('YES') : do_lang_tempcode('NO');
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
        if ($field['cf_required'] == 1) {
            return form_input_tick($_cf_name, $_cf_description, 'field_' . strval($field['id']), $actual_value == '1');
        }
        $_list = new Tempcode();
        $_list->attach(form_input_list_entry('', is_null($actual_value) || ($actual_value === ''), do_lang_tempcode('NA_EM')));
        $_list->attach(form_input_list_entry('0', $actual_value === '0', do_lang_tempcode('NO')));
        $_list->attach(form_input_list_entry('1', $actual_value === '1', do_lang_tempcode('YES')));
        return form_input_list($_cf_name, $_cf_description, 'field_' . strval($field['id']), $_list, null, false, $field['cf_required'] == 1);
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
        $tmp_name = 'field_' . strval($id);
        return post_param($tmp_name, ($editing && is_null(post_param('tick_on_form__' . $tmp_name, null))) ? STRING_MAGIC_NULL : '');
    }
}
