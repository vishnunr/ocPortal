<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		filedump
 */

class Hook_Notification_filedump extends Hook_Notification
{
	/**
	 * Find whether a handled notification code supports categories.
	 * (Content types, for example, will define notifications on specific categories, not just in general. The categories are interpreted by the hook and may be complex. E.g. it might be like a regexp match, or like FORUM:3 or TOPIC:100)
	 *
	 * @param  ID_TEXT		Notification code
	 * @return boolean		Whether it does
	 */
	function supports_categories($notification_code)
	{
		return true;
	}

	/**
	 * Standard function to create the standardised category tree
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?ID_TEXT		The ID of where we're looking under (NULL: N/A)
	 * @return array 			Tree structure
	 */
	function create_category_tree($notification_code,$id)
	{
		require_code('files2');

		$path=get_custom_file_base().'/uploads/filedump';
		if (!is_null($id))
		{
			$path.='/'.$id;
		}
		$files=get_directory_contents($path,'',false,false);

		if (count($files)>30) return array(); // Too many, so don't show

		$pagelinks=array();
		foreach ($files as $file)
		{
			if (is_dir($path.'/'.$file))
			{
				$pagelinks[]=array(
					'id'=>(($id=='')?'':($id.'/')).$file,
					'title'=>$file,
					'child_count'=>count($this->create_category_tree($notification_code,(($id=='')?'':($id.'/')).$file)),
				);
			}
		}

		return $pagelinks;
	}

	/**
	 * Find the initial setting that members have for a notification code (only applies to the member_could_potentially_enable members).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return integer		Initial setting
	 */
	function get_initial_setting($notification_code,$category=NULL)
	{
		return A_NA;
	}

	/**
	 * Get a list of all the notification codes this hook can handle.
	 * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
	 *
	 * @return array			List of codes (mapping between code names, and a pair: section and labelling for those codes)
	 */
	function list_handled_codes()
	{
		$list=array();
		$list['filedump']=array(do_lang('menus:CONTENT'),do_lang('NOTIFICATION_TYPE_filedump'));
		return $list;
	}

	/**
	 * Get a list of members who have enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function list_members_who_have_enabled($notification_code,$category=NULL,$to_member_ids=NULL,$start=0,$max=300)
	{
		$members=$this->_all_members_who_have_enabled($notification_code,$category,$to_member_ids,$start,$max);
		$members=$this->_all_members_who_have_enabled_with_page_access($members,'filedump',$notification_code,$category,$to_member_ids,$start,$max);

		return $members;
	}
}
