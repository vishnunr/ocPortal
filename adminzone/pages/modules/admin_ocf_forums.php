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
 * @package    ocf_forum
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_ocf_forums extends Standard_crud_module
{
    public $lang_type = 'FORUM';
    public $select_name = 'NAME';
    public $protect_first = 1;
    public $archive_entry_point = '_SEARCH:forumview';
    public $archive_label = 'SECTION_FORUMS';
    public $view_entry_point = '_SEARCH:forumview:_ID';
    public $special_edit_frontend = true;
    public $privilege_page = 'topics';
    public $permission_module = 'forums';
    public $content_type = 'forum';
    public $javascript = 'if (document.getElementById(\'delete\')) { var form=document.getElementById(\'delete\').form; var crf=function() { form.elements[\'target_forum\'].disabled=(!form.elements[\'delete\'].checked); form.elements[\'delete_topics\'].disabled=(!form.elements[\'delete\'].checked); }; crf(); form.elements[\'delete\'].onchange=crf; }';
    public $menu_label = 'SECTION_FORUMS';
    public $do_preview = null;

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  Whether to check permissions.
     * @param  ?MEMBER                  The member to check permissions as (null: current user).
     * @param  boolean                  Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        if (get_forum_type() != 'ocf') {
            return null;
        }

        $ret = array(
                'misc' => array('MANAGE_FORUMS', 'menu/social/forum/forums'),
            ) + parent::get_entry_points();

        if ($support_crosslinks) {
            $ret['_SEARCH:admin_ocf_forum_groupings:ad'] = array('ADD_FORUM_GROUPING', 'menu/_generic_admin/add_one_category');
            $ret['_SEARCH:admin_ocf_forum_groupings:ed'] = array(do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('EDIT_FORUM_GROUPING'), make_string_tempcode(escape_html(integer_format($GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_groupings', 'COUNT(*)', null, '', true))))), 'menu/_generic_admin/edit_one_category');
            if (addon_installed('ocf_post_templates')) {
                require_lang('ocf_post_templates');
                $ret['_SEARCH:admin_ocf_post_templates:misc'] = array(do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('POST_TEMPLATES'), make_string_tempcode(escape_html(integer_format($GLOBALS['FORUM_DB']->query_select_value_if_there('f_post_templates', 'COUNT(*)', null, '', true))))), 'menu/adminzone/structure/forum/post_templates');
            }
            if (addon_installed('ocf_multi_moderations')) {
                require_lang('ocf_multi_moderations');
                $ret['_SEARCH:admin_ocf_multi_moderations:misc'] = array(do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('MULTI_MODERATIONS'), make_string_tempcode(escape_html(integer_format($GLOBALS['FORUM_DB']->query_select_value_if_there('f_multi_moderations', 'COUNT(*)', null, '', true))))), 'menu/adminzone/structure/forum/multi_moderations');
            }

            require_code('fields');
            $ret += manage_custom_fields_entry_points('post') + manage_custom_fields_entry_points('topic') + manage_custom_fields_entry_points('forum');
        }
        return $ret;
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @param  boolean                  Whether this is running at the top level, prior to having sub-objects called.
     * @param  ?ID_TEXT                 The screen type to consider for meta-data purposes (null: read from environment).
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run($top_level = true, $type = null)
    {
        $type = get_param('type', 'misc');

        require_lang('ocf');
        require_css('ocf_admin');

        inform_non_canonical_parameter('parent_forum');
        inform_non_canonical_parameter('forum_grouping_id');

        set_helper_panel_tutorial('tut_forums');

        if ($type == 'reorder' || $type == 'ed') {
            $this->title = get_screen_title('EDIT_FORUM');
        }

        return parent::pre_run($top_level);
    }

    /**
     * Standard crud_module run_start.
     *
     * @param  ID_TEXT                  The type of module execution
     * @return tempcode                 The output of the run
     */
    public function run_start($type)
    {
        $this->add_one_label = do_lang_tempcode('ADD_FORUM');
        $this->edit_this_label = do_lang_tempcode('EDIT_THIS_FORUM');
        $this->edit_one_label = do_lang_tempcode('EDIT_FORUM');

        global $C_TITLE;
        $C_TITLE = null;

        if (get_forum_type() != 'ocf') {
            warn_exit(do_lang_tempcode('NO_OCF'));
        } else {
            ocf_require_all_forum_stuff();
        }
        require_code('ocf_forums_action');
        require_code('ocf_forums_action2');
        require_code('ocf_forums2');
        require_css('ocf');
        require_css('ocf_editor');

        load_up_all_module_category_permissions($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'forums');

        if ($type == 'misc') {
            return $this->misc();
        }
        if ($type == 'reorder') {
            return $this->reorder();
        }

        return new Tempcode();
    }

    /**
     * The do-next manager for before content management.
     *
     * @return tempcode                 The UI
     */
    public function misc()
    {
        $menu_links = array(
            array('menu/_generic_admin/add_one_category', array('admin_ocf_forum_groupings', array('type' => 'ad'), get_module_zone('admin_ocf_forum_groupings')), do_lang('ADD_FORUM_GROUPING')),
            array('menu/_generic_admin/edit_one_category', array('admin_ocf_forum_groupings', array('type' => 'ed'), get_module_zone('admin_ocf_forum_groupings')), do_lang('EDIT_FORUM_GROUPING')),
            array('menu/_generic_admin/add_one', array('_SELF', array('type' => 'ad'), '_SELF'), do_lang('ADD_FORUM')),
            array('menu/_generic_admin/edit_one', array('_SELF', array('type' => 'ed'), '_SELF'), do_lang('EDIT_FORUM')),
        );

        if (addon_installed('ocf_post_templates')) {
            require_lang('ocf_post_templates');
            $menu_links[] = array('menu/adminzone/structure/forum/post_templates', array('admin_ocf_post_templates', array('type' => 'misc'), get_module_zone('admin_ocf_post_templates')), do_lang_tempcode('POST_TEMPLATES'), 'DOC_POST_TEMPLATES');
        }
        if (addon_installed('ocf_multi_moderations')) {
            require_lang('ocf_multi_moderations');
            $menu_links[] = array('menu/adminzone/structure/forum/multi_moderations', array('admin_ocf_multi_moderations', array('type' => 'misc'), get_module_zone('admin_ocf_multi_moderations')), do_lang_tempcode('MULTI_MODERATIONS'), 'DOC_MULTI_MODERATIONS');
        }

        require_code('templates_donext');
        require_code('fields');
        return do_next_manager(get_screen_title('MANAGE_FORUMS'), comcode_to_tempcode(do_lang('DOC_FORUMS') . "\n\n" . do_lang('DOC_FORUM_CATEGORIES'), null, true),
            array_merge($menu_links, manage_custom_fields_donext_link('post'), manage_custom_fields_donext_link('topic'), manage_custom_fields_donext_link('forum')),
            do_lang('MANAGE_FORUMS')
        );
    }

    /**
     * Get tempcode for a forum adding/editing form.
     *
     * @param  ?AUTO_LINK               The ID of the forum being edited (null: adding, not editing)
     * @param  SHORT_TEXT               The name of the forum
     * @param  LONG_TEXT                The description of the forum
     * @param  ?AUTO_LINK               The ID of the forum grouping for the forum (null: first)
     * @param  ?AUTO_LINK               The parent forum (null: root)
     * @param  ?integer                 The position (null: next)
     * @param  BINARY                   Whether post counts are incremented in this forum
     * @param  BINARY                   Whether subforums are ordered alphabetically (instead of manually)
     * @param  LONG_TEXT                Introductory question posed to all newcomers to the forum
     * @param  LONG_TEXT                Answer to the introductory question (or blank if it was just an 'ok')
     * @param  SHORT_TEXT               Redirection code (blank implies a normal forum, not a redirector)
     * @param  ID_TEXT                  The order the topics are shown in, by default.
     * @param  BINARY                   Whether the forum is threaded.
     * @return array                    A pair: The input fields, Hidden fields
     */
    public function get_form_fields($id = null, $name = '', $description = '', $forum_grouping_id = null, $parent_forum = null, $position = null, $post_count_increment = 1, $order_sub_alpha = 0, $intro_question = '', $intro_answer = '', $redirection = '', $order = 'last_post', $is_threaded = 0)
    {
        if (is_null($forum_grouping_id)) {
            $forum_grouping_id = get_param_integer('forum_grouping_id', db_get_first_id());
        }

        if (is_null($parent_forum)) {
            $parent_forum = get_param_integer('parent_forum', null);
        }

        if (is_null($position)) {
            $position = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums', 'MAX(f_position)') + 1;
        }

        $fields = new Tempcode();
        $hidden = new Tempcode();

        $fields->attach(form_input_line(do_lang_tempcode('NAME'), do_lang_tempcode('DESCRIPTION_NAME'), 'name', $name, true));
        $fields->attach(form_input_line_comcode(do_lang_tempcode('DESCRIPTION'), do_lang_tempcode('DESCRIPTION_DESCRIPTION'), 'description', $description, false));
        $list = ocf_create_selection_list_forum_groupings(null, $forum_grouping_id);
        $fields->attach(form_input_list(do_lang_tempcode('FORUM_GROUPING'), do_lang_tempcode('DESCRIPTION_FORUM_GROUPING'), 'forum_grouping_id', $list));
        if ((is_null($id)) || ((!is_null($id)) && ($id != db_get_first_id()))) {
            $fields->attach(form_input_tree_list(do_lang_tempcode('PARENT'), do_lang_tempcode('DESCRIPTION_PARENT_FORUM'), 'parent_forum', null, 'choose_forum', array(), true, is_null($parent_forum) ? '' : strval($parent_forum)));
        }
        if ($GLOBALS['FORUM_DB']->query_select_value('f_forums', 'COUNT(*)') > 300) {
            $fields->attach(form_input_integer(do_lang_tempcode('ORDER'), do_lang_tempcode('DESCRIPTION_FORUM_ORDER'), 'position', $position, true));
        } else {
            $hidden->attach(form_input_hidden('position', strval($position)));
        }

        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'cb47ed06695dc2cd99211772fe4c5643', 'SECTION_HIDDEN' => $post_count_increment == 1 && $order_sub_alpha == 0 && ($intro_question == '') && ($intro_answer == '') && ($redirection == '') && ($order == 'last_post'), 'TITLE' => do_lang_tempcode('ADVANCED'))));
        $fields->attach(form_input_tick(do_lang_tempcode('POST_COUNT_INCREMENT'), do_lang_tempcode('DESCRIPTION_POST_COUNT_INCREMENT'), 'post_count_increment', $post_count_increment == 1));
        $fields->attach(form_input_tick(do_lang_tempcode('ORDER_SUB_ALPHA'), do_lang_tempcode('DESCRIPTION_ORDER_SUB_ALPHA'), 'order_sub_alpha', $order_sub_alpha == 1));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('INTRO_QUESTION'), do_lang_tempcode('DESCRIPTION_INTRO_QUESTION'), 'intro_question', $intro_question, false));
        $fields->attach(form_input_line(do_lang_tempcode('INTRO_ANSWER'), do_lang_tempcode('DESCRIPTION_INTRO_ANSWER'), 'intro_answer', $intro_answer, false));
        $fields->attach(form_input_line(do_lang_tempcode('REDIRECTING'), do_lang_tempcode('DESCRIPTION_FORUM_REDIRECTION'), 'redirection', $redirection, false));
        $list = new Tempcode();
        $list->attach(form_input_list_entry('last_post', $order == 'last_post', do_lang_tempcode('FORUM_ORDER_BY_LAST_POST')));
        $list->attach(form_input_list_entry('first_post', $order == 'first_post', do_lang_tempcode('FORUM_ORDER_BY_FIRST_POST')));
        $list->attach(form_input_list_entry('title', $order == 'title', do_lang_tempcode('FORUM_ORDER_BY_TITLE')));
        $fields->attach(form_input_list(do_lang_tempcode('TOPIC_ORDER'), do_lang_tempcode('DESCRIPTION_TOPIC_ORDER'), 'order', $list));
        $fields->attach(form_input_tick(do_lang_tempcode('IS_THREADED'), do_lang_tempcode('DESCRIPTION_IS_THREADED'), 'is_threaded', $is_threaded == 1));

        $fields->attach(meta_data_get_fields('forum', is_null($id) ? null : strval($id)));

        if (addon_installed('content_reviews')) {
            $fields->attach(content_review_get_fields('forum', is_null($id) ? null : strval($id)));
        }

        // Permissions
        $fields->attach($this->get_permission_fields(is_null($id) ? null : strval($id), null, is_null($id)));

        return array($fields, $hidden);
    }

    /**
     * Get a UI to choose a forum to edit.
     *
     * @param  AUTO_LINK                The ID of the forum we are generating the tree below (start recursion with db_get_first_id())
     * @param  SHORT_TEXT               The name of the forum $id
     * @param  array                    A list of rows of all forums, or array() if the function is to get the list itself
     * @param  integer                  The relative position of this forum wrt the others on the same level/branch in the UI
     * @param  integer                  The number of forums in the parent forum grouping
     * @param  ?BINARY                  Whether to order own subcategories alphabetically (null: ask the DB)
     * @param  ?BINARY                  Whether to order subcategories alphabetically (null: ask the DB)
     * @param  boolean                  Whether we are dealing with a huge forum structure
     * @return tempcode                 The UI
     */
    public function get_forum_tree($id, $forum, &$all_forums, $position = 0, $sub_num_in_parent_forum_grouping = 1, $order_sub_alpha = null, $parent_order_sub_alpha = null, $huge = false)
    {
        $forum_groupings = new Tempcode();

        if ($huge) {
            $all_forums = $GLOBALS['FORUM_DB']->query_select('f_forums', array('id', 'f_name', 'f_position', 'f_forum_grouping_id', 'f_order_sub_alpha', 'f_parent_forum'), array('f_parent_forum' => $id), 'ORDER BY f_parent_forum,f_position', 300);
            if (count($all_forums) == 300) {
                return paragraph(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));
            }
        } else {
            if (count($all_forums) == 0) {
                $all_forums = $GLOBALS['FORUM_DB']->query_select('f_forums', array('id', 'f_name', 'f_position', 'f_forum_grouping_id', 'f_order_sub_alpha', 'f_parent_forum'), null, 'ORDER BY f_parent_forum,f_position');
            }
        }

        if (is_null($order_sub_alpha)) {
            $parent_order_sub_alpha = 0;
            $order_sub_alpha = $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'f_order_sub_alpha', array('id' => $id));
        }

        global $C_TITLE;
        if (is_null($C_TITLE)) {
            $C_TITLE = collapse_2d_complexity('id', 'c_title', $GLOBALS['FORUM_DB']->query_select('f_forum_groupings', array('id', 'c_title')));
        }

        $_forum_groupings = array();
        foreach ($all_forums as $_forum) {
            if ($_forum['f_parent_forum'] == $id) {
                $_forum_groupings[$_forum['f_forum_grouping_id']] = 1;
            }
        }
        $num_forum_groupings = count($_forum_groupings);

        $order = ($order_sub_alpha == 1) ? 'f_name' : 'f_position';
        $subforums = array();
        foreach ($all_forums as $_forum) {
            if ($_forum['f_parent_forum'] == $id) {
                $subforums[$_forum['id']] = $_forum;
            }
        }
        if ($order == 'f_name') {
            sort_maps_by($subforums, 'f_name');
        }
        $forum_grouping_id = mixed();
        $position_in_cat = 0;
        $forum_grouping_position = 0;
        $forums = null;
        $orderings = '';
        while (count($subforums) != 0) {
            $i = null;
            if (!is_null($forum_grouping_id)) {
                foreach ($subforums as $j => $subforum) {
                    if ($subforum['f_forum_grouping_id'] == $forum_grouping_id) {
                        $i = $j;
                        break;
                    }
                }
            }

            if (is_null($i)) {
                if (!is_null($forums)) {
                    $forum_groupings->attach(do_template('OCF_EDIT_FORUM_SCREEN_GROUPING', array('_GUID' => '889173769e237b917b7e06eda0fb4350', 'ORDERINGS' => $orderings, 'GROUPING' => $C_TITLE[$forum_grouping_id], 'SUBFORUMS' => $forums)));
                    $forum_grouping_position++;
                }
                $forums = new Tempcode();
                $i = 0;
                foreach ($subforums as $j => $subforum) {
                    $i = $j;
                    break;
                }
                $forum_grouping_id = $subforums[$i]['f_forum_grouping_id'];
                $position_in_cat = 0;
                $sub_num_in_forum_grouping = 0;
                foreach ($subforums as $subforum) {
                    if ($subforum['f_forum_grouping_id'] == $forum_grouping_id) {
                        $sub_num_in_forum_grouping++;
                    }
                }
            }

            $subforum = $subforums[$i];

            $orderings = '';
            if (($order_sub_alpha == 0) && (!$huge)) {
                for ($_i = 0; $_i < $num_forum_groupings; $_i++) {
                    $orderings .= '<option ' . (($_i == $forum_grouping_position) ? 'selected="selected"' : '') . '>' . strval($_i + 1) . '</option>';
                }
                $orderings = '<label for="forum_grouping_order_' . strval($id) . '_' . strval($forum_grouping_id) . '">' . do_lang('ORDER') . '<span class="accessibility_hidden"> (' . (array_key_exists($forum_grouping_id, $C_TITLE) ? escape_html($C_TITLE[$forum_grouping_id]) : '') . ')</span> <select id="forum_grouping_order_' . strval($id) . '_' . strval($forum_grouping_id) . '" name="forum_grouping_order_' . strval($id) . '_' . strval($forum_grouping_id) . '">' . $orderings . '</select></label>'; // XHTMLXHTML
            }

            $forums->attach($this->get_forum_tree($subforum['id'], $subforum['f_name'], $all_forums, $position_in_cat, $sub_num_in_forum_grouping, $subforum['f_order_sub_alpha'], $order_sub_alpha, $huge));

            $position_in_cat++;
            unset($subforums[$i]);
        }
        if (!is_null($forum_grouping_id)) {
            $forum_groupings->attach(do_template('OCF_EDIT_FORUM_SCREEN_GROUPING', array('_GUID' => '6cb30ec5189f75a9631b2bb430c89fd0', 'ORDERINGS' => $orderings, 'GROUPING' => $C_TITLE[$forum_grouping_id], 'SUBFORUMS' => $forums)));
        }

        $edit_url = build_url(array('page' => '_SELF', 'type' => '_ed', 'id' => $id), '_SELF');
        $view_map = array('page' => 'forumview');
        if ($id != db_get_first_id()) {
            $view_map['id'] = $id;
        }
        $view_url = build_url($view_map, get_module_zone('forumview'));

        $class = (!has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(), 'forums', strval($id))) ? 'access_restricted_in_list' : '';

        $orderings = '';
        if ($parent_order_sub_alpha == 0) {
            for ($i = 0; $i < $sub_num_in_parent_forum_grouping; $i++) {
                $orderings .= '<option ' . (($i == $position) ? 'selected="selected"' : '') . '>' . strval($i + 1) . '</option>';
            }
            $orderings = '<label for="order_' . strval($id) . '">' . do_lang('ORDER') . '<span class="accessibility_hidden"> (' . escape_html($forum) . ')</span> <select id="order_' . strval($id) . '" name="order_' . strval($id) . '">' . $orderings . '</select></label>';
        }

        if ($GLOBALS['XSS_DETECT']) {
            ocp_mark_as_escaped($orderings);
        }

        return do_template('OCF_EDIT_FORUM_SCREEN_FORUM', array('_GUID' => '35fdeb9848919b5c30b069eb5df603d5', 'ID' => strval($id), 'ORDERINGS' => $orderings, 'FORUM_GROUPINGS' => $forum_groupings, 'CLASS' => $class, 'FORUM' => $forum, 'VIEW_URL' => $view_url, 'EDIT_URL' => $edit_url));
    }

    /**
     * The UI to choose a forum to edit (relies on get_forum_tree to do almost all the work).
     *
     * @return tempcode                 The UI
     */
    public function ed()
    {
        $huge = ($GLOBALS['FORUM_DB']->query_select_value('f_forums', 'COUNT(*)') > 300);

        $all_forums = array();
        $forums = $this->get_forum_tree(db_get_first_id(), $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'f_name', array('id' => db_get_first_id())), $all_forums, 0, 1, null, null, $huge);

        if ($huge) {
            $reorder_url = new Tempcode();
        } else {
            $reorder_url = build_url(array('page' => '_SELF', 'type' => 'reorder'), '_SELF');
        }

        return do_template('OCF_EDIT_FORUM_SCREEN', array('_GUID' => '762810dcff9acfa51995984d2c008fef', 'REORDER_URL' => $reorder_url, 'TITLE' => $this->title, 'ROOT_FORUM' => $forums));
    }

    /**
     * The actualiser to reorder forums.
     *
     * @return tempcode                 The UI
     */
    public function reorder()
    {
        $all = $GLOBALS['FORUM_DB']->query_select('f_forums', array('id', 'f_parent_forum', 'f_forum_grouping_id'));
        $ordering = array();
        foreach ($all as $forum) {
            $cat_order = post_param_integer('forum_grouping_order_' . strval($forum['f_parent_forum']) . '_' . strval($forum['f_forum_grouping_id']), -1);
            $order = post_param_integer('order_' . strval($forum['id']), -1);
            if (($cat_order != -1) && ($order != -1)) { // Should only be -1 if since deleted
                if (!array_key_exists($forum['f_parent_forum'], $ordering)) {
                    $ordering[$forum['f_parent_forum']] = array();
                }
                if (!array_key_exists($cat_order, $ordering[$forum['f_parent_forum']])) {
                    $ordering[$forum['f_parent_forum']][$cat_order] = array();
                }
                while (array_key_exists($order, $ordering[$forum['f_parent_forum']][$cat_order])) {
                    $order++;
                }

                $ordering[$forum['f_parent_forum']][$cat_order][$order] = $forum['id'];
            }
        }

        foreach ($ordering as $_ordering) {
            ksort($_ordering);
            $order = 0;
            foreach ($_ordering as $forums) {
                ksort($forums);
                foreach ($forums as $forum_id) {
                    $GLOBALS['FORUM_DB']->query_update('f_forums', array('f_position' => $order), array('id' => $forum_id), '', 1);
                    $order++;
                }
            }
        }

        $url = build_url(array('page' => '_SELF', 'type' => 'ed'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }

    /**
     * Standard crud_module delete possibility checker.
     *
     * @param  ID_TEXT                  The entry being potentially deleted
     * @return boolean                  Whether it may be deleted
     */
    public function may_delete_this($_id)
    {
        $id = intval($_id);

        if ($id == db_get_first_id()) {
            return false;
        }

        $fname = $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'f_name', array('id' => $id));

        $hooks = find_all_hooks('systems', 'config');
        foreach (array_keys($hooks) as $hook) {
            $value = get_option($hook, true);
            if (($value === $fname) || ($value === $_id)) {
                require_code('hooks/systems/config/' . filter_naughty($hook));
                $ob = object_factory('Hook_config_' . $hook);

                $option = $ob->get_details();
                if ($option['the_type'] == 'forum') {
                    if ((is_null($GLOBALS['CURRENT_SHARE_USER'])) || ($option['shared_hosting_restricted'] == 0)) {
                        require_code('config2');
                        require_all_lang();
                        $edit_url = config_option_url($hook);
                        $message = do_lang_tempcode(
                            'CANNOT_DELETE_FORUM_OPTION',
                            escape_html($edit_url),
                            escape_html(do_lang_tempcode($option['human_name']))
                        );
                        attach_message($message, 'notice');
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Standard crud_module edit form filler.
     *
     * @param  ID_TEXT                  The entry being edited
     * @return array                    A tuple: fields, hidden-fields, delete-fields, N/A, N/A, N/A, action fields
     */
    public function fill_in_edit_form($id)
    {
        $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('group_privileges p JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_groups g ON g.id=group_id', 'g.id', array('module_the_name' => 'forums', 'category_name' => $id, 'the_value' => '1', 'g_is_private_club' => 1));
        if (!is_null($test)) {
            attach_message(do_lang_tempcode('THIS_CLUB_FORUM'), 'notice');
        }

        $m = $GLOBALS['FORUM_DB']->query_select('f_forums', array('*'), array('id' => intval($id)), '', 1);
        if (!array_key_exists(0, $m)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $r = $m[0];

        $fields = $this->get_form_fields($r['id'], $r['f_name'], get_translated_text($r['f_description'], $GLOBALS['FORUM_DB']), $r['f_forum_grouping_id'], $r['f_parent_forum'], $r['f_position'], $r['f_post_count_increment'], $r['f_order_sub_alpha'], get_translated_text($r['f_intro_question'], $GLOBALS['FORUM_DB']), $r['f_intro_answer'], $r['f_redirection'], $r['f_order'], $r['f_is_threaded']);

        $delete_fields = new Tempcode();
        if (intval($id) != db_get_first_id()) {
            $delete_fields->attach(form_input_tree_list(do_lang_tempcode('TARGET'), do_lang_tempcode('DESCRIPTION_TOPIC_MOVE_TARGET'), 'target_forum', null, 'choose_forum', array(), true, $id));
            $delete_fields->attach(form_input_tick(do_lang_tempcode('DELETE_TOPICS'), do_lang_tempcode('DESCRIPTION_DELETE_TOPICS'), 'delete_topics', false));
        }

        $action_fields = new Tempcode();
        $action_fields->attach(form_input_tick(do_lang_tempcode('RESET_INTRO_ACCEPTANCE'), do_lang_tempcode('DESCRIPTION_RESET_INTRO_ACCEPTANCE'), 'reset_intro_acceptance', false));

        return array($fields[0], $fields[1], $delete_fields, null, false, null, $action_fields);
    }

    /**
     * Standard crud_module add actualiser.
     *
     * @return ID_TEXT                  The entry added
     */
    public function add_actualisation()
    {
        require_code('ocf_forums_action2');

        $parent_forum = post_param_integer('parent_forum', -1);
        $name = post_param('name');

        $meta_data = actual_meta_data_get_fields('forum', null);

        $id = strval(ocf_make_forum($name, post_param('description'), post_param_integer('forum_grouping_id'), null, $parent_forum, post_param_integer('position'), post_param_integer('post_count_increment', 0), post_param_integer('order_sub_alpha', 0), post_param('intro_question'), post_param('intro_answer'), post_param('redirection'), post_param('order'), post_param_integer('is_threaded', 0)));

        // Warning if there is full access to this forum, but not to the parent
        $admin_groups = $GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
        $groups = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(true, true);
        $full_access = true;
        foreach (array_keys($groups) as $gid) {
            if (!in_array($gid, $admin_groups)) {
                if (post_param_integer('access_' . strval($gid), 0) == 0) {
                    $full_access = false;
                    break;
                }
            }
        }
        if ($full_access) {
            $parent_has_full_access = true;
            $access_rows = $GLOBALS['FORUM_DB']->query_select('group_category_access', array('group_id'), array('module_the_name' => 'forums', 'category_name' => strval($parent_forum)));
            $access = array();
            foreach ($access_rows as $row) {
                $access[$row['group_id']] = 1;
            }
            foreach (array_keys($groups) as $gid) {
                if (!in_array($gid, $admin_groups)) {
                    if (!array_key_exists($gid, $access)) {
                        $parent_has_full_access = false;
                        break;
                    }
                }
            }
            if (!$parent_has_full_access) {
                attach_message(do_lang_tempcode('ANOMALOUS_FORUM_ACCESS'), 'notice');
            }
        }

        $this->set_permissions($id);

        if (addon_installed('content_reviews')) {
            content_review_set('forum', $id);
        }

        if ((has_actual_page_access(get_modal_user(), 'forumview')) && (has_category_access(get_modal_user(), 'forums', $id))) {
            require_code('activities');
            syndicate_described_activity('ocf:ACTIVITY_ADD_FORUM', $name, '', '', '_SEARCH:forumview:misc:' . $id, '', '', 'ocf_forum');
        }

        return $id;
    }

    /**
     * Standard crud_module edit actualiser.
     *
     * @param  ID_TEXT                  The entry being edited
     */
    public function edit_actualisation($id)
    {
        $meta_data = actual_meta_data_get_fields('forum', $id);

        ocf_edit_forum(intval($id), post_param('name'), post_param('description', STRING_MAGIC_NULL), post_param_integer('forum_grouping_id', INTEGER_MAGIC_NULL), post_param_integer('parent_forum', INTEGER_MAGIC_NULL), post_param_integer('position', INTEGER_MAGIC_NULL), post_param_integer('post_count_increment', fractional_edit() ? INTEGER_MAGIC_NULL : 0), post_param_integer('order_sub_alpha', fractional_edit() ? INTEGER_MAGIC_NULL : 0), post_param('intro_question', STRING_MAGIC_NULL), post_param('intro_answer', STRING_MAGIC_NULL), post_param('redirection', STRING_MAGIC_NULL), post_param('order', STRING_MAGIC_NULL), post_param_integer('is_threaded', fractional_edit() ? INTEGER_MAGIC_NULL : 0), post_param_integer('reset_intro_acceptance', 0) == 1);

        if (!fractional_edit()) {
            require_code('ocf_groups2');

            $old_access_mapping = collapse_1d_complexity('group_id', $GLOBALS['FORUM_DB']->query_select('group_category_access', array('group_id'), array('module_the_name' => 'forums', 'category_name' => $id)));

            require_code('ocf_groups_action');
            require_code('ocf_groups_action2');

            $lost_groups = array();
            foreach ($old_access_mapping as $group_id) {
                if (post_param_integer('access_' . strval($group_id), 0) == 0) {// Lost access
                    $lost_groups[] = $group_id;
                }
            }

            $this->set_permissions($id);

            if (addon_installed('content_reviews')) {
                content_review_set('forum', $id);
            }
        }
    }

    /**
     * Standard crud_module delete actualiser.
     *
     * @param  ID_TEXT                  The entry being deleted
     */
    public function delete_actualisation($id)
    {
        ocf_delete_forum(intval($id), post_param_integer('target_forum'), post_param_integer('delete_topics', 0));
    }
}
