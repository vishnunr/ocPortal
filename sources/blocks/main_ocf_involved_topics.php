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
 * @package		ocf_forum
 */

class Block_main_ocf_involved_topics
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (get_forum_type()!='ocf') return NULL;

		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('member_id','max','start');
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		$block_id=get_block_id($map);

		$member_id_of=array_key_exists('member_id',$map)?intval($map['member_id']):get_member();
		$max=get_param_integer($block_id.'_max',array_key_exists('max',$map)?intval($map['max']):10);
		$start=get_param_integer($block_id.'_start',array_key_exists('start',$map)?intval($map['start']):0);

		require_code('ocf_topics');
		require_code('ocf_general');
		require_lang('ocf');
		require_code('ocf_forumview');

		$topics=new ocp_tempcode();

		$forum1=NULL;//$GLOBALS['FORUM_DRIVER']->forum_id_from_name(get_option('comments_forum_name'));
		$tf=get_option('ticket_forum_name',true);
		if (!is_null($tf)) $forum2=$GLOBALS['FORUM_DRIVER']->forum_id_from_name($tf); else $forum2=NULL;
		$where_more='';
		if (!is_null($forum1)) $where_more.=' AND p_cache_forum_id<>'.strval($forum1);
		if (!is_null($forum2)) $where_more.=' AND p_cache_forum_id<>'.strval($forum2);
		$rows=$GLOBALS['FORUM_DB']->query('SELECT DISTINCT p_topic_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_poster='.strval($member_id_of).$where_more.' ORDER BY p_time DESC',$max,$start,false,true);
		if (count($rows)!=0)
		{
			$max_rows=$GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(DISTINCT p_topic_id) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_poster='.strval($member_id_of).$where_more,false,true);

			$moderator_actions='';
			$has_topic_marking=has_delete_permission('mid',get_member(),$member_id_of,'topics');
			if ($has_topic_marking)
			{
				$moderator_actions.='<option value="delete_topics_and_posts">'.do_lang('DELETE_TOPICS_AND_POSTS').'</option>';
			}

			$where='';
			foreach ($rows as $row)
			{
				if ($where!='') $where.=' OR ';
				$where.='t.id='.strval($row['p_topic_id']);
			}
			$topic_rows=$GLOBALS['FORUM_DB']->query('SELECT t.*,lan.text_parsed AS _trans_post,l_time FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics t LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON (t.id=l.l_topic_id AND l.l_member_id='.strval(get_member()).') LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate lan ON t.t_cache_first_post=lan.id WHERE '.$where,NULL,NULL,false,true);
			$topic_rows_map=array();
			foreach ($topic_rows as $topic_row)
			{
				if (has_category_access(get_member(),'forums',strval($topic_row['t_forum_id'])))
					$topic_rows_map[$topic_row['id']]=$topic_row;
			}
			$hot_topic_definition=intval(get_option('hot_topic_definition'));
			foreach ($rows as $row)
			{
				if (array_key_exists($row['p_topic_id'],$topic_rows_map))
					$topics->attach(ocf_render_topic(ocf_get_topic_array($topic_rows_map[$row['p_topic_id']],get_member(),$hot_topic_definition,true),$has_topic_marking));
			}
			if (!$topics->is_empty())
			{
				$action_url=build_url(array('page'=>'topics'),get_module_zone('topics'),NULL,false,true);

				$forum_name=do_lang_tempcode('TOPICS_PARTICIPATED_IN',integer_format($start+1).'-'.integer_format($start+$max));
				$marker='';
				$breadcrumbs=new ocp_tempcode();
				require_code('templates_pagination');
				$pagination=pagination(do_lang_tempcode('FORUM_TOPICS'),$start,$block_id.'_start',$max,$block_id.'_max',$max_rows,false,5,NULL);
				$topics=do_template('OCF_FORUM_TOPIC_WRAPPER',array(
					'_GUID'=>'8723270b128b4eea47ab3c756b342e14',
					'ORDER'=>'',
					'MAX'=>'15',
					'MAY_CHANGE_MAX'=>false,
					'BREADCRUMBS'=>$breadcrumbs,
					'ACTION_URL'=>$action_url,
					'BUTTONS'=>'',
					'STARTER_TITLE'=>'',
					'MARKER'=>$marker,
					'FORUM_NAME'=>$forum_name,
					'TOPICS'=>$topics,
					'PAGINATION'=>$pagination,
					'MODERATOR_ACTIONS'=>$moderator_actions,
				));
			}
		}

		return do_template('BLOCK_MAIN_OCF_INVOLVED_TOPICS',array('_GUID'=>'3f1025f5d3391d43afbdfa292721aa09','BLOCK_PARAMS'=>block_params_arr_to_str($map),
			'TOPICS'=>$topics,

			'START'=>strval($start),
			'MAX'=>strval($max),
			'START_PARAM'=>$block_id.'_start',
			'MAX_PARAM'=>$block_id.'_max',
		));
	}

}


