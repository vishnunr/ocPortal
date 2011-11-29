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
 * @package		ocf_forum
 */

/**
 * Get details of a topic (to show eventually as a row in a forum or results view). This is a helper function, and thus the interface is not very user friendly.
 *
 * @param  array		The DB row of the topic.
 * @param  MEMBER		The member the details are being prepared for.
 * @param  integer	The hot topic definition (taken from the config options).
 * @param  boolean	Whether the viewing member has a post in the topic.
 * @return array		The details.
 */
function ocf_get_topic_array($topic_row,$member_id,$hot_topic_definition,$involved)
{
	$topic=array();

	if ((is_null($topic_row['t_cache_first_post'])) || ($topic_row['t_cache_first_post']===0))
	{
		$topic['first_post']=new ocp_tempcode();
	} else
	{
		if ((!is_null($topic_row['_trans_post'])) && ($topic_row['_trans_post']!=''))
		{
			$topic['first_post']=new ocp_tempcode();
			if (!$topic['first_post']->from_assembly($topic_row['_trans_post']))
				$topic_row['_trans_post']=NULL;
		}

		if ((is_null($topic_row['_trans_post'])) || ($topic_row['_trans_post']==''))
		{
			if (!is_null($topic_row['t_cache_first_post'])) $topic['first_post']=get_translated_tempcode($topic_row['t_cache_first_post'],$GLOBALS['FORUM_DB']);
			else $topic['first_post']=new ocp_tempcode();
		} else
		{
			$topic['first_post']->singular_bind('ATTACHMENT_DOWNLOADS',make_string_tempcode('?'));
		}
	}

	$topic['id']=$topic_row['id'];
	$topic['num_views']=$topic_row['t_num_views'];
	$topic['num_posts']=$topic_row['t_cache_num_posts'];
	$topic['forum_id']=$topic_row['t_forum_id'];
	$topic['description']=$topic_row['t_description'];
	$topic['emoticon']=$topic_row['t_emoticon'];
	$topic['first_time']=$topic_row['t_cache_first_time'];
	$topic['first_title']=$topic_row['t_cache_first_title'];
	if ($topic['first_title']=='') $topic['first_title']=do_lang_tempcode('NA');
	$topic['first_username']=$topic_row['t_cache_first_username'];
	$topic['first_member_id']=$topic_row['t_cache_first_member_id'];
	if (is_null($topic['first_member_id']))
	{
		require_code('ocf_posts_action2');
		ocf_force_update_topic_cacheing($topic_row['id'],NULL,true,true);
		//$_topic_row=$GLOBALS['FORUM_DB']->query_select('f_topics',array('*'),array('id'=>$topic_row['id']),'',1);
		//$topic_row=$_topic_row[0];
		//$topic['first_member_id']=$GLOBALS['OCF_DRIVER']->get_guest_id();
	}
	if (!is_null($topic_row['t_cache_last_post_id']))
	{
		$topic['last_post_id']=$topic_row['t_cache_last_post_id'];
		$topic['last_time']=$topic_row['t_cache_last_time'];
		$topic['last_time_string']=get_timezoned_date($topic_row['t_cache_last_time']);
		$topic['last_title']=$topic_row['t_cache_last_title'];
		$topic['last_username']=$topic_row['t_cache_last_username'];
		$topic['last_member_id']=$topic_row['t_cache_last_member_id'];
	}

	// Modifiers
	$topic['modifiers']=array();
	$has_read=ocf_has_read_topic($topic['id'],$topic_row['t_cache_last_time'],$member_id,$topic_row['l_time']);
	if (!$has_read) $topic['modifiers'][]='unread';
	if ($involved) $topic['modifiers'][]='involved';
	if ($topic_row['t_cascading']==1) $topic['modifiers'][]='announcement';
	if ($topic_row['t_pinned']==1) $topic['modifiers'][]='pinned';
	if ($topic_row['t_sunk']==1) $topic['modifiers'][]='sunk';
	if ($topic_row['t_is_open']==0) $topic['modifiers'][]='closed';
	if ($topic_row['t_validated']==0) $topic['modifiers'][]='unvalidated';
	if (!is_null($topic_row['t_poll_id'])) $topic['modifiers'][]='poll';
	$num_posts=$topic_row['t_cache_num_posts'];
	$start_time=$topic_row['t_cache_first_time'];
	$end_time=$topic_row['t_cache_last_time'];
	$days=floatval($end_time-$start_time)/60.0/60.0/24.0;
	if ($days==0.0) $days=1.0;
	if (($num_posts>=8) && (intval(round(floatval($num_posts)/$days))>=$hot_topic_definition)) $topic['modifiers'][]='hot';

	return $topic;
}

/**
 * Render a topic row (i.e. a row in a forum or results view), from given details (from ocf_get_topic_array).
 *
 * @param  array		The details (array containing: last_post_id, id, modifiers, emoticon, first_member_id, first_username, first_post, num_posts, num_views).
 * @param  boolean	Whether the viewing member has the facility to mark off topics (send as false if there are no actions for them to perform).
 * @param  boolean	Whether the topic is a Private Topic.
 * @param  ?string	The forum name (NULL: do not show the forum name).
 * @return tempcode	The topic row.
 */
function ocf_render_topic($topic,$has_topic_marking,$pt=false,$show_forum=NULL)
{
	if ((array_key_exists('last_post_id',$topic)) && (!is_null($topic['last_post_id'])))
	{
		$last_post_url=build_url(array('page'=>'topicview','id'=>$topic['last_post_id'],'type'=>'findpost'),get_module_zone('topicview'));
		$last_post_url->attach('#post_'.strval($topic['last_post_id']));
		if (!is_null($topic['last_member_id']))
		{
			if ($topic['last_member_id']!=$GLOBALS['OCF_DRIVER']->get_guest_id())
			{
				//$colour=get_group_colour(ocf_get_member_primary_group($topic['last_member_id']));
				$poster=do_template('OCF_USER_MEMBER',array(
					/*'COLOUR'=>$colour,*/
					'USERNAME'=>$topic['last_username'],
					'PROFILE_URL'=>$GLOBALS['OCF_DRIVER']->member_profile_link($topic['last_member_id'],false,true)
				));
			} else $poster=protect_from_escaping(escape_html($topic['last_username']));
		} else
		{
			$poster=do_lang_tempcode('NA');
		}
		$last_post=do_template('OCF_FORUM_TOPIC_ROW_LAST_POST',array('_GUID'=>'6aa8d0f4024ae12bf94b68b74faae7cf','ID'=>strval($topic['id']),'DATE_RAW'=>strval($topic['last_time']),'DATE'=>$topic['last_time_string'],'POSTER'=>$poster,'LAST_URL'=>$last_post_url));
	} else $last_post=do_lang_tempcode('NA_EM');
	$map=array('page'=>'topicview','id'=>$topic['id']);
	if ((array_key_exists('forum_id',$topic)) && (is_null(get_bot_type())) && (get_param_integer('start',0)!=0)) $map['kfs'.strval($topic['forum_id'])]=get_param_integer('start',0);
	$url=build_url($map,get_module_zone('topicview'));

	// Modifiers
	$topic_row_links=new ocp_tempcode();
	$modifiers=$topic['modifiers'];
	if (in_array('unread',$modifiers))
	{
		$first_unread_url=build_url(array('page'=>'topicview','id'=>$topic['id'],'type'=>'first_unread'),get_module_zone('topicview'));
		$first_unread_url->attach('#first_unread');
		$topic_row_links->attach(do_template('OCF_TOPIC_ROW_LINK',array('_GUID'=>'6f52881ed999f4c543c9d8573b37fa48','URL'=>$first_unread_url,'IMG'=>'unread','ALT'=>do_lang_tempcode('JUMP_TO_FIRST_UNREAD'))));
	}
	$topic_row_modifiers=new ocp_tempcode();
	foreach ($modifiers as $modifier)
	{
		if ($modifier!='unread')
		{
			$topic_row_modifiers->attach(do_template('OCF_TOPIC_ROW_MODIFIER',array('_GUID'=>'fbcb8791b571187fd699aa6796c3f401','IMG'=>$modifier,'ALT'=>do_lang_tempcode('MODIFIER_'.$modifier))));
		}
	}

	// Emoticon
	if ($topic['emoticon']!='') $emoticon=do_template('OCF_TOPIC_EMOTICON',array('_GUID'=>'dfbe0e4a11b3caa4d2da298ff23ca221','EMOTICON'=>$topic['emoticon']));
	else $emoticon=do_template('OCF_TOPIC_EMOTICON_NONE');

	if ($topic['first_member_id']!=$GLOBALS['OCF_DRIVER']->get_guest_id())
	{
		$poster_profile_url=$GLOBALS['OCF_DRIVER']->member_profile_link($topic['first_member_id'],false,true);
		//$colour=get_group_colour(ocf_get_member_primary_group($topic['first_member_id']));
		$poster=do_template('OCF_USER_MEMBER',array(
										/*'COLOUR'=>$colour,*/
										'PROFILE_URL'=>$poster_profile_url,
										'USERNAME'=>$topic['first_username']
									));
	} else
	{
		$poster=make_string_tempcode(escape_html($topic['first_username']));
	}
	if ($pt)
	{
		$with=($topic['pt_from']==$topic['first_member_id'])?$topic['pt_to']:$topic['pt_from'];
		$with_username=$GLOBALS['OCF_DRIVER']->get_username($with);
		if (is_null($with_username)) $with_username=do_lang('UNKNOWN');
		$colour=get_group_colour(ocf_get_member_primary_group($with));
		$b=do_template('OCF_USER_MEMBER',array(
										'COLOUR'=>$colour,
										'PROFILE_URL'=>$GLOBALS['OCF_DRIVER']->member_profile_link($with,false,true),
										'USERNAME'=>$with_username
									));
		$poster=do_template('OCF_PT_BETWEEN',array('_GUID'=>'619cd7076c4baf7b26cb3149694af929','A'=>$poster,'B'=>$b));
	}

	// Marker
	$marker=new ocp_tempcode();
	if ($has_topic_marking)
	{
		$marker=do_template('OCF_TOPIC_MARKER',array('_GUID'=>'62ff977640d3d4270cf333edab42a18f','ID'=>strval($topic['id'])));
	}

	// Title
	$title=$topic['first_title'];

	// Page jump
	$max=intval(get_option('forum_posts_per_page'));
	require_code('templates_result_launcher');
	$pages=results_launcher(do_lang_tempcode('NAMED_TOPIC',escape_html($title)),'topicview',$topic['id'],$max,$topic['num_posts'],'view',5);

	// Tpl
	$post=$topic['first_post'];
	if (!is_null($show_forum))
	{
		$hover=do_lang_tempcode('FORUM_AND_TIME_HOVER',escape_html($show_forum),get_timezoned_date($topic['first_time']));
		$breadcrumbs=ocf_forum_breadcrumbs($topic['forum_id'],NULL,NULL,false);
	} else
	{
		$hover=protect_from_escaping(is_null($topic['first_time'])?'':escape_html(get_timezoned_date($topic['first_time'])));
		$breadcrumbs=new ocp_tempcode();
	}

	return do_template('OCF_FORUM_TOPIC_ROW',array('_GUID'=>'1aca672272132f390c9ec23eebe0d171','BREADCRUMBS'=>$breadcrumbs,'RAW_TIME'=>is_null($topic['first_time'])?'':strval($topic['first_time']),'UNREAD'=>in_array('unread',$modifiers),'ID'=>strval($topic['id']),'HOVER'=>$hover,'PAGES'=>$pages,'MARKER'=>$marker,'TOPIC_ROW_LINKS'=>$topic_row_links,'TOPIC_ROW_MODIFIERS'=>$topic_row_modifiers,'POST'=>$post,'EMOTICON'=>$emoticon,'DESCRIPTION'=>$topic['description'],'URL'=>$url,'TITLE'=>$title,'POSTER'=>$poster,'NUM_POSTS'=>integer_format($topic['num_posts']),'NUM_VIEWS'=>integer_format($topic['num_views']),'LAST_POST'=>$last_post));
}

/**
 * Get a map of details relating to the view of a certain forum of a certain member.
 *
 * @param  integer	The start row for getting details of topics in the forum (i.e. 0 is newest, higher is starting further back in time).
 * @param  ?integer	The maximum number of topics to get detail of (NULL: default).
 * @param  ?MEMBER	The member viewing (NULL: current member).
 * @return array		The details.
 */
function ocf_get_forum_view($start=0,$max=NULL,$forum_id=NULL)
{
	if (is_null($max)) $max=intval(get_option('forum_topics_per_page'));

	$member_id=get_member();

	load_up_all_module_category_permissions($member_id,'forums');

	if (is_null($forum_id))
	{
		/*$forum_info[0]['f_name']=do_lang('ROOT_FORUM'); This optimisation was more trouble that it was worth, and constraining
		$forum_info[0]['f_description']='';
		$forum_info[0]['f_parent_forum']=NULL;*/
		$forum_id=db_get_first_id();
	}/* else*/
	{
		$forum_info=$GLOBALS['FORUM_DB']->query_select('f_forums f',array('f_redirection','f_intro_question','f_intro_answer','f_order_sub_alpha','f_parent_forum','f_name','f_description','f_order'),array('f.id'=>$forum_id),'',1,NULL,false,array('f_description','f_intro_question'));
		if (!array_key_exists(0,$forum_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		
		if (($forum_info[0]['f_redirection']!='') && (looks_like_url($forum_info[0]['f_redirection'])))
		{
			header('Location: '.$forum_info[0]['f_redirection']);
			exit();
		}
	}

	if (!is_null($forum_id)) // Anyone may view the root (and see the topics in the root - but there will hardly be any)
	{
		if (!has_category_access($member_id,'forums',strval($forum_id))) access_denied('CATEGORY_ACCESS_LEVEL'); // We're only allowed to view it existing from a parent forum, or nothing at all -- so access denied brother!
	}

	// Find our subforums first
	$order=$forum_info[0]['f_order_sub_alpha']?'f_name':'f_position';
	$huge_forums=$GLOBALS['FORUM_DB']->query_value('f_forums','COUNT(*)')>100;
	if ($huge_forums)
	{
		$subforum_rows=$GLOBALS['FORUM_DB']->query('SELECT f.* FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums f WHERE f.id='.strval($forum_id).' OR f_parent_forum='.strval($forum_id).' ORDER BY f_parent_forum,'.$order,300,NULL,false,false,array('f_description','f_intro_question'));
		if (count($subforum_rows)==300) $subforum_rows=array(); // Will cause performance breakage
	} else
	{
		$subforum_rows=$GLOBALS['FORUM_DB']->query_select('f_forums f',array('f.*'),NULL,'ORDER BY f_parent_forum,'.$order,NULL,NULL,false,array('f_description','f_intro_question'));
	}

	$unread_forums=array();
	if ((!is_null($forum_id)) && (get_member()!=$GLOBALS['OCF_DRIVER']->get_guest_id()))
	{
		// Where are there unread topics in subforums?
		$tree=array();
		$subforum_rows_copy=$subforum_rows;
		$tree=ocf_organise_into_tree($subforum_rows_copy,$forum_id);
		if ($forum_id!=db_get_first_id())
		{
			$child_or_list=ocf_get_all_subordinate_forums($forum_id,'t_forum_id',$tree);
		} else $child_or_list='';
		if ($child_or_list!='') $child_or_list.=' AND ';
		$query='SELECT DISTINCT t_forum_id,t.id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON (t.id=l_topic_id AND l_member_id='.strval((integer)get_member()).') WHERE '.$child_or_list.'t_cache_last_time>'.strval(time()-60*60*24*intval(get_option('post_history_days'))).' AND (l_time<t_cache_last_time OR l_time IS NULL)';
		if (!has_specific_permission(get_member(),'jump_to_unvalidated')) $query.=' AND t_validated=1';
		$unread_forums=collapse_2d_complexity('t_forum_id','id',$GLOBALS['FORUM_DB']->query($query));
	}

	// Find all the categories that are used
	$categories=array();
	$or_list='';
	foreach ($subforum_rows as $tmp_key=>$subforum_row)
	{
		if ($subforum_row['f_parent_forum']!=$forum_id) continue;

		if (!has_category_access($member_id,'forums',strval($subforum_row['id'])))
		{
			unset($subforum_rows[$tmp_key]);
			continue;
		}

		$category_id=$subforum_row['f_category_id'];
		if (!array_key_exists($category_id,$categories))
		{
			$categories[$category_id]=array('subforums'=>array());
			if ($or_list!='') $or_list.=' OR ';
			$or_list.='id='.strval((integer)$category_id);
		}
	}
	if ($or_list!='')
	{
		$category_rows=$GLOBALS['FORUM_DB']->query('SELECT * FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_categories WHERE '.$or_list);
		foreach ($category_rows as $category_row)
		{
			$category_id=$category_row['id'];
			$title=$category_row['c_title'];
			$description=$category_row['c_description'];
			$expanded_by_default=$category_row['c_expanded_by_default'];
			$categories[$category_id]['title']=$title;
			$categories[$category_id]['description']=$description;
			$categories[$category_id]['expanded_by_default']=$expanded_by_default;
		}
		$categories[NULL]['title']='';
		$categories[NULL]['description']='';
		$categories[NULL]['expanded_by_default']=true;
		foreach ($subforum_rows as $subforum_row)
		{
			if ($subforum_row['f_parent_forum']!=$forum_id) continue;

			$category_id=$subforum_row['f_category_id'];

//			if (!array_key_exists('position',$categories[$category_id])) $categories[$category_id]['position']=$subforum_row['f_position'];

			$subforum=array();
			$subforum['id']=$subforum_row['id'];
			$subforum['name']=$subforum_row['f_name'];
			$subforum['description']=get_translated_tempcode($subforum_row['f_description'],$GLOBALS['FORUM_DB']);
			$subforum['redirection']=$subforum_row['f_redirection'];
			$subforum['intro_question']=get_translated_tempcode($subforum_row['f_intro_question'],$GLOBALS['FORUM_DB']);
			$subforum['intro_answer']=$subforum_row['f_intro_answer'];

			if (is_numeric($subforum_row['f_redirection']))
			{
				$subforum_row=$GLOBALS['FORUM_DB']->query_select('f_forums',array('*'),array('id'=>intval($subforum_row['f_redirection'])),'',1);
				$subforum_row=$subforum_row[0];
			}

			if (($subforum_row['f_redirection']=='') || (is_numeric($subforum_row['f_redirection'])))
			{
				$subforum['num_topics']=$subforum_row['f_cache_num_topics'];
				$subforum['num_posts']=$subforum_row['f_cache_num_posts'];

				$subforum['has_new']=false;
				if (get_member()!=$GLOBALS['OCF_DRIVER']->get_guest_id())
				{
					$subforums_recurse=ocf_get_all_subordinate_forums($subforum['id'],NULL,$tree[$subforum['id']]['children']);
					foreach ($subforums_recurse as $subforum_potential)
					{
						if (array_key_exists($subforum_potential,$unread_forums)) $subforum['has_new']=true;
					}
				}

				if ((is_null($subforum_row['f_cache_last_forum_id'])) || (has_category_access($member_id,'forums',strval($subforum_row['f_cache_last_forum_id']))))
				{
					$subforum['last_topic_id']=$subforum_row['f_cache_last_topic_id'];
					$subforum['last_title']=$subforum_row['f_cache_last_title'];
					$subforum['last_time']=$subforum_row['f_cache_last_time'];
					$subforum['last_username']=$subforum_row['f_cache_last_username'];
					$subforum['last_member_id']=$subforum_row['f_cache_last_member_id'];
					$subforum['last_forum_id']=$subforum_row['f_cache_last_forum_id'];
				} else $subforum['protected_last_post']=true;

				// Subsubforums
				$subforum['children']=array();
				foreach ($subforum_rows as $tmp_key_2=>$subforum_row2)
				{
					if (($subforum_row2['f_parent_forum']==$subforum_row['id']) && (has_category_access($member_id,'forums',strval($subforum_row2['id']))))
					{
						$subforum['children'][$subforum_row2['f_name'].'__'.strval($subforum_row2['id'])]=array('id'=>$subforum_row2['id'],'name'=>$subforum_row2['f_name'],'redirection'=>$subforum_row2['f_redirection']);
					}
				}
				global $M_SORT_KEY;
				$M_SORT_KEY='name';
				uasort($subforum['children'],'multi_sort');
			}

			$categories[$category_id]['subforums'][]=$subforum;
		}
	}

	// Find topics
	$extra='';
	if ((!has_specific_permission(get_member(),'see_unvalidated')) && (!ocf_may_moderate_forum($forum_id,$member_id))) $extra='t_validated=1 AND ';
	if (is_null($forum_info[0]['f_parent_forum']))
	{
		$where=$extra.' (t_forum_id='.strval((integer)$forum_id).')';
	} else
	{
		$extra2='';
		$parent_or_list=ocf_get_forum_parent_or_list($forum_id,$forum_info[0]['f_parent_forum']);
		if ($parent_or_list!='')
		{
			$extra2='AND ('.$parent_or_list.')';
		}
		$where=$extra.' (t_forum_id='.strval((integer)$forum_id).' OR (t_cascading=1 '.$extra2.'))';
	}
	$order=get_param('order',$forum_info[0]['f_order']);
	$order2='t_cache_last_time DESC';
	if ($order=='first_post') $order2='t_cache_first_time DESC';
	elseif ($order=='title') $order2='t_cache_first_title ASC';
	if (get_value('disable_sunk')!=='1')
		$order2='t_sunk ASC,'.$order2;
	if (is_guest())
	{
		$query='SELECT ttop.*,t.text_parsed AS _trans_post,NULL AS l_time FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics ttop LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND ttop.t_cache_first_post=t.id WHERE '.$where.' ORDER BY t_cascading DESC,t_pinned DESC,'.$order2;
	} else
	{
		$query='SELECT ttop.*,t.text_parsed AS _trans_post,l_time FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics ttop LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON (ttop.id=l.l_topic_id AND l.l_member_id='.strval((integer)get_member()).') LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND ttop.t_cache_first_post=t.id WHERE '.$where.' ORDER BY t_cascading DESC,t_pinned DESC,'.$order2;
	}
	$topic_rows=$GLOBALS['FORUM_DB']->query($query,$max,$start);
	if (($start==0) && (count($topic_rows)<$max)) $max_rows=$max; // We know that they're all on this screen
	else $max_rows=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE '.$where);
	$topics=array();
	$hot_topic_definition=intval(get_option('hot_topic_definition'));
	$or_list='';
	foreach ($topic_rows as $topic_row)
	{
		if ($or_list!='') $or_list.=' OR ';
		$or_list.='p_topic_id='.strval((integer)$topic_row['id']);
	}
	if (($or_list!='') && (!is_guest()))
	{
		$involved=$GLOBALS['FORUM_DB']->query('SELECT DISTINCT p_topic_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE ('.$or_list.') AND p_poster='.strval((integer)get_member()));
		$involved=collapse_1d_complexity('p_topic_id',$involved);
	} else $involved=array();
	foreach ($topic_rows as $topic_row)
	{
		$topics[]=ocf_get_topic_array($topic_row,$member_id,$hot_topic_definition,in_array($topic_row['id'],$involved));
	}

	$description=get_translated_tempcode($forum_info[0]['f_description'],$GLOBALS['FORUM_DB']);
	$description_text=get_translated_text($forum_info[0]['f_description'],$GLOBALS['FORUM_DB']);
	$out=array('name'=>$forum_info[0]['f_name'],
					'description'=>$description,
					'categories'=>$categories,
					'topics'=>$topics,
					'max_rows'=>$max_rows,
					'order'=>$order,
					'parent_forum'=>$forum_info[0]['f_parent_forum']);

	$GLOBALS['META_DATA']+=array(
		'created'=>'',
		'creator'=>'',
		'publisher'=>'', // blank means same as creator
		'modified'=>'',
		'type'=>'Forum',
		'title'=>$forum_info[0]['f_name'],
		'identifier'=>'_SEARCH:forumview:misc:'.strval($forum_id),
		'description'=>$description_text,
	);

	// Is there a question/answer situation?
	$question=get_translated_tempcode($forum_info[0]['f_intro_question'],$GLOBALS['FORUM_DB']);
	if (!$question->is_empty())
	{
		$is_guest=($member_id==$GLOBALS['OCF_DRIVER']->get_guest_id());
		$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_forum_intro_ip','i_ip',array('i_forum_id'=>$forum_id,'i_ip'=>get_ip_address(3)));
		if ((is_null($test)) && (!$is_guest))
		{
			$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_forum_intro_member','i_member_id',array('i_forum_id'=>$forum_id,'i_member_id'=>$member_id));
		}
		if (is_null($test))
		{
			$out['question']=$question;
			$out['answer']=$forum_info[0]['f_intro_answer'];
		}
	}

	if (ocf_may_track_forum($forum_id,$member_id))
	{
		if (!ocf_is_tracking_forum($forum_id))
		{
			$out['may_track_forum']=1;
		} else
		{
			$out['may_untrack_forum']=1;
		}
	}
	if (ocf_may_post_topic($forum_id,$member_id)) $out['may_post_topic']=1;
	if (ocf_may_moderate_forum($forum_id,$member_id))
	{
		$out['may_change_max']=1;
		$out['may_move_topics']=1;
		if (has_specific_permission(get_member(),'multi_delete_topics')) $out['may_delete_topics']=1; // Only super admins can casually delete topics - other staff are expected to trash them. At least deleted posts or trashed topics can be restored!
	}
	return $out;
}


