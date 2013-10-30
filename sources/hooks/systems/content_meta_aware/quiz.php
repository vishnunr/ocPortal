<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		quizzes
 */

class Hook_content_meta_aware_quiz
{

	/**
	 * Standard modular info function for content hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		return array(
			'supports_custom_fields'=>true,

			'content_type_label'=>'quiz:QUIZ',

			'connection'=>$GLOBALS['SITE_DB'],
			'table'=>'quizzes',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>NULL,
			'parent_category_meta_aware_type'=>NULL,
			'is_category'=>false,
			'is_entry'=>true,
			'category_field'=>'q_type', // For category permissions
			'category_type'=>NULL, // For category permissions
			'parent_spec__table_name'=>NULL,
			'parent_spec__parent_name'=>NULL,
			'parent_spec__field_name'=>NULL,
			'category_is_string'=>true,

			'title_field'=>'q_name',
			'title_field_dereference'=>true,
			'description_field'=>'q_start_text',
			'thumb_field'=>NULL,

			'view_pagelink_pattern'=>'_SEARCH:quiz:do:_WILD',
			'edit_pagelink_pattern'=>'_SEARCH:cms_quiz:_ed:_WILD',
			'view_category_pagelink_pattern'=>NULL,
			'add_url'=>(function_exists('has_submit_permission') && has_submit_permission('high',get_member(),get_ip_address(),'cms_quiz'))?(get_module_zone('cms_quiz').':cms_quiz:ad'):NULL,
			'archive_url'=>((!is_null($zone))?$zone:get_module_zone('quiz')).':quiz',

			'support_url_monikers'=>true,

			'views_field'=>NULL,
			'submitter_field'=>'q_submitter',
			'add_time_field'=>'q_add_date',
			'edit_time_field'=>NULL,
			'date_field'=>'q_add_date',
			'validated_field'=>'q_validated',

			'seo_type_code'=>NULL,

			'feedback_type_code'=>NULL,

			'permissions_type_code'=>NULL, // NULL if has no permissions

			'search_hook'=>'quiz',

			'addon_name'=>'quizzes',

			'cms_page'=>'cms_quiz',
			'module'=>'quiz',

			'occle_filesystem_hook'=>'quizzes',
			'occle_filesystem__is_folder'=>false,

			'rss_hook'=>NULL,

			'actionlog_regexp'=>'\w+_QUIZ',
		);
	}

	/**
	 * Standard modular run function for content hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @param  boolean	Whether to include context (i.e. say WHAT this is, not just show the actual content)
	 * @param  boolean	Whether to include breadcrumbs (if there are any)
	 * @param  ?ID_TEXT	Virtual root to use (NULL: none)
	 * @param  boolean	Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
	 * @param  ID_TEXT	Overridden GUID to send to templates (blank: none)
	 * @return tempcode	Results
	 */
	function run($row,$zone,$give_context=true,$include_breadcrumbs=true,$root=NULL,$attach_to_url_filter=false,$guid='')
	{
		require_code('quiz');

		return render_quiz_box($row,$zone,$give_context,$guid);
	}

}
