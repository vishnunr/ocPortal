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
 * @package		points
 */

/**
 * Module page class.
 */
class Module_admin_points
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
		return array('misc'=>'GIFT_TRANSACTIONS');
	}
	
	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$GLOBALS['HELPER_PANEL_PIC']='points';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_points';

		require_code('points');
		require_css('points');
		require_lang('points');
	
		$type=get_param('type','misc');

		if ($type=='charge') return $this->points_charge();
		if ($type=='reverse') return $this->reverse();
		if ($type=='misc') return $this->points_log();

		return new ocp_tempcode();
	}

	/**
	 * The UI to view all point transactions ordered by date.
	 *
	 * @return tempcode		The UI
	 */
	function points_log()
	{
		$title=get_page_title('GIFT_TRANSACTIONS');
		
		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);
		$sortables=array('date_and_time'=>do_lang_tempcode('DATE'),'amount'=>do_lang_tempcode('AMOUNT'));
		$test=explode(' ',get_param('sort','date_and_time DESC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$max_rows=$GLOBALS['SITE_DB']->query_value('gifts','COUNT(*)');
		$rows=$GLOBALS['SITE_DB']->query_select('gifts',array('*'),NULL,'ORDER BY '.$sortable.' '.$sort_order,$max,$start);
		if (count($rows)==0)
		{
			return inform_screen($title,do_lang_tempcode('NO_ENTRIES'));
		}
		$fields=new ocp_tempcode();
		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('DATE'),do_lang_tempcode('AMOUNT'),do_lang_tempcode('FROM'),do_lang_tempcode('TO'),do_lang_tempcode('REASON'),do_lang_tempcode('REVERSE')),$sortables,'sort',$sortable.' '.$sort_order);
		foreach ($rows as $myrow)
		{
			$date=get_timezoned_date($myrow['date_and_time']);
			$reason=get_translated_tempcode($myrow['reason']);
			if (is_guest($myrow['gift_to']))
			{
				$to=do_lang_tempcode('USER_SYSTEM');
			} else
			{
				$toname=$GLOBALS['FORUM_DRIVER']->get_username($myrow['gift_to']);
				$tourl=build_url(array('page'=>'points','type'=>'member','id'=>$myrow['gift_to']),get_module_zone('points'));
				$to=is_null($toname)?do_lang_tempcode('UNKNOWN_EM'):hyperlink($tourl,escape_html($toname));
			}
			if (is_guest($myrow['gift_from']))
			{
				$from=do_lang_tempcode('USER_SYSTEM');
			} else
			{
				$fromname=$GLOBALS['FORUM_DRIVER']->get_username($myrow['gift_from']);
				$fromurl=build_url(array('page'=>'points','type'=>'member','id'=>$myrow['gift_from']),get_module_zone('points'));
				$from=is_null($fromname)?do_lang_tempcode('UNKNOWN_EM'):hyperlink($fromurl,escape_html($fromname));
			}
			$deleteurl=build_url(array('page'=>'_SELF','type'=>'reverse','redirect'=>get_self_url(true)),'_SELF');
			$delete=hyperlink($deleteurl,do_lang_tempcode('REVERSE'),false,false,'',NULL,form_input_hidden('id',strval($myrow['id'])));

			$fields->attach(results_entry(array($date,$myrow['amount'],$from,$to,$reason,$delete),true));
		}

		$results_table=results_table(do_lang_tempcode('GIFT_TRANSACTIONS'),$start,'start',$max,'max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort',paragraph(do_lang_tempcode('GIFT_POINTS_LOG')));

		return do_template('RESULTS_TABLE_SCREEN',array('_GUID'=>'12ce8cf5c2f669948b14e68bd6c00fe9','TITLE'=>$title,'RESULTS_TABLE'=>$results_table));
	}

	/**
	 * The actualiser to reverse a point gift transaction.
	 *
	 * @return tempcode		The UI
	 */
	function reverse()
	{
		$title=get_page_title('REVERSE_TITLE');

		$id=post_param_integer('id');
		$rows=$GLOBALS['SITE_DB']->query_select('gifts',array('*'),array('id'=>$id),'',1);
		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$myrow=$rows[0];
		$amount=$myrow['amount'];
		$sender_id=$myrow['gift_from'];
		$recipient_id=$myrow['gift_to'];

		$confirm=get_param_integer('confirm',0);
		if ($confirm==0)
		{
			$_sender_id=(is_guest($sender_id))?get_site_name():$GLOBALS['FORUM_DRIVER']->get_username($sender_id);
			$_recipient_id=(is_guest($recipient_id))?get_site_name():$GLOBALS['FORUM_DRIVER']->get_username($recipient_id);
			if (is_null($_sender_id)) $_sender_id=do_lang('UNKNOWN');
			if (is_null($_recipient_id)) $_recipient_id=do_lang('UNKNOWN');
			$preview=do_lang_tempcode('ARE_YOU_SURE_REVERSE',escape_html(integer_format($amount)),escape_html($_sender_id),escape_html($_recipient_id));
			return do_template('CONFIRM_SCREEN',array('_GUID'=>'d3d654c7dcffb353638d08b53697488b','TITLE'=>$title,'PREVIEW'=>$preview,'URL'=>get_self_url(false,false,array('confirm'=>1)),'FIELDS'=>build_keep_post_fields()));
		}

		$GLOBALS['SITE_DB']->query_delete('gifts',array('id'=>$id),'',1);
		if (!is_guest($sender_id))
		{
			$_sender_gift_points_used=point_info($sender_id);
			$sender_gift_points_used=array_key_exists('gift_points_used',$_sender_gift_points_used)?$_sender_gift_points_used['gift_points_used']:0;
			$GLOBALS['FORUM_DRIVER']->set_custom_field($sender_id,'gift_points_used',strval($sender_gift_points_used-$amount));
		}
		$temp_points=point_info($recipient_id);
		$GLOBALS['FORUM_DRIVER']->set_custom_field($recipient_id,'points_gained_given',strval((array_key_exists('points_gained_given',$temp_points)?$temp_points['points_gained_given']:0)-$amount));

		// Show it worked / Refresh
		$url=get_param('redirect',NULL);
		if (is_null($url))
		{
			$_url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
			$url=$_url->evaluate();
		}
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The actualiser to charge a member points.
	 *
	 * @return tempcode		The UI
	 */
	function points_charge()
	{
		$title=get_page_title('CHARGE_USER');
	
		$member=post_param_integer('user');
		$amount=post_param_integer('amount');
		$reason=post_param('reason');

		require_code('points2');
		charge_member($member,$amount,$reason);
		$left=available_points($member);
	
		$username=$GLOBALS['FORUM_DRIVER']->get_username($member);
		if (is_null($username)) $username=do_lang('UNKNOWN');
		$text=do_lang_tempcode('USER_HAS_BEEN_CHARGED',escape_html($username),escape_html(integer_format($amount)),escape_html(integer_format($left)));
	
		// Show it worked / Refresh
		$url=get_param('redirect',NULL);
		if (is_null($url))
		{
			$_url=build_url(array('page'=>'points','type'=>'member','id'=>$member),get_module_zone('points'));
			$url=$_url->evaluate();
		}
		return redirect_screen($title,$url,$text);
	}

}


