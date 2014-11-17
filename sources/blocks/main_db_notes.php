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
 * @package    core
 */

/**
 * Block class.
 */
class Block_main_db_notes
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (null: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array('param', 'title', 'scrolls');
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        $file = array_key_exists('param', $map) ? $map['param'] : 'admin_notes';
        $title = array_key_exists('title', $map) ? $map['title'] : do_lang('NOTES');
        $scrolls = array_key_exists('scrolls', $map) ? $map['scrolls'] : '0';

        $new = post_param('new', null);
        if (!is_null($new)) {
            set_long_value('note_text_' . $file, $new);
            log_it('NOTES', $file);

            attach_message(do_lang_tempcode('SUCCESS'), 'inform');
        }

        $contents = get_long_value('note_text_' . $file);
        if (is_null($contents)) {
            $contents = '';
        }

        $post_url = get_self_url();

        $map_comcode = '';
        foreach ($map as $key => $val) {
            $map_comcode .= ' ' . $key . '="' . addslashes($val) . '"';
        }
        return do_template('BLOCK_MAIN_NOTES', array(
            '_GUID' => '2a9e1c512b66600583735552b56e0911',
            'TITLE' => $title,
            'BLOCK_NAME' => 'main_db_notes',
            'MAP' => $map_comcode,
            'SCROLLS' => array_key_exists('scrolls', $map) && ($map['scrolls'] == '1'),
            'CONTENTS' => $contents,
            'URL' => $post_url,
        ));
    }
}
