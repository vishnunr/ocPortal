<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core
 */

/*EXTRA FUNCTIONS: wincache\_.+*/

/**
 * Cache Driver.
 * @package		core
 */
class ocp_wincache
{
	/**
	 * Get data from the persistent cache.
	 *
	 * @param  mixed			Key
	 * @param  ?TIME			Minimum timestamp that entries from the cache may hold (NULL: don't care)
	 * @return ?mixed			The data (NULL: not found / NULL entry)
	 */
	function get($key,$min_cache_date=NULL)
	{
		$success=false;
		$data=wincache_ucache_get($key,$success);
		if (!$success) return NULL;
		if ((!is_null($min_cache_date)) && ($data[0]<$min_cache_date)) return NULL;
		return $data[1];
	}

	/**
	 * Put data into the persistent cache.
	 *
	 * @param  mixed			Key
	 * @param  mixed			The data
	 * @param  integer		Various flags (parameter not used)
	 * @param  integer		The expiration time in seconds.
	 */
	function set($key,$data,$flags,$expire_secs)
	{
		if ($expire_secs==-1) $expire_secs=0;
		wincache_ucache_set($key,array(time(),$data),$expire_secs);
	}

	/**
	 * Delete data from the persistent cache.
	 *
	 * @param  mixed			Key name
	 */
	function delete($key)
	{
		wincache_ucache_delete($key);
	}

	/**
	 * Remove all data from the persistent cache.
	 */
	function flush()
	{
		wincache_ucache_clear();
	}
}
