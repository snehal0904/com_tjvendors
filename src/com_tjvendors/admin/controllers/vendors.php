<?php
/**
 * @package     TJVendors
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\AdminController;

jimport('joomla.application.component.controllerform');
JLoader::register('TjControllerHouseKeeping', JPATH_SITE . "/libraries/techjoomla/controller/houseKeeping.php");

/**
 * Vendors list controller class.
 *
 * @since  1.6
 */
class TjvendorsControllerVendors extends AdminController
{
	use TjControllerHouseKeeping;

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since    1.6
	 */
	public function getModel($name = 'vendor', $prefix = 'TjvendorsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method for delete vendor
	 *
	 * @return  boolean
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$input  = Factory::getApplication()->input;
		$client = $input->get('client', '', 'STRING');
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		$model = $this->getModel("vendors");

		foreach ($cid as $vendor_id)
		{
			$model->deleteClientFromVendor($vendor_id, $client);
		}

		$redirect = 'index.php?option=com_tjvendors&view=vendors&client=' . $client;
		$this->setRedirect($redirect);
	}

	/**
	 * Function to publish vendors
	 *
	 * @return  void
	 */
	public function publish()
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$client = $input->get('client', '', 'STRING');
		$cid = Factory::getApplication()->input->get('cid', array(), 'array');
		$data = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
		$task = $this->getTask();
		$value = JArrayHelper::getValue($data, $task, 0, 'int');

		// Get some variables from the request

		if (empty($cid))
		{
			Log::add(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), Log::WARNING, 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel('vendors');

			// Make sure the item ids are integers
			JArrayHelper::toInteger($cid);

			// Publish the items.
			try
			{
				$model->setItemState($cid, $value, $client);

				if ($value == 1)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_PUBLISHED';
				}
				elseif ($value == 0)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_UNPUBLISHED';
				}

				$this->setMessage(Text::plural($ntext, count($cid)));
			}
			catch (Exception $e)
			{
				$this->setMessage(Text::_('JLIB_DATABASE_ERROR_ANCESTOR_NODES_LOWER_STATE'), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_tjvendors&view=vendors&client=' . $client);
	}
}
