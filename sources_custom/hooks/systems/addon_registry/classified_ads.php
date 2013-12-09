<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		classified_ads
 */

class Hook_addon_registry_classified_ads
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the addon category
	 *
	 * @return string			The category
	 */
	function get_category()
	{
		return 'New Features';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Graham';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array(
			'Icon by Andrey Kem',
		);
	}

	/**
	 * Get the addon licence (one-line summary only)
	 *
	 * @return string			The licence
	 */
	function get_licence()
	{
		return 'Licensed on the same terms as ocPortal';
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Set up price scales for placing entries (\'adverts\') in catalogues.

The scales allow you to define discounts to customers buying longer listing periods. You can set up a free period if you wish to.

Customers are given a control panel (the classifieds module) that shows their listings and allows renewal.

E-mails are sent the day before an ad expires.

Fully integrated with catalogues, eCommerce, and OCF member accounts.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(
				'OCF',
				'catalogues',
				'ecommerce',
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
			)
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images_custom/icons/48x48/menu/classifieds.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images_custom/icons/24x24/menu/classifieds.png',
			'themes/default/images_custom/icons/48x48/menu/classifieds.png',
			'sources_custom/hooks/systems/addon_registry/classified_ads.php',
			'adminzone/pages/minimodules_custom/admin_classifieds.php',
			'lang_custom/EN/classifieds.ini',
			'site/pages/modules_custom/classifieds.php',
			'sources_custom/classifieds.php',
			'sources_custom/hooks/modules/members/classifieds.php',
			'sources_custom/hooks/systems/cron/classifieds.php',
			'sources_custom/hooks/systems/page_groupings/classifieds.php',
			'sources_custom/hooks/systems/ecommerce/classifieds.php',
			'sources_custom/hooks/systems/notifications/classifieds.php',
			'sources_custom/miniblocks/main_classifieds_prices.php',
			'themes/default/templates_custom/CLASSIFIED_ADVERTS_SCREEN.tpl',
			'themes/default/templates_custom/CLASSIFIEDS_PRICING_SCREEN.tpl',
			'themes/default/templates_custom/CLASSIFIEDS.tpl',
			'sources_custom/hooks/systems/config/max_classified_listings_per_page.php',
		);
	}
}