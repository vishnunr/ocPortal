<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		welcome_emails
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_ocf_welcome_emails extends standard_crud_module
{
	var $lang_type='WELCOME_EMAIL';
	var $select_name='SUBJECT';
	var $select_name_description='DESCRIPTION_WELCOME_EMAIL_SUBJECT';
	var $menu_label='WELCOME_EMAILS';
	var $orderer='w_name';
	var $title_is_multi_lang=false;

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
		$info['version']=4;
		$info['locked']=true;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;
		$GLOBALS['SITE_DB']->drop_table_if_exists('f_welcome_emails');
		$GLOBALS['NO_DB_SCOPE_CHECK']=false;
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;

		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('f_welcome_emails',array(
				'id'=>'*AUTO',
				'w_name'=>'SHORT_TEXT',
				'w_subject'=>'SHORT_TRANS',
				'w_text'=>'LONG_TRANS',
				'w_send_time'=>'INTEGER',
				'w_newsletter'=>'?AUTO_LINK',
				'w_usergroup'=>'?AUTO_LINK',
				'w_usergroup_type'=>'ID_TEXT',
			));
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<4))
		{
			$GLOBALS['SITE_DB']->add_table_field('f_welcome_emails','w_usergroup','?AUTO_LINK',NULL);
			$GLOBALS['SITE_DB']->add_table_field('f_welcome_emails','w_usergroup_type','ID_TEXT','');
			$GLOBALS['SITE_DB']->alter_table_field('f_welcome_emails','w_newsletter','?AUTO_LINK');
		}

		$GLOBALS['NO_DB_SCOPE_CHECK']=false;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'WELCOME_EMAILS'),parent::get_entry_points());
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @param  boolean		Whether this is running at the top level, prior to having sub-objects called.
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run($top_level=true)
	{
		$type=get_param('type','misc');

		require_lang('ocf_welcome_emails');

		set_helper_panel_pic('pagepics/welcome_emails');
		set_helper_panel_tutorial('tut_members');
		set_helper_panel_text(comcode_lang_string('DOC_WELCOME_EMAIL_PREVIEW'));

		breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS'))));

		return parent::pre_run($top_level);
	}

	/**
	 * Standard crud_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;

		require_code('ocf_general_action');
		require_code('ocf_general_action2');

		ocf_require_all_forum_stuff();

		if (get_forum_type()=='ocf')
		{
			$this->javascript='
				var newsletter_field=document.getElementById(\'newsletter\');
				var usergroup_field=newsletter_field.form.elements[\'usergroup\'];
				var update_newsletter_settings=function() {
					var has_newsletter=(newsletter_field.selectedIndex!=0);
					var has_usergroup=(usergroup_field.selectedIndex!=0);
					newsletter_field.form.elements[\'usergroup\'].disabled=has_newsletter;
					newsletter_field.form.elements[\'usergroup_type\'][0].disabled=has_newsletter || !has_usergroup;
					newsletter_field.form.elements[\'usergroup_type\'][1].disabled=has_newsletter || !has_usergroup;
					newsletter_field.form.elements[\'usergroup_type\'][2].disabled=has_newsletter || !has_usergroup;
				}
				newsletter_field.onchange=update_newsletter_settings;
				usergroup_field.onchange=update_newsletter_settings;
				update_newsletter_settings();
			';
		}

		$this->add_one_label=do_lang_tempcode('ADD_WELCOME_EMAIL');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_WELCOME_EMAIL');
		$this->edit_one_label=do_lang_tempcode('EDIT_WELCOME_EMAIL');

		if ($type=='misc') return $this->misc();
		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		if (!cron_installed()) attach_message(do_lang_tempcode('CRON_NEEDED_TO_WORK',escape_html(get_tutorial_url('tut_configuration'))),'warn');

		require_code('templates_donext');
		return do_next_manager(get_screen_title('WELCOME_EMAILS'),comcode_lang_string('DOC_WELCOME_EMAILS'),
			array(
				/*	 type							  page	 params													 zone	  */
				array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_WELCOME_EMAIL')),
				array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_WELCOME_EMAIL')),
			),
			do_lang('WELCOME_EMAILS')
		);
	}

	/**
	 * Get tempcode for adding/editing form.
	 *
	 * @param  SHORT_TEXT	A name for the Welcome E-mail
	 * @param  SHORT_TEXT	The subject of the Welcome E-mail
	 * @param  LONG_TEXT		The message body of the Welcome E-mail
	 * @param  integer		The number of hours before sending the e-mail
	 * @param  ?AUTO_LINK	What newsletter to send out to instead of members (NULL: none)
	 * @param  ?AUTO_LINK	The usergroup to tie to (NULL: none)
	 * @param  ID_TEXT		How to send regarding usergroups (blank: indiscriminately)
	 * @set primary secondary ""
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function get_form_fields($name='',$subject='',$text='',$send_time=0,$newsletter=NULL,$usergroup=NULL,$usergroup_type='')
	{
		$fields=new ocp_tempcode();
		$fields->attach(form_input_line(do_lang_tempcode('NAME'),do_lang_tempcode('DESCRIPTION_NAME_REFERENCE'),'name',$name,true));
		$fields->attach(form_input_line(do_lang_tempcode('SUBJECT'),do_lang_tempcode('DESCRIPTION_WELCOME_EMAIL_SUBJECT'),'subject',$subject,true));
		$fields->attach(form_input_huge_comcode(do_lang_tempcode('TEXT'),do_lang_tempcode('DESCRIPTION_WELCOME_EMAIL_TEXT'),'text',$text,true));
		$fields->attach(form_input_integer(do_lang_tempcode('SEND_TIME'),do_lang_tempcode('DESCRIPTION_SEND_TIME'),'send_time',$send_time,true));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'3c9bf61e762eb8715a7fdde214b7eac2','SECTION_HIDDEN'=>false,
			'TITLE'=>do_lang_tempcode('SCOPE'),
		)));

		if (addon_installed('newsletter'))
		{
			require_lang('newsletter');
			$newsletters=new ocp_tempcode();
			$rows=$GLOBALS['SITE_DB']->query_select('newsletters',array('id','title'));
			if (get_forum_type()=='ocf')
			{
				$newsletters->attach(form_input_list_entry('',is_null($newsletter),do_lang_tempcode('WELCOME_EMAIL_MEMBERS')));
			}
			foreach ($rows as $_newsletter)
				$newsletters->attach(form_input_list_entry(strval($_newsletter['id']),$_newsletter['id']===$newsletter,get_translated_text($_newsletter['title'])));
			if (!$newsletters->is_empty())
				$fields->attach(form_input_list(do_lang_tempcode('NEWSLETTER'),do_lang_tempcode('DESCRIPTION_WELCOME_EMAIL_NEWSLETTER'),'newsletter',$newsletters,NULL,false,false));
		}
		if (get_forum_type()=='ocf')
		{
			require_code('ocf_groups');
			$usergroups=new ocp_tempcode();
			$usergroups->attach(form_input_list_entry('',$usergroup===NULL,do_lang_tempcode('NA_EM')));
			$usergroups->attach(ocf_nice_get_usergroups($usergroup));
			$fields->attach(form_input_list(do_lang_tempcode('GROUP'),do_lang_tempcode('DESCRIPTION_WELCOME_EMAIL_USERGROUP',escape_html(get_site_name())),'usergroup',$usergroups,NULL,false,false));

			$radios=new ocp_tempcode();
			$radios->attach(form_input_radio_entry('usergroup_type','',true,do_lang_tempcode('WELCOME_EMAIL_USERGROUP_TYPE_BOTH')));
			$radios->attach(form_input_radio_entry('usergroup_type','primary',false,do_lang_tempcode('WELCOME_EMAIL_USERGROUP_TYPE_PRIMARY')));
			$radios->attach(form_input_radio_entry('usergroup_type','secondary',false,do_lang_tempcode('WELCOME_EMAIL_USERGROUP_TYPE_SECONDARY')));
			$fields->attach(form_input_radio(do_lang_tempcode('WELCOME_EMAIL_USERGROUP_TYPE'),do_lang_tempcode('DESCRIPTION_WELCOME_EMAIL_USERGROUP_TYPE'),'usergroup_type',$radios,false));
		}

		return array($fields,new ocp_tempcode());
	}

	/**
	 * Standard crud_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A pair: The choose table, Whether re-ordering is supported from this screen.
	 */
	function nice_get_choose_table($url_map)
	{
		require_code('templates_results_table');

		$current_ordering=get_param('sort','w_name ASC');
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			'w_name'=>do_lang_tempcode('NAME'),
			'w_subject'=>do_lang_tempcode('SUBJECT'),
			'w_send_time'=>do_lang_tempcode('SEND_TIME'),
		);
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');

		$header_row=results_field_title(array(
			do_lang_tempcode('NAME'),
			do_lang_tempcode('SUBJECT'),
			do_lang_tempcode('SEND_TIME'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering);
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$fields->attach(results_entry(array($row['w_name'],get_translated_text($row['w_subject']),do_lang_tempcode('HOURS',escape_html(strval($row['w_send_time']))),protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id']))))),true);
		}

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false);
	}

	/**
	 * Standard crud_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_entries()
	{
		$_m=$GLOBALS['SITE_DB']->query_select('f_welcome_emails',array('*'));
		$entries=new ocp_tempcode();
		foreach ($_m as $m)
		{
			$entries->attach(form_input_list_entry(strval($m['id']),false,$m['w_name']));
		}

		return $entries;
	}

	/**
	 * Standard crud_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function fill_in_edit_form($id)
	{
		$m=$GLOBALS['SITE_DB']->query_select('f_welcome_emails',array('*'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$m)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$r=$m[0];

		return $this->get_form_fields($r['w_name'],get_translated_text($r['w_subject']),get_translated_text($r['w_text']),$r['w_send_time'],$r['w_newsletter'],$r['w_usergroup'],$r['w_usergroup_type']);
	}

	/**
	 * Standard crud_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		$name=post_param('name');
		$subject=post_param('subject');
		$text=post_param('text');
		$send_time=post_param_integer('send_time');
		$newsletter=post_param_integer('newsletter',NULL);
		$usergroup=post_param_integer('usergroup',NULL);
		$usergroup_type=post_param('usergroup_type','');
		$id=ocf_make_welcome_email($name,$subject,$text,$send_time,$newsletter,$usergroup,$usergroup_type);
		return strval($id);
	}

	/**
	 * Standard crud_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		$name=post_param('name');
		$subject=post_param('subject');
		$text=post_param('text');
		$send_time=post_param_integer('send_time');
		$newsletter=post_param_integer('newsletter',NULL);
		$usergroup=post_param_integer('usergroup',NULL);
		$usergroup_type=post_param('usergroup_type','');
		ocf_edit_welcome_email(intval($id),$name,$subject,$text,$send_time,$newsletter,$usergroup,$usergroup_type);
	}

	/**
	 * Standard crud_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		ocf_delete_welcome_email(intval($id));
	}
}


