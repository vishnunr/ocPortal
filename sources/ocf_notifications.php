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
 * Standard code module initialisation function.
 */
function init__ocf_notifications()
{
	global $PP_ROWS;
	$PP_ROWS=NULL;
}

/**
 * Get the personal post rows for the current member.
 *
 * @param  integer	The maximum number of rows to get (gets newest first).
 * @return array		The personal post rows (with corresponding topic details).
 */
function ocf_get_pp_rows($limit=5)
{
	global $PP_ROWS;
	if (!is_null($PP_ROWS)) return $PP_ROWS;

	$member_id=get_member();

//	return $GLOBALS['FORUM_DB']->query_select('f_topics t LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON p.p_topic_id=t.id',array('*'),NULL,'',1); // For testing
	$query='';
	global $SITE_INFO;
	if (((isset($SITE_INFO['mysql_old'])) && ($SITE_INFO['mysql_old']=='1')) || ((!isset($SITE_INFO['mysql_old'])) && (is_file(get_file_base().'/mysql_old'))))
	{
		$query.='SELECT t.*,l.*,p.*,p.id AS p_id,t.id as t_id FROM
		'.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t
		LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON ( t.id=l_topic_id AND l_member_id ='.strval($member_id).' )
		JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON (p.id=t.t_cache_last_post_id OR p_intended_solely_for ='.strval($member_id).')
		WHERE
		t_cache_last_time > '.strval(time()-60*60*24*intval(get_option('post_history_days'))).' AND
		(t_pt_from ='.strval($member_id).' OR t_pt_to ='.strval($member_id).' OR p_intended_solely_for ='.strval($member_id).')
		AND (l_time IS NULL OR l_time < p_time)
		'.(can_arbitrary_groupby()?' GROUP BY t.id':'');
	} else
	{
		$query.='SELECT t.*,l.*,p.*,p.id AS p_id,t.id as t_id FROM
		'.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t
		LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON ( t.id=l_topic_id AND l_member_id ='.strval($member_id).' )
		JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON (p.id=t.t_cache_last_post_id OR p_topic_id=t.id AND p_intended_solely_for ='.strval($member_id).')
		WHERE
		t_cache_last_time > '.strval(time()-60*60*24*intval(get_option('post_history_days'))).' AND
		t_pt_from ='.strval($member_id).'
		AND (l_time IS NULL OR l_time < p_time)
		'.(can_arbitrary_groupby()?' GROUP BY t.id':'');

		$query.=' UNION ';

		$query.='SELECT t.*,l.*,p.*,p.id AS p_id,t.id as t_id FROM
		'.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t
		LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON ( t.id=l_topic_id AND l_member_id ='.strval($member_id).' )
		JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON (p.id=t.t_cache_last_post_id OR p_topic_id=t.id AND p_intended_solely_for ='.strval($member_id).')
		WHERE
		t_cache_last_time > '.strval(time()-60*60*24*intval(get_option('post_history_days'))).' AND
		t_pt_to ='.strval($member_id).'
		AND (l_time IS NULL OR l_time < p_time)
		'.(can_arbitrary_groupby()?' GROUP BY t.id':'');

		$query.=' UNION ';

		$query.='SELECT t.*,l.*,p.*,p.id AS p_id,t.id as t_id FROM
		'.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t
		LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON ( t.id=l_topic_id AND l_member_id ='.strval($member_id).' )
		JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON (p.id=t.t_cache_last_post_id OR p_topic_id=t.id AND p_intended_solely_for ='.strval($member_id).')
		WHERE
		t_cache_last_time > '.strval(time()-60*60*24*intval(get_option('post_history_days'))).' AND
		p_intended_solely_for ='.strval($member_id).'
		AND (l_time IS NULL OR l_time < p_time)
		'.(can_arbitrary_groupby()?' GROUP BY t.id':'');

		$query.=' UNION ';

		$query.='SELECT t.*,l.*,p.*,p.id AS p_id,t.id as t_id FROM
		'.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t
		LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_special_pt_access i ON (i.s_topic_id=t.id)
		LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON ( t.id=l_topic_id AND l_member_id ='.strval($member_id).' )
		JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON (p.id=t.t_cache_last_post_id OR p_topic_id=t.id AND p_intended_solely_for ='.strval($member_id).')
		WHERE
		t_cache_last_time > '.strval(time()-60*60*24*intval(get_option('post_history_days'))).' AND
		i.s_member_id ='.strval($member_id).'
		AND (l_time IS NULL OR l_time < p_time)
		'.(can_arbitrary_groupby()?' GROUP BY t.id':'');
	}

	$query.=' ORDER BY t_cache_last_time DESC';

	$PP_ROWS=$GLOBALS['FORUM_DB']->query($query,$limit,NULL,false,true);

	$PP_ROWS=remove_duplicate_rows($PP_ROWS,'t_id');

	return $PP_ROWS;
}

/**
 * Calculate OCF notifications and render.
 *
 * @param  MEMBER		Member to look up for.
 * @return array		A pair: Number of notifications, Rendered notifications.
 */
function generate_notifications($member_id)
{
	$cache_identifier=serialize(array($member_id));

	static $notifications_cache=NULL;
	if (isset($notifications_cache[$cache_identifier])) return $notifications_cache[$cache_identifier];

	$notifications=mixed();
	if (((get_option('is_on_block_cache')=='1') || (get_param_integer('keep_cache',0)==1) || (get_param_integer('cache',0)==1)) && ((get_param_integer('keep_cache',NULL)!==0) && (get_param_integer('cache',NULL)!==0)))
	{
		$_notifications=get_cache_entry('_new_pp',$cache_identifier,10000);

		if (!is_null($_notifications))
		{
			list($__notifications,$num_unread_pps)=$_notifications;
			$notifications=new ocp_tempcode();
			if (!$notifications->from_assembly($__notifications,true))
			{
				$notifications=NULL;
			}
		}
	}

	if (is_null($notifications))
	{
		$nql_backup=$GLOBALS['NO_QUERY_LIMIT'];
		$GLOBALS['NO_QUERY_LIMIT']=true;

		$unread_pps=ocf_get_pp_rows();
		$notifications=new ocp_tempcode();
		$num_unread_pps=0;
		foreach ($unread_pps as $unread_pp)
		{
			$by_id=(is_null($unread_pp['t_cache_first_member_id']) || !is_null($unread_pp['t_forum_id']))?$unread_pp['p_poster']:$unread_pp['t_cache_first_member_id'];
			$by=is_guest($by_id)?do_lang('SYSTEM'):$GLOBALS['OCF_DRIVER']->get_username($by_id);
			if (is_null($by)) $by=do_lang('UNKNOWN');
			$u_title=$unread_pp['t_cache_first_title'];
			if (is_null($unread_pp['t_forum_id']))
			{
				$type=do_lang_tempcode(($unread_pp['t_cache_first_post_id']==$unread_pp['id'])?'NEW_PT_NOTIFICATION':'NEW_PP_NOTIFICATION');
				$num_unread_pps++;
				$reply_url=build_url(array('page'=>'topics','type'=>'new_post','id'=>$unread_pp['p_topic_id'],'quote'=>$unread_pp['id']),get_module_zone('topics'));

				$additional_posts=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) AS cnt FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_topic_id='.strval($unread_pp['p_topic_id']).' AND id>'.strval($unread_pp['id']));
			} else
			{
				$type=do_lang_tempcode('ADD_INLINE_PERSONAL_POST');
				if ($unread_pp['p_title']!='') $u_title=$unread_pp['p_title'];
				$reply_url=build_url(array('page'=>'topics','type'=>'new_post','id'=>$unread_pp['p_topic_id'],'quote'=>$unread_pp['id'],'intended_solely_for'=>$unread_pp['p_poster']),get_module_zone('topics'));

				$additional_posts=0;
			}
			$time_raw=$unread_pp['p_time'];
			$time=get_timezoned_date($unread_pp['p_time']);
			$topic_url=$GLOBALS['OCF_DRIVER']->post_url($unread_pp['id'],NULL,true);
			$post=get_translated_tempcode($unread_pp['p_post'],$GLOBALS['FORUM_DB']);
			$description=$unread_pp['t_description'];
			if ($description!='') $description=' ('.$description.')';
			$profile_link=is_guest($by_id)?new ocp_tempcode():$GLOBALS['OCF_DRIVER']->member_profile_url($by_id,false,true);
			$redirect=get_self_url(true,true);
			$ignore_url=build_url(array('page'=>'topics','type'=>'mark_read_topic','id'=>$unread_pp['p_topic_id'],'redirect'=>$redirect),get_module_zone('topics'));
			$ignore_url_2=build_url(array('page'=>'topics','type'=>'mark_read_topic','id'=>$unread_pp['p_topic_id'],'redirect'=>$redirect,'ajax'=>1),get_module_zone('topics'));
			require_javascript('javascript_ajax');
			$notifications->attach(do_template('OCF_NOTIFICATION',array('_GUID'=>'3b224ea3f4da2f8f869a505b9756970a','ADDITIONAL_POSTS'=>integer_format($additional_posts),'_ADDITIONAL_POSTS'=>strval($additional_posts),'ID'=>strval($unread_pp['id']),'U_TITLE'=>$u_title,'IGNORE_URL'=>$ignore_url,'IGNORE_URL_2'=>$ignore_url_2,'REPLY_URL'=>$reply_url,'TOPIC_URL'=>$topic_url,'POST'=>$post,'DESCRIPTION'=>$description,'TIME'=>$time,'TIME_RAW'=>strval($time_raw),'BY'=>$by,'PROFILE_URL'=>$profile_link,'TYPE'=>$type)));
		}

		require_code('caches2');
		put_into_cache('_new_pp',60*60*24,$cache_identifier,array($notifications->to_assembly(),$num_unread_pps));

		$GLOBALS['NO_QUERY_LIMIT']=$nql_backup;
	}

	$notifications_cache[$cache_identifier]=array($notifications,$num_unread_pps);
	return array($notifications,$num_unread_pps);
}
