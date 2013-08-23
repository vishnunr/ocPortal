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
 * @package		catalogues
 */

class Hook_sw_catalogues
{

	/**
	 * Standard modular run function for features in the setup wizard.
	 *
	 * @param  array		Default values for the fields, from the install-profile.
	 * @return tempcode	An input field.
	 */
	function get_fields($field_defaults)
	{
		if (!addon_installed('catalogues')) return new ocp_tempcode();

		require_lang('catalogues');
		$fields=new ocp_tempcode();
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'hosted'));
		if (!is_null($test)) $fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_CATALOGUES_HOSTING'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_CATALOGUES_HOSTING'),'have_default_catalogues_hosting',array_key_exists('have_default_catalogues_hosting',$field_defaults)?($field_defaults['have_default_catalogues_hosting']=='1'):false));
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'projects'));
		if (!is_null($test)) $fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_CATALOGUES_PROJECTS'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_CATALOGUES_PROJECTS'),'have_default_catalogues_projects',array_key_exists('have_default_catalogues_projects',$field_defaults)?($field_defaults['have_default_catalogues_projects']=='1'):false));
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'faqs'));
		if (!is_null($test)) $fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_CATALOGUES_FAQS'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_CATALOGUES_FAQS'),'have_default_catalogues_faqs',array_key_exists('have_default_catalogues_faqs',$field_defaults)?($field_defaults['have_default_catalogues_faqs']=='1'):true));
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'links'));
		if (!is_null($test)) $fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_CATALOGUES_LINKS'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_CATALOGUES_LINKS'),'have_default_catalogues_links',array_key_exists('have_default_catalogues_links',$field_defaults)?($field_defaults['have_default_catalogues_links']=='1'):true));
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'modifications'));
		if (!is_null($test)) $fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_CATALOGUES_MODIFICATIONS'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_CATALOGUES_MODIFICATIONS'),'have_default_catalogues_modifications',array_key_exists('have_default_catalogues_modifications',$field_defaults)?($field_defaults['have_default_catalogues_modifications']=='1'):false));
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'contacts'));
		if (!is_null($test)) $fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_CATALOGUES_CONTACTS'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_CATALOGUES_CONTACTS'),'have_default_catalogues_contacts',array_key_exists('have_default_catalogues_contacts',$field_defaults)?($field_defaults['have_default_catalogues_contacts']=='1'):true));
		return $fields;
	}

	/**
	 * Standard modular run function for setting features from the setup wizard.
	 */
	function set_fields()
	{
		if (!addon_installed('catalogues')) return;

		if (post_param_integer('have_default_catalogues_hosting',0)==0)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'hosted'));
			if (!is_null($test))
			{
				require_code('catalogues2');
				actual_delete_catalogue('hosted');
				delete_menu_item_simple('_SEARCH:catalogues:type=index:id=hosted');
			}
		}
		if (post_param_integer('have_default_catalogues_projects',0)==0)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'projects'));
			if (!is_null($test))
			{
				require_code('catalogues2');
				actual_delete_catalogue('projects');
				require_lang('catalogues');
				delete_menu_item_simple(do_lang('DEFAULT_CATALOGUE_PROJECTS_TITLE'));
				delete_menu_item_simple('_SEARCH:catalogues:id=projects:type=index');
				delete_menu_item_simple('_SEARCH:cms_catalogues:type=add_entry:catalogue_name=projects');
				delete_menu_item_simple('_SEARCH:catalogues:type=index:id=projects');
			}
		}
		if (post_param_integer('have_default_catalogues_faqs',0)==0)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'faqs'));
			if (!is_null($test))
			{
				require_code('catalogues2');
				actual_delete_catalogue('faqs');
				delete_menu_item_simple('_SEARCH:catalogues:type=index:id=faqs');
			}
		}
		if (post_param_integer('have_default_catalogues_links',0)==0)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'links'));
			if (!is_null($test))
			{
				require_code('catalogues2');
				actual_delete_catalogue('links');
				delete_menu_item_simple('_SEARCH:catalogues:type=index:id=links');
			}
		}
		if (post_param_integer('have_default_catalogues_modifications',0)==0)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'modifications'));
			if (!is_null($test))
			{
				require_code('catalogues2');
				actual_delete_catalogue('modifications');
				delete_menu_item_simple('_SEARCH:catalogues:type=index:id=modifications');
			}
		}
		if (post_param_integer('have_default_catalogues_contacts',0)==0)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'contacts'));
			if (!is_null($test))
			{
				require_code('catalogues2');
				actual_delete_catalogue('contacts');
				delete_menu_item_simple('_SEARCH:catalogues:type=index:id=contacts');
			}
		}
	}

	/**
	 * Standard modular run function for blocks in the setup wizard.
	 *
	 * @return array		Map of block names, to display types.
	 */
	function get_blocks()
	{
		if (!addon_installed('catalogues')) return array();

		return array(array('main_recent_cc_entries'=>array('NO','NO')),array());
	}
}


