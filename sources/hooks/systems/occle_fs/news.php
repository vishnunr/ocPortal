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

require_code('resource_fs');

class Hook_occle_fs_news extends resource_fs_base
{
    public $folder_resource_type = 'news_category';
    public $file_resource_type = 'news';

    /**
     * Standard occle_fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT                  The resource type
     * @return integer                  How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        switch ($resource_type) {
            case 'news':
                return $GLOBALS['SITE_DB']->query_select_value('news', 'COUNT(*)');

            case 'news_category':
                return $GLOBALS['SITE_DB']->query_select_value('news_categories', 'COUNT(*)');
        }
        return 0;
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
        switch ($resource_type) {
            case 'news':
                $_ret = $GLOBALS['SITE_DB']->query_select('news', array('id'), array($GLOBALS['SITE_DB']->translate_field_ref('title') => $label));
                $ret = array();
                foreach ($_ret as $r) {
                    $ret[] = strval($r['id']);
                }
                return $ret;

            case 'news_category':
                $_ret = $GLOBALS['SITE_DB']->query_select('news_categories', array('id'), array($GLOBALS['SITE_DB']->translate_field_ref('nc_title') => $label));
                $ret = array();
                foreach ($_ret as $r) {
                    $ret[] = strval($r['id']);
                }
                return $ret;
        }
        return array();
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    protected function _enumerate_folder_properties()
    {
        return array(
            'rep_image' => 'URLPATH',
            'notes' => 'LONG_TEXT',
            'owner' => 'member',
        );
    }

    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array                    Resource row (not full, but does contain the ID)
     * @return ?TIME                    The edit date or add date, whichever is higher (NULL: could not find one)
     */
    protected function _get_folder_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_NEWS_CATEGORY') . ' OR ' . db_string_equal_to('the_type', 'EDIT_NEWS_CATEGORY') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard occle_fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT                Filename OR Resource label
     * @param  string                   The path (blank: root / not applicable)
     * @param  array                    Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error)
     */
    public function folder_add($filename, $path, $properties)
    {
        if ($path != '') {
            return false; // Only one depth allowed for this resource type
        }

        list($properties, $label) = $this->_folder_magic_filter($filename, $path, $properties);

        require_code('news2');

        $img = $this->_default_property_str($properties, 'rep_image');
        $notes = $this->_default_property_str($properties, 'notes');
        $owner = $this->_default_property_int_null($properties, 'owner');
        $id = add_news_category($label, $img, $notes, $owner);
        return strval($id);
    }

    /**
     * Standard occle_fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT               Filename
     * @param  string                   The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array                   Details of the resource (false: error)
     */
    public function folder_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        $rows = $GLOBALS['SITE_DB']->query_select('news_categories', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        return array(
            'label' => $row['nc_title'],
            'rep_image' => $row['nc_img'],
            'notes' => $row['notes'],
            'owner' => $row['nc_owner'],
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
    public function folder_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        require_code('news2');

        $label = $this->_default_property_str($properties, 'label');
        $img = $this->_default_property_str($properties, 'rep_image');
        $notes = $this->_default_property_str($properties, 'notes');
        $owner = $this->_default_property_int_null($properties, 'owner');

        edit_news_category(intval($resource_id), $label, $img, $notes, $owner);

        return $resource_id;
    }

    /**
     * Standard occle_fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT                  The filename
     * @param  string                   The path (blank: root / not applicable)
     * @return boolean                  Success status
     */
    public function folder_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        require_code('news2');
        delete_news_category(intval($resource_id));

        return true;
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    protected function _enumerate_file_properties()
    {
        return array(
            'news_summary' => 'LONG_TRANS',
            'news_article' => 'LONG_TRANS',
            'author' => 'author',
            'validated' => 'BINARY',
            'allow_rating' => 'BINARY',
            'allow_comments' => 'SHORT_INTEGER',
            'allow_trackbacks' => 'BINARY',
            'notes' => 'LONG_TEXT',
            'views' => 'INTEGER',
            'image' => 'URLPATH',
            'meta_keywords' => 'LONG_TRANS',
            'meta_description' => 'LONG_TRANS',
            'submitter' => 'member',
            'add_date' => 'TIME',
            'edit_date' => '?TIME',
        );
    }

    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array                    Resource row (not full, but does contain the ID)
     * @return ?TIME                    The edit date or add date, whichever is higher (NULL: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_NEWS') . ' OR ' . db_string_equal_to('the_type', 'EDIT_NEWS') . ')';
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
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties);

        if (is_null($category)) {
            return false; // Folder not found
        }

        require_code('news2');

        $news = $this->_default_property_str($properties, 'summary');
        $author = $this->_default_property_str($properties, 'author');
        $validated = $this->_default_property_int_null($properties, 'validated');
        if (is_null($validated)) {
            $validated = 1;
        }
        $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'news', 1);
        $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'news', 1);
        $allow_trackbacks = $this->_default_property_int_modeavg($properties, 'allow_trackbacks', 'news', 1);
        $notes = $this->_default_property_str($properties, 'notes');
        $news_article = $this->_default_property_str($properties, 'article');
        $main_news_category = $this->_integer_category($category);
        $news_category = array();
        if ((array_key_exists('categories', $properties)) && ($properties['categories'] != '')) {
            $news_category = array_map('intval', explode(',', $properties['categories']));
        }
        $time = $this->_default_property_int_null($properties, 'add_date');
        $submitter = $this->_default_property_int_null($properties, 'submitter');
        $views = $this->_default_property_int($properties, 'views');
        $edit_date = $this->_default_property_int_null($properties, 'edit_date');
        $image = $this->_default_property_str($properties, 'image');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');
        $id = add_news($label, $news, $author, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $news_article, $main_news_category, $news_category, $time, $submitter, $views, $edit_date, null, $image, $meta_keywords, $meta_description);
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

        $rows = $GLOBALS['SITE_DB']->query_select('news', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        list($meta_keywords, $meta_description) = seo_meta_get_for('news', strval($row['id']));

        return array(
            'label' => $row['title'],
            'summary' => $row['news'],
            'article' => $row['news_article'],
            'author' => $row['author'],
            'validated' => $row['validated'],
            'allow_rating' => $row['allow_rating'],
            'allow_comments' => $row['allow_comments'],
            'allow_trackbacks' => $row['allow_trackbacks'],
            'notes' => $row['notes'],
            'views' => $row['news_views'],
            'image' => $row['news_image'],
            'meta_keywords' => $meta_keywords,
            'meta_description' => $meta_description,
            'submitter' => $row['submitter'],
            'add_date' => $row['date_and_time'],
            'edit_date' => $row['edit_date'],
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
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties);

        if (is_null($category)) {
            return false; // Folder not found
        }

        require_code('news2');

        $label = $this->_default_property_str($properties, 'label');
        $news = $this->_default_property_str($properties, 'summary');
        $author = $this->_default_property_str($properties, 'author');
        $validated = $this->_default_property_int_null($properties, 'validated');
        if (is_null($validated)) {
            $validated = 1;
        }
        $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'news', 1);
        $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'news', 1);
        $allow_trackbacks = $this->_default_property_int_modeavg($properties, 'allow_trackbacks', 'news', 1);
        $notes = $this->_default_property_str($properties, 'notes');
        $news_article = $this->_default_property_str($properties, 'article');
        $main_news_category = $this->_integer_category($category);
        $news_category = array();
        if ((array_key_exists('categories', $properties)) && ($properties['categories'] != '')) {
            $news_category = array_map('intval', explode(',', $properties['categories']));
        }
        $add_time = $this->_default_property_int_null($properties, 'add_date');
        $submitter = $this->_default_property_int_null($properties, 'submitter');
        $views = $this->_default_property_int($properties, 'views');
        $edit_time = $this->_default_property_int_null($properties, 'edit_date');
        $image = $this->_default_property_str($properties, 'image');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');

        edit_news(intval($resource_id), $label, $news, $author, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $news_article, $main_news_category, $news_category, $meta_keywords, $meta_description, $image, $add_time, $edit_time, $views, $submitter, true);

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

        require_code('news2');
        delete_news(intval($resource_id));

        return true;
    }
}
