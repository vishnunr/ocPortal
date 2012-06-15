<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_abstract_interfaces
 */

class Hook_addon_registry_core_abstract_interfaces
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
		return 'Core rendering functionality.';
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
			'sources/hooks/systems/addon_registry/core_abstract_interfaces.php',
			'QUESTION_UI_BUTTONS.tpl',
			'NEXT_BROWSER_BROWSE_NEXT.tpl',
			'NEXT_BROWSER_SCREEN.tpl',
			'CONFIRM_SCREEN.tpl',
			'WARN_SCREEN.tpl',
			'JAVASCRIPT_SPLURGH.tpl',
			'SPLURGH.tpl',
			'SPLURGH_SCREEN.tpl',
			'FULL_MESSAGE_SCREEN.tpl',
			'INFORM_SCREEN.tpl',
			'REDIRECT_SCREEN.tpl',
			'WARNING_BOX.tpl',
			'DO_NEXT_SCREEN.tpl',
			'DO_NEXT_ITEM.tpl',
			'DO_NEXT_SECTION.tpl',
			'do_next.css',
			'INDEX_SCREEN.tpl',
			'INDEX_SCREEN_ENTRY.tpl',
			'INDEX_SCREEN_FANCIER_SCREEN.tpl',
			'INDEX_SCREEN_FANCIER_ENTRY.tpl',
			'INDEX_SCREEN_FANCY_SCREEN.tpl',
			'INDEX_SCREEN_FANCY_ENTRY.tpl',
			'MAP_TABLE.tpl',
			'MAP_TABLE_FIELD.tpl',
			'MAP_TABLE_FIELD_ABBR.tpl',
			'MAP_TABLE_FIELD_RAW.tpl',
			'MAP_TABLE_FIELD_RAW_ABBR.tpl',
			'MAP_TABLE_SCREEN.tpl',
			'COLUMNED_TABLE.tpl',
			'COLUMNED_TABLE_SCREEN.tpl',
			'COLUMNED_TABLE_ROW_CELL_SELECT.tpl',
			'COLUMNED_TABLE_ACTION_DELETE_CATEGORY.tpl',
			'COLUMNED_TABLE_ACTION_DELETE_ENTRY.tpl',
			'COLUMNED_TABLE_ACTION_INSTALL_ENTRY.tpl',
			'COLUMNED_TABLE_ACTION_REINSTALL_ENTRY.tpl',
			'COLUMNED_TABLE_ACTION_TRANSLATE.tpl',
			'COLUMNED_TABLE_ACTION_UPGRADE_ENTRY.tpl',
			'COLUMNED_TABLE_ACTION_DOWNLOAD.tpl',
			'COLUMNED_TABLE_HEADER_ROW.tpl',
			'COLUMNED_TABLE_HEADER_ROW_CELL.tpl',
			'COLUMNED_TABLE_ROW.tpl',
			'COLUMNED_TABLE_ROW_CELL.tpl',
			'PAGINATION_CONTINUE.tpl',
			'PAGINATION_CONTINUE_LAST.tpl',
			'PAGINATION_CONTINUE_FIRST.tpl',
			'PAGINATION_LIST_PAGES.tpl',
			'PAGINATION_NEXT.tpl',
			'PAGINATION_NEXT_LINK.tpl',
			'PAGINATION_PAGE_NUMBER.tpl',
			'PAGINATION_PAGE_NUMBER_LINK.tpl',
			'PAGINATION_PER_SCREEN.tpl',
			'PAGINATION_PER_PAGE_OPTION.tpl',
			'PAGINATION_PREVIOUS.tpl',
			'PAGINATION_PREVIOUS_LINK.tpl',
			'PAGINATION_SORT.tpl',
			'PAGINATION_SORTER.tpl',
			'PAGINATION_WRAP.tpl',
			'RESULTS_LAUNCHER_CONTINUE.tpl',
			'RESULTS_LAUNCHER_PAGE_NUMBER_LINK.tpl',
			'RESULTS_LAUNCHER_WRAP.tpl',
			'RESULTS_TABLE.tpl',
			'RESULTS_TABLE_ENTRY.tpl',
			'RESULTS_TABLE_FIELD.tpl',
			'RESULTS_TABLE_FIELD_TITLE.tpl',
			'RESULTS_TABLE_FIELD_TITLE_SORTABLE.tpl',
			'RESULTS_TABLE_TICK.tpl',
			'JAVASCRIPT_PAGINATION.tpl',
			'IFRAME_SCREEN.tpl',
			'MEMBER_TOOLTIP.tpl',
			'SIMPLE_PREVIEW_BOX.tpl',
			'JAVASCRIPT_IFRAME_SCREEN.tpl',
			'RESULTS_TABLE_SCREEN.tpl',
			'sources/templates_interfaces.php',
			'sources/templates_redirect_screen.php',
			'sources/templates_confirm_screen.php',
			'sources/templates_internalise_screen.php',
			'sources/templates_results_table.php',
			'sources/templates_result_launcher.php',
			'sources/templates_pagination.php',
			'sources/templates_columned_table.php',
			'sources/templates_map_table.php',
			'sources/templates_donext.php'
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
			'RESULTS_TABLE_TICK.tpl'=>'result_table_screen',
			'REDIRECT_SCREEN.tpl'=>'redirect_screen',
			'CONFIRM_SCREEN.tpl'=>'confirm_screen',
			'RESULTS_TABLE_SCREEN.tpl'=>'result_table_screen',
			'COLUMNED_TABLE_ACTION_DELETE_ENTRY.tpl'=>'full_table_screen',
			'COLUMNED_TABLE.tpl'=>'full_table_screen',
			'INDEX_SCREEN_ENTRY.tpl'=>'index_screen',
			'INDEX_SCREEN.tpl'=>'index_screen',
			'INDEX_SCREEN_FANCIER_ENTRY.tpl'=>'index_screen_fancier_screen',
			'INDEX_SCREEN_FANCIER_SCREEN.tpl'=>'index_screen_fancier_screen',
			'COLUMNED_TABLE_ACTION_INSTALL_ENTRY.tpl'=>'full_table_screen',
			'COLUMNED_TABLE_ACTION_UPGRADE_ENTRY.tpl'=>'full_table_screen',
			'COLUMNED_TABLE_ACTION_REINSTALL_ENTRY.tpl'=>'full_table_screen',
			'MAP_TABLE.tpl'=>'map_table',
			'MAP_TABLE_SCREEN.tpl'=>'map_table_screen',
			'COLUMNED_TABLE_ACTION_DELETE_CATEGORY.tpl'=>'columned_table_action_delete_category',
			'SPLURGH.tpl'=>'splurgh_screen',
			'NEXT_BROWSER_BROWSE_NEXT.tpl'=>'next_browser_screen',
			'WARNING_BOX.tpl'=>'warning_box',
			'PAGINATION_SORTER.tpl'=>'result_table_screen',
			'PAGINATION_SORT.tpl'=>'result_table_screen',
			'RESULTS_TABLE_FIELD.tpl'=>'result_table_screen',
			'RESULTS_TABLE_ENTRY.tpl'=>'result_table_screen',
			'RESULTS_TABLE_FIELD_TITLE_SORTABLE.tpl'=>'result_table_screen',
			'RESULTS_TABLE_FIELD_TITLE.tpl'=>'result_table_screen',
			'COLUMNED_TABLE_HEADER_ROW_CELL.tpl'=>'full_table_screen',
			'COLUMNED_TABLE_HEADER_ROW.tpl'=>'full_table_screen',
			'COLUMNED_TABLE_ROW_CELL.tpl'=>'full_table_screen',
			'COLUMNED_TABLE_ROW.tpl'=>'full_table_screen',
			'RESULTS_LAUNCHER_PAGE_NUMBER_LINK.tpl'=>'result_launcher_screen',
			'RESULTS_LAUNCHER_CONTINUE.tpl'=>'result_launcher_screen',
			'RESULTS_LAUNCHER_WRAP.tpl'=>'result_launcher_screen',
			'SIMPLE_PREVIEW_BOX.tpl'=>'simple_preview_box',
			'INFORM_SCREEN.tpl'=>'inform_screen',
			'PAGINATION_PER_PAGE_OPTION.tpl'=>'result_table_screen',
			'PAGINATION_PER_SCREEN.tpl'=>'result_table_screen',
			'PAGINATION_CONTINUE_FIRST.tpl'=>'result_table_screen',
			'PAGINATION_PREVIOUS_LINK.tpl'=>'result_table_screen',
			'PAGINATION_PREVIOUS.tpl'=>'result_table_screen_2',
			'PAGINATION_CONTINUE.tpl'=>'result_table_screen_2',
			'PAGINATION_PAGE_NUMBER.tpl'=>'result_table_screen',
			'PAGINATION_PAGE_NUMBER_LINK.tpl'=>'result_table_screen',
			'PAGINATION_NEXT_LINK.tpl'=>'result_table_screen',
			'PAGINATION_NEXT.tpl'=>'result_table_screen_2',
			'PAGINATION_CONTINUE_LAST.tpl'=>'result_table_screen',
			'PAGINATION_LIST_PAGES.tpl'=>'result_table_screen',
			'PAGINATION_WRAP.tpl'=>'result_table_screen',
			'MAP_TABLE_FIELD.tpl'=>'map_table',
			'MAP_TABLE_FIELD_ABBR.tpl'=>'map_table',
			'MAP_TABLE_FIELD_RAW.tpl'=>'map_table',
			'MAP_TABLE_FIELD_RAW_ABBR.tpl'=>'map_table',
			'IFRAME_SCREEN.tpl'=>'iframe_screen',
			'WARN_SCREEN.tpl'=>'warn_screen',
			'DO_NEXT_SCREEN.tpl'=>'administrative__do_next_screen',
			'DO_NEXT_ITEM.tpl'=>'administrative__do_next_screen',
			'DO_NEXT_SECTION.tpl'=>'administrative__do_next_screen',
			'QUESTION_UI_BUTTONS.tpl'=>'question_ui_buttons',
			'NEXT_BROWSER_SCREEN.tpl'=>'next_browser_screen',
			'SPLURGH_SCREEN.tpl'=>'splurgh_screen',
			'FULL_MESSAGE_SCREEN.tpl'=>'full_message_screen',
			'INDEX_SCREEN_FANCY_SCREEN.tpl'=>'index_screen_fancy_screen',
			'RESULTS_TABLE.tpl'=>'result_table_screen',
			'MEMBER_TOOLTIP.tpl'=>'member_tooltip',
			'INDEX_SCREEN_FANCY_ENTRY.tpl'=>'index_screen_fancy_screen',
			'COLUMNED_TABLE_ACTION_DOWNLOAD.tpl'=>'columned_table_action_download',
			'COLUMNED_TABLE_ACTION_TRANSLATE.tpl'=>'administrative__columned_table_action_translate',
			'COLUMNED_TABLE_ROW_CELL_SELECT.tpl'=>'full_table_screen',
			'COLUMNED_TABLE_SCREEN.tpl'=>'administrative__columned_table_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__index_screen_fancy_screen()
	{
		$content=new ocp_tempcode();
		foreach (placeholder_array() as $v)
		{
			$content->attach(do_lorem_template('INDEX_SCREEN_FANCY_ENTRY', array(
				'NAME'=>lorem_word(),
				'URL'=>placeholder_url()
			)));
		}
		return array(
			lorem_globalise(do_lorem_template('INDEX_SCREEN_FANCY_SCREEN', array(
				'TITLE'=>lorem_title(),
				'PRE'=>lorem_phrase(),
				'CONTENT'=>$content,
				'POST'=>lorem_phrase()
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
	function tpl_preview__administrative__columned_table_action_translate()
	{
		require_lang('lang');
		return array(
			lorem_globalise(do_lorem_template('COLUMNED_TABLE_ACTION_TRANSLATE', array(
				'URL'=>placeholder_url(),
				'NAME'=>lorem_phrase()
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
	function tpl_preview__columned_table_action_download()
	{
		return array(
			lorem_globalise(do_lorem_template('COLUMNED_TABLE_ACTION_DOWNLOAD', array(
				'URL'=>placeholder_url(),
				'NAME'=>lorem_phrase()
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
	function tpl_preview__redirect_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('REDIRECT_SCREEN', array(
				'URL'=>placeholder_url(),
				'TITLE'=>lorem_title(),
				'TEXT'=>lorem_sentence_html()
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
	function tpl_preview__confirm_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('CONFIRM_SCREEN', array(
				'URL'=>placeholder_url(),
				'BACK_URL'=>placeholder_url(),
				'PREVIEW'=>lorem_phrase(),
				'FIELDS'=>lorem_phrase(),
				'TITLE'=>lorem_title()
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
	function tpl_preview__index_screen()
	{
		$entry=new ocp_tempcode();

		foreach (placeholder_array() as $value)
		{
			$entry->attach(do_lorem_template('INDEX_SCREEN_ENTRY', array(
				'NAME'=>lorem_word(),
				'URL'=>placeholder_url(),
				'DISPLAY_STRING'=>lorem_phrase()
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('INDEX_SCREEN', array(
				'TITLE'=>lorem_title(),
				'PRE'=>lorem_phrase(),
				'POST'=>lorem_phrase(),
				'CONTENT'=>$entry
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
	function tpl_preview__index_screen_fancier_screen()
	{
		$entries=new ocp_tempcode();
		foreach (placeholder_array() as $value)
		{
			$entries->attach(do_lorem_template('INDEX_SCREEN_FANCIER_ENTRY', array(
				'TITLE'=>lorem_phrase(),
				'URL'=>placeholder_url(),
				'NAME'=>$value,
				'DESCRIPTION'=>lorem_paragraph_html(),
				'COUNT'=>placeholder_random()
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('INDEX_SCREEN_FANCIER_SCREEN', array(
				'CONTENT'=>$entries,
				'TITLE'=>lorem_title(),
				'POST'=>lorem_phrase(),
				'PRE'=>lorem_phrase(),
				'ADD_URL'=>placeholder_url()
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
	function tpl_preview__map_table()
	{
		$fields=new ocp_tempcode();
		$fields->attach(do_lorem_template('MAP_TABLE_FIELD', array(
			'NAME'=>lorem_word(),
			'VALUE'=>lorem_phrase()
		)));
		$fields->attach(do_lorem_template('MAP_TABLE_FIELD_ABBR', array(
			'ABBR'=>lorem_phrase(),
			'NAME'=>lorem_word(),
			'VALUE'=>lorem_phrase()
		)));
		$fields->attach(do_lorem_template('MAP_TABLE_FIELD_RAW_ABBR', array(
			'ABBR'=>lorem_phrase(),
			'NAME'=>lorem_word(),
			'VALUE'=>lorem_phrase()
		)));
		$fields->attach(do_lorem_template('MAP_TABLE_FIELD_RAW', array(
			'NAME'=>lorem_word(),
			'VALUE'=>lorem_phrase()
		)));

		return array(
			lorem_globalise(do_lorem_template('MAP_TABLE', array(
				'WIDTH'=>placeholder_number(),
				'FIELDS'=>$fields
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
	function tpl_preview__map_table_screen()
	{
		$fields=do_lorem_template('MAP_TABLE_FIELD', array(
			'ABBR'=>lorem_phrase(),
			'NAME'=>lorem_word(),
			'VALUE'=>lorem_phrase()
		));

		return array(
			lorem_globalise(do_lorem_template('MAP_TABLE_SCREEN', array(
				'TITLE'=>lorem_title(),
				'FIELDS'=>$fields
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
	function tpl_preview__columned_table_action_delete_category()
	{
		return array(
			lorem_globalise(do_lorem_template('COLUMNED_TABLE_ACTION_DELETE_CATEGORY', array(
				'URL'=>placeholder_url(),
				'NAME'=>lorem_phrase()
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
	function tpl_preview__warning_box()
	{
		return array(
			lorem_globalise(do_lorem_template('WARNING_BOX', array(
				'WARNING'=>lorem_phrase()
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
	function tpl_preview__simple_preview_box()
	{
		return array(
			lorem_globalise(do_lorem_template('SIMPLE_PREVIEW_BOX', array(
				'SUMMARY'=>lorem_paragraph_html(),
				'URL'=>placeholder_url()
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
	function tpl_preview__inform_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('INFORM_SCREEN', array(
				'TITLE'=>lorem_title(),
				'TEXT'=>lorem_sentence()
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
	function tpl_preview__iframe_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('IFRAME_SCREEN', array(
				'TITLE'=>lorem_title(),
				'REFRESH_IF_CHANGED'=>lorem_phrase(),
				'CHANGE_DETECTION_URL'=>placeholder_url(),
				'REFRESH_TIME'=>placeholder_date_raw(),
				'IFRAME_URL'=>'http://ocportal.com'
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
	function tpl_preview__warn_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('WARN_SCREEN', array(
				'TITLE'=>lorem_title(),
				'WEBSERVICE_RESULT'=>lorem_phrase(),
				'TEXT'=>lorem_sentence(),
				'PROVIDE_BACK'=>placeholder_id()
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
	function tpl_preview__administrative__do_next_screen()
	{
		require_lang('do_next');
		$list=array(
			array(
				'main_home',
				array(
					NULL,
					array(),
					''
				)
			),
			array(
				'cms_home',
				array(
					NULL,
					array(),
					''
				)
			),
			array(
				'admin_home',
				array(
					NULL,
					array(),
					''
				)
			)
		);

		$sections=new ocp_tempcode();
		$next_items=new ocp_tempcode();
		$i=0;
		foreach ($list as $_option)
		{
			$option=$_option[0];
			$next_items->attach(do_lorem_template('DO_NEXT_ITEM', array(
				'I'=>strval($i),
				'I2'=>placeholder_random_id() . '_' . strval($i),
				'TARGET'=>NULL,
				'PICTURE'=>$option,
				'DESCRIPTION'=>lorem_phrase(),
				'LINK'=>placeholder_url(),
				'DOC'=>'',
				'WARNING'=>''
			)));
			$i++;
		}
		$do_next_section=do_lorem_template('DO_NEXT_SECTION', array(
			'I'=>placeholder_number(),
			'TITLE'=>lorem_phrase(),
			'CONTENT'=>$next_items
		));
		$sections->attach($do_next_section);

		return array(
			lorem_globalise(do_lorem_template('DO_NEXT_SCREEN', array(
				'INTRO'=>lorem_phrase_html(),
				'QUESTION'=>lorem_phrase(),
				'TITLE'=>lorem_title(),
				'SECTIONS'=>$sections
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
	function tpl_preview__question_ui_buttons()
	{
		return array(
			lorem_globalise(do_lorem_template('QUESTION_UI_BUTTONS', array(
				'BUTTONS'=>placeholder_array(),
				'IMAGES'=>array(),
				'TITLE'=>lorem_phrase(),
				'MESSAGE'=>lorem_phrase()
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
	function tpl_preview__next_browser_screen()
	{
		$browse=do_lorem_template('NEXT_BROWSER_BROWSE_NEXT', array(
			'NEXT_URL'=>placeholder_url(),
			'PREVIOUS_URL'=>placeholder_url(),
			'PAGE_NUM'=>placeholder_number(),
			'NUM_PAGES'=>placeholder_number()
		));

		return array(
			lorem_globalise(do_lorem_template('NEXT_BROWSER_SCREEN', array(
				'TITLE'=>lorem_title(),
				'CONTENT'=>lorem_phrase(),
				'BROWSE'=>$browse
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
	function tpl_preview__splurgh_screen()
	{
		$content=do_lorem_template('SPLURGH', array(
			'KEY_NAME'=>lorem_word(),
			'URL_STUB'=>placeholder_url(),
			'SPLURGH'=>'a,b,[c,d,],'
		));

		return array(
			lorem_globalise(do_lorem_template('SPLURGH_SCREEN', array(
				'TITLE'=>lorem_title(),
				'CONTENT'=>$content
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
	function tpl_preview__full_message_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('FULL_MESSAGE_SCREEN', array(
				'TITLE'=>lorem_title(),
				'TEXT'=>lorem_sentence()
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
	function tpl_preview__full_table_screen()
	{
		$table_rows=new ocp_tempcode();
		foreach (placeholder_array() as $row)
		{
			$actions=do_lorem_template('COLUMNED_TABLE_ACTION_DELETE_ENTRY', array(
				'GET'=>true,
				'HIDDEN'=>'',
				'NAME'=>lorem_phrase(),
				'URL'=>placeholder_url()
			));
			$actions->attach(do_lorem_template('COLUMNED_TABLE_ACTION_INSTALL_ENTRY', array(
				'GET'=>true,
				'HIDDEN'=>'',
				'NAME'=>lorem_phrase(),
				'URL'=>placeholder_url()
			)));
			$actions->attach(do_lorem_template('COLUMNED_TABLE_ACTION_UPGRADE_ENTRY', array(
				'GET'=>true,
				'HIDDEN'=>'',
				'NAME'=>lorem_phrase(),
				'URL'=>placeholder_url()
			)));
			$actions->attach(do_lorem_template('COLUMNED_TABLE_ACTION_REINSTALL_ENTRY', array(
				'GET'=>true,
				'HIDDEN'=>'',
				'NAME'=>lorem_phrase(),
				'URL'=>placeholder_url()
			)));

			$select=do_lorem_template('COLUMNED_TABLE_ROW_CELL_SELECT', array(
				'LABEL'=>lorem_phrase(),
				'NAME'=>placeholder_random_id(),
				'LIST'=>placeholder_options()
			));

			$values=array(
				lorem_word(),
				lorem_word(),
				lorem_word(),
				placeholder_time(),
				$select,
				$actions
			);
			$cells=new ocp_tempcode();
			foreach ($values as $value)
			{
				$cells->attach(do_lorem_template('COLUMNED_TABLE_ROW_CELL', array(
					'VALUE'=>$value
				)));
			}

			$tpl=do_lorem_template('COLUMNED_TABLE_ROW', array(
				'CELLS'=>$cells
			));
			$table_rows->attach($tpl);
		}

		$values=array(
			lorem_word(),
			lorem_word_2(),
			lorem_word(),
			lorem_word_2(),
			lorem_word(),
			lorem_word()
		);
		$cells=new ocp_tempcode();
		foreach ($values as $value)
		{
			$cells->attach(do_lorem_template('COLUMNED_TABLE_HEADER_ROW_CELL', array(
				'VALUE'=>$value
			)));
		}

		$header_row=do_lorem_template('COLUMNED_TABLE_HEADER_ROW', array(
			'CELLS'=>$cells
		));

		$field_rows=do_lorem_template('COLUMNED_TABLE', array(
			'HEADER_ROW'=>$header_row,
			'ROWS'=>$table_rows
		));

		$return=do_lorem_template('FORM_GROUPED', array(
			'TEXT'=>"",
			'URL'=>"#",
			'FIELD_GROUPS'=>$field_rows,
			'SUBMIT_NAME'=>lorem_word_2()
		));

		return array(
			lorem_globalise($return, NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__result_table_screen()
	{
		//results_table starts
		//results_entry starts
		$array=placeholder_array();
		$cells=new ocp_tempcode();
		foreach ($array as $k=>$v)
		{
			if ($k==1)
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE', array(
					'VALUE'=>$v
				)));
			else
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE_SORTABLE', array(
					'VALUE'=>$v,
					'SORT_URL_DESC'=>placeholder_url(),
					'SORT_DESC_SELECTED'=>lorem_word(),
					'SORT_ASC_SELECTED'=>lorem_word(),
					'SORT_URL_ASC'=>placeholder_url()
				)));
		}
		$fields_title=$cells;

		$order_entries=new ocp_tempcode();
		foreach ($array as $k1=>$v)
		{
			$cells=new ocp_tempcode();
			foreach ($array as $k2=>$v2)
			{
				$tick=do_lorem_template('RESULTS_TABLE_TICK', array(
					'ID'=>placeholder_id() . '_' . strval($k1) . '_' . strval($k2)
				));
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD', array(
					'VALUE'=>$tick
				)));
			}
			$order_entries->attach(do_lorem_template('RESULTS_TABLE_ENTRY', array(
				'VALUES'=>$cells
			)));
		}
		//results_entry ends

		$selectors=new ocp_tempcode();
		$sortable=NULL;
		foreach ($array as $k=>$v)
		{
			$selectors->attach(do_lorem_template('PAGINATION_SORTER', array(
				'SELECTED'=>'',
				'NAME'=>$v,
				'VALUE'=>$v
			)));
		}
		$sort=do_lorem_template('PAGINATION_SORT', array(
			'HIDDEN'=>'',
			'SORT'=>lorem_word(),
			'URL'=>placeholder_url(),
			'SELECTORS'=>$selectors
		));

		$results_table=do_lorem_template('RESULTS_TABLE', array(
			'WIDTHS'=>array(),
			'TEXT_ID'=>lorem_phrase(),
			'FIELDS_TITLE'=>$fields_title,
			'FIELDS'=>$order_entries,
			'MESSAGE'=>'',
			'SORT'=>$sort,
			'PAGINATION'=>placeholder_pagination()
		));
		//results_table ends

		$table=do_lorem_template('RESULTS_TABLE_SCREEN', array(
			'TITLE'=>lorem_title(),
			'RESULTS_TABLE'=>$results_table
		));

		return array(
			lorem_globalise($table, NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__result_table_screen_2()
	{
		//results_table starts
		//results_entry starts
		$array=placeholder_array();
		$cells=new ocp_tempcode();
		foreach ($array as $k=>$v)
		{
			if ($k==1)
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE', array(
					'VALUE'=>$v
				)));
			else
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE_SORTABLE', array(
					'VALUE'=>$v,
					'SORT_URL_DESC'=>placeholder_url(),
					'SORT_DESC_SELECTED'=>lorem_word(),
					'SORT_ASC_SELECTED'=>lorem_word(),
					'SORT_URL_ASC'=>placeholder_url()
				)));
		}
		$fields_title=$cells;

		$order_entries=new ocp_tempcode();
		foreach ($array as $k1=>$v)
		{
			$cells=new ocp_tempcode();
			foreach ($array as $k2=>$v2)
			{
				$tick=do_lorem_template('RESULTS_TABLE_TICK', array(
					'ID'=>placeholder_id() . '_' . strval($k1) . '_' . strval($k2)
				));
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD', array(
					'VALUE'=>$tick
				)));
			}
			$order_entries->attach(do_lorem_template('RESULTS_TABLE_ENTRY', array(
				'VALUES'=>$cells
			)));
		}
		//results_entry ends

		$selectors=new ocp_tempcode();
		$sortable=NULL;
		foreach ($array as $k=>$v)
		{
			$selectors->attach(do_lorem_template('PAGINATION_SORTER', array(
				'SELECTED'=>'',
				'NAME'=>$v,
				'VALUE'=>$v
			)));
		}
		$sort=do_lorem_template('PAGINATION_SORT', array(
			'HIDDEN'=>'',
			'SORT'=>lorem_word(),
			'URL'=>placeholder_url(),
			'SELECTORS'=>$selectors
		));

		$results_table=do_lorem_template('RESULTS_TABLE', array(
			'WIDTHS'=>array(),
			'TEXT_ID'=>lorem_phrase(),
			'FIELDS_TITLE'=>$fields_title,
			'FIELDS'=>$order_entries,
			'MESSAGE'=>'',
			'SORT'=>$sort,
			'PAGINATION'=>placeholder_pagination()
		));
		//results_table ends

		$table=do_lorem_template('RESULTS_TABLE_SCREEN', array(
			'TITLE'=>lorem_title(),
			'RESULTS_TABLE'=>$results_table
		));

		return array(
			lorem_globalise($table, NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__result_launcher_screen()
	{
		require_lang('ocf');
		$part=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$part->attach(do_lorem_template('RESULTS_LAUNCHER_PAGE_NUMBER_LINK', array(
				'TITLE'=>lorem_phrase(),
				'URL'=>placeholder_url(),
				'P'=>placeholder_number()
			)));
			$part->attach(do_lorem_template('RESULTS_LAUNCHER_CONTINUE', array(
				'TITLE'=>lorem_phrase(),
				'MAX'=>placeholder_number(),
				'NUM_PAGES'=>placeholder_number(),
				'URL_STUB'=>placeholder_url()
			)));
		}

		$pages=do_lorem_template('RESULTS_LAUNCHER_WRAP', array(
			'PART'=>$part
		));

		return array(
			lorem_globalise($pages, NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__columned_table_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('COLUMNED_TABLE_SCREEN', array(
				'TITLE'=>lorem_title(),
				'TABLE'=>placeholder_table(),
				'SUBMIT_NAME'=>lorem_word(),
				'POST_URL'=>placeholder_url()
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
	function tpl_preview__member_tooltip()
	{
		return array(
			lorem_globalise(do_lorem_template('MEMBER_TOOLTIP', array(
				'SUBMITTER'=>placeholder_id(),
			)), NULL, '', true)
		);

	}

}
