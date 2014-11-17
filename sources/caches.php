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
 * Standard code module initialisation function.
 */
function init__caches()
{
    global $BLOCK_CACHE_ON_CACHE;
    $BLOCK_CACHE_ON_CACHE = null;

    global $PERSISTENT_CACHE, $SITE_INFO;
    /** The persistent cache access object (NULL if there is no persistent cache).
     *
     * @global ?object $PERSISTENT_CACHE
     */
    $PERSISTENT_CACHE = null;

    $use_memcache = ((array_key_exists('use_mem_cache', $SITE_INFO)) && ($SITE_INFO['use_mem_cache'] != '') && ($SITE_INFO['use_mem_cache'] != '0'));// Default to off because badly configured caches can result in lots of very slow misses and lots of lost sessions || ((!array_key_exists('use_mem_cache',$SITE_INFO)) && ((function_exists('xcache_get')) || (function_exists('wincache_ucache_get')) || (function_exists('apc_fetch')) || (function_exists('eaccelerator_get')) || (function_exists('mmcache_get'))));
    if (($use_memcache) && (!$GLOBALS['IN_MINIKERNEL_VERSION'])) {
        if ((class_exists('Memcached')) && (($SITE_INFO['use_mem_cache'] == 'memcached') || ($SITE_INFO['use_mem_cache'] == '1'))) {
            require_code('persistent_cacheing/memcached');
            $PERSISTENT_CACHE = new Persistent_cacheing_memcached();
        } elseif ((class_exists('Memcache')) && (($SITE_INFO['use_mem_cache'] == 'memcache') || ($SITE_INFO['use_mem_cache'] == '1'))) {
            require_code('persistent_cacheing/memcache');
            $PERSISTENT_CACHE = new Persistent_cacheing_memcache();
        } elseif ((function_exists('apc_fetch')) && (($SITE_INFO['use_mem_cache'] == 'apc') || ($SITE_INFO['use_mem_cache'] == '1'))) {
            require_code('persistent_cacheing/apc');
            $PERSISTENT_CACHE = new Persistent_cacheing_apccache();
        } elseif (((function_exists('eaccelerator_put')) || (function_exists('mmcache_put'))) && (($SITE_INFO['use_mem_cache'] == 'eaccelerator') || ($SITE_INFO['use_mem_cache'] == '1'))) {
            require_code('persistent_cacheing/eaccelerator');
            $PERSISTENT_CACHE = new Persistent_cacheing_eacceleratorcache();
        } elseif ((function_exists('xcache_get')) && (($SITE_INFO['use_mem_cache'] == 'xcache') || ($SITE_INFO['use_mem_cache'] == '1'))) {
            require_code('persistent_cacheing/xcache');
            $PERSISTENT_CACHE = new Persistent_cacheing_xcache();
        } elseif ((function_exists('wincache_ucache_get')) && (($SITE_INFO['use_mem_cache'] == 'wincache') || ($SITE_INFO['use_mem_cache'] == '1'))) {
            require_code('persistent_cacheing/wincache');
            $PERSISTENT_CACHE = new Persistent_cacheing_wincache();
        } elseif ((file_exists(get_custom_file_base() . '/caches/persistent/')) && (($SITE_INFO['use_mem_cache'] == 'filesystem') || ($SITE_INFO['use_mem_cache'] == '1'))) {
            require_code('persistent_cacheing/filesystem');
            $PERSISTENT_CACHE = new Persistent_cacheing_filecache();
        }
    }
}

/**
 * Get data from the persistent cache.
 *
 * @param  mixed                        Key
 * @param  ?TIME                        Minimum timestamp that entries from the cache may hold (null: don't care)
 * @return ?mixed                       The data (null: not found / NULL entry)
 */
function persistent_cache_get($key, $min_cache_date = null)
{
    global $PERSISTENT_CACHE;
    //if (($GLOBALS['DEV_MODE']) && (mt_rand(0,3)==1)) return NULL;  Annoying when doing performance tests, but you can enable to test persistent cache more
    if ($PERSISTENT_CACHE === null) {
        return null;
    }
    $test = $PERSISTENT_CACHE->get(get_file_base() . serialize($key), $min_cache_date); // First we'll try specifically for site
    if ($test !== null) {
        return $test;
    }
    $test = $PERSISTENT_CACHE->get(('ocp' . float_to_raw_string(ocp_version_number())) . serialize($key), $min_cache_date); // And last we'll try server-wide
    return $test;
}

/**
 * Put data into the persistent cache.
 *
 * @param  mixed                        Key
 * @param  mixed                        The data
 * @param  boolean                      Whether it is server-wide data
 * @param  ?integer                     The expiration time in seconds. (null: Default expiry in 60 minutes, or never if it is server-wide).
 */
function persistent_cache_set($key, $data, $server_wide = false, $expire_secs = null)
{
    global $PERSISTENT_CACHE;
    if ($PERSISTENT_CACHE === null) {
        return null;
    }
    if ($expire_secs === null) {
        $expire_secs = $server_wide ? 0 : (60 * 60);
    }
    $PERSISTENT_CACHE->set(($server_wide ? ('ocp' . float_to_raw_string(ocp_version_number())) : get_file_base()) . serialize($key), $data, 0, $expire_secs);
}

/**
 * Delete data from the persistent cache.
 *
 * @param  mixed                        Key name
 * @param  boolean                      Whether we are deleting via substring
 */
function persistent_cache_delete($key, $substring = false)
{
    global $PERSISTENT_CACHE;
    if ($PERSISTENT_CACHE === null) {
        return null;
    }
    if ($substring) {
        $list = $PERSISTENT_CACHE->load_objects_list();
        foreach (array_keys($list) as $l) {
            $delete = true;
            foreach (is_array($key) ? $key : array($key) as $key_part) {
                if (strpos($l, $key_part) === false) { // Should work even though key was serialized, in reasonable cases
                    $delete = false;
                    break;
                }
            }
            if ($delete) {
                $PERSISTENT_CACHE->delete($l);
            }
        }
    } else {
        $PERSISTENT_CACHE->delete(get_file_base() . serialize($key));
        $PERSISTENT_CACHE->delete('ocp' . float_to_raw_string(ocp_version_number()) . serialize($key));
    }
}

/**
 * Remove all data from the persistent cache and static cache.
 */
function erase_persistent_cache()
{
    $path = get_custom_file_base() . '/caches/persistent';
    if (!file_exists($path)) {
        return;
    }
    $d = opendir($path);
    while (($e = readdir($d)) !== false) {
        if (substr($e, -4) == '.gcd') {
            // Ideally we'd lock whilst we delete, but it's not stable (and the workaround would be too slow for our efficiency context). So some people reading may get errors whilst we're clearing the cache. Fortunately this is a rare op to perform.
            @unlink(get_custom_file_base() . '/persistent_cache/' . $e);
        }
    }
    closedir($d);

    global $PERSISTENT_CACHE;
    if ($PERSISTENT_CACHE === null) {
        return null;
    }
    $PERSISTENT_CACHE->flush();
}

/**
 * Remove an item from the general cache (most commonly used for blocks).
 *
 * @param  mixed                        The type of what we are cacheing (e.g. block name) (ID_TEXT or an array of ID_TEXT, the array may be pairs re-specifying $identifier)
 * @param  ?array                       A map of identifiying characteristics (null: no identifying characteristics, decache all)
 */
function decache($cached_for, $identifier = null)
{
    if (get_mass_import_mode()) {
        return;
    }

    require_code('caches2');
    _decache($cached_for, $identifier);
}

/**
 * Find the cache-on parameters for 'codename's cacheing style (prevents us needing to load up extra code to find it).
 *
 * @param  ID_TEXT                      The codename of what will be checked for cacheing
 * @return ?array                       The cached result (null: no cached result)
 */
function find_cache_on($codename)
{
    // See if we have it cached
    global $BLOCK_CACHE_ON_CACHE;
    if ($BLOCK_CACHE_ON_CACHE === null) {
        $BLOCK_CACHE_ON_CACHE = function_exists('persistent_cache_get') ? persistent_cache_get('BLOCK_CACHE_ON_CACHE') : null;
        if ($BLOCK_CACHE_ON_CACHE === null) {
            $BLOCK_CACHE_ON_CACHE = list_to_map('cached_for', $GLOBALS['SITE_DB']->query_select('cache_on', array('*')));
            persistent_cache_set('BLOCK_CACHE_ON_CACHE', $BLOCK_CACHE_ON_CACHE);
        }
    }
    if (isset($BLOCK_CACHE_ON_CACHE[$codename])) {
        return $BLOCK_CACHE_ON_CACHE[$codename];
    }
    return null;
}

/**
 * Find the cached result of what is named by codename and the further constraints.
 *
 * @param  ID_TEXT                      The codename to check for cacheing
 * @param  LONG_TEXT                    The further restraints (a serialized map)
 * @param  integer                      The TTL for the cache entry
 * @param  boolean                      Whether we are cacheing Tempcode (needs special care)
 * @param  boolean                      Whether to defer caching to CRON. Note that this option only works if the block's defined cache signature depends only on $map (timezone and bot-type are automatically considered)
 * @param  ?array                       Parameters to call up block with if we have to defer caching (null: none)
 * @return ?mixed                       The cached result (null: no cached result)
 */
function get_cache_entry($codename, $cache_identifier, $ttl = 10000, $tempcode = false, $caching_via_cron = false, $map = null) // Default to a very big ttl
{
    if ($GLOBALS['PERSISTENT_CACHE'] !== null) {
        $theme = $GLOBALS['FORUM_DRIVER']->get_theme();
        $lang = user_lang();
        $pcache = persistent_cache_get(array('CACHE', $codename, md5($cache_identifier), $lang, $theme));
        if ($pcache === null) {
            if ($caching_via_cron) {
                require_code('caches2');
                request_via_cron($codename, $map, $tempcode);
                return paragraph(do_lang_tempcode('CACHE_NOT_READY_YET'), '', 'nothing_here');
            }
            return null;
        }
        $cache_rows = array($pcache);
    } else {
        $cache_rows = $GLOBALS['SITE_DB']->query_select('cache', array('the_value', 'date_and_time', 'dependencies'), array('lang' => user_lang(), 'cached_for' => $codename, 'the_theme' => $GLOBALS['FORUM_DRIVER']->get_theme(), 'identifier' => md5($cache_identifier)), '', 1);
        if (!isset($cache_rows[0])) { // No
            if ($caching_via_cron) {
                require_code('caches2');
                request_via_cron($codename, $map, $tempcode);
                return paragraph(do_lang_tempcode('CACHE_NOT_READY_YET'), '', 'nothing_here');
            }
            return null;
        }

        if ($tempcode) {
            $ob = new Tempcode();
            if (!$ob->from_assembly($cache_rows[0]['the_value'], true)) {
                return null;
            }
            $cache_rows[0]['the_value'] = $ob;
        } else {
            $cache_rows[0]['the_value'] = unserialize($cache_rows[0]['the_value']);
        }
    }

    $stale = (($ttl != -1) && (time() > ($cache_rows[0]['date_and_time'] + $ttl * 60)));

    if ((!$caching_via_cron) && ($stale)) { // Out of date
        return null;
    } else { // We can use directly
        if ($stale) {
            require_code('caches2');
            request_via_cron($codename, $map, $tempcode);
        }

        $cache = $cache_rows[0]['the_value'];
        if ($cache_rows[0]['dependencies'] != '') {
            $bits = explode('!', $cache_rows[0]['dependencies']);
            $langs_required = explode(':', $bits[0]); // Sometimes lang has got intertwinded with non cacheable stuff (and thus was itself not cached), so we need the lang files
            foreach ($langs_required as $lang) {
                if ($lang != '') {
                    require_lang($lang, null, null, true);
                }
            }
            if (isset($bits[1])) {
                $javascripts_required = explode(':', $bits[1]);
                foreach ($javascripts_required as $javascript) {
                    if ($javascript != '') {
                        require_javascript($javascript);
                    }
                }
            }
            if (isset($bits[2])) {
                $csss_required = explode(':', $bits[2]);
                foreach ($csss_required as $css) {
                    if ($css != '') {
                        require_css($css);
                    }
                }
            }
        }
        return $cache;
    }
}
