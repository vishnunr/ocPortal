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
 * Get template-ready details of members viewing the specified ocPortal location.
 *
 * @param  ?ID_TEXT		The page they need to be viewing (NULL: don't care)
 * @param  ?ID_TEXT		The page-type they need to be viewing (NULL: don't care)
 * @param  ?SHORT_TEXT	The type-id they need to be viewing (NULL: don't care)
 * @param  boolean		Whether this has to be done over the forum driver (multi site network)
 * @return ?array			A map of member-ids to rows about them (NULL: Too many)
 */
function get_members_viewing_wrap($page=NULL,$type=NULL,$id=NULL,$forum_layer=false)
{
	$members=is_null($id)?array():get_members_viewing($page,$type,$id,$forum_layer);
	$num_guests=0;
	$num_members=0;
	if (is_null($members))
	{
		$members_viewing=new ocp_tempcode();
	} else
	{
		$members_viewing=new ocp_tempcode();
		if (!isset($members[get_member()]))
		{
			$members[get_member()]=array('mt_cache_username'=>$GLOBALS['FORUM_DRIVER']->get_username(get_member()));
		}
		foreach ($members as $member_id=>$at_details)
		{
			$username=$at_details['mt_cache_username'];

			if (is_guest($member_id))
			{
				$num_guests++;
			} else
			{
				$num_members++;
				$profile_url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,false,true);
				$map=array('PROFILE_URL'=>$profile_url,'USERNAME'=>$username);
				if (isset($at_details['the_title']))
				{
					if ((has_specific_permission(get_member(),'show_user_browsing')) || ((in_array($at_details['the_page'],array('topics','topicview'))) && ($at_details['the_id']==$id)))
					{
						$map['AT']=escape_html($at_details['the_title']);
					}
				}
				$map['COLOUR']=get_group_colour(ocf_get_member_primary_group($member_id));
				$members_viewing->attach(do_template('OCF_USER_MEMBER',$map));
			}
		}
		if ($members_viewing->is_empty()) $members_viewing=do_lang_tempcode('NONE_EM');
	}

	return array($num_guests,$num_members,$members_viewing);
}

/**
 * Convert a string to an array, with utf-8 awareness where possible/required.
 *
 * @param  string			Input
 * @return array			Output
 */
function ocp_mb_str_split($str)
{
	$len=ocp_mb_strlen($str);
	$array=array();
	for ($i=0;$i<$len;$i++)
	{
		$array[]=ocp_mb_substr($str,$i,1);
	}
	return $array;
}

/**
 * Split a string into smaller chunks, with utf-8 awareness where possible/required. Can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode output to match RFC 2045 semantics. It inserts end (defaults to "\r\n") every chunklen characters.
 *
 * @param  string		The input string.
 * @param  integer	The maximum chunking length.
 * @param  string		Split character.
 * @return string		The chunked version of the input string.
 */
function ocp_mb_chunk_split($str,$len=76,$glue="\r\n")
{
	if ($str=='') return '';
	$array=ocp_mb_str_split($str);
	$n=-1;
	$new='';
	foreach ($array as $char)
	{
		$n++;
		if ($n<$len)
		{
			$new.=$char;
		}
		elseif ($n==$len)
		{
			$new.=$glue.$char;
			$n=0;
		}
	}
	return $new;
}

/**
 * Shuffle an array into an array of columns.
 *
 * @param  integer		The number of columns
 * @param  array			The source array
 * @return array			Array of columns
 */
function shuffle_for($by,$in)
{
	// Split evenly into $by equal piles (if won't equally divide, bias so the last one is the smallest via 'ceil')
	$out_piles=array();
	for ($i=0;$i<$by;$i++)
	{
		$out_piles[$i]=array();
	}
	$cnt=count($in);
	$split_point=intval(ceil(floatval($cnt)/floatval($by)));
	$next_split_point=$split_point;
	$for=0;
	for ($i=0;$i<$cnt;$i++)
	{
		if ($i>=$next_split_point)
		{
			$for++;
			$next_split_point+=intval(ceil(floatval($cnt-$i)/floatval($by-$for)));
		}

		$out_piles[$for][]=$in[$i];
	}

	// Take one from each pile in turn until none are left, putting into $out
	$out=array();
	while (true)
	{
		for ($j=0;$j<$by;$j++)
		{
			$next=array_shift($out_piles[$j]);
			if (!is_null($next)) $out[]=$next; else break 2;
		}
	}

	// $out now holds our result
	return $out;
}

/**
 * Check to see if an IP address is banned.
 *
 * @param  string			The IP address to check for banning (potentially encoded with *'s)
 * @param  boolean		Force check via database
 * @param  boolean		Handle uncertainities (used for the external bans - if true, we may return NULL, showing we need to do an external check). Only works with $force_db.
 * @return ?boolean		Whether the IP address is banned (NULL: unknown)
 */
function ip_banned($ip,$force_db=false,$handle_uncertainties=false) // This is the very first query called, so we will be a bit smarter, checking for errors
{
	static $cache=array();
	if ($handle_uncertainties)
	{
		if (array_key_exists($ip,$cache)) return $cache[$ip];
	}

	if (!addon_installed('securitylogging')) return false;

	// Check exclusions first
	$_exclusions=get_option('spam_check_exclusions',true);
	if (!is_null($_exclusions))
	{
		$exclusions=explode(',',$_exclusions);
		foreach ($exclusions as $exclusion)
		{
			if (trim($ip)==$exclusion) return false;
		}
	}

	$ip4=(strpos($ip,'.')!==false);
	if ($ip4)
	{
		$ip_parts=explode('.',$ip);
	} else
	{
		$ip_parts=explode(':',$ip);
	}

	global $SITE_INFO;
	if ((!$force_db) && (((isset($SITE_INFO['known_suexec'])) && ($SITE_INFO['known_suexec']=='1')) || (is_writable_wrap(get_file_base().'/.htaccess'))))
	{
		$bans=array();
		$ban_count=preg_match_all('#\ndeny from (.*)#',file_get_contents(get_file_base().'/.htaccess'),$bans);
		$ip_bans=array();
		for ($i=0;$i<$ban_count;$i++)
		{
			$ip_bans[]=array('ip'=>$bans[1][$i]);
		}
	} else
	{
		$ip_bans=persistent_cache_get('IP_BANS');
		if (is_null($ip_bans))
		{
			$ip_bans=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'usersubmitban_ip',NULL,NULL,true);
			if (!is_null($ip_bans))
			{
				persistent_cache_set('IP_BANS',$ip_bans);
			}
		}
		if (is_null($ip_bans)) critical_error('DATABASE_FAIL');
	}
	$self_ip=NULL;
	foreach ($ip_bans as $ban)
	{
		if ((isset($ban['i_ban_until'])) && ($ban['i_ban_until']<time()))
		{
			if (!$GLOBALS['SITE_DB']->table_is_locked('usersubmitban_ip'))
				$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'usersubmitban_ip WHERE i_ban_until IS NOT NULL AND i_ban_until<'.strval(time()));
			continue;
		}

		if ((($ip4) && (compare_ip_address_ip4($ban['ip'],$ip_parts))) || ((!$ip4) && (compare_ip_address_ip6($ban['ip'],$ip_parts))))
		{
			if (is_null($self_ip))
			{
				$self_host=ocp_srv('HTTP_HOST');
				if (($self_host=='') || (preg_match('#^localhost[\.\:$]#',$self_host)!=0))
				{
					$self_ip='';
				} else
				{
					if (preg_match('#(\s|,|^)gethostbyname(\s|$|,)#i',@ini_get('disable_functions'))==0)
					{
						$self_ip=gethostbyname($self_host);
					} else $self_ip='';
					if ($self_ip=='') $self_ip=ocp_srv('SERVER_ADDR');
				}
			}

			if (($self_ip!='') && (!compare_ip_address($ban['ip'],$self_ip))) continue;
			if (compare_ip_address($ban['ip'],'127.0.0.1')) continue;
			if (compare_ip_address($ban['ip'],'fe00:0000:0000:0000:0000:0000:0000:0000')) continue;

			if (array_key_exists('i_ban_positive',$ban))
			{
				$ret=($ban['i_ban_positive']==1);
			} else
			{
				$ret=true;
			}

			if ($handle_uncertainties)
			{
				$cache[$ip]=$ret;
			}
			return $ret;
		}
	}

	$ret=$handle_uncertainties?NULL:false;
	if ($handle_uncertainties)
	{
		$cache[$ip]=$ret;
	}
	return $ret;
}

/**
 * Log an action
 *
 * @param  ID_TEXT		The type of activity just carried out (a lang string)
 * @param  ?SHORT_TEXT	The most important parameter of the activity (e.g. id) (NULL: none)
 * @param  ?SHORT_TEXT	A secondary (perhaps, human readable) parameter of the activity (e.g. caption) (NULL: none)
 */
function _log_it($type,$a=NULL,$b=NULL)
{
	if (!function_exists('get_member')) return; // If this is during installation

	if ((get_option('site_closed')=='1') && (get_option('no_stats_when_closed',true)==='1')) return;

	// Run hooks, if any exist
	$hooks=find_all_hooks('systems','upon_action_logging');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/upon_action_logging/'.filter_naughty($hook));
		$ob=object_factory('upon_action_logging'.filter_naughty($hook),true);
		if (is_null($ob)) continue;
		$ob->run($type,$a,$b);
	}

	$ip=get_ip_address();
	$GLOBALS['SITE_DB']->query_insert('adminlogs',array('the_type'=>$type,'param_a'=>is_null($a)?'':substr($a,0,80),'param_b'=>is_null($b)?'':substr($b,0,80),'date_and_time'=>time(),'the_user'=>get_member(),'ip'=>$ip));

	decache('side_tag_cloud');
	decache('main_staff_actions');
	decache('main_staff_checklist');
	decache('main_awards');
	decache('main_multi_content');
	decache('side_stored_menu'); // Due to the content counts in the CMS/Admin Zones

	if ((get_page_name()!='admin_themewizard') && (get_page_name()!='admin_import'))
	{
		require_all_lang();
		static $logged=0;
		$logged++;
		if ($logged<10) // Be extra sure it's not some kind of import, causing spam
		{
			if (is_null($a)) $a=do_lang('NA');
			if (is_null($a)) $a=do_lang('NA');
			require_code('notifications');
			$subject=do_lang('ACTIONLOG_NOTIFICATION_MAIL_SUBJECT',get_site_name(),do_lang($type),array($a,$b));
			$mail=do_lang('ACTIONLOG_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape(do_lang($type)),array(is_null($a)?'':comcode_escape($a),is_null($b)?'':comcode_escape($b)));
			dispatch_notification('actionlog',$type,$subject,$mail);
		}
	}
}

