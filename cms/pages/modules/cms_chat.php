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
 * @package		chat
 */

/**
 * Module page class.
 */
class Module_cms_chat
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Philip Withnall';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=3;
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
		return array('misc'=>'CHAT_MOD_PANEL');
	}

	/**
	 * Standard modular privilege-overide finder function.
	 *
	 * @return array	A map of privileges that are overridable; sp to 0 or 1. 0 means "not category overridable". 1 means "category overridable".
	 */
	function get_sp_overrides()
	{
		require_lang('chat');
		return array('edit_lowrange_content'=>array(1,'MODERATE_CHATROOMS'));
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/forums';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_chat';

		require_lang('chat');
		require_code('chat');
		require_code('chat2');
		require_css('chat');

		$type=get_param('type','misc');

		if ($type=='ban') return $this->chat_ban();
		if ($type=='unban') return $this->chat_unban();
		if ($type=='room') return $this->moderate_chat_room();
		if ($type=='delete') return $this->chat_delete_all_messages();
		if ($type=='_delete') return $this->_chat_delete_all_messages();
		if ($type=='mass_delete') return $this->_chat_delete_many_messages();
		if ($type=='ed') return $this->chat_edit_message();
		if ($type=='_ed') return $this->_chat_edit_message();
		if ($type=='misc') return $this->chat_choose_room();

		return new ocp_tempcode();
	}

	/**
	 * The main user interface for choosing a chat room to moderate.
	 *
	 * @return tempcode	The UI.
	 */
	function chat_choose_room()
	{
		if (has_actual_page_access(get_member(),'admin_chat'))
		{
			require_lang('menus');
			$also_url=build_url(array('page'=>'admin_chat'),get_module_zone('admin_chat'));
			attach_message(do_lang_tempcode('ALSO_SEE_CMS',escape_html($also_url->evaluate())),'inform');
		}

		$title=get_page_title('CHAT_MOD_PANEL');
		$introtext=do_lang_tempcode('CHAT_PANEL_INTRO');

		breadcrumb_set_self(do_lang_tempcode('CHOOSE'));

		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);
		$sortables=array('room_name'=>do_lang_tempcode('ROOM_NAME'),'messages'=>do_lang_tempcode('MESSAGES'));
		$test=explode(' ',either_param('sort','room_name DESC'));
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';
		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('ROOM_NAME'),do_lang_tempcode('ROOM_OWNER'),do_lang_tempcode('ROOM_LANG'),do_lang_tempcode('MESSAGES')),$sortables,'sort',$sortable.' '.$sort_order);

		$max_rows=$GLOBALS['SITE_DB']->query_value('chat_rooms','COUNT(*)',array('is_im'=>0));
		$sort_clause=($sortable=='room_name')?('ORDER BY room_name '.$sort_order):'';
		$rows=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('is_im'=>0),$sort_clause,$max,$start);
		if ($sortable=='messages')
		{
			usort($rows,array('Module_cms_chat','_sort_chat_browse_rows'));
			if ($sort_order=='DESC') $rows=array_reverse($rows);
		}

		$fields=new ocp_tempcode();
		foreach ($rows as $row)
		{
			$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$row['id']))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
			if ((!handle_chatroom_pruning($row)) && ($has_mod_access))
			{
				$url=build_url(array('page'=>'_SELF','type'=>'room','id'=>$row['id']),'_SELF');
				$messages=$GLOBALS['SITE_DB']->query_value('chat_messages','COUNT(*)',array('room_id'=>$row['id']));
				$username=$GLOBALS['FORUM_DRIVER']->get_username($row['room_owner']);
				if (is_null($username)) $username='';//do_lang('UNKNOWN');
				$fields->attach(results_entry(array(hyperlink($url,escape_html($row['room_name'])),escape_html($username),escape_html($row['room_language']),escape_html(integer_format($messages)))));
			}
		}
		if ($fields->is_empty()) inform_exit(do_lang_tempcode('NO_CATEGORIES'));

		$results_table=results_table(do_lang_tempcode('ROOMS'),$start,'start',$max,'max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort');
		return do_template('CHAT_MODERATE_SCREEN',array('_GUID'=>'c59cb6c8409d0e678b05628d92e423db','TITLE'=>$title,'INTRODUCTION'=>$introtext,'CONTENT'=>$results_table,'LINKS'=>array()));
	}

	/**
	 * Sort chatroom rows (callback).
	 *
	 * @param  array		First row.
	 * @param  array		Second row.
	 * @return integer	Sorting code.
	 */
	function _sort_chat_browse_rows($a,$b)
	{
		$messages_a=$GLOBALS['SITE_DB']->query_value('chat_messages','COUNT(*)',array('room_id'=>$a['id']));
		$messages_b=$GLOBALS['SITE_DB']->query_value('chat_messages','COUNT(*)',array('room_id'=>$b['id']));
		if ($messages_a<$messages_b) return (-1);
		elseif ($messages_a==$messages_b) return 0;
		else return 1;
	}

	/**
	 * The main user interface for moderating a chat room.
	 *
	 * @return tempcode	The UI.
	 */
	function moderate_chat_room()
	{
		$title=get_page_title('CHAT_MOD_PANEL');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE'))));

		$room_id=get_param_integer('id');
		check_chatroom_access($room_id);
		$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$room_id),'',1);
		if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$room_details[0];
		$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$room_id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
		if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');

		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);
		$sortables=array('date_and_time'=>do_lang_tempcode('DATE_TIME'),'user_id'=>do_lang_tempcode('MEMBER'));
		$test=explode(' ',get_param('sort','date_and_time DESC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';
		$max_rows=$GLOBALS['SITE_DB']->query_value('chat_messages','COUNT(*)',array('room_id'=>$room_id));
		$rows=$GLOBALS['SITE_DB']->query_select('chat_messages',array('*'),array('room_id'=>$room_id),'ORDER BY '.$sortable.' '.$sort_order,$max,$start);
		$fields=new ocp_tempcode();
		require_code('templates_results_table');
		$array=array(do_lang_tempcode('MEMBER'),do_lang_tempcode('DATE_TIME'),do_lang_tempcode('MESSAGE')/*,do_lang_tempcode('CHAT_OPTIONS_COLOUR_NAME'),do_lang_tempcode('CHAT_OPTIONS_TEXT_NAME')*/);
		if (has_js())
			$array[]=do_lang_tempcode('DELETE');
		$fields_title=results_field_title($array,$sortables,'sort',$sortable.' '.$sort_order);
		foreach ($rows as $myrow)
		{
			$url=build_url(array('page'=>'_SELF','type'=>'ed','room_id'=>$room_id,'id'=>$myrow['id']),'_SELF');

			$username=$GLOBALS['FORUM_DRIVER']->get_username($myrow['user_id']);
			if (is_null($username)) $username='';//do_lang('UNKNOWN');

			$message=get_translated_tempcode($myrow['the_message']);

			$link_time=hyperlink($url,escape_html(get_timezoned_date($myrow['date_and_time'])));

			$_row=array($GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($GLOBALS['FORUM_DRIVER']->get_member_from_username($username),false,$username),escape_html($link_time),$message/*,escape_html($myrow['text_colour']),escape_html($myrow['font_name'])*/);
			if (has_js())
			{
				$deletion_tick=do_template('RESULTS_TABLE_TICK',array('ID'=>strval($myrow['id'])));
				$_row[]=$deletion_tick;
			}

			$fields->attach(results_entry($_row));
		}
		if ($fields->is_empty())
		{
			if ($start!=0) // Go back a page, because we might have come here after deleting
			{
				$_GET['start']=strval(max(0,$start-$max));
				return $this->moderate_chat_room();
			}
			inform_exit(do_lang_tempcode('NO_ENTRIES'));
		}

		$content=results_table(do_lang_tempcode('MESSAGES'),$start,'start',$max,'max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort');

		$mod_link=hyperlink(build_url(array('page'=>'_SELF','type'=>'delete','stage'=>0,'id'=>$room_id),'_SELF'),do_lang_tempcode('DELETE_ALL_MESSAGES'));
		$view_link=hyperlink(build_url(array('page'=>'chat','type'=>'room','id'=>$room_id),get_module_zone('chat')),do_lang_tempcode('VIEW'));
		$logs_link=hyperlink(build_url(array('page'=>'chat','type'=>'download_logs','id'=>$room_id),get_module_zone('chat')),do_lang_tempcode('CHAT_DOWNLOAD_LOGS'));
		$links=array($mod_link,$view_link,$logs_link);

		$delete_url=build_url(array('page'=>'_SELF','type'=>'mass_delete','room_id'=>$room_id,'start'=>$start,'max'=>$max),'_SELF');

		return do_template('CHAT_MODERATE_SCREEN',array('_GUID'=>'940de7e8c9a0ac3c575892887c7ef3c0','URL'=>$delete_url,'TITLE'=>$title,'INTRODUCTION'=>'','CONTENT'=>$content,'LINKS'=>$links));
	}

	/**
	 * The actualiser for banning a chatter.
	 *
	 * @return tempcode	The UI.
	 */
	function chat_ban()
	{
		$title=get_page_title('CHAT_BAN');

		$id=get_param_integer('id');

		$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$id),'',1);
		if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$room_details[0];
		$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
		if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');
		check_specific_permission('ban_chatters_from_rooms');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE')),array('_SELF:_SELF:room:id='.strval($id),do_lang_tempcode('CHAT_MOD_PANEL'))));

		$member_id=post_param_integer('member_id',NULL);
		if (is_null($member_id))
		{
			$member_id=get_param_integer('member_id');
			$confirm_needed=true;
		} else $confirm_needed=false;

		if (is_guest($member_id))
			warn_exit(do_lang_tempcode('CHAT_BAN_GUEST'));

		if ($member_id==get_member())
			warn_exit(do_lang_tempcode('CHAT_BAN_YOURSELF'));

		$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id);
		if (is_null($username)) $username=do_lang('UNKNOWN');

		if ($confirm_needed)
		{
			$hidden=form_input_hidden('member_id',strval($member_id));
			return do_template('YESNO_SCREEN',array('_GUID'=>'7d04bebbac2c49be4458afdbf5619dc7','TITLE'=>$title,'TEXT'=>do_lang_tempcode('Q_SURE_BAN',escape_html($username)),'URL'=>get_self_url(),'HIDDEN'=>$hidden));
		}

		chatroom_ban_to($member_id,$id);

		return inform_screen($title,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The actualiser for unbanning a chatter.
	 *
	 * @return tempcode	The UI.
	 */
	function chat_unban()
	{
		$title=get_page_title('CHAT_UNBAN');

		$id=get_param_integer('id');

		$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$id),'',1);
		if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$room_details[0];
		$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
		if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');
		check_specific_permission('ban_chatters_from_rooms');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE')),array('_SELF:_SELF:room:id='.strval($id),do_lang_tempcode('CHAT_MOD_PANEL'))));

		$member_id=post_param_integer('member_id',NULL);
		if (is_null($member_id))
		{
			$member_id=get_param_integer('member_id');
			$confirm_needed=true;
		} else $confirm_needed=false;

		$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id);
		if (is_null($username)) $username=do_lang('UNKNOWN');

		if ($confirm_needed)
		{
			$hidden=form_input_hidden('member_id',strval($member_id));
			return do_template('YESNO_SCREEN',array('TITLE'=>$title,'TEXT'=>do_lang_tempcode('Q_SURE_UNBAN',escape_html($username)),'URL'=>get_self_url(),'HIDDEN'=>$hidden));
		}

		chatroom_unban_to($member_id,$id);

		return inform_screen($title,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI for editing a message.
	 *
	 * @return tempcode	The UI.
	 */
	function chat_edit_message()
	{
		$title=get_page_title('EDIT_MESSAGE');

		$id=get_param_integer('id');

		$rows=$GLOBALS['SITE_DB']->query_select('chat_messages',array('*'),array('id'=>$id),'',1);
		if (!array_key_exists(0,$rows))
		{
			return warn_screen($title,do_lang_tempcode('MISSING_RESOURCE'));
		}
		$myrow=$rows[0];

		$room_id=$myrow['room_id'];
		check_chatroom_access($room_id);

		$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$room_id),'',1);
		if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$room_details[0];
		$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$room_id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
		if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');

		//$post_url=build_url(array('page'=>'_SELF','type'=>'delete','id'=>$myrow['id'],'room_id'=>get_param_integer('room_id',db_get_first_id())),'_SELF');

		//$edit_form=do_template('FORM',array('_GUID'=>'da2ac49bc6af253b36e2855763ad8ae3','HIDDEN'=>'','TEXT'=>paragraph(do_lang_tempcode('DESCRIPTION_DELETE_MESSAGE')),'FIELDS'=>'','SUBMIT_NAME'=>do_lang_tempcode('DELETE_MESSAGE'),'URL'=>$post_url));

		$post_url=build_url(array('page'=>'_SELF','type'=>'_ed','id'=>$myrow['id'],'room_id'=>$room_id),'_SELF');

		$message=get_translated_tempcode($myrow['the_message']);

		require_code('form_templates');

		$text_colour=($myrow['text_colour']=='')?get_option('chat_default_post_colour'):$myrow['text_colour'];
		$font_name=($myrow['font_name']=='')?get_option('chat_default_post_font'):$myrow['font_name'];

		$fields=form_input_text_comcode(do_lang_tempcode('MESSAGE'),do_lang_tempcode('DESCRIPTION_MESSAGE'),'message',$message->evaluate(),true);
		$fields->attach(form_input_line(do_lang_tempcode('CHAT_OPTIONS_COLOUR_NAME'),do_lang_tempcode('CHAT_OPTIONS_COLOUR_DESCRIPTION'),'textcolour',$text_colour,false));
		//$fields->attach(do_template('EDIT_CSS_ENTRY',array('_GUID'=>'d65449d25908252cc7f34c19ee5e8747','COLOR'=>'#'.$myrow['text_colour'],'NAME'=>do_lan g_tempcode('TEXTCOLOUR'),'CONTEXT'=>do_l ang_tempcode('DESCRIPTION_TEXTCOLOUR'))));
		$fields->attach(form_input_line(do_lang_tempcode('CHAT_OPTIONS_TEXT_NAME'),do_lang_tempcode('CHAT_OPTIONS_TEXT_DESCRIPTION'),'fontname',$font_name,false));
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('ACTIONS'))));
		$fields->attach(form_input_tick(do_lang_tempcode('DELETE'),do_lang_tempcode('DESCRIPTION_DELETE_MESSAGE'),'delete',false));
		//	$fields->attach(do_template('COLOUR_CHOOSER',array('_GUID'=>'817dbcdb419982774f86b8e5a0c5c1fe','NAME'=>$myrow['id'],'CONTEXT'=>do_l ang_tempcode('DESCRIPTION_TEXTCOLOUR'),'COLOR'=>$myrow['text_colour'])));

		//$edit_form->attach(do_template('FORM',array('_GUID'=>'43651e089d7ec45e1468ae57e3bf315e','HIDDEN'=>'','TEXT'=>'','FIELDS'=>$fields,'SUBMIT_NAME'=>do_lang_tempcode('EDIT_MESSAGE'),'URL'=>$post_url)));

		//return do_template('CHAT_PANEL',array('_GUID'=>'6278e2571f20ad1b9becd12e007caac2','TITLE'=>$title,'INTRODUCTION'=>'','CONTENT'=>$edit_form));

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE')),array('_SELF:_SELF:room:id='.strval($room_id),do_lang_tempcode('CHAT_MOD_PANEL'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'bf92ecd4d5f923f78bbed4faca6c0cb6','HIDDEN'=>'','TITLE'=>$title,'TEXT'=>'','FIELDS'=>$fields,'URL'=>$post_url,'SUBMIT_NAME'=>do_lang_tempcode('SAVE')));
	}

	/**
	 * The actualiser for editing a message.
	 *
	 * @return tempcode	The UI.
	 */
	function _chat_edit_message()
	{
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		$delete=post_param_integer('delete',0);
		if ($delete==1)
		{
			return $this->_chat_delete_message();
		}
		else
		{
			$message_id=get_param_integer('id');

			$room_id=$GLOBALS['SITE_DB']->query_value_null_ok('chat_messages','room_id',array('id'=>$message_id));
			if (is_null($room_id)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
			check_chatroom_access($room_id);

			$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$room_id),'',1);
			if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
			$row=$room_details[0];
			$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$room_id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
			if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');

			$title=get_page_title('EDIT_MESSAGE');
			$_message_parsed=insert_lang_comcode(wordfilter_text(post_param('message')),4);
			$GLOBALS['SITE_DB']->query_update('chat_messages',array('the_message'=>$_message_parsed,'text_colour'=>post_param('textcolour'),'font_name'=>post_param('fontname')),array('id'=>$message_id),'',1);

			log_it('EDIT_MESSAGE',strval($message_id),post_param('message'));

			decache('side_shoutbox');

			require_code('templates_donext');
			return do_next_manager($title,do_lang_tempcode('SUCCESS'),
						NULL,
						NULL,
						/*		TYPED-ORDERED LIST OF 'LINKS'		*/
						/*	 page	 params				  zone	  */
						NULL,																						 // Add one
						array('_SELF',array('type'=>'ed','id'=>$message_id,'room_id'=>$room_id),'_SELF'),	 // Edit this
						array('_SELF',array('type'=>'room','id'=>$room_id),'_SELF'),											  // Edit one
						NULL,	// View this
						array('_SELF',array(),'_SELF'),											 // View archive
						NULL,																						// Add to category
						NULL,																						// Add one category
						NULL,																						// Edit one category
						NULL,																						// Edit this category
						NULL,																							// View this category
						/*	  SPECIALLY TYPED 'LINKS'				  */
						array(),
						array(),
						array(
							/*	 type							  page	 params													 zone	  */
							has_actual_page_access(get_member(),'admin_chat')?array('chatrooms',array('admin_chat',array('type'=>'misc'),get_module_zone('admin_chat')),do_lang('ROOMS')):NULL,
						),
						do_lang('SETUP')
			);
		}
	}

	/**
	 * The actualiser for deleting a message.
	 *
	 * @return tempcode	The UI.
	 */
	function _chat_delete_message()
	{
		$title=get_page_title('DELETE_MESSAGE');

		$message_id=get_param_integer('id');

		$rows=$GLOBALS['SITE_DB']->query_select('chat_messages',array('the_message','room_id'),array('id'=>$message_id));
		if (!array_key_exists(0,$rows))
		{
			return warn_screen($title,do_lang_tempcode('MISSING_RESOURCE'));
		}
		$myrow=$rows[0];
		$message=$myrow['the_message'];

		$room_id=$myrow['room_id'];
		check_chatroom_access($room_id);

		$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$room_id),'',1);
		if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$room_details[0];
		$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$room_id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
		if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');

		$GLOBALS['SITE_DB']->query_delete('chat_messages',array('id'=>$message_id),'',1);

		decache('side_shoutbox');

		$message2=get_translated_tempcode($message);
		delete_lang($message);

		log_it('DELETE_MESSAGE',strval($message_id),$message2->evaluate());

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE')),array('_SELF:_SELF:room:id='.strval($room_id),do_lang_tempcode('CHAT_MOD_PANEL'))));

		require_code('templates_donext');
		return do_next_manager($title,do_lang_tempcode('SUCCESS'),
					NULL,
					NULL,
					/*		TYPED-ORDERED LIST OF 'LINKS'		*/
					/*	 page	 params				  zone	  */
					NULL,																						 // Add one
					NULL,	 // Edit this
					array('_SELF',array('type'=>'room','id'=>$room_id),'_SELF'),											  // Edit one
					NULL,	// View this
					array('_SELF',array(),'_SELF'),											 // View archive
					NULL,																						// Add to category
					NULL,																						// Add one category
					NULL,																						// Edit one category
					NULL,																						// Edit this category
					NULL,																							// View this category
					/*	  SPECIALLY TYPED 'LINKS'				  */
					array(
						/*	 type							  page	 params													 zone	  */
						has_actual_page_access(get_member(),'admin_chat')?array('chatrooms',array('admin_chat',array('type'=>'misc'),get_module_zone('admin_chat')),do_lang('SETUP')):NULL,
					)
		);
	}

	/**
	 * The UI for deleting all the messages in a room.
	 *
	 * @return tempcode	The UI.
	 */
	function chat_delete_all_messages()
	{
		$title=get_page_title('DELETE_ALL_MESSAGES');

		$id=get_param_integer('id');
		check_chatroom_access($id);

		$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$id),'',1);
		if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$room_details[0];
		$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
		if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');

		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_tick(do_lang_tempcode('PROCEED'),do_lang_tempcode('Q_SURE'),'continue_delete',false));
		$text=paragraph(do_lang_tempcode('CONFIRM_DELETE_ALL_MESSAGES',escape_html(get_chatroom_name($id))));
		$post_url=build_url(array('page'=>'_SELF','type'=>'_delete','id'=>$id),'_SELF');
		$submit_name=do_lang_tempcode('DELETE');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE')),array('_SELF:_SELF:room:id='.strval($id),do_lang_tempcode('CHAT_MOD_PANEL'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'31b488e5d4ff52ffd5e097876c0b13c7','SKIP_VALIDATION'=>true,'HIDDEN'=>'','TITLE'=>$title,'URL'=>$post_url,'FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name,'TEXT'=>$text));
	}

	/**
	 * The actualiser for deleting all the messages in a room.
	 *
	 * @return tempcode	The UI.
	 */
	function _chat_delete_all_messages()
	{
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		$delete=post_param_integer('continue_delete',0);
		if ($delete!=1)
		{
			return $this->chat_choose_room();
		}
		else
		{
			$title=get_page_title('DELETE_ALL_MESSAGES');
			//Delete all the posts in the specified room
			//delete_chatroom_messages(get_param_integer('room_id'));

			$room_id=get_param_integer('id');
			check_chatroom_access($room_id);

			$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$room_id),'',1);
			if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
			$row=$room_details[0];
			$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$room_id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
			if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');

			delete_chat_messages(array('room_id'=>$room_id));

			decache('side_shoutbox');

			log_it('DELETE_ALL_MESSAGES',strval($room_id));

			// Redirect
			$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
			return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
		}
	}

	/**
	 * The actualiser for deleting all the ticked messages in a room.
	 *
	 * @return tempcode	The UI.
	 */
	function _chat_delete_many_messages()
	{
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		$title=get_page_title('DELETE_SOME_MESSAGES');

		$room_id=get_param_integer('room_id');
		check_chatroom_access($room_id);

		$room_details=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id'=>$room_id),'',1);
		if (!array_key_exists(0,$room_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$room_details[0];
		$has_mod_access=((has_specific_permission(get_member(),'edit_lowrange_content','cms_chat',array('chat',$room_id))) || ($row['room_owner']==get_member()) && (has_specific_permission(get_member(),'moderate_my_private_rooms')));
		if (!$has_mod_access) access_denied('SPECIFIC_PERMISSION','edit_lowrange_content');

		// Actualiser
		$count=0;
		foreach (array_keys($_REQUEST) as $key)
		{
			if (substr($key,0,4)=='del_')
			{
				delete_chat_messages(array('room_id'=>$room_id,'id'=>intval(substr($key,4))));
				$count++;
			}
		}

		if ($count==0) warn_exit(do_lang_tempcode('NOTHING_SELECTED'));

		decache('side_shoutbox');

		$num_remaining=$GLOBALS['SITE_DB']->query_value('chat_messages','COUNT(*)',array('room_id'=>$room_id));
		if ($num_remaining==0)
		{
			$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		} else
		{
			$url=build_url(array('page'=>'_SELF','type'=>'room','id'=>$room_id,'start'=>get_param_integer('start'),'max'=>get_param_integer('max')),'_SELF');
		}

		// Redirect
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

}


