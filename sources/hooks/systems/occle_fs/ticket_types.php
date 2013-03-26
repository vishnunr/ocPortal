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
 * @package		tickets
 */

require_code('content_fs');

class Hook_occle_fs_ticket_types extends content_fs_base
{
	var $file_content_type='ticket_type';

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'guest_emails_mandatory'=>'BINARY',
			'search_faq'=>'BINARY'
		);
	}

	/**
	 * Standard modular add function for content hooks. Adds some content with the given label and properties.
	 *
	 * @param  SHORT_TEXT	Filename OR Content label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The content ID (false: error, could not create via these properties / here)
	 */
	function _file_add($filename,$path,$properties)
	{
		list($category_content_type,$category)=$this->_folder_convert_filename_to_id($path);
		list($properties,$label)=$this->_file_magic_filter($filename,$path,$properties);

		require_code('tickets2');

		$guest_emails_mandatory=$this->_default_property_int($properties,'guest_emails_mandatory');
		$search_faq=$this->_default_property_int($properties,'search_faq');

		$id=add_ticket_type($label,$guest_emails_mandatory,$search_faq);
		return strval($id);
	}

	/**
	 * Standard modular delete function for content hooks. Deletes the content.
	 *
	 * @param  ID_TEXT	The filename
	 */
	function _file_delete($filename)
	{
		list($content_type,$content_id)=$this->_file_convert_filename_to_id($filename);

		require_code('tickets2');
		delete_ticket_type(intval($content_id));
	}
}
