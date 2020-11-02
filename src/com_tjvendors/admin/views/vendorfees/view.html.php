<?php
/**
 * @package     TJVendors
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

JLoader::import('com_tjvendors.helpers.fronthelper', JPATH_SITE . '/components');

/**
 * View class for a list of Tjvendors.
 *
 * @since  1.6
 */
class TjvendorsViewVendorFees extends HtmlView
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$input = Factory::getApplication()->input;
		$this->vendor_id = $input->get('vendor_id', '', 'INT');
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->input = Factory::getApplication()->input;
		$this->client = $this->input->get('client', '', 'STRING');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		TjvendorsHelper::addSubmenu('vendorfees');
		$this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		$input = Factory::getApplication()->input;

		$state = $this->get('State');
		$canDo = TjvendorsHelper::getActions();

		JToolBarHelper::custom('vendorfees.back', 'chevron-left.png', '', 'COM_TJVENDORS_BACK', false);
		JToolBarHelper::addNew('vendorfee.add');

		if ($canDo->get('core.edit') && isset($this->items[0]))
		{
			JToolBarHelper::editList('vendorfee.edit', 'JTOOLBAR_EDIT');
		}

		$tjvendorFrontHelper = new TjvendorFrontHelper;
		$clientTitle = $tjvendorFrontHelper->getClientName($this->client);
		ToolbarHelper::title($clientTitle . ' : ' . Text::_('COM_TJVENDORS_TITLE_VENDORS_FEES'), 'list.png');

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('vendorfees.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('vendorfees.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tjvendors');
		}

		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_tjvendors&view=vendorfees');

		$this->extra_sidebar = '';
	}

	/**
	 * Method to ord$this->itemer fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'b.`percent_commission`' => Text::_('COM_TJVENDORS_VENDORS_PERCENT_COMMISSION'),
			'b.`flat_commission`' => Text::_('COM_TJVENDORS_VENDORS_FLAT_COMMISSION'),
		);
	}
}
