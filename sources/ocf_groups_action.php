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

/**
 * Add a usergroup.
 *
 * @param  SHORT_TEXT	The name of the usergroup.
 * @param  BINARY			Whether members are automatically put into the when they join.
 * @param  BINARY			Whether members of this usergroup are all super administrators.
 * @param  BINARY			Whether members of this usergroup are all super moderators.
 * @param  SHORT_TEXT 	The title for primary members of this usergroup that don't have their own title.
 * @param  URLPATH		The rank image for this.
 * @param  ?GROUP			The that members of this usergroup get promoted to at point threshold (NULL: no promotion prospects).
 * @param  ?integer		The point threshold for promotion (NULL: no promotion prospects).
 * @param  ?MEMBER		The leader of this usergroup (NULL: none).
 * @param  integer		The number of seconds that members of this usergroup must endure between submits (group 'best of' applies).
 * @param  integer		The number of seconds that members of this usergroup must endure between accesses (group 'best of' applies).
 * @param  integer		The number of megabytes that members of this usergroup may attach per day (group 'best of' applies).
 * @param  integer		The number of attachments that members of this usergroup may attach to something (group 'best of' applies).
 * @param  integer		The maximum avatar width that members of this usergroup may have (group 'best of' applies).
 * @param  integer		The maximum avatar height that members of this usergroup may have (group 'best of' applies).
 * @param  integer		The maximum post length that members of this usergroup may make (group 'best of' applies).
 * @param  integer		The maximum signature length that members of this usergroup may make (group 'best of' applies).
 * @param  integer		The number of gift points that members of this usergroup start with (group 'best of' applies).
 * @param  integer		The number of gift points that members of this usergroup get per day (group 'best of' applies).
 * @param  BINARY			Whether e-mail confirmation is needed for new IP addresses seen for any member of this usergroup (group 'best of' applies).
 * @param  BINARY			Whether the usergroup is presented for joining at joining (implies anyone may be in the, but only choosable at joining)
 * @param  BINARY			Whether the name and membership of the is hidden
 * @param  ?integer		The display order this will be given, relative to other usergroups. Lower numbered usergroups display before higher numbered usergroups (NULL: next).
 * @param  BINARY			Whether the rank image will not be shown for secondary membership
 * @param  BINARY			Whether members may join this usergroup without requiring any special permission
 * @param  BINARY			Whether this usergroup is a private club. Private clubs may be managed in the CMS zone, and do not have any special permissions - except over their own associated forum.
 * @return AUTO_LINK		The ID of the new.
 */
function ocf_make_group($name,$is_default=0,$is_super_admin=0,$is_super_moderator=0,$title='',$rank_image='',$promotion_target=NULL,$promotion_threshold=NULL,$group_leader=NULL,$flood_control_submit_secs=0,$flood_control_access_secs=0,$max_daily_upload_mb=70,$max_attachments_per_post=50,$max_avatar_width=100,$max_avatar_height=100,$max_post_length_comcode=30000,$max_sig_length_comcode=700,$gift_points_base=25,$gift_points_per_day=1,$enquire_on_new_ips=0,$is_presented_at_install=0,$hidden=0,$order=NULL,$rank_image_pri_only=1,$open_membership=0,$is_private_club=0)
{
	//	if (is_null($group_leader)) $group_leader=db_get_first_id()+1;

	if (!running_script('stress_test_loader'))
	{
		$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_groups g LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON g.g_name=t.id WHERE '.db_string_equal_to('text_original',$name),'g.id');
		if (!is_null($test)) warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($name)));
	}

	if (is_null($is_super_admin)) $is_super_admin=0;
	if (is_null($is_super_moderator)) $is_super_moderator=0;

	if (!running_script('stress_test_loader'))
	{
		if (is_null($order))
		{
			$order=$GLOBALS['FORUM_DB']->query_value('f_groups','MAX(g_order)');
			if (is_null($order)) $order=0; else $order++;
		}
	} else $order=100;

	$map=array(
		'g_is_default'=>$is_default,
		'g_is_presented_at_install'=>$is_presented_at_install,
		'g_is_super_admin'=>$is_super_admin,
		'g_is_super_moderator'=>$is_super_moderator,
		'g_group_leader'=>$group_leader,
		'g_promotion_target'=>$promotion_target,
		'g_promotion_threshold'=>$promotion_threshold,
		'g_flood_control_submit_secs'=>$flood_control_submit_secs,
		'g_flood_control_access_secs'=>$flood_control_access_secs,
		'g_max_daily_upload_mb'=>$max_daily_upload_mb,
		'g_max_attachments_per_post'=>$max_attachments_per_post,
		'g_max_avatar_width'=>$max_avatar_width,
		'g_max_avatar_height'=>$max_avatar_height,
		'g_max_post_length_comcode'=>$max_post_length_comcode,
		'g_max_sig_length_comcode'=>$max_sig_length_comcode,
		'g_gift_points_base'=>$gift_points_base,
		'g_gift_points_per_day'=>$gift_points_per_day,
		'g_enquire_on_new_ips'=>$enquire_on_new_ips,
		'g_rank_image'=>$rank_image,
		'g_hidden'=>$hidden,
		'g_order'=>$order,
		'g_rank_image_pri_only'=>$rank_image_pri_only,
		'g_open_membership'=>$open_membership,
		'g_is_private_club'=>$is_private_club,
	);
	$map+=insert_lang('g_name',$name,2,$GLOBALS['FORUM_DB']);
	$map+=insert_lang('g_title',$title,2,$GLOBALS['FORUM_DB']);
	$group_id=$GLOBALS['FORUM_DB']->query_insert('f_groups',$map,true);

	if (($group_id>db_get_first_id()+8) && ($is_private_club==0))
	{
		// Copy permissions from members
		require_code('ocf_groups');
		$group_members=get_first_default_group();
		$member_access=$GLOBALS['SITE_DB']->query_select('gsp',array('*'),array('group_id'=>$group_members));
		foreach ($member_access as $access)
		{
			$access['group_id']=$group_id;
			$GLOBALS['SITE_DB']->query_insert('gsp',$access,false,true); // failsafe, in case we have put in some permissions for a group since deleted (can happen during install)
		}
		$member_access=$GLOBALS['SITE_DB']->query_select('group_category_access',array('*'),array('group_id'=>$group_members));
		foreach ($member_access as $access)
		{
			$access['group_id']=$group_id;
			$GLOBALS['SITE_DB']->query_insert('group_category_access',$access,false,true); // failsafe, in case we have put in some permissions for a group since deleted (can happen during install)
		}
		$member_access=$GLOBALS['SITE_DB']->query_select('group_zone_access',array('*'),array('group_id'=>$group_members));
		foreach ($member_access as $access)
		{
			$access['group_id']=$group_id;
			$GLOBALS['SITE_DB']->query_insert('group_zone_access',$access,false,true); // failsafe, in case we have put in some permissions for a group since deleted (can happen during install)
		}
	}

	log_it('ADD_GROUP',strval($group_id),$name);

	if ($is_private_club==1)
	{
		require_code('notifications');
		$subject=do_lang('NEW_CLUB_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$name);
		$view_url=build_url(array('page'=>'groups','type'=>'view','id'=>$group_id),get_module_zone('groups'),NULL,false,false,true);
		$mail=do_lang('NEW_CLUB_NOTIFICATION_MAIL',get_site_name(),comcode_escape($name),array(comcode_escape($view_url->evaluate())));
		dispatch_notification('ocf_club',NULL,$subject,$mail);
	}

	return $group_id;
}

