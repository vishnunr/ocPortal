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
 * @package		ocf_signatures
 */

class Hook_Profiles_Tabs_Edit_signature
{

	/**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
	function is_active($member_id_of,$member_id_viewing)
	{
		return (($member_id_of==$member_id_viewing) || (has_specific_permission($member_id_viewing,'assume_any_member')) || (has_specific_permission($member_id_viewing,'member_maintenance')));
	}

	/**
	 * Standard modular render function for profile tabs edit hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return array			A tuple: The tab title, the tab body text (may be blank), the tab fields, extra Javascript (may be blank) the suggested tab order
	 */
	function render_tab($member_id_of,$member_id_viewing)
	{
		$title=do_lang_tempcode('SIGNATURE');

		$order=40;

		// Actualiser
		$new_signature=post_param('signature',NULL);
		if ($new_signature!==NULL)
		{
			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			ocf_member_choose_signature($new_signature,$member_id_of);

			require_code('autosave');
			clear_ocp_autosave();

			attach_message(do_lang_tempcode('SUCCESS_SAVE'),'inform');
		}

		// UI

		$_signature=get_translated_tempcode($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of,'m_signature'),$GLOBALS['FORUM_DB']);
		$signature=($_signature->is_empty())?do_lang_tempcode('NONE_EM'):$_signature;
		$_signature_original=get_translated_text($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of,'m_signature'),$GLOBALS['FORUM_DB']);

		$size=ocf_get_member_best_group_property($member_id_of,'max_sig_length_comcode');

		$javascript="
			var form=document.getElementById('signature').form;
			form.old_submit=form.onsubmit;
			form.onsubmit=function()
				{
					var post=form.elements['signature'];
					if ((!post.value) && (post[1])) post=post[1];
					if (post.value.length>".strval($size).")
					{
						window.alert('".php_addslashes(do_lang('SIGNATURE_TOO_BIG'))."');
						return false;
					}
					if (typeof form.old_submit!='undefined' && form.old_submit) return form.old_submit();
					return true;
				};
		";

		require_code('form_templates');

		$required=false;
		$has_preview=true;

		require_lang('javascript');
		require_javascript('javascript_posting');
		require_javascript('javascript_editing');
		require_javascript('javascript_ajax');
		require_javascript('javascript_swfupload');
		require_css('swfupload');

		require_lang('comcode');

		$tabindex=get_form_field_tabindex();

		$post_comment=do_lang_tempcode('SIGNATURE');

		list($attachments,$attach_size_field)=get_attachments('signature');

		$hidden_fields=new ocp_tempcode();
		$hidden_fields->attach($attach_size_field);

		$continue_url=get_self_url();

		$comcode_help=build_url(array('page'=>'userguide_comcode'),get_comcode_zone('userguide_comcode',false));

		$emoticon_chooser=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();

		$comcode_editor=get_comcode_editor();
		$comcode_editor_small=get_comcode_editor('signature',true);

		$w=/* (has_specific_permission($member_id_viewing,'comcode_dangerous')) && */(has_js()) && (browser_matches('wysiwyg') && (strpos($_signature_original,'{$,page hint: no_wysiwyg}')===false));

		$class='';
		global $JAVASCRIPT,$WYSIWYG_ATTACHED;
		if (!$WYSIWYG_ATTACHED)
			$JAVASCRIPT->attach(do_template('HTML_EDIT'));
		$WYSIWYG_ATTACHED=true;
		@header('Content-type: text/html; charset='.get_charset());

		if ($w) $class.=' wysiwyg';

		global $LAX_COMCODE;
		$temp=$LAX_COMCODE;
		$LAX_COMCODE=true;
		$GLOBALS['COMCODE_PARSE_URLS_CHECKED']=100; // Little hack to stop it checking any URLs
		/*if (is_null($default_parsed)) */$default_parsed=comcode_to_tempcode($_signature_original,NULL,false,60,NULL,NULL,true);
		$LAX_COMCODE=$temp;

		$fields=new ocp_tempcode();
		$fields->attach(do_template('POSTING_FIELD',array('PRETTY_NAME'=>do_lang_tempcode('SIGNATURE'),'DESCRIPTION'=>'','DEFAULT_PARSED'=>$_signature,'HIDDEN_FIELDS'=>$hidden_fields,'NAME'=>'signature','REQUIRED'=>$required,'TABINDEX_PF'=>strval($tabindex)/*not called TABINDEX due to conflict with FORM_STANDARD_END*/,'COMCODE_EDITOR'=>$comcode_editor,'COMCODE_EDITOR_SMALL'=>$comcode_editor_small,'CLASS'=>$class,'COMCODE_URL'=>build_url(array('page'=>'userguide_comcode'),get_comcode_zone('userguide_comcode',false)),'EXTRA'=>'','POST_COMMENT'=>$post_comment,'EMOTICON_CHOOSER'=>$emoticon_chooser,'COMCODE_HELP'=>$comcode_help,'POST'=>$_signature_original,'DEFAULT_PARSED'=>$default_parsed,'CONTINUE_URL'=>$continue_url,'ATTACHMENTS'=>$attachments)));

		$text=do_template('OCF_EDIT_SIGNATURE_TAB',array('_GUID'=>'f5f2eb2552c34840c9cf46886422401e','SIZE'=>integer_format($size),'SIGNATURE'=>$signature,'TITLE'=>$title));

		return array($title,$fields,$text,$javascript,$order);
	}

}


