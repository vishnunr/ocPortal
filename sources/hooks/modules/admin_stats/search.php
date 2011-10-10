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
 * @package		search
 */

class Hook_admin_stats_search
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		require_lang('search');
		
		return array(
			array('search'=>'SEARCH_STATISTICS',),
			array('searchstats',array('_SELF',array('type'=>'search'),'_SELF'),do_lang('SEARCH_STATISTICS'),('DESCRIPTION_SEARCH_STATISTICS')),
		);
	}

	/**
	 * The UI to show top search keywords.
	 *
	 * @param  object			The stats module object
	 * @param  string			The screen type
	 * @return tempcode		The UI
	 */
	function search($ob,$type)
	{
		// Handle time range
		if (get_param_integer('dated',0)==0)
		{
			$title=get_page_title('SEARCH_STATISTICS');

			return $ob->get_between($title);
		}
		$time_start=get_input_date('time_start',true);
		$time_end=get_input_date('time_end',true);
		if (is_null($time_start)) $time_start=0;
		if (is_null($time_end)) $time_end=time();

		$title=get_page_title('SEARCH_STATISTICS_RANGE',true,array(escape_html(get_timezoned_date($time_start,false)),escape_html(get_timezoned_date($time_end,false))));

		$start=get_param_integer('start',0);
		$max=get_param_integer('max',20);
		$sortables=array('s_primary'=>do_lang_tempcode('SEARCH_STATISTICS'));
		$test=explode(' ',get_param('sort','s_primary DESC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$rows=$GLOBALS['SITE_DB']->query('SELECT s_primary,COUNT(*) AS cnt FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'searches_logged WHERE s_time>'.strval((integer)$time_start).' AND s_time<'.strval((integer)$time_end).' GROUP BY s_primary ORDER BY '.$sortable.' '.$sort_order);
		if (count($rows)<1) return warn_screen($title,do_lang_tempcode('NO_DATA'));

		$keywords=array();
		$total=0;
		foreach ($rows as $value)
		{
			$keywords[$value['s_primary']]=$value['cnt'];
			$total+=$value['cnt'];
		}

		if ($sort_order=='ASC') asort($keywords); else arsort($keywords);

		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('KEYWORD'),do_lang_tempcode('COUNT_VIEWS')),$sortables,'sort',$sortable.' '.$sort_order);
		$fields=new ocp_tempcode();
		$degrees=360/$total;
		$done_total=0;
		//$done=0;
		$data=array();
		$i=0;

		foreach ($keywords as $keyword=>$views)
		{
			if ($i<$start)
			{
				$i++; continue;
			} elseif ($i>=$start+$max) break;
			if ($keyword=='') $link=do_lang_tempcode('SEARCH_STATS_ADVANCED'); else $link=protect_from_escaping(escape_html($keyword));
			$fields->attach(results_entry(array($link,integer_format($views))));

			//if ($done<20)
			//{
				$data[$keyword]=$keywords[$keyword]*$degrees;
				//$done++;
				$done_total+=$data[$keyword];
			//}
			$i++;
		}
		if ((360-$done_total)>0)
		{
			$data[do_lang('OTHER')]=360-$done_total;
			$fields->attach(results_entry(array(do_lang('OTHER'),float_format((360-$done_total)/$degrees))));
		}
		$list=results_table(do_lang_tempcode('SEARCH_STATISTICS'),$start,'start',$max,'max',count($keywords),$fields_title,$fields,$sortables,$sortable,$sort_order,'sort',new ocp_tempcode());

		$output=create_pie_chart($data);
		$ob->save_graph('Global-Search',$output);

		$graph=do_template('STATS_GRAPH',array('GRAPH'=>get_custom_base_url().'/data_custom/modules/admin_stats/Global-Search.xml','TITLE'=>do_lang_tempcode('SEARCH_STATISTICS'),'TEXT'=>do_lang_tempcode('DESCRIPTION_SEARCH_STATISTICS')));

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('SITE_STATISTICS'))));

		return do_template('STATS_SCREEN',array('_GUID'=>'727a59e061727c4a1e24345cecb769aa','TITLE'=>$title,'GRAPH'=>$graph,'STATS'=>$list));
	}

}


