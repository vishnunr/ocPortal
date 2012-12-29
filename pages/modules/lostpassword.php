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
 * Module page class.
 */
class Module_lostpassword
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
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();

		$type=get_param('type','misc');

		if ($type=='misc') return $this->step1();
		if ($type=='step2') return $this->step2();
		if ($type=='step3') return $this->step3();

		return new ocp_tempcode();
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return is_guest()?array('misc'=>'RESET_PASSWORD'):array();
	}

	/**
	 * The UI to ask for the username to get the lost password for.
	 *
	 * @return tempcode		The UI
	 */
	function step1()
	{
		$title=get_screen_title('RESET_PASSWORD');

		$fields=new ocp_tempcode();
		require_code('form_templates');

		$set_name='account';
		$required=true;
		$set_title=do_lang_tempcode('ACCOUNT');
		$field_set=alternate_fields_set__start($set_name);

		$field_set->attach(form_input_username(do_lang_tempcode('USERNAME'),'','username',trim(get_param('username','')),false));

		$field_set->attach(form_input_email(do_lang_tempcode('EMAIL_ADDRESS'),'','email_address',trim(get_param('email_address','')),false));

		$fields->attach(alternate_fields_set__end($set_name,$set_title,'',$field_set,$required));

		$text=do_lang_tempcode('_PASSWORD_RESET_TEXT');
		$submit_name=do_lang_tempcode('PROCEED');
		$post_url=build_url(array('page'=>'_SELF','type'=>'step2'),'_SELF');

		breadcrumb_set_self(do_lang_tempcode('RESET_PASSWORD'));

		return do_template('FORM_SCREEN',array('_GUID'=>'080e516fef7c928dbb9fb85beb6e435a','SKIP_VALIDATION'=>true,'TITLE'=>$title,'HIDDEN'=>'','FIELDS'=>$fields,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'URL'=>$post_url));
	}

	/**
	 * The UI and actualisation for sending out the confirm email.
	 *
	 * @return tempcode		The UI
	 */
	function step2()
	{
		$title=get_screen_title('RESET_PASSWORD');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('RESET_PASSWORD'))));
		breadcrumb_set_self(do_lang_tempcode('START'));

		$username=trim(post_param('username',''));
		$email_address=trim(post_param('email_address',''));
		if (($username=='') && ($email_address==''))
			warn_exit(do_lang_tempcode('PASSWORD_RESET_ERROR'));

		if ($username!='')
		{
			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
		} else
		{
			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_email_address($email_address);
		}
		if (is_null($member_id)) warn_exit(do_lang_tempcode('PASSWORD_RESET_ERROR_2'));
		if (($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_password_compat_scheme')=='') && (has_privilege($member_id,'disable_lost_passwords')) && (!$GLOBALS['IS_ACTUALLY_ADMIN']))
		{
			warn_exit(do_lang_tempcode('NO_RESET_ACCESS'));
		}
		if ($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_password_compat_scheme')=='httpauth')
		{
			warn_exit(do_lang_tempcode('NO_PASSWORD_RESET_HTTPAUTH'));
		}
		$is_ldap=ocf_is_ldap_member($member_id);
		$is_httpauth=ocf_is_httpauth_member($member_id);
		if (($is_ldap)/* || ($is_httpauth  Actually covered more explicitly above - over mock-httpauth, like Facebook, may have passwords reset to break the integrations)*/) warn_exit(do_lang_tempcode('EXT_NO_PASSWORD_CHANGE'));

		$code=mt_rand(0,mt_getrandmax());
		$GLOBALS['FORUM_DB']->query_update('f_members',array('m_password_change_code'=>strval($code)),array('id'=>$member_id),'',1);

		log_it('RESET_PASSWORD',strval($member_id),strval($code));

		$email=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_email_address');
		if ($email=='') warn_exit(do_lang_tempcode('MEMBER_NO_EMAIL_ADDRESS_RESET_TO'));

		// Send confirm mail
		$zone=get_module_zone('join');
		$_url=build_url(array('page'=>'lostpassword','type'=>'step3','code'=>$code,'member'=>$member_id),$zone,NULL,false,false,true);
		$url=$_url->evaluate();
		$_url_simple=build_url(array('page'=>'lostpassword','type'=>'step3','code'=>NULL,'username'=>NULL,'member'=>NULL),$zone,NULL,false,false,true);
		$url_simple=$_url_simple->evaluate();
		$message=do_lang('RESET_PASSWORD_TEXT',comcode_escape(get_site_name()),comcode_escape($username),array(comcode_escape($url),$url_simple,strval($member_id),strval($code)),get_lang($member_id));
		require_code('mail');
		mail_wrap(do_lang('RESET_PASSWORD',NULL,NULL,NULL,get_lang($member_id)),$message,array($email),$GLOBALS['FORUM_DRIVER']->get_username($member_id));

		breadcrumb_set_self(do_lang_tempcode('DONE'));

		return inform_screen($title,do_lang_tempcode('RESET_CODE_MAILED'));
	}

	/**
	 * The UI and actualisation for: accepting code if it is correct (and not ''), and setting password to something random, emailing it
	 *
	 * @return tempcode		The UI
	 */
	function step3()
	{
		$title=get_screen_title('RESET_PASSWORD');

		$code=get_param('code','');
		if ($code=='')
		{
			require_code('form_templates');
			$fields=new ocp_tempcode();
			$fields->attach(form_input_username(do_lang_tempcode('USERNAME'),'','username',NULL,true));
			$fields->attach(form_input_integer(do_lang_tempcode('CODE'),'','code',NULL,true));
			$submit_name=do_lang_tempcode('PROCEED');
			return do_template('FORM_SCREEN',array(
				'_GUID'=>'6e4db5c6f3c75faa999251339533d22a',
				'TITLE'=>$title,
				'GET'=>true,
				'SKIP_VALIDATION'=>true,
				'HIDDEN'=>'',
				'URL'=>get_self_url(false,false,NULL,false,true),
				'FIELDS'=>$fields,
				'TEXT'=>do_lang_tempcode('MISSING_CONFIRM_CODE'),
				'SUBMIT_NAME'=>$submit_name,
			));
		}
		$username=post_param('username',NULL);
		if (!is_null($username))
		{
			$username=trim($username);
			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
			if (is_null($member_id)) warn_exit(do_lang_tempcode('PASSWORD_RESET_ERROR_2'));
		} else
		{
			$member_id=get_param_integer('member');
		}
		$correct_code=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_password_change_code');
		if ($correct_code=='')
		{
			$_reset_url=build_url(array('page'=>'_SELF','username'=>$GLOBALS['FORUM_DRIVER']->get_username($member_id)),'_SELF');
			$reset_url=$_reset_url->evaluate();
			warn_exit(do_lang_tempcode('PASSWORD_ALREADY_RESET',escape_html($reset_url),get_site_name()));
		}
		if ($code!=$correct_code)
		{
			$test=$GLOBALS['SITE_DB']->query_select_value_if_there('adminlogs','date_and_time',array('the_type'=>'RESET_PASSWORD','param_a'=>strval($member_id),'param_b'=>$code));
			if (!is_null($test)) warn_exit(do_lang_tempcode('INCORRECT_PASSWORD_RESET_CODE'));
			log_hack_attack_and_exit('HACK_ATTACK_PASSWORD_CHANGE');
		}

		$email=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_email_address');

		require_code('crypt');
		$new_password=get_rand_password();

		// Send password in mail
		$_login_url=build_url(array('page'=>'login','username'=>$GLOBALS['FORUM_DRIVER']->get_username($member_id)),get_module_zone('login'),NULL,false,false,true);
		$login_url=$_login_url->evaluate();
		$message=do_lang('MAIL_NEW_PASSWORD',comcode_escape($new_password),$login_url,get_site_name());
		require_code('mail');
		mail_wrap(do_lang('RESET_PASSWORD'),$message,array($email),$GLOBALS['FORUM_DRIVER']->get_username($member_id));

		if (get_value('no_password_hashing')==='1')
		{
			$password_compatibility_scheme='plain';
			$new=$new_password;
		} else
		{
			$password_compatibility_scheme='';
			$salt=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_pass_salt');
			$new=md5($salt.md5($new_password));
		}

		unset($_GET['code']);
		$GLOBALS['FORUM_DB']->query_update('f_members',array('m_validated_email_confirm_code'=>'','m_password_compat_scheme'=>$password_compatibility_scheme,'m_password_change_code'=>'','m_pass_hash_salted'=>$new),array('id'=>$member_id),'',1);

		return inform_screen($title,do_lang_tempcode('NEW_PASSWORD_MAILED',escape_html($email)));
	}

}


