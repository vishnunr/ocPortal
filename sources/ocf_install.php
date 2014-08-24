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
 * @package		core_ocf
 */

/*

OCF is designed to run under a number of different situations, via the object orientated driver situation, and some driver variable switcher functions in import.php.
 - Standalone OCF (FORUM_DB==SITE_DB, but not explicitly)
 - Installation (force standalone OCF, regardless of whether it is on an MSN in normal use: FORUM_DB:=SITE_DB, for installation/upgrading of OCF only)
 - Multi site network linking / Importing a non-forum (FORUM_DB!=SITE_DB)
 - Importing a forum (import.php functions used to to and from OCF and normal-forum-driver [which might not be OCF])

Exception: Welcome Mails are per-site, so run through SITE_DB. They have their own install code in the admin module though.

SITE_DB - The site database, always
FORUM_DB - The forum database. If not using OCF, will not be the OCF db. Switched around by installer/forum-importers according for need for locality/OCFality.
FORUM_DRIVER - The forum driver. May not be OCF, depending on which forum driver is used.
OCF_DRIVER - A forum driver to OCF. This is always used in OCF rather than FORUM_DRIVER, in case OCF functions are being called when OCF is not the primary forum driver running (when importing to OCF but using a different forum driver, often when in the process of switching forums)

The OCF functions should always call up the attachment and language systems using explicitly the forum database, so multi site networks function properly.

*/

/**
 * Standard code module initialisation function.
 */
function init__ocf_install()
{
	global $OCF_TRUE_PERMISSIONS,$OCF_FALSE_PERMISSIONS;
	$OCF_TRUE_PERMISSIONS=array(
		'run_multi_moderations',
		'use_pt',
		'edit_private_topic_posts',
		'may_unblind_own_poll',
		'may_report_post',
		'view_member_photos',
		'use_quick_reply',
		'view_profiles',
		'own_avatars',
	);
	$OCF_FALSE_PERMISSIONS=array(
		'rename_self',
		'use_special_emoticons',
		'view_any_profile_field',
		'disable_lost_passwords',
		'close_own_topics',
		'edit_own_polls',
		'double_post',
		'see_warnings',
		'see_ip',
		'may_choose_custom_title',
		'delete_account',
		'view_other_pt',
		'view_poll_results_before_voting',
		'moderate_private_topic',
		'member_maintenance',
		'probate_members',
		'warn_members',
		'control_usergroups',
		'multi_delete_topics',
		'show_user_browsing',
		'see_hidden_groups',
		'pt_anyone',
		'delete_private_topic_posts',
	);
}

/**
 * Uninstall OCF
 */
function uninstall_ocf()
{
	global $OCF_TRUE_PERMISSIONS,$OCF_FALSE_PERMISSIONS;

	foreach ($OCF_TRUE_PERMISSIONS as $permission)
	{
		delete_privilege($permission);
	}
	foreach ($OCF_FALSE_PERMISSIONS as $permission)
	{
		delete_privilege($permission);
	}

	$GLOBALS['FORUM_DB']->query_delete('group_category_access',array('module_the_name'=>'forums'));

	delete_value('ocf_newest_member_id');
	delete_value('ocf_newest_member_username');
	delete_value('ocf_member_count');
	delete_value('ocf_topic_count');
	delete_value('ocf_post_count');

	require_code('files');
	deldir_contents(get_custom_file_base().'/uploads/ocf_avatars',true);
	deldir_contents(get_custom_file_base().'/uploads/ocf_photos',true);
	deldir_contents(get_custom_file_base().'/uploads/ocf_photos_thumbs',true);
	deldir_contents(get_custom_file_base().'/uploads/avatars',true);
	deldir_contents(get_custom_file_base().'/uploads/photos',true);
	deldir_contents(get_custom_file_base().'/uploads/photos_thumbs',true);

	delete_attachments('ocf_post');
	delete_attachments('ocf_signature');

	require_code('database_action');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_emoticons');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forum_group_access');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_custom_fields');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_member_custom_fields');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_groups');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forum_groupings');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forums');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forum_intro_ip');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forum_intro_member');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_topics');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_posts');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_post_history');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_polls');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_poll_answers');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_poll_votes');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_post_templates');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_warnings');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_moderator_logs');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_member_known_login_ips');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_members');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_group_members');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_read_logs');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forum_tracking');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_topic_tracking');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_multi_moderations');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_invites');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forum_group_access');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_special_pt_access');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_saved_warnings');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_member_cpf_perms');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_group_join_log');
	$GLOBALS['FORUM_DB']->drop_table_if_exists('f_password_history');

	$GLOBALS['FORUM_DB']->query_delete('group_privileges',array('module_the_name'=>'forums'));
}

/**
 * Install/upgrade OCF.
 *
 * @param  ?float	The version to upgrade from (NULL: fresh install).
 */
function install_ocf($upgrade_from=NULL)
{
	if (strtoupper(ocp_srv('REQUEST_METHOD'))!='POST') exit(); // Needed as YSlow can load as GET's in background and cause horrible results

	require_code('ocf_members');
	require_code('ocf_topics');
	require_code('ocf_groups');
	require_code('ocf_forums');
	require_lang('ocf');
	require_lang('ocf_config');
	require_code('ocf_moderation_action');
	require_code('ocf_posts_action');
	require_code('ocf_members_action');
	require_code('ocf_groups_action');
	require_code('ocf_general_action');
	require_code('ocf_forums_action');
	require_code('ocf_topics_action');
	require_code('database_action');

	if (is_null($upgrade_from))
	{
		uninstall_ocf(); // Remove if already installed
	}

	// Upgrade code for making changes (<7 not supported) lots of LEGACY code below
	if ((!is_null($upgrade_from)) && ($upgrade_from<7.2))
	{
		$rows=$GLOBALS['FORUM_DB']->query('SELECT m_name FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'db_meta WHERE ('.db_string_equal_to('m_type','?INTEGER').' OR '.db_string_equal_to('m_type','BINARY').') AND '.db_string_equal_to('m_table','f_member_custom_fields'));
		foreach ($rows as $row)
		{
			$GLOBALS['FORUM_DB']->alter_table_field('f_member_custom_fields',$row['m_name'],'SHORT_TEXT');
		}

		$i=0;
		do
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_member_custom_fields',array('*'),NULL,'',100,$i);
			foreach ($rows as $j=>$row)
			{
				foreach ($row as $key=>$val)
				{
					if ((substr($key,0,6)=='field_') && (is_string($val)))
					{
						$val=str_replace('|',"\n",$val);
						$row[$key]=$val;
					}
				}
				if ($rows[$j]!=$row)
				{
					$GLOBALS['FORUM_DB']->query_update('f_member_custom_fields',array('mf_member_id'=>$row['mf_member_id']),$row,'',1);
				}
			}
			$i+=100;
		}
		while (count($rows)!=0);

		$GLOBALS['FORUM_DB']->alter_table_field('f_members','m_track_contributed_topics','BINARY','m_auto_monitor_contrib_content');
	}
	if ((!is_null($upgrade_from)) && ($upgrade_from<8.0))
	{
		$GLOBALS['FORUM_DB']->add_table_field('f_members','m_allow_emails_from_staff','BINARY');
		$GLOBALS['FORUM_DB']->add_table_field('f_custom_fields','cf_show_on_join_form','BINARY');
		$GLOBALS['FORUM_DB']->add_table_field('f_forums','f_is_threaded','BINARY',0);
		$GLOBALS['FORUM_DB']->add_table_field('f_posts','p_parent_id','?AUTO_LINK',NULL);
		$GLOBALS['FORUM_DB']->query('UPDATE '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_custom_fields SET cf_show_on_join_form=cf_required');
		delete_config_option('send_staff_message_post_validation');

		require_code('notifications');
		$start=0;
		do
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_forum_tracking',array('r_forum_id','r_member_id'),NULL,'',100,$start);
			foreach ($rows as $row)
			{
				enable_notifications('ocf_topic','forum:'.strval($row['r_forum_id']),$row['r_member_id']);
			}
			$start+=100;
		}
		while (count($rows)==100);
		$start=0;
		do
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_topic_tracking',array('r_topic_id','r_member_id'),NULL,'',100,$start);
			foreach ($rows as $row)
			{
				enable_notifications('ocf_topic',strval($row['r_topic_id']),$row['r_member_id']);
			}
			$start+=100;
		}
		while (count($rows)==100);

		$GLOBALS['FORUM_DB']->drop_table_if_exists('f_forum_tracking');
		$GLOBALS['FORUM_DB']->drop_table_if_exists('f_topic_tracking');
	}
	if ((!is_null($upgrade_from)) && ($upgrade_from<8.1))
	{
		delete_config_option('no_dob_ask');
	}
	if ((is_null($upgrade_from)) || ($upgrade_from<10.0))
	{
		$GLOBALS['FORUM_DB']->create_table('f_group_join_log',array(
			'id'=>'*AUTO',
			'member_id'=>'MEMBER',
			'usergroup_id'=>'?AUTO_LINK',
			'join_time'=>'TIME'
		));
		$GLOBALS['FORUM_DB']->create_index('f_group_join_log','member_id',array('member_id'));
		$GLOBALS['FORUM_DB']->create_index('f_group_join_log','usergroup_id',array('usergroup_id'));
		$GLOBALS['FORUM_DB']->create_index('f_group_join_log','join_time',array('join_time'));

		$GLOBALS['FORUM_DB']->create_table('f_password_history',array(
			'id'=>'*AUTO',
			'p_member_id'=>'MEMBER',
			'p_hash_salted'=>'SHORT_TEXT',
			'p_salt'=>'SHORT_TEXT',
			'p_time'=>'TIME',
		));
		$GLOBALS['FORUM_DB']->create_index('f_password_history','p_member_id',array('p_member_id'));
	}
	if ((!is_null($upgrade_from)) && ($upgrade_from<10.0))
	{
		$GLOBALS['FORUM_DB']->add_table_field('f_members','m_profile_views','UINTEGER');
		$GLOBALS['FORUM_DB']->add_table_field('f_members','m_total_sessions','UINTEGER');

		if (strpos(get_db_type(),'mysql')!==false)
		{
			$GLOBALS['FORUM_DB']->query('ALTER TABLE '.get_table_prefix().'f_poll_votes ADD COLUMN id int NOT NULL AUTO_INCREMENT, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
			$GLOBALS['FORUM_DB']->query_update('db_meta',array('m_type'=>'AUTO_LINK'),array('m_table'=>'f_poll_votes','m_type'=>'*AUTO_LINK'));
			$GLOBALS['FORUM_DB']->query_update('db_meta',array('m_type'=>'MEMBER'),array('m_table'=>'f_poll_votes','m_type'=>'*USER'));
			$GLOBALS['FORUM_DB']->query_insert('db_meta',array('m_table'=>'f_poll_votes','m_name'=>'id','m_type'=>'*AUTO'));
		}
		$GLOBALS['FORUM_DB']->add_table_field('f_poll_votes','pv_ip','IP');

		$GLOBALS['FORUM_DB']->rename_table('f_categories','f_forum_groupings');
		$GLOBALS['FORUM_DB']->alter_table_field('f_forums','f_category_id','AUTO_LINK','f_forum_grouping_id');
		$privileges=array('moderate_private_topic'=>'moderate_private_topic','edit_private_topic_posts'=>'edit_private_topic_posts','delete_private_topic_posts'=>'delete_private_topic_posts');
		foreach ($privileges as $old=>$new)
		{
			rename_privilege($old,$new);
		}
		$GLOBALS['FORUM_DB']->delete_table_field('f_members','m_notes');
		$GLOBALS['FORUM_DB']->delete_table_field('f_members','m_zone_wide');
		delete_config_option('skip_email_confirm_join');
		delete_config_option('prevent_shouting');

		// Initialise f_password_history with current data (we'll assume m_last_submit_time represents last password change, which is not true - but ok enough for early initialisation, and will scatter things quite nicely to break in the new rules gradually)
		if (function_exists('set_time_limit')) @set_time_limit(0);
		$max=500;
		$start=0;
		do
		{
			$members=$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_pass_hash_salted','m_pass_salt','m_last_submit_time','m_join_time'),NULL,'',$max,$start);
			foreach ($members as $member)
			{
				if ($member['id']!=$GLOBALS['FORUM_DRIVER']->get_guest_id())
				{
					$GLOBALS['FORUM_DB']->query_insert('f_password_history',array(
						'p_member_id'=>$member['id'],
						'p_hash_salted'=>$member['m_pass_hash_salted'],
						'p_salt'=>$member['m_pass_salt'],
						'p_time'=>is_null($member['m_last_submit_time'])?$member['m_join_time']:$member['m_last_submit_time'],
					));
				}
			}
			$start+=$max;
		}
		while (count($members)>0);
	}

	// If we have the forum installed to this db already, leave
	if (is_null($upgrade_from))
	{
		$GLOBALS['FORUM_DB']->create_table('f_member_cpf_perms',array(
			'member_id'=>'*MEMBER',
			'field_id'=>'*AUTO_LINK',
			'guest_view'=>'BINARY',
			'member_view'=>'BINARY',
			'friend_view'=>'BINARY',
			'group_view'=>'SHORT_TEXT'
		));

		$GLOBALS['FORUM_DB']->create_table('f_emoticons',array(
			'e_code'=>'*ID_TEXT',
			'e_theme_img_code'=>'SHORT_TEXT',
			'e_relevance_level'=>'INTEGER', // 0=core,1=supported,2=unsupported,3=crappy,4=unused
			'e_use_topics'=>'BINARY', // Whether to use it to show a topics emotion
			'e_is_special'=>'BINARY'
		));
		$GLOBALS['FORUM_DB']->create_index('f_emoticons','relevantemoticons',array('e_relevance_level'));
		$GLOBALS['FORUM_DB']->create_index('f_emoticons','topicemos',array('e_use_topics'));

		$GLOBALS['FORUM_DB']->create_table('f_custom_fields',array(
			'id'=>'*AUTO',
			'cf_locked'=>'BINARY',  // Can't be deleted
			'cf_name'=>'SHORT_TRANS',
			'cf_description'=>'SHORT_TRANS',
			'cf_default'=>'LONG_TEXT',
			'cf_public_view'=>'BINARY',
			'cf_owner_view'=>'BINARY',
			'cf_owner_set'=>'BINARY',
			'cf_type'=>'ID_TEXT', // /* can be short_text,long_text,long_trans,integer,url,upload,picture,list,tick */
			'cf_required'=>'BINARY',
			'cf_show_in_posts'=>'BINARY',
			'cf_show_in_post_previews'=>'BINARY',
			'cf_order'=>'INTEGER',
			'cf_only_group'=>'LONG_TEXT',
			'cf_encrypted'=>'BINARY',
			'cf_show_on_join_form'=>'BINARY',
		));

		// These don't need to be filled in. We just use default from custom field if they aren't
		$GLOBALS['FORUM_DB']->create_table('f_member_custom_fields',array(
			'mf_member_id'=>'*MEMBER'
		));

		ocf_make_boiler_custom_field('SELF_DESCRIPTION');
		//ocf_make_boiler_custom_field('im_jabber'); Old-school, although XMPP is still popular for some, so we won't remove entirely
		ocf_make_boiler_custom_field('im_skype');
		ocf_make_boiler_custom_field('sn_facebook');
		ocf_make_boiler_custom_field('sn_google');
		ocf_make_boiler_custom_field('sn_twitter');
		ocf_make_boiler_custom_field('interests');
		ocf_make_boiler_custom_field('location');
		ocf_make_boiler_custom_field('occupation');
		ocf_make_boiler_custom_field('staff_notes');

		$GLOBALS['FORUM_DB']->create_table('f_invites',array(
			'id'=>'*AUTO',
			'i_inviter'=>'MEMBER',
			'i_email_address'=>'SHORT_TEXT',
			'i_time'=>'TIME',
			'i_taken'=>'BINARY'
		));

		$GLOBALS['FORUM_DB']->create_table('f_group_members',array(
			'gm_group_id'=>'*GROUP',
			'gm_member_id'=>'*MEMBER',
			'gm_validated'=>'BINARY'
		));
		$GLOBALS['FORUM_DB']->create_index('f_group_members','gm_validated',array('gm_validated'));
		$GLOBALS['FORUM_DB']->create_index('f_group_members','gm_member_id',array('gm_member_id'));
		$GLOBALS['FORUM_DB']->create_index('f_group_members','gm_group_id',array('gm_group_id'));

		$GLOBALS['FORUM_DB']->create_table('f_members',array(
			'id'=>'*AUTO',
			'm_username'=>'ID_TEXT',
			'm_pass_hash_salted'=>'SHORT_TEXT', // Not MD5 type because it could store different things according to password_compatibility_scheme
			'm_pass_salt'=>'SHORT_TEXT',
			'm_theme'=>'ID_TEXT', // Blank means default
			'm_avatar_url'=>'URLPATH', // Blank means no avatar
			'm_validated'=>'BINARY',
			'm_validated_email_confirm_code'=>'SHORT_TEXT',
			'm_cache_num_posts'=>'INTEGER',
			'm_cache_warnings'=>'INTEGER',
			'm_join_time'=>'TIME',
			'm_timezone_offset'=>'SHORT_TEXT',
			'm_primary_group'=>'GROUP',
			'm_last_visit_time'=>'TIME', // This field is generally kept up-to-date, while the cookie 'last_visit' refers to the previous browsing session's time
			'm_last_submit_time'=>'TIME',
			'm_signature'=>'LONG_TRANS__COMCODE',
			'm_is_perm_banned'=>'BINARY',
			'm_preview_posts'=>'BINARY',
			'm_dob_day'=>'?SHORT_INTEGER',
			'm_dob_month'=>'?SHORT_INTEGER',
			'm_dob_year'=>'?INTEGER',
			'm_reveal_age'=>'BINARY',
			'm_email_address'=>'SHORT_TEXT',
			'm_title'=>'SHORT_TEXT', // Blank means use title
			'm_photo_url'=>'URLPATH', // Blank means no photo
			'm_photo_thumb_url'=>'URLPATH', // Blank means no photo
			'm_views_signatures'=>'BINARY',
			'm_auto_monitor_contrib_content'=>'BINARY',
			'm_language'=>'ID_TEXT',
			'm_ip_address'=>'IP',
			'm_allow_emails'=>'BINARY',
			'm_allow_emails_from_staff'=>'BINARY',
			'm_highlighted_name'=>'BINARY',
			'm_pt_allow'=>'SHORT_TEXT',
			'm_pt_rules_text'=>'LONG_TRANS__COMCODE',
			'm_max_email_attach_size_mb'=>'INTEGER',
			'm_password_change_code'=>'SHORT_TEXT',
			'm_password_compat_scheme'=>'ID_TEXT',
			'm_on_probation_until'=>'?TIME',
			'm_profile_views'=>'UINTEGER',
			'm_total_sessions'=>'UINTEGER',
		));
		$GLOBALS['FORUM_DB']->create_index('f_members','#search_user',array('m_username'));
		$GLOBALS['FORUM_DB']->create_index('f_members','user_list',array('m_username'));
		$GLOBALS['FORUM_DB']->create_index('f_members','menail',array('m_email_address'));
		$GLOBALS['FORUM_DB']->create_index('f_members','external_auth_lookup',array('m_pass_hash_salted'));
		$GLOBALS['FORUM_DB']->create_index('f_members','sort_post_count',array('m_cache_num_posts'));
		$GLOBALS['FORUM_DB']->create_index('f_members','m_join_time',array('m_join_time'));
		$GLOBALS['FORUM_DB']->create_index('f_members','whos_validated',array('m_validated'));
		$GLOBALS['FORUM_DB']->create_index('f_members','birthdays',array('m_dob_day','m_dob_month'));
		$GLOBALS['FORUM_DB']->create_index('f_members','ftjoin_msig',array('m_signature'));
		$GLOBALS['FORUM_DB']->create_index('f_members','primary_group',array('m_primary_group'));
		$GLOBALS['FORUM_DB']->create_index('f_members','avatar_url',array('m_avatar_url')); // Used for uniform avatar randomisation

		$no_use_topics=array('party'=>1,'christmas'=>1,'offtopic'=>1,'rockon'=>1,'guitar'=>1,'sinner'=>1,'wink'=>1,'kiss'=>1,'nod'=>1,'smile'=>1,'mellow'=>1,'whistle'=>1,'shutup'=>1,'cyborg'=>1);
		$core_emoticons=array(
			':P'=>'cheeky',
			":'("=>'cry',
			':dry:'=>'dry',
			':$'=>'blush',
			';)'=>'wink',
			'O_o'=>'blink',
			':wub:'=>'wub',
			':cool:'=>'cool',
			':lol:'=>'lol',
			':('=>'sad',
			':)'=>'smile',
			':thumbs:'=>'thumbs',
			':|'=>'mellow',
			':ninja:'=>'ph34r',
			':o'=>'shocked'
		);
		$supported_emoticons=array(
			':offtopic:'=>'offtopic', // Larger than normal, so don't put in core set
			':rolleyes:'=>'rolleyes',
			':D'=>'grin',
			'^_^'=>'glee',
			'(K)'=>'kiss',
			':S'=>'confused',
			':@'=>'angry',
			':shake:'=>'shake',
			':hand:'=>'hand',
			':drool:'=>'drool',
			':devil:'=>'devil',
			':party:'=>'party',
			':constipated:'=>'constipated',
			':depressed:'=>'depressed',
			':zzz:'=>'zzz',
			':whistle:'=>'whistle',
			':upsidedown:'=>'upsidedown',
			':sick:'=>'sick',
			':shutup:'=>'shutup',
			':sarcy:'=>'sarcy',
			':puppyeyes:'=>'puppyeyes',
			':nod:'=>'nod',
			':nerd:'=>'nerd',
			':king:'=>'king',
			':birthday:'=>'birthday',
			':cyborg:'=>'cyborg',
			':hippie:'=>'hippie',
			':ninja2:'=>'ninja2',
			':rockon:'=>'rockon',
			':sinner:'=>'sinner',
			':guitar:'=>'guitar',
			':angel:'=>'angel',
			':cowboy:'=>'cowboy',
			':fight:'=>'fight',
			':goodbye:'=>'goodbye',
			':idea:'=>'idea',
			':boat:'=>'boat',
			':fishing:'=>'fishing',
			':reallybadday:'=>'reallybadday',
		);
		$unused_emoticons=array(
			':christmas:'=>'christmas'
		);
		foreach ($core_emoticons as $a=>$b)
			ocf_make_emoticon($a,'ocf_emoticons/'.$b,0,array_key_exists($b,$no_use_topics)?0:1);
		foreach ($supported_emoticons as $a=>$b)
			ocf_make_emoticon($a,'ocf_emoticons/'.$b,1,array_key_exists($b,$no_use_topics)?0:1);
		foreach ($unused_emoticons as $a=>$b)
			ocf_make_emoticon($a,'ocf_emoticons/'.$b,1,array_key_exists($b,$no_use_topics)?0:1);

		$GLOBALS['FORUM_DB']->create_table('f_groups',array(
			'id'=>'*AUTO',
			'g_name'=>'SHORT_TRANS',
			'g_is_default'=>'BINARY',
			'g_is_presented_at_install'=>'BINARY',
			'g_is_super_admin'=>'BINARY',
			'g_is_super_moderator'=>'BINARY',
			'g_group_leader'=>'?MEMBER',
			'g_title'=>'SHORT_TRANS',
			'g_promotion_target'=>'?GROUP',
			'g_promotion_threshold'=>'?INTEGER',
			'g_flood_control_submit_secs'=>'INTEGER',
			'g_flood_control_access_secs'=>'INTEGER',
			'g_gift_points_base'=>'INTEGER',
			'g_gift_points_per_day'=>'INTEGER',
			'g_max_daily_upload_mb'=>'INTEGER',
			'g_max_attachments_per_post'=>'INTEGER',
			'g_max_avatar_width'=>'INTEGER',
			'g_max_avatar_height'=>'INTEGER',
			'g_max_post_length_comcode'=>'INTEGER',
			'g_max_sig_length_comcode'=>'INTEGER',
			'g_enquire_on_new_ips'=>'BINARY',
			'g_rank_image'=>'ID_TEXT',
			'g_hidden'=>'BINARY',
			'g_order'=>'INTEGER',
			'g_rank_image_pri_only'=>'BINARY',
			'g_open_membership'=>'BINARY',
			'g_is_private_club'=>'BINARY',
		));
		$GLOBALS['FORUM_DB']->create_index('f_groups','ftjoin_gname',array('g_name'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','ftjoin_gtitle',array('g_title'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','is_private_club',array('g_is_private_club'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','is_super_admin',array('g_is_super_admin'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','is_super_moderator',array('g_is_super_moderator'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','is_default',array('g_is_default'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','hidden',array('g_hidden'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','is_presented_at_install',array('g_is_presented_at_install'));
		$GLOBALS['FORUM_DB']->create_index('f_groups','gorder',array('g_order','id'));

		// For the_zone_access table
		require_code('zones2');
		reinstall_module('adminzone','admin_permissions');

		// Make guest
		$guest_group=ocf_make_group(do_lang('GUESTS'),0,0,0,do_lang('DESCRIPTION_GUESTS'));
		// Make admin
		$administrator_group=ocf_make_group(do_lang('ADMINISTRATORS'),0,1,0,do_lang('DESCRIPTION_ADMINISTRATORS'),'ocf_rank_images/admin',NULL,NULL,NULL,0);
		// Make mod
		$super_moderator_group=ocf_make_group(do_lang('SUPER_MODERATORS'),0,0,1,do_lang('DESCRIPTION_SUPER_MODERATORS'),'ocf_rank_images/mod',NULL,NULL,NULL,0);
		// Make supermember
		$super_member_group=ocf_make_group(do_lang('SUPER_MEMBERS'),0,0,0,do_lang('DESCRIPTION_SUPER_MEMBERS'),'',NULL,NULL,NULL,0);
		// Make member
		$member_group_4=ocf_make_group(do_lang('DEFAULT_RANK_4'),0,0,0,do_lang('DESCRIPTION_MEMBERS'),'ocf_rank_images/4');
		$member_group_3=ocf_make_group(do_lang('DEFAULT_RANK_3'),0,0,0,do_lang('DESCRIPTION_MEMBERS'),'ocf_rank_images/3',$member_group_4,10000);
		$member_group_2=ocf_make_group(do_lang('DEFAULT_RANK_2'),0,0,0,do_lang('DESCRIPTION_MEMBERS'),'ocf_rank_images/2',$member_group_3,2500);
		$member_group_1=ocf_make_group(do_lang('DEFAULT_RANK_1'),0,0,0,do_lang('DESCRIPTION_MEMBERS'),'ocf_rank_images/1',$member_group_2,400);
		$member_group_0=ocf_make_group(do_lang('DEFAULT_RANK_0'),0,0,0,do_lang('DESCRIPTION_MEMBERS'),'ocf_rank_images/0',$member_group_1,100); // Not default because primary is always defaulted to this one
		// Make probation
		$probation_group=ocf_make_group(do_lang('PROBATION'),0,0,0,do_lang('DESCRIPTION_PROBATION'),'',NULL,NULL,NULL,0);

		$GLOBALS['FORUM_DB']->create_table('f_forum_groupings',array(
			'id'=>'*AUTO',
			'c_title'=>'SHORT_TEXT',
			'c_description'=>'LONG_TEXT',
			'c_expanded_by_default'=>'BINARY'
		));
		$forum_grouping_id=ocf_make_forum_grouping(do_lang('DEFAULT_GROUPING_TITLE'),'');
		$forum_grouping_id_staff=ocf_make_forum_grouping(do_lang('STAFF'),'');

		$GLOBALS['FORUM_DB']->create_table('f_forums',array(
			'id'=>'*AUTO',
			'f_name'=>'SHORT_TEXT',
			'f_description'=>'LONG_TRANS__COMCODE',
			'f_forum_grouping_id'=>'?AUTO_LINK', // Categories can exist on multiple forum levels and positions - wherever a forum exists, the forum grouping it uses exists too (but not forums in the forum grouping which aren't at level and position)
			'f_parent_forum'=>'?AUTO_LINK',
			'f_position'=>'INTEGER', // might have been called 'f_order'=>'INTEGER' (consistent with other table's ordering fields) if we had not used f_order as a text field to determine the automatic ordering type
			'f_order_sub_alpha'=>'BINARY',
			'f_post_count_increment'=>'BINARY',
			'f_intro_question'=>'LONG_TRANS__COMCODE',
			'f_intro_answer'=>'SHORT_TEXT',	// Comcode
			'f_cache_num_topics'=>'INTEGER',
			'f_cache_num_posts'=>'INTEGER',
			'f_cache_last_topic_id'=>'?AUTO_LINK',
			'f_cache_last_title'=>'SHORT_TEXT',
			'f_cache_last_time'=>'?TIME',
			'f_cache_last_username'=>'SHORT_TEXT',
			'f_cache_last_member_id'=>'?MEMBER',
			'f_cache_last_forum_id'=>'?AUTO_LINK',
			'f_redirection'=>'SHORT_TEXT',
			'f_order'=>'ID_TEXT',
			'f_is_threaded'=>'BINARY',
		));
		$GLOBALS['FORUM_DB']->create_index('f_forums','cache_num_posts',array('f_cache_num_posts')); // Used to find active forums
		$GLOBALS['FORUM_DB']->create_index('f_forums','subforum_parenting',array('f_parent_forum'));
		$GLOBALS['FORUM_DB']->create_index('f_forums','findnamedforum',array('f_name'));
		$GLOBALS['FORUM_DB']->create_index('f_forums','f_position',array('f_position'));
		$typical_access=array($guest_group=>4,$administrator_group=>5,$super_moderator_group=>5,$probation_group=>2,$super_member_group=>4,$member_group_0=>4,$member_group_1=>4,$member_group_2=>4,$member_group_3=>4,$member_group_4=>4);
		$staff_post_access=array($guest_group=>1,$administrator_group=>5,$super_moderator_group=>5,$probation_group=>1,$super_member_group=>2,$member_group_0=>1,$member_group_1=>1,$member_group_2=>1,$member_group_3=>1,$member_group_4=>1);
		$staff_access=array($administrator_group=>5,$super_moderator_group=>5);
		$root_forum=ocf_make_forum(do_lang('ROOT_FORUM'),'',NULL,$staff_post_access,NULL);
		ocf_make_forum(do_lang('DEFAULT_FORUM_TITLE'),'',$forum_grouping_id,$typical_access,$root_forum);
		ocf_make_forum(do_lang('REPORTED_POSTS_FORUM'),'',$forum_grouping_id_staff,$staff_access,$root_forum);
		$trash_forum_id=ocf_make_forum(do_lang('TRASH'),'',$forum_grouping_id_staff,$staff_access,$root_forum);
		ocf_make_forum(do_lang('COMMENT_FORUM_NAME'),'',$forum_grouping_id,$typical_access,$root_forum,1,1,0,'','','','last_post',1);
		if (addon_installed('tickets'))
		{
			require_lang('tickets');
			ocf_make_forum(do_lang('TICKET_FORUM_NAME'),'',$forum_grouping_id_staff,$staff_access,$root_forum);
		}
		$staff_forum_id=ocf_make_forum(do_lang('STAFF'),'',$forum_grouping_id_staff,$staff_access,$root_forum);

		$GLOBALS['FORUM_DB']->create_table('f_topics',array(
			'id'=>'*AUTO',
			't_pinned'=>'BINARY',
			't_sunk'=>'BINARY',
			't_cascading'=>'BINARY', // Cascades to deeper forums, as an announcement
			't_forum_id'=>'?AUTO_LINK', // Null if it's a Private Topic
			't_pt_from'=>'?MEMBER',
			't_pt_to'=>'?MEMBER',
			't_pt_from_category'=>'SHORT_TEXT',
			't_pt_to_category'=>'SHORT_TEXT',
			't_description'=>'SHORT_TEXT',
			't_description_link'=>'SHORT_TEXT',
			't_emoticon'=>'SHORT_TEXT',
			't_num_views'=>'INTEGER',
			't_validated'=>'BINARY',
			't_is_open'=>'BINARY',
			't_poll_id'=>'?AUTO_LINK',
			't_cache_first_post_id'=>'?AUTO_LINK',
			't_cache_first_time'=>'?TIME',
			't_cache_first_title'=>'SHORT_TEXT',
			't_cache_first_post'=>'?LONG_TRANS__COMCODE', // Careful, can't use this if !multi_lang_content()
			't_cache_first_username'=>'ID_TEXT',
			't_cache_first_member_id'=>'?MEMBER',
			't_cache_last_post_id'=>'?AUTO_LINK',
			't_cache_last_time'=>'?TIME',
			't_cache_last_title'=>'SHORT_TEXT',
			't_cache_last_username'=>'ID_TEXT',
			't_cache_last_member_id'=>'?MEMBER',
			't_cache_num_posts'=>'INTEGER',
		));
		$GLOBALS['FORUM_DB']->create_index('f_topics','t_num_views',array('t_num_views'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','t_pt_to',array('t_pt_to'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','t_pt_from',array('t_pt_from'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','t_validated',array('t_validated'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','in_forum',array('t_forum_id'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','topic_order_time',array('t_cache_last_time'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','topic_order_time_2',array('t_cache_first_time'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','#t_description',array('t_description'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','descriptionsearch',array('t_description'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','forumlayer',array('t_cache_first_title'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','t_cascading',array('t_cascading'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','t_cascading_or_forum',array('t_cascading','t_forum_id'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','topic_order',array('t_cascading','t_pinned','t_cache_last_time')); // Ordering for forumview, is picked up over topic_order_3 for just the ordering bit (it seems)
		$GLOBALS['FORUM_DB']->create_index('f_topics','topic_order_2',array('t_forum_id','t_cascading','t_pinned','t_sunk','t_cache_last_time')); // Total index for forumview, including ordering. Doesn't work on current MySQL.
		$GLOBALS['FORUM_DB']->create_index('f_topics','topic_order_3',array('t_forum_id','t_cascading','t_pinned','t_cache_last_time')); // Total index for forumview, including ordering. Works if disable_sunk is turned on.
		$GLOBALS['FORUM_DB']->create_index('f_topics','ownedtopics',array('t_cache_first_member_id'));
		$GLOBALS['FORUM_DB']->create_index('f_topics','unread_forums',array('t_forum_id','t_cache_last_time'));

		// Welcome topic
		$topic_id=ocf_make_topic($staff_forum_id,'','',1,1,0,0,0,NULL,NULL,false);

		$GLOBALS['FORUM_DB']->create_table('f_posts',array(
			'id'=>'*AUTO',
			'p_title'=>'SHORT_TEXT',
			'p_post'=>'LONG_TRANS__COMCODE',
			'p_ip_address'=>'IP',
			'p_time'=>'TIME',
			'p_poster'=>'MEMBER',
			'p_intended_solely_for'=>'?MEMBER',
			'p_poster_name_if_guest'=>'ID_TEXT',
			'p_validated'=>'BINARY',
			'p_topic_id'=>'AUTO_LINK',
			'p_cache_forum_id'=>'?AUTO_LINK', // Null if for a PT
			'p_last_edit_time'=>'?TIME',
			'p_last_edit_by'=>'?MEMBER',
			'p_is_emphasised'=>'BINARY',
			'p_skip_sig'=>'BINARY',
			'p_parent_id'=>'?AUTO_LINK'
		));
		$GLOBALS['FORUM_DB']->create_index('f_posts','p_validated',array('p_validated'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','in_topic',array('p_topic_id','p_time','id'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','post_order_time',array('p_time','id'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','posts_since',array('p_time','p_cache_forum_id')); // p_cache_forum_id is used to not count PT posts
		$GLOBALS['FORUM_DB']->create_index('f_posts','p_last_edit_time',array('p_last_edit_time'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','posts_by',array('p_poster'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','find_pp',array('p_intended_solely_for'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','search_join',array('p_post'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','postsinforum',array('p_cache_forum_id'));
		$GLOBALS['FORUM_DB']->create_index('f_posts','deletebyip',array('p_ip_address'));

		$GLOBALS['FORUM_DB']->create_table('f_special_pt_access',array(
			's_member_id'=>'*MEMBER',
			's_topic_id'=>'*AUTO_LINK',
		));

		$GLOBALS['FORUM_DB']->create_table('f_saved_warnings',array(
			's_title'=>'*SHORT_TEXT',
			's_explanation'=>'LONG_TEXT',
			's_message'=>'LONG_TEXT',
		));

		$GLOBALS['FORUM_DB']->create_table('f_post_history',array(
			'id'=>'*AUTO',
			'h_create_date_and_time'=>'TIME',
			'h_action_date_and_time'=>'TIME',
			'h_owner_member_id'=>'MEMBER',
			'h_alterer_member_id'=>'MEMBER',
			'h_post_id'=>'AUTO_LINK',
			'h_topic_id'=>'AUTO_LINK',
			'h_before'=>'LONG_TEXT',
			'h_action'=>'ID_TEXT'
		));
		$GLOBALS['FORUM_DB']->create_index('f_post_history','phistorylookup',array('h_post_id'));

		$GLOBALS['FORUM_DB']->create_table('f_forum_intro_ip',array(
			'i_forum_id'=>'*AUTO_LINK',
			'i_ip'=>'*IP'
		));

		$GLOBALS['FORUM_DB']->create_table('f_forum_intro_member',array(
			'i_forum_id'=>'*AUTO_LINK',
			'i_member_id'=>'*MEMBER'
		));

		$GLOBALS['FORUM_DB']->create_table('f_post_templates',array(
			'id'=>'*AUTO',
			't_title'=>'SHORT_TEXT',
			't_text'=>'LONG_TEXT',
			't_forum_multi_code'=>'SHORT_TEXT',
			't_use_default_forums'=>'BINARY'
		));
		require_lang('ocf_post_templates');
		ocf_make_post_template(do_lang('DEFAULT_POST_TEMPLATE_bug_title'),do_lang('DEFAULT_POST_TEMPLATE_bug_text'),'',0);
		ocf_make_post_template(do_lang('DEFAULT_POST_TEMPLATE_task_title'),do_lang('DEFAULT_POST_TEMPLATE_task_text'),'',0);
		ocf_make_post_template(do_lang('DEFAULT_POST_TEMPLATE_fault_title'),do_lang('DEFAULT_POST_TEMPLATE_fault_text'),'',0);

		$GLOBALS['FORUM_DB']->create_index('f_posts','#p_title',array('p_title'));

		$GLOBALS['FORUM_DB']->create_table('f_polls',array(
			'id'=>'*AUTO',
			'po_question'=>'SHORT_TEXT',
			'po_cache_total_votes'=>'INTEGER',
			'po_is_private'=>'BINARY',
			'po_is_open'=>'BINARY',
			'po_minimum_selections'=>'INTEGER',
			'po_maximum_selections'=>'INTEGER',
			'po_requires_reply'=>'BINARY'
		));

		$GLOBALS['FORUM_DB']->create_table('f_poll_answers',array(
			'id'=>'*AUTO',
			'pa_poll_id'=>'AUTO_LINK',
			'pa_answer'=>'SHORT_TEXT',
			'pa_cache_num_votes'=>'INTEGER'
		));

		$GLOBALS['FORUM_DB']->create_table('f_poll_votes',array(
			'id'=>'*AUTO',
			'pv_poll_id'=>'AUTO_LINK',
			'pv_member_id'=>'MEMBER',
			'pv_answer_id'=>'AUTO_LINK' // -1 means "forfeited". We'd use NULL, but we aren't allowed NULL fragments in keys
		));

		$GLOBALS['FORUM_DB']->create_table('f_multi_moderations',array(
			'id'=>'*AUTO',
			'mm_name'=>'*SHORT_TRANS',
			'mm_post_text'=>'LONG_TEXT',	// Comcode
			'mm_move_to'=>'?AUTO_LINK',
			'mm_pin_state'=>'?BINARY',
			'mm_sink_state'=>'?BINARY',
			'mm_open_state'=>'?BINARY',
			'mm_forum_multi_code'=>'SHORT_TEXT',
			'mm_title_suffix'=>'SHORT_TEXT'
		));
		ocf_make_multi_moderation(do_lang('TRASH'),'',$trash_forum_id,0,0,0);

		$GLOBALS['FORUM_DB']->create_table('f_warnings',array(
			'id'=>'*AUTO',
			'w_member_id'=>'MEMBER',
			'w_time'=>'TIME',
			'w_explanation'=>'LONG_TEXT',
			'w_by'=>'MEMBER',
			'w_is_warning'=>'BINARY',
			'p_silence_from_topic'=>'?AUTO_LINK',
			'p_silence_from_forum'=>'?AUTO_LINK',
			'p_probation'=>'INTEGER',
			'p_banned_ip'=>'IP',
			'p_charged_points'=>'INTEGER',
			'p_banned_member'=>'BINARY',
			'p_changed_usergroup_from'=>'?GROUP',
		));
		$GLOBALS['FORUM_DB']->create_index('f_warnings','warningsmemberid',array('w_member_id'));

		$GLOBALS['FORUM_DB']->create_table('f_moderator_logs',array(
			'id'=>'*AUTO',
			'l_the_type'=>'ID_TEXT', // Language identifier
			'l_param_a'=>'SHORT_TEXT',
			'l_param_b'=>'SHORT_TEXT',
			'l_date_and_time'=>'TIME',
			'l_reason'=>'LONG_TEXT',
			'l_by'=>'MEMBER'
		));

		$GLOBALS['FORUM_DB']->create_table('f_member_known_login_ips',array(
			'i_member_id'=>'*MEMBER',
			'i_ip'=>'*IP',
			'i_val_code'=>'SHORT_TEXT'
		));

		// NB: post_param's will return default's if OCF is being installed but not used yet (e.g. IPB forum driver chosen at installation)
		// Make guest
		ocf_make_member(do_lang('GUEST'),'','',NULL,NULL,NULL,NULL,array(),NULL,$guest_group,1,time(),time(),'',NULL,'',0,1,1,'','','',1,0,'',1,1,NULL,'',false);
		// Make admin user
		ocf_make_member(post_param('admin_username','admin'),post_param('ocf_admin_password','admin'),'',NULL,NULL,NULL,NULL,array(),NULL,$administrator_group,1,time(),time(),'','themes/default/images/ocf_default_avatars/default_set/cool_flare.png','',0,0,1,'','','',1,1,'',1,1,NULL,'',false);
		// Make test user
		ocf_make_member('test',post_param('ocf_admin_password','admin'),'',NULL,NULL,NULL,NULL,array(),NULL,$member_group_0,1,time(),time(),'',NULL,'',0,0,1,'','','',1,0,'',1,1,NULL,'',false);

		$GLOBALS['FORUM_DB']->create_table('f_read_logs',array(
			'l_member_id'=>'*MEMBER',
			'l_topic_id'=>'*AUTO_LINK',
			'l_time'=>'TIME'
		));
		$GLOBALS['FORUM_DB']->create_index('f_read_logs','erase_old_read_logs',array('l_time'));

		ocf_make_post($topic_id,do_lang('DEFAULT_POST_TITLE'),do_lang('DEFAULT_POST_CONTENT'),0,true,1,0,do_lang('SYSTEM'),'127.0.0.1',time(),$GLOBALS['OCF_DRIVER']->get_guest_id(),NULL,NULL,NULL,false,true);

		// Add privileges
		global $OCF_TRUE_PERMISSIONS,$OCF_FALSE_PERMISSIONS;
		foreach ($OCF_TRUE_PERMISSIONS as $permission)
		{
			add_privilege('FORUMS_AND_MEMBERS',$permission,true);
		}
		foreach ($OCF_FALSE_PERMISSIONS as $permission)
		{
			add_privilege('FORUMS_AND_MEMBERS',$permission,false,($permission=='view_other_pt'));
		}
	}

	if ((is_null($upgrade_from)) || ($upgrade_from<10.0))
	{
		$GLOBALS['FORUM_DB']->create_index('f_members','last_visit_time',array('m_dob_month','m_dob_day','m_last_visit_time'));
	}
}


