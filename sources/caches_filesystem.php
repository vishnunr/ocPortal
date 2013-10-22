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

/**
 * Cache Driver.
 * @package		core
 */
class ocp_filecache
{
	/**
	 * Constructor.
	 */
	function __construct()
	{
		require_code('files');
	}

	/**
	 * Get data from the persistent cache.
	 *
	 * @param  mixed			Key
	 * @param  ?TIME			Minimum timestamp that entries from the cache may hold (NULL: don't care)
	 * @return ?mixed			The data (NULL: not found / NULL entry)
	 */
	function get($key,$min_cache_date=NULL)
	{
		$myfile=@fopen(get_custom_file_base().'/caches/persistent/'.md5($key).'.gcd','rb');
		if ($myfile===false) return NULL;
		if (!is_null($min_cache_date)) // Code runs here as we know file exists at this point
		{
			if (filemtime(get_custom_file_base().'/caches/persistent/'.md5($key).'.gcd')<$min_cache_date)
			{
				fclose($myfile);
				return NULL;
			}
		}
		@flock($myfile,LOCK_SH);
		$contents='';
		while (!feof($myfile)) $contents.=fread($myfile,1024);

		$ret=@unserialize($contents);

		@flock($myfile,LOCK_UN);
		fclose($myfile);

		return $ret;
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
		$to_write=serialize($data);

		$path=get_custom_file_base().'/caches/persistent/'.md5($key).'.gcd';
		$myfile=@fopen($path,GOOGLE_APPENGINE?'wb':'ab');
		if ($myfile===false) return; // Failure

		@flock($myfile,LOCK_EX);
		if (!GOOGLE_APPENGINE) ftruncate($myfile,0);
		if (fwrite($myfile,$to_write)!==false)
		{
			// Success
			@flock($myfile,LOCK_UN);
			fclose($myfile);
			fix_permissions($path);
		} else
		{
			// Failure
			@flock($myfile,LOCK_UN);
			fclose($myfile);
			unlink($path);
		}
	}

	/**
	 * Delete data from the persistent cache.
	 *
	 * @param  mixed			Key name
	 */
	function delete($key)
	{
		// Ideally we'd lock whilst we delete, but it's not stable (and the workaround would be too slow for our efficiency context). So some people reading may get errors whilst we're clearing the cache. Fortunately this is a rare op to perform.
		@unlink(get_custom_file_base().'/caches/persistent/'.md5($key).'.gcd');
	}

	/**
	 * Remove all data from the persistent cache.
	 */
	function flush()
	{
		$d=opendir(get_custom_file_base().'/caches/persistent');
		while (($e=readdir($d))!==false)
		{
			if (substr($e,-4)=='.gcd')
			{
				// Ideally we'd lock whilst we delete, but it's not stable (and the workaround would be too slow for our efficiency context). So some people reading may get errors whilst we're clearing the cache. Fortunately this is a rare op to perform.
				@unlink(get_custom_file_base().'/caches/persistent/'.$e);
			}
		}
		closedir($d);
	}
}
