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
 * @package    quizzes
 */

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_occle_fs_quizzes extends Resource_fs_base
{
    public $file_resource_type = 'quiz';

    /**
     * Standard occle_fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT                  The resource type
     * @return integer                  How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        return $GLOBALS['SITE_DB']->query_select_value('quizzes', 'COUNT(*)');
    }

    /**
     * Standard occle_fs function for searching for a resource by label.
     *
     * @param  ID_TEXT                  The resource type
     * @param  LONG_TEXT                The resource label
     * @return array                    A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        $_ret = $GLOBALS['SITE_DB']->query_select('quizzes', array('id'), array($GLOBALS['SITE_DB']->translate_field_ref('q_name') => $label));
        $ret = array();
        foreach ($_ret as $r) {
            $ret[] = strval($r['id']);
        }
        return $ret;
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    protected function _enumerate_file_properties()
    {
        return array(
            'timeout' => '?TIME',
            'start_text' => 'LONG_TRANS',
            'end_text' => 'LONG_TRANS',
            'end_text_fail' => 'LONG_TRANS',
            'notes' => 'LONG_TEXT',
            'percentage' => 'INTEGER',
            'open_time' => 'TIME',
            'close_time' => '?TIME',
            'num_winners' => 'INTEGER',
            'redo_time' => '?INTEGER',
            'type' => 'ID_TEXT',
            'validated' => 'BINARY',
            'text' => 'LONG_TRANS',
            'submitter' => 'member',
            'points_for_passing' => 'INTEGER',
            //'tied_newsletter'=>'?newsletter',
            'reveal_answers' => 'BINARY',
            'shuffle_questions' => 'BINARY',
            'shuffle_answers' => 'BINARY',
            'add_date' => '?TIME',
            'meta_keywords' => 'LONG_TRANS',
            'meta_description' => 'LONG_TRANS',
        );
    }

    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array                    Resource row (not full, but does contain the ID)
     * @return ?TIME                    The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_QUIZ') . ' OR ' . db_string_equal_to('the_type', 'EDIT_QUIZ') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard occle_fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT                Filename OR Resource label
     * @param  string                   The path (blank: root / not applicable)
     * @param  array                    Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties)
    {
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('quiz2');

        $timeout = $this->_default_property_int($properties, 'timeout');
        $start_text = $this->_default_property_str($properties, 'start_text');
        $end_text = $this->_default_property_str($properties, 'end_text');
        $end_text_fail = $this->_default_property_str($properties, 'end_text_fail');
        $notes = $this->_default_property_str($properties, 'notes');
        $percentage = $this->_default_property_int($properties, 'percentage');
        $open_time = $this->_default_property_int_null($properties, 'open_time');
        if (is_null($open_time)) {
            $open_time = time();
        }
        $close_time = $this->_default_property_int_null($properties, 'close_time');
        $num_winners = $this->_default_property_int($properties, 'num_winners');
        $redo_time = $this->_default_property_int($properties, 'redo_time');
        $type = $this->_default_property_str($properties, 'type');
        if ($type == '') {
            $type = 'SURVEY';
        }
        $validated = $this->_default_property_int_null($properties, 'validated');
        if (is_null($validated)) {
            $validated = 1;
        }
        $text = $this->_default_property_str($properties, 'text');
        $submitter = $this->_default_property_int_null($properties, 'submitter');
        $points_for_passing = $this->_default_property_int($properties, 'points_for_passing');
        $tied_newsletter = null;//$this->_default_property_int_null($properties,'tied_newsletter');
        $reveal_answers = $this->_default_property_int($properties, 'reveal_answers');
        $shuffle_questions = $this->_default_property_int($properties, 'shuffle_questions');
        $shuffle_answers = $this->_default_property_int($properties, 'shuffle_answers');
        $add_time = $this->_default_property_int_null($properties, 'add_date');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');
        $id = add_quiz($label, $timeout, $start_text, $end_text, $end_text_fail, $notes, $percentage, $open_time, $close_time, $num_winners, $redo_time, $type, $validated, $text, $submitter, $points_for_passing, $tied_newsletter, $reveal_answers, $shuffle_questions, $shuffle_answers, $add_time, $meta_keywords, $meta_description);
        return strval($id);
    }

    /**
     * Standard occle_fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT               Filename
     * @param  string                   The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array                   Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['SITE_DB']->query_select('quizzes', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        require_code('quiz2');
        $text = load_quiz_questions_to_string(intval($resource_id));

        list($meta_keywords, $meta_description) = seo_meta_get_for('quiz', strval($row['id']));

        return array(
            'label' => $row['q_name'],
            'timeout' => $row['q_timeout'],
            'start_text' => $row['q_start_text'],
            'end_text' => $row['q_end_text'],
            'end_text_fail' => $row['q_end_text_fail'],
            'notes' => $row['q_notes'],
            'percentage' => $row['q_percentage'],
            'open_time' => $row['q_open_time'],
            'close_time' => $row['q_close_time'],
            'num_winners' => $row['q_num_winners'],
            'redo_time' => $row['q_redo_time'],
            'type' => $row['q_type'],
            'validated' => $row['q_validated'],
            'text' => $text,
            'submitter' => $row['q_submitter'],
            'points_for_passing' => $row['q_points_for_passing'],
            //'tied_newsletter'=>$row['q_tied_newsletter'],
            'reveal_answers' => $row['q_reveal_answers'],
            'shuffle_questions' => $row['q_shuffle_questions'],
            'shuffle_answers' => $row['q_shuffle_answers'],
            'add_date' => $row['q_add_date'],
            'meta_keywords' => $meta_keywords,
            'meta_description' => $meta_description,
        );
    }

    /**
     * Standard occle_fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT                  The filename
     * @param  string                   The path (blank: root / not applicable)
     * @param  array                    Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('quiz2');

        $label = $this->_default_property_str($properties, 'label');
        $timeout = $this->_default_property_int($properties, 'timeout');
        $start_text = $this->_default_property_str($properties, 'start_text');
        $end_text = $this->_default_property_str($properties, 'end_text');
        $end_text_fail = $this->_default_property_str($properties, 'end_text_fail');
        $notes = $this->_default_property_str($properties, 'notes');
        $percentage = $this->_default_property_int($properties, 'percentage');
        $open_time = $this->_default_property_int_null($properties, 'open_time');
        if (is_null($open_time)) {
            $open_time = time();
        }
        $close_time = $this->_default_property_int_null($properties, 'close_time');
        $num_winners = $this->_default_property_int($properties, 'num_winners');
        $redo_time = $this->_default_property_int($properties, 'redo_time');
        $type = $this->_default_property_str($properties, 'type');
        if ($type == '') {
            $type = 'SURVEY';
        }
        $validated = $this->_default_property_int_null($properties, 'validated');
        if (is_null($validated)) {
            $validated = 1;
        }
        $text = $this->_default_property_str($properties, 'text');
        $submitter = $this->_default_property_int_null($properties, 'submitter');
        $points_for_passing = $this->_default_property_int($properties, 'points_for_passing');
        $tied_newsletter = null;//$this->_default_property_int_null($properties,'tied_newsletter');
        $reveal_answers = $this->_default_property_int($properties, 'reveal_answers');
        $shuffle_questions = $this->_default_property_int($properties, 'shuffle_questions');
        $shuffle_answers = $this->_default_property_int($properties, 'shuffle_answers');
        $add_time = $this->_default_property_int_null($properties, 'add_date');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');

        edit_quiz(intval($resource_id), $label, $timeout, $start_text, $end_text, $end_text_fail, $notes, $percentage, $open_time, $close_time, $num_winners, $redo_time, $type, $validated, $text, $meta_keywords, $meta_description, $points_for_passing, $tied_newsletter, $reveal_answers, $shuffle_questions, $shuffle_answers, $add_time, $submitter, true);

        return $resource_id;
    }

    /**
     * Standard occle_fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT                  The filename
     * @param  string                   The path (blank: root / not applicable)
     * @return boolean                  Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('quiz2');
        delete_quiz(intval($resource_id));

        return true;
    }
}
