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
 * @package    core_notifications
 */

/**
 * Hook class.
 */
class Hook_task_dispatch_notification
{
    /**
     * Run the task hook.
     *
     * @param  object                   The notification dispatch object (pre-filled with details of the notification to send)
     * @return ?array                   A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run($dispatcher)
    {
        require_code('notifications');
        require_lang('notifications');

        $dispatcher->dispatch();

        return null;
    }
}
