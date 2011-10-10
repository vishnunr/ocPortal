<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

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
 * Module page class.
 */
class Module_members
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
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'MEMBERS'/*,'remote'=>'LEARN_ABOUT_REMOTE_LOGINS'*/);
	}

	/**
	 * Standard modular new-style deep page-link finder function (does not return the main entry-points).
	 *
	 * @param  string  	Callback function to send discovered page-links to.
	 * @param  MEMBER		The member we are finding stuff for (we only find what the member can view).
	 * @param  integer	Code for how deep we are tunnelling down, in terms of whether we are getting entries as well as categories.
	 * @param  string		Stub used to create page-links. This is passed in because we don't want to assume a zone or page name within this function.
	 */
	function get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub)
	{
		// Entries
		if ($depth>=DEPTH__ENTRIES)
		{
			$start=0;
			do
			{
				$groups=$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_username AS title','m_join_time'),NULL,'',500,$start);

				foreach ($groups as $row)
				{
					if ($row['id']!=db_get_first_id())
					{
						$pagelink=$pagelink_stub.'view:'.strval($row['id']);
						call_user_func_array($callback,array($pagelink,$pagelink_stub.'misc',$row['m_join_time'],NULL,0.2,$row['title'])); // Callback
					}
				}
				
				$start+=500;
			}
			while (array_key_exists(0,$groups));
		}
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_css('ocf');

		$type=get_param('type','misc');
	
		if ($type=='misc') return $this->directory();
		if ($type=='view') return $this->profile();
		//if ($type=='remote') return $this->remote();
	
		return new ocp_tempcode();
	}
	
	/**
	 * The UI to show info about remote logins.
	 *
	 * @return tempcode		The UI
	 */
	function remote()
	{
		$title=get_page_title('LEARN_ABOUT_REMOTE_LOGINS');
		
		if (get_option('allow_member_integration')=='off') warn_exit(do_lang_tempcode('NO_REMOTE_ON'));

		return do_template('FULL_MESSAGE_SCREEN',array('_GUID'=>'c0d5fa4f2b90e5d8e967763cca787636','TITLE'=>$title,'TEXT'=>do_lang_tempcode('DESCRIPTION_IS_REMOTE_MEMBER',ocp_srv('HTTP_HOST'))));
	}

	/**
	 * The UI to show the member directory.
	 *
	 * @return tempcode		The UI
	 */
	function directory()
	{
		require_javascript('javascript_ajax');
		require_javascript('javascript_ajax_people_lists');

		$title=get_page_title('MEMBERS');

		require_code('templates_internalise_screen');
		$test_tpl=internalise_own_screen($title);
		if (is_object($test_tpl)) return $test_tpl;

		$get_url=find_script('iframe');
		$hidden=build_keep_form_fields('_SELF',true,array('filter'));

		$start=get_param_integer('md_start',0);
		$max=get_param_integer('md_max',50);
		$sortables=array('m_username'=>do_lang_tempcode('USERNAME'),'m_primary_group'=>do_lang_tempcode('PRIMARY_GROUP'),'m_cache_num_posts'=>do_lang_tempcode('COUNT_POSTS'),'m_join_time'=>do_lang_tempcode('JOIN_DATE'));
		$default_sort_order=get_value('md_default_sort_order');
		if (is_null($default_sort_order))
			$default_sort_order='m_join_time DESC';
		$test=explode(' ',get_param('md_sort',$default_sort_order),2);
		if (count($test)==1) $test[]='ASC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='md_sort';

		$group_filter=get_param('group_filter','');

		$_usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,false,false,($group_filter=='')?NULL:array(intval($group_filter)));
		$usergroups=array();
		require_code('ocf_groups2');
		foreach ($_usergroups as $group_id=>$group)
		{
			$num=ocf_get_group_members_raw_count($group_id,true);
			$usergroups[$group_id]=array('USERGROUP'=>$group,'NUM'=>strval($num));
		}

		$query='FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE id<>'.strval(db_get_first_id());
		if (!has_specific_permission(get_member(),'see_unvalidated')) $query.=' AND m_validated=1';
		if ($group_filter!='')
		{
			if (is_numeric($group_filter))
				$title=get_page_title('USERGROUP',true,array($usergroups[intval($group_filter)]['USERGROUP']));

			require_code('ocfiltering');
			$filter=ocfilter_to_sqlfragment($group_filter,'m_primary_group','f_groups',NULL,'m_primary_group','id');
			$query.=' AND '.$filter;
		}
		$search=get_param('filter','');
		$sup=($search!='')?(' AND m_username LIKE \''.db_encode_like(str_replace('*','%',$search)).'\''):'';
		if ($sortable=='m_join_time')
		{
			$query.=$sup.' ORDER BY m_join_time '.$sort_order.','.'id '.$sort_order;
		} else
		{
			$query.=$sup.' ORDER BY '.$sortable.' '.$sort_order;
		}

		$max_rows=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) '.$query);
		$rows=$GLOBALS['FORUM_DB']->query('SELECT * '.$query,$max,$start);
		if (count($rows)==0)
		{
			return inform_screen($title,do_lang_tempcode('NO_RESULTS'));
		}
		$members=new ocp_tempcode();
		$member_boxes=array();
		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('USERNAME'),do_lang_tempcode('PRIMARY_GROUP'),do_lang_tempcode('COUNT_POSTS'),do_lang_tempcode('JOIN_DATE')),$sortables,'md_sort',$sortable.' '.$sort_order);
		require_code('ocf_members2');
		foreach ($rows as $row)
		{
			$link=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['id'],true,$row['m_username']);
			if ($row['m_validated']==0) $link->attach(do_lang_tempcode('MEMBER_IS_UNVALIDATED'));
			if ($row['m_validated_email_confirm_code']!='') $link->attach(do_lang_tempcode('MEMBER_IS_UNCONFIRMED'));
			$member_primary_group=ocf_get_member_primary_group($row['id']);
			$primary_group=ocf_get_group_link($member_primary_group);

			$members->attach(results_entry(array($link,$primary_group,integer_format($row['m_cache_num_posts']),escape_html(get_timezoned_date($row['m_join_time'])))));

			$member_boxes[]=ocf_show_member_box($row['id'],true);
		}
		$results_table=results_table(do_lang_tempcode('MEMBERS'),$start,'md_start',$max,'md_max',$max_rows,$fields_title,$members,$sortables,$sortable,$sort_order,'md_sort');

		$results_browser=results_browser(do_lang_tempcode('MEMBERS'),NULL,$start,'md_start',$max,'md_max',$max_rows,NULL,NULL,true,true);

		$symbols=NULL;
		if (get_option('allow_alpha_search')=='1')
		{
			$alpha_query=$GLOBALS['FORUM_DB']->query('SELECT m_username FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE id<>'.strval(db_get_first_id()).' ORDER BY m_username ASC');
			$symbols=array(array('START'=>'0','SYMBOL'=>do_lang('ALL')),array('START'=>'0','SYMBOL'=>'#'));
			foreach (array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z') as $s)
			{
				foreach ($alpha_query as $i=>$q)
				{
					if (strtolower(substr($q['m_username'],0,1))==$s)
					{
						break;
					}
				}
				if (substr(strtolower($q['m_username']),0,1)!=$s) $i=intval($symbols[count($symbols)-1]['START']);
				$symbols[]=array('START'=>strval(intval($max*floor(floatval($i)/floatval($max)))),'SYMBOL'=>$s);
			}
		}

		return do_template('OCF_MEMBER_DIRECTORY_SCREEN',array('_GUID'=>'096767e9aaabce9cb3e6591b7bcf95b8','MAX'=>strval($max),'RESULTS_BROWSER'=>$results_browser,'MEMBER_BOXES'=>$member_boxes,'USERGROUPS'=>$usergroups,'HIDDEN'=>$hidden,'SYMBOLS'=>$symbols,'SEARCH'=>$search,'GET_URL'=>$get_url,'TITLE'=>$title,'RESULTS_TABLE'=>$results_table));
	}

	/**
	 * The UI to show a member's profile.
	 *
	 * @return tempcode		The UI
	 */
	function profile()
	{
		require_javascript('javascript_profile');

		$username=get_param('id',strval(get_member()));
		if ($username=='') $username=strval(get_member());
		if (is_numeric($username))
		{
			$member_id=get_param_integer('id',get_member());
			$username=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_username');
			if ((is_null($username)) || (is_guest($member_id))) warn_exit(do_lang_tempcode('USER_NO_EXIST'));
		} else
		{
			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
			if (is_null($member_id)) warn_exit(do_lang_tempcode('_USER_NO_EXIST',escape_html($username)));
		}

		load_up_all_self_page_permissions(get_member());

		if (addon_installed('awards'))
		{
			require_code('awards');
			$awards=find_awards_for('member',strval($member_id));
		} else $awards=array();

		$title=get_page_title('MEMBER_PROFILE',true,array(escape_html($username)),NULL,$awards);

		$photo_url=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_photo_url');
		if (($photo_url!='') && (addon_installed('ocf_member_photos')) && (has_specific_permission(get_member(),'view_member_photos')))
		{
			require_code('images');
			$photo_thumb_url=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_photo_thumb_url');
			$photo_thumb_url=ensure_thumbnail($photo_url,$photo_thumb_url,(strpos($photo_url,'uploads/photos')!==false)?'photos':'ocf_photos','f_members',$member_id,'m_photo_thumb_url');
			if (url_is_local($photo_url))
			{
				$photo_url=get_complex_base_url($photo_url).'/'.$photo_url;
			}
			if (url_is_local($photo_thumb_url))
			{
				$photo_thumb_url=get_complex_base_url($photo_thumb_url).'/'.$photo_thumb_url;
			}
		} else
		{
			$photo_url='';
			$photo_thumb_url='';
		}

		$avatar_url=$GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id);

		// Things staff can do with this user
		$modules=array();
		if ((has_specific_permission(get_member(),'warn_member')) && (has_actual_page_access(get_member(),'warnings')) && (addon_installed('ocf_warnings')))
		{
			$redir_url=get_self_url(true);
			$modules[]=array('usage',do_lang_tempcode('WARN_MEMBER'),build_url(array('page'=>'warnings','type'=>'ad','id'=>$member_id,'redirect'=>$redir_url),get_module_zone('warnings')));
			$modules[]=array('usage',do_lang_tempcode('PUNITIVE_HISTORY'),build_url(array('page'=>'warnings','type'=>'history','id'=>$member_id),get_module_zone('warnings')));
		}
		if ((has_specific_permission(get_member(),'view_content_history')) && (has_actual_page_access(get_member(),'admin_ocf_history')))
			$modules[]=(!addon_installed('ocf_forum'))?NULL:array('usage',do_lang_tempcode('POST_HISTORY'),build_url(array('page'=>'admin_ocf_history','member_id'=>$member_id),'adminzone'));
		if (has_actual_page_access(get_member(),'admin_lookup'))
		{
			require_lang('submitban');
			$modules[]=array('usage',do_lang_tempcode('INVESTIGATE_USER'),build_url(array('page'=>'admin_lookup','param'=>$member_id),'adminzone'));
		}
		if (has_actual_page_access(get_member(),'admin_security'))
		{
			require_lang('security');
			$modules[]=array('usage',do_lang_tempcode('SECURITY_LOGGING'),build_url(array('page'=>'admin_security','member_id'=>$member_id),'adminzone'));
		}
		if (addon_installed('actionlog'))
		{
			if (has_actual_page_access(get_member(),'admin_actionlog'))
			{
				require_lang('submitban');
				$modules[]=array('usage',do_lang_tempcode('VIEW_ACTION_LOGS'),build_url(array('page'=>'admin_actionlog','type'=>'list','id'=>$member_id),'adminzone'));
			}
		}
		if ((has_actual_page_access(get_member(),'search')) && (addon_installed('ocf_forum')) && (addon_installed('search')))
			$modules[]=array('content',do_lang_tempcode('SEARCH_POSTS'),build_url(array('page'=>'search','type'=>'misc','id'=>'ocf_posts','author'=>$username,'sort'=>'add_date','direction'=>'DESC','content'=>''),get_module_zone('search')),'search');
		if ((get_member()==$member_id) || (has_specific_permission(get_member(),'member_maintenance')) || (has_specific_permission(get_member(),'assume_any_member')))
		{
			$modules=array_merge($modules,array(
								((!addon_installed('ocf_member_avatars')) || (!has_actual_page_access(get_member(),'editavatar')))?NULL:array('profile',do_lang_tempcode('EDIT_AVATAR'),build_url(array('page'=>'editavatar','type'=>'misc','id'=>$member_id),get_module_zone('editavatar')),'edit'),
								(!has_actual_page_access(get_member(),'editprofile'))?NULL:array('profile',do_lang_tempcode('EDIT_PROFILE'),build_url(array('page'=>'editprofile','type'=>'misc','id'=>$member_id),get_module_zone('editprofile')),'edit'),
								((!addon_installed('ocf_member_photos')) || (!has_actual_page_access(get_member(),'editphoto')))?NULL:array('profile',do_lang_tempcode('EDIT_PHOTO'),build_url(array('page'=>'editphoto','type'=>'misc','id'=>$member_id),get_module_zone('editphoto')),'edit'),
								((!addon_installed('ocf_signatures')) || (!has_actual_page_access(get_member(),'editsignature')))?NULL:array('profile',do_lang_tempcode('EDIT_SIGNATURE'),build_url(array('page'=>'editsignature','type'=>'misc','id'=>$member_id),get_module_zone('editsignature')),'edit'),
								((!addon_installed('ocf_member_titles')) || (!has_actual_page_access(get_member(),'edittitle')))?NULL:array('profile',do_lang_tempcode('EDIT_TITLE'),build_url(array('page'=>'edittitle','type'=>'misc','id'=>$member_id),get_module_zone('edittitle')),'edit'),
						));
		}
		if ((get_member()==$member_id) || (has_specific_permission(get_member(),'assume_any_member')))
		{
			$modules=array_merge($modules,array(
								array('views',do_lang_tempcode('_OCF_MEMBER_HOME'),build_url(array('page'=>'myhome','type'=>'misc','id'=>$member_id),get_module_zone('myhome'))),
								((has_specific_permission(get_member(),'delete_account')) && (has_actual_page_access(get_member(),'delete')))?array('profile',do_lang_tempcode('DELETE_MEMBER'),build_url(array('page'=>'delete','type'=>'misc','id'=>$member_id),get_module_zone('delete')),'delete'):NULL,
						));
		}
		if ((has_actual_page_access(get_member(),'search')) && (addon_installed('search')))
			$modules[]=array('content',do_lang_tempcode('SEARCH'),build_url(array('page'=>'search','type'=>'misc','author'=>$username),get_module_zone('search')),'search');
		if (addon_installed('authors'))
		{
			$author=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT author FROM '.get_table_prefix().'authors WHERE (forum_handle='.strval(get_member()).') OR (forum_handle IS NULL AND '.db_string_equal_to('author',$username).')');
			if ((has_actual_page_access(get_member(),'authors')) && (!is_null($author)))
			{
				$modules[]=array('content',do_lang_tempcode('AUTHOR'),build_url(array('page'=>'authors','type'=>'misc','id'=>$author),get_module_zone('authors')));
			}
		}
		require_code('ocf_members2');
		if ((!is_guest()) && (ocf_may_whisper($member_id)) && (has_actual_page_access(get_member(),'topics')) && (ocf_may_make_personal_topic()))
		{
			$modules[]=(!addon_installed('ocf_forum'))?NULL:array('contact',do_lang_tempcode('ADD_PERSONAL_TOPIC'),build_url(array('page'=>'topics','type'=>'new_pt','id'=>$member_id),get_module_zone('topics')),'reply');
		}
		if ((addon_installed('points')) && (has_actual_page_access(get_member(),'points')))
		{
			$modules[]=array('usage',do_lang_tempcode('POINTS'),build_url(array('page'=>'points','type'=>'member','id'=>$member_id),get_module_zone('points')));
		}
		$extra_sections=array();
		$info_details=array();
		$hooks=find_all_hooks('modules','members');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/modules/members/'.filter_naughty_harsh($hook));
			$object=object_factory('Hook_members_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			if (method_exists($object,'run'))
			{
				$hook_result=$object->run($member_id);
				$modules=array_merge($modules,$hook_result);
			}
			if (method_exists($object,'get_info_details'))
			{
				$hook_result=$object->get_info_details($member_id);
				$info_details=array_merge($info_details,$hook_result);
			}
			if (method_exists($object,'get_sections'))
			{
				$hook_result=$object->get_sections($member_id);
				$extra_sections=array_merge($extra_sections,$hook_result);
			}
		}
		if ((($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_allow_emails')==1) || (get_option('allow_email_disable')=='0')) && (!is_guest($member_id)) && (has_actual_page_access(get_member(),'contactmember')))
		{
			$redirect=get_self_url(true);
			$modules[]=array('contact',do_lang_tempcode('_EMAIL_MEMBER'),build_url(array('page'=>'contactmember','redirect'=>$redirect,'id'=>$member_id),get_module_zone('contactmember')),'reply');
		}
		require_lang('menus');
		$sections=array('contact'=>do_lang_tempcode('CONTACT'),'profile'=>do_lang_tempcode('EDIT_PROFILE'),'views'=>do_lang_tempcode('PERSONAL_ZONE'),'usage'=>do_lang_tempcode('USAGE'),'content'=>do_lang_tempcode('CONTENT'));
		$actions=array();
		global $M_SORT_KEY;
		$M_SORT_KEY=mixed();
		$M_SORT_KEY=1;
		@uasort($modules,'multi_sort'); /* @ is to stop PHP bug warning about altered array contents when Tempcode copies are evaluated internally */
		foreach ($sections as $section_code=>$section_title)
		{
			$links=new ocp_tempcode();
			foreach ($modules as $module)
			{
				if (count($module)==3)
				{
					list($_section_code,$lang,$url)=$module;
					$rel=NULL;
				} else
				{
					list($_section_code,$lang,$url,$rel)=$module;
				}
				if ($section_code==$_section_code)
					$links->attach(do_template('OCF_MEMBER_ACTION',array('_GUID'=>'67b2a640a368c6f53f1b1fa10f922fd0','ID'=>strval($member_id),'URL'=>$url,'LANG'=>$lang,'REL'=>$rel)));
			}
			$actions[$section_code]=$links;
		}

		// Custom fields
		$_custom_fields=ocf_get_all_custom_fields_match_member($member_id,((get_member()!=$member_id) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL,((get_member()==$member_id) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL);
		$custom_fields=array();
		require_code('encryption');
		$value=mixed();
		foreach ($_custom_fields as $name=>$_value)
		{
			$value=$_value['RAW'];
			$rendered_value=$_value['RENDERED'];
		
			$encrypted_value='';
			if (is_data_encrypted($value))
			{
				$encrypted_value=remove_magic_encryption_marker($value);
			}
			elseif ((is_string($value)) && (substr($value,0,7)=='http://'))
			{
				$_value=hyperlink($value,$value,true,true);
				$value=$_value->evaluate();
			} elseif (is_integer($value))
			{
				$value=escape_html(integer_format($value));
			} else
			{
				if (!is_object($value)) $value=escape_html($value);
			}

			if (((!is_object($value)) && ($value!='')) || ((is_object($value)) && (!$value->is_empty())))
			{
				$custom_fields[]=array('NAME'=>$name,'RAW_VALUE'=>$value,'VALUE'=>$rendered_value,'ENCRYPTED_VALUE'=>$encrypted_value);
				if ($name==do_lang('KEYWORDS')) $GLOBALS['SEO_KEYWORDS']=is_object($value)?$value->evaluate():$value;
				if ($name==do_lang('DESCRIPTION')) $GLOBALS['SEO_DESCRIPTION']=is_object($value)?$value->evaluate():$value;
			}
		}

		// Birthday
		$dob='';
		if ($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_reveal_age')==1)
		{
			$day=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_dob_day');
			$month=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_dob_month');
			$year=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_dob_year');
			if (!is_null($day))
			{
				if (@strftime('%Y',@mktime(0,0,0,1,1,1963))!='1963') $dob=strval($year).'-'.str_pad(strval($month),2,'0',STR_PAD_LEFT).'-'.str_pad(strval($day),2,'0',STR_PAD_LEFT); else $dob=get_timezoned_date(mktime(12,0,0,$month,$day,$year),false,true,true);
			}
		}

		// Find forum with most posts
		$forums=$GLOBALS['FORUM_DB']->query('SELECT id,f_name FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE f_cache_num_posts>0');
		$best_yet_forum=0; // Initialise to integer type
		$best_yet_forum=NULL;
		$most_active_forum=NULL;
		$_best_yet_forum=$GLOBALS['FORUM_DB']->query_select('f_posts',array('COUNT(*) as cnt','p_cache_forum_id'),array('p_poster'=>$member_id),'GROUP BY p_cache_forum_id');
		$_best_yet_forum=collapse_2d_complexity('p_cache_forum_id','cnt',$_best_yet_forum);
		foreach ($forums as $forum)
		{
			if (((array_key_exists($forum['id'],$_best_yet_forum)) && ((is_null($best_yet_forum)) || ($_best_yet_forum[$forum['id']]>$best_yet_forum))))
			{
				$most_active_forum=has_category_access(get_member(),'forums',strval($forum['id']))?protect_from_escaping(escape_html($forum['f_name'])):do_lang_tempcode('PROTECTED_FORUM');
				$best_yet_forum=$_best_yet_forum[$forum['id']];
			}
		}
		$post_count=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_cache_num_posts');
		$best_post_fraction=($post_count==0)?do_lang_tempcode('NA_EM'):make_string_tempcode(integer_format(100*$best_yet_forum/$post_count));
		$most_active_forum=is_null($best_yet_forum)?new ocp_tempcode():do_lang_tempcode('_MOST_ACTIVE_FORUM',make_string_tempcode(escape_html($most_active_forum)),make_string_tempcode(integer_format($best_yet_forum)),array($best_post_fraction));
		$time_for_them_raw=tz_time(time(),get_users_timezone($member_id));
		$time_for_them=get_timezoned_time(time(),true,$member_id);

		$banned=($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_is_perm_banned')==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO');

		$last_submit_time=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_last_submit_time');
		$submit_days_ago=intval(floor(floatval(time()-$last_submit_time)/60.0/60.0/24.0));

		require_code('ocf_groups');
		$primary_group_id=ocf_get_member_primary_group($member_id);
		$primary_group=ocf_get_group_link($primary_group_id);
	
		$signature=get_translated_tempcode($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_signature'),$GLOBALS['FORUM_DB']);
	
		$last_visit_time=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_last_visit_time');
		if (member_is_online($member_id))
		{
			$online_now=do_lang_tempcode('YES');
		} else
		{
			$minutes_ago=intval(floor((floatval(time()-$last_visit_time)/60.0)));
			$hours_ago=intval(floor((floatval(time()-$last_visit_time)/60.0/60.0)));
			$days_ago=intval(floor((floatval(time()-$last_visit_time)/60.0/60.0/24.0)));
			$months_ago=intval(floor((floatval(time()-$last_visit_time)/60.0/60.0/24.0/31.0)));
			if ($minutes_ago<180)
				$online_now=do_lang_tempcode('_ONLINE_NOW_NO_MINUTES',integer_format($minutes_ago));
			elseif ($hours_ago<72)
				$online_now=do_lang_tempcode('_ONLINE_NOW_NO_HOURS',integer_format($hours_ago));
			elseif ($days_ago<93)
				$online_now=do_lang_tempcode('_ONLINE_NOW_NO_DAYS',integer_format($days_ago));
			else
				$online_now=do_lang_tempcode('_ONLINE_NOW_NO_MONTHS',integer_format($months_ago));
		}

		$join_time=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_join_time');
		$days_joined=intval(round((time()-$join_time)/60/60/24));
		$total_posts=$GLOBALS['FORUM_DB']->query_value('f_posts','COUNT(*)');
		$join_date=($join_time==0)?'':get_timezoned_date($join_time,false);
		$count_posts=do_lang_tempcode('_COUNT_POSTS',integer_format($post_count),float_format(floatval($post_count)/floatval(($days_joined==0)?1:$days_joined)),array(float_format(floatval(100*$post_count)/floatval(($total_posts==0)?1:$total_posts))));

		// Show galleries
		$galleries=new ocp_tempcode();
		if (addon_installed('galleries'))
		{
			require_lang('galleries');
			require_code('galleries');
			$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'galleries WHERE name LIKE \''.db_encode_like('member\_'.strval($member_id).'\_%').'\'');
			foreach ($rows as $i=>$row)
			{
				$galleries->attach(do_template('GALLERY_SUBGALLERY_WRAP',array('CONTENT'=>show_gallery_box($row,'root',false,get_module_zone('galleries')))));
			}
		}
		
		// Show recent blog posts
		$recent_blog_posts=new ocp_tempcode();
		$rss_url=new ocp_tempcode();
		if (addon_installed('news'))
		{
			$news_cat=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner'=>$member_id),'',1);
			if ((array_key_exists(0,$news_cat)) && (has_category_access(get_member(),'news',strval($news_cat[0]['id']))))
			{
				$rss_url=make_string_tempcode(find_script('backend').'?type=rss2&mode=news&filter='.strval($news_cat[0]['id']));

				require_css('news');
				$news1=$GLOBALS['SITE_DB']->query_select('news',array('*'),array('news_category'=>$news_cat[0]['id']),'ORDER BY date_and_time DESC',10);
				$news2=$GLOBALS['SITE_DB']->query_select('news n LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_category_entries c ON n.id=c.news_entry',array('n.*'),array('news_category'=>$news_cat[0]['id']),'ORDER BY date_and_time DESC',10);
				$news=array();
				foreach ($news1 as $row) $news[$row['id']]=$row;
				foreach ($news2 as $row) $news[$row['id']]=$row;
				unset($news1);
				unset($news2);
				$M_SORT_KEY='date_and_time';
				usort($news,'multi_sort');
				$news=array_reverse($news);
				foreach ($news as $i=>$myrow)
				{
					if ($i==10) break;

					$news_id=$myrow['id'];
					$news_date=get_timezoned_date($myrow['date_and_time']);
					$author_url='';
					$author=$myrow['author'];
					$news_title=get_translated_tempcode($myrow['title']);
					$news_summary=get_translated_tempcode($myrow['news']);
					if ($news_summary->is_empty())
					{
						$news_summary=get_translated_tempcode($myrow['news_article']);
						$truncate=true;
					} else $truncate=false;
					$news_full_url=build_url(array('page'=>'news','type'=>'view','id'=>$news_id,'filter'=>$news_cat[0]['id']),get_module_zone('news'));
					$news_img=find_theme_image($news_cat[0]['nc_img']);
					if (is_null($news_img)) $news_img='';
					if ($myrow['news_image']!='')
					{
						$news_img=$myrow['news_image'];
						if (url_is_local($news_img)) $news_img=get_base_url().'/'.$news_img;
					}
					$news_category=get_translated_text($news_cat[0]['nc_title']);
					$seo_bits=seo_meta_get_for('news',strval($news_id));
					$map2=array('TAGS'=>get_loaded_tags('news',explode(',',$seo_bits[0])),'TRUNCATE'=>$truncate,'BLOG'=>false,'ID'=>strval($news_id),'SUBMITTER'=>strval($myrow['submitter']),'CATEGORY'=>$news_category,'IMG'=>$news_img,'DATE'=>$news_date,'DATE_RAW'=>strval($myrow['date_and_time']),'NEWS_TITLE'=>$news_title,'AUTHOR'=>$author,'AUTHOR_URL'=>$author_url,'NEWS'=>$news_summary,'FULL_URL'=>$news_full_url);
					if ((get_option('is_on_comments')=='1') && (!has_no_forum()) && ($myrow['allow_comments']>=1)) $map2['COMMENT_COUNT']='1';
					$recent_blog_posts->attach(do_template('NEWS_PIECE_SUMMARY',$map2));
				}
			}
		}

		$a=($avatar_url=='')?0:ocf_get_member_best_group_property($member_id,'max_avatar_width');
		$b=($photo_thumb_url=='')?0:intval(get_option('thumb_width'));
		$right_margin=(max($a,$b)==0)?'auto':(strval(max($a,$b)+6).'px');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MEMBERS'))));

		if (has_specific_permission(get_member(),'see_ip'))
		{
			$ip_address=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_ip_address');
		} else $ip_address='';

		$secondary_groups=ocf_get_members_groups($member_id,true);
		unset($secondary_groups[$primary_group_id]);
		if (count($secondary_groups)>0)
		{
			$_secondary_groups=array();
			$all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,false,false,array_keys($secondary_groups),$member_id);
			foreach (array_keys($secondary_groups) as $key)
			{
				$_secondary_groups[$key]=$all_groups[$key];
			}
			$secondary_groups=$_secondary_groups;
		}

		if (addon_installed('points'))
		{
			require_code('points');
			$count_points=integer_format(total_points($member_id));
		} else
		{
			$count_points='';
		}

		$user_agent=NULL;
		$operating_system=NULL;
		if ((has_specific_permission(get_member(),'show_user_browsing')) && (addon_installed('stats')))
		{
			$last_stats=$GLOBALS['SITE_DB']->query_select('stats',array('browser','operating_system'),array('the_user'=>$member_id),'ORDER BY date_and_time DESC',1);
			if (array_key_exists(0,$last_stats))
			{
				$user_agent=$last_stats[0]['browser'];
				$operating_system=$last_stats[0]['operating_system'];
			}
		}

		// Friends
		$friends_a=array();
		$friends_b=array();
		$add_friend_url=new ocp_tempcode();
		$remove_friend_url=new ocp_tempcode();
		$all_buddies_link=new ocp_tempcode();
		if (addon_installed('chat'))
		{
			require_code('chat');
			if (($member_id!=get_member()) && (!is_guest()))
			{
				if (!member_befriended($member_id))
				{
					$add_friend_url=build_url(array('page'=>'chat','type'=>'buddy_add','member_id'=>$member_id,'redirect'=>get_self_url(true)),get_module_zone('chat'));
				} else
				{
					$remove_friend_url=build_url(array('page'=>'chat','type'=>'buddy_remove','member_id'=>$member_id,'redirect'=>get_self_url(true)),get_module_zone('chat'));
				}
			}

			$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'chat_buddies WHERE member_likes='.strval(intval($member_id)).' OR member_liked='.strval(intval($member_id)).' ORDER BY date_and_time',100);
			//$rows=array(array('member_liked'=>2,'member_likes'=>3),array('member_liked'=>3,'member_likes'=>2));
			$blocked=collapse_1d_complexity('member_blocked',$GLOBALS['SITE_DB']->query_select('chat_blocking',array('member_blocked'),array('member_blocker'=>$member_id)));
			$done_already=array();
			$all_usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,false,false,NULL,$member_id);
			foreach ($rows as $i=>$row)
			{
				$f_id=($row['member_liked']==$member_id)?$row['member_likes']:$row['member_liked'];

				if (array_key_exists($f_id,$done_already)) continue;

				if (($f_id==$row['member_likes']) || (!in_array($f_id,$blocked)))
				{
					$appears_twice=false;
					foreach ($rows as $j=>$row2)
					{
						$f_id_2=($row2['member_liked']==$member_id)?$row2['member_likes']:$row2['member_liked'];
						if (($f_id_2==$f_id) && ($i!=$j))
						{
							$appears_twice=true;
							break;
						}
					}
					require_code('ocf_members2');
					$friend_username=$GLOBALS['FORUM_DRIVER']->get_username($f_id);
					$friend_usergroup_id=$GLOBALS['FORUM_DRIVER']->get_member_row_field($f_id,'m_primary_group');
					$friend_usergroup=array_key_exists($friend_usergroup_id,$all_usergroups)?$all_usergroups[$friend_usergroup_id]:do_lang_tempcode('UNKNOWN');
					$mutual_label=do_lang('MUTUAL_FRIEND');
					$box=ocf_show_member_box($f_id,false,NULL,NULL,true,($f_id==get_member() || $member_id==get_member())?array($mutual_label=>do_lang($appears_twice?'YES':'NO')):NULL);
					if ($box->is_empty()) continue;
					$friend_map=array('USERGROUP'=>$friend_usergroup,'USERNAME'=>$friend_username,'URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_link($f_id,false,true),'F_ID'=>strval($f_id),'BOX'=>$box);
					if ($appears_twice) // Mutual friendship
					{
						$friends_a[]=$friend_map;
					} else // One-way friendship
					{
						$friends_b[]=$friend_map;
					}
				}

				$done_already[$f_id]=1;
			}
			if (count($rows)==100) $all_buddies_link=build_url(array('page'=>'chat','type'=>'buddies_list','id'=>$member_id),get_module_zone('chat'));
		}

		/*if ((get_option('allow_member_integration')!='off') && (get_option('allow_member_integration')!='hidden'))
		{
			$remote=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_password_compat_scheme')=='remote';
		} else */$remote=NULL;

		$_on_probation=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_on_probation_until');
		if ($_on_probation<=time()) $on_probation=NULL; else $on_probation=strval($_on_probation);

		$GLOBALS['META_DATA']+=array(
			'created'=>date('Y-m-d',$join_time),
			'creator'=>$username,
			'publisher'=>'', // blank means same as creator
			'modified'=>'',
			'type'=>'Member',
			'title'=>'',
			'identifier'=>'_SEARCH:members:view:'.strval($member_id),
			'description'=>'',
			'image'=>$avatar_url,
		);

		// Look up member's clubs
		$clubs=array();
		if (addon_installed('ocf_clubs'))
		{
			$club_ids=$GLOBALS['FORUM_DRIVER']->get_members_groups($member_id,true);
			$club_rows=list_to_map('id',$GLOBALS['FORUM_DB']->query_select('f_groups',array('*'),array('g_is_private_club'=>1),'',200));
			if (count($club_rows)==200) $club_rows=NULL;
			foreach ($club_ids as $club_id)
			{
				if (is_null($club_rows))
				{
					$club_rows=list_to_map('id',$GLOBALS['FORUM_DB']->query_select('f_groups',array('*'),array('g_is_private_club'=>1,'id'=>$club_id),'',200));
					if (!array_key_exists($club_id,$club_rows)) continue;
					$club_row=$club_rows[$club_id];
					$club_rows=NULL;
				} else
				{
					if (!array_key_exists($club_id,$club_rows)) continue;
					$club_row=$club_rows[$club_id];
				}
				
				$club_name=get_translated_text($club_row['g_name']);
				$club_forum=$GLOBALS['FORUM_DB']->query_value_null_ok('f_forums f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=f.f_description','f.id',array('text_original'=>do_lang('FORUM_FOR_CLUB',$club_name)));

				$clubs[]=array(
					'CLUB_NAME'=>$club_name,
					'CLUB_ID'=>strval($club_row['id']),
					'CLUB_FORUM'=>is_null($club_forum)?'':strval($club_forum),
				);
			}
		}

		$edit_profile_url=new ocp_tempcode();
		$edit_avatar_url=new ocp_tempcode();
		$edit_photo_url=new ocp_tempcode();
		$edit_signature_url=new ocp_tempcode();
		$edit_title_url=new ocp_tempcode();
		$delete_member_url=new ocp_tempcode();
		if ((has_specific_permission(get_member(),'member_maintenance')) || ($member_id==get_member()) || (has_specific_permission(get_member(),'assume_any_member')))
		{
			$edit_profile_url=build_url(array('page'=>'editprofile','type'=>'misc','id'=>$member_id),get_module_zone('editprofile'));
			if (addon_installed('ocf_forum'))
			{
				if ((addon_installed('ocf_member_avatars')) && (has_actual_page_access(get_member(),'editavatar'))) $edit_avatar_url=build_url(array('page'=>'editavatar','type'=>'misc','id'=>$member_id),get_module_zone('editavatar'));
				if ((addon_installed('ocf_member_photos')) && (has_actual_page_access(get_member(),'editphoto'))) $edit_photo_url=build_url(array('page'=>'editphoto','type'=>'misc','id'=>$member_id),get_module_zone('editphoto'));
				if ((addon_installed('ocf_signatures')) && (has_actual_page_access(get_member(),'editsignature'))) $edit_signature_url=build_url(array('page'=>'editsignature','type'=>'misc','id'=>$member_id),get_module_zone('editsignature'));
				if ((addon_installed('ocf_member_titles')) && (has_actual_page_access(get_member(),'edittitle'))) $edit_title_url=build_url(array('page'=>'edittitle','type'=>'misc','id'=>$member_id),get_module_zone('edittitle'));
			}
			if (has_actual_page_access(get_member(),'delete'))
				$delete_member_url=build_url(array('page'=>'delete','type'=>'misc','id'=>$member_id),get_module_zone('delete'));
		}

		return do_template('OCF_MEMBER_PROFILE_SCREEN',
			array('_GUID'=>'fodfjdsfjsdljfdls',
					'TITLE'=>$title,
					'CLUBS'=>$clubs,
					'REMOTE'=>$remote,
					'GALLERIES'=>$galleries,
					'RECENT_BLOG_POSTS'=>$recent_blog_posts,
					'RIGHT_MARGIN'=>$right_margin,
					'AVATAR_WIDTH'=>strval($a).'px',
					'PHOTO_WIDTH'=>strval($b).'px',
					'MOST_ACTIVE_FORUM'=>$most_active_forum,
					'TIME_FOR_THEM'=>$time_for_them,
					'TIME_FOR_THEM_RAW'=>strval($time_for_them_raw),
					'SUBMIT_DAYS_AGO'=>integer_format($submit_days_ago),
					'SUBMIT_TIME_RAW'=>strval($last_submit_time),
					'LAST_VISIT_TIME_RAW'=>strval($last_visit_time),
					'ONLINE_NOW'=>$online_now,
					'BANNED'=>$banned,
					'USER_AGENT'=>$user_agent,
					'OPERATING_SYSTEM'=>$operating_system,
					'DOB'=>$dob,
					'IP_ADDRESS'=>$ip_address,
					'COUNT_POSTS'=>$count_posts,
					'COUNT_POINTS'=>$count_points,
					'PRIMARY_GROUP'=>$primary_group,
					'PRIMARY_GROUP_ID'=>strval($primary_group_id),
					'PHOTO_URL'=>$photo_url,
					'PHOTO_THUMB_URL'=>$photo_thumb_url,
					'EMAIL_ADDRESS'=>$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_email_address'),
					'AVATAR_URL'=>$avatar_url,
					'SIGNATURE'=>$signature,
					'JOIN_DATE'=>$join_date,
					'JOIN_DATE_RAW'=>strval($join_time),
					'CUSTOM_FIELDS'=>$custom_fields,
					'ACTIONS_contact'=>$actions['contact'],
					'ACTIONS_profile'=>$actions['profile'],
					'ACTIONS_views'=>$actions['views'],
					'ACTIONS_usage'=>$actions['usage'],
					'ACTIONS_content'=>$actions['content'],
					'USERNAME'=>$username,
					'MEMBER_ID'=>strval($member_id),
					'SECONDARY_GROUPS'=>$secondary_groups,
					'VIEW_PROFILES'=>has_specific_permission(get_member(),'view_profiles'),
					'FRIENDS_A'=>$friends_a,
					'FRIENDS_B'=>$friends_b,
					'ALL_BUDDIES_LINK'=>$all_buddies_link,
					'ON_PROBATION'=>$on_probation,
					'RSS_URL'=>$rss_url,
					'EXTRA_INFO_DETAILS'=>$info_details,
					'EXTRA_SECTIONS'=>$extra_sections,
					'ADD_FRIEND_URL'=>$add_friend_url,
					'REMOVE_FRIEND_URL'=>$remove_friend_url,
					'EDIT_PROFILE_URL'=>$edit_profile_url,
					'EDIT_AVATAR_URL'=>$edit_avatar_url,
					'EDIT_PHOTO_URL'=>$edit_photo_url,
					'EDIT_SIGNATURE_URL'=>$edit_signature_url,
					'EDIT_TITLE_URL'=>$edit_title_url,
					'DELETE_MEMBER_URL'=>$delete_member_url,
			)
		);
	}

}


