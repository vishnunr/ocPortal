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
 * @package		polls
 */

/**
 * Handle the poll.
 *
 * @param  boolean			Whether to get the output instead of outputting it directly
 * @param  ?AUTO_LINK		Poll ID (NULL: read from environment)
 * @return ?object			Output (NULL: outputted it already)
 */
function poll_script($ret=false,$param=NULL)
{
	require_lang('polls');
	require_css('polls');

	if (is_null($param)) $param=get_param_integer('param');
	$zone=get_param('zone',get_module_zone('polls'));

	if ($param==-1)
	{
		$rows=persistent_cache_get('POLL');
		if (is_null($rows))
		{
			$rows=$GLOBALS['SITE_DB']->query_select('poll',array('*'),array('is_current'=>1),'ORDER BY id DESC',1);
			persistent_cache_set('POLL',$rows);
		}
	} else
	{
		$rows=$GLOBALS['SITE_DB']->query_select('poll',array('*'),array('id'=>$param),'',1);
	}
	if ((has_actual_page_access(NULL,'cms_polls',NULL,NULL)) && (has_submit_permission('mid',get_member(),get_ip_address(),'cms_polls')))
	{
		$submit_url=build_url(array('page'=>'cms_polls','type'=>'ad','redirect'=>get_self_url(true,false)),get_module_zone('cms_polls'));
	} else $submit_url=new ocp_tempcode();
	if (!array_key_exists(0,$rows))
	{
		$content=do_template('BLOCK_NO_ENTRIES',array('_GUID'=>'fdc85bb2e14bdf00830347e52f25cdac','HIGH'=>true,'TITLE'=>do_lang_tempcode('POLL'),'MESSAGE'=>do_lang_tempcode('NO_ENTRIES'),'ADD_NAME'=>do_lang_tempcode('ADD_POLL'),'SUBMIT_URL'=>$submit_url));
	} else
	{
		$myrow=$rows[0];
		$ip=get_ip_address();

		// Show the poll normally
		$show_poll_results=get_param_integer('show_poll_results_'.strval($myrow['id']),0);
		if ($show_poll_results==0)
		{
			$content=render_poll_box(false,$myrow,$zone);
		} else
		{
			// Voting
			$cast=post_param_integer('cast_'.strval($myrow['id']),-1);
			if ($cast!=-1)
			{
				if (may_vote_in_poll($myrow))
				{
					if (addon_installed('points'))
					{
						require_code('points');
						$_before=point_info(get_member());
						$before=array_key_exists('points_gained_voting',$_before)?$_before['points_gained_voting']:0;
						$GLOBALS['FORUM_DRIVER']->set_custom_field(get_member(),'points_gained_voting',$before+1);
					}
					$GLOBALS['SITE_DB']->query_update('poll',array(('votes'.strval($cast))=>($myrow['votes'.strval($cast)]+1)),array('id'=>$myrow['id']),'',1);

					$GLOBALS['SITE_DB']->query_insert('poll_votes',array(
						'v_poll_id'=>$myrow['id'],
						'v_voter_id'=>get_member(),
						'v_voter_ip'=>$ip,
						'v_vote_for'=>$cast,
					));

					$myrow['votes'.strval($cast)]++;
				}
			} else
			{
				// Viewing the results
				if (may_vote_in_poll($myrow)) // If they do this, they nullify their vote
				{
					$GLOBALS['SITE_DB']->query_insert('poll_votes',array(
						'v_poll_id'=>$myrow['id'],
						'v_voter_id'=>is_guest()?NULL:get_member(),
						'v_voter_ip'=>$ip,
						'v_vote_for'=>NULL,
					));
				}
			}

			// Show poll, with results
			$content=render_poll_box(true,$myrow,$zone);
		}
	}

	if ($ret) return $content;

	// Display
	$echo=do_template('STANDALONE_HTML_WRAP',array('TITLE'=>do_lang_tempcode('POLL'),'FRAME'=>true,'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
	return NULL;
}

/**
 * Find whether the current member may vote.
 *
 * @param  array				The poll row
 * @return boolean			Whether the current member may vote
 */
function may_vote_in_poll($myrow)
{
	if (!has_specific_permission(get_member(),'vote_in_polls','cms_polls')) return false;

	if (get_value('poll_no_member_ip_restrict')==='1')
	{
		if (is_guest())
		{
			return is_null($GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT id FROM '.get_table_prefix().'poll_votes WHERE v_poll_id='.strval($myrow['id']).' AND '.db_string_equal_to('v_voter_ip',get_ip_address())));
		} else
		{
			return is_null($GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT id FROM '.get_table_prefix().'poll_votes WHERE v_poll_id='.strval($myrow['id']).' AND v_voter_id='.strval(get_member())));
		}
	}

	return is_null($GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT id FROM '.get_table_prefix().'poll_votes WHERE v_poll_id='.strval($myrow['id']).' AND (v_voter_id='.strval(get_member()).' OR '.db_string_equal_to('v_voter_ip',get_ip_address()).')'));
}

/**
 * Show an actual poll box.
 *
 * @param  boolean			Whether to show results (if we've already voted, this'll be overrided)
 * @param  array				The poll row
 * @param  ID_TEXT			The zone our poll module is in
 * @return tempcode			The box
 */
function render_poll_box($results,$myrow,$zone='_SEARCH')
{
	$ip=get_ip_address();
	if (!may_vote_in_poll($myrow)) $results=true;

	// Count our total votes
	$num_options=$myrow['num_options'];
	$totalvotes=0;
	for ($i=1;$i<=$num_options;$i++)
	{
		if (!array_key_exists('votes'.strval($i),$myrow)) $myrow['votes'.strval($i)]=0;
		$totalvotes+=$myrow['votes'.strval($i)];
	}

	// Sort by results
	$orderings=array();
	for ($i=1;$i<=$num_options;$i++)
	{
		$orderings[$i]=$myrow['votes'.strval($i)];
	}
	if ($results) asort($orderings);

	$poll_results='show_poll_results_'.strval($myrow['id']);
	$vote_url=get_self_url(false,true,array('poll_id'=>$myrow['id'],$poll_results=>1,'utheme'=>NULL));
	$result_url=$results?'':get_self_url(false,true,array($poll_results=>1,'utheme'=>NULL));
	if (get_param('utheme','')!='')
	{
		if (is_object($result_url))
		{
			if (!$result_url->is_empty()) $result_url->attach('&utheme='.get_param('utheme'));
		} else
		{
			if ($result_url!='') $result_url.='&utheme='.get_param('utheme');
		}
		if (is_object($vote_url))
		{
			if (!$vote_url->is_empty()) $vote_url->attach('&utheme='.get_param('utheme'));
		} else
		{
			if ($vote_url!='') $vote_url.='&utheme='.get_param('utheme');
		}
	}

	// Our questions templated
	$tpl=new ocp_tempcode();
	for ($i=1;$i<=$num_options;$i++)
	{
		$answer=get_translated_tempcode($myrow['option'.strval($i)]);
		$answer_plain=get_translated_text($myrow['option'.strval($i)]);
		if (!$results)
		{
			$tpl->attach(do_template('POLL_ANSWER',array('_GUID'=>'bc9c2e818f2e7031075d8d7b01d79cd5','PID'=>strval($myrow['id']),'I'=>strval($i),'CAST'=>strval($i),'VOTE_URL'=>$vote_url,'ANSWER'=>$answer,'ANSWER_PLAIN'=>$answer_plain)));
		} else
		{
			$votes=$myrow['votes'.strval($i)];
			if (!is_numeric($votes)) $votes=0;
			if ($totalvotes!=0) $width=intval(round(70.0*floatval($votes)/floatval($totalvotes))); else $width=0;
			$tpl->attach(do_template('POLL_ANSWER_RESULT',array('_GUID'=>'887ea0ed090c48305eb84500865e5178','PID'=>strval($myrow['id']),'I'=>strval($i),'VOTE_URL'=>$vote_url,'ANSWER'=>$answer,'ANSWER_PLAIN'=>$answer_plain,'WIDTH'=>strval($width),'VOTES'=>integer_format($votes))));
		}
	}

	if ((has_actual_page_access(NULL,'cms_polls',NULL,NULL)) && (has_submit_permission('mid',get_member(),get_ip_address(),'cms_polls')))
	{
		$submit_url=build_url(array('page'=>'cms_polls','type'=>'ad','redirect'=>running_script('index')?get_self_url(true,true,array()):NULL),get_module_zone('cms_polls'));
	} else $submit_url=new ocp_tempcode();

	// Do our final template
	$question=get_translated_tempcode($myrow['question']);
	$question_plain=get_translated_text($myrow['question']);
	$archive_url=build_url(array('page'=>'polls','type'=>'misc'),$zone);
	$full_url=new ocp_tempcode();
	if ((get_page_name()!='polls') || (get_param('type','')!='view'))
		$full_url=build_url(array('page'=>'polls','type'=>'view','id'=>$myrow['id']),$zone);
	$map2=array('_GUID'=>'4c6b026f7ed96f0b5b8408eb5e5affb5','VOTE_URL'=>$vote_url,'SUBMITTER'=>strval($myrow['submitter']),'PID'=>strval($myrow['id']),'FULL_URL'=>$full_url,'CONTENT'=>$tpl,'QUESTION'=>$question,'QUESTION_PLAIN'=>$question_plain,'SUBMIT_URL'=>$submit_url,'ARCHIVE_URL'=>$archive_url,'RESULT_URL'=>$result_url,'ZONE'=>$zone);
	if ((get_option('is_on_comments')=='1') && (!has_no_forum()) && ($myrow['allow_comments']>=1)) $map2['COMMENT_COUNT']='1';
	return do_template('POLL_BOX',$map2);
}

/**
 * Add a new poll to the database, then return the ID of the new entry.
 *
 * @param  SHORT_TEXT		The question
 * @param  SHORT_TEXT		The first choice
 * @range  1 max
 * @param  SHORT_TEXT		The second choice
 * @range  1 max
 * @param  SHORT_TEXT		The third choice (blank means not a choice)
 * @param  SHORT_TEXT		The fourth choice (blank means not a choice)
 * @param  SHORT_TEXT		The fifth choice (blank means not a choice)
 * @param  SHORT_TEXT		The sixth choice (blank means not a choice)
 * @param  SHORT_TEXT		The seventh choice (blank means not a choice)
 * @param  SHORT_TEXT		The eighth choice (blank means not a choice)
 * @param  SHORT_TEXT		The ninth choice (blank means not a choice)
 * @param  SHORT_TEXT		The tenth choice (blank means not a choice)
 * @param  integer			The number of choices
 * @range  2 5
 * @param  BINARY				Whether the poll is the current poll
 * @param  BINARY				Whether to allow rating of this poll
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether to allow trackbacking on this poll
 * @param  LONG_TEXT			Notes about this poll
 * @param  ?TIME				The time the poll was submitted (NULL: now)
 * @param  ?MEMBER			The member who submitted (NULL: the current member)
 * @param  ?TIME				The time the poll was put to use (NULL: not put to use yet)
 * @param  integer			How many have voted for option 1
 * @range  0 max
 * @param  integer			How many have voted for option 2
 * @range  0 max
 * @param  integer			How many have voted for option 3
 * @range  0 max
 * @param  integer			How many have voted for option 4
 * @range  0 max
 * @param  integer			How many have voted for option 5
 * @range  0 max
 * @param  integer			How many have voted for option 6
 * @range  0 max
 * @param  integer			How many have voted for option 7
 * @range  0 max
 * @param  integer			How many have voted for option 8
 * @range  0 max
 * @param  integer			How many have voted for option 9
 * @range  0 max
 * @param  integer			How many have voted for option 10
 * @range  0 max
 * @param  integer			The number of views had
 * @param  ?TIME				The edit date (NULL: never)
 * @return AUTO_LINK			The poll ID of our new poll
 */
function add_poll($question,$a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10,$num_options,$current,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$time=NULL,$submitter=NULL,$use_time=NULL,$v1=0,$v2=0,$v3=0,$v4=0,$v5=0,$v6=0,$v7=0,$v8=0,$v9=0,$v10=0,$views=0,$edit_date=NULL)
{
	if ($current==1)
	{
		persistent_cache_delete('POLL');
		$GLOBALS['SITE_DB']->query_update('poll',array('is_current'=>0),array('is_current'=>1),'',1);
	}

	if (is_null($time)) $time=time();
	if (is_null($submitter)) $submitter=get_member();

	$map=array(
		'edit_date'=>$edit_date,
		'poll_views'=>$views,
		'add_time'=>$time,
		'allow_trackbacks'=>$allow_trackbacks,
		'allow_rating'=>$allow_rating,
		'allow_comments'=>$allow_comments,
		'notes'=>$notes,
		'submitter'=>$submitter,
		'date_and_time'=>$use_time,
		'votes1'=>$v1,
		'votes2'=>$v2,
		'votes3'=>$v3,
		'votes4'=>$v4,
		'votes5'=>$v5,
		'votes6'=>$v6,
		'votes7'=>$v7,
		'votes8'=>$v8,
		'votes9'=>$v9,
		'votes10'=>$v10,
		'num_options'=>$num_options,
		'is_current'=>$current,
	);
	$map+=insert_lang_comcode('question',$question,1);
	$map+=insert_lang_comcode('option1',$a1,1);
	$map+=insert_lang_comcode('option2',$a2,1);
	$map+=insert_lang_comcode('option3',$a3,1);
	$map+=insert_lang_comcode('option4',$a4,1);
	$map+=insert_lang_comcode('option5',$a5,1);
	$map+=insert_lang_comcode('option6',$a6,1);
	$map+=insert_lang_comcode('option7',$a7,1);
	$map+=insert_lang_comcode('option8',$a8,1);
	$map+=insert_lang_comcode('option9',$a9,1);
	$map+=insert_lang_comcode('option10',$a10,1);
	$id=$GLOBALS['SITE_DB']->query_insert('poll',$map,true);

	log_it('ADD_POLL',strval($id),$question);

	return $id;
}

/**
 * Edit a poll.
 *
 * @param  AUTO_LINK			The ID of the poll to edit
 * @param  SHORT_TEXT		The question
 * @param  SHORT_TEXT		The first choice
 * @range  1 max
 * @param  SHORT_TEXT		The second choice
 * @range  1 max
 * @param  SHORT_TEXT		The third choice (blank means not a choice)
 * @param  SHORT_TEXT		The fourth choice (blank means not a choice)
 * @param  SHORT_TEXT		The fifth choice (blank means not a choice)
 * @param  SHORT_TEXT		The sixth choice (blank means not a choice)
 * @param  SHORT_TEXT		The seventh choice (blank means not a choice)
 * @param  SHORT_TEXT		The eighth choice (blank means not a choice)
 * @param  SHORT_TEXT		The ninth choice (blank means not a choice)
 * @param  SHORT_TEXT		The tenth choice (blank means not a choice)
 * @param  integer			The number of choices
 * @param  BINARY				Whether to allow rating of this poll
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether to allow trackbacking on this poll
 * @param  LONG_TEXT			Notes about this poll
 */
function edit_poll($id,$question,$a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10,$num_options,$allow_rating,$allow_comments,$allow_trackbacks,$notes)
{
	log_it('EDIT_POLL',strval($id),$question);

	persistent_cache_delete('POLL');

	$rows=$GLOBALS['SITE_DB']->query_select('poll',array('*'),array('id'=>$id),'',1);
	$_question=$rows[0]['question'];
	$_a1=$rows[0]['option1'];
	$_a2=$rows[0]['option2'];
	$_a3=$rows[0]['option3'];
	$_a4=$rows[0]['option4'];
	$_a5=$rows[0]['option5'];
	$_a6=$rows[0]['option6'];
	$_a7=$rows[0]['option7'];
	$_a8=$rows[0]['option8'];
	$_a9=$rows[0]['option9'];
	$_a10=$rows[0]['option10'];

	$GLOBALS['SITE_DB']->query_update('poll',array('edit_date'=>time(),'allow_rating'=>$allow_rating,'allow_comments'=>$allow_comments,'allow_trackbacks'=>$allow_trackbacks,'notes'=>$notes,'num_options'=>$num_options,'question'=>lang_remap_comcode($_question,$question),'option1'=>lang_remap_comcode($_a1,$a1),'option2'=>lang_remap_comcode($_a2,$a2),'option3'=>lang_remap_comcode($_a3,$a3),'option4'=>lang_remap_comcode($_a4,$a4),'option5'=>lang_remap_comcode($_a5,$a5),'option6'=>lang_remap_comcode($_a6,$a6),'option7'=>lang_remap_comcode($_a7,$a7),'option8'=>lang_remap_comcode($_a8,$a8),'option9'=>lang_remap_comcode($_a9,$a9),'option10'=>lang_remap_comcode($_a10,$a10)),array('id'=>$id),'',1);
	decache('main_poll');

	require_code('urls2');
	suggest_new_idmoniker_for('polls','view',strval($id),$question);

	require_code('feedback');
	update_spacer_post(
		$allow_comments!=0,
		'polls',
		strval($id),
		build_url(array('page'=>'polls','type'=>'view','id'=>$id),get_module_zone('polls'),NULL,false,false,true),
		$question,
		find_overridden_comment_forum('polls')
	);
}

/**
 * Delete a poll.
 *
 * @param  AUTO_LINK		The ID of the poll to delete
 */
function delete_poll($id)
{
	$rows=$GLOBALS['SITE_DB']->query_select('poll',array('*'),array('id'=>$id),'',1);

	persistent_cache_delete('POLL');

	if (addon_installed('catalogues'))
	{
		update_catalogue_content_ref('poll',strval($id),'');
	}

	$question=get_translated_text($rows[0]['question']);
	log_it('DELETE_POLL',strval($id),$question);

	delete_lang($rows[0]['question']);
	for ($i=1;$i<=10;$i++)
	{
		delete_lang($rows[0]['option'.strval($i)]);
	}

	$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>'polls','rating_for_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'polls','trackback_for_id'=>$id));
	require_code('notifications');
	delete_all_notifications_on('comment_posted','polls_'.strval($id));

	$GLOBALS['SITE_DB']->query_delete('poll',array('id'=>$id),'',1);
}

/**
 * Set the poll.
 *
 * @param  AUTO_LINK		The poll ID to set
 */
function set_poll($id)
{
	persistent_cache_delete('POLL');

	$rows=$GLOBALS['SITE_DB']->query_select('poll',array('question','submitter'),array('id'=>$id));
	$question=$rows[0]['question'];
	$submitter=$rows[0]['submitter'];

	log_it('CHOOSE_POLL',strval($id),get_translated_text($question));

	if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'polls'))
		syndicate_described_activity('polls:ACTIVITY_CHOOSE_POLL',get_translated_text($question),'','','_SEARCH:polls:view:'.strval($id),'','','polls');

	if ((!is_guest($submitter)) && (addon_installed('points')))
	{
		require_code('points2');
		$_points_chosen=get_option('points_CHOOSE_POLL');
		if (is_null($_points_chosen)) $points_chosen=35; else $points_chosen=intval($_points_chosen);
		if ($points_chosen!=0)
			system_gift_transfer(do_lang('POLL'),$points_chosen,$submitter);
	}

	$GLOBALS['SITE_DB']->query_update('poll',array('is_current'=>0),array('is_current'=>1));
	$GLOBALS['SITE_DB']->query_update('poll',array('is_current'=>1,'date_and_time'=>time()),array('id'=>$id),'',1);
	decache('main_poll');

	require_lang('polls');
	require_code('notifications');
	$subject=do_lang('POLL_CHOSEN_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$question);
	$poll_url=build_url(array('page'=>'polls','type'=>'view','id'=>$id),get_module_zone('polls'),NULL,false,false,true);
	$mail=do_lang('POLL_CHOSEN_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape(get_translated_text($question)),$poll_url->evaluate());
	dispatch_notification('poll_chosen',NULL,$subject,$mail);
}

/**
 * Get a list of polls.
 *
 * @param  ?AUTO_LINK	The ID of the poll to select by default (NULL: first)
 * @param  ?MEMBER		Only show polls owned by this member (NULL: no such restriction)
 * @return tempcode		The list
 */
function nice_get_polls($it=NULL,$only_owned=NULL)
{
	$where=is_null($only_owned)?NULL:array('submitter'=>$only_owned);
	$rows=$GLOBALS['SITE_DB']->query_select('poll',array('question','is_current','votes1','votes2','votes3','votes4','votes5','votes6','votes7','votes8','votes9','votes10','id'),$where,'ORDER BY is_current DESC,date_and_time,question',400);
	if (count($rows)==400) // Ok, just new ones
	{
		if (is_null($where)) $where=array();
		$rows=$GLOBALS['SITE_DB']->query_select('poll',array('question','is_current','votes1','votes2','votes3','votes4','votes5','votes6','votes7','votes8','votes9','votes10','id'),$where+array('date_and_time'=>NULL),'ORDER BY add_time DESC',400);
	}
	$out=new ocp_tempcode();
	foreach ($rows as $myrow)
	{
		$selected=!is_null($it);

		if ($myrow['is_current']==1)
		{
			$status=do_lang_tempcode('CURRENT');
			if (is_null($it)) $selected=true;
		}
		else
		{
			// If people have voted the IP field will have something in it. So we can tell if its new or not from this
			if ($myrow['votes1']+$myrow['votes2']+$myrow['votes3']+$myrow['votes4']+$myrow['votes5']+$myrow['votes6']+$myrow['votes7']+$myrow['votes8']+$myrow['votes9']+$myrow['votes10']!=0)
				$status=do_lang_tempcode('USED_PREVIOUSLY');
			else $status=do_lang_tempcode('NOT_USED_PREVIOUSLY');
		}
		$text=do_template('POLL_LIST_ENTRY',array('_GUID'=>'dadf669bca2add9b79329b21e45d1010','QUESTION'=>get_translated_text($myrow['question']),'STATUS'=>$status));
		$out->attach(form_input_list_entry(strval($myrow['id']),$selected,$text));
	}

	return $out;
}


