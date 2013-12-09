<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_abstract_interfaces
 */

/**
 * Standard code module initialisation function.
 */
function init__templates_pagination()
{
	global $INCREMENTAL_ID_GENERATOR;
	$INCREMENTAL_ID_GENERATOR=1;
}

/**
 * Get the tempcode for a results browser.
 *
 * @param  tempcode		The title/name of the resource we are browsing through
 * @param  integer		The current position in the browser
 * @param  ID_TEXT		The parameter name used to store our position in the results (usually, 'start')
 * @param  integer		The maximum number of rows to show per browser page
 * @param  ID_TEXT		The parameter name used to store the total number of results to show per-page (usually, 'max')
 * @param  integer		The maximum number of rows in the entire dataset
 * @param  boolean		Whether to keep post data when browsing through
 * @param  integer		The maximum number of quick-jump page-links to show
 * @param  ?array			List of per-page selectors to show (NULL: show hard-coded ones)
 * @param  ID_TEXT		Hash component to URL
 * @return tempcode		The results browser
 */
function pagination($title,$start,$start_name,$max,$max_name,$max_rows,$keep_post=false,$max_page_links=5,$_selectors=NULL,$hash='')
{
	inform_non_canonical_parameter($max_name);
	inform_non_canonical_parameter($start_name);

	if (get_page_name()=='members')
	{
		// Don't allow guest bots to probe too deep into the forum index, it gets very slow; the XML Sitemap is for guiding to topics like this
		if (($start>$max*5) && (is_guest()) && (!is_null(get_bot_type())))
			access_denied('NOT_AS_GUEST');
	}

	$post_array=array();
	if ($keep_post)
	{
		foreach ($_POST as $key=>$val)
		{
			if (is_array($val)) continue;
			if (get_magic_quotes_gpc()) $val=stripslashes($val);
			$post_array[$key]=$val;
		}
	}

	$get_url=get_self_url(true);

	// How many to show per page
	if (is_null($_selectors)) $_selectors=array(10,25,50,80);
	if (has_privilege(get_member(),'remove_page_split'))
	{
		if (get_param_integer('keep_avoid_memory_limit',0)==1) $_selectors[]=$max_rows;
	}
	$_selectors[]=$max;
	sort($_selectors);
	$_selectors=array_unique($_selectors);
	$selectors=new ocp_tempcode();
	foreach ($_selectors as $selector_value)
	{
		if ($selector_value>$max_rows) $selector_value=$max_rows;
		$selected=($max==$selector_value);
		$selectors->attach(do_template('PAGINATION_PER_PAGE_OPTION',array('_GUID'=>'1a0583bab42257c60289459ce1ac1e05','SELECTED'=>$selected,'VALUE'=>strval($selector_value),'NAME'=>integer_format($selector_value))));

		if ($selector_value==$max_rows) break;
	}
	$hidden=build_keep_form_fields('_SELF',true,array($max_name,$start_name));
	$per_page=do_template('PAGINATION_PER_PAGE',array('_GUID'=>'1993243727e58347d1544279c5eba496','HASH'=>($hash=='')?NULL:$hash,'HIDDEN'=>$hidden,'URL'=>$get_url,'MAX_NAME'=>$max_name,'SELECTORS'=>$selectors));
	$GLOBALS['INCREMENTAL_ID_GENERATOR']++;

	if ($max<$max_rows) // If they don't all fit on one page
	{
		$parts=new ocp_tempcode();
		$num_pages=($max==0)?1:intval(ceil(floatval($max_rows)/floatval($max)));

		// Link to first
		if ($start>0)
		{
			$url_array=array('page'=>'_SELF',$start_name=>running_script('index')?NULL:0);
			$cat_url=_build_pagination_cat_url($url_array,$post_array,$hash);
			$first=do_template('PAGINATION_CONTINUE_FIRST',array('_GUID'=>'f5e510da318af9b37c3a4b23face5ae3','TITLE'=>$title,'P'=>strval(1),'FIRST_URL'=>$cat_url));
		} else $first=new ocp_tempcode();

		// Link to previous
		if ($start>0)
		{
			$url_array=array('page'=>'_SELF',$start_name=>strval(max($start-$max,0)));
			$cat_url=_build_pagination_cat_url($url_array,$post_array,$hash);
			$previous=do_template('PAGINATION_PREVIOUS_LINK',array('_GUID'=>'ec4d4da9677b5b9c8cea08676337c6eb','TITLE'=>$title,'P'=>integer_format(intval($start/$max)),'URL'=>$cat_url));
		} else $previous=do_template('PAGINATION_PREVIOUS');

		// CALCULATIONS FOR CROPPING OF SEQUENCE
		// $from is the index number (one less than written page number) we start showing page-links from
		// $to is the index number (one less than written page number) we stop showing page-links from
		if ($max!=0)
		{
			$max_dispersal=$max_page_links/2;
			$from=max(0,intval(floatval($start)/floatval($max)-$max_dispersal));
			$to=intval(ceil(min(floatval($max_rows)/floatval($max),floatval($start)/floatval($max)+$max_dispersal)));
			$dif=(floatval($start)/floatval($max)-$max_dispersal);
			if ($dif<0.0) // We have more forward range than max dispersal as we're near the start
			{
				$to=intval(ceil(min(floatval($max_rows)/floatval($max),floatval($start)/floatval($max)+$max_dispersal-$dif)));
			}
		} else
		{
			$from=0;
			$to=0;
		}

		// Indicate that the sequence is incomplete with an ellipsis
		if ($from>0)
		{
			$continues_left=do_template('PAGINATION_CONTINUE');
		} else
		{
			$continues_left=new ocp_tempcode();
		}

		$bot=(is_guest()) && (!is_null(get_bot_type()));

		// Show the page number jump links
		for ($x=$from;$x<$to;$x++)
		{
			$url_array=array('page'=>'_SELF',$start_name=>($x==0)?NULL:strval($x*$max));
			$cat_url=_build_pagination_cat_url($url_array,$post_array,$hash);
			if ($x*$max==$start)
			{
				$parts->attach(do_template('PAGINATION_PAGE_NUMBER',array('_GUID'=>'13cdaf548d5486fb8d8ae0d23b6a08ec','P'=>strval($x+1))));
			} else
			{
				$rel=NULL;
				if ($x==0) $rel='first';
				$parts->attach(do_template('PAGINATION_PAGE_NUMBER_LINK',array('_GUID'=>'a6d1a0ba93e3b7deb6fe6f8f1c117c0f','NOFOLLOW'=>($x*$max>$max*5) && ($bot),'REL'=>$rel,'TITLE'=>$title,'URL'=>$cat_url,'P'=>strval($x+1))));
			}
		}

		// Indicate that the sequence is incomplete with an ellipsis
		if ($to<$num_pages)
		{
			$continues_right=do_template('PAGINATION_CONTINUE');
		} else
		{
			$continues_right=new ocp_tempcode();
		}

		// Link to next
		if (($start+$max)<$max_rows)
		{
			$url_array=array('page'=>'_SELF',$start_name=>strval($start+$max));
			$cat_url=_build_pagination_cat_url($url_array,$post_array,$hash);
			$p=($max==0)?1.0:($start/$max+2);
			$rel=NULL;
			if (($start+$max*2)>$max_rows) $rel='last';
			$next=do_template('PAGINATION_NEXT_LINK',array('_GUID'=>'6da9b396bdd46b7ee18c05b5a7eb4d10','NOFOLLOW'=>($start+$max>$max*5) && ($bot),'REL'=>$rel,'TITLE'=>$title,'NUM_PAGES'=>integer_format($num_pages),'P'=>integer_format(intval($p)),'URL'=>$cat_url));
		} else $next=do_template('PAGINATION_NEXT');

		// Link to last
		if ($start+$max<$max_rows)
		{
			$url_array=array('page'=>'_SELF',($num_pages-1==0)?NULL:$start_name=>strval(($num_pages-1)*$max));
			$cat_url=_build_pagination_cat_url($url_array,$post_array,$hash);
			$last=do_template('PAGINATION_CONTINUE_LAST',array('_GUID'=>'2934936df4ba90989e949a8ebe905522','TITLE'=>$title,'P'=>strval($num_pages),'LAST_URL'=>$cat_url));
		} else $last=new ocp_tempcode();

		// Page jump dropdown, if we had to crop
		if ($num_pages>$max_page_links)
		{
			$list=new ocp_tempcode();
			$pg_start=0;
			$pg_to=$num_pages;
			$pg_at=intval(floatval($start)/floatval($max));
			if ($pg_to>100)
			{
				$pg_start=max($pg_at-50,0);
				$pg_to=$pg_start+100;
			}
			if ($pg_start!=0)
			{
				$list->attach(form_input_list_entry('',false,'...',false,true));
			}
			for ($i=$pg_start;$i<$pg_to;$i++)
			{
				$list->attach(form_input_list_entry(strval($i*$max),($i*$max==$start),strval($i+1)));
			}
			if ($pg_to!=$num_pages)
			{
				$list->attach(form_input_list_entry('',false,'...',false,true));
			}
			$dont_auto_keep=array();
			$hidden=build_keep_form_fields('_SELF',true,$dont_auto_keep);
			$pages_list=do_template('PAGINATION_LIST_PAGES',array('_GUID'=>'9e1b394763619433f23b8ed95f5ac134','URL'=>$get_url,'HIDDEN'=>$hidden,'START_NAME'=>$start_name,'LIST'=>$list));
		} else
		{
			$pages_list=new ocp_tempcode();
		}

		// Put it all together
		return do_template('PAGINATION_WRAP',array(
			'_GUID'=>'2c3fc957d4d8ab9103ef26458e18aed1',
			'TEXT_ID'=>$title,
			'PER_PAGE'=>$per_page,
			'FIRST'=>$first,
			'PREVIOUS'=>$previous,
			'CONTINUES_LEFT'=>$continues_left,
			'PARTS'=>$parts,
			'CONTINUES_RIGHT'=>$continues_right,
			'NEXT'=>$next,
			'LAST'=>$last,
			'PAGES_LIST'=>$pages_list,

			'START'=>strval($start),
			'MAX'=>strval($max),
			'MAX_ROWS'=>strval($max_rows),
			'NUM_PAGES'=>strval($num_pages),
		));
	}

	if (get_value('pagination_when_not_needed')==='1')
	{
		return do_template('PAGINATION_WRAP',array('_GUID'=>'451167645e67c7beabcafe11c78680db','TEXT_ID'=>$title,
			'PER_PAGE'=>$per_page,
			'FIRST'=>'',
			'PREVIOUS'=>'',
			'CONTINUES_LEFT'=>'',
			'PARTS'=>'',
			'CONTINUES_RIGHT'=>'',
			'NEXT'=>'',
			'LAST'=>'',
			'PAGES_LIST'=>'',

			'START'=>strval($start),
			'MAX'=>strval($max),
			'MAX_ROWS'=>strval($max_rows),
			'NUM_PAGES'=>strval(1),
		));
	}

	return new ocp_tempcode();
}

/**
 * Helper function to work out a results browser URL.
 *
 * @param  array			Map of GET array segments to use (others will be added by this function)
 * @param  array			Map of POST array segments (relayed as GET) to use
 * @param  ID_TEXT		Hash component to URL
 * @return mixed			The URL
 */
function _build_pagination_cat_url($url_array,$post_array,$hash)
{
	$url_array=array_merge($url_array,$post_array);
	$cat_url=build_url($url_array,'_SELF',NULL,true,false,false,$hash);

	return $cat_url;
}

