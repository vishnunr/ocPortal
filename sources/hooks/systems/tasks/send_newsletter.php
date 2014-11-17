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
 * @package    newsletter
 */

/**
 * Hook class.
 */
class Hook_task_send_newsletter
{
    /**
     * Run the task hook.
     *
     * @param  LONG_TEXT                The newsletter message
     * @param  SHORT_TEXT               The newsletter subject
     * @param  LANGUAGE_NAME            The language
     * @param  array                    A map describing what newsletters and newsletter levels the newsletter is being sent to
     * @param  BINARY                   Whether to only send in HTML format
     * @param  string                   Override the email address the mail is sent from (blank: staff address)
     * @param  string                   Override the name the mail is sent from (blank: site name)
     * @param  integer                  The message priority (1=urgent, 3=normal, 5=low)
     * @range  1 5
     * @param  string                   CSV data of extra subscribers (blank: none). This is in the same ocPortal newsletter CSV format that we export elsewhere.
     * @param  ID_TEXT                  The template used to show the email
     * @return ?array                   A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run($message, $subject, $lang, $send_details, $html_only, $from_email, $from_name, $priority, $csv_data, $mail_template)
    {
        require_code('newsletter');
        require_code('mail');

        //mail_wrap($subject,$message,$addresses,$usernames,$from_email,$from_name,3,null,true,null,true,$html_only==1);  Not so easy any more as message needs tailoring per subscriber

        $last_cron = get_value('last_cron');

        // These variables are for optimisation, we detect if we can avoid work on the loop iterations via looking at what happened on the first
        $needs_substitutions = mixed();
        $needs_tempcode = mixed();

        $blocked = newsletter_block_list();

        $start = 0;
        do {
            list($addresses, $hashes, $usernames, $forenames, $surnames, $ids,) = newsletter_who_send_to($send_details, $lang, $start, 100, false, $csv_data);

            // Send to all
            foreach ($addresses as $i => $email_address) {
                if (isset($blocked[$email_address])) {
                    continue;
                }

                // Variable substitution in body
                if ($needs_substitutions === null || $needs_substitutions) {
                    $newsletter_message_substituted = (strpos($message, '{') === false) ? $message : newsletter_variable_substitution($message, $subject, $forenames[$i], $surnames[$i], $usernames[$i], $email_address, $ids[$i], $hashes[$i]);

                    if ($needs_substitutions === null) {
                        $needs_substitutions = ($newsletter_message_substituted != $message);
                    }
                } else {
                    $newsletter_message_substituted = $message;
                }
                $in_html = false;
                if (strpos($newsletter_message_substituted, '<html') === false) {
                    if ($html_only == 1) {
                        $_m = comcode_to_tempcode($newsletter_message_substituted, get_member(), true);
                        $newsletter_message_substituted = $_m->evaluate($lang);
                        $in_html = true;
                    }
                } else {
                    if ($needs_tempcode === null || $needs_tempcode) {
                        require_code('tempcode_compiler');
                        $_m = template_to_tempcode($newsletter_message_substituted);
                        $temp = $_m->evaluate($lang);

                        if ($needs_tempcode === null) {
                            $needs_tempcode = (trim($temp) != trim($newsletter_message_substituted));
                        }

                        $newsletter_message_substituted = $temp;
                    }
                    $in_html = true;
                }

                if (!is_null($last_cron)) {
                    $GLOBALS['SITE_DB']->query_insert('newsletter_drip_send', array(
                        'd_inject_time' => time(),
                        'd_subject' => $subject,
                        'd_message' => $newsletter_message_substituted,
                        'd_html_only' => $html_only,
                        'd_to_email' => $email_address,
                        'd_to_name' => $usernames[$i],
                        'd_from_email' => $from_email,
                        'd_from_name' => $from_name,
                        'd_priority' => $priority,
                        'd_template' => $mail_template,
                    ));
                } else {
                    mail_wrap($subject, $newsletter_message_substituted, array($email_address), array($usernames[$i]), $from_email, $from_name, $priority, null, true, null, true, $in_html, false, $mail_template);
                }

                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles(); // Stop problem with PHP leaking memory
                }
            }
            $start += 100;
        }
        while (array_key_exists(0, $addresses));

        return array('text/html', do_lang_tempcode('SENDING_NEWSLETTER'));
    }
}
