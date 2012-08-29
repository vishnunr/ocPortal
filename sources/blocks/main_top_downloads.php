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
 * @package		downloads
 */

class Block_main_top_downloads
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('param','zone','show_dload_trees','filter','title');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(array_key_exists(\'title\',$map)?$map[\'title\']:\'\',$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'param\',$map)?intval($map[\'param\']):10,array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'downloads\'),((get_option(\'show_dload_trees\',true)===\'1\') || ((array_key_exists(\'show_dload_trees\',$map)) && ($map[\'show_dload_trees\']==\'1\')))?2:1,array_key_exists(\'filter\',$map)?$map[\'filter\']:\'*\')';
		$info['ttl']=60*24;
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_code('downloads');
		require_css('downloads');
		require_lang('downloads');
		require_code('ocfiltering');

		$number=array_key_exists('param',$map)?intval($map['param']):10;
		$filter=array_key_exists('filter',$map)?$map['filter']:'*';
		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('downloads');

		$sql_filter=ocfilter_to_sqlfragment($filter,'p.category_id','download_categories','parent_id','p.category_id','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
		$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'download_downloads p WHERE validated=1 AND ('.$sql_filter.') ORDER BY num_downloads DESC',$number);

		$title=do_lang_tempcode('TOP',make_string_tempcode(integer_format($number)),do_lang_tempcode('SECTION_DOWNLOADS'));
		if ((array_key_exists('title',$map)) && ($map['title']!='')) $title=make_string_tempcode(escape_html($map['title']));

		$out=new ocp_tempcode();
		foreach ($rows as $row)
		{
			$out->attach(render_download_box($row,true,true,$zone));
		}
		if ($out->is_empty())
		{
			if ((has_actual_page_access(NULL,'cms_downloads',NULL,NULL)) && (has_submit_permission('mid',get_member(),get_ip_address(),'cms_downloads')))
			{
				$submit_url=build_url(array('page'=>'cms_downloads','type'=>'ad','redirect'=>SELF_REDIRECT),get_module_zone('cms_downloads'));
			} else $submit_url=new ocp_tempcode();
			return do_template('BLOCK_NO_ENTRIES',array('_GUID'=>'1274325c9ec105918d5c1c54200109de','HIGH'=>false,'TITLE'=>$title,'MESSAGE'=>do_lang_tempcode('NO_DOWNLOADS_YET'),'ADD_NAME'=>do_lang_tempcode('ADD_DOWNLOAD'),'SUBMIT_URL'=>$submit_url));
		}

		return do_template('BLOCK_MAIN_TOP_DOWNLOADS',array('_GUID'=>'740c31abd06b331b0276b04f67291cdb','TITLE'=>$title,'CONTENT'=>$out,'NUMBER'=>integer_format($number)));
	}

}


