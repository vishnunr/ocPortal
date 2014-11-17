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
 * @package    core_zone_editor
 */

/**
 * Hook class.
 */
class Hook_resource_meta_aware_zone
{
    /**
     * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
     *
     * @param  ?ID_TEXT                 The zone to link through to (null: autodetect).
     * @return ?array                   Map of award content-type info (null: disabled).
     */
    public function info($zone = null)
    {
        return array(
            'supports_custom_fields' => false,

            'content_type_label' => 'ZONES',

            'connection' => $GLOBALS['SITE_DB'],
            'table' => 'zones',
            'id_field' => 'zone_name',
            'id_field_numeric' => false,
            'parent_category_field' => null,
            'parent_category_meta_aware_type' => null,
            'is_category' => true,
            'is_entry' => false,
            'category_field' => null, // For category permissions
            'category_type' => null, // For category permissions
            'parent_spec__table_name' => null,
            'parent_spec__parent_name' => null,
            'parent_spec__field_name' => null,
            'category_is_string' => true,

            'title_field' => 'zone_title',
            'title_field__resource_fs' => 'zone_name',
            'title_field_dereference' => true,
            'title_field_dereference__resource_fs' => false,

            'view_page_link_pattern' => null,
            'edit_page_link_pattern' => '_SEARCH:admin_zones:_ed:_WILD',
            'view_category_page_link_pattern' => null,
            'add_url' => (function_exists('get_member') && has_actual_page_access(get_member(), 'admin_zones')) ? (get_module_zone('admin_zones') . ':admin_zones:ad') : null,
            'archive_url' => null,

            'support_url_monikers' => false,

            'views_field' => null,
            'submitter_field' => null,
            'add_time_field' => null,
            'edit_time_field' => null,
            'date_field' => null,
            'validated_field' => null,

            'seo_type_code' => null,

            'feedback_type_code' => null,

            'permissions_type_code' => null, // NULL if has no permissions

            'search_hook' => null,

            'addon_name' => 'core_zone_editor',

            'cms_page' => 'admin_zones',
            'module' => null,

            'occle_filesystem_hook' => 'comcode_pages',
            'occle_filesystem__is_folder' => true,

            'rss_hook' => null,

            'actionlog_regexp' => '\w+_ZONE',
        );
    }
}
