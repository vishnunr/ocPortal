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
 * @package 	import
 */

/**
 * Standard code module initialisation function.
 */
function init__hooks__modules__admin_import__aef()
{
	global $TOPIC_FORUM_CACHE;
	$TOPIC_FORUM_CACHE=array();

	global $STRICT_FILE;
	$STRICT_FILE=false; // Disable this for a quicker import that is quite liable to go wrong if you don't have the files in the right place

	global $OLD_BASE_URL;
	$OLD_BASE_URL=NULL;
}

/**
 * Forum Driver.
 */
class Hook_aef
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['supports_advanced_import']=false;
		$info['product']='Advanced Electron Forum (AEF) 1.0.6';
		$info['prefix']='aef_';
		$info['import']=array(
								'config',
								'ocf_groups',
								'ocf_members',
								'ocf_member_files',
								'ip_bans',
								'ocf_forum_groupings',
								'ocf_forums',
								'ocf_topics',
								'ocf_posts',
								'ocf_private_topics',
								'ocf_post_files',
								'ocf_polls_and_votes',
								'notifications',
								'wordfilter',
								'authors',
								'news_and_categories',
							);

		$info['dependencies']=array( // This dependency tree is overdefined, but I wanted to make it clear what depends on what, rather than having a simplified version
								'ocf_members'=>array('ocf_groups'),
								'ocf_member_files'=>array('ocf_members'),
								'ocf_forums'=>array('ocf_forum_groupings','ocf_members','ocf_groups'),
								'ocf_topics'=>array('ocf_forums','ocf_members'),
								'ocf_polls_and_votes'=>array('ocf_topics','ocf_members'),
								'ocf_posts'=>array('ocf_topics','ocf_members'),
								'ocf_private_topics'=>array('ocf_members'),
								'ocf_post_files'=>array('ocf_posts','ocf_private_topics'),
								'notifications'=>array('ocf_topics','ocf_members'),
								'authors'=>array('ocf_topics','ocf_members'),
								'news_and_categories'=>array('authors','ocf_topics','ocf_members'),
							);
		$_cleanup_url=build_url(array('page'=>'admin_cleanup'),get_module_zone('admin_cleanup'));
		$cleanup_url=$_cleanup_url->evaluate();
		$info['message']=(get_param('type','misc')!='import' && get_param('type','misc')!='hook')?new ocp_tempcode():do_lang_tempcode('FORUM_CACHE_CLEAR',escape_html($cleanup_url));

		return $info;
	}

	/**
	 * Probe a file path for DB access details.
	 *
	 * @param  string			The probe path
	 * @return array			A quartet of the details (db_name, db_user, db_pass, table_prefix)
	 */
	function probe_db_access($file_base)
	{
		$globals=array();
		if (!file_exists($file_base.'/universal.php'))
			warn_exit(do_lang_tempcode('BAD_IMPORT_PATH','universal.php'));
		require($file_base.'/universal.php');
		$INFO=array();
		$INFO['sql_database']=$globals['database'];
		$INFO['sql_user']=$globals['user'];
		$INFO['sql_pass']=$globals['password'];
		$INFO['sql_tbl_prefix']=$globals['dbprefix'];

		return array($INFO['sql_database'],$INFO['sql_user'],$INFO['sql_pass'],$INFO['sql_tbl_prefix']);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_config($db,$table_prefix,$file_base)
	{
		$globals=array();

		require($file_base.'/universal.php');

		$rows=$db->query('SELECT * FROM '.$table_prefix.'registry WHERE '.db_string_equal_to('name','disableshoutingtopics').' OR '.db_string_equal_to('name','maxpostsintopics').' OR '.db_string_equal_to('name','maxtopics'));

		$config_remapping=array();

		foreach ($rows as $row)
		{
			if (isset($row['name'])&&$row['name']=='disableshoutingtopics')
			{
				$config_remapping['prevent_shouting']=$row['regval'];
				continue;
			}

			if (isset($row['name'])&&$row['name']=='maxpostsintopics')
			{
				$config_remapping['forum_posts_per_page']=$row['regval'];
				continue;
			}

			if (isset($row['name'])&&$row['name']=='maxtopics')
			{
				$config_remapping['forum_topics_per_page']=$row['regval'];
				continue;
			}
		}

		$config_remapping['site_name']=$globals['sn'];
		$config_remapping['staff_address']=$globals['board_email'];
		$config_remapping['gzip_output']=$globals['gzip'];

		$INFO=array();

		foreach ($config_remapping as $key=>$value)
		{
			set_option($key,$value);

			$INFO[$key]=$row;
		}

		$INFO['board_prefix']=$globals['url'];
		$INFO['user_cookie']=$globals['cookie_name'];
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_groups($db,$table_prefix,$file_base)
	{
		$globals=array();
		require($file_base.'/universal.php');

		//avatar dementions are set in av_width and av_height values from aef_registry db table
		$rows=$db->query('SELECT * FROM '.$table_prefix.'registry WHERE '.db_string_equal_to('name','av_width').' OR '.db_string_equal_to('name','av_height').' OR '.db_string_equal_to('name','usersiglen'));
		$INFO=array();
		foreach ($rows as $row)
		{
			$key=$row['name'];
			$val=$row['regval'];
			$INFO[$key]=$val;
		}

		$rows=$db->query('SELECT * FROM '.$table_prefix.'user_groups WHERE post_count=-1 ORDER BY member_group');

		foreach ($rows as $row)
		{
			if (import_check_if_imported('mem_gr_name',strval($row['member_group']))) continue;

			$is_super_admin=($row['mem_gr_name']=='Administrator')?1:0;
			$is_super_moderator=($row['mem_gr_name']=='Universal Moderator')?1:0;

			$id_new=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups g LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON g.g_name=t.id WHERE '.db_string_equal_to('text_original',$row['mem_gr_name']),'g.id');
			if (is_null($id_new))
			{
				$id_new=ocf_make_group($row['mem_gr_name'],0,$is_super_admin,$is_super_moderator,'','',NULL,NULL,NULL,5,0,5,5,$INFO['av_width'],$INFO['av_height'],30000,$INFO['usersiglen']);
			}

			// privileges
			set_privilege($id_new,'comcode_dangerous',true);

			$check_id_exists=$GLOBALS['FORUM_DB']->query_select_value_if_there('import_id_remap WHERE id_old='.strval($row['member_group']).' AND id_type=\'group\' AND id_session='.strval(get_session_id()), 'id_old');

			if (is_null($check_id_exists))
				import_id_remap_put('group',strval($row['member_group']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_members($db,$table_prefix,$file_base)
	{
		$default_group=get_first_default_group();

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'users u ORDER BY u.id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('member',strval($row['id']))) continue;

				$test=$GLOBALS['OCF_DRIVER']->get_member_from_username($row['username']);
				if (!is_null($test))
				{
					import_id_remap_put('member',strval($row['id']),$test);
					continue;
				}

				$language='';
				if ($row['language']!='')
				{
					switch ($language) // Can be extended as needed
					{
						case 'english':
						default:
							$language='EN';
							break;
					}
				}

				$primary_group=import_id_remap_get('group',strval($row['u_member_group']),true);
				if (is_null($primary_group)) $primary_group=$default_group;

				$secondary_groups=array();

				$custom_fields=array(
										ocf_make_boiler_custom_field('im_icq')=>$row['icq'],
										ocf_make_boiler_custom_field('im_aim')=>$row['aim'],
										ocf_make_boiler_custom_field('im_msn')=>$row['msn'],
										ocf_make_boiler_custom_field('im_yahoo')=>$row['yim'],
										ocf_make_boiler_custom_field('location')=>$row['location'],
									);
				if ($row['www']!='')
					$custom_fields[ocf_make_boiler_custom_field('website')]=(strlen($row['www'])>0)?('[url]'.$row['www'].'[/url]'):'';

				$signature=$this->fix_links($row['sig'],$db,$table_prefix,$file_base);
				$validated=$row['act_status'];

				$reveal_age=($row['birth_date']!='')?1:0;
				$bits=explode('-',$row['birth_date']);
				if (($reveal_age==1) && (count($bits)==3))
				{
					list($bday_day,$bday_month,$bday_year)=$bits;
				} else
				{
					list($bday_day,$bday_month,$bday_year)=array(0,0,0);
				}

				$views_signatures=1;
				$preview_posts=1;
				$track_posts=$row['pm_notify'];
				$title=$row['customtitle'];
				$title=@html_entity_decode($title,ENT_QUOTES,get_charset());

				// These are done in the members-files stage
				$avatar_url=$row['avatar'];
				$photo_url=$row['ppic'];
				$photo_thumb_url='';

				$password=$row['password'];
				$type='aef';
				$salt=$row['salt'];

				$id_new=ocf_make_member($row['username'],$password,$row['email'],NULL,$bday_day,$bday_month,$bday_year,$custom_fields,strval($row['timezone']),$primary_group,$validated,$row['r_time'],$row['lastlogin_1'],'',$avatar_url,$signature,($row['temp_ban']!=0)?1:0,$preview_posts,$reveal_age,$title,$photo_url,$photo_thumb_url,$views_signatures,$track_posts,$language,1,1,'','',false,$type,$salt,1);

				// Fix group leadership
				$GLOBALS['FORUM_DB']->query_update('f_groups',array('g_group_leader'=>$id_new),array('g_group_leader'=>-$row['id']));

				import_id_remap_put('member',strval($row['id']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_authors($db,$table_prefix,$old_base_dir)
	{
		require_code('authors');
		$rows=$db->query('SELECT uid FROM '.$table_prefix.'news');

		foreach ($rows as $row)
		{
			$author_data=$db->query('SELECT username, email FROM '.$table_prefix.'users WHERE id='.strval($row['uid']));

			$url=(isset($author_data[0]['email'])&&($author_data[0]['email']!=''))?'mailto:'.$author_data[0]['email']:'';
			$author_name=(isset($author_data[0]['username'])&&($author_data[0]['username']!=''))?$author_data[0]['username']:'';
			add_author($author_name,$url,NULL,'','');
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_news_and_categories($db,$table_prefix,$old_base_dir)
	{
		require_code('news');
		$com=0; // we cannot comment the news

		//get AEF news
		$rows=$db->query('SELECT * FROM '.$table_prefix.'news');

		foreach ($rows as $row)
		{
			$author_data=$db->query('SELECT username FROM '.$table_prefix.'users WHERE id='.strval($row['uid']));
			$author_name=(isset($author_data[0]['username'])&&($author_data[0]['username']!=''))?$author_data[0]['username']:'';

			$append='';
			$topic=db_get_first_id(); //there is no news topic/category specified in AEF

			ocf_over_msn(); //set the proper forum driver FORUM_DB
			$news=html_to_comcode($row['news']).$append;
			ocf_over_local(); //return the OCF FORUM_DB
			$new_id=add_news($row['title'],$row['news'],$author_name,$row['approved'],$com,1,1,'Full story: '.$row['fullstorylink'],$news,$topic,array(),$row['time'],get_member(),0,NULL,NULL,$row['image']);

		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_member_files($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;

		$options=$db->query('SELECT * FROM '.$table_prefix.'registry WHERE name LIKE \''.db_encode_like('%avatar%').'\'');
		$options_array=array();

		$avatar_path='';
		$avatar_gallery_path='';

		foreach ($options as $option)
		{
			$options_array[$option['name']]=$option['regval'];
			if ($option['name']=='uploadavatardir') $avatar_path=$option['regval'];
			if ($option['name']=='avatardir') $avatar_gallery_path=$option['regval'];
		}

		$row_start=0;
		$rows=array();
		do
		{
			$query='SELECT id,avatar,avatar_type,avatar_width,avatar_height FROM '.$table_prefix.'users WHERE id<>-1 ORDER BY id';
			$rows=$db->query($query,200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('member_files',strval($row['id']))) continue;

				$member_id=import_id_remap_get('member',strval($row['id']));

				$avatar_url='';
				switch ($row['avatar_type'])
				{
					case '0':
						break;
					case '1': // Gallery
						$filename=$row['avatar'];
						if ((file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$filename)) || (@rename($avatar_gallery_path.'/'.$filename,get_custom_file_base().'/uploads/ocf_avatars/'.$filename)))
						{
							$avatar_url='uploads/ocf_avatars/'.substr($filename,strrpos($filename,'/'));
							sync_file($avatar_url);
						} else
						{
							// Try as a pack avatar then
							$striped_filename=str_replace('/','_',$filename);
							if (file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$striped_filename))
							{
								$avatar_url='uploads/ocf_avatars/'.substr($filename,strrpos($filename,'/'));
							} else
							{
								if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_AVATAR',escape_html($filename)));
								$avatar_url='';
							}
						}
						break;

					case '2': // Remote
						$avatar_url=$row['avatar'];
						break;

					case '3': // Upload
						$filename=$row['avatar'];
						if ((file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$filename)) || (@rename($avatar_path.'/'.$filename,get_custom_file_base().'/uploads/ocf_avatars/'.$filename)))
						{
							$avatar_url='uploads/ocf_avatars/'.$filename;
							sync_file($avatar_url);
						} else
						{
							if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_AVATAR',escape_html($filename)));
							$avatar_url='';
						}
						break;


				}

				$GLOBALS['FORUM_DB']->query_update('f_members',array('m_avatar_url'=>$avatar_url),array('id'=>$member_id),'',1);

				import_id_remap_put('member_files',strval($row['id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ip_bans($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'users WHERE u_member_group=-3');

		require_code('failure');

		foreach ($rows as $row)
		{
			$ban_time=$row['temp_ban_time']; //when is banned user
			$ban_period=$row['temp_ban']; //how many days is banned
			$ban_till=$ban_time+$ban_period; //the user is banned till this date/time

			if ($ban_till < time()) continue;

			if (import_check_if_imported('ip_ban',strval($row['id']))) continue;

			add_ip_ban($row['r_ip']);

			import_id_remap_put('ip_ban',strval($row['id']),0);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_forum_groupings($db,$table_prefix,$old_base_dir)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'categories');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('category',strval($row['cid']))) continue;

			$title=$row['name'];
			$title=@html_entity_decode($title,ENT_QUOTES,get_charset());

			$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_groupings','id',array('c_title'=>$title));
			if (!is_null($test))
			{
				import_id_remap_put('category',strval($row['cid']),$test);
				continue;
			}

			$id_new=ocf_make_forum_grouping($title,'',1);

			import_id_remap_put('category',strval($row['cid']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_forums($db,$table_prefix,$old_base_dir)
	{
		require_code('ocf_forums_action2');

		$rows=$db->query('SELECT * FROM '.$table_prefix.'forums');
		foreach ($rows as $row)
		{
			$remapped=import_id_remap_get('forum',strval($row['fid']),true);
			if (!is_null($remapped))
			{
				continue;
			}

			$name=$row['fname'];
			ocf_over_msn(); //set the proper forum driver FORUM_DB
			$description=html_to_comcode($row['description']);
			ocf_over_local(); //return the OCF FORUM_DB

			$position=$row['forum_order'];
			$post_count_increment=1;

			$category_id=import_id_remap_get('category',strval($row['cat_id']),true);
			$parent_forum=db_get_first_id();

			$access_mapping=array();
			if ($row['status']==0)
			{
				$permissions=$db->query('SELECT * FROM '.$table_prefix.'forumpermissions WHERE fpfid='.strval((integer)$row['fid']));

				foreach ($permissions as $p)
				{
					$v=0;
					if ($p['can_post_topic']==1) $v=2;
					if ($p['can_reply']==1) $v=3;
					if ($p['can_post_polls']==1) $v=4;

					$group_id=import_id_remap_get('group',strval($p['fpugid']),true);
					if (is_null($group_id)) continue;
					$access_mapping[$group_id]=$v;
				}
			}

			$id_new=ocf_make_forum($name,$description,$category_id,$access_mapping,$parent_forum,$position,$post_count_increment,0,'');

			import_id_remap_put('forum',strval($row['fid']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_topics($db,$table_prefix,$file_base)
	{
		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'topics WHERE t_status=1 ORDER BY tid',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic',strval($row['tid']))) continue;

				$forum_id=import_id_remap_get('forum',strval($row['t_bid']));

				$id_new=ocf_make_topic($forum_id,$row['topic'],'',1,($row['t_status']==1)?0:1,0,0,0,NULL,NULL,false,$row['n_views']);

				import_id_remap_put('topic',strval($row['tid']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_posts($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'posts p ORDER BY p.pid',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('post',strval($row['pid']))) continue;

				$topic_id=import_id_remap_get('topic',strval($row['post_tid']),true);
				if (is_null($topic_id))
				{
					import_id_remap_put('post',strval($row['pid']),-1);
					continue;
				}
				$member_id=import_id_remap_get('member',strval($row['poster_id']),true);
				if (is_null($member_id)) $member_id=db_get_first_id();

				$forum_id=import_id_remap_get('forum',strval($row['post_fid']),true);

				$title='';
				$topics=$db->query('SELECT topic FROM '.$table_prefix.'topics WHERE tid='.strval((integer)$row['post_tid']));
				$first_post=$row['ptime'];
				if ($first_post)
				{
					$title=$topics[0]['topic'];
				}
				elseif (!is_null($row['post_title'])) $title=$row['post_title'];

				$title=@html_entity_decode($title,ENT_QUOTES,get_charset());

				$post=$this->fix_links($row['post'],$db,$table_prefix,$file_base);

				$last_edit_by=NULL;
				$last_edit_time=$row['modtime'];

				$post_username=$GLOBALS['OCF_DRIVER']->get_username($member_id);

				$id_new=ocf_make_post($topic_id,$title,$post,0,$first_post,1,0,$post_username,$row['poster_ip'],$row['ptime'],$member_id,NULL,$last_edit_time,$last_edit_by,false,false,$forum_id,false);

				import_id_remap_put('post',strval($row['pid']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array				The match
	 * @return  string			The substitution string
	 */
	function _fix_links_callback_topic($m)
	{
		return 'index.php?page=topicview&id='.strval(import_id_remap_get('topic',strval($m[2]),true));
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array			The match
	 * @return string			The substitution string
	 */
	function _fix_links_callback_post($m)
	{
		return 'index.php?page=topicview&type=findpost&id='.strval(import_id_remap_get('post',strval($m[2]),true));
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array				The match
	 * @return  string			The substitution string
	 */
	function _fix_links_callback_forum($m)
	{
		return 'index.php?page=forumview&id='.strval(import_id_remap_get('forum',strval($m[2]),true));
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array				The match
	 * @return  string			The substitution string
	 */
	function _fix_links_callback_member($m)
	{
		return 'index.php?page=members&type=view&id='.strval(import_id_remap_get('member',strval($m[2]),true));
	}

	/**
	 * Convert AEF URLs pasted in text fields into ocPortal ones.
	 *
	 * @param  string			The text field text (e.g. a post)
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 * @return string			The new text field text
	 */
	function fix_links($post,$db,$table_prefix,$file_base)
	{
		$globals=array();
		require($file_base.'/universal.php');

		$old_base_url=$globals['url'];

		$post=preg_replace_callback('#'.preg_quote($old_base_url).'/(index\.php\?tid=)(\d*)#',array($this,'_fix_links_callback_topic'),$post);
		$post=preg_replace_callback('#'.preg_quote($old_base_url).'/(index\.php\?fid=)(\d*)#',array($this,'_fix_links_callback_forum'),$post);
		$post=preg_replace_callback('#'.preg_quote($old_base_url).'/(index\.php\?mid=)(\d*)#',array($this,'_fix_links_callback_member'),$post);
		$post=preg_replace('#:[0-9a-f]{10}#','',$post);
		return $post;
	}

	/**
	 * Convert a AEF database file to an ocPortal uploaded file (stored on disk).
	 *
	 * @param  string			The file data
	 * @param  string			The optimal filename
	 * @param  ID_TEXT		The upload type (e.g. ocf_photos)
	 * @param  PATH			The base directory we are importing from
	 * @return array			Pair: The URL, the thumb url
	 */
	function data_to_disk($data,$filename,$sections,$file_base)
	{
		$globals=array();
		require($file_base.'/universal.php');
		$attachments_dir=$globals['server_url'].'/uploads/attachments/'; //forum attachments directory
		$file_path=$attachments_dir.$filename;
		$data=($data=='')?file_get_contents($file_path):$data;

		$filename=find_derivative_filename('uploads/'.$sections,$filename);
		$path=get_custom_file_base().'/uploads/'.$sections.'/'.$filename;
		$myfile=@fopen($path,'wb') OR warn_exit(do_lang_tempcode('WRITE_ERROR',escape_html('uploads/'.$sections.'/'.$filename)));
		if (fwrite($myfile,$data)<strlen($data)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
		fclose($myfile);
		fix_permissions($path);
		sync_file($path);

		$url='uploads/'.$sections.'/'.$filename;

		return array($url, $url);
	}

	/**
	 * Standard import function. Note that this is designed for a very popular phpBB mod, and will exit silently if the mod hasn't been installed.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_post_files($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;
		require_code('attachments2');
		require_code('attachments3');

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'attachments ORDER BY atid',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('post_files',strval($row['atid']))) continue;

				$post_id=import_id_remap_get('post',strval($row['at_pid']));

				$post_row=$GLOBALS['FORUM_DB']->query_select('f_posts p LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON p.p_post=t.id',array('p_time','text_original','p_poster','p_post'),array('p.id'=>$post_id),'',1);
				if (!array_key_exists(0,$post_row))
				{
					import_id_remap_put('post_files',strval($row['atid']),1);
					continue; // Orphaned post
				}
				$post=$post_row[0]['text_original'];
				$lang_id=$post_row[0]['p_post'];
				$member_id=$post_row[0]['p_poster'];

				list($url,$thumb_url)=$this->data_to_disk('',$row['at_file'],'attachments',$file_base);
				$a_id=$GLOBALS['SITE_DB']->query_insert('attachments',array('a_member_id'=>$member_id,'a_file_size'=>$row['at_size'],'a_url'=>$url,'a_thumb_url'=>$thumb_url,'a_original_filename'=>$row['at_original_file'],'a_num_downloads'=>$row['at_downloads'],'a_last_downloaded_time'=>NULL,'a_add_time'=>$row['at_time'],'a_description'=>''),true);

				$GLOBALS['SITE_DB']->query_insert('attachment_refs',array('r_referer_type'=>'ocf_post','r_referer_id'=>strval($post_id),'a_id'=>$a_id));
				$post.="\n\n".'[attachment]'.strval($a_id).'[/attachment]';

				ocf_over_msn();
				update_lang_comcode_attachments($lang_id,$post,'ocf_post',strval($post_id));
				ocf_over_local();

				import_id_remap_put('post_files',strval($row['atid']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_polls_and_votes($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'polls');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('poll',strval($row['poid']))) continue;

			$topic_id=import_id_remap_get('topic',strval($row['poll_tid']),true);
			if (is_null($topic_id))
			{
				import_id_remap_put('poll',strval($row['pollid']),-1);
				continue;
			}

			$is_open=($row['poll_expiry']<time()&&$row['poll_expiry']!=0)?1:0;

			$rows2=$db->query('SELECT * FROM '.$table_prefix.'poll_options WHERE poo_poid='.strval((integer)$row['poid']).' ORDER BY pooid');
			$answers=array();
			$answer_map=array();
			foreach ($rows2 as $answer)
			{
				$answer_map[$answer['pooid']]=count($answers);
				$answers[]=$answer['poo_option'];
			}
			$maximum=count($answers);

			$rows2=$db->query('SELECT * FROM '.$table_prefix.'poll_voters WHERE pv_poid='.strval($row['poid']));
			foreach ($rows2 as $row2)
			{
				$row2['pv_mid']=import_id_remap_get('member',strval($row2['pv_mid']),true);
			}

			$id_new=ocf_make_poll($topic_id,$row['poll_qt'],0,$is_open,1,$maximum,0,$answers,false);

			$answers=collapse_1d_complexity('id',$GLOBALS['FORUM_DB']->query_select('f_poll_answers',array('id'),array('pa_poll_id'=>$id_new))); // Effectively, a remapping from IPB vote number to ocP vote number

			foreach ($rows2 as $row2)
			{
				$member_id=$row2['pv_mid'];
				if ((!is_null($member_id)) && ($member_id!=0))
				{
					if ($row2['pv_pooid']==0)
					{
						$answer=-1;
					} else
					{
						$answer=$answers[$answer_map[$row2['pv_pooid']]];
					}
					$GLOBALS['FORUM_DB']->query_insert('f_poll_votes',array('pv_poll_id'=>$id_new,'pv_member_id'=>$member_id,'pv_answer_id'=>$answer));
				}
			}

			import_id_remap_put('poll',strval($row['poid']),$id_new);
		}
	}


	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_private_topics($db,$table_prefix,$old_base_dir)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'pm p ORDER BY pm_time');

		// Group them up into what will become topics
		$groups=array();
		foreach ($rows as $row)
		{
			// Do some fiddling around for duplication
			if ($row['pm_from']>$row['pm_to'])
			{
				$a=$row['pm_to'];
				$b=$row['pm_from'];
			} else
			{
				$a=$row['pm_from'];
				$b=$row['pm_to'];
			}
			$row['pm_subject']=str_replace('Re: ','',$row['pm_subject']);
			$groups[strval($a).':'.strval($b).':'.$row['pm_subject']][]=$row;
		}

		// Import topics
		foreach ($groups as $group)
		{
			$row=$group[0];

			if (import_check_if_imported('pt',strval($row['pmid']))) continue;

			// Create topic
			$from_id=import_id_remap_get('member',strval($row['pm_from']),true);
			if (is_null($from_id)) $from_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
			$to_id=import_id_remap_get('member',strval($row['pm_to']),true);
			if (is_null($to_id)) $to_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
			$topic_id=ocf_make_topic(NULL,'','',1,1,0,0,0,$from_id,$to_id,false);

			$first_post=true;
			foreach ($group as $_post)
			{
				if ($first_post)
				{
					$title=$row['pm_subject'];
				} else $title='';

				$title=@html_entity_decode($title,ENT_QUOTES,get_charset());

				$post=$this->fix_links($_post['pm_body'],$db,$table_prefix,$old_base_dir);
				$validated=1;
				$from_id=import_id_remap_get('member',strval($_post['pm_from']),true);
				if (is_null($from_id)) $from_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
				$poster_name_if_guest=$GLOBALS['OCF_DRIVER']->get_username($from_id);
				$ip_address='';
				$time=$_post['pm_time'];
				$poster=$from_id;
				$last_edit_time=NULL;
				$last_edit_by=NULL;

				ocf_make_post($topic_id,$title,$post,0,$first_post,$validated,0,$poster_name_if_guest,$ip_address,$time,$poster,NULL,$last_edit_time,$last_edit_by,false,false,NULL,false);
				$first_post=false;
			}

			import_id_remap_put('pt',strval($row['pmid']),$topic_id);
		}
	}

	/**
	 * Convert a AEF topic icon code into a standard ocPortal theme image code.
	 *
	 * @param  integer		VB code
	 * @return ID_TEXT		ocPortal code
	 */
	function convert_topic_emoticon($iconid)
	{
		switch ($iconid)
		{
			case 1:
				return 'ocf_emoticons/smile';
			case 2:
				return 'ocf_emoticons/grin';
			case 4:
				return 'ocf_emoticons/shutup';
			case 5:
				return 'ocf_emoticons/cry';
			case 6:
				return 'ocf_emoticons/kiss';
			case 7:
				return 'ocf_emoticons/nerd';
			case 8:
				return 'ocf_emoticons/mellow';
			case 10:
				return 'ocf_emoticons/sad';
			case 11:
				return 'ocf_emoticons/dry';
			case 13:
				return 'ocf_emoticons/wink';
		}
		return '';
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_notifications($db,$table_prefix,$file_base)
	{
		require_code('notifications');

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'notify_topic',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic_notification',strval($row['notify_tid']).'-'.strval($row['notify_mid']))) continue;

				$member_id=import_id_remap_get('member',strval($row['notify_mid']),true);
				if (is_null($member_id)) continue;
				$topic_id=import_id_remap_get('topic',strval($row['notify_tid']),true);
				if (is_null($topic_id)) continue;
				enable_notifications('ocf_topic',strval($topic_id),$member_id);

				import_id_remap_put('topic_notification',strval($row['notify_tid']).'-'.strval($row['notify_mid']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_wordfilter($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'registry WHERE '.db_string_equal_to('name','censor_words_from').' OR '.db_string_equal_to('name','censor_words_to'));
		$censor_words_from=(isset($rows[0]['regval'])&&$rows[0]['regval']!='')?$rows[0]['regval']:'';
		$censor_words_to=(isset($rows[1]['regval'])&&$rows[1]['regval']!='')?$rows[1]['regval']:'';

		$censor_words_from_array=explode('|',$censor_words_from);
		$censor_words_to_array=explode('|',$censor_words_to);

		foreach ($censor_words_from_array as $key=>$row)
		{
			add_wordfilter_word($censor_words_from_array[$key],(isset($censor_words_to_array[$key])&&$censor_words_to_array[$key]!='')?$censor_words_to_array[$key]:'');
		}
	}

}


