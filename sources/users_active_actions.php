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
 * @package		core
 */

/**
 * Backdoor handler. Can only be activated by those with FTP write-access.
 *
 * @return MEMBER			The member to simulate
 */
function restricted_manually_enabled_backdoor()
{
	global $IS_A_COOKIE_LOGIN;
	$IS_A_COOKIE_LOGIN=true;

	$ks=get_param('keep_su',NULL);
	if (!is_null($ks))
	{
		$GLOBALS['IS_ACTUALLY_ADMIN']=true;
		$su=$GLOBALS['FORUM_DRIVER']->get_member_from_username($ks);
		if (!is_null($su)) return $su; elseif (is_numeric($ks)) return intval($ks);
	}

	$members=$GLOBALS['FORUM_DRIVER']->member_group_query($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(),1);
	if (count($members)!=0)
	{
		$ret=$GLOBALS['FORUM_DRIVER']->pname_id($members[key($members)]);
		$GLOBALS['FORUM_DRIVER']->ocf_flood_control($ret);
	} else
	{
		$ret=$GLOBALS['FORUM_DRIVER']->get_guest_id()+1;
	}

	require_code('users_inactive_occasionals');
	create_session($ret,1);

	return $ret;
}

/**
 * Process a login.
 *
 * @param  ID_TEXT		Username
 */
function handle_active_login($username)
{
	global $SESSION_CACHE;

	$result=array();

	$member_cookie_name=get_member_cookie();
	$colon_pos=strpos($member_cookie_name,':');

	if ($colon_pos!==false)
	{
		$base=substr($member_cookie_name,0,$colon_pos);
		$real_member_cookie=substr($member_cookie_name,$colon_pos+1);
		$real_pass_cookie=substr(get_pass_cookie(),$colon_pos+1);
		$serialized=true;
	} else
	{
		$real_member_cookie=get_member_cookie();
		$base=$real_member_cookie;
		$real_pass_cookie=get_pass_cookie();
		$serialized=false;
	}

	$password=trim(post_param('password'));
	$login_array=$GLOBALS['FORUM_DRIVER']->forum_authorise_login($username,NULL,apply_forum_driver_md5_variant($password,$username),$password);
	$member=$login_array['id'];

	// Run hooks, if any exist
	$hooks=find_all_hooks('systems','upon_login');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/upon_login/'.filter_naughty($hook));
		$ob=object_factory('upon_login'.filter_naughty($hook),true);
		if (is_null($ob)) continue;
		$ob->run(true,$username,$member); // true means "a new login attempt"
	}

	if (!is_null($member)) // Valid user
	{
		$remember=post_param_integer('remember',0);

		// Create invisibility cookie
		if ((array_key_exists(get_member_cookie().'_invisible',$_COOKIE)/*i.e. already has cookie set, so adjust*/) || ($remember==1))
		{
			$invisible=post_param_integer('login_invisible',0);
			ocp_setcookie(get_member_cookie().'_invisible',strval($invisible));
			$_COOKIE[get_member_cookie().'_invisible']=strval($invisible);
		}

		// Store the cookies
		if ($remember==1)
		{
			global $IS_A_COOKIE_LOGIN;
			$IS_A_COOKIE_LOGIN=true;

			// Create user cookie
			if (method_exists($GLOBALS['FORUM_DRIVER'],'forum_create_cookie'))
			{
				$GLOBALS['FORUM_DRIVER']->forum_create_cookie($member,NULL,$password);
			}
			else
			{
				if ($GLOBALS['FORUM_DRIVER']->is_cookie_login_name())
				{
					$name=$GLOBALS['FORUM_DRIVER']->get_username($member);
					if ($serialized)
					{
						$result[$real_member_cookie]=$name;
					} else
					{
						ocp_setcookie(get_member_cookie(),$name,false,true);
						$_COOKIE[get_member_cookie()]=$name;
					}
				} else
				{
					if ($serialized)
					{
						$result[$real_member_cookie]=$member;
					} else
					{
						ocp_setcookie(get_member_cookie(),strval($member),false,true);
						$_COOKIE[get_member_cookie()]=strval($member);
					}
				}

				// Create password cookie
				if (!$serialized)
				{
					if ($GLOBALS['FORUM_DRIVER']->is_hashed())
					{
						ocp_setcookie(get_pass_cookie(),apply_forum_driver_md5_variant($password,$username),false,true);
					}
					else
					{
						ocp_setcookie(get_pass_cookie(),$password,false,true);
					}
				} else
				{
					if ($GLOBALS['FORUM_DRIVER']->is_hashed()) $result[$real_pass_cookie]=apply_forum_driver_md5_variant($password,$username); else $result[$real_pass_cookie]=$password;
					$_result=serialize($result);
					ocp_setcookie($base,$_result,false,true);
				}
			}
		}

		// Create session
		require_code('users_inactive_occasionals');
		create_session($member,1,post_param_integer('login_invisible',0)==1);
		global $MEMBER_CACHED;
		$MEMBER_CACHED=$member;
	} else
	{
		$GLOBALS['SITE_DB']->query_insert('failedlogins',array('failed_account'=>trim(post_param('login_username')),'date_and_time'=>time(),'ip'=>get_ip_address()));

		$brute_force_login_minutes=15;
		$_brute_force_login_minutes=get_value('brute_force_login_minutes');
		if (!is_null($_brute_force_login_minutes))
		{
			$brute_force_login_minutes=intval($_brute_force_login_minutes);
		}

		$brute_force_threshold=30;
		$_brute_force_threshold=get_value('brute_force_threshold');
		if (!is_null($_brute_force_threshold))
		{
			$brute_force_threshold=intval($_brute_force_threshold);
		}

		$count=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'failedlogins WHERE date_and_time>'.strval(time()-60*$brute_force_login_minutes).' AND '.db_string_equal_to('ip',get_ip_address()));
		if ($count>=$brute_force_threshold) log_hack_attack_and_exit('BRUTEFORCE_LOGIN_HACK',$username,'',false,get_value('brute_force_instant_ban')==='1');
	}
}

/**
 * Process a logout.
 */
function handle_active_logout()
{
	// Kill cookie
//	$expire=time()-300;
	$member_cookie_name=get_member_cookie();
	$colon_pos=strpos($member_cookie_name,':');
	if ($colon_pos!==false)
	{
		$base=substr($member_cookie_name,0,$colon_pos);
	} else
	{
		$real_member_cookie=get_member_cookie();
		$base=$real_member_cookie;
	}
	ocp_eatcookie($base);
	unset($_COOKIE[$base]);

	// Kill session
	$session=get_session_id();
	if ($session!=-1)
	{
		delete_session($session);
	}

	// Update last-visited cookie
	if (get_forum_type()=='ocf')
	{
		require_code('users_active_actions');
		ocp_setcookie('last_visit',strval(time()),true);
	}
}

/**
 * Delete a session.
 *
 * @param  integer		The new session
 */
function delete_session($session)
{
	require_code('users_inactive_occasionals');
	set_session_id(-1);

	$GLOBALS['SITE_DB']->query_delete('sessions',array('the_session'=>$session),'',1);

	global $SESSION_CACHE;
	unset($SESSION_CACHE[$session]);
	if (get_value('session_prudence')!=='1')
	{
		persistent_cache_set('SESSION_CACHE',$SESSION_CACHE);
	}
}

/**
 * Create a cookie, inside ocPortal's cookie environment.
 *
 * @param  string			The name of the cookie
 * @param  string			The value to store in the cookie
 * @param  boolean		Whether it is a session cookie (gets removed once the browser window closes)
 * @param  boolean		Whether the cookie should not be readable by Javascript
 * @return boolean		The result of the PHP setcookie command
 */
function ocp_setcookie($name,$value,$session=false,$http_only=false)
{
	if (($GLOBALS['DEV_MODE']) && (!running_script('occle')) && (get_forum_type()=='ocf') && (get_param_integer('keep_debug_has_cookies',0)==0)) return true;

	$cookie_domain=get_cookie_domain();
	$path=get_cookie_path();
	if ($path=='')
	{
		$base_url=get_base_url();
		$pos=strpos($base_url,'/');
		if ($pos===false)
		{
			$path='/';
		} else
		{
			$path=substr($base_url,$pos).'/';
		}
	}

	$time=$session?NULL:(time()+get_cookie_days()*24*60*60);
	if ($cookie_domain=='')
	{
		$output=@setcookie($name,$value,$time,$path);
	} else
	{
		if (!$http_only)
		{
			$output=@setcookie($name,$value,$time,$path,$cookie_domain);
		} else
		{
			if (PHP_VERSION<5.2)
			{
				$output=@setcookie($name,$value,$time,$path,$cookie_domain.'; HttpOnly');
			} else
			{
				$output=@call_user_func_array('setcookie',array($name,$value,$time,$path,$cookie_domain,0,true)); // For Phalanger
				//$output=@setcookie($name,$value,$time,$path,$cookie_domain,0,true);
			}
		}
	}
	if ($name!='has_cookies')
		$_COOKIE[$name]=get_magic_quotes_gpc()?addslashes($value):$value;

	return $output;
}

