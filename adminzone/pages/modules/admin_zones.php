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
 * @package		core_zone_editor
 */

/**
 * Module page class.
 */
class Module_admin_zones
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
		return array('misc'=>'ZONES','edit'=>'EDIT_ZONE','add'=>'ADD_ZONE');
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		/*		$zones=find_all_zones(true);		We don't really want to throw away on-disk data on reinstalls
		require_code('files');
		foreach ($zones as $zone)
		{
			//if (!in_array($zone,array('','docs','adminzone','collaboration','forum','cms','site'))) deldir_contents(get_file_base().'/'.$zone,true);
			$langs=find_all_langs(true);
			foreach (array_keys($langs) as $lang)
			{
				$path=get_custom_file_base().(($zone=='')?'':'/').$zone.'/pages/comcode_custom/'.$lang;
				if (file_exists($path)) deldir_contents($path,true);
				$path=get_custom_file_base().(($zone=='')?'':'/').$zone.'/pages/html_custom/'.$lang;
				if (file_exists($path)) deldir_contents($path,true);
			}
			//deldir_contents(get_file_base().(($zone=='')?'':'/').$zone.'/pages/minimodules_custom',true);
			// modules_custom purposely left
		}*/
	}

	var $title;
	var $id;
	var $nice_zone_name;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('zones');

		if ($type=='_editor')
		{
			attach_to_screen_header(make_string_tempcode('<base target="_blank" />'));
		}

		if ($type!='editor' && $type!='_editor' && $type!='__editor')
		{
			set_helper_panel_pic('pagepics/zones');
			set_helper_panel_tutorial('tut_structure');
		}

		if ($type=='editor')
		{
			breadcrumb_set_self(do_lang_tempcode('CHOOSE'));
		}

		if ($type=='_editor')
		{
			$id=get_param('id',''); // '' needed for short URLs
			if ($id=='/') $id='';

			$nice_zone_name=($id=='')?do_lang('_WELCOME'):$id;

			breadcrumb_set_parents(array(array('_SELF:_SELF:editor',do_lang_tempcode('CHOOSE'))));
			breadcrumb_set_self($nice_zone_name);

			$this->title=get_screen_title('_ZONE_EDITOR',true,array(escape_html($nice_zone_name)));

			$this->id=$id;
			$this->nice_zone_name=$nice_zone_name;
		}

		if ($type=='__editor')
		{
			$this->title=get_screen_title('ZONE_EDITOR');
		}

		if ($type=='add')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('ZONES'))));

			$this->title=get_screen_title('ADD_ZONE');
		}

		if ($type=='_add')
		{
			$this->title=get_screen_title('ADD_ZONE');
		}

		if ($type=='edit')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('ZONES'))));
			breadcrumb_set_self(do_lang_tempcode('CHOOSE'));

			$this->title=get_screen_title('EDIT_ZONE');
		}

		if ($type=='_edit')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('ZONES')),array('_SELF:_SELF:edit_zone',do_lang_tempcode('CHOOSE'))));

			$this->title=get_screen_title('EDIT_ZONE');
		}

		if ($type=='__edit')
		{
			$delete=post_param_integer('delete',0);
			if ($delete==1)
			{
				$this->title=get_screen_title('DELETE_ZONE');
			} else
			{
				$this->title=get_screen_title('EDIT_ZONE');
			}
		}

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('zones2');
		require_code('zones3');

		require_css('zone_editor');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->misc();
		if ($type=='editor') return $this->editor();
		if ($type=='_editor') return $this->_editor();
		if ($type=='__editor') return $this->__editor();
		if ($type=='add') return $this->add_zone();
		if ($type=='_add') return $this->_add_zone();
		if ($type=='edit') return $this->edit_zone();
		if ($type=='_edit') return $this->_edit_zone();
		if ($type=='__edit') return $this->__edit_zone();

		return $this->misc();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		return do_next_manager(get_screen_title('ZONES'),comcode_lang_string('DOC_ZONES'),
			array(
				/*	 type							  page	 params													 zone	  */
				array('add_one',array('_SELF',array('type'=>'add'),'_SELF'),do_lang('ADD_ZONE')),
				array('edit_one',array('_SELF',array('type'=>'edit'),'_SELF'),do_lang('EDIT_ZONE')),
			),
			do_lang('ZONES')
		);
	}

	/**
	 * The UI to choose a zone to edit using the zone editor.
	 *
	 * @return tempcode		The UI
	 */
	function editor()
	{
		return $this->edit_zone('_editor',get_screen_title('ZONE_EDITOR'));
	}

	/**
	 * The UI for the zone editor.
	 *
	 * @return tempcode		The UI
	 */
	function _editor()
	{
		$id=$this->id;
		$nice_zone_name=$this->nice_zone_name;

		$lang=choose_language($this->title,true);
		if (is_object($lang)) return $lang;

		require_javascript('javascript_zone_editor');
		require_javascript('javascript_ajax');
		require_javascript('javascript_more');
		require_javascript('javascript_posting');
		require_javascript('javascript_editing');
		require_javascript('javascript_validation');
		require_code('form_templates');
		require_lang('comcode');

		if (!has_js())
		{
			// Send them to the page permissions screen
			$url=build_url(array('page'=>'_SELF','type'=>'edit'),'_SELF');
			require_code('site2');
			assign_refresh($url,5.0);
			return redirect_screen($this->title,$url,do_lang_tempcode('NO_JS_ADVANCED_SCREEN_ZONE_EDITOR'));
		}

		// After completion prep/relay
		$_default_redirect=build_url(array('page'=>''),$id);
		$default_redirect=$_default_redirect->evaluate();
		$post_url=build_url(array('page'=>'_SELF','type'=>'__editor','lang'=>$lang,'redirect'=>get_param('redirect',$default_redirect),'id'=>$id),'_SELF');

		// Zone editing stuff
		$rows=$GLOBALS['SITE_DB']->query_select('zones',array('*'),array('zone_name'=>$id),'',1);
		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$rows[0];
		$header_text=get_translated_text($row['zone_header_text'],NULL,$lang);
		$default_page=$row['zone_default_page'];
		list($fields,,)=$this->get_form_fields(true,get_translated_text($row['zone_title'],NULL,$lang),$default_page,$header_text,$row['zone_theme'],$row['zone_wide'],$row['zone_require_session'],$row['zone_displayed_in_menu'],$id);

		// Page editing stuff
		$editor=array();
		foreach (array('panel_left',$default_page,'panel_right') as $i=>$for)
		{
			$page_info=_request_page($for,$id,NULL,$lang);
			if ($page_info===false)
			{
				$page_info=array('COMCODE_CUSTOM',$id,$for,$lang);
			}
			$is_comcode=false;
			$redirecting_to=NULL;
			$current_for=$for;
			$pure=false;
			switch ($page_info[0])
			{
				case 'COMCODE_CUSTOM_PURE':
					$pure=true;
				case 'COMCODE':
				case 'COMCODE_CUSTOM':
					$is_comcode=true;
					$type=do_lang_tempcode('COMCODE_PAGE');
					break;
				case 'HTML':
				case 'HTML_CUSTOM':
					$type=protect_from_escaping(escape_html('HTML'));
					break;
				case 'MODULES':
				case 'MODULES_CUSTOM':
					$type=do_lang_tempcode('MODULE');
					break;
				case 'MINIMODULES':
				case 'MINIMODULES_CUSTOM':
					$type=do_lang_tempcode('MINIMODULE');
					break;
				case 'REDIRECT':
					$type=do_lang_tempcode('REDIRECT_PAGE_TO',escape_html($page_info[1]['r_to_zone']),escape_html($page_info[1]['r_to_page']));
					$redirecting_to=$page_info[1]['r_to_zone'];
					$current_for=$page_info[1]['r_to_page'];

					$page_info=_request_page($current_for,$redirecting_to,NULL,$lang);
					if ($page_info!==false)
					{
						switch ($page_info[0])
						{
							case 'COMCODE_CUSTOM_PURE':
								$pure=true;
							case 'COMCODE':
							case 'COMCODE_CUSTOM':
								$is_comcode=true;
								break;
						}
					}
					break;
				default:
					$type=do_lang_tempcode('UNKNOWN');
					break;
			}
			$class='';
			$w=false;
			$current_zone=is_null($redirecting_to)?$id:$redirecting_to;
			$default_parsed=NULL;
			if ($is_comcode)
			{
				$fullpath=zone_black_magic_filterer((($page_info[0]=='comcode' || $pure)?get_file_base():get_custom_file_base()).'/'.$current_zone.'/pages/'.strtolower($page_info[0]).'/'.$lang.'/'.$current_for.'.txt');
				if (!file_exists($fullpath)) $fullpath=zone_black_magic_filterer((($page_info[0]=='comcode' || $pure)?get_file_base():get_custom_file_base()).'/'.$current_zone.'/pages/'.strtolower($page_info[0]).'/'.get_site_default_lang().'/'.$current_for.'.txt');
				if (file_exists($fullpath))
				{
					$tmp=fopen($fullpath,'rb');
					flock($tmp,LOCK_SH);
					$comcode=file_get_contents($fullpath);
					flock($tmp,LOCK_UN);
					fclose($tmp);
					$default_parsed=comcode_to_tempcode($comcode,NULL,false,60,NULL,NULL,true);
				} else
				{
					$comcode='';
				}

				$edit_url=build_url(array('page'=>'cms_comcode_pages','type'=>'_ed','page_link'=>$current_zone.':'.$current_for),get_module_zone('cms_comcode_pages'));

				// WYSIWYG?
				require_javascript('javascript_editing');
				$w=(has_js()) && (browser_matches('wysiwyg') && (strpos($comcode,'{$,page hint: no_wysiwyg}')===false));
				attach_wysiwyg();
				if ($w) $class.=' wysiwyg';
			} else // Can't edit a non-Comcode page in the zone editor
			{
				$comcode=NULL;
				$edit_url=new ocp_tempcode();
			}

			$field_name='edit_'.$for.'_textarea';
			if ($i==1)
			{
				$settings=$fields;
				$comcode_editor=get_comcode_editor($field_name);
			} else
			{
				$settings=NULL;
				$button='block';
				$comcode_editor=new ocp_tempcode();
				$comcode_editor->attach(do_template('COMCODE_EDITOR_BUTTON',array('_GUID'=>'0acc5dcf299325d0cf55871923148a54','DIVIDER'=>false,'FIELD_NAME'=>$field_name,'TITLE'=>do_lang_tempcode('INPUT_COMCODE_'.$button),'B'=>$button)));
				$button='comcode';
				$comcode_editor->attach(do_template('COMCODE_EDITOR_BUTTON',array('_GUID'=>'1acc5dcf299325d0cf55871923148a54','DIVIDER'=>false,'FIELD_NAME'=>$field_name,'TITLE'=>do_lang_tempcode('INPUT_COMCODE_'.$button),'B'=>$button)));
			}

			$preview=(substr($page_info[0],0,6)=='MODULE')?NULL:request_page($for,false,$id,NULL,true);
			if (!is_null($preview))
			{
				$_preview=$preview->evaluate();
				if ((!$is_comcode) || (strpos($comcode,'<')!==false)) // Save RAM by only doing this if needed
				{
					require_code('xhtml');
					$_preview=xhtmlise_html($_preview,true); // Fix potential errors by passing it through our XHTML fixer functions
				} else
				{
					$new=$_preview;
					if (preg_replace('#\s+#','',$new)!=preg_replace('#\s+#','',$_preview)) // If it was changed there was probably an error
					{
						$_preview=$new;
						$_preview.=do_lang('BROKEN_XHTML_FIXED');
					}
				}
			} else $_preview=NULL;

			$is_panel=(substr($for,0,6)=='panel_');

			require_code('zones3');
			$zone_list=($for==$current_for)?nice_get_zones($redirecting_to,array($id)):new ocp_tempcode() /*not simple so leave field out*/;

			$editor[$for]=static_evaluate_tempcode(do_template('ZONE_EDITOR_PANEL',array(
				'_GUID'=>'f32ac84fe18b90497acd4afa27698bf0',
				'DEFAULT_PARSED'=>$default_parsed,
				'CLASS'=>$class,
				'CURRENT_ZONE'=>$current_zone,
				'ZONES'=>$zone_list,
				'COMCODE'=>$comcode,
				'PREVIEW'=>$_preview,
				'ZONE'=>$id,
				'ID'=>$for,
				'IS_PANEL'=>$is_panel,
				'TYPE'=>$type,
				'EDIT_URL'=>$edit_url,
				'SETTINGS'=>$settings,
				'COMCODE_EDITOR'=>$comcode_editor,
			)));
		}

		list($warning_details,$ping_url)=handle_conflict_resolution($id);

		return do_template('ZONE_EDITOR_SCREEN',array(
			'_GUID'=>'3cb1aab6b16444484e82d22f2c8f1e9a',
			'ID'=>$id,
			'LANG'=>$lang,
			'PING_URL'=>$ping_url,
			'WARNING_DETAILS'=>$warning_details,
			'TITLE'=>$this->title,
			'URL'=>$post_url,
			'LEFT_EDITOR'=>$editor['panel_left'],
			'RIGHT_EDITOR'=>$editor['panel_right'],
			'MIDDLE_EDITOR'=>$editor[$default_page],
		));
	}

	/**
	 * The actualiser to edit a zone (via zone editor).
	 *
	 * @return tempcode		The UI
	 */
	function __editor()
	{
		$lang=choose_language($this->title,true);
		if (is_object($lang)) return $lang;

		$id=get_param('id','');

		// Edit settings
		$_title=post_param('title');
		$default_page=post_param('default_page');
		$header_text=post_param('header_text');
		$theme=post_param('theme');
		$wide=post_param_integer('wide');
		if ($wide==-1) $wide=NULL;
		$require_session=post_param_integer('require_session',0);
		$displayed_in_menu=post_param_integer('displayed_in_menu',0);
		actual_edit_zone($id,$_title,$default_page,$header_text,$theme,$wide,$require_session,$displayed_in_menu,$id);
		if ($id!='') $this->set_permissions($id);

		// Edit pages
		foreach (array('panel_left','start','panel_right') as $for)
		{
			$redirect=post_param('redirect_'.$for,NULL);
			if (!is_null($redirect))
			{
				if (addon_installed('redirects_editor'))
				{
					$GLOBALS['SITE_DB']->query_delete('redirects',array(
						'r_from_page'=>$for,
						'r_from_zone'=>$id,
					),'',1);

					if ($redirect!=$id)
					{
						$GLOBALS['SITE_DB']->query_insert('redirects',array(
							'r_from_page'=>$for,
							'r_from_zone'=>$id,
							'r_to_page'=>$for,
							'r_to_zone'=>$redirect,
							'r_is_transparent'=>1,
						),false,true); // Avoid problem when same key entered twice
					} else $redirect=NULL;
				} else $redirect=NULL;
			}

			$comcode=post_param($for,NULL);
			if (!is_null($comcode))
			{
				// Where to save to
				$fullpath=zone_black_magic_filterer(get_custom_file_base().(((is_null($redirect)?$id:$redirect)=='')?'':'/').(is_null($redirect)?$id:$redirect).'/pages/comcode_custom/'.$lang.'/'.$for.'.txt');

				// Make dir if needed
				if (!file_exists(dirname($fullpath)))
				{
					if (@mkdir(dirname($fullpath),0777,true)===false)
					{
						warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY_REPAIR',escape_html(basename(dirname($fullpath))),escape_html(dirname(dirname($fullpath)))));
					}
					fix_permissions(dirname($fullpath),0777);
					sync_file(dirname($fullpath));
				}

				// Store revision
				if ((file_exists($fullpath)) && (get_option('store_revisions')=='1'))
				{
					$time=time();
					@copy($fullpath,$fullpath.'.'.strval($time)) OR intelligent_write_error($fullpath.'.'.strval($time));
					fix_permissions($fullpath.'.'.strval($time));
					sync_file($fullpath.'.'.strval($time));
				}

				// Save
				$myfile=@fopen($fullpath,'at') OR intelligent_write_error($fullpath);
				flock($myfile,LOCK_EX);
				ftruncate($myfile,0);
				if (fwrite($myfile,$comcode)<strlen($comcode)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
				flock($myfile,LOCK_UN);
				fclose($myfile);
				fix_permissions($fullpath);
				sync_file($fullpath);

				// De-cache
				$caches=$GLOBALS['SITE_DB']->query_select('cached_comcode_pages',array('string_index'),array('the_zone'=>is_null($redirect)?$id:$redirect,'the_page'=>$for));
				foreach ($caches as $cache)
				{
					delete_lang($cache['string_index']);
				}
				$GLOBALS['SITE_DB']->query_delete('cached_comcode_pages',array('the_zone'=>is_null($redirect)?$id:$redirect,'the_page'=>$for));
			}
		}

		erase_persistent_cache();

		// Redirect
		$url=get_param('redirect');
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Get tempcode for a zone adding/editing form.
	 *
	 * @param  boolean		Whether the zone editor will be used
	 * @param  SHORT_TEXT	The zone title
	 * @param  ID_TEXT		The zones default page
	 * @param  SHORT_TEXT	The header text
	 * @param  ?ID_TEXT		The theme (NULL: no override)
	 * @param  BINARY			Whether the zone is wide
	 * @param  BINARY			Whether the zone requires a session for pages to be used
	 * @param  BINARY			Whether the zone in displayed in the menu coded into some themes
	 * @param  ?ID_TEXT		Name of the zone (NULL: unknown)
	 * @return array			A tuple: The tempcode for the fields, hidden fields, and extra Javascript
	 */
	function get_form_fields($in_zone_editor=false,$title='',$default_page='start',$header_text='',$theme=NULL,$wide=0,$require_session=0,$displayed_in_menu=1,$zone=NULL)
	{
		require_lang('permissions');

		$javascript="
			var zone=document.getElementById('zone');
			zone.onblur=function() {
				var title=document.getElementById('title');
				if (title.value=='') title.value=zone.value.substr(0,1).toUpperCase()+zone.value.substring(1,zone.value.length).replace(/\_/g,' ');
			}
		";

		$fields='';
		$hidden=new ocp_tempcode();

		require_code('form_templates');
		$fields.=static_evaluate_tempcode(form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_TITLE'),'title',$title,true));
		$fields.=static_evaluate_tempcode(form_input_line(do_lang_tempcode('DEFAULT_PAGE'),do_lang_tempcode('DESCRIPTION_DEFAULT_PAGE'),'default_page',$default_page,true));
		$fields.=static_evaluate_tempcode(form_input_line(do_lang_tempcode('HEADER_TEXT'),do_lang_tempcode('DESCRIPTION_HEADER_TEXT'),'header_text',$header_text,false));
		$list='';
		$list.=static_evaluate_tempcode(form_input_list_entry('0',($wide==0),do_lang_tempcode('NO')));
		$list.=static_evaluate_tempcode(form_input_list_entry('1',($wide==1),do_lang_tempcode('YES')));
		$list.=static_evaluate_tempcode(form_input_list_entry('-1',is_null($wide),do_lang_tempcode('RELY_FORUMS')));
		$fields.=static_evaluate_tempcode(form_input_list(do_lang_tempcode('WIDE'),do_lang_tempcode('DESCRIPTION_WIDE'),'wide',make_string_tempcode($list)));
		$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('DISPLAYED_IN_MENU'),do_lang_tempcode('DESCRIPTION_DISPLAYED_IN_MENU'),'displayed_in_menu',($displayed_in_menu==1)));

		// Theme
		require_code('themes2');
		$entries=nice_get_themes($theme,false,true);
		$fields.=static_evaluate_tempcode(form_input_list(do_lang_tempcode('THEME'),do_lang_tempcode((get_forum_type()=='ocf')?'_DESCRIPTION_THEME_OCF':'_DESCRIPTION_THEME',substr(preg_replace('#[^A-Za-z\d]#','_',get_site_name()),0,80)),'theme',$entries));

		$fields.=static_evaluate_tempcode(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'b997e901934b59fa72c944e0ce6fc1b0','SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('ADVANCED'))));
		$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('REQUIRE_SESSION'),do_lang_tempcode('DESCRIPTION_REQUIRE_SESSION'),'require_session',($require_session==1)));

		$base_url='';
		if (($zone!==NULL) && ($zone!=''))
		{
			global $SITE_INFO;
			if (isset($SITE_INFO['ZONE_MAPPING_'.$zone]))
			{
				$base_url='http://'.$SITE_INFO['ZONE_MAPPING_'.$zone][0].'/'.$SITE_INFO['ZONE_MAPPING_'.$zone][1];
			}
		}
		$fields.=static_evaluate_tempcode(form_input_line(do_lang_tempcode('ZONE_BASE_URL'),do_lang_tempcode('DESCRIPTION_ZONE_BASE_URL'),'base_url',$base_url,false));

		if ((!$in_zone_editor) && (!is_null($zone)) && (addon_installed('zone_logos')))
		{
			// Logos
			handle_max_file_size($hidden,'image');
			require_code('themes2');
			$themes=find_all_themes();
			foreach ($themes as $theme=>$theme_name)
			{
				$fields.=static_evaluate_tempcode(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'8c4c1267060970f8b89d1068d03280a7','SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('THEME_LOGO',escape_html($theme_name)))));

				require_code('themes2');
				$ids=get_all_image_ids_type('logo',false,NULL,$theme);

				$set_name='logo_choose_'.$theme;
				$required=true;
				$set_title=do_lang_tempcode('LOGO');
				$field_set=(count($ids)==0)?new ocp_tempcode():alternate_fields_set__start($set_name);

				$field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'),'','logo_upload_'.$theme,$required,NULL,NULL,true,str_replace(' ','',get_option('valid_images'))));

				$current_logo='logo/'.$zone.'-logo';
				if (!in_array($current_logo,$ids)) $current_logo='logo/-logo';

				foreach ($ids as $id)
				{
					$test=find_theme_image($id,true,false,$theme);
					if ($test=='') $test=find_theme_image($id,false,false,'default');
					if (($test=='') && ($id==$current_logo)) $current_logo=$ids[0];
				}
				$field_set->attach(form_input_theme_image(do_lang_tempcode('STOCK'),'','logo_select_'.$theme,$ids,NULL,$current_logo,NULL,false,NULL,$theme));

				$fields.=static_evaluate_tempcode(alternate_fields_set__end($set_name,$set_title,'',$field_set,$required));
			}
		}

		if ($zone!=='')
		{
			$fields.=static_evaluate_tempcode(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'579ab823f5f8ec7b48e3b59af8a64ba2','TITLE'=>do_lang_tempcode('PERMISSIONS'))));

			// Permissions
			$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
			foreach ($groups as $id=>$name)
			{
				if (in_array($id,$admin_groups)) continue;

				$perhaps=is_null($zone)?true:$GLOBALS['SITE_DB']->query_select_value_if_there('group_zone_access','zone_name',array('zone_name'=>$zone,'group_id'=>$id));
				$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('ACCESS_FOR',escape_html($name)),do_lang_tempcode('DESCRIPTION_ACCESS_FOR',escape_html($name)),'access_'.strval($id),!is_null($perhaps)));
			}
		}

		return array(make_string_tempcode($fields),$hidden,$javascript);
	}

	/**
	 * The UI to add a zone.
	 *
	 * @return tempcode		The UI
	 */
	function add_zone()
	{
		appengine_live_guard();

		if (get_file_base()!=get_custom_file_base()) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		$url_scheme=get_option('url_scheme');
		$change_htaccess=(($url_scheme=='HTM') || ($url_scheme=='SIMPLE'));
		$htaccess_path=get_file_base().'/.htaccess';
		if (($change_htaccess) && (file_exists($htaccess_path)) && (!is_writable_wrap($htaccess_path)))
		{
			attach_message(do_lang_tempcode('HTM_SHORT_URLS_CARE'),'warn');
		}

		require_code('form_templates');

		url_default_parameters__enable();

		$fields=new ocp_tempcode();
		$fields->attach(form_input_codename(do_lang_tempcode('CODENAME'),do_lang_tempcode('DESCRIPTION_NAME'),'zone','',true));
		list($_fields,$hidden,$javascript)=$this->get_form_fields();
		$fields->attach($_fields);

		url_default_parameters__disable();

		$post_url=build_url(array('page'=>'_SELF','type'=>'_add'),'_SELF');
		$submit_name=do_lang_tempcode('ADD_ZONE');
		$text=paragraph(do_lang_tempcode('ZONE_ADD_TEXT'));

		require_javascript('javascript_ajax');
		$script=find_script('snippet');
		$javascript.="
			var form=document.getElementById('main_form');
			form.old_submit=form.onsubmit;
			form.onsubmit=function()
				{
					document.getElementById('submit_button').disabled=true;
					var url='".addslashes($script)."?snippet=exists_zone&name='+window.encodeURIComponent(form.elements['zone'].value);
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

		return do_template('FORM_SCREEN',array('_GUID'=>'d8f08884cc370672c2e5604aefe78c6c','JAVASCRIPT'=>$javascript,'HIDDEN'=>$hidden,'SUBMIT_NAME'=>$submit_name,'TITLE'=>$this->title,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text));
	}

	/**
	 * The actualiser to add a zone.
	 *
	 * @return tempcode		The UI
	 */
	function _add_zone()
	{
		appengine_live_guard();

		if (get_file_base()!=get_custom_file_base()) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		$zone=post_param('zone');

		check_zone_name($zone);

		require_code('abstract_file_manager');
		force_have_afm_details();

		$_title=post_param('title');
		$default_page=post_param('default_page');
		$header_text=post_param('header_text');
		$theme=post_param('theme');
		$wide=post_param_integer('wide');
		if ($wide==-1) $wide=NULL;
		$require_session=post_param_integer('require_session',0);
		$displayed_in_menu=post_param_integer('displayed_in_menu',0);
		$base_url=post_param('base_url','');

		actual_add_zone($zone,$_title,$default_page,$header_text,$theme,$wide,$require_session,$displayed_in_menu,false,$base_url);

		sync_htaccess_with_zones();

		$this->set_permissions($zone);

		// Show it worked / Refresh
		$url=build_url(array('page'=>$default_page),$zone);
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI to choose a zone to edit.
	 *
	 * @param  string			The follow-on type
	 * @param  ?tempcode		The title to use (NULL: the EDIT_ZONE title)
	 * @return tempcode		The UI
	 */
	function edit_zone($type='_edit',$title=NULL)
	{
		if (is_null($title)) $title=$this->title;

		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);

		$_zones=find_all_zones(false,true,false,$start,$max);

		$url_map=array('page'=>'_SELF','type'=>$type);
		if ($type=='_editor') $url_map['wide']=1;

		require_code('templates_results_table');

		$current_ordering='name ASC';
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array();

		$header_row=results_field_title(array(
			do_lang_tempcode('NAME'),
			do_lang_tempcode('TITLE'),
			do_lang_tempcode('DEFAULT_PAGE'),
			do_lang_tempcode('THEME'),
			do_lang_tempcode('DISPLAYED_IN_MENU'),
			do_lang_tempcode('WIDE'),
			do_lang_tempcode('REQUIRE_SESSION'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		require_code('form_templates');
		$max_rows=$GLOBALS['SITE_DB']->query_select_value('zones','COUNT(*)');
		foreach ($_zones as $_zone_details)
		{
			list($zone_name,$zone_title,$zone_show_in_menu,$zone_default_page,$remaining_row)=$_zone_details;

			$edit_link=build_url($url_map+array('id'=>$zone_name),'_SELF');

			$fields->attach(results_entry(array(
				hyperlink(build_url(array('page'=>''),$zone_name),($zone_name=='')?do_lang_tempcode('NA_EM'):make_string_tempcode(escape_html($zone_name))),
				$zone_title,
				$zone_default_page,
				($remaining_row['zone_theme']=='-1')?do_lang_tempcode('NA_EM'):hyperlink(build_url(array('page'=>'admin_themes'),'adminzone'),escape_html($remaining_row['zone_theme'])),
				($zone_show_in_menu==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
				($remaining_row['zone_wide']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
				($remaining_row['zone_require_session']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
				protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,$zone_name)),
			)),true);
		}

		$table=results_table(do_lang('ZONES'),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order);

		$text=do_lang_tempcode('CHOOSE_EDIT_LIST');
		$tpl=do_template('COLUMNED_TABLE_SCREEN',array('_GUID'=>'a33d3ff1178e7898b42acd83b38b5dcb','TITLE'=>$title,'TEXT'=>$text,'TABLE'=>$table,'SUBMIT_NAME'=>NULL,'POST_URL'=>get_self_url()));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl);
	}

	/**
	 * The UI to edit a zone.
	 *
	 * @return tempcode		The UI
	 */
	function _edit_zone()
	{
		require_lang('themes');

		$zone=get_param('id',''); // '' needed for short URLs
		if ($zone=='/') $zone='';

		$rows=$GLOBALS['SITE_DB']->query_select('zones',array('*'),array('zone_name'=>$zone),'',1);
		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$rows[0];

		$header_text=get_translated_text($row['zone_header_text']);
		list($fields,$hidden,$javascript)=$this->get_form_fields(false,get_translated_text($row['zone_title']),$row['zone_default_page'],$header_text,$row['zone_theme'],$row['zone_wide'],$row['zone_require_session'],$row['zone_displayed_in_menu'],$zone);
		$hidden->attach(form_input_hidden('zone',$zone));
		$no_delete_zones=(get_forum_type()=='ocf')?array('','adminzone','forum'):array('','adminzone');
		$no_rename_zones=array('','adminzone','forum');
		$no_rename=(appengine_is_live()) || (in_array($zone,$no_rename_zones)) || (get_file_base()!=get_custom_file_base());
		if ($no_rename)
		{
			$hidden->attach(form_input_hidden('new_zone',$zone));
		} else
		{
			$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'b6915d8e00ae36d2f47e44bbbb14ae69','TITLE'=>do_lang_tempcode('ACTIONS'))));
			$rename_label='DESCRIPTION_ZONE_RENAME';
			if (in_array($zone,array('site','cms','collaboration')))
				$rename_label='DESCRIPTION_ZONE_RENAME_DEFAULT_ZONE';
			$fields->attach(form_input_codename(do_lang_tempcode('CODENAME'),do_lang_tempcode($rename_label),'new_zone',$zone,true));
		}
		if ((!in_array($zone,$no_delete_zones)) && (!appengine_is_live()) && (get_file_base()==get_custom_file_base()))
		{
			if ($no_rename) $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'2fec0bddfe975b573da9bbd68ec16689','TITLE'=>do_lang_tempcode('ACTIONS'))));
			$fields->attach(form_input_tick(do_lang_tempcode('DELETE'),do_lang_tempcode('DESCRIPTION_DELETE'),'delete',false));
		}

		$map=array('page'=>'_SELF','type'=>'__edit');
		$url=get_param('redirect',NULL);
		if (!is_null($url)) $map['redirect']=$url;
		$post_url=build_url($map,'_SELF');
		$submit_name=do_lang_tempcode('SAVE');

		return do_template('FORM_SCREEN',array('_GUID'=>'54a578646aed86da06f30c459c9586c2','JAVASCRIPT'=>$javascript,'HIDDEN'=>$hidden,'SUBMIT_NAME'=>$submit_name,'TITLE'=>$this->title,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>''));
	}

	/**
	 * The actualiser to edit a zone.
	 *
	 * @return tempcode		The UI
	 */
	function __edit_zone()
	{
		$zone=post_param('zone');

		$delete=post_param_integer('delete',0);

		if (($delete==1) && (!appengine_is_live()))
		{
			actual_delete_zone($zone);

			// Show it worked / Refresh
			$_url=build_url(array('page'=>'_SELF','type'=>'edit'),'_SELF');
			return redirect_screen($this->title,$_url,do_lang_tempcode('SUCCESS'));
		} else
		{
			$_title=post_param('title');
			$default_page=post_param('default_page');
			$header_text=post_param('header_text');
			$theme=post_param('theme');
			$wide=post_param_integer('wide');
			if ($wide==-1) $wide=NULL;
			$require_session=post_param_integer('require_session',0);
			$displayed_in_menu=post_param_integer('displayed_in_menu',0);
			$base_url=post_param('base_url','');

			$new_zone=post_param('new_zone');
			if ($new_zone!=$zone)
			{
				appengine_live_guard();
				check_zone_name($new_zone);
			}
			actual_edit_zone($zone,$_title,$default_page,$header_text,$theme,$wide,$require_session,$displayed_in_menu,$new_zone,false,false,$base_url);

			if ($new_zone!='') $this->set_permissions($new_zone);

			$this->title=get_screen_title('EDIT_ZONE'); // Re-get title late, as we might be changing the theme this title is got from

			// Handle logos
			if (addon_installed('zone_logos'))
			{
				require_code('themes2');
				require_code('uploads');
				$themes=find_all_themes();
				foreach (array_keys($themes) as $theme)
				{
					$iurl='';
					if ((is_swf_upload()) || (((array_key_exists('logo_upload_'.$theme,$_FILES)) && (is_uploaded_file($_FILES['logo_upload_'.$theme]['tmp_name'])))))
					{
						$urls=get_url('','logo_upload_'.$theme,'themes/'.$theme.'/images_custom',0,OCP_UPLOAD_IMAGE);
						$iurl=$urls[0];
					}
					if ($iurl=='')
					{
						$theme_img_code=post_param('logo_select_'.$theme,'');
						if ($theme_img_code=='')
						{
							continue; // Probably a theme was added half-way
							//warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD'));
						}
						$iurl=find_theme_image($theme_img_code,false,true,$theme);
					}
					$GLOBALS['SITE_DB']->query_delete('theme_images',array('id'=>'logo/'.$new_zone.'-logo','theme'=>$theme,'lang'=>get_site_default_lang()),'',1);
					$GLOBALS['SITE_DB']->query_insert('theme_images',array('id'=>'logo/'.$new_zone.'-logo','theme'=>$theme,'path'=>$iurl,'lang'=>get_site_default_lang()));
					persistent_cache_delete('THEME_IMAGES');
				}
			}

			sync_htaccess_with_zones();

			// Show it worked / Refresh
			$url=get_param('redirect',NULL);
			if (is_null($url))
			{
				$_url=build_url(array('page'=>'_SELF','type'=>'edit'),'_SELF');
				$url=$_url->evaluate();
			}
			return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
		}
	}

	/**
	 * Set zone access permissions from info in the POST request.
	 *
	 * @param  ID_TEXT		The zone that we're setting permissions for
	 */
	function set_permissions($zone)
	{
		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		foreach (array_keys($groups) as $id)
		{
			if (in_array($id,$admin_groups)) continue;

			$value=post_param_integer('access_'.strval($id),0);
			$GLOBALS['SITE_DB']->query_delete('group_zone_access',array('zone_name'=>$zone,'group_id'=>$id),'',1);
			if ($value==1)
			{
				$GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>$zone,'group_id'=>$id));
			}
		}

		decache('main_sitemap');
		require_code('caches3');
		erase_block_cache();
		erase_persistent_cache();
	}

}


