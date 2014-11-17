<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    ocf_signatures
 */

/**
 * Hook class.
 */
class Hook_profiles_tabs_edit_signature
{
    /**
     * Find whether this hook is active.
     *
     * @param  MEMBER                   The ID of the member who is being viewed
     * @param  MEMBER                   The ID of the member who is doing the viewing
     * @return boolean                  Whether this hook is active
     */
    public function is_active($member_id_of, $member_id_viewing)
    {
        return (($member_id_of == $member_id_viewing) || (has_privilege($member_id_viewing, 'assume_any_member')) || (has_privilege($member_id_viewing, 'member_maintenance')));
    }

    /**
     * Render function for profile tabs edit hooks.
     *
     * @param  MEMBER                   The ID of the member who is being viewed
     * @param  MEMBER                   The ID of the member who is doing the viewing
     * @param  boolean                  Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
     * @return ?array                   A tuple: The tab title, the tab body text (may be blank), the tab fields, extra JavaScript (may be blank) the suggested tab order, hidden fields (optional) (null: if $leave_to_ajax_if_possible was set), the icon
     */
    public function render_tab($member_id_of, $member_id_viewing, $leave_to_ajax_if_possible = false)
    {
        $title = do_lang_tempcode('SIGNATURE');

        $order = 40;

        // Actualiser
        $new_signature = post_param('signature', null);
        if ($new_signature !== null) {
            require_code('ocf_members_action');
            require_code('ocf_members_action2');
            ocf_member_choose_signature($new_signature, $member_id_of);

            require_code('autosave');
            clear_ocp_autosave();

            attach_message(do_lang_tempcode('SUCCESS_SAVE'), 'inform');
        }

        if ($leave_to_ajax_if_possible) {
            return null;
        }

        // UI

        $signature = get_translated_tempcode($GLOBALS['FORUM_DRIVER']->get_member_row($member_id_of), 'm_signature', $GLOBALS['FORUM_DB']);
        $signature_original = get_translated_text($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_signature'), $GLOBALS['FORUM_DB']);

        $size = ocf_get_member_best_group_property($member_id_of, 'max_sig_length_comcode');

        $javascript = "
            var form=document.getElementById('signature').form;
            form.old_submit=form.onsubmit;
            form.onsubmit=function()
                    {
                            var post=form.elements['signature'];
                            if ((!post.value) && (post[1])) post=post[1];
                            if (post.value.length>" . strval($size) . ")
                            {
                                        window.fauxmodal_alert('" . php_addslashes(do_lang('SIGNATURE_TOO_BIG')) . "');
                                        return false;
                            }
                            if (typeof form.old_submit!='undefined' && form.old_submit) return form.old_submit();
                            return true;
                    };
        ";

        require_code('form_templates');

        $required = false;
        $has_preview = true;

        require_lang('javascript');
        require_javascript('javascript_posting');
        require_javascript('javascript_editing');
        require_javascript('javascript_ajax');
        require_javascript('javascript_plupload');
        require_css('widget_plupload');

        require_lang('comcode');

        $tabindex = get_form_field_tabindex();

        $post_comment = null;

        list($attachments, $attach_size_field) = get_attachments('signature');

        $hidden_fields = new Tempcode();
        $hidden_fields->attach($attach_size_field);

        $continue_url = get_self_url();

        $help_zone = get_comcode_zone('userguide_comcode', false);

        $emoticon_chooser = $GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();

        $comcode_editor = get_comcode_editor();
        $comcode_editor_small = get_comcode_editor('signature', true);

        $w = (has_js()) && (browser_matches('wysiwyg') && (strpos($signature_original, '{$,page hint: no_wysiwyg}') === false));
        $class = '';
        attach_wysiwyg();
        if ($w) {
            $class .= ' wysiwyg';
        }

        global $LAX_COMCODE;
        $temp = $LAX_COMCODE;
        $LAX_COMCODE = true;
        $GLOBALS['COMCODE_PARSE_URLS_CHECKED'] = 100; // Little hack to stop it checking any URLs
        /*Make sure we reparse with semi-parse mode if (is_null($default_parsed)) */
        $default_parsed = comcode_to_tempcode($signature_original, null, false, 60, null, null, true);
        $LAX_COMCODE = $temp;

        $fields = new Tempcode();
        $fields->attach(do_template('POSTING_FIELD', array(
            '_GUID' => '0424aff8c7961ed20ac525e7de04c219',
            'PRETTY_NAME' => do_lang_tempcode('SIGNATURE'),
            'DESCRIPTION' => '',
            'HIDDEN_FIELDS' => $hidden_fields,
            'NAME' => 'signature',
            'REQUIRED' => $required,
            'TABINDEX_PF' => strval($tabindex)/*not called TABINDEX due to conflict with FORM_STANDARD_END*/,
            'COMCODE_EDITOR' => $comcode_editor,
            'COMCODE_EDITOR_SMALL' => $comcode_editor_small,
            'CLASS' => $class,
            'COMCODE_URL' => is_null($help_zone) ? new Tempcode() : build_url(array('page' => 'userguide_comcode'), $help_zone),
            'EXTRA' => '',
            'POST_COMMENT' => $post_comment,
            'EMOTICON_CHOOSER' => $emoticon_chooser,
            'POST' => $signature_original,
            'DEFAULT_PARSED' => $default_parsed,
            'CONTINUE_URL' => $continue_url,
            'ATTACHMENTS' => $attachments,
        )));

        $text = do_template('OCF_EDIT_SIGNATURE_TAB', array('_GUID' => 'f5f2eb2552c34840c9cf46886422401e', 'SIZE' => integer_format($size), 'SIGNATURE' => $signature, 'TITLE' => $title));

        return array($title, $fields, $text, $javascript, $order, null, 'tabs/member_account/edit/signature');
    }
}
