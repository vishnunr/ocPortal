<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		downloads
 */

class Hook_addon_registry_downloads
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Host a downloads directory.';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array()
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources/hooks/systems/resource_meta_aware/download_licence.php',
			'sources/hooks/systems/occle_fs/download_licences.php',
			'sources/hooks/systems/preview/download.php',
			'sources/hooks/modules/admin_import/downloads.php',
			'sources/hooks/systems/notifications/download.php',
			'sources/hooks/systems/config_default/download_gallery_root.php',
			'sources/hooks/systems/config_default/downloads_show_stats_count_archive.php',
			'sources/hooks/systems/config_default/downloads_show_stats_count_bandwidth.php',
			'sources/hooks/systems/config_default/downloads_show_stats_count_downloads.php',
			'sources/hooks/systems/config_default/downloads_show_stats_count_total.php',
			'sources/hooks/systems/config_default/immediate_downloads.php',
			'sources/hooks/systems/config_default/maximum_download.php',
			'sources/hooks/systems/config_default/points_ADD_DOWNLOAD.php',
			'sources/hooks/systems/config_default/show_dload_trees.php',
			'sources/hooks/systems/content_meta_aware/download.php',
			'sources/hooks/systems/content_meta_aware/download_category.php',
			'sources/hooks/systems/occle_fs/downloads.php',
			'sources/hooks/systems/meta/downloads_category.php',
			'sources/hooks/systems/meta/downloads_download.php',
			'sources/hooks/systems/disposable_values/archive_size.php',
			'sources/hooks/modules/admin_import_types/downloads.php',
			'sources/hooks/modules/admin_setupwizard/downloads.php',
			'sources/hooks/modules/admin_stats/downloads.php',
			'sources/hooks/systems/addon_registry/downloads.php',
			'sources/hooks/systems/disposable_values/download_bandwidth.php',
			'sources/hooks/systems/disposable_values/num_archive_downloads.php',
			'sources/hooks/systems/disposable_values/num_downloads_downloaded.php',
			'site/pages/html_custom/EN/download_tree_made.htm',
			'DOWNLOAD_GALLERY_IMAGE_CELL.tpl',
			'DOWNLOAD_GALLERY_ROW.tpl',
			'DOWNLOAD_CATEGORY_SCREEN.tpl',
			'DOWNLOAD_SCREEN_IMAGE.tpl',
			'DOWNLOAD_BOX.tpl',
			'DOWNLOAD_LIST_LINE.tpl',
			'DOWNLOAD_LIST_LINE_2.tpl',
			'DOWNLOAD_SCREEN.tpl',
			'DOWNLOAD_ALL_SCREEN.tpl',
			'DOWNLOAD_AND_IMAGES_SIMPLE_BOX.tpl',
			'uploads/downloads/.htaccess',
			'uploads/downloads/index.html',
			'downloads.css',
			'themes/default/images/bigicons/downloads.png',
			'themes/default/images/pagepics/downloads.png',
			'cms/pages/modules/cms_downloads.php',
			'lang/EN/downloads.ini',
			'site/pages/modules/downloads.php',
			'sources/downloads.php',
			'sources/downloads2.php',
			'sources/downloads_stats.php',
			'sources/hooks/blocks/side_stats/stats_downloads.php',
			'sources/hooks/modules/admin_newsletter/downloads.php',
			'sources/hooks/modules/admin_unvalidated/downloads.php',
			'sources/hooks/modules/galleries_users/downloads.php',
			'sources/hooks/modules/search/downloads.php',
			'sources/hooks/modules/search/download_categories.php',
			'sources/hooks/systems/do_next_menus/downloads.php',
			'sources/hooks/systems/module_permissions/downloads.php',
			'sources/hooks/systems/rss/downloads.php',
			'sources/hooks/systems/trackback/downloads.php',
			'sources/hooks/systems/ajax_tree/choose_download.php',
			'sources/hooks/systems/ajax_tree/choose_download_category.php',
			'themes/default/images/bigicons/add_one_licence.png',
			'themes/default/images/bigicons/edit_one_licence.png',
			'site/dload.php',
			'site/download_licence.php',
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
	function tpl_previews()
	{
		return array(
			'DOWNLOAD_LIST_LINE.tpl'=>'download_list_line',
			'DOWNLOAD_LIST_LINE_2.tpl'=>'download_list_line_2',
			'DOWNLOAD_BOX.tpl'=>'download_category_screen',
			'DOWNLOAD_AND_IMAGES_SIMPLE_BOX.tpl'=>'download_and_images_simple_box',
			'DOWNLOAD_CATEGORY_SCREEN.tpl'=>'download_category_screen',
			'DOWNLOAD_ALL_SCREEN.tpl'=>'download_all_screen',
			'DOWNLOAD_SCREEN_IMAGE.tpl'=>'download_screen',
			'DOWNLOAD_GALLERY_IMAGE_CELL.tpl'=>'download_screen',
			'DOWNLOAD_GALLERY_ROW.tpl'=>'download_screen',
			'DOWNLOAD_SCREEN.tpl'=>'download_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__download_and_images_simple_box()
	{
		return array(
			lorem_globalise(do_lorem_template('DOWNLOAD_AND_IMAGES_SIMPLE_BOX', array(
				'DESCRIPTION'=>lorem_paragraph_html(),
				'IMAGES'=>lorem_phrase(),
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__download_list_line()
	{
		return array(
			lorem_globalise(do_lorem_template('DOWNLOAD_LIST_LINE', array(
				'BREADCRUMBS'=>lorem_word(),
				'DOWNLOAD'=>lorem_phrase()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__download_list_line_2()
	{
		return array(
			lorem_globalise(do_lorem_template('DOWNLOAD_LIST_LINE_2', array(
				'BREADCRUMBS'=>lorem_phrase(),
				'FILECOUNT'=>placeholder_number()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__download_category_screen()
	{
		$out=new ocp_tempcode();
		foreach (placeholder_array() as $id=>$subcats)
		{
			$out->attach(do_lorem_template('CATEGORY_ENTRY', array(
				'ID'=>"$id",
				'NAME_FIELD'=>'category',
				'AJAX_EDIT_URL'=>placeholder_url(),
				'URL'=>placeholder_url(),
				'REP_IMAGE'=>placeholder_image(),
				'CHILDREN'=>lorem_word(),
				'NAME'=>lorem_phrase(),
				'NAME_PLAIN'=>lorem_word_2()
			)));
		}

		$subcategories=do_lorem_template('CATEGORY_LIST', array(
			'CONTENT'=>$out
		));

		$downloads=new ocp_tempcode();
		$download_items=array(
			array(
				'id'=>1,
				'file_size'=>placeholder_number(),
				'description'=>lorem_phrase(),
				'add_date'=>placeholder_time(),
				'category_id'=>placeholder_id(),
				'default_pic'=>'',
				'download_views'=>placeholder_number(),
				'submitter'=>placeholder_id(),
				'num_downloads'=>placeholder_number(),
				'edit_date'=>placeholder_time(),
				'name'=>lorem_phrase()
			)
		);

		foreach ($download_items as $download)
		{
			$map=array(
				'ORIGINAL_FILENAME'=>lorem_phrase(),
				'AUTHOR'=>lorem_phrase(),
				'ID'=>placeholder_id(),
				'VIEWS'=>placeholder_number(),
				'SUBMITTER'=>placeholder_id(),
				'DESCRIPTION'=>lorem_sentence(),
				'FILE_SIZE'=>placeholder_number(),
				'DOWNLOADS'=>placeholder_number(),
				'DATE_RAW'=>placeholder_date_raw(),
				'DATE'=>placeholder_date(),
				'EDIT_DATE_RAW'=>'',
				'SIZE'=>placeholder_number(),
				'URL'=>placeholder_url(),
				'NAME'=>lorem_phrase(),
				'BREADCRUMBS'=>placeholder_breadcrumbs(),
				'IMGCODE'=>'',
				'GIVE_CONTEXT'=>false,
				'MAY_DOWNLOAD'=>true,
			);
			$tpl=do_lorem_template('DOWNLOAD_BOX', $map);

			$downloads->attach($tpl);
		}

		return array(
			lorem_globalise(do_lorem_template('DOWNLOAD_CATEGORY_SCREEN', array(
				'TAGS'=>lorem_word_html(),
				'TITLE'=>lorem_title(),
				'SUBMIT_URL'=>placeholder_url(),
				'ADD_CAT_URL'=>placeholder_url(),
				'EDIT_CAT_URL'=>placeholder_url(),
				'DESCRIPTION'=>lorem_paragraph_html(),
				'SUBCATEGORIES'=>$subcategories,
				'DOWNLOADS'=>$downloads,
				'SORTING'=>lorem_phrase(),
				'ID'=>placeholder_id()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__download_all_screen()
	{
		$downloads=new ocp_tempcode();
		$download_items=array(
			array(
				'id'=>placeholder_id(),
				'file_size'=>placeholder_number(),
				'description'=>lorem_phrase(),
				'add_date'=>placeholder_time(),
				'category_id'=>placeholder_id(),
				'default_pic'=>'',
				'download_views'=>placeholder_number(),
				'submitter'=>placeholder_id(),
				'num_downloads'=>placeholder_number(),
				'edit_date'=>placeholder_time(),
				'name'=>lorem_phrase()
			)
		);

		$subcats=array();
		foreach (placeholder_array() as $cat)
		{
			foreach ($download_items as $download)
			{
				$map=array(
					'AUTHOR'=>lorem_phrase(),
					'ID'=>placeholder_id(),
					'VIEWS'=>placeholder_number(),
					'SUBMITTER'=>placeholder_id(),
					'DESCRIPTION'=>lorem_sentence(),
					'FILE_SIZE'=>placeholder_number(),
					'DOWNLOADS'=>placeholder_number(),
					'DATE_RAW'=>placeholder_date_raw(),
					'DATE'=>placeholder_date(),
					'EDIT_DATE_RAW'=>'',
					'SIZE'=>placeholder_number(),
					'URL'=>placeholder_url(),
					'NAME'=>lorem_phrase(),
					'BREADCRUMBS'=>placeholder_breadcrumbs(),
					'IMGCODE'=>'',
					'GIVE_CONTEXT'=>false,
					'MAY_DOWNLOAD'=>true,
				);
				$tpl=do_lorem_template('DOWNLOAD_BOX', $map);

				$downloads->attach($tpl);
			}

			$data=array();
			$data['DOWNLOADS']=$downloads;
			$subcats[]=$data;
		}

		return array(
			lorem_globalise(do_lorem_template('DOWNLOAD_ALL_SCREEN', array(
				'TITLE'=>lorem_title(),
				'LETTER'=>lorem_word(),
				'SUBMIT_URL'=>placeholder_url(),
				'ADD_CAT_URL'=>placeholder_url(),
				'EDIT_CAT_URL'=>placeholder_url(),
				'SUB_CATEGORIES'=>$subcats
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__download_screen()
	{
		require_lang('galleries');
		$images_details=new ocp_tempcode();
		foreach (placeholder_array() as $row)
		{
			$image=do_lorem_template('DOWNLOAD_SCREEN_IMAGE', array(
				'ID'=>placeholder_id(),
				'VIEW_URL'=>placeholder_url(),
				'EDIT_URL'=>placeholder_url(),
				'THUMB'=>placeholder_image(),
				'DESCRIPTION'=>lorem_phrase()
			));

			$cell=do_lorem_template('DOWNLOAD_GALLERY_IMAGE_CELL', array(
				'CONTENT'=>$image
			));

			$images_details->attach(do_lorem_template('DOWNLOAD_GALLERY_ROW', array(
				'CELLS'=>$cell
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('DOWNLOAD_SCREEN', array(
				'ORIGINAL_FILENAME'=>lorem_phrase(),
				'TAGS'=>lorem_word_html(),
				'LICENCE'=>lorem_phrase(),
				'LICENCE_TITLE'=>lorem_phrase(),
				'LICENCE_HYPERLINK'=>placeholder_link(),
				'SUBMITTER'=>placeholder_id(),
				'EDIT_DATE'=>placeholder_time(),
				'EDIT_DATE_RAW'=>placeholder_date_raw(),
				'VIEWS'=>lorem_phrase(),
				'DATE'=>placeholder_time(),
				'DATE_RAW'=>placeholder_date_raw(),
				'NUM_DOWNLOADS'=>placeholder_number(),
				'TITLE'=>lorem_title(),
				'NAME'=>lorem_phrase(),
				'OUTMODE_URL'=>placeholder_url(),
				'WARNING_DETAILS'=>'',
				'EDIT_URL'=>placeholder_url(),
				'ADD_IMG_URL'=>placeholder_url(),
				'DESCRIPTION'=>lorem_paragraph_html(),
				'ADDITIONAL_DETAILS'=>lorem_sentence_html(),
				'IMAGES_DETAILS'=>$images_details,
				'ID'=>placeholder_id(),
				'FILE_SIZE'=>placeholder_filesize(),
				'AUTHOR_URL'=>placeholder_url(),
				'AUTHOR'=>lorem_phrase(),
				'TRACKBACK_DETAILS'=>lorem_sentence_html(),
				'RATING_DETAILS'=>lorem_sentence_html(),
				'COMMENT_DETAILS'=>lorem_sentence_html(),
				'MAY_DOWNLOAD'=>true,
				'NUM_IMAGES'=>'3'
			)), NULL, '', true)
		);
	}
}
