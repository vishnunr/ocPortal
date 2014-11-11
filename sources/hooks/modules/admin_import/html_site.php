<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: levenshtein*/

/**
 * Calculate Levenshtein distance between two strings, but work past the PHP function's character limit.
 *
 * @param  string                       First string.
 * @param  string                       Second string.
 * @return integer                      Distance.
 */
function fake_levenshtein($a, $b)
{
    // Some stripping, for performance, and because white space doesn't matter so much in HTML anyway
    $a = preg_replace('#\s#', '', $a);
    $b = preg_replace('#\s#', '', $b);

    $a_len = strlen($a);
    $b_len = strlen($b);
    if (($a_len < 255) && ($b_len < 255)) {
        return levenshtein($a, $b);
    }
    $percent = 0.0;
    return max($a_len, $b_len) - similar_text($a, $b, $percent);
}

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    import
 */
class Hook_html_site
{
    /**
     * Standard importer hook info function.
     *
     * @return ?array                   Importer handling details, including lists of all the import types covered (import types are not necessarily the same as actual tables) (NULL: importer is disabled).
     */
    public function info()
    {
        $info = array();
        $info['supports_advanced_import'] = false;
        $info['product'] = 'HTML website (page extraction and basic themeing)';
        $info['import'] = array(
            'pages',
        );
        return $info;
    }

    /**
     * Probe a file path for DB access details.
     *
     * @param  string                   The probe path
     * @return array                    A quartet of the details (db_name, db_user, db_pass, table_prefix)
     */
    public function probe_db_access($file_base)
    {
        return array(null, null, null, null); // No DB connection needed
    }

    /**
     * Standard import function to get extra fields to ask for when starting the import.
     *
     * @return tempcode                 Extra fields
     */
    public function get_extra_fields()
    {
        // Give user options
        //  - where to copy files from [actually this field is in admin_import.php]
        //  - theme to save into (advise they should use Theme Wizard to create a theme with similar colour first)
        //  - whether to Comcode-convert
        //  - whether to fix invalid XHTML
        //  - the base URL to use to turn absolute URLs into relative URLs

        $fields = new ocp_tempcode();

        $themes = new ocp_tempcode();
        require_code('themes2');
        $_themes = find_all_themes();
        require_code('form_templates');
        foreach ($_themes as $theme => $theme_title) {
            $themes->attach(form_input_list_entry($theme, ($theme == $GLOBALS['FORUM_DRIVER']->get_theme()), $theme_title));
        }
        $fields = form_input_list(do_lang_tempcode('THEME'), do_lang_tempcode('THEME_TO_SAVE_INTO'), 'theme', $themes, null, true);

        $fields->attach(form_input_tick(do_lang_tempcode('WHETHER_CONVERT_COMCODE'), do_lang_tempcode('DESCRIPTION_WHETHER_CONVERT_COMCODE'), 'convert_to_comcode', false));

        $fields->attach(form_input_tick(do_lang_tempcode('FIX_INVALID_HTML'), do_lang_tempcode('DESCRIPTION_FIX_INVALID_HTML'), 'fix_html', true));

        $fields->attach(form_input_line(do_lang_tempcode('BASE_URL'), do_lang_tempcode('DESCRIPTION_IMPORT_BASE_URL'), 'base_url', get_base_url(), true));

        return $fields;
    }

    /**
     * Standard import function.
     *
     * @param  object                   The DB connection to import from
     * @param  string                   The table prefix the target prefix is using
     * @param  PATH                     The base directory we are importing from
     */
    public function import_pages($db, $table_prefix, $file_base)
    {
        appengine_live_guard();

        require_code('files2');
        $files = @get_directory_contents($file_base);

        $theme = either_param('theme');
        $convert_to_comcode = either_param_integer('convert_to_comcode', 0);
        $fix_html = either_param_integer('fix_html', 0);
        $base_url = either_param('base_url');
        if (substr($base_url, -1) == '/') {
            $base_url = substr($base_url, 0, strlen($base_url) - 1);
        }

        // Find all htm/html/php files
        $content_files = array();
        foreach ($files as $i => $file) {
            if ((substr(strtolower($file), -4) == '.htm') || (substr(strtolower($file), -5) == '.html') || (substr(strtolower($file), -4) == '.php')) {
                $content_files[] = $file;
                unset($files[$i]);
            }
        }
        if (count($content_files) == 0) {
            warn_exit(do_lang_tempcode('NO_PAGES_FOUND'));
        }

        // Discern new zones needed
        //  Note: files in directories in a deep path will be considered in a zone name changed so underscores replace slashes
        $new_zones = array();
        $current_zones = find_all_zones();
        foreach ($content_files as $file) {
            $zone = str_replace('/', '_', dirname($file));
            if ($zone == '.') {
                $zone = '';
            }
            if (!in_array($zone, $current_zones)) {
                $new_zones[] = $zone;
            }
        }
        $new_zones = array_unique($new_zones);

        // (Maybe AFM needed here - if zones have to be created, and possibly .htaccess changed to incorporate zone names in the redirects)
        if (count($new_zones) != 0) {
            require_code('abstract_file_manager');
            force_have_afm_details();

            // Create new zones as needed (and set them to our chosen theme too)
            require_code('zones2');
            foreach ($new_zones as $zone) {
                actual_add_zone($zone, titleify($zone), 'start', '', $theme, 0);
            }

            sync_htaccess_with_zones();
        }

        // Discern cruft in htm/html via looking for best levenshtein to length ratio over a few pages; scan by tag, not by byte
        $compare_file_contents = array();
        shuffle($content_files);
        for ($i = 0; $i < min(2 /* We would like this to be 5 so we can remove the problem of outliers, but performance is an issue */, count($content_files)); $i++) {
            $file_contents = file_get_contents($file_base . '/' . $content_files[$i]);

            $compare_file_contents[$content_files[$i]] = $this->_html_filter($file_contents, $fix_html, $base_url, $files, $file_base);
        }
        $cruft = array();

        if (count($compare_file_contents) > 1) {
            $to_find = array();
            if (file_exists($file_base . '/header.txt')) {
                $cruft['HEADER'] = $this->_html_filter(file_get_contents($file_base . '/header.txt'), $fix_html, $base_url, $files, $file_base);
            } else {
                $to_find[] = 'HEADER';
            }
            if (file_exists($file_base . '/footer.txt')) {
                $cruft['FOOTER'] = $this->_html_filter(file_get_contents($file_base . '/footer.txt'), $fix_html, $base_url, $files, $file_base);
            } else {
                $to_find[] = 'FOOTER';
            }
            foreach ($to_find as $template_wanted) {
                $best_ratios = array();
                foreach ($compare_file_contents as $i => $reference_file) { // We have to try using each as a reference point (it is this that will form the template text), as we may get unlucky if we chose a reference point file that was completely different.
                    if ($template_wanted == 'HEADER') {
                        $last_pos = strpos($reference_file, '<body');
                        if ($last_pos === false) {
                            $last_pos = 0;
                        } else {
                            $last_pos += 5;
                        }
                    } else {
                        $last_pos = strlen($reference_file) - 1;
                    }
                    $best_av_ratios = mixed();
                    $ratios = array();
                    while ($last_pos !== false) {
                        //@print('!'.(strlen($reference_file)-$last_pos).' '.$lv.' '.$ratio.'<br />'."\n");flush();if (@$dd++==180) @exit('fini'); // Useful for debugging
                        if ($template_wanted == 'HEADER') {
                            $next_pos = strpos($reference_file, '<', $last_pos);
                        } else {
                            $next_pos = strrpos(substr($reference_file, 0, $last_pos), '<');
                        }
                        if ($next_pos !== false) {
                            if ($template_wanted == 'HEADER') {
                                $up_to = substr($reference_file, 0, $next_pos);
                            } else {
                                $up_to = substr($reference_file, $next_pos);
                            }
                            $all_ratios_for_pos = array();
                            foreach ($compare_file_contents as $j => $other_file) {
                                if ($i != $j) {
                                    if ($template_wanted == 'HEADER') {
                                        $up_to_other_file = substr($other_file, 0, $next_pos);
                                    } else {
                                        $up_to_other_file = substr($other_file, $next_pos - (strlen($reference_file) - strlen($other_file)));
                                    }
                                    $lv = fake_levenshtein($up_to, $up_to_other_file);
                                    if ($template_wanted == 'HEADER') {
                                        $ratio = floatval($lv) * 3 - floatval($next_pos + 1 /* +1 stops divides by zero */); // We want this number to be as small as possible. We have multiplied the levenshtein distance because we care about that more than length (this number reached by experimentation); HTML has a low entropy which this number is fighting against.
                                    } else {
                                        $ratio = floatval($lv) * 3 - floatval(strlen($reference_file) - $next_pos); // We want this number to be as small as possible. We have multiplied the levenshtein distance because we care about that more than length (this number reached by experimentation); HTML has a low entropy which this number is fighting against.
                                    }
                                    $all_ratios_for_pos[] = $ratio;
                                }
                            }
                            $av_ratios = array_sum($all_ratios_for_pos) / floatval(count($all_ratios_for_pos));
                            if ((is_null($best_av_ratios)) || ($av_ratios < $best_av_ratios)) {
                                $best_av_ratios = $av_ratios;
                            } elseif ($av_ratios > $best_av_ratios + 300) { // If we go a long way off-course, die out for efficiency reasons (no point going through whole file if we think a new peak is very unlikely)
                                break;
                            }
                            $ratios[$next_pos] = $av_ratios;

                            if ($template_wanted == 'HEADER') {
                                $next_pos++;
                            } else {
                                $next_pos--;
                            }
                        }
                        $last_pos = $next_pos;
                    }

                    asort($ratios);
                    $best_by_pos = array_keys($ratios);
                    $best_ratios[] = array($best_by_pos[0], $ratios[$best_by_pos[0]], $reference_file);
                }
                $best = mixed();
                $best_pos = null;
                $best_reference_file = null;
                foreach ($best_ratios as $bits) {
                    list($pos, $ratio, $reference_file) = $bits;
                    if ((is_null($best)) || ($ratio < $best)) {
                        $best = $ratio;
                        $best_pos = $pos;
                        $best_reference_file = $reference_file;
                    }
                }
                if ($template_wanted == 'HEADER') {
                    $cruft[$template_wanted] = substr($best_reference_file, 0, $best_pos);
                } else {
                    $cruft[$template_wanted] = substr($best_reference_file, $best_pos);
                }
            }
        } else {
            // We can't find any common consistency when we only have one, so we mark all cruft and then later we will actually assume GLOBAL.tpl does not change and the only header/footer bit is the logical one
            $cruft['HEADER'] = array_key_exists(0, $compare_file_contents) ? $compare_file_contents[0] : '';
            $cruft['FOOTER'] = array_key_exists(1, $compare_file_contents) ? $compare_file_contents[0] : '';
        }

        // Extract header from cruft (<body> and before); SAVE
        $header = $cruft['HEADER'];
        // special cases of something with ID or class of header/top going through too
        $header_cases = array('<div id="header"', '<div id="page_header"', '<div class="header"', '<div class="page_header"');
        foreach ($header_cases as $header_case) {
            $header_start_pos = strpos($header, $header_case);
            if ($header_start_pos !== false) {
                $header_start_pos = strpos($header, '>', $header_start_pos) + 1;
                break;
            }
        }
        if ($header_start_pos !== false) {
            $div_count = 1;
            do {
                $next_start = strpos($header, '<div ', $header_start_pos);
                $next_end = strpos($header, '</div>', $header_start_pos);
                $header_start_pos = (($next_start !== false) && ($next_start < $next_end)) ? $next_start : $next_end;
                if ($header_start_pos !== false) {
                    $header_start_pos = strpos($header, '>', $header_start_pos) + 1;
                }
                $div_count += (($next_start !== false) && ($next_start < $next_end)) ? 1 : -1;
            }
            while (($div_count > 0) && ($header_start_pos !== false));
        }
        $body_start_pos = strpos($header, '<body');
        $head_end_pos = strpos($header, '<link');
        if ($head_end_pos === false) {
            $head_end_pos = strpos($header, '</head');
        }
        if ($header_start_pos === false) {
            $header_start_pos = strpos($header, '>', $body_start_pos) + 1;
        }
        if ($header_start_pos !== false) {
            $header = substr($header, 0, $header_start_pos);
        }
        $header_to_write = substr($header, 0, $head_end_pos) . '{+START,INCLUDE,HTML_HEAD}{+END}' . substr($header, $head_end_pos);
        $header_to_write = preg_replace('#<title>[^<>]*</title>#', '<title>{+START,IF_NON_EMPTY,{HEADER_TEXT}}{HEADER_TEXT*} - {+END}{$SITE_NAME*}</title>', $header_to_write);
        $header_to_write = preg_replace('#<meta name="keywords" content="([^"]*)"[^>]*>#', '', $header_to_write);
        $header_to_write = preg_replace('#<meta name="description" content="([^"]*)"[^>]*>#', '', $header_to_write);

        // Extract footer from cruft (</body> and below); SAVE
        $footer = $cruft['FOOTER'];
        // special cases of something with ID or class of footer/bottom going through too
        $footer_cases = array('<div id="footer"', '<div id="page_footer"', '<div class="footer"', '<div class="page_footer"');
        foreach ($footer_cases as $footer_case) {
            $footer_start_pos = strpos($footer, $footer_case);
            if ($footer_start_pos !== false) {
                break;
            }
        }
        if ($footer_start_pos === false) {
            $footer_start_pos = strpos($footer, '</body');
        }
        if ($footer_start_pos !== false) {
            $footer = substr($footer, $footer_start_pos);
        }
        $footer_to_write = $footer;

        // What remains is saved to GLOBAL_HTML_WRAP (note that we don't try and be clever about panels - this is up to the user, and they don't really need them anyway)
        if (count($compare_file_contents) > 1) {
            $global_to_write = $header_to_write . substr($cruft['HEADER'], strlen($header)) . "\n{MIDDLE}\n" . substr($cruft['FOOTER'], 0, strlen($cruft['FOOTER']) - strlen($footer)) . $footer_to_write;
        } else {
            $cruft['HEADER'] = $header_to_write;
            $cruft['FOOTER'] = $footer_to_write;
            $global_to_write = $header_to_write . '{MIDDLE}' . $footer_to_write;
        }
        $path = get_custom_file_base() . '/themes/' . filter_naughty($theme) . '/templates_custom/GLOBAL_HTML_WRAP.tpl';
        $myfile = fopen($path, GOOGLE_APPENGINE ? 'wb' : 'wt');
        fwrite($myfile, $global_to_write);
        fclose($myfile);
        fix_permissions($path);
        sync_file($path);

        // Extract site name from <title> tag, based on common consistency (largest common substring)
        $site_name = get_site_name();
        if (count($compare_file_contents) > 1) {
            $titles_in_reference_files = array();
            foreach ($compare_file_contents as $reference_file) {
                $matches = array();
                if (preg_match('#<title>(.*)</title>#', $reference_file, $matches) != 0) {
                    $titles_in_reference_files[] = $matches[1];
                }
            }
            // Find largest common substring
            $lcs = '';
            foreach ($titles_in_reference_files as $title_a) {
                for ($start = 0; $start < strlen($title_a); $start++) {
                    for ($end = $start + 1; $end < strlen($title_a); $end++) {
                        $current = substr($title_a, $start, $end - $start + 1);
                        foreach ($titles_in_reference_files as $title_b) {
                            if ($title_a != $title_b) {
                                if (strpos(strtolower($title_b), strtolower($current)) === false) {
                                    continue 2;
                                }
                            }
                        }
                        if (strpos(strtolower($title_b), strtolower($current)) !== false) {
                            if (strlen($current) > strlen($lcs)) {
                                $lcs = $current;
                            }
                        }
                    }
                }
            }
            // Strip bits
            $site_name = trim(preg_replace('#^[\|\-�,]#', '', preg_replace('#[\|\-�,]$#', '', trim($lcs))));
            // Save as site name
            set_option('site_name', $site_name);
        }

        // Go and save our pages
        disable_php_memory_limit();

        foreach ($content_files as $content_file) {
            $file_contents = file_get_contents($file_base . '/' . $content_file);

            // Find page-link for page
            $slash_count = substr_count($content_file, '/');
            if ($slash_count == 0) {
                $content_file = '/' . $content_file;
            } elseif ($slash_count > 1) {
                $last_slash_pos = strrpos($content_file, '/');
                $content_file = str_replace('/', '_', substr($content_file, 0, $last_slash_pos)) . substr($content_file, 0, $last_slash_pos);
            }
            list($zone, $page) = explode('/', preg_replace('#\..*$#', '', $content_file), 2);
            if ($page == 'index') {
                $page = 'start';
            }

            if (substr($content_file, -4) == '.php') {
                $file_path = zone_black_magic_filterer(get_custom_file_base() . '/' . $zone . '/pages/minimodules_custom/' . $page . '.php');
                $myfile = fopen($file_path, GOOGLE_APPENGINE ? 'wb' : 'wt');
                fwrite($myfile, $file_contents);
                fclose($myfile);
                fix_permissions($file_path);
                sync_file($file_path);
            } else {
                $filtered = $this->_html_filter($file_contents, $fix_html, $base_url, $files, $file_base);

                // Try and work out page title from <title> tag
                $matches = array();
                $page_title = null;
                if (preg_match('#<title>(.*)</title>#', $filtered, $matches) != 0) {
                    $page_title = preg_replace('#( [\|\-�] )?' . preg_quote($site_name) . '( [\|\-�] )?#', '', $matches[1]);
                }
                $page_keywords = null;
                if (preg_match('#<meta name="keywords" content="([^"]*)"#', $filtered, $matches) != 0) {
                    $page_keywords = $matches[1];
                }
                $page_description = null;
                if (preg_match('#<meta name="description" content="([^"]*)"#', $filtered, $matches) != 0) {
                    $page_description = $matches[1];
                }
                require_code('seo2');
                seo_meta_set_for_explicit('comcode_page', $zone . ':' . $page, $page_keywords, $page_description);

                // Strip cruft off for htm/html files
                $i = strpos($filtered, '</head>');
                if ($i === false) {
                    $i = 0;
                } else {
                    $i += 7;
                }
                $filtered = $this->levenshtein_strip_search($cruft['HEADER'], $filtered, false, $i);
                $filtered = $this->levenshtein_strip_search($cruft['FOOTER'], $filtered, true, 0);

                // Copy htm/html/php files to correct zone page directories (html_custom/<lang>, or minimodules_custom)
                if ($convert_to_comcode == 0) {
                    // Insert an <h1> if the h1 is not there
                    if ((strpos($filtered, '<h1') === false) && (!is_null($page_title))) {
                        $filtered = "<h1>" . $page_title . "</h1>\n\n" . $filtered;
                    }

                    $file_path = zone_black_magic_filterer(get_custom_file_base() . '/' . $zone . '/pages/comcode_custom/' . get_site_default_lang() . '/' . $page . '.txt');

                    $myfile = fopen($file_path, GOOGLE_APPENGINE ? 'wb' : 'wt');
                    fwrite($myfile, '[semihtml]' . $filtered . '[/semihtml]');
                    fclose($myfile);
                    fix_permissions($file_path);
                    sync_file($file_path);
                } else { // Or copy htm/html's as Comcode-converted instead, if the user chose this
                    // Insert an <h1> if the h1 is not there
                    if ((strpos($filtered, '[title') === false) && (!is_null($page_title))) {
                        $filtered = "[title]" . $page_title . "[/title]\n\n" . $filtered;
                    }

                    require_code('comcode_from_html');
                    $comcode = semihtml_to_comcode($filtered);
                    $file_path = zone_black_magic_filterer(get_custom_file_base() . '/' . $zone . '/pages/comcode_custom/' . get_site_default_lang() . '/' . $page . '.txt');
                    $myfile = fopen($file_path, GOOGLE_APPENGINE ? 'wb' : 'wt');
                    fwrite($myfile, $comcode);
                    fclose($myfile);
                    fix_permissions($file_path);
                    sync_file($file_path);
                }
            }
        }

        // Copy all remaining files to under uploads/website_specific
        foreach ($files as $file) {
            if (!file_exists($file_base . '/' . $file)) {
                continue;
            }

            $path = get_custom_file_base() . '/uploads/website_specific/' . $file;
            if (!file_exists($path)) {
                require_code('files2');
                make_missing_directory($path);
            }
        }

        // Set the panels to be blank
        foreach (array('site/', '') as $zone) {
            $panels = array('panel_left', 'panel_right');
            foreach ($panels as $panel) {
                $path = zone_black_magic_filterer(get_custom_file_base() . '/' . $zone . 'pages/comcode_custom/' . filter_naughty(fallback_lang()) . '/' . filter_naughty($panel) . '.txt');
                $myfile = fopen($path, GOOGLE_APPENGINE ? 'wb' : 'wt');
                fclose($myfile);
                fix_permissions($path);
                sync_file($path);
            }
        }
    }

    /**
     * Filter HTML that has been read, to make it more compatible with ocPortal.
     *
     * @param  string                   The HTML
     * @param  BINARY                   Whether to fix XHTML errors
     * @param  PATH                     The base URL of the old site
     * @param  array                    A list of all files on the site
     * @param  PATH                     The base directory we are importing from
     * @return string                   Filtered HTML
     */
    protected function _html_filter($file_contents, $fix_html, $base_url, $files, $file_base)
    {
        // If selected, clean up all the HTML
        if ($fix_html == 1) {
            require_code('xhtml');
            $file_contents = xhtmlise_html($file_contents);
        }

        // Strip base URL
        if ($base_url != '') {
            $file_contents = str_replace($base_url . '/', '', $file_contents);
            $file_contents = str_replace(escape_html($base_url . '/'), '', $file_contents);
        }

        // Extra sense for rewriting local URLs in templates to image links and page-links
        $matches = array();
        $num_matches = preg_match_all('# (src|href)="([^"]*)"#', $file_contents, $matches);
        for ($i = 0; $i < $num_matches; $i++) {
            $this_url = $matches[2][$i];
            $this_url = preg_replace('#^\.*/#', '', $this_url);
            if (trim($this_url) == '') {
                continue;
            }
            if ((strpos($this_url, '://') === false) || (substr($this_url, 0, strlen($base_url)) == $base_url)) {
                if (strpos($this_url, '://') !== false) {
                    $this_url = substr($this_url, strlen($base_url));
                }

                $decoded_url = rawurldecode($this_url);

                if (substr($decoded_url, 0, 2) == './') {
                    $decoded_url = substr($decoded_url, 2);
                }

                // Links to directories in a deep path will be changed so underscores replace slashes
                if ((substr(trim($decoded_url), -4) == '.htm') || (substr(trim($decoded_url), -5) == '.html')) {
                    if (substr_count($decoded_url, '/') > 1) {
                        $last_slash_pos = strrpos($decoded_url, '/');
                        $decoded_url = str_replace('/', '_', substr($decoded_url, 0, $last_slash_pos)) . substr($decoded_url, 0, $last_slash_pos);
                    }
                    $decoded_url = trim(preg_replace('#(^|/)index\.htm[l]#', '${1}start.htm', $decoded_url));
                    $stripped_decoded_url = preg_replace('#\..*$#', '', $decoded_url);
                    if (strpos($stripped_decoded_url, '/') === false) {
                        $stripped_decoded_url = '/' . $stripped_decoded_url;
                    }
                    list($zone, $page) = explode('/', $stripped_decoded_url, 2);
                    if ($page == 'index') {
                        $page = 'start';
                    }
                    $file_contents = str_replace($matches[2][$i], '{$PAGE_LINK*,' . $zone . ':' . $page . '}', $file_contents);
                } else {
                    if (in_array($decoded_url, $files)) {
                        $target = get_custom_file_base() . '/uploads/website_specific/' . $decoded_url;
                        $create_path = $target;
                        @mkdir(dirname($target), 0777, true);
                        @unlink($target);
                        @copy($file_base . '/' . $decoded_url, $target);

                        /*if (substr($decoded_url,-4)=='.css') Not needed, as relative paths maintained
                                        {
                                                        $css_file=file_get_contents($target);
                                                        $css_file=preg_replace('#(url\([\'"]?)(\.*'.'/)?#','${1}{$BASE_URL;}/uploads/website_specific/',$css_file);
                                                        $my_css_file=fopen($target,GOOGLE_APPENGINE?'wb':'wt');
                                                        fwrite($my_css_file,$css_file);
                                                        fclose($my_css_file);
                                        }*/

                        fix_permissions($target);
                        sync_file($target);
                    }

                    $decoded_url = 'uploads/website_specific/' . $decoded_url;
                    $file_contents = str_replace('src="' . $matches[2][$i] . '"', 'src="{$BASE_URL*}/' . str_replace('%2F', '/', rawurlencode($decoded_url)) . '"', $file_contents);
                    $file_contents = str_replace('href="' . $matches[2][$i] . '"', 'href="{$BASE_URL*}/' . str_replace('%2F', '/', rawurlencode($decoded_url)) . '"', $file_contents);
                }
            }
        }

        return $file_contents;
    }

    /* Try and strip out a bit of HTML from the start/end of another bit of HTML, but with rough levenshtein matching.
     *
     * @param  string                   What we are stripping.
     * @param  string                   What we are stripping from.
     * @param  boolean                  Whether we are removing from the end.
     * @param  integer                  The position to start at (if $backwards=true, then this is relative to the end).
     * @return string                   The altered string.
     */
    public function levenshtein_strip_search($to_strip, $subject, $backwards, $i)
    {
        $best = mixed();
        $best_at = $i;

        // Find all tag start/end positions (comparison reference points), loading them into the search list, ordered by position
        $up_to = min(strlen($subject), intval(floatval(strlen($to_strip)) * 1.5));
        $positions = array();
        for (; $i < $up_to; $i++) {
            if ($i != 0) {
                if ($backwards) {
                    $next_tag_a = strrpos(substr($subject, 0, strlen($subject) - $i), '<'); // Makes performance reasonable, by only checking at tag points
                    $next_tag_b = strrpos(substr($subject, 0, strlen($subject) - $i), '>');
                } else {
                    $next_tag_a = strpos($subject, '<', $i); // Makes performance reasonable, by only checking at tag points
                    $next_tag_b = strpos($subject, '>', $i);
                }
                $next_tag = (($next_tag_b !== false) && (($next_tag_a === false) || ((!$backwards) && ($next_tag_b < $next_tag_a)) || (($backwards) && ($next_tag_b > $next_tag_a)))) ? ($next_tag_b + 1) : $next_tag_a;
                if ($next_tag === false) {
                    $i = (strlen($subject) - 1);
                } else {
                    $possible_i = $backwards ? (strlen($subject) - $next_tag) : $next_tag;
                    if ($possible_i != $i) {
                        $i = $possible_i;
                    }
                }
            }
            $lev = null;
            //$lev=fake_levenshtein($backwards?substr($subject,-$i):substr($subject,0,$i),$to_strip);    For efficiency the next loop has a more intelligent searching algorithm, to narrow down on the peak
            $positions[] = array($i, $lev);
        }

        do {
            $cnt = count($positions);
            $point_a = intval(3.0 * floatval($cnt) / 8.0);
            $point_b = intval(5.0 * floatval($cnt) / 8.0);
            if (($cnt < 24)/*The peak algorithm breaks down on small data sets due to integer rounding problems and local maxima*/ || ($point_a == $point_b)) {
                break; // Okay now we need to scan manually over the few that are left
            }

            // Take the 3/8 point of the search list, and find it's levenshtein distance
            if (is_null($positions[$point_a][1])) {
                $positions[$point_a][1] = fake_levenshtein($backwards ? substr($subject, -$positions[$point_a][0]) : substr($subject, 0, $positions[$point_a][0]), $to_strip);
            }

            // Take the 5/8 point of the search list, and find it's levenshtein distance
            if (is_null($positions[$point_b][1])) {
                $positions[$point_b][1] = fake_levenshtein($backwards ? substr($subject, -$positions[$point_b][0]) : substr($subject, 0, $positions[$point_b][0]), $to_strip);
            }
            // If the 3/8 point has a higher or equal levenshtein  distance, throw away everything to the left of the 3/8 point
            if ($positions[$point_a][1] >= $positions[$point_b][1]) {
                array_splice($positions, 0, $point_a);
            } else {
                // Therefore the 5/8 point has a higher levenshtein  distance: throw away everything to the right of the 5/8 point
                array_splice($positions, $point_b);
            }
        }
        while (true);    // Repeats until the 3/8 or 5/8 points are the same, due to indivisibility ('break' line does this)

        // Loop over the remaining results, finding the smallest levenshtein distance remaining- this is our result
        foreach ($positions as $p) {
            list($i, $lev) = $p;
            if (is_null($lev)) {
                $lev = fake_levenshtein(substr($subject, 0, $i), $to_strip);
            }

            if ((is_null($best)) || ($lev < $best)) {
                $best = $lev;
                $best_at = $i;
            }
        }
        $ret = $backwards ? substr($subject, 0, (strlen($subject) - $best_at)) : substr($subject, $best_at);

        return $ret;
    }
}
