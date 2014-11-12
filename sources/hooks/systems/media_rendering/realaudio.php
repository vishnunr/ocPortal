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
 * @package    core_rich_media
 */

/**
 * Hook class.
 */
class Hook_media_rendering_realaudio extends Media_renderer_with_fallback
{
    /**
     * Get the label for this media rendering type.
     *
     * @return string                   The label
     */
    public function get_type_label()
    {
        require_lang('comcode');
        return do_lang('MEDIA_TYPE_' . preg_replace('#^Hook_media_rendering_#', '', __CLASS__));
    }

    /**
     * Find the media types this hook serves.
     *
     * @return integer                  The media type(s), as a bitmask
     */
    public function get_media_type()
    {
        return MEDIA_TYPE_AUDIO;
    }

    /**
     * See if we can recognise this mime type.
     *
     * @param  ID_TEXT                  The mime type
     * @return integer                  Recognition precedence
     */
    public function recognises_mime_type($mime_type)
    {
        if ($mime_type == 'audio/x-pn-realaudio-plugin') {
            return MEDIA_RECOG_PRECEDENCE_HIGH;
        }
        return MEDIA_RECOG_PRECEDENCE_NONE;
    }

    /**
     * See if we can recognise this URL pattern.
     *
     * @param  URLPATH                  URL to pattern match
     * @return integer                  Recognition precedence
     */
    public function recognises_url($url)
    {
        if (substr($url, 0, 7) == 'rtsp://') {
            return MEDIA_RECOG_PRECEDENCE_MEDIUM;
        }
        return MEDIA_RECOG_PRECEDENCE_NONE;
    }

    /**
     * Provide code to display what is at the URL, in the most appropriate way.
     *
     * @param  mixed                    URL to render
     * @param  mixed                    URL to render (no sessions etc)
     * @param  array                    Attributes (e.g. width, height, length)
     * @param  boolean                  Whether there are admin privileges, to render dangerous media types
     * @param  ?MEMBER                  Member to run as (NULL: current member)
     * @return tempcode                 Rendered version
     */
    public function render($url, $url_safe, $attributes, $as_admin = false, $source_member = null)
    {
        $ret = $this->fallback_render($url, $url_safe, $attributes, $as_admin, $source_member, $url);
        if ($ret !== null) {
            return $ret;
        }

        return do_template('MEDIA_REALMEDIA', array('_GUID' => 'ae662cde8913c0982ced8ec903b052d9', 'HOOK' => 'realaudio') + _create_media_template_parameters($url, $attributes, $as_admin, $source_member));
    }
}
