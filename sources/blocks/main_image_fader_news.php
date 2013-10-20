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
 * @package		news
 */

class Block_main_image_fader_news
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
		$info['parameters']=array('title','max','time','param','zone','blogs','as_guest');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='((addon_installed(\'content_privacy\')) && (!(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false)))?NULL:array(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,array_key_exists(\'blogs\',$map)?$map[\'blogs\']:\'-1\',array_key_exists(\'max\',$map)?intval($map[\'max\']):5,array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'time\',$map)?intval($map[\'time\']):8000,array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'news\'),array_key_exists(\'param\',$map)?$map[\'param\']:\'\')';
		$info['ttl']=60;
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
		require_lang('news');
		require_code('news');
		require_css('news');

		$cat=array_key_exists('param',$map)?$map['param']:'*';
		if ($cat=='') $cat='root';
		$mill=array_key_exists('time',$map)?intval($map['time']):8000; // milliseconds between animations
		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('news');
		$max=array_key_exists('max',$map)?intval($map['max']):5;
		$blogs=array_key_exists('blogs',$map)?intval($map['blogs']):-1;

		$main_title=do_lang_tempcode('NEWS');
		$_title=array_key_exists('title',$map)?$map['title']:'';
		if ($_title!='') $main_title=protect_from_escaping(escape_html($_title));

		require_code('ocfiltering');
		$ocfilter=ocfilter_to_sqlfragment($cat,'id','news','NULL','news_category','id',true,true);

		$q_filter='';
		if ($blogs===0)
		{
			$q_filter.=' AND nc_owner IS NULL';
		}
		elseif ($blogs===1)
		{
			$q_filter.=' AND (nc_owner IS NOT NULL)';
		}
		if ($blogs!=-1)
		{
			$join=' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_categories c ON c.id=r.news_category';
		} else $join='';

		if (addon_installed('content_privacy'))
		{
			require_code('content_privacy');
			$as_guest=array_key_exists('as_guest',$map)?($map['as_guest']=='1'):false;
			$viewing_member_id=$as_guest?$GLOBALS['FORUM_DRIVER']->get_guest_id():mixed();
			list($privacy_join,$privacy_where)=get_privacy_where_clause('news','r',$viewing_member_id);
			$join.=$privacy_join;
			$q_filter.=$privacy_where;
		}

		$query='SELECT r.id,news_image,title,news,news_article,date_and_time,submitter,author FROM '.get_table_prefix().'news r'.$join.' WHERE '.$ocfilter.$q_filter.' AND validated=1 ORDER BY date_and_time DESC';
		$all_rows=$GLOBALS['SITE_DB']->query($query,100/*reasonable amount*/);
		$news=array();
		require_code('images');
		foreach ($all_rows as $row)
		{
			$title=get_translated_tempcode($row['title']);

			$image_url=$row['news_image'];
			if ($image_url=='')
			{
				$article=get_translated_text($row['news_article']);
				$matches=array();
				if (preg_match('#["\'\]](http:[^\'"\[\]]+\.(jpeg|jpg|gif|png))["\'\[]#i',$article,$matches)!=0)
				{
					$image_url=$matches[1];
				} else
				{
					continue; // Invalid: no image
				}
			}
			if (url_is_local($image_url)) $image_url=get_custom_base_url().'/'.$image_url;

			$url_map=array('page'=>'news','type'=>'view','id'=>$row['id'],'filter'=>($cat=='')?NULL:$cat);
			if ($blogs===1) $url_map['blog']=1;
			$url=build_url($url_map,$zone);

			$body=get_translated_tempcode($row['news']);
			if ($body->is_empty()) $body=get_translated_tempcode($row['news_article']);
			if ($body->is_empty()) continue; // Invalid: empty text

			$date=get_timezoned_date($row['date_and_time']);
			$date_raw=strval($row['date_and_time']);

			$author_url=(addon_installed('authors'))?build_url(array('page'=>'authors','type'=>'misc','id'=>$row['author']),get_module_zone('authors')):new ocp_tempcode();

			$news[]=array(
				'TITLE'=>$title,
				'IMAGE_URL'=>$image_url,
				'URL'=>$url,
				'BODY'=>$body,
				'DATE'=>$date,
				'DATE_RAW'=>$date_raw,
				'SUBMITTER'=>strval($row['submitter']),
				'AUTHOR'=>$row['author'],
				'AUTHOR_URL'=>$author_url,
			);

			if (count($news)==$max) break;
		}

		if (count($news)==0)
		{
			$submit_url=mixed();
			if ((has_actual_page_access(NULL,($blogs===1)?'cms_blogs':'cms_news',NULL,NULL)) && (has_submit_permission('mid',get_member(),get_ip_address(),($blogs===1)?'cms_blogs':'cms_news',array('news',$cat))))
			{
				$submit_url=build_url(array('page'=>($blogs===1)?'cms_blogs':'cms_news','type'=>'ad','cat'=>$cat,'redirect'=>SELF_REDIRECT),get_module_zone(($blogs===1)?'cms_blogs':'cms_news'));
			}
			return do_template('BLOCK_NO_ENTRIES',array('_GUID'=>'ba84d65b8dd134ba6cd7b1b7bde99de2','HIGH'=>false,'TITLE'=>$main_title,'MESSAGE'=>do_lang_tempcode('NO_ENTRIES'),'ADD_NAME'=>do_lang_tempcode('ADD_NEWS'),'SUBMIT_URL'=>$submit_url));
		}

		$tmp=array('page'=>'news','type'=>'misc','filter'=>($cat=='')?NULL:$cat);
		if ($blogs!=-1) $tmp['blog']=$blogs;
		$archive_url=build_url($tmp,$zone);

		return do_template('BLOCK_MAIN_IMAGE_FADER_NEWS',array('_GUID'=>'dbe34e6f670edfd74b15d3c4afbe615e','TITLE'=>$main_title,
			'ARCHIVE_URL'=>$archive_url,
			'NEWS'=>$news,
			'MILL'=>strval($mill),
		));
	}

}
