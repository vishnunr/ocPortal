TODO (uses do_next_menus for grouping guidance)

	//if ((($meta_gather & SITEMAP_GATHER_IMAGE)!=0) && (isset($cma_info['thumb_field'])))	We don't have 2x images for content
	//	$struct['extra_meta']['image_2x']=$row[$cma_info['thumb_field']];


	<?php /*

	 ocPortal
	 Copyright (c) ocProducts, 2004-2014

	 See text/EN/licence.txt for full licencing information.


	 NOTE TO PROGRAMMERS:
	   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
	   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

	*/

	/**
	 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
	 * @copyright	ocProducts Ltd
	 * @package		?
	 */

	class Hook_sitemap_?
	{
		/**
		 * Convert a page link to a category ID and category permission module type.
		 *
		 * @param  ID_TEXT		The page-link.
		 * @return boolean		Whether the page-link is handled by this hook.
		 */
		function handles_pagelink($pagelink)
		{
			?
		}

		/**
		 * Find details of a position in the sitemap.
		 *
		 * @param  ID_TEXT  		The page-link we are finding.
		 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
		 * @param  ?array			List of node content types we will return/recurse-through (NULL: no limit)
		 * @param  ?integer		How deep to go from the sitemap root (NULL: no limit).
		 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
		 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
		 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
		 * @param  boolean		Whether to filter out non-validated content.
		 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
		 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
		 * @return ?array			Result structure (NULL: working via callback).
		 */
		function get_node($pagelink,$callback=NULL,$valid_node_content_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0)
		{
			return array(
				'title'=>?,
				'content_type'=>?,
				'content_id'=>?,
				'pagelink'=>?,
				'sitemap_priority'=>?, // 0.0 to 1.0
				'sitemap_changefreq'=>?, // always|hourly|daily|weekly|monthly|yearly|never
				'extra_meta'=>array(
					'description'=>?,
					'image'=>?,
					'image_2x'=>?,
					'add_date'=>?,
					'edit_date'=>?,
					'submitter'=>?,
					'views'=>?,
					'rating'=>?,
					'meta_keywords'=>?,
					'meta_description'=>?,
					'categories'=>array(?),
					'db_row'=>array(?),
				),
				'permissions'=>array(
					array(
						'type'=>'privilege',
						'privilege'=>?
						'permission_module'=>?,
						'category_name'=>?,
						'page_name'=>?,
					),
					array(
						'type'=>'zone',
						'zone_name'=>?,
					),
					array(
						'type'=>'page',
						'zone_name'=>?,
						'page_name'=>?,
					),
					array(
						'type'=>'category',
						'permission_module'=>?,
						'category_name'=>?,
						'page_name'=>?,
					),
				),
				'child_pagelink_pattern'=>?,
				'child_permission_module'=>?,
				'has_possible_children'=>?,
				'children'=>array(
					? ...
				),
			);
		}

		/**
		 * Convert a page link to a category ID and category permission module type.
		 *
		 * @param  string	The page link
		 * @return ?array	The pair (NULL: permission modules not handled)
		 */
		function extract_child_pagelink_permission_pair($pagelink)
		{
			$matches=array();
			preg_match('#^([^:]*):([^:]*):type=misc:id=(.*)$#',$pagelink,$matches);
			return array($matches[3],'?');
		}
	}
