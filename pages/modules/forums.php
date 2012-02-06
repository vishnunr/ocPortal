<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

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
 * Module page class.
 */
class Module_forums
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}
	
	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('!'=>'SECTION_FORUMS');
	}
	
	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$base_url=get_forum_base_url();

		$forums=get_param('url',$base_url.'/');

		if (substr($forums,0,strlen($base_url))!=$base_url)
		{
			$base_url=rtrim($forums,'/');
			if ((strpos($base_url,'.php')!==false) || (strpos($base_url,'?')!==false)) $base_url=dirname($base_url);
			
			//log_hack_attack_and_exit('REFERRER_IFRAME_HACK'); No longer a hack attack becase people webmasters changed their forum base URL at some point, creating problems with old bookmarks!
			header('Location: '.get_self_url(true,false,array('url'=>get_forum_base_url())));
			exit();
		}

		$old_method=false;
		if ($old_method)
		{
			return do_template('FORUMS_EMBED',array('_GUID'=>'159575f6b83c5366d29e184a8dd5fc49','FORUMS'=>$forums));
		}
		
		$GLOBALS['SCREEN_TEMPLATE_CALLED']='';

		require_code('integrator');
		return make_string_tempcode(reprocess_url($forums,$base_url));
	}

}


