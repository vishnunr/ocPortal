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
 * Get the contents of an HTML page.
 * HTML isn't great... no dynamicness/reconfigurability at all.
 * We prefer Comcode with [html]HTML goes here[/html] usage
 *
 * @param  PATH                         The relative (to ocPortals base directory) path to the HTML page
 * @param  ?PATH                        The file base to load from (null: standard)
 * @param  ?object                      Semi-filled output template (null: definitely not doing output streaming)
 * @return string                       The page
 */
function load_html_page($string, $file_base = null, &$out = null)
{
    if (is_null($file_base)) {
        $file_base = get_file_base();
    }

    global $PAGE_STRING;
    if (is_null($PAGE_STRING)) {
        $PAGE_STRING = $string;
    }

    $html = file_get_contents($file_base . '/' . $string);

    // Post-processing
    if (strpos($html, '<html') !== false) {
        $matches = array();

        // Fix links to anything in same dir, by assuming eitheran ocP page in same zone -- or uploads/website_specific, or next to html files, or in root
        $link_attributes = array('src', 'href', 'action', 'data', 'codebase', 'background');
        foreach ($link_attributes as $attribute) {
            $num_matches = preg_match_all('#<[^<>]* ' . $attribute . '="([^&"]+\.[^&"\.]+)"[^<>]*>#mis', $html, $matches);
            for ($i = 0; $i < $num_matches; $i++) {
                $old_link = html_entity_decode($matches[1][$i], ENT_QUOTES);

                $zone = '_SELF';
                if ($old_link[0] == '/') {
                    $old_link = substr($old_link, 1);
                    $zone = '';
                }
                $possible_zone = str_replace('/', '_', dirname($old_link));
                if ($possible_zone == '.') {
                    $possible_zone = '';
                }
                if (($possible_zone != '') && ($possible_zone != get_zone_name()) && (file_exists(get_file_base() . '/' . $possible_zone . '/index.php'))) {
                    $zone = $possible_zone;
                }

                if (substr($old_link, -4) == '.htm') {
                    $_new_link = build_url(array('page' => basename(substr($old_link, 0, strlen($old_link) - 4))), $zone);
                    $new_link = $_new_link->evaluate();
                } elseif (substr($old_link, -5) == '.html') {
                    $_new_link = build_url(array('page' => basename(substr($old_link, 0, strlen($old_link) - 5))), $zone);
                    $new_link = $_new_link->evaluate();
                } else {
                    $new_link = $old_link;
                    if (url_is_local($old_link)) {
                        if (is_file(get_custom_file_base() . '/' . dirname($string) . '/' . urldecode($old_link))) { // HTML pages dir
                            $dirname = dirname($string);
                            if ($dirname == '.') {
                                $dirname = '';
                            }
                            $new_link = get_base_url() . '/' . (($dirname == '') ? '' : ($dirname . '/')) . $old_link;
                        } elseif (is_file(get_custom_file_base() . '/' . get_zone_name() . '/' . urldecode($old_link))) { // Zone dir
                            $new_link = get_base_url() . '/' . ((get_zone_name() == '') ? '' : (get_zone_name() . '/')) . $old_link;
                        } elseif (is_file(get_custom_file_base() . '/' . urldecode($old_link))) { // Root dir
                            $new_link = get_base_url() . '/' . $old_link;
                        } else {
                            $new_link = get_base_url() . '/uploads/website_specific/' . $old_link; // uploads/website_specific
                        }
                    }
                }

                $html = str_replace(' ' . $attribute . '="' . $old_link . '"', ' ' . $attribute . '="' . $new_link . '"', $html);
            }
        }

        // Extract script, style, and link elements from head
        if (preg_match('#<\s*head[^<>]*>(.*)<\s*/\s*head\s*>#mis', $html, $matches) != 0) {
            $head = $matches[1];

            $head_patterns = array('#<\s*script.*<\s*/\s*script\s*>#misU', '#<\s*link[^<>]*>#misU', '#<\s*style.*<\s*/\s*style\s*>#misU');
            foreach ($head_patterns as $pattern) {
                $num_matches = preg_match_all($pattern, $head, $matches);
                for ($i = 0; $i < $num_matches; $i++) {
                    attach_to_screen_header($matches[0][$i]);
                }
            }
        }

        // Extra meta keywords and description, and title
        global $SEO_KEYWORDS, $SEO_DESCRIPTION;
        if (preg_match('#<\s*meta\s+name\s*=\s*"keywords"\s+content="([^"]*)"#mi', $html, $matches) != 0) {
            $SEO_KEYWORDS = explode(',', @html_entity_decode(trim($matches[1]), ENT_QUOTES, get_charset()));
        }
        if (preg_match('#<\s*meta\s+name\s*=\s*"description"\s+content="([^"]*)"#mi', $html, $matches) != 0) {
            $SEO_DESCRIPTION = @html_entity_decode(trim($matches[1]), ENT_QUOTES, get_charset());
        }
        if (preg_match('#<\s*title\s*>([^<>]*)<\s*/\s*title\s*>#mis', $html, $matches) != 0) {
            set_short_title(@html_entity_decode(trim($matches[1]), ENT_QUOTES, get_charset()));
        }

        // Extract body
        if (preg_match('#<\s*body[^>]*>(.*)<\s*/\s*body\s*>#mis', $html, $matches) != 0) {
            $html = $matches[1];
        } else {
            $html = '';
        }
    }

    if (($GLOBALS['OUTPUT_STREAMING']) && ($out !== null)) {
        $out->evaluate_echo(null, true);
    }

    return $html;
}
