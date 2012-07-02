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
 * @package		custom_comcode
 */

require_code('aed_module');

/**
 * Module page class.
 */
class Module_admin_custom_comcode extends standard_aed_module
{
	var $table_prefix='tag_';
	var $array_key='tag_tag';
	var $lang_type='CUSTOM_COMCODE_TAG';
	var $select_name='TITLE';
	var $non_integer_id=true;
	var $menu_label='CUSTOM_COMCODE';
	var $javascript="var tag=document.getElementById('tag'); var old=tag.value; tag.onblur=function() { var e=document.getElementById('example'); e.value=e.value.replace('['+old+' ','['+tag.value+' ').replace('['+old+']','['+tag.value+']').replace('[/'+old+']','[/'+tag.value+']'); old=tag.value; };";
	var $orderer='tag_title';
	var $title_is_multi_lang=true;

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'CUSTOM_COMCODE'),parent::get_entry_points());
	}

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
		$info['locked']=true;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('custom_comcode');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['SITE_DB']->create_table('custom_comcode',array(
			'tag_tag'=>'*ID_TEXT',
			'tag_title'=>'SHORT_TRANS',
			'tag_description'=>'SHORT_TRANS',
			'tag_replace'=>'LONG_TEXT',
			'tag_example'=>'LONG_TEXT',
			'tag_parameters'=>'SHORT_TEXT',
			'tag_enabled'=>'BINARY',
			'tag_dangerous_tag'=>'BINARY',
			'tag_block_tag'=>'BINARY',
			'tag_textual_tag'=>'BINARY'
		));

		require_lang('custom_comcode');

		$ve_hooks=find_all_hooks('systems','video_embed');
		foreach (array_keys($ve_hooks) as $ve_hook)
		{
			require_code('hooks/systems/video_embed/'.$ve_hook);
			$ve_ob=object_factory('Hook_video_embed_'.$ve_hook);
			if (method_exists($ve_ob,'add_custom_comcode_field'))
				$ve_ob->add_custom_comcode_field();
		}
	}

	/**
	 * Standard aed_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/customcomcode';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_adv_comcode';

		$this->add_one_label=do_lang_tempcode('ADD_CUSTOM_COMCODE_TAG');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_CUSTOM_COMCODE_TAG');
		$this->edit_one_label=do_lang_tempcode('EDIT_CUSTOM_COMCODE_TAG');

		if ($type=='ad')
		{
			require_javascript('javascript_ajax');
			$script=find_script('snippet');
			$this->javascript.="
				var form=document.getElementById('main_form');
				form.old_submit=form.onsubmit;
				form.onsubmit=function()
					{
						document.getElementById('submit_button').disabled=true;
						var url='".addslashes($script)."?snippet=exists_tag&name='+window.encodeURIComponent(form.elements['tag'].value);
						if (!do_ajax_field_test(url))
						{
							document.getElementById('submit_button').disabled=false;
							return false;
						}
						document.getElementById('submit_button').disabled=false;
						if (typeof form.old_submit!='undefined' && form.old_submit) return form.old_submit();
						return true;
					};
			";
		}

		if ($type=='misc') return $this->misc();
		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		return do_next_manager(get_page_title('CUSTOM_COMCODE'),comcode_lang_string('DOC_CUSTOM_COMCODE'),
					array(
						/*	 type							  page	 params													 zone	  */
						array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_CUSTOM_COMCODE_TAG')),
						array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_CUSTOM_COMCODE_TAG')),
					),
					do_lang('CUSTOM_COMCODE')
		);
	}

	/**
	 * Standard aed_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A pair: The choose table, Whether re-ordering is supported from this screen.
	 */
	function nice_get_choose_table($url_map)
	{
		require_code('templates_results_table');

		$current_ordering=get_param('sort','tag_tag ASC');
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			'tag_tag'=>do_lang_tempcode('COMCODE_TAG'),
			'tag_title'=>do_lang_tempcode('TITLE'),
			'tag_dangerous_tag'=>do_lang_tempcode('DANGEROUS_TAG'),
			'tag_block_tag'=>do_lang_tempcode('BLOCK_TAG'),
			'tag_textual_tag'=>do_lang_tempcode('TEXTUAL_TAG'),
			'tag_enabled'=>do_lang_tempcode('ENABLED'),
		);

		$header_row=results_field_title(array(
			do_lang_tempcode('COMCODE_TAG'),
			do_lang_tempcode('TITLE'),
			do_lang_tempcode('DANGEROUS_TAG'),
			do_lang_tempcode('BLOCK_TAG'),
			do_lang_tempcode('TEXTUAL_TAG'),
			do_lang_tempcode('ENABLED'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$fields=new ocp_tempcode();

		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering);
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['tag_tag']),'_SELF');

			$fields->attach(results_entry(array($row['tag_tag'],get_translated_text($row['tag_title']),($row['tag_dangerous_tag']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),($row['tag_block_tag']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),($row['tag_textual_tag']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),($row['tag_enabled']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.$row['tag_tag'])))),true);
		}

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false);
	}

	/**
	 * Get tempcode for a custom comcode tag adding/editing form.
	 *
	 * @param  SHORT_TEXT	The title (name) of the custom comcode tag
	 * @param  LONG_TEXT		The description of the tag
	 * @param  BINARY			Whether the tag is enabled
	 * @param  ID_TEXT		The actual tag code
	 * @param  LONG_TEXT		What to replace the tag with
	 * @param  LONG_TEXT		Example usage
	 * @param  SHORT_TEXT	Comma-separated list of accepted parameters
	 * @param  BINARY			Whether it is a dangerous tag
	 * @param  BINARY			Whether it is a block tag
	 * @param  BINARY			Whether it is a textual tag
	 * @return tempcode		The input fields
	 */
	function get_form_fields($title='',$description='',$enabled=1,$tag='this',$replace='<span class="example" style="color: {color}">{content}</span>',$example='[this color="red"]blah[/this]',$parameters='color=black',$dangerous_tag=0,$block_tag=0,$textual_tag=1)
	{
		$fields=new ocp_tempcode();
		require_code('comcode_text');
		$fields->attach(form_input_codename(do_lang_tempcode('COMCODE_TAG'),do_lang_tempcode('DESCRIPTION_COMCODE_TAG'),'tag',$tag,true,NULL,MAX_COMCODE_TAG_LOOK_AHEAD_LENGTH));
		$fields->attach(form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_TAG_TITLE'),'title',$title,true));
		$fields->attach(form_input_line(do_lang_tempcode('DESCRIPTION'),do_lang_tempcode('DESCRIPTION_DESCRIPTION'),'description',$description,true));
		$fields->attach(form_input_text(do_lang_tempcode('COMCODE_REPLACE'),do_lang_tempcode('DESCRIPTION_COMCODE_REPLACE'),'replace',$replace,true));
		$fields->attach(form_input_line(do_lang_tempcode('PARAMETERS'),do_lang_tempcode('DESCRIPTION_COMCODE_PARAMETERS'),'parameters',$parameters,false));
		$fields->attach(form_input_tick(do_lang_tempcode('DANGEROUS_TAG'),do_lang_tempcode('DESCRIPTION_DANGEROUS_TAG'),'dangerous_tag',$dangerous_tag==1));
		$fields->attach(form_input_tick(do_lang_tempcode('BLOCK_TAG'),do_lang_tempcode('DESCRIPTION_BLOCK_TAG'),'block_tag',$block_tag==1));
		$fields->attach(form_input_tick(do_lang_tempcode('TEXTUAL_TAG'),do_lang_tempcode('DESCRIPTION_TEXTUAL_TAG'),'textual_tag',$textual_tag==1));
		$fields->attach(form_input_line(do_lang_tempcode('EXAMPLE'),do_lang_tempcode('DESCRIPTION_COMCODE_EXAMPLE'),'example',$example,true));
		$fields->attach(form_input_tick(do_lang_tempcode('ENABLED'),'','enabled',$enabled==1));

		return $fields;
	}

	/**
	 * Standard aed_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return tempcode		The edit form
	 */
	function fill_in_edit_form($id)
	{
		$m=$GLOBALS['SITE_DB']->query_select('custom_comcode',array('*'),array('tag_tag'=>$id),'',1);
		if (!array_key_exists(0,$m)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$r=$m[0];

		$fields=$this->get_form_fields(get_translated_text($r['tag_title']),get_translated_text($r['tag_description']),$r['tag_enabled'],$r['tag_tag'],$r['tag_replace'],$r['tag_example'],$r['tag_parameters'],$r['tag_dangerous_tag'],$r['tag_block_tag'],$r['tag_textual_tag']);

		return $fields;
	}

	/**
	 * Standard aed_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		$tag=post_param('tag');

		global $VALID_COMCODE_TAGS;
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('custom_comcode','tag_tag',array('tag_tag'=>$tag));
		if ((array_key_exists($tag,$VALID_COMCODE_TAGS)) || (!is_null($test))) warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($tag)));

		$GLOBALS['SITE_DB']->query_insert('custom_comcode',array(
			'tag_tag'=>$tag,
			'tag_title'=>insert_lang(post_param('title'),3),
			'tag_description'=>insert_lang(post_param('description'),3),
			'tag_replace'=>post_param('replace'),
			'tag_example'=>post_param('example'),
			'tag_parameters'=>post_param('parameters'),
			'tag_enabled'=>post_param_integer('enabled',0),
			'tag_dangerous_tag'=>post_param_integer('dangerous_tag',0),
			'tag_block_tag'=>post_param_integer('block_tag',0),
			'tag_textual_tag'=>post_param_integer('textual_tag',0)
		));

		log_it('ADD_'.$this->lang_type,$tag);

		return $tag;
	}

	/**
	 * Standard aed_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		$tag=post_param('tag');

		global $VALID_COMCODE_TAGS;
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('custom_comcode','tag_tag',array('tag_tag'=>$tag));
		if ($id==$tag) $test=NULL;
		if ((array_key_exists($tag,$VALID_COMCODE_TAGS)) || (!is_null($test))) warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($tag)));

		$old=$GLOBALS['SITE_DB']->query_select('custom_comcode',array('tag_title','tag_description'),array('tag_tag'=>$id),'',1);
		if (!array_key_exists(0,$old)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$_title=$old[0]['tag_title'];
		$_description=$old[0]['tag_description'];

		$GLOBALS['SITE_DB']->query_update('custom_comcode',array(
			'tag_tag'=>$tag,
			'tag_title'=>lang_remap($_title,post_param('title')),
			'tag_description'=>lang_remap($_description,post_param('description')),
			'tag_replace'=>post_param('replace'),
			'tag_example'=>post_param('example'),
			'tag_parameters'=>post_param('parameters'),
			'tag_enabled'=>post_param_integer('enabled',0),
			'tag_dangerous_tag'=>post_param_integer('dangerous_tag',0),
			'tag_block_tag'=>post_param_integer('block_tag',0),
			'tag_textual_tag'=>post_param_integer('textual_tag',0)
		),array('tag_tag'=>$id),'',1);

		$this->new_id=$tag;

		log_it('EDIT_'.$this->lang_type,$id);
	}

	/**
	 * Standard aed_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		$old=$GLOBALS['SITE_DB']->query_select('custom_comcode',array('tag_title','tag_description'),array('tag_tag'=>$id),'',1);
		if (!array_key_exists(0,$old)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$_title=$old[0]['tag_title'];
		$_description=$old[0]['tag_description'];

		$GLOBALS['SITE_DB']->query_delete('custom_comcode',array('tag_tag'=>$id),'',1);
		log_it('DELETE_'.$this->lang_type,$id);

		delete_lang($_title);
		delete_lang($_description);
	}
}


