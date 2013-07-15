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
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__caches3()
{
	global $ERASED_TEMPLATES_ONCE;
	$ERASED_TEMPLATES_ONCE=false;
}

/**
 * Rebuild the specified caches.
 *
 * @param  ?array			The caches to rebuild (NULL: all)
 * @return tempcode		Any messages returned
 */
function ocportal_cleanup($caches=NULL)
{
	require_lang('cleanup');

	$max_time=intval(round(floatval(ini_get('max_execution_time'))/1.5));
	if ($max_time<60*4)
	{
		if (function_exists('set_time_limit')) @set_time_limit(0);
	}
	$messages=new ocp_tempcode();
	$hooks=find_all_hooks('systems','cleanup');
	if ((array_key_exists('ocf',$hooks)) && (array_key_exists('ocf_topics',$hooks)))
	{
		// A little re-ordering
		$temp=$hooks['ocf'];
		unset($hooks['ocf']);
		$hooks['ocf']=$temp;
	}

	if (!is_null($caches))
	{
		foreach ($caches as $cache)
		{
			if (array_key_exists($cache,$hooks))
			{
				require_code('hooks/systems/cleanup/'.filter_naughty_harsh($cache));
				$object=object_factory('Hook_'.filter_naughty_harsh($cache),true);
				if (is_null($object)) continue;
				$messages->attach($object->run());
			} else
			{
				$messages->attach(paragraph(do_lang_tempcode('_MISSING_RESOURCE',escape_html($cache))));
			}
		}
	}
	else
	{
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/cleanup/'.filter_naughty_harsh($hook));
			$object=object_factory('Hook_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			$info=$object->info();
			if ($info['type']=='cache')
				$messages->attach($object->run());
		}
	}

	log_it('CLEANUP_TOOLS');
	return $messages;
}

/**
 * Erase the block cache.
 */
function erase_block_cache()
{
	$GLOBALS['SITE_DB']->query_delete('cache_on',NULL,'',NULL,NULL,true);
	$GLOBALS['SITE_DB']->query_delete('cache');
	erase_persistent_cache();
}

/**
 * Erase the Comcode cache. Warning: This can take a long time on large sites, so is best to avoid.
 */
function erase_comcode_cache()
{
	static $done_once=false; // Useful to stop it running multiple times in admin_cleanup module, as this code takes time
	if ($done_once) return;
	if ((substr(get_db_type(),0,5)=='mysql') && (!is_null($GLOBALS['SITE_DB']->query_select_value_if_there('db_meta_indices','i_fields',array('i_table'=>'translate','i_name'=>'decache')))))
	{
		$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'translate FORCE INDEX (decache) SET text_parsed=\'\' WHERE '.db_string_not_equal_to('text_parsed','')/*this WHERE is so indexing helps*/);
	} else
	{
		$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'translate SET text_parsed=\'\' WHERE '.db_string_not_equal_to('text_parsed','')/*this WHERE is so indexing helps*/);
	}
	$done_once=true;
}

/**
 * Erase the thumbnail cache.
 */
function erase_thumb_cache()
{
	$thumb_fields=$GLOBALS['SITE_DB']->query('SELECT m_name,m_table FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'db_meta WHERE m_name LIKE \''.db_encode_like('%thumb_url').'\'');
	foreach ($thumb_fields as $field)
	{
		if ($field['m_table']=='videos') continue;

		$GLOBALS['SITE_DB']->query_update($field['m_table'],array($field['m_name']=>''));
	}
	$full=get_custom_file_base().'/uploads/auto_thumbs';
	$dh=@opendir($full);
	if ($dh!==false)
	{
		while (($file=readdir($dh))!==false)
		{
			@unlink($full.'/'.$file);
		}
		closedir($dh);
	}
}

/**
 * Erase the language cache.
 */
function erase_cached_language()
{
	$langs=find_all_langs(true);
	foreach (array_keys($langs) as $lang)
	{
		$path=get_custom_file_base().'/caches/lang/'.$lang;
		$_dir=@opendir($path);
		if ($_dir===false)
		{
			if (!file_exists(dirname($path)))
				@mkdir(dirname($path),0777) OR intelligent_write_error($path);
			@mkdir($path,0777) OR intelligent_write_error($path);
			fix_permissions($path,0777);
		} else
		{
			while (false!==($file=readdir($_dir)))
			{
				if (substr($file,-4)=='.lcd')
				{
					if (running_script('index'))
					{
						$key='page__'.get_zone_name().'__'.get_page_name();
					} else
					{
						$key='script__'.md5(serialize(ocp_srv('PHP_SELF')).serialize($_GET));
					}
					if ($key.'.lcd'==$file) continue; // Will be open/locked

					$i=0;
					while ((@unlink($path.'/'.$file)===false) && ($i<5))
					{
						if (!file_exists($path.'/'.$file)) break; // Race condition, gone already
						sleep(1); // May be race condition, lock
						$i++;
					}
					if ($i>=5)
					{
						if ((file_exists($path.'/'.$file)) && (substr($file,0,5)!='page_') && (substr($file,0,7)!='script_'))
						{
							@unlink($path.'/'.$file) OR intelligent_write_error($path.'/'.$file);
						}
					}
				}
			}
			closedir($_dir);
		}
	}

	init__lang();
	require_all_lang();
}

/**
 * Erase all template caches (caches in all themes).
 *
 * @param  boolean	Whether to preserve CSS and JS files that might be linked to between requests
 */
function erase_cached_templates($preserve_some=false)
{
	global $ERASED_TEMPLATES_ONCE;
	$ERASED_TEMPLATES_ONCE=true;

	require_code('themes2');
	$themes=find_all_themes();
	$langs=find_all_langs(true);
	foreach (array_keys($themes) as $theme)
	{
		foreach (array_keys($langs) as $lang)
		{
			$path=get_custom_file_base().'/themes/'.$theme.'/templates_cached/'.$lang.'/';
			$_dir=@opendir($path);
			if ($_dir===false)
			{
				@mkdir($path,0777);// OR warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY_REPAIR',escape_html($path)));
				fix_permissions($path,0777);
			} else
			{
				while (false!==($file=readdir($_dir)))
				{
					if ((substr($file,-4)=='.tcd') || (substr($file,-4)=='.tcp') || ((!$preserve_some) && ((substr($file,-3)=='.js') || (substr($file,-4)=='.css'))))
					{
						$i=0;
						while ((@unlink($path.$file)===false) && ($i<5))
						{
							if (!file_exists($path.$file)) break; // Race condition, gone already
							sleep(1); // May be race condition, lock
							$i++;
						}
						if ($i>=5)
							if (file_exists($path.$file)) @unlink($path.$file) OR intelligent_write_error($path.$file);
					}
				}
				closedir($_dir);
			}
		}
	}
	foreach (array_keys($langs) as $lang)
	{
		$path=get_custom_file_base().'/site/pages/html_custom/'.$lang.'/';
		$_dir=@opendir($path);
		if ($_dir!==false)
		{
			while (false!==($file=readdir($_dir)))
			{
				if (substr($file,-14)=='_tree_made.htm')
				{
					@unlink($path.$file);
				}
			}
			closedir($_dir);
		}
	}

	$zones=find_all_zones();
	foreach ($zones as $zone)
	{
		delete_value('merged__'.$zone.'.css');
		delete_value('merged__'.$zone.'.js');
		delete_value('merged__'.$zone.'__admin.css');
		delete_value('merged__'.$zone.'__admin.js');
	}

	// Often the back button will be used to return to a form, so we need to ensure we have not broken the Javascript
	if (function_exists('get_member'))
	{
		javascript_enforce('javascript_validation');
		javascript_enforce('javascript_editing');
	}
}

/**
 * Erase the Comcode page cache
 */
function erase_comcode_page_cache()
{
	$GLOBALS['NO_QUERY_LIMIT']=true;

	do
	{
		$rows=$GLOBALS['SITE_DB']->query_select('cached_comcode_pages',array('string_index'),NULL,'',50,NULL,true,array());
		if (is_null($rows)) $rows=array();
		foreach ($rows as $row)
		{
			delete_lang($row['string_index']);
			$GLOBALS['SITE_DB']->query_delete('cached_comcode_pages',array('string_index'=>$row['string_index']));
		}
	}
	while (count($rows)!=0);
	erase_persistent_cache();

	$GLOBALS['NO_QUERY_LIMIT']=false;
}

