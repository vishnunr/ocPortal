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
 * @package		filedump
 */

class Hook_occle_fs_home
{
	/**
	 * Standard modular listing function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string	The root node of the current meta-directory
	 * @param  array		The current directory listing
	 * @param  array		A reference to the OcCLE filesystem object
	 * @return ~array 	The final directory listing (false: failure)
	 */
	function listing($meta_dir,$meta_root_node,$current_dir,&$occle_fs)
	{
		$path=get_custom_file_base().'/uploads/filedump';
		foreach ($meta_dir as $meta_dir_section) $path.='/'.filter_naughty($meta_dir_section);

		require_code('files');

		$listing=array();
		if (is_dir($path))
		{
			$dh=opendir($path);
			while (($file=readdir($dh))!==false)
			{
				if (!should_ignore_file($file,IGNORE_ACCESS_CONTROLLERS))
				{
					$listing[]=array(
						$file,
						is_dir($path.'/'.$file)?OCCLEFS_DIR:OCCLEFS_FILE,
						is_dir($path.'/'.$file)?NULL:filesize($path.'/'.$file),
						filemtime($path.'/'.$file),
					);
				}
			}
			return $listing;
		}

		return false; // Directory doesn't exist
	}

	/**
	 * Standard modular directory creation function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The new directory name
	 * @param  array	 	A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function make_directory($meta_dir,$meta_root_node,$new_dir_name,&$occle_fs)
	{
		$new_dir_name=filter_naughty($new_dir_name);
		$path=get_custom_file_base().'/uploads/filedump';
		foreach ($meta_dir as $meta_dir_section) $path.='/'.filter_naughty($meta_dir_section);

		if ((is_dir($path)) && (!file_exists($path.'/'.$new_dir_name)))
		{
			$ret=@mkdir($path.'/'.$new_dir_name,0777) OR warn_exit(do_lang_tempcode('WRITE_ERROR',escape_html($path.'/'.$new_dir_name)));
			fix_permissions($path.'/'.$new_dir_name,0777);
			sync_file($path.'/'.$new_dir_name);
			return $ret;
		}
		else return false; // Directory exists
	}

	/**
	 * Standard modular directory removal function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The directory name
	 * @param  array		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function remove_directory($meta_dir,$meta_root_node,$dir_name,&$occle_fs)
	{
		$dir_name=filter_naughty($dir_name);
		$path=get_custom_file_base().'/uploads/filedump';
		foreach ($meta_dir as $meta_dir_section) $path.='/'.filter_naughty($meta_dir_section);

		if ((is_dir($path)) && (file_exists($path.'/'.$dir_name)))
		{
			require_code('files');
			deldir_contents($path.'/'.$dir_name);
			$ret=@rmdir($path.'/'.$dir_name) OR warn_exit(do_lang_tempcode('WRITE_ERROR',escape_html($path.'/'.$dir_name)));
			sync_file($path.'/'.$dir_name);
			return true;
		}
		else return false; // Directory doesn't exist
	}

	/**
	 * Standard modular file removal function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  array		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function remove_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
		$file_name=filter_naughty($file_name);
		$path=get_custom_file_base().'/uploads/filedump';
		foreach ($meta_dir as $meta_dir_section) $path.='/'.filter_naughty($meta_dir_section);

		if ((is_dir($path)) && (file_exists($path.'/'.$file_name)))
		{
			$ret=@unlink($path.'/'.$file_name) OR intelligent_write_error($path.'/'.$file_name);
			sync_file($path.'/'.$file_name);
			return $ret;
		}
		else return false; // File doesn't exist
	}

	/**
	 * Standard modular file reading function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  array		A reference to the OcCLE filesystem object
	 * @return ~string	The file contents (false: failure)
	 */
	function read_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
		$file_name=filter_naughty($file_name);
		$path=get_custom_file_base().'/uploads/filedump';
		foreach ($meta_dir as $meta_dir_section) $path.='/'.filter_naughty($meta_dir_section);

		if ((is_dir($path)) && (file_exists($path.'/'.$file_name)) && (is_readable($path.'/'.$file_name)))
		{
			return file_get_contents($path.'/'.$file_name);
		}
		else return false; // File doesn't exist
	}

	/**
	 * Standard modular file writing function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  string		The new file contents
	 * @param  array		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function write_file($meta_dir,$meta_root_node,$file_name,$contents,&$occle_fs)
	{
		$file_name=filter_naughty($file_name);
		$path=get_custom_file_base().'/uploads/filedump';
		foreach ($meta_dir as $meta_dir_section) $path.='/'.filter_naughty($meta_dir_section);

		if ((is_dir($path)) && (((file_exists($path.'/'.$file_name)) && (is_writable_wrap($path.'/'.$file_name))) || ((!file_exists($path.'/'.$file_name)) && (is_writable_wrap($path)))))
		{
			$fh=@fopen($path.'/'.$file_name,'wt') OR intelligent_write_error($path.'/'.$file_name);
			$output=fwrite($fh,$contents);
			fclose($fh);
			if ($output<strlen($contents)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
			fix_permissions($path.'/'.$file_name);
			sync_file($path.'/'.$file_name);
			return $output;
		}
		else return false; // File doesn't exist
	}

}
