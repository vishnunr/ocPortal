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
 * Standard code module initialisation function.
 */
function init__failure()
{
	global $DONE_ONE_WEB_SERVICE;
	$DONE_ONE_WEB_SERVICE=false;

	if (!defined('MAX_STACK_TRACE_VALUE_LENGTH'))
	{
		define('MAX_STACK_TRACE_VALUE_LENGTH',300);
	}
}

/**
 * Terminate with an error caused by unzipping.
 *
 * @param  integer	The zip error number.
 * @param  boolean	Whether mzip was used.
 * @return tempcode	Error message.
 */
function zip_error($errno,$mzip=false)
{
	$zip_file_function_errors = array( // Based on comment from php.net
		'ZIPARCHIVE::ER_MULTIDISK' => 'Multi-disk zip archives not supported.',
		'ZIPARCHIVE::ER_RENAME' => 'Renaming temporary file failed.',
		'ZIPARCHIVE::ER_CLOSE' => 'Closing zip archive failed',
		'ZIPARCHIVE::ER_SEEK' => 'Seek error',
		'ZIPARCHIVE::ER_READ' => 'Read error',
		'ZIPARCHIVE::ER_WRITE' => 'Write error',
		'ZIPARCHIVE::ER_CRC' => 'CRC error',
		'ZIPARCHIVE::ER_ZIPCLOSED' => 'Containing zip archive was closed',
		'ZIPARCHIVE::ER_NOENT' => 'No such file.',
		'ZIPARCHIVE::ER_EXISTS' => 'File already exists',
		'ZIPARCHIVE::ER_OPEN' => 'Can\'t open file',
		'ZIPARCHIVE::ER_TMPOPEN' => 'Failure to create temporary file.',
		'ZIPARCHIVE::ER_ZLIB' => 'Zlib error',
		'ZIPARCHIVE::ER_MEMORY' => 'Memory allocation failure',
		'ZIPARCHIVE::ER_CHANGED' => 'Entry has been changed',
		'ZIPARCHIVE::ER_COMPNOTSUPP' => 'Compression method not supported.',
		'ZIPARCHIVE::ER_EOF' => 'Premature EOF',
		'ZIPARCHIVE::ER_INVAL' => 'Invalid argument',
		'ZIPARCHIVE::ER_NOZIP' => 'Not a zip archive',
		'ZIPARCHIVE::ER_INTERNAL' => 'Internal error',
		'ZIPARCHIVE::ER_INCONS' => 'Zip archive inconsistent',
		'ZIPARCHIVE::ER_REMOVE' => 'Can\'t remove file',
		'ZIPARCHIVE::ER_DELETED' => 'Entry has been deleted',
	);
	$errmsg = 'unknown';
	foreach ($zip_file_function_errors as $const_name => $error_message)
	{
		if ((defined($const_name)) && (constant($const_name))==$errno)
		{
			$errmsg=$error_message;
		}
	}
	return do_lang_tempcode($mzip?'ZIP_ERROR_MZIP':'ZIP_ERROR',$errmsg);
}

/**
 * Handle invalid parameter values.
 *
 * @param  string			The parameter deemed to have an invalid value somehow
 * @param  ?string		The value of the parameter deemed invalid (NULL: we known we can't recover)
 * @param  boolean		Whether the parameter is a POST parameter
 * @return string			Fixed parameter (usually the function won't return [instead will give an error], but in special cases, it can filter an invalid return)
 */
function _param_invalid($name,$ret,$posted)
{
	// Invalid params can happen for many reasons:
	//  [/url] getting onto the end of URLs by bad URL extractors getting URLs out of Comcode
	//  Spiders trying to ascend directory trees, and forcing index.php into the integer position of short URLs
	//  Spiders that don't understand entity decoding
	//  People copying and pasting text shown after URLs as part of the URL itself
	//  New line characters getting pasted in (weird, but it's happened-- think might be some kind of screen reader browser)
	//  People typing the wrong URLs for many reasons
	// Therefore we can't really treat it as a hack-attack, even though that would be preferable.

	static $param_invalid_looping=false;
	if ($param_invalid_looping) return '0'; // stop loop, e.g. with keep_fatalistic=<corruptvalue>
	$param_invalid_looping=true;

	if (!is_null($ret))
	{
		// Try and recover by stripping junk off...
		$test=preg_replace('#[^\d]+$#','',$ret);
		if (is_numeric($test)) return $test;
	}

	$GLOBALS['HTTP_STATUS_CODE']='400';
	if (!headers_sent())
	{
		if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 400 Bad Request');
	}

	require_lang('javascript');
	warn_exit(do_lang_tempcode('NOT_INTEGER'));
	return '';
}

/**
 * Complain about a field being missing.
 *
 * @param  string			The name of the parameter
 * @param  ?boolean		Whether the parameter is a POST parameter (NULL: undetermined)
 * @param  array			The array we're extracting parameters from
 */
function improperly_filled_in($name,$posted,$array)
{
	require_code('tempcode');

	$GLOBALS['HTTP_STATUS_CODE']='400';
	if (!headers_sent())
	{
		if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 400 Bad Request');
	}

	if ($posted!==false)
	{
		improperly_filled_in_post($name);
	}

	if ($name=='login_username')
	{
		warn_exit(do_lang_tempcode('NO_PARAMETER_SENT_SPECIAL',escape_html($name)));
	}

	if ((!isset($array[$name])) && (($name=='id') || ($name=='type')) && (!headers_sent()))
	{
		$GLOBALS['HTTP_STATUS_CODE']='404';
		if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 404 Not Found'); // Direct ascending for short URLs - not possible, so should give 404's to avoid indexing
	}
	warn_exit(do_lang_tempcode('NO_PARAMETER_SENT',escape_html($name)));
}

/**
 * Complain about a POST field being missing.
 *
 * @param  string			The name of the parameter
 */
function improperly_filled_in_post($name)
{
	$GLOBALS['HTTP_STATUS_CODE']='400';
	if (!headers_sent())
	{
		if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 400 Bad Request');
	}

	if ((count($_POST)==0) && (get_option('user_postsize_errors')=='1'))
	{
		$upload_max_filesize=(ini_get('upload_max_filesize')=='0')?do_lang('NA'):clean_file_size(php_return_bytes(ini_get('upload_max_filesize')));
		$post_max_size=(ini_get('post_max_size')=='0')?do_lang('NA'):clean_file_size(php_return_bytes(ini_get('post_max_size')));
		warn_exit(do_lang_tempcode((get_param_integer('uploading',0)==1)?'SHOULD_HAVE_BEEN_POSTED_FILE_ERROR':'SHOULD_HAVE_BEEN_POSTED',escape_html($name),escape_html($post_max_size),escape_html($upload_max_filesize)));
	}

	// We didn't give some required input
	warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN'));
}

/**
 * Called by 'ocportal_error_handler'. ocPortal error handler (hooked into PHP error system).
 *
 * @param  ID_TEXT		Error type indicator (tiny human-readable text string)
 * @param  integer		The error code-number
 * @param  PATH			The error message
 * @param  string			The file the error occurred in
 * @param  integer		The line the error occurred on
 */
function _ocportal_error_handler($type,$errno,$errstr,$errfile,$errline)
{
	if (!$GLOBALS['SUPPRESS_ERROR_DEATH'])
	{
		// Turn off MSN, as this increases stability
		if ((array_key_exists('MSN_DB',$GLOBALS)) && (!is_null($GLOBALS['MSN_DB'])))
		{
			$GLOBALS['FORUM_DB']=$GLOBALS['MSN_DB'];
			$GLOBALS['MSN_DB']=NULL;
		}
	}

	// Generate error message
	$outx='<strong>'.strtoupper($type).'</strong> ['.strval($errno).'] '.$errstr.' in '.$errfile.' on line '.strval($errline).'<br />'.chr(10);
	if (class_exists('ocp_tempcode'))
	{
		if ($GLOBALS['SUPPRESS_ERROR_DEATH'])
		{
			$trace=new ocp_tempcode();
		} else
		{
			$trace=get_html_trace();
		}
		$out=$outx.$trace->evaluate();
	} else $out=$outx;

	// Put into error log
	if (get_param_integer('keep_fatalistic',0)==0)
		@error_log('PHP '.ucwords($type).':  '.$errstr.' in '.$errfile.' on line '.strval($errline).' @ '.get_self_url_easy(),0);

	if (!$GLOBALS['SUPPRESS_ERROR_DEATH']) // Don't display - die as normal
	{
		@ini_set('display_errors','0');
		fatal_exit('PHP '.strtoupper($type).' ['.strval($errno).'] '.$errstr.' in '.$errfile.' on line '.strval($errline));
		relay_error_notification($out);
		exit();
	} else
	{
		require_code('site');
		attach_message(protect_from_escaping($out),'warn'); // Display
	}
}

/**
 * Do a terminal execution on a defined page type
 *
 * @param  mixed			The error message (string or tempcode)
 * @param  ID_TEXT		Name of the terminal page template
 */
function _generic_exit($text,$template)
{
	@ob_end_clean(); // Incase in minimodule

	if (get_param_integer('keep_fatalistic',0)==1) fatal_exit($text);

	@header('Content-type: text/html; charset='.get_charset());
	@header('Content-Disposition: inline');

	//$x=@ob_get_contents(); @ob_end_clean(); //if (is_string($x)) @print($x);		Disabled as causes weird crashes

	$text_eval=is_object($text)?$text->evaluate():$text;

	if ($GLOBALS['HTTP_STATUS_CODE']=='200')
	{
		if (($text_eval==do_lang('ocf:NO_MARKERS_SELECTED')) || ($text_eval==do_lang('NOTHING_SELECTED')))
		{
			if (!headers_sent())
			{
				$GLOBALS['HTTP_STATUS_CODE']='400';
				if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 400 Bad Request');
			}
		}
		elseif (($text_eval==do_lang('MISSING_RESOURCE')) || ($text_eval==do_lang('USER_NO_EXIST')))
		{
			if (!headers_sent())
			{
				$GLOBALS['HTTP_STATUS_CODE']='404';
				if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 404 Not Found');
			}
			if (ocp_srv('HTTP_REFERER')!='')
			{
				relay_error_notification($text_eval.' '.do_lang('REFERRER',ocp_srv('HTTP_REFERER'),substr(get_browser_string(),0,255)),false,'error_occurred_missing_resource');
			}
		}
		elseif ($template=='WARN_SCREEN')
		{
			if (!headers_sent())
			{
				$GLOBALS['HTTP_STATUS_CODE']='500';
				if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 500 Internal server error');
			}
		}
	}

	if ((array_key_exists('MSN_DB',$GLOBALS)) && (!is_null($GLOBALS['MSN_DB'])))
	{
		$GLOBALS['FORUM_DB']=$GLOBALS['MSN_DB'];
		$GLOBALS['MSN_DB']=NULL;
	}

	global $EXITING;
	if ((running_script('upgrader')) || (!function_exists('get_page_title'))) critical_error('PASSON',is_object($text)?$text->evaluate():$text);

	if (($EXITING==1) || (!function_exists('get_member'))) critical_error('EMERGENCY',is_object($text)?$text->evaluate():escape_html($text));
	$EXITING++;
	if (!function_exists('do_header')) require_code('site');

	if ((get_forum_type()=='ocf') && (get_db_type()!='xml'))
	{
		require_code('ocf_groups');
		$restrict_answer=ocf_get_best_group_property($GLOBALS['FORUM_DRIVER']->get_members_groups(get_member()),'flood_control_submit_secs');
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;
		$GLOBALS['SITE_DB']->query_update('f_members',array('m_last_submit_time'=>time()-$restrict_answer-1),array('id'=>get_member()),'',1);
		$GLOBALS['NO_DB_SCOPE_CHECK']=false;
	}

	global $DONE_HEADER;
	$bail_out=(isset($DONE_HEADER) && $DONE_HEADER);
	$echo=$bail_out?new ocp_tempcode():do_header(running_script('preview') || running_script('iframe') || running_script('shoutbox'));
	if (($template=='INFORM_SCREEN') && (is_object($GLOBALS['DISPLAYED_TITLE'])))
	{
		$title=get_page_title($GLOBALS['DISPLAYED_TITLE'],false);
	} else
	{
		$title=get_page_title(($template=='INFORM_SCREEN')?'MESSAGE':'ERROR_OCCURRED');
	}

	if (running_script('preview') || running_script('iframe') || running_script('shoutbox'))
	{
		$echo=do_template('STYLED_HTML_WRAP',array('TITLE'=>do_lang_tempcode(($template=='INFORM_SCREEN')?'MESSAGE':'ERROR_OCCURRED'),'FRAME'=>true,'TARGET'=>'_top','CONTENT'=>$text));
		$echo->handle_symbol_preprocessing();
		$echo->evaluate_echo();
		exit();
	}

	$inside=do_template($template,array('TITLE'=>$title,'TEXT'=>$text,'PROVIDE_BACK'=>true));
	$echo->attach((running_script('preview') || running_script('iframe') || running_script('shoutbox'))?$inside:globalise($inside));
	$echo->attach(do_footer($bail_out));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
	exit();
}

/**
 * Log a hackattack, then displays an error message. It also attempts to send an e-mail to the staff alerting them of the hackattack.
 *
 * @param  ID_TEXT		The reason for the hack attack. This has to be a language string codename
 * @param  SHORT_TEXT	A parameter for the hack attack language string (this should be based on a unique ID, preferably)
 * @param  SHORT_TEXT	A more illustrative parameter, which may be anything (e.g. a title)
 */
function _log_hack_attack_and_exit($reason,$reason_param_a='',$reason_param_b='')
{
	if (function_exists('set_time_limit')) @set_time_limit(4);

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$GLOBALS['HTTP_STATUS_CODE']='403';
	if (!headers_sent())
	{
		if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 403 Forbidden'); // Stop spiders ever storing the URL that caused this
	}

	if (!addon_installed('securitylogging'))
		warn_exit(do_lang_tempcode('HACK_ATTACK_USER'));

	$ip=get_ip_address();
	$ip2=ocp_srv('REMOTE_ADDR');
	if (!is_valid_ip($ip2)) $ip2='';
	if (($ip2==$ip) || ($ip2=='') || (ocp_srv('SERVER_ADDR')==$ip2)) $ip2=NULL;
	if (function_exists('get_member'))
	{
		$id=get_member();
		$username=$GLOBALS['FORUM_DRIVER']->get_username($id);
		if (is_null($username)) $username=do_lang('UNKNOWN');
	} else
	{
		$id=db_get_first_id();
		$username=function_exists('do_lang')?do_lang('UNKNOWN'):'Unknown';
	}

	$url=ocp_srv('PHP_SELF').'?'.ocp_srv('QUERY_STRING');
	$post='';
	foreach ($_POST as $key=>$val)
	{
		if (!is_string($val)) continue;
		$post.=$key.' => '.$val."\n\n";
	}

	$count=$GLOBALS['SITE_DB']->query_value('hackattack','COUNT(*)',array('ip'=>$ip));
	$alt_ip=false;
	if (!is_null($ip2))
	{
		$count2=$GLOBALS['SITE_DB']->query_value('hackattack','COUNT(*)',array('ip'=>$ip2));
		if ($count2>$count)
		{
			$count=$count2;
			$alt_ip=true;
		}
	}
	$hack_threshold=5;
	if ((array_key_exists('FORUM_DRIVER',$GLOBALS)) && (function_exists('get_member')) && ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))) $count=0;
	$new_row=array('user_agent'=>substr(get_browser_string(),0,255),'referer'=>substr(ocp_srv('HTTP_REFERER'),0,255),'user_os'=>substr(get_os_string(),0,255),'reason'=>$reason,'reason_param_a'=>substr($reason_param_a,0,255),'reason_param_b'=>substr($reason_param_b,0,255),'url'=>substr($url,0,255),'data_post'=>$post,'the_user'=>$id,'date_and_time'=>time(),'ip'=>$ip);
	$ip_ban_todo=NULL;
	if (($count>=$hack_threshold) && (get_option('autoban')!='0'))
	{
		// Test we're not banning a good bot
		$se_ip_lists=array('http://www.iplists.com.nyud.net/nw/google.txt','http://www.iplists.com.nyud.net/nw/msn.txt','http://www.iplists.com.nyud.net/infoseek.txt','http://www.iplists.com.nyud.net/nw/inktomi.txt','http://www.iplists.com.nyud.net/nw/lycos.txt','http://www.iplists.com.nyud.net/nw/askjeeves.txt','http://www.iplists.com.nyud.net/northernlight.txt','http://www.iplists.com.nyud.net/nw/altavista.txt','http://www.iplists.com.nyud.net/nw/misc.txt');
		$ip_stack=array();
		$ip_bits=explode((strpos($alt_ip?$ip2:$ip,'.')!==false)?'.':':',$alt_ip?$ip2:$ip);
		foreach ($ip_bits as $i=>$ip_bit)
		{
			$buildup='';
			for ($j=0;$j<=$i;$j++)
			{
				if ($buildup!='') $buildup.=(strpos($alt_ip?$ip2:$ip,'.')!==false)?'.':':';
				$buildup.=$ip_bits[$j];
			}
			$ip_stack[]=$buildup;
		}
		$is_se=false;
		foreach ($se_ip_lists as $ip_list)
		{
			$ip_list_file=http_download_file($ip_list,NULL,false);
			if (is_string($ip_list_file))
			{
				$ip_list_array=explode(chr(10),$ip_list_file);
				foreach ($ip_stack as $ip_s)
				{
					if (in_array($ip_s,$ip_list_array)) $is_se=true;
				}
				if ($is_se) break;
			}
		}
		$dns=@gethostbyaddr($alt_ip?$ip2:$ip);
		if ((preg_match('#(\s|,|^)gethostbyname(\s|$|,)#i',@ini_get('disable_functions'))!=0) || (@gethostbyname($dns)===($alt_ip?$ip2:$ip))) // Verify it's not faking the DNS
		{
			$se_domain_names=array('googlebot.com','google.com','msn.com','yahoo.com','ask.com','aol.com');
			foreach ($se_domain_names as $domain_name)
			{
				if (substr($dns,-strlen($domain_name)-1)=='.'.$domain_name)
				{
					$is_se=true;
					break;
				}
			}
		}
		if ((!$is_se) && (($alt_ip?$ip2:$ip)!='127.0.0.1'))
		{
			$rows=$GLOBALS['SITE_DB']->query_select('hackattack',array('*'),array('ip'=>$alt_ip?$ip2:$ip));
			$rows[]=$new_row;
			$summary='';
			foreach ($rows as $row)
			{
				$full_reason=do_lang($row['reason'],$row['reason_param_a'],$row['reason_param_b'],NULL,get_site_default_lang());
				$summary.="\n".' - '.$full_reason.' ['.$row['url'].']';
			}
			add_ip_ban($alt_ip?$ip2:$ip,$full_reason);
			$_ip_ban_url=build_url(array('page'=>'admin_ipban','type'=>'misc'),get_module_zone('admin_ipban'),NULL,false,false,true);
			$ip_ban_url=$_ip_ban_url->evaluate();
			$ip_ban_todo=do_lang('AUTO_BAN_HACK_MESSAGE',$alt_ip?$ip2:$ip,integer_format($hack_threshold),array($summary,$ip_ban_url),get_site_default_lang());
		}
	}
	$GLOBALS['SITE_DB']->query_insert('hackattack',$new_row);
	if (!is_null($ip2))
	{
		$new_row['ip']=$ip2;
		$GLOBALS['SITE_DB']->query_insert('hackattack',$new_row);
	}

	if (function_exists('do_lang'))
	{
		$reason_full=do_lang($reason,$reason_param_a,$reason_param_b,NULL,get_site_default_lang());
		$_stack_trace=get_html_trace();
		$stack_trace=str_replace('html','&#104;tml',$_stack_trace->evaluate());
		$time=get_timezoned_date(time(),true,true,true);
		$message=do_template('HACK_ATTEMPT_MAIL',array('_GUID'=>'6253b3c42c5e6c70d20afa9d1f5b40bd','STACK_TRACE'=>$stack_trace,'USER_AGENT'=>get_browser_string(),'REFERER'=>ocp_srv('HTTP_REFERER'),'USER_OS'=>get_os_string(),'REASON'=>$reason_full,'IP'=>$ip,'ID'=>strval($id),'USERNAME'=>$username,'TIME_RAW'=>strval(time()),'TIME'=>$time,'URL'=>$url,'POST'=>$post),get_site_default_lang());

		require_code('notifications');

		$subject=do_lang('HACK_ATTACK_SUBJECT',$ip,NULL,NULL,get_site_default_lang());
		dispatch_notification('hack_attack',NULL,$subject,$message->evaluate(get_site_default_lang(),false),NULL,A_FROM_SYSTEM_PRIVILEGED);

		if (!is_null($ip_ban_todo))
		{
			$subject=do_lang('AUTO_BAN_SUBJECT',$ip,NULL,NULL,get_site_default_lang());
			dispatch_notification('auto_ban',NULL,$subject,$ip_ban_todo,NULL,A_FROM_SYSTEM_PRIVILEGED);
		}
	}

	if ((preg_match('#^localhost[\.\:$]#',ocp_srv('HTTP_HOST'))!=0) && (substr(get_base_url(),0,17)=='http://localhost/')) fatal_exit(do_lang('HACK_ATTACK'));
	warn_exit(do_lang_tempcode('HACK_ATTACK_USER'));
}

/**
 * Add an IP-ban.
 *
 * @param  IP				The IP address to ban
 * @param  LONG_TEXT		Explanation for ban
 */
function add_ip_ban($ip,$descrip='')
{
	if (!addon_installed('securitylogging')) return;

	$GLOBALS['SITE_DB']->query_delete('usersubmitban_ip',array('ip'=>$ip),'',1);
	$GLOBALS['SITE_DB']->query_insert('usersubmitban_ip',array('ip'=>$ip,'i_descrip'=>$descrip),false,true); // To stop weird race-like conditions
	persistant_cache_delete('IP_BANS');
	if (is_writable_wrap(get_file_base().'/.htaccess'))
	{
		$original_contents=file_get_contents(get_file_base().'/.htaccess',FILE_TEXT);
		$ip_cleaned=str_replace('*','',$ip);
		$ip_cleaned=str_replace('..','.',$ip_cleaned);
		$ip_cleaned=str_replace('..','.',$ip_cleaned);
		$contents=str_replace('# deny from xxx.xx.x.x (leave this comment here!)','# deny from xxx.xx.x.x (leave this comment here!)'.chr(10).'deny from '.$ip_cleaned,$original_contents);
		if ((function_exists('file_put_contents')) && (defined('LOCK_EX'))) // Safer
		{
			if (file_put_contents(get_file_base().'/.htaccess',$contents,LOCK_EX)<strlen($contents))
			{
				file_put_contents(get_file_base().'/.htaccess',$original_contents,LOCK_EX);
				warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
			}
		} else
		{
			$myfile=fopen(get_file_base().'/.htaccess','wt');
			if (fwrite($myfile,$contents)<strlen($contents))
			{
				rewind($myfile);
				fwrite($myfile,$original_contents);
				warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
			}
			fclose($myfile);
		}
		sync_file(get_file_base().'/.htaccess');
	}
}

/**
 * Remove an IP-ban.
 *
 * @param  IP				The IP address to unban
 */
function remove_ip_ban($ip)
{
	if (!addon_installed('securitylogging')) return;

	$GLOBALS['SITE_DB']->query_delete('usersubmitban_ip',array('ip'=>$ip),'',1);
	persistant_cache_delete('IP_BANS');
	if (is_writable_wrap(get_file_base().'/.htaccess'))
	{
		$contents=file_get_contents(get_file_base().'/.htaccess',FILE_TEXT);
		$ip_cleaned=str_replace('*','',$ip);
		$ip_cleaned=str_replace('..','.',$ip_cleaned);
		$ip_cleaned=str_replace('..','.',$ip_cleaned);
		$contents=str_replace(chr(10).'deny from '.$ip_cleaned.chr(10),chr(10),$contents);
		$contents=str_replace(chr(13).'deny from '.$ip_cleaned.chr(13),chr(13),$contents); // Just in case
		$myfile=fopen(get_file_base().'/.htaccess','wt');
		if (fwrite($myfile,$contents)<strlen($contents)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
		fclose($myfile);
		sync_file('.htaccess');
	}
	$GLOBALS['SITE_DB']->query_delete('hackattack',array('ip'=>$ip));
}

/**
 * Lookup error on ocportal.com, to see if there is more information.
 *
 * @param  mixed				The error message (string or tempcode)
 * @return ?string			The result from the web service (NULL: no result)
 */
function get_webservice_result($error_message)
{
	if (get_domain()=='ocportal.com') return NULL;

	if ((!function_exists('has_zone_access')) || (!has_zone_access(get_member(),'adminzone'))) return NULL;

	require_code('files');
	global $DONE_ONE_WEB_SERVICE;
	if (($GLOBALS['DOWNLOAD_LEVEL']>0) || ($DONE_ONE_WEB_SERVICE)) return NULL;
	$DONE_ONE_WEB_SERVICE=true;

	if (is_object($error_message)) $error_message=$error_message->evaluate();

	if ($GLOBALS['HTTP_STATUS_CODE']=='401') return NULL;

	// Get message IN ENGLISH
	if (user_lang()!=fallback_lang())
	{
		global $LANGUAGE;
		foreach ($LANGUAGE as $_)
		{
			foreach ($_ as $key=>$val)
			{
				$regexp=preg_replace('#\\\{\d+\\\}#','.*',str_replace('#','\#',preg_quote($val)));
				if ($regexp!='.*')
				{
					if (preg_match('#'.$regexp.'#',$error_message)!=0)
					{
						$_error_message=do_lang($key,'','','',fallback_lang(),false);
						if (!is_null($_error_message)) $error_message=$_error_message;
						break;
					}
				}
			}
		}
	}

	// Talk to web service
	$brand=get_value('rebrand_name');
	if (is_null($brand)) $brand='ocPortal';

	$result=http_download_file('http://ocportal.com/uploads/website_specific/ocportal.com/scripts/errorservice.php?version='.float_to_raw_string(ocp_version_number()).'&error_message='.rawurlencode($error_message).'&product='.rawurlencode($brand),NULL,false);
	if ($GLOBALS['HTTP_DOWNLOAD_MIME_TYPE']!='text/plain') return NULL;

	if ($result=='') return NULL;
	if (function_exists('ocp_mark_as_escaped')) ocp_mark_as_escaped($result);
	return $result;
}

/**
 * Do a fatal exit, echo the header (if possible) and an error message, followed by a debugging back-trace.
 * It also adds an entry to the error log, for reference.
 *
 * @param  mixed				The error message (string or tempcode)
 * @param  boolean			Whether to return
 */
function _fatal_exit($text,$return=false)
{
	@ob_end_clean(); // Incase in minimodule

	if (!headers_sent())
	{
		require_code('firephp');
		if (function_exists('fb'))
			fb('Error: '.(is_object($text)?$text->evaluate():$text));
	}

	if (running_script('occle'))
	{
		header('Content-Type: text/xml');
		header('HTTP/1.0 200 Ok');

		header('Content-type: text/xml');
		$output='<'.'?xml version="1.0" encoding="utf-8" ?'.'>
<response>
	<result>
		<command>'.post_param('command','').'</command>
		<stdcommand></stdcommand>
		<stdhtml><div xmlns="http://www.w3.org/1999/xhtml">'.((get_param_integer('keep_fatalistic',0)==1)?static_evaluate_tempcode(get_html_trace()):'').'</div></stdhtml>
		<stdout>'.xmlentities(is_object($text)?str_replace(array('&ldquo;','&rdquo;'),array('"','"'),html_entity_decode(strip_tags($text->evaluate()),ENT_QUOTES,get_charset())):$text).'</stdout>
		<stderr>'.xmlentities(do_lang('EVAL_ERROR')).'</stderr>
		<stdnotifications><div xmlns="http://www.w3.org/1999/xhtml"></div></stdnotifications>
	</result>
</response>';

		if ($GLOBALS['XSS_DETECT'])
			ocp_mark_as_escaped($output);

		exit($output);
	}

	$GLOBALS['HTTP_STATUS_CODE']='500';
	if (!headers_sent())
	{
		if (function_exists('browser_matches'))
			if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 500 Internal server error');
		header('Content-type: text/html; charset='.get_charset());
		header('Content-Disposition: inline');
	}

	//$x=@ob_get_contents(); @ob_end_clean(); //if (is_string($x)) @print($x);	Disabled as causes weird crashes

	if ((array_key_exists('MSN_DB',$GLOBALS)) && (!is_null($GLOBALS['MSN_DB'])))
	{
		$GLOBALS['FORUM_DB']=$GLOBALS['MSN_DB'];
		$GLOBALS['MSN_DB']=NULL;
	}

	// Supplement error message with some useful info
	if ((function_exists('ocp_version_full')) && (function_exists('ocp_srv')))
	{
		$sup=' (version: '.ocp_version_full().', PHP version: '.phpversion().', URL: '.ocp_srv('REQUEST_URI').')';
	} else
	{
		$sup='';
	}
	if (is_object($text))
	{
		if ($text->pure_lang) $sup=escape_html($sup);
		$text->attach($sup);
	} else
	{
		$text.=$sup;
	}

	// To break any looping of errors
//@var_dump(debug_backtrace());@exit($text); // Useful if things go a bit nuts and error won't come out
	global $EXITING;
	if ((!function_exists('do_header')) || (!function_exists('die_html_trace'))) $EXITING++; //exit(escape_html($text));
	$EXITING++;
	if (($EXITING>1) || (running_script('upgrader')) || (!class_exists('ocp_tempcode')))
	{
		if (($EXITING<3) && (function_exists('may_see_stack_dumps')) && (may_see_stack_dumps()) && ($GLOBALS['HAS_SET_ERROR_HANDLER']))
		{
			die_html_trace(is_object($text)?$text->evaluate():escape_html($text));
		} else
		{
			critical_error('EMERGENCY',is_object($text)?$text->evaluate():escape_html($text));
		}
	}

	if (may_see_stack_dumps())
	{
		$trace=get_html_trace();
	} else
	{
		$trace=paragraph(do_lang_tempcode('STACK_TRACE_DENIED_ERROR_NOTIFICATION'),'yrthrty4ttewdf');
	}

	$title=get_page_title('ERROR_OCCURRED');

	if (get_param_integer('keep_fatalistic',0)==0)
		@error_log('ocPortal:  '.(is_object($text)?$text->evaluate():$text).' @ '.get_self_url_easy(),0);

	$error_tpl=do_template('FATAL_SCREEN',array('_GUID'=>'9fdc6d093bdb685a0eda6bb56988a8c5','TITLE'=>$title,'WEBSERVICE_RESULT'=>get_webservice_result($text),'MESSAGE'=>$text,'TRACE'=>$trace));
	$echo=globalise($error_tpl,NULL,'',true);
	$echo->evaluate_echo();

	if (get_param_integer('keep_fatalistic',0)==0)
	{
		$trace=get_html_trace();
		$error_tpl=do_template('FATAL_SCREEN',array('_GUID'=>'9fdc6d093bdb685a0eda6bb56988a8c5','TITLE'=>$title,'WEBSERVICE_RESULT'=>get_webservice_result($text),'MESSAGE'=>$text,'TRACE'=>$trace));
		relay_error_notification((is_object($text)?$text->evaluate():$text).'[html]'.$error_tpl->evaluate().'[/html]');
	}

	if (!$return) exit();
}

/**
 * Relay an error message, if appropriate, to e-mail listeners (sometimes ocProducts, and site staff).
 *
 * @param  string			A error message (in HTML)
 * @param  boolean		Also send to ocProducts
 * @param  ID_TEXT		The notification type
 */
function relay_error_notification($text,$ocproducts=true,$notification_type='error_occurred')
{
	// Make sure we don't send too many error emails
	if ((function_exists('get_value')) && ($GLOBALS['BOOTSTRAPPING']==0) && (array_key_exists('SITE_DB',$GLOBALS)) && (!is_null($GLOBALS['SITE_DB'])))
	{
		$num=intval(get_value('num_error_mails_'.date('Y-m-d')))+1;
		if ($num==51) return; // We've sent too many error mails today
		$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'values WHERE the_name LIKE \''.db_encode_like('num\_error\_mails\_%').'\'');
		persistant_cache_delete('VALUES');
		set_value('num_error_mails_'.date('Y-m-d'),strval($num));
	}

	if (!function_exists('require_lang')) return;

	require_code('urls');
	require_code('tempcode');

	$error_url=running_script('index')?static_evaluate_tempcode(build_url(array('page'=>'_SELF'),'_SELF',NULL,true,false,true)):get_self_url_easy();

	require_code('notifications');
	require_code('comcode');
	$mail=do_lang('ERROR_MAIL',comcode_escape($error_url),$text,NULL,get_site_default_lang());
	dispatch_notification($notification_type,NULL,do_lang('ERROR_OCCURRED_SUBJECT',get_page_name(),NULL,NULL,get_site_default_lang()),$mail,NULL,A_FROM_SYSTEM_PRIVILEGED);
	if (
		($ocproducts) && 
		(get_option('send_error_emails_ocproducts',true)=='1') && 
		(!running_script('cron_bridge')) && 
		(strpos($text,'_custom/')===false) && 
		(strpos($text,'data/occle.php')===false) && 
		(strpos($text,'/mini')===false) && 
		(strpos($text,'&#')===false/*charset encoding issue*/) && 
		(strpos($text,'has been disabled for security reasons')===false) && 
		(strpos($text,'max_questions')/*mysql limit*/===false) && 
		(strpos($text,'Error at offset')===false) && 
		(strpos($text,'Unable to allocate memory for pool')===false) && 
		(strpos($text,'Out of memory')===false) && 
		(strpos($text,'Disk is full writing')===false) && 
		(strpos($text,'Disk quota exceeded')===false) && 
		(strpos($text,'from storage engine')===false) && 
		(strpos($text,'Lost connection to MySQL server')===false) && 
		(strpos($text,'Unable to save result set')===false) && 
		(strpos($text,'.MYI')===false) && 
		(strpos($text,'MySQL server has gone away')===false) && 
		(strpos($text,'Incorrect key file')===false) && 
		(strpos($text,'Too many connections')===false) && 
		(strpos($text,'marked as crashed and should be repaired')===false) && 
		(strpos($text,'connect to')===false) && 
		(strpos($text,'Access denied for')===false) && 
		(strpos($text,'Unknown database')===false) && 
		(strpos($text,'headers already sent')===false) && 
		(preg_match('#Maximum execution time of \d+ seconds#',$text)==0) && 
		(preg_match('#Out of memory \(allocated (1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24)\d{6}\)#',$text)==0) && 
		(strpos($text,'is marked as crashed and last')===false) && 
		(strpos($text,'failed to open stream: Permission denied')===false) && 
		(strpos($text,'phpinfo() has been disabled')===false) && 
		((strpos($text,'Maximum execution time')===false) || ((strpos($text,'/js_')===false) && (strpos($text,'/caches_filesystem.php')===false) && (strpos($text,'/files2.php')===false))) && 
		((strpos($text,'doesn\'t exist')===false) || ((strpos($text,'import')===false))) && 
		((strpos($text,'No such file or directory')===false) || ((strpos($text,'admin_setupwizard')===false))) && 
		(strpos($text,'File(/tmp/) is not within the allowed path')===false)
	)
	{
		require_code('mail');
		mail_wrap(do_lang('ERROR_OCCURRED_SUBJECT',get_page_name(),NULL,NULL,get_site_default_lang()).' '.ocp_version_full(),$mail,array('errors_final'.strval(ocp_version()).'@ocportal.com'),'','','',3,NULL,true,NULL,true);
	}
	if (($ocproducts) && (!is_null(get_value('agency_email_address'))))
	{
		require_code('mail');
		$agency_email_address=get_value('agency_email_address');
		mail_wrap(do_lang('ERROR_OCCURRED_SUBJECT',get_page_name(),NULL,NULL,get_site_default_lang()).' '.ocp_version_full(),$mail,array($agency_email_address),'','','',3,NULL,true,NULL,true);
	}
}

/**
 * Find whether the current user may see stack dumps.
 *
 * @return boolean			Whether the current user may see stack dumps
 */
function may_see_stack_dumps()
{
	if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) return true; // myOCP exception
	if ((function_exists('ocp_srv')) && (ocp_srv('REQUEST_METHOD')=='')) return true; // Command line
	if ((function_exists('running_script')) && (running_script('upgrader'))) return true;
	if (!function_exists('get_member')) return false;
	if (!function_exists('has_specific_permission')) return false;
	if ($GLOBALS['IS_ACTUALLY_ADMIN']) return true;

	return (get_domain()=='localhost') || (has_specific_permission(get_member(),'see_stack_dump'));
}

/**
 * Echo an error message, and a debug back-trace of the current execution stack. Use this for debugging purposes.
 *
 * @param  string			An error message
 */
function die_html_trace($message)
{
	if (!function_exists('debug_backtrace')) critical_error('EMERGENCY',$message);
	if (!function_exists('var_export')) critical_error('EMERGENCY',$message);
	//$x=@ob_get_contents(); @ob_end_clean(); //if (is_string($x)) @print($x);	Disabled as causes weird crashes
	$_trace=debug_backtrace();
	$trace='<div class="medborder medborder_box"><h2>Stack trace&hellip;</h2>';
	foreach ($_trace as $stage)
	{
		$traces='';
		foreach ($stage as $key=>$value)
		{
			if ((is_object($value) && (is_a($value,'ocp_tempcode'))) || (is_null($value)) || (is_array($value) && (strlen(serialize($value))>MAX_STACK_TRACE_VALUE_LENGTH)))
			{
				$_value=gettype($value);
			} else
			{
				@ob_start();
				/*var_dump*/var_export($value);
				$_value=ob_get_contents();
				ob_end_clean();
			}

			global $SITE_INFO;
			if (is_object($_value)) $_value=$_value->evaluate();
			if ((isset($SITE_INFO['db_site_password'])) && (strlen($SITE_INFO['db_site_password'])>4))
				$_value=str_replace($SITE_INFO['db_site_password'],'(password removed)',$_value);
			if ((isset($SITE_INFO['db_forums_password'])) && (strlen($SITE_INFO['db_forums_password'])>4))
				$_value=str_replace($SITE_INFO['db_forums_password'],'(password removed)',$_value);

			$traces.=ucfirst($key).' -> '.escape_html($_value).'<br />'.chr(10);
		}
		$trace.='<p>'.$traces.'</p>'.chr(10);
	}
	$trace.='</div>';

	if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($trace);

	critical_error('EMERGENCY',$message.$trace);
}

/**
 * Return a debugging back-trace of the current execution stack. Use this for debugging purposes.
 *
 * @return tempcode		Debugging backtrace
 */
function get_html_trace()
{
	if (!function_exists('debug_backtrace')) return new ocp_tempcode();
	if (!function_exists('var_export')) return new ocp_tempcode();
	//$x=@ob_get_contents(); @ob_end_clean(); //if (is_string($x)) @print($x);	Disabled as causes weird crashes
	$GLOBALS['SUPPRESS_ERROR_DEATH']=true;
	$_trace=debug_backtrace();
	$trace=new ocp_tempcode();
	foreach ($_trace as $i=>$stage)
	{
		$traces=new ocp_tempcode();
//		if (in_array($stage['function'],array('get_html_trace','ocportal_error_handler','fatal_exit'))) continue;
		$file='';
		$line='';
		$__value=mixed();
		foreach ($stage as $key=>$__value)
		{
			if ($key=='file') $file=str_replace('\'','',$__value);
			elseif ($key=='line') $line=strval($__value);
			if ($key=='args')
			{
				$_value=new ocp_tempcode();
				foreach ($__value as $param)
				{
					if (!((is_array($param)) && (array_key_exists('GLOBALS',$param)))) // Some versions of PHP give the full environment as parameters. This will cause a recursive issue when outputting due to GLOBALS->ENV chaining.
					{
						if ((is_object($param) && (is_a($param,'ocp_tempcode'))) || (is_null($param)) || ((is_array($param)) && (defined('HIPHOP_PHP'))) || (is_null($param)) || (strpos(serialize($param),';R:')!==false))
						{
							$__value=gettype($param);
						} else
						{
							@ob_start();
							/*var_dump*/var_export($param);
							$__value=ob_get_contents();
							ob_end_clean();
						}
						if ((strlen($__value)<MAX_STACK_TRACE_VALUE_LENGTH) || (defined('HIPHOP_PHP')))
						{
							$_value->attach(paragraph(escape_html($__value)));
						} else
						{
							$_value=make_string_tempcode(escape_html('...'));
						}
					}
				}
			} else
			{
				$value=mixed();
				if (is_float($__value))
					$value=float_format($__value);
				elseif (is_integer($__value))
					$value=integer_format($__value);
				else $value=$__value;

				if ((is_object($value) && (is_a($value,'ocp_tempcode'))) || (is_null($value)) || (is_array($value) && (strlen(serialize($value))>MAX_STACK_TRACE_VALUE_LENGTH)) || (strpos(serialize($value),';R:')!==false))
				{
					$_value=make_string_tempcode(escape_html(gettype($value)));
				} else
				{
					@ob_start();
					/*var_dump*/var_export($value);
					$_value=make_string_tempcode(escape_html(ob_get_contents()));
					ob_end_clean();
				}
			}

			global $SITE_INFO;
			if (is_object($_value)) $_value=$_value->evaluate();
			if ((isset($SITE_INFO['db_site_password'])) && (strlen($SITE_INFO['db_site_password'])>4))
				$_value=str_replace($SITE_INFO['db_site_password'],'(password removed)',$_value);
			if ((isset($SITE_INFO['db_forums_password'])) && (strlen($SITE_INFO['db_forums_password'])>4))
				$_value=str_replace($SITE_INFO['db_forums_password'],'(password removed)',$_value);

			$traces->attach(do_template('STACK_TRACE_LINE',array('_GUID'=>'40752b5212f56534ebe7970baa638e5a','LINE'=>$line,'FILE'=>$file,'KEY'=>ucfirst($key),'VALUE'=>$_value)));
		}
		$trace->attach(do_template('STACK_TRACE_WRAP',array('_GUID'=>'beb78896baefd0f623c1c480840dace1','TRACES'=>$traces)));
	}
	$GLOBALS['SUPPRESS_ERROR_DEATH']=false;

	return do_template('STACK_TRACE_HYPER_WRAP',array('_GUID'=>'9620695fb8c3e411a6a4926432cea64f','POST'=>(count($_POST)<200)?$_POST:array(),'CONTENT'=>$trace));
}

/**
 * Show a helpful access-denied page. Has a login ability if it senses that logging in could curtail the error.
 *
 * @param  ID_TEXT		The class of error (e.g. SPECIFIC_PERMISSION)
 * @param  string			The parameteter given to the error message
 * @param  boolean		Force the user to login (even if perhaps they are logged in already)
 */
function _access_denied($class,$param,$force_login)
{
	$GLOBALS['HTTP_STATUS_CODE']='401';
	if (!headers_sent())
	{
		if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 401 Unauthorized'); // Stop spiders ever storing the URL that caused this
	}

	require_lang('permissions');
	require_lang('ocf_config');

	$match_keys=$GLOBALS['SITE_DB']->query_select('match_key_messages',array('k_message','k_match_key'));
	global $M_SORT_KEY;
	$M_SORT_KEY='k_match_key';
	usort($match_keys,'strlen_sort');
	$match_keys=array_reverse($match_keys);
	$message=NULL;
	foreach ($match_keys as $match_key)
	{
		if (match_key_match($match_key['k_match_key']))
		{
			$message=get_translated_tempcode($match_key['k_message']);
		}
	}
	if (is_null($message))
	{
		if (strpos($class,' ')!==false)
		{
			$message=make_string_tempcode($class);
		} else
		{
			if ($class=='SPECIFIC_PERMISSION') $param=do_lang('PT_'.$param);
			$message=do_lang_tempcode('ACCESS_DENIED__'.$class,escape_html($GLOBALS['FORUM_DRIVER']->get_username(get_member())),escape_html($param));
		}
	}

	// Run hooks, if any exist
	$hooks=find_all_hooks('systems','upon_access_denied');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/upon_access_denied/'.filter_naughty($hook));
		$ob=object_factory('Hook_upon_access_denied_'.filter_naughty($hook),true);
		if (is_null($ob)) continue;
		$ob->run($class,$param,$force_login);
	}

	require_code('site');
	log_stats('/access_denied',0);

	if (((is_guest()) && ($GLOBALS['NON_PAGE_SCRIPT']==0)) || ($force_login))
	{
		@ob_end_clean();

		$redirect=get_self_url(true,true,array('page'=>get_param('page',''))); // We have to pass in 'page' because an access-denied situation tells get_page_name() (which get_self_url() relies on) that we are on page ''.
		$_GET['redirect']=$redirect;
		$_GET['page']='login';
		$_GET['type']='misc';
		global $PAGE_NAME_CACHE;
		$PAGE_NAME_CACHE='login';

		$middle=load_module_page(_get_module_path('','login'),'login');
		require_code('site');
		attach_message($message,'warn');
		$echo=globalise($middle,NULL,'',true);
		$echo->evaluate_echo();
		exit();
	}

	//if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) fatal_exit($message);
	warn_exit($message);
}

