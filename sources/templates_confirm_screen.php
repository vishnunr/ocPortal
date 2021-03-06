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
 * @package		core_abstract_interfaces
 */

/**
 * Get the tempcode for a confirmation page.
 *
 * @param  tempcode		The title for the confirmation page (out of get_page_title)
 * @param  tempcode		The preview that's being confirmed for actualisation
 * @param  ID_TEXT		The URL type to confirm through to
 * @param  mixed			The URL type if we click back OR a full URL (if long, or if tempcode)
 * @param  ?array			A map of supplementary post data to get passed through upon confirmation (NULL: none)
 * @param  ?tempcode		Form fields to pass through as post data on confirmation (NULL: none)
 * @return tempcode		The confirmation page
 */
function form_confirm_screen($title,$preview,$url_type,$back_url_type,$sup_post=NULL,$fields=NULL)
{
	if (is_null($sup_post)) $sup_post=array();

	if ((is_string($back_url_type)) && (strlen($back_url_type)<10))
	{
		$back_url=build_url(array('page'=>'_SELF','type'=>$back_url_type),'_SELF',NULL,true);
	} else $back_url=$back_url_type;
	$url=build_url(array('page'=>'_SELF','type'=>$url_type),'_SELF',NULL,true);

	if (is_null($fields)) $fields=new ocp_tempcode();
	$fields->attach(build_keep_post_fields(array_keys($sup_post))); // Everything EXCEPT what might have been passed in sup_post
	foreach ($sup_post as $key=>$val)
	{
		$fields->attach(form_input_hidden($key,is_string($val)?$val:strval($val)));
	}

	return do_template('FORM_CONFIRM_SCREEN',array('_GUID'=>'a99b861d24ab876a40cc010af2b26bc8','URL'=>$url,'BACK_URL'=>$back_url,'PREVIEW'=>$preview,'FIELDS'=>$fields,'TITLE'=>$title));
}

