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
 * @package    core_ocf
 */

require_code('resource_fs');

class Hook_occle_fs_groups extends resource_fs_base
{
    public $folder_resource_type = 'group';
    public $file_resource_type = 'member';

    /**
     * Standard occle_fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT                  The resource type
     * @return integer                  How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        switch ($resource_type) {
            case 'member':
                return $GLOBALS['FORUM_DB']->query_select_value('f_members', 'COUNT(*)');

            case 'group':
                return $GLOBALS['FORUM_DB']->query_select_value('f_groups', 'COUNT(*)');
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
            case 'member':
                $ret = $GLOBALS['FORUM_DB']->query_select('f_members', array('m_username'), array('m_username' => $label));
                return collapse_1d_complexity('m_username', $ret);

            case 'group':
                $_ret = $GLOBALS['FORUM_DB']->query_select('f_groups', array('id'), array($GLOBALS['FORUM_DB']->translate_field_ref('g_name') => $label));
                $ret = array();
                foreach ($_ret as $r) {
                    $ret[] = strval($r['id']);
                }
                return $ret;
        }
        return array();
    }

    /**
     * Whether the filesystem hook is active.
     *
     * @return boolean                  Whether it is
     */
    protected function _is_active()
    {
        return (get_forum_type() == 'ocf') && (!is_ocf_satellite_site());
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    protected function _enumerate_folder_properties()
    {
        return array(
            'is_default' => 'BINARY',
            'is_super_admin' => 'BINARY',
            'is_super_moderator' => 'BINARY',
            'rank_title' => 'SHORT_TRANS',
            'rank_image' => 'URLPATH',
            'promotion_target' => '?group',
            'promotion_threshold' => '?INTEGER',
            'group_leader' => '?member',
            'flood_control_submit_secs' => 'INTEGER',
            'flood_control_access_secs' => 'INTEGER',
            'max_daily_upload_mb' => 'INTEGER',
            'max_attachments_per_post' => 'INTEGER',
            'max_avatar_width' => 'INTEGER',
            'max_avatar_height' => 'INTEGER',
            'max_post_length_comcode' => 'INTEGER',
            'max_sig_length_comcode' => 'INTEGER',
            'gift_points_base' => 'INTEGER',
            'gift_points_per_day' => 'INTEGER',
            'enquire_on_new_ips' => 'BINARY',
            'is_presented_at_install' => 'BINARY',
            'hidden' => 'BINARY',
            'order' => 'INTEGER',
            'rank_image_pri_only' => 'BINARY',
            'open_membership' => 'BINARY',
            'is_private_club' => 'BINARY',
        ) + $this->_custom_fields_enumerate_properties('group');
    }

    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array                    Resource row (not full, but does contain the ID)
     * @return ?TIME                    The edit date or add date, whichever is higher (NULL: could not find one)
     */
    protected function _get_folder_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_GROUP') . ' OR ' . db_string_equal_to('the_type', 'EDIT_GROUP') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Convert properties to variables for adding/editing members.
     *
     * @param  string                   The path (blank: root / not applicable)
     * @param  array                    Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return array                    Properties
     */
    protected function __folder_read_in_properties($path, $properties)
    {
        $is_default = $this->_default_property_int($properties, 'is_default');
        $is_super_admin = $this->_default_property_int($properties, 'is_super_admin');
        $is_super_moderator = $this->_default_property_int($properties, 'is_super_moderator');
        $rank_title = $this->_default_property_str($properties, 'rank_title');
        $rank_image = $this->_default_property_str($properties, 'rank_image');
        $promotion_target = $this->_default_property_int_null($properties, 'promotion_target');
        $promotion_threshold = $this->_default_property_int_null($properties, 'promotion_threshold');
        $group_leader = $this->_default_property_int_null($properties, 'group_leader');
        $flood_control_submit_secs = $this->_default_property_int_modeavg($properties, 'flood_control_submit_secs', 'f_groups', 0, 'g_flood_control_submit_secs');
        $flood_control_access_secs = $this->_default_property_int_modeavg($properties, 'flood_control_access_secs', 'f_groups', 0, 'g_flood_control_access_secs');
        $max_daily_upload_mb = $this->_default_property_int_modeavg($properties, 'max_daily_upload_mb', 'f_groups', 70, 'g_max_daily_upload_mb');
        $max_attachments_per_post = $this->_default_property_int_modeavg($properties, 'max_attachments_per_post', 'f_groups', 50, 'g_max_attachments_per_post');
        $max_avatar_width = $this->_default_property_int_modeavg($properties, 'max_avatar_width', 'f_groups', 100, 'g_max_avatar_width');
        $max_avatar_height = $this->_default_property_int_modeavg($properties, 'max_avatar_height', 'f_groups', 100, 'g_max_avatar_height');
        $max_post_length_comcode = $this->_default_property_int_modeavg($properties, 'max_post_length_comcode', 'f_groups', 30000, 'g_max_post_length_comcode');
        $max_sig_length_comcode = $this->_default_property_int_modeavg($properties, 'max_sig_length_comcode', 'f_groups', 700, 'g_max_sig_length_comcode');
        $gift_points_base = $this->_default_property_int_modeavg($properties, 'gift_points_base', 'f_groups', 25, 'g_gift_points_base');
        $gift_points_per_day = $this->_default_property_int_modeavg($properties, 'gift_points_per_day', 'f_groups', 1, 'g_gift_points_per_day');
        $enquire_on_new_ips = $this->_default_property_int($properties, 'enquire_on_new_ips');
        $is_presented_at_install = $this->_default_property_int($properties, 'is_presented_at_install');
        $hidden = $this->_default_property_int($properties, 'hidden');
        $order = $this->_default_property_int_null($properties, 'order');
        if (is_null($order)) {
            $order = $GLOBALS['FORUM_DB']->query_select_value('f_groups', 'MAX(g_order)') + 1;
        }
        $rank_image_pri_only = $this->_default_property_int($properties, 'rank_image_pri_only');
        $open_membership = $this->_default_property_int($properties, 'open_membership');
        $is_private_club = $this->_default_property_int($properties, 'is_private_club');

        return array($is_default, $is_super_admin, $is_super_moderator, $rank_title, $rank_image, $promotion_target, $promotion_threshold, $group_leader, $flood_control_submit_secs, $flood_control_access_secs, $max_daily_upload_mb, $max_attachments_per_post, $max_avatar_width, $max_avatar_height, $max_post_length_comcode, $max_sig_length_comcode, $gift_points_base, $gift_points_per_day, $enquire_on_new_ips, $is_presented_at_install, $hidden, $order, $rank_image_pri_only, $open_membership, $is_private_club);
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

        require_code('ocf_groups_action');

        list($is_default, $is_super_admin, $is_super_moderator, $rank_title, $rank_image, $promotion_target, $promotion_threshold, $group_leader, $flood_control_submit_secs, $flood_control_access_secs, $max_daily_upload_mb, $max_attachments_per_post, $max_avatar_width, $max_avatar_height, $max_post_length_comcode, $max_sig_length_comcode, $gift_points_base, $gift_points_per_day, $enquire_on_new_ips, $is_presented_at_install, $hidden, $order, $rank_image_pri_only, $open_membership, $is_private_club) = $this->__folder_read_in_properties($path, $properties);

        $id = ocf_make_group($label, $is_default, $is_super_admin, $is_super_moderator, $rank_title, $rank_image, $promotion_target, $promotion_threshold, $group_leader, $flood_control_submit_secs, $flood_control_access_secs, $max_daily_upload_mb, $max_attachments_per_post, $max_avatar_width, $max_avatar_height, $max_post_length_comcode, $max_sig_length_comcode, $gift_points_base, $gift_points_per_day, $enquire_on_new_ips, $is_presented_at_install, $hidden, $order, $rank_image_pri_only, $open_membership, $is_private_club, true, false);

        $this->_custom_fields_save('group', strval($id), $properties);

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

        $rows = $GLOBALS['FORUM_DB']->query_select('f_groups', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        return array(
            'label' => $row['g_name'],
            'is_default' => $row['g_is_default'],
            'is_super_admin' => $row['g_is_super_admin'],
            'is_super_moderator' => $row['g_is_super_moderator'],
            'rank_title' => $row['g_title'],
            'rank_image' => $row['g_rank_image'],
            'promotion_target' => $row['g_promotion_target'],
            'promotion_threshold' => $row['g_promotion_threshold'],
            'group_leader' => $row['g_group_leader'],
            'flood_control_submit_secs' => $row['g_flood_control_submit_secs'],
            'flood_control_access_secs' => $row['g_flood_control_access_secs'],
            'max_daily_upload_mb' => $row['g_max_daily_upload_mb'],
            'max_attachments_per_post' => $row['g_max_attachments_per_post'],
            'max_avatar_width' => $row['g_max_avatar_width'],
            'max_avatar_height' => $row['g_max_avatar_height'],
            'max_post_length_comcode' => $row['g_max_post_length_comcode'],
            'max_sig_length_comcode' => $row['g_max_sig_length_comcode'],
            'gift_points_base' => $row['g_gift_points_base'],
            'gift_points_per_day' => $row['g_gift_points_per_day'],
            'enquire_on_new_ips' => $row['g_enquire_on_new_ips'],
            'is_presented_at_install' => $row['g_is_presented_at_install'],
            'hidden' => $row['g_hidden'],
            'order' => $row['g_order'],
            'rank_image_pri_only' => $row['g_rank_image_pri_only'],
            'open_membership' => $row['g_open_membership'],
            'is_private_club' => $row['g_is_private_club'],
        ) + $this->_custom_fields_load('group', strval($row['id']));
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

        require_code('ocf_groups_action2');

        $label = $this->_default_property_str($properties, 'label');
        list($is_default, $is_super_admin, $is_super_moderator, $rank_title, $rank_image, $promotion_target, $promotion_threshold, $group_leader, $flood_control_submit_secs, $flood_control_access_secs, $max_daily_upload_mb, $max_attachments_per_post, $max_avatar_width, $max_avatar_height, $max_post_length_comcode, $max_sig_length_comcode, $gift_points_base, $gift_points_per_day, $enquire_on_new_ips, $is_presented_at_install, $hidden, $order, $rank_image_pri_only, $open_membership, $is_private_club) = $this->__folder_read_in_properties($path, $properties);

        ocf_edit_group(intval($resource_id), $label, $is_default, $is_super_admin, $is_super_moderator, $rank_title, $rank_image, $promotion_target, $promotion_threshold, $group_leader, $flood_control_submit_secs, $flood_control_access_secs, $max_daily_upload_mb, $max_attachments_per_post, $max_avatar_width, $max_avatar_height, $max_post_length_comcode, $max_sig_length_comcode, $gift_points_base, $gift_points_per_day, $enquire_on_new_ips, $is_presented_at_install, $hidden, $order, $rank_image_pri_only, $open_membership, $is_private_club, true);

        $this->_custom_fields_save('group', $resource_id, $properties);

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

        require_code('ocf_groups_action2');
        ocf_delete_group(intval($resource_id));

        return true;
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    protected function _enumerate_file_properties()
    {
        $props = array(
            'password_hashed' => 'SHORT_TEXT',
            'salt' => 'SHORT_TEXT',
            'password_compatibility_scheme' => 'ID_TEXT',
            'email_address' => 'SHORT_TEXT',
            'groups' => 'LONG_TEXT',
            'dob_day' => '?SHORT_INTEGER',
            'dob_month' => '?SHORT_INTEGER',
            'dob_year' => '?SHORT_INTEGER',
            'timezone' => 'ID_TEXT',
            'validated' => 'BINARY',
            'join_time' => 'TIME',
            'last_visit_time' => 'TIME',
            'theme' => 'ID_TEXT',
            'avatar_url' => 'URLPATH',
            'signature' => 'LONG_TRANS',
            'is_perm_banned' => 'BINARY',
            'preview_posts' => 'BINARY',
            'reveal_age' => 'BINARY',
            'user_title' => 'SHORT_TEXT',
            'photo_url' => 'URLPATH',
            'photo_thumb_url' => 'URLPATH',
            'views_signatures' => 'BINARY',
            'auto_monitor_contrib_content' => 'BINARY',
            'language' => 'LANGUAGE_NAME',
            'allow_emails' => 'BINARY',
            'allow_emails_from_staff' => 'BINARY',
            'ip_address' => 'IP',
            'validated_email_confirm_code' => 'SHORT_TEXT',
            'last_submit_time' => 'TIME',
            'highlighted_name' => 'BINARY',
            'pt_allow' => 'SHORT_TEXT',
            'pt_rules_text' => 'LONG_TRANS',
            'on_probation_until' => '?TIME',
        );
        require_code('ocf_members');
        $custom_fields = ocf_get_all_custom_fields_match(null, null, null, null, null, null, null, 0, null);
        foreach ($custom_fields as $i => $custom_field) {
            $cf_name = get_translated_text($custom_field['cf_name'], $GLOBALS['FORUM_DB']);
            $fixed_id = fix_id($cf_name);
            if (!array_key_exists($fixed_id, $props)) {
                $key = $fixed_id;
            } else {
                $key = 'field_' . strval($custom_field['id']);
            }

            require_code('fields');
            $ob = get_fields_hook($custom_field['cf_type']);
            list(, , $storage_type) = $ob->get_field_value_row_bits(array('id' => null, 'cf_type' => $custom_field['cf_type'], 'cf_default' => ''));
            $_type = 'SHORT_TEXT';
            switch ($storage_type) {
                case 'short_trans':
                    $_type = 'SHORT_TRANS';
                    break;
                case 'long_trans':
                    $_type = 'LONG_TRANS';
                    break;
                case 'long':
                    $_type = 'LONG_TEXT';
                    break;
                case 'integer':
                    $_type = 'INTEGER';
                    break;
                case 'float':
                    $_type = 'REAL';
                    break;
            }
            $props[$key] = $_type;
        }
        return $props;
    }

    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array                    Resource row (not full, but does contain the ID)
     * @return ?TIME                    The edit date or add date, whichever is higher (NULL: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'EDIT_EDIT_MEMBER_PROFILE') . ')';
        $time = $GLOBALS['SITE_DB']->query_value_if_there($query);
        //if (is_null($time)) $time=$row['m_join_time']; This will be picked up naturally
        return $time;
    }

    /**
     * Convert properties to variables for adding/editing members.
     *
     * @param  string                   The path (blank: root / not applicable)
     * @param  array                    Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return array                    Properties
     */
    protected function __file_read_in_properties($path, $properties)
    {
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        $password_hashed = $this->_default_property_str($properties, 'password_hashed');
        $email_address = $this->_default_property_str($properties, 'email_address');
        $groups = array();
        $primary_group_id = $this->_integer_category($category);
        $groups[] = $primary_group_id;
        if ((isset($properties['groups'])) && ($properties['groups'] != '')) {
            $_groups = @unserialize($properties['groups']);
            if ($_groups !== false) {
                foreach ($_groups as $group) {
                    $groups[] = intval(remap_portable_as_resource_id('group', $group));
                }
            }
        }
        $dob_day = $this->_default_property_int_null($properties, 'dob_day');
        $dob_month = $this->_default_property_int_null($properties, 'dob_month');
        $dob_year = $this->_default_property_int_null($properties, 'dob_year');
        $timezone = $this->_default_property_str_null($properties, 'timezone');
        $validated = $this->_default_property_int_null($properties, 'validated');
        if (is_null($validated)) {
            $validated = 1;
        }
        $join_time = $this->_default_property_int_null($properties, 'join_time');
        $last_visit_time = $this->_default_property_int_null($properties, 'last_visit_time');
        $theme = $this->_default_property_str($properties, 'theme');
        $avatar_url = $this->_default_property_str($properties, 'avatar_url');
        $signature = $this->_default_property_str($properties, 'signature');
        $is_perm_banned = $this->_default_property_int($properties, 'is_perm_banned');
        $preview_posts = $this->_default_property_int_modeavg($properties, 'preview_posts', 'f_members', 0, 'm_preview_posts');
        $reveal_age = $this->_default_property_int_modeavg($properties, 'reveal_age', 'f_members', 0, 'm_reveal_age');
        $user_title = $this->_default_property_str($properties, 'user_title');
        $photo_url = $this->_default_property_str($properties, 'photo_url');
        $photo_thumb_url = $this->_default_property_str($properties, 'photo_thumb_url');
        $views_signatures = $this->_default_property_int($properties, 'views_signatures');
        $auto_monitor_contrib_content = $this->_default_property_int_null($properties, 'auto_monitor_contrib_content');
        if (is_null($auto_monitor_contrib_content)) {
            $auto_monitor_contrib_content = intval(get_option('allow_auto_notifications'));
        }
        $language = $this->_default_property_str_null($properties, 'language');
        $allow_emails = $this->_default_property_int_modeavg($properties, 'allow_emails', 'f_members', 1, 'm_allow_emails');
        $allow_emails_from_staff = $this->_default_property_int_modeavg($properties, 'allow_emails_from_staff', 'f_members', 1, 'm_allow_emails_from_staff');
        $ip_address = $this->_default_property_str_null($properties, 'ip_address');
        $validated_email_confirm_code = $this->_default_property_str($properties, 'validated_email_confirm_code');
        $password_compatibility_scheme = $this->_default_property_str_null($properties, 'password_compatibility_scheme');
        $salt = $this->_default_property_str($properties, 'salt');
        $last_submit_time = $this->_default_property_int_null($properties, 'last_submit_time');
        $highlighted_name = $this->_default_property_int($properties, 'highlighted_name');
        $pt_allow = $this->_default_property_str($properties, 'pt_allow');
        $pt_rules_text = $this->_default_property_str($properties, 'pt_rules_text');
        $on_probation_until = $this->_default_property_int_null($properties, 'on_probation_until');

        require_code('ocf_members');
        $custom_fields = ocf_get_all_custom_fields_match(null, null, null, null, null, null, null, 0, null);
        $actual_custom_fields = array();
        $props_already = array();
        foreach ($custom_fields as $i => $custom_field) {
            $cf_name = get_translated_text($custom_field['cf_name'], $GLOBALS['FORUM_DB']);
            $fixed_id = fix_id($cf_name);
            if (!array_key_exists($fixed_id, $props_already)) {
                $key = $fixed_id;
            } else {
                $key = 'field_' . strval($custom_field['id']);
            }
            $props_already[$key] = true;
            $value = $this->_default_property_str_null($properties, $key);
            if (is_null($value)) {
                $value = $custom_field['cf_default'];
            }
            $actual_custom_fields[$custom_field['id']] = $value;
        }

        return array($password_hashed, $email_address, $groups, $dob_day, $dob_month, $dob_year, $actual_custom_fields, $timezone, $validated, $join_time, $last_visit_time, $theme, $avatar_url, $signature, $is_perm_banned, $preview_posts, $reveal_age, $user_title, $photo_url, $photo_thumb_url, $views_signatures, $auto_monitor_contrib_content, $language, $allow_emails, $allow_emails_from_staff, $ip_address, $validated_email_confirm_code, $password_compatibility_scheme, $salt, $last_submit_time, $highlighted_name, $pt_allow, $pt_rules_text, $on_probation_until);
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

        require_code('ocf_members_action');

        list($password_hashed, $email_address, $groups, $dob_day, $dob_month, $dob_year, $actual_custom_fields, $timezone, $validated, $join_time, $last_visit_time, $theme, $avatar_url, $signature, $is_perm_banned, $preview_posts, $reveal_age, $user_title, $photo_url, $photo_thumb_url, $views_signatures, $auto_monitor_contrib_content, $language, $allow_emails, $allow_emails_from_staff, $ip_address, $validated_email_confirm_code, $password_compatibility_scheme, $salt, $last_submit_time, $highlighted_name, $pt_allow, $pt_rules_text, $on_probation_until) = $this->__file_read_in_properties($path, $properties);

        $id = ocf_make_member($label, $password_hashed, $email_address, $groups, $dob_day, $dob_month, $dob_year, $actual_custom_fields, $timezone, $category, $validated, $join_time, $last_visit_time, $theme, $avatar_url, $signature, $is_perm_banned, $preview_posts, $reveal_age, $user_title, $photo_url, $photo_thumb_url, $views_signatures, $auto_monitor_contrib_content, $language, $allow_emails, $allow_emails_from_staff, $ip_address, $validated_email_confirm_code, false, $password_compatibility_scheme, $salt, $last_submit_time, null, $highlighted_name, $pt_allow, $pt_rules_text, $on_probation_until);

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

        $rows = $GLOBALS['FORUM_DB']->query_select('f_members', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        $groups = array();
        $_groups = $GLOBALS['FORUM_DB']->query_select('f_group_members', array('gm_group_id'), array('gm_member_id' => intval($resource_id)));
        foreach ($_groups as $_group) {
            $groups[] = remap_resource_id_as_portable('group', strval($_group['gm_group_id']));
        }

        $ret = array(
            'label' => $row['m_username'],
            'password_hashed' => $row['m_pass_hash_salted'],
            'salt' => $row['m_pass_salt'],
            'password_compatibility_scheme' => $row['m_password_compat_scheme'],
            'email_address' => $row['m_email_address'],
            'groups' => serialize($groups),
            'dob_day' => $row['m_dob_day'],
            'dob_month' => $row['m_dob_month'],
            'dob_year' => $row['m_dob_year'],
            'timezone' => $row['m_timezone_offset'],
            'validated' => $row['m_validated'],
            'join_time' => $row['m_join_time'],
            'last_visit_time' => $row['m_last_visit_time'],
            'theme' => $row['m_theme'],
            'avatar_url' => $row['m_avatar_url'],
            'signature' => $row['m_signature'],
            'is_perm_banned' => $row['m_is_perm_banned'],
            'preview_posts' => $row['m_preview_posts'],
            'reveal_age' => $row['m_reveal_age'],
            'user_title' => $row['m_title'],
            'photo_url' => $row['m_photo_url'],
            'photo_thumb_url' => $row['m_photo_thumb_url'],
            'views_signatures' => $row['m_views_signatures'],
            'auto_monitor_contrib_content' => $row['m_auto_monitor_contrib_content'],
            'language' => $row['m_language'],
            'allow_emails' => $row['m_allow_emails'],
            'allow_emails_from_staff' => $row['m_allow_emails_from_staff'],
            'ip_address' => $row['m_ip_address'],
            'validated_email_confirm_code' => $row['m_validated_email_confirm_code'],
            'last_submit_time' => $row['m_last_submit_time'],
            'highlighted_name' => $row['m_highlighted_name'],
            'pt_allow' => $row['m_pt_allow'],
            'pt_rules_text' => $row['m_pt_rules_text'],
        );

        require_code('ocf_members');
        $cpfs = ocf_get_all_custom_fields_match_member(intval($resource_id));
        foreach ($cpfs as $cf_name => $cpf) {
            $fixed_id = fix_id($cf_name);
            if (!array_key_exists($fixed_id, $ret)) {
                $key = $fixed_id;
            } else {
                $key = 'field_' . strval($cpf['FIELD_ID']);
            }
            $ret[$key] = $cpf['RAW'];
        }

        return $ret;
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

        require_code('ocf_members_action2');

        $label = $this->_default_property_str($properties, 'label');
        list($password_hashed, $email_address, $groups, $dob_day, $dob_month, $dob_year, $actual_custom_fields, $timezone, $validated, $join_time, $last_visit_time, $theme, $avatar_url, $signature, $is_perm_banned, $preview_posts, $reveal_age, $user_title, $photo_url, $photo_thumb_url, $views_signatures, $auto_monitor_contrib_content, $language, $allow_emails, $allow_emails_from_staff, $ip_address, $validated_email_confirm_code, $password_compatibility_scheme, $salt, $last_submit_time, $highlighted_name, $pt_allow, $pt_rules_text, $on_probation_until) = $this->__file_read_in_properties($path, $properties);

        ocf_edit_member(intval($resource_id), $email_address, $preview_posts, $dob_day, $dob_month, $dob_year, $timezone, $category, $actual_custom_fields, $theme, $reveal_age, $views_signatures, $auto_monitor_contrib_content, $language, $allow_emails, $allow_emails_from_staff, $validated, $label, $password_hashed, $highlighted_name, $pt_allow, $pt_rules_text, $on_probation_until, $join_time, $avatar_url, $signature, $is_perm_banned, $photo_url, $photo_thumb_url, $salt, $password_compatibility_scheme, true);

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

        require_code('ocf_members_action2');
        ocf_delete_member(intval($resource_id));

        return true;
    }
}
