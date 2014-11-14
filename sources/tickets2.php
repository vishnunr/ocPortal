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
 * @package    tickets
 */

/**
 * Get ticket details.
 *
 * @param  string                       The ticket ID
 * @param  boolean                      Exit with an error message if it cannot find the ticket
 * @return ?array                       A tuple: The ticket title, the topic ID, the ticket type ID, the ticket owner (NULL: not found)
 */
function get_ticket_details($ticket_id, $hard_error = true)
{
    $forum = 1;
    $topic_id = 1;
    $_ticket_type_id = 1; // These will be returned by reference
    $_comments = get_ticket_posts($ticket_id, $forum, $topic_id, $_ticket_type_id, 0, 1);
    if ((!is_array($_comments)) || (!array_key_exists(0, $_comments))) {
        if ($hard_error) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }

        return null;
    }
    $ticket_title = $_comments[0]['title'];

    $_temp = explode('_', $ticket_id);
    $uid = intval($_temp[0]);

    return array($ticket_title, $topic_id, $forum, $uid);
}

/**
 * Add a ticket type.
 *
 * @param  SHORT_TEXT                   The ticket type name
 * @param  BINARY                       Whether guest e-mail addresses are mandatory for new tickets
 * @param  BINARY                       Whether the FAQ should be searched before submitting a new ticket
 * @return AUTO_LINK                    The ticket type ID
 */
function add_ticket_type($ticket_type_name, $guest_emails_mandatory = 0, $search_faq = 0)
{
    require_code('global4');
    prevent_double_submit('ADD_TICKET_TYPE', null, $ticket_type_name);

    $map = array(
        'guest_emails_mandatory' => $guest_emails_mandatory,
        'search_faq' => $search_faq,
        'cache_lead_time' => null,
    );
    $map += insert_lang('ticket_type_name', $ticket_type_name, 1);
    $ticket_type_id = $GLOBALS['SITE_DB']->query_insert('ticket_types', $map, true);

    log_it('ADD_TICKET_TYPE', strval($ticket_type_id), $ticket_type_name);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('ticket_type', strval($ticket_type_id), null, null, true);
    }

    return $ticket_type_id;
}

/**
 * Edit a ticket type, keeping the integer ID the same.
 *
 * @param  AUTO_LINK                    The ticket type ID
 * @param  ?SHORT_TEXT                  The new ticket type text (NULL: do not change)
 * @param  BINARY                       Whether guest e-mail addresses are mandatory for new tickets
 * @param  BINARY                       Whether the FAQ should be searched before submitting a new ticket
 */
function edit_ticket_type($ticket_type_id, $ticket_type_name, $guest_emails_mandatory, $search_faq)
{
    $old_ticket_type_name = $GLOBALS['SITE_DB']->query_select_value('ticket_types', 'ticket_type_name', array('id' => $ticket_type_id));

    $map = array(
        'guest_emails_mandatory' => $guest_emails_mandatory,
        'search_faq' => $search_faq,
    );
    $map += lang_remap('ticket_type_name', $old_ticket_type_name, $ticket_type_name);
    $GLOBALS['SITE_DB']->query_update('ticket_types', $map, array('id' => $ticket_type_id), '', 1);

    log_it('EDIT_TICKET_TYPE', strval($ticket_type_id), $ticket_type_name);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('ticket_type', strval($ticket_type_id), $ticket_type_name);
    }
}

/**
 * Delete a ticket type.
 *
 * @param  AUTO_LINK                    The ticket type ID
 */
function delete_ticket_type($ticket_type_id)
{
    $GLOBALS['SITE_DB']->query_delete('group_category_access', array('module_the_name' => 'tickets', 'category_name' => strval($ticket_type_id)));
    $GLOBALS['SITE_DB']->query_delete('group_privileges', array('module_the_name' => 'tickets', 'category_name' => strval($ticket_type_id)));

    $ticket_type_name = $GLOBALS['SITE_DB']->query_select_value('ticket_types', 'ticket_type_name', array('id' => $ticket_type_id));
    $_ticket_type_name = get_translated_text($ticket_type_name);

    $GLOBALS['SITE_DB']->query_delete('ticket_types', array('id' => $ticket_type_id), '', 1);

    delete_lang($ticket_type_name);

    log_it('DELETE_TICKET_TYPE', strval($ticket_type_id), $_ticket_type_name);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resourcefs_moniker('ticket_type', strval($ticket_type_id));
    }
}

/**
 * Get a map of properties for the given ticket type.
 *
 * @param  ?AUTO_LINK                   The ticket type (NULL: fallback for old tickets)
 * @return ?array                       Array of properties (NULL: ticket type not found)
 */
function get_ticket_type($ticket_type_id)
{
    if (is_null($ticket_type_id)) {
        return array('ticket_type' => null, 'guest_emails_mandatory' => false, 'search_faq' => false, 'cache_lead_time' => null);
    }

    $rows = $GLOBALS['SITE_DB']->query_select('ticket_types', null, array('id' => $ticket_type_id), '', 1);
    if (count($rows) == 0) {
        return null;
    }
    return $rows[0];
}

/**
 * Update the cache of ticket type lead times (average time taken for a response to tickets of that type) in the database.
 * This is a query-intensive function, so should only be run occasionally.
 */
function update_ticket_type_lead_times()
{
    require_code('feedback');

    $ticket_types = $GLOBALS['SITE_DB']->query_select('ticket_types', array('*'));
    foreach ($ticket_types as $ticket_type) {
        $total_lead_time = 0;
        $tickets_counted = 0;

        $tickets = $GLOBALS['SITE_DB']->query_select('tickets', null, array('ticket_type' => $ticket_type['id']));
        foreach ($tickets as $ticket) {
            $max_rows = 0;
            $topic = $GLOBALS['FORUM_DRIVER']->show_forum_topics($ticket['forum_id'], 1, 0, $max_rows, $ticket['ticket_id'], true, 'lasttime', false, do_lang('SUPPORT_TICKET') . ': #' . $ticket['ticket_id']);
            if (is_null($topic)) {
                continue;
            }
            $topic = $topic[0];

            // We need to have two posts for new-style tickets, or three for old-style tickets (with spacers)
            if (($topic['num'] < 2) || (($topic['firstusername'] == do_lang('SYSTEM')) && ($topic['num'] < 3))) {
                continue;
            }

            $ticket_id = extract_topic_identifier($topic['description']);
            $_forum = 1;
            $_topic_id = 1;
            $_ticket_type_id = 1; // These will be returned by reference
            $posts = get_ticket_posts($ticket_id, $_forum, $_topic_id, $_ticket_type_id);

            // Differentiate between old- and new-style tickets
            if ($topic['firstusername'] == do_lang('SYSTEM')) {
                $first_key = 1;
            } else {
                $first_key = 0;
            }

            // Find the first post by someone other than the ticket owner
            $i = $first_key + 1;
            while ((array_key_exists($i, $posts)) && ($posts[$i]['member'] != $posts[$first_key]['member'])) {
                $i++;
            }

            if (array_key_exists($i, $posts)) {
                $total_lead_time += $posts[$i]['date'] - $posts[$first_key]['date'];
                $tickets_counted++;
            }
        }

        /* Calculate the new lead time and store it in the DB */
        if ($tickets_counted > 0) {
            $GLOBALS['SITE_DB']->query_update('ticket_types', array('cache_lead_time' => $total_lead_time / $tickets_counted), array('id' => $ticket_type['id']), '', 1);
        }
    }
}

/**
 * Get an array of tickets for the given member and ticket type. If the member has permission to see others' tickets, it will be a list of all tickets
 * in the system, restricted by ticket type as appropriate. Otherwise, it will be a list of that member's tickets, as restricted by ticket type.
 *
 * @param  AUTO_LINK                    The member ID
 * @param  ?AUTO_LINK                   The ticket type (NULL: all ticket types)
 * @param  boolean                      Don't view others' tickets, even if the member has permission to
 * @param  boolean                      Whether to skip showing errors, returning NULL instead
 * @return array                        Array of tickets, empty on failure
 */
function get_tickets($member, $ticket_type_id = null, $override_view_others_tickets = false, $silent_error_handling = false)
{
    $restrict = '';
    $restrict_description = '';
    $view_others_tickets = (!$override_view_others_tickets) && (has_privilege($member, 'view_others_tickets'));

    if (!$view_others_tickets) {
        $restrict = strval($member) . '\_%';
        $restrict_description = do_lang('SUPPORT_TICKET') . ': #' . $restrict;
    } else {
        if ((!is_null($ticket_type_id)) && (!has_category_access(get_member(), 'tickets', strval($ticket_type_id)))) {
            return array();
        }
    }

    if (!is_null($ticket_type_id)) {
        $ticket_type_name = $GLOBALS['SITE_DB']->query_select_value('ticket_types', 'ticket_type_name', array('id' => $ticket_type_id));
    }

    if ((get_option('ticket_member_forums') == '1') || (get_option('ticket_type_forums') == '1')) {
        if (get_forum_type() == 'ocf') {
            $fid = ($view_others_tickets) ? get_ticket_forum_id(null, null, false, $silent_error_handling) : get_ticket_forum_id(get_member(), null, false, $silent_error_handling);
            if (is_null($fid)) {
                return array();
            }

            if (is_null($ticket_type_id)) {
                require_code('ocf_forums');
                $forums = ocf_get_all_subordinate_forums($fid, null, null, true);
            } else {
                $query = 'SELECT id FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE ' . db_string_equal_to('f_name', get_translated_text($ticket_type_name)) . ' AND ';
                if ($view_others_tickets) {
                    $query .= 'f_parent_forum IN (SELECT id FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE f_parent_forum=' . strval($fid) . ')';
                } else {
                    $query .= 'f_parent_forum=' . strval($fid);
                }

                $rows = $GLOBALS['FORUM_DB']->query($query, null, null, false, true);
                $forums = collapse_2d_complexity('id', 'id', $rows);
            }
        } else {
            $forums = array(get_ticket_forum_id($member, $ticket_type_id, false, $silent_error_handling));
        }
    } else {
        $forums = array(get_ticket_forum_id(null, null, false, $silent_error_handling));
    }

    if ((count($forums) == 1) && (array_key_exists(0, $forums)) && (is_null($forums[0]))) {
        return array();
    }
    $max_rows = 0;
    $topics = $GLOBALS['FORUM_DRIVER']->show_forum_topics(array_flip($forums), $view_others_tickets ? 100 : 500, 0, $max_rows, $restrict, true, 'lasttime', false, $restrict_description);
    if (is_null($topics)) {
        return array();
    }
    $filtered_topics = array();
    foreach ($topics as $topic) {
        $fp = $topic['firstpost'];
        unset($topic['firstpost']); // To stop Tempcode randomly making serialization sometimes change such that the refresh_if_changed is triggered
        if ((is_null($ticket_type_id)) || (strpos($fp->evaluate(), do_lang('TICKET_TYPE') . ': ' . get_translated_text($ticket_type_name)) !== false)) {
            $filtered_topics[] = $topic;
        }
    }
    return $filtered_topics;
}

/**
 * Get the posts from a given ticket, and also return the IDs of the forum and topic containing it. The return value is the same as
 * that of get_forum_topic_posts(), except in error conditions
 *
 * @param  string                       The ticket ID
 * @param  AUTO_LINK                    Return location for the forum ID
 * @param  AUTO_LINK                    Return location for the topic ID
 * @param  AUTO_LINK                    Return location for the ticket type
 * @param  integer                      Start offset in pagination
 * @param  ?integer                     Max per page in pagination (NULL: no limit)
 * @return mixed                        The array of maps (Each map is: title, message, member, date) (NULL: no such ticket)
 */
function get_ticket_posts($ticket_id, &$forum, &$topic_id, &$ticket_type, $start = 0, $max = null)
{
    $ticket = $GLOBALS['SITE_DB']->query_select('tickets', null, array('ticket_id' => $ticket_id), '', 1, null, true);
    if (count($ticket) == 1) { // We know about it, so grab details from tickets table
        $ticket_type_id = $ticket[0]['ticket_type'];

        if (has_privilege(get_member(), 'view_others_tickets')) {
            if (!has_category_access(get_member(), 'tickets', strval($ticket_type_id))) {
                access_denied('CATEGORY_ACCESS_LEVEL');
            }
        }

        $forum = $ticket[0]['forum_id'];
        $topic_id = $ticket[0]['topic_id'];
        $count = 0;
        return $GLOBALS['FORUM_DRIVER']->get_forum_topic_posts($GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum, $ticket_id), $count, $max, $start);
    }

    // It must be an old-style ticket, residing in the root ticket forum
    $forum = get_ticket_forum_id();
    $topic_id = $GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier(get_option('ticket_forum_name'), $ticket_id);
    $ticket_type_id = null;
    $count = 0;
    return $GLOBALS['FORUM_DRIVER']->get_forum_topic_posts($GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum, $ticket_id), $count);
}

/**
 * Remove a ticket from the database. This does not remove the associated forum topic.
 *
 * @param  AUTO_LINK                    The associated topic ID
 */
function delete_ticket_by_topic_id($topic_id)
{
    $GLOBALS['SITE_DB']->query_delete('tickets', array('topic_id' => $topic_id));
}

/**
 * Add a new post to a ticket, or create a new ticket if a ticket with the given ID doesn't exist.
 * It has the same return value as make_post_forum_topic().
 *
 * @param  AUTO_LINK                    The member ID
 * @param  string                       The ticket ID (doesn't have to exist)
 * @param  AUTO_LINK                    The ticket type
 * @param  LONG_TEXT                    The post title
 * @param  LONG_TEXT                    The post content in Comcode format
 * @param  string                       The home URL
 * @param  ?TIME                        The post time (NULL: use current time)
 * @param  boolean                      Whether the reply is staff only (invisible to ticket owner, only on OCF)
 */
function ticket_add_post($member, $ticket_id, $ticket_type_id, $title, $post, $ticket_url, $staff_only = false, $time_post = null)
{
    // Get the forum ID first
    $fid = $GLOBALS['SITE_DB']->query_select_value_if_there('tickets', 'forum_id', array('ticket_id' => $ticket_id));
    if (is_null($fid)) {
        $fid = get_ticket_forum_id($member, $ticket_type_id);
    }

    $GLOBALS['FORUM_DRIVER']->make_post_forum_topic(
        $fid,
        $ticket_id,
        $member,
        $title,
        $post,
        $title,
        do_lang('SUPPORT_TICKET'),
        $ticket_url,
        null,
        null,
        1,
        1,
        false,
        '',
        null,
        $staff_only,
        null,
        null,
        $time_post
    );
    $topic_id = $GLOBALS['LAST_TOPIC_ID'];
    $is_new = $GLOBALS['LAST_TOPIC_IS_NEW'];
    if (($is_new) && ($ticket_type_id != -1)) {
        $GLOBALS['SITE_DB']->query_insert('tickets', array('ticket_id' => $ticket_id, 'forum_id' => $fid, 'topic_id' => $topic_id, 'ticket_type' => $ticket_type_id));
    }
}

/**
 * Send an e-mail notification for a new post in a support ticket, either to the staff or to the ticket's owner.
 *
 * @param  string                       The ticket ID
 * @param  LONG_TEXT                    The ticket title
 * @param  LONG_TEXT                    The ticket post's content
 * @param  mixed                        The home URL (to view the ticket) (URLPATH or Tempcode URL)
 * @param  EMAIL                        Ticket owner's e-mail address, in the case of a new ticket
 * @param  integer                      The new ticket type, or -1 if it is a reply to an existing ticket
 * @param  ?MEMBER                      Posting member (NULL: current member)
 * @param  boolean                      Whether the ticket was auto-created
 */
function send_ticket_email($ticket_id, $title, $post, $ticket_url, $uid_email, $ticket_type_id_if_new, $new_poster = null, $auto_created = false)
{
    if (is_null($new_poster)) {
        $new_poster = get_member();
    }

    require_lang('tickets');
    require_code('notifications');

    // Lookup user details
    $_temp = explode('_', $ticket_id);
    $uid = intval($_temp[0]);
    $uid_displayname = $GLOBALS['FORUM_DRIVER']->get_username($uid, true);
    if (is_null($uid_displayname)) {
        $uid_displayname = do_lang('UNKNOWN');
    }
    $uid_username = $GLOBALS['FORUM_DRIVER']->get_username($uid);
    if (is_null($uid_username)) {
        $uid_username = do_lang('UNKNOWN');
    }

    // Clarify some details about this ticket
    if ($title == '') {
        $title = do_lang('UNKNOWN');
    }
    $new_ticket = ($ticket_type_id_if_new != -1);

    // Lookup ticket type details
    if ($new_ticket) {
        $ticket_type_id = $ticket_type_id_if_new;
    } else {
        $ticket_type_id = $GLOBALS['SITE_DB']->query_select_value_if_there('tickets', 'ticket_type', array('ticket_id' => $ticket_id));
    }
    $_ticket_type_name = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_types', 'ticket_type_name', array('id' => $ticket_type_id));
    if (is_null($_ticket_type_name)) {
        $ticket_type_name = do_lang('UNKNOWN');
    } else {
        $ticket_type_name = get_translated_text($_ticket_type_name);
    }

    if ($uid != $new_poster) {
        // Reply from staff, notification to member
        $post_tempcode = comcode_to_tempcode($post);
        if (trim($post_tempcode->evaluate()) != '') {
            $staff_displayname = $GLOBALS['FORUM_DRIVER']->get_username($new_poster, true);
            $staff_username = $GLOBALS['FORUM_DRIVER']->get_username($new_poster);

            if ((get_option('ticket_mail_on') == '1') && (cron_installed()) && (function_exists('imap_open'))) {
                require_code('tickets_email_integration');
                if ($uid_email == '') {
                    $uid_email = $GLOBALS['FORUM_DRIVER']->get_member_email_address($uid);
                }
                ticket_outgoing_message($ticket_id, $ticket_url, $ticket_type_name, $title, $post, $uid_displayname, $uid_email, $staff_displayname);
            } elseif (!is_guest($uid)) {
                $uid_lang = get_lang($uid);

                $subject = do_lang(
                    'TICKET_REPLY',
                    $ticket_type_name,
                    $ticket_type_name,
                    $title,
                    $uid_lang
                );

                $message = do_lang(
                    'TICKET_REPLY_MESSAGE',
                    comcode_escape($title),
                    comcode_escape($ticket_url),
                    array(
                        comcode_escape($staff_displayname),
                        $post,
                        comcode_escape($ticket_type_name),
                        strval($new_poster),
                        comcode_escape($staff_username)
                    ),
                    $uid_lang
                );

                dispatch_notification(
                    'ticket_reply',
                    is_null($ticket_type_id) ? '' : strval($ticket_type_id),
                    $subject,
                    $message,
                    array($uid)
                );
            }
        }
    } else {
        // Reply from member, notification to staff
        if (is_object($ticket_url)) {
            $ticket_url = $ticket_url->evaluate();
        }

        $subject = do_lang(
            $new_ticket ? 'TICKET_NEW_STAFF' : 'TICKET_REPLY_STAFF',
            $ticket_type_name,
            $title,
            null,
            get_site_default_lang()
        );

        $message = do_lang(
            $new_ticket ? 'TICKET_NEW_MESSAGE_FOR_STAFF' : 'TICKET_REPLY_MESSAGE_FOR_STAFF',
            comcode_escape($title),
            comcode_escape($ticket_url),
            array(
                comcode_escape($uid_displayname),
                $post,
                comcode_escape($ticket_type_name),
                strval($new_poster),
                comcode_escape($uid_username)
            ),
            get_site_default_lang()
        );

        dispatch_notification(
            $new_ticket ? 'ticket_new_staff' : 'ticket_reply_staff',
            strval($ticket_type_id),
            $subject,
            $message
        );

        // ALSO: Tell member that their message was received
        if ($uid_email != '') {
            if ((get_option('ticket_mail_on') == '1') && (cron_installed()) && (function_exists('imap_open')) && ($new_ticket) && ($auto_created)) {
                require_code('tickets_email_integration');
                ticket_outgoing_message($ticket_id, $ticket_url, $ticket_type_name, $title, $post, $uid_displayname, $uid_email, '', true);
            } else {
                require_code('mail');
                mail_wrap(do_lang('YOUR_MESSAGE_WAS_SENT_SUBJECT', $title), do_lang('YOUR_MESSAGE_WAS_SENT_BODY', $post), array($uid_email), null, '', '', 3, null, false, $new_poster);
            }
        }
    }

    // Notification to any staff monitoring, in general

    if (!$new_ticket) {
        $subject = do_lang(
            'TICKET_ACTIVITY_SUBJECT',
            $title,
            $GLOBALS['FORUM_DRIVER']->get_username($new_poster, true),
            $GLOBALS['FORUM_DRIVER']->get_username($new_poster),
            get_site_default_lang()
        );

        $message = do_lang(
            'TICKET_ACTIVITY_BODY',
            comcode_escape($title),
            comcode_escape($ticket_url),
            array(
                $post,
                comcode_escape($GLOBALS['FORUM_DRIVER']->get_username($new_poster, true)),
                comcode_escape($GLOBALS['FORUM_DRIVER']->get_username($new_poster))
            ),
            get_site_default_lang()
        );

        dispatch_notification(
            'ticket_assigned_staff',
            $ticket_id,
            $subject,
            $message
        );
    }
}

/**
 * Is the given ticket post intended for staff only? Works only on OCF.
 *
 * @param  array                        Array of data for the post
 * @return boolean                      Whether the post's staff only
 */
function is_ticket_post_staff_only($post)
{
    if (get_forum_type() != 'ocf') {
        return false;
    }
    return ((!is_null($post['p_intended_solely_for'])) && (is_guest($post['p_intended_solely_for'])));
}
