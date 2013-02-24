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
 * Get template fields to insert into a form page, for manipulation of meta data.
 *
 * @param  ID_TEXT		The type of resource (e.g. download)
 * @param  ?ID_TEXT		The ID of the resource (NULL: adding)
 * @param  boolean		Whether to allow owner to be left blank (meaning no owner)
 * @param  ?array			List of fields to NOT take in (NULL: empty list)
 * @return tempcode		Form page tempcode fragment
 */
function meta_data_get_fields($content_type,$content_id,$allow_no_owner=false,$fields_to_skip=NULL)
{
	if (!has_privilege(get_member(),'edit_meta_fields')) return new ocp_tempcode();

	require_lang('meta_data');

	if (is_null($fields_to_skip)) $fields_to_skip=array();

	require_code('hooks/systems/content_meta_aware/'.filter_naughty($content_type));
	$ob=object_factory('Hook_content_meta_aware_'.$content_type);
	$info=$ob->info();

	$fields=new ocp_tempcode();

	require_code('content');
	$content_row=mixed();
	if (!is_null($content_id))
	{
		list(,,,$content_row)=content_get_details($content_type,$content_id);
	}

	$views_field=in_array('views',$fields_to_skip)?NULL:$info['views_field'];
	if (!is_null($views_field))
	{
		$views=is_null($content_row)?0:$content_row[$views_field];
		$fields->attach(form_input_integer(do_lang_tempcode('_VIEWS'),do_lang_tempcode('DESCRIPTION_META_VIEWS'),'meta_views',NULL,false));
	}

	$submitter_field=in_array('submitter',$fields_to_skip)?NULL:$info['submitter_field'];
	if (!is_null($submitter_field))
	{
		$submitter=is_null($content_row)?get_member():$content_row[$submitter_field];
		$username=$GLOBALS['FORUM_DRIVER']->get_username($submitter);
		if (is_null($username)) $username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
		$fields->attach(form_input_username(do_lang_tempcode('OWNER'),do_lang_tempcode('DESCRIPTION_OWNER'),'meta_submitter',$username,!$allow_no_owner));
	}

	$add_time_field=in_array('add_time',$fields_to_skip)?NULL:$info['add_time_field'];
	if (!is_null($add_time_field))
	{
		$add_time=is_null($content_row)?time():$content_row[$add_time_field];
		$fields->attach(form_input_date(do_lang_tempcode('ADD_TIME'),do_lang_tempcode('DESCRIPTION_META_ADD_TIME'),'meta_add_time',false,false,true,$add_time,10,NULL,NULL,true));
	}

	if (!is_null($content_id))
	{
		$edit_time_field=in_array('edit_time',$fields_to_skip)?NULL:$info['edit_time_field'];
		if (!is_null($edit_time_field))
		{
			$edit_time=is_null($content_row)?NULL:(is_null($content_row[$edit_time_field])?time():max(time(),$content_row[$edit_time_field]));
			$fields->attach(form_input_date(do_lang_tempcode('EDIT_TIME'),do_lang_tempcode('DESCRIPTION_META_EDIT_TIME'),'meta_edit_time',true,is_null($edit_time),true,$edit_time,10,NULL,NULL,false));
		}
	}

	if (($info['support_url_monikers']) && (!in_array('url_moniker',$fields_to_skip)))
	{
		$url_moniker=mixed();
		if (!is_null($content_id))
		{
			list($zone,$attributes,)=page_link_decode($info['view_pagelink_pattern']);
			$url_moniker=find_id_moniker($attributes+array('id'=>$content_id));
			if (is_null($url_moniker)) $url_moniker='';
			$manually_chosen=!is_null($GLOBALS['SITE_DB']->query_select_value_if_there('url_id_monikers','m_moniker',array('m_manually_chosen'=>1,'m_resource_page'=>$attributes['page'],'m_resource_type'=>$attributes['type'],'m_resource_id'=>$content_id)));
		} else
		{
			$url_moniker='';
			$manually_chosen=false;
		}
		$fields->attach(form_input_codename(do_lang_tempcode('URL_MONIKER'),do_lang_tempcode('DESCRIPTION_META_URL_MONIKER',escape_html($url_moniker)),'url_moniker',$manually_chosen?$url_moniker:'',false,NULL,NULL,array('/')));
	}

	if (!$fields->is_empty())
	{
		$_fields=new ocp_tempcode();
		$_fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array(
			'SECTION_HIDDEN'=>true,
			'TITLE'=>do_lang_tempcode('META_DATA'),
			'HELP'=>do_lang_tempcode('DESCRIPTION_META_DATA',is_null($content_id)?do_lang_tempcode('RESOURCE_NEW'):$content_id),
		)));
		$_fields->attach($fields);
		return $_fields;
	}

	return $fields;
}

/**
 * Get field values for meta data.
 *
 * @param  ID_TEXT		The type of resource (e.g. download)
 * @param  ?ID_TEXT		The ID of the resource (NULL: adding)
 * @param  ?array			List of fields to NOT take in (NULL: empty list)
 * @return array			A map of standard meta data fields (name to value). If adding, this map is accurate for adding. If editing, NULLs mean do-not-edit or non-editable.
 */
function actual_meta_data_get_fields($content_type,$content_id,$fields_to_skip=NULL)
{
	require_lang('meta_data');

	if (is_null($fields_to_skip)) $fields_to_skip=array();

	if (fractional_edit())
	{
		return array(
			'views'=>INTEGER_MAGIC_NULL,
			'submitter'=>INTEGER_MAGIC_NULL,
			'add_time'=>INTEGER_MAGIC_NULL,
			'edit_time'=>INTEGER_MAGIC_NULL,
			/*'url_moniker'=>NULL,, was handled internally*/
		);
	}

	if (!has_privilege(get_member(),'edit_meta_fields')) // Pass through as how an edit would normally function (things left alone except edit time)
	{
		return array(
			'views'=>is_null($content_id)?0:INTEGER_MAGIC_NULL,
			'submitter'=>is_null($content_id)?get_member():INTEGER_MAGIC_NULL,
			'add_time'=>is_null($content_id)?time():INTEGER_MAGIC_NULL,
			'edit_time'=>time(),
			/*'url_moniker'=>NULL,, was handled internally*/
		);
	}

	require_code('hooks/systems/content_meta_aware/'.filter_naughty($content_type));
	$ob=object_factory('Hook_content_meta_aware_'.$content_type);
	$info=$ob->info();

	$views=mixed();
	$views_field=in_array('views',$fields_to_skip)?NULL:$info['views_field'];
	if (!is_null($views_field))
	{
		$views=post_param_integer('meta_views',NULL);
		if (is_null($views))
		{
			if (is_null($content_id))
			{
				$views=0;
			} else
			{
				$views=INTEGER_MAGIC_NULL;
			}
		}
	}

	$submitter=mixed();
	$submitter_field=in_array('submitter',$fields_to_skip)?NULL:$info['submitter_field'];
	if (!is_null($submitter_field))
	{
		$_submitter=post_param('meta_submitter',$GLOBALS['FORUM_DRIVER']->get_username(get_member()));
		if ($_submitter!='')
		{
			$submitter=$GLOBALS['FORUM_DRIVER']->get_member_from_username($_submitter);
			if (is_null($submitter))
			{
				$submitter=NULL; // Leave alone, we did not recognise the user
				attach_message(do_lang_tempcode('_MEMBER_NO_EXIST',escape_html($_submitter)),'warn'); // ...but attach an error at least
			}
			if (is_null($submitter))
			{
				if (is_null($content_id)) $submitter=get_member();
			}
		} else
		{
			$submitter=NULL;
		}
	}

	$add_time=mixed();
	$add_time_field=in_array('add_time',$fields_to_skip)?NULL:$info['add_time_field'];
	if (!is_null($add_time_field))
	{
		$add_time=get_input_date('meta_add_time');
		if (is_null($add_time))
		{
			if (is_null($content_id))
			{
				$add_time=time();
			} else
			{
				$add_time=INTEGER_MAGIC_NULL; // This code branch should actually be impossible to reach
			}
		}
	}

	$edit_time=mixed();
	$edit_time_field=in_array('edit_time',$fields_to_skip)?NULL:$info['edit_time_field'];
	if (!is_null($edit_time_field))
	{
		$edit_time=get_input_date('meta_edit_time');
		if (is_null($edit_time))
		{
			if (is_null($content_id))
			{
				$edit_time=NULL; // No edit time
			} else
			{
				$edit_time=NULL; // Edit time explicitly wiped out
			}
		}
	}

	$url_moniker=mixed();
	if (($info['support_url_monikers']) && (!in_array('url_moniker',$fields_to_skip)))
	{
		$url_moniker=post_param('meta_url_moniker','');
		if ($url_moniker=='') $url_moniker=NULL;

		require_code('type_validation');
		if (!is_alphanumeric(str_replace('/','',$url_moniker)))
		{
			attach_message(do_lang_tempcode('BAD_CODENAME'),'warn');
			$url_moniker=NULL;
		}

		if (!is_null($url_moniker))
		{
			list($zone,$attributes,)=page_link_decode($info['view_pagelink_pattern']);
			$page=$attributes['page'];
			$type=$attributes['type'];

			$test=$GLOBALS['SITE_DB']->query_select_value_if_there('url_id_monikers','m_resource_id',array(
				'm_resource_page'=>$page,
				'm_resource_type'=>$type,
				'm_moniker'=>$url_moniker,
				'm_deprecated'=>0
			));

			if (($test===NULL) || ($test===$content_id))
			{
				// Insert
				$GLOBALS['SITE_DB']->query_delete('url_id_monikers',array(	// It's possible we're re-activating/replacing a deprecated one
					'm_resource_page'=>$page,
					'm_resource_type'=>$type,
					'm_resource_id'=>$content_id,
					'm_moniker'=>$url_moniker,
				),'',1);
				$GLOBALS['SITE_DB']->query_insert('url_id_monikers',array(
					'm_resource_page'=>$page,
					'm_resource_type'=>$type,
					'm_resource_id'=>$content_id,
					'm_moniker'=>$url_moniker,
					'm_deprecated'=>0,
					'm_manually_chosen'=>1,
				));
			} else
			{
				attach_message(do_lang_tempcode('URL_MONIKER_TAKEN',escape_html($page.':'.$type.':'.$test),escape_html($url_moniker)),'warn');
			}
		}
	}

	return array(
		'views'=>$views,
		'submitter'=>$submitter,
		'add_time'=>$add_time,
		'edit_time'=>$edit_time,
		/*'url_moniker'=>$url_moniker, was handled internally*/
	);
}

/**
 * Read in an additional meta data field, specific to a resource type.
 *
 * @param  array			Meta data already collected
 * @param  ID_TEXT		The parameter name
 * @param  mixed			The default if it was not set
 */
function actual_meta_data_get_fields__special($meta_data,$key,$default)
{
	$meta_data[$key]=$default;
	if (has_privilege(get_member(),'edit_meta_fields'))
	{
		if (is_integer($default))
		{
			switch ($default)
			{
				case 0:
				case INTEGER_MAGIC_NULL:
					$meta_data[$key]=post_param_integer('meta_'.$key,$default);
					break;
			}
		} else
		{
			switch ($default)
			{
				case '':
				case STRING_MAGIC_NULL:
					$meta_data[$key]=post_param('meta_'.$key,$default);
					if ($meta_data[$key]=='') $meta_data[$key]=$default;
					break;
	
				case NULL:
					$meta_data[$key]=post_param_integer('meta_'.$key,NULL);
					break;
			}
		}
	}
}
