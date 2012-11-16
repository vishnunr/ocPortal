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
 * @package		catalogues
 */

class Hook_symbol_CATALOGUE_ENTRY_FIELD_VALUE
{

	/**
	 * Standard modular run function for symbol hooks. Searches for tasks to perform.
    *
    * @param  array		Symbol parameters
    * @return string		Result
	 */
	function run($param)
	{
		$value='';
		if (array_key_exists(1,$param))
		{
			$map=NULL;

			$entry_id=intval($param[0]);
			$field_id=intval($param[1]);

			static $cache=array();
			if (isset($cache[$entry_id]))
			{
				$map=$cache[$entry_id];
			} else
			{
				require_code('catalogues');
				$entry=$GLOBALS['SITE_DB']->query_select('catalogue_entries',array('*'),array('id'=>$entry_id),'',1);
				if (array_key_exists(0,$entry))
				{
					$catalogue=$GLOBALS['SITE_DB']->query_select('catalogues',array('*'),array('c_name'=>$entry[0]['c_name']),'',1);
					if (array_key_exists(0,$catalogue))
					{
						$map=get_catalogue_entry_map($entry[0],$catalogue[0],'PAGE','DEFAULT',NULL,NULL/*,array($field_id)*/);
					}
				}

				$cache[$entry_id]=$map;
			}

			if (!is_null($map))
			{
				if (isset($map['FIELD_'.strval($field_id)]))
					$value=$map['FIELD_'.strval($field_id)];
				elseif (isset($map['_FIELD_'.strval($field_id)]))
					$value=$map['_FIELD_'.strval($field_id)];
			}

			if (is_object($value)) $value=$value->evaluate();
		}
		return $value;
	}

}
