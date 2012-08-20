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
 * @package		core_ocf
 */

class Hook_awards_member
{

	/**
	 * Standard modular info function for award hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		if (get_forum_type()!='ocf') return NULL;

		$info=array();
		$info['connection']=$GLOBALS['FORUM_DB'];
		$info['table']='f_members';
		$info['date_field']='m_join_time';
		$info['id_field']='id';
		$info['add_url']='';
		$info['category_field']='m_primary_group';
		$info['submitter_field']='id';
		$info['id_is_string']=false;
		$info['title']=do_lang_tempcode('MEMBERS');
		$info['validated_field']='m_validated';
		$info['category_is_string']=false;
		$info['archive_url']=build_url(array('page'=>'members'),(!is_null($zone))?$zone:get_module_zone('members'));
		$info['cms_page']='admin_ocf_join';

		return $info;
	}

	/**
	 * Standard modular run function for award hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @return tempcode	Results
	 */
	function run($row,$zone)
	{
		unset($zone);

		require_code('ocf_members');
		require_code('ocf_members2');

		$GLOBALS['OCF_DRIVER']->MEMBER_ROWS_CACHED[$row['id']]=$row;

		return render_member_box($row['id']);
	}

}


