<?php
/**
 * @package     TJVendor
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.form.helper');
\JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of vendors
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldVendorsList extends \JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var   string
	 * @since __DEPLOY_VERSION__
	 */
	protected $type = 'vendorslist';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$lang = Factory::getLanguage();
		$lang->load('com_tjvendors', JPATH_ADMINISTRATOR);
		$options   = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJVENDORS_VENDOR_SELECT_VENDOR'));

		// Get all vendors options, Param false to return option not all vendors
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn(array('v.vendor_id', 'v.vendor_title')))
		->from($db->qn('#__tjvendors_vendors', 'v'))
		->join('LEFT', $db->qn('#__vendor_client_xref', 'x') . 'ON (' . $db->qn('x.vendor_id') . ' = ' . $db->qn('v.vendor_id') . ')')
		->where($db->qn('x.client') . ' = ' . $db->quote($this->element['client']))
		->where($db->qn('x.state') . ' = ' . $db->quote('1'));
		$db->setQuery($query);
		$vendorsList = $db->loadAssocList();

		if (!empty($vendorsList))
		{
			foreach ($vendorsList as $key => $vendor)
			{
				$options[]  = HTMLHelper::_('select.option', htmlspecialchars($vendor['vendor_id']), htmlspecialchars($vendor['vendor_title']));
			}
		}

		return $options;
	}
}
