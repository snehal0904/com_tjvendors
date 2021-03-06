<?php
/**
 * @version    SVN:
 * @package    Com_Tjvendors
 * @author     Techjoomla  <contact@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.controller');


/**
 * script for migration
 *
 * @package  TJvendor
 *
 * @since    1.0
 */
class Com_TjvendorsInstallerScript
{
	// Used to identify new install or update
	private $componentStatus = "install";

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   array  $type    data
	 *
	 * @param   array  $parent  data
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   array  $type    data
	 *
	 * @param   array  $parent  data
	 *
	 * @return void
	 */
	public function postflight( $type, $parent )
	{
		// Write template file for email template
		$this->_insertTjNotificationTemplates();

		// Add default permissions
		$this->defaultPermissionsFix();
	}

	/**
	 * method to install the component
	 *
	 * @param   array  $parent  data
	 *
	 * @return void
	 */
	public function install($parent)
	{
		$this->installSqlFiles($parent);
	}

	/**
	 * method to update the component
	 *
	 * @param   array  $parent  data
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$this->componentStatus = "update";
		$this->installSqlFiles($parent);
		$check = $this->checkTableExists('tj_vendors');

		if ($check)
		{
			$oldVendorsData = $this->getOldData();

			if (!empty($oldVendorsData))
			{
				$this->updateData();
			}
		}
	}

	/**
	 * method to install the sql files
	 *
	 * @param   array  $parent  data
	 *
	 * @return void
	 */
	public function installSqlFiles($parent)
	{
		$db = JFactory::getDBO();

		// Lets create the table
		$this->runSQL($parent, 'install.mysql.utf8.sql');
	}

	/**
	 * method to run the sql
	 *
	 * @param   array  $parent   data
	 *
	 * @param   array  $sqlfile  data
	 *
	 * @return void
	 */
	public function runSQL($parent,$sqlfile)
	{
		$db = JFactory::getDBO();

		// Obviously you may have to change the path and name if your installation SQL file ;)
		if (method_exists($parent, 'extension_root'))
		{
			$sqlfile = $parent->getPath('extension_root') . '/administrator/sql/' . $sqlfile;
		}
		else
		{
			$sqlfile = $parent->getParent()->getPath('extension_root') . '/sql/' . $sqlfile;
		}

		// Don't modify below this line
		$buffer = file_get_contents($sqlfile);

		if ($buffer !== false)
		{
			jimport('joomla.installer.helper');
			$queries = JInstallerHelper::splitSql($buffer);

			if (count($queries) != 0)
			{
				foreach ($queries as $query)
				{
					$query = trim($query);

					if ($query != '' && $query{0} != '#')
					{
						$db->setQuery($query);

						if (!$db->query())
						{
							JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));

							return false;
						}
					}
				}
			}
		}
	}

	/**
	 * method to migrate the old data
	 *
	 * @return boolean
	 */
	public function updateData()
	{
		$oldVendorsData = $this->getOldData();

		if (!empty($oldVendorsData))
		{
			foreach ($oldVendorsData as $oldData)
			{
				$com_params = JComponentHelper::getParams('com_jticketing');
				$currency = $com_params->get('currency');
				$newVendorData = new stdClass;
				$newVendorData->user_id = $oldData->user_id;
				$newVendorData->vendor_id = $oldData->id;
				$newVendorData->state = 1;
				$newVendorData->vendor_title = JFactory::getUser($oldData->user_id)->name;
				$result = JFactory::getDbo()->insertObject('#__tjvendors_vendors', $newVendorData);

				$newXrefData = new stdClass;
				$newXrefData->vendor_id = $oldData->id;
				$newXrefData->id = $oldData->id;
				$newXrefData->client = 'com_jticketing';
				$result = JFactory::getDbo()->insertObject('#__vendor_client_xref', $newXrefData);

				$newFeeData = new stdClass;
				$newFeeData->vendor_id = $oldData->id;
				$newFeeData->id = $oldData->id;
				$newFeeData->client = 'com_jticketing';
				$newFeeData->currency = $currency;
				$newFeeData->percent_commission = $oldData->percent_commission;
				$newFeeData->flat_commission = $oldData->flat_commission;
				$result = JFactory::getDbo()->insertObject('#__tjvendors_fee', $newFeeData);
			}

			return true;
		}
	}

	/**
	 * method to check if the old table exists
	 *
	 * @param   string  $table  table name
	 *
	 * @return boolean
	 */
	public function checkTableExists($table)
	{
		$db = JFactory::getDBO();
		$config = JFactory::getConfig();

		$dbname = $config->get('db');
		$dbprefix = $config->get('dbprefix');

		$query = $db->getQuery(true);
		$query->select($db->quoteName('table_name'));
		$query->from($db->quoteName('information_schema.tables'));
		$query->where($db->quoteName('table_schema') . ' = ' . $db->quote($dbname));
		$query->where($db->quoteName('table_name') . ' = ' . $db->quote($dbprefix . $table));
		$db->setQuery($query);
		$check = $db->loadResult();

		if ($check)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * method to get old data
	 *
	 * @return object
	 */
	public function getOldData()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tj_vendors'));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Installed Notifications
	 * method to install default email templates
	 *
	 * @return  void
	 */
	public function _insertTjNotificationTemplates()
	{
		jimport('joomla.application.component.model');
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/tables');

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('key'));
		$query->from($db->quoteName('#__tj_notification_templates'));
		$query->where($db->quoteName('client') . ' = ' . $db->quote("com_tjvendors"));
		$db->setQuery($query);
		$existingKeys = $db->loadColumn();

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/models');
		$notificationsModel = JModelLegacy::getInstance('Notification', 'TJNotificationsModel');

		$filePath = JPATH_ADMINISTRATOR . '/components/com_tjvendors/tjvendorsTemplate.json';
		$str = file_get_contents($filePath);
		$json = json_decode($str, true);

		$app   = JFactory::getApplication();

		if (count($json) != 0)
		{
			foreach ($json as $template => $array)
			{
				if (!in_array($array['key'], $existingKeys))
				{
					$notificationsModel->createTemplates($array);
				}
			}
		}
	}

	/**
	 * Add default ACL permissions
	 *
	 * @return  void
	 */
	public function defaultPermissionsFix()
	{
		$db = JFactory::getDbo();
		$columnArray = array('id', 'rules');
		$query = $db->getQuery(true);

		$query->select($db->quoteName($columnArray));
		$query->from($db->quoteName('#__assets'));
		$query->where($db->quoteName('name') . ' = ' . $db->quote('com_tjvendors'));
		$db->setQuery($query);

		try
		{
			$result = $db->loadobject();
			$obj = new Stdclass;
			$obj->id = $result->id;
			$obj->rules = '{"core.edit.own":{"1":1,"2":1,"7":1},"core.edit":{"7":0},"core.create":{"7":1,"2":1},"core.delete":{"7":1},"core.edit.state":{"7":1}}';

			$db->updateObject('#__assets', $obj, 'id');
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_TJVENDORS_DB_EXCEPTION_WARNING_MESSAGE'), 'error');
		}
	}
}
