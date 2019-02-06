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

	/** @var array The list of extra modules and plugins to install */
	private $queue = array(

	// plugins => { (folder) => { (element) => (published) }* }*
	'plugins' => array(
						'actionlog' => array('tjvendors' => 1),
						'privacy'   => array('tjvendors' => 1)
					)
				);

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
		// Payment gateway migration
		$this->updatePaymentGatewayConfig();
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   array   $type    data
	 *
	 * @param   object  $parent  data
	 *
	 * @return void
	 */
	public function postflight( $type, $parent )
	{
		// Write template file for email template
		$this->_insertTjNotificationTemplates();

		// Add default permissions
		$this->defaultPermissionsFix();

		// Install Layouts
		$this->_addLayout($parent);

		// Install plugins
		$this->_installPlugins($parent);
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
	 * This method is called after a component is uninstalled.
	 *
	 * @param   \stdClass  $parent  Parent object calling this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
		jimport('joomla.installer.installer');

		$db = JFactory::getDBO();

		$status          = new JObject;
		$status->plugins = array();

		$src = $parent->getParent()->getPath('source');

		// Plugins uninstallation
		if (count($this->queue['plugins']))
		{
			foreach ($this->queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $db->getQuery(true)->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('type') . ' = ' . $db->q('plugin'))
						->where($db->qn('element') . ' = ' . $db->q($plugin))
						->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new JInstaller;
							$result            = $installer->uninstall('plugin', $id);
							$status->plugins[] = array(
								'name' => 'plg_' . $plugin,
								'group' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * This method is called after a component is install to install plugins.
	 *
	 * @param   \stdClass  $parent  Parent object calling this method.
	 *
	 * @return void
	 */
	public function _installPlugins($parent)
	{
		jimport('joomla.installer.installer');
		$src = $parent->getParent()->getPath('source');

		$db = JFactory::getDbo();

		$status = new JObject;
		$status->plugins = array();

		// Plugins installation
		if (count($this->queue['plugins']))
		{
			foreach ($this->queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (!is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}

						if (!is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $db->getQuery(true)
							->select('COUNT(*)')
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$count = $db->loadResult();

						$installer = new JInstaller;
						$result = $installer->install($path);

						$status->plugins[] = array('name' => 'plg_' . $plugin, 'group' => $folder, 'result' => $result);

						if ($published && !$count)
						{
							$query = $db->getQuery(true)
								->update($db->qn('#__extensions'))
								->set($db->qn('enabled') . ' = ' . $db->q('1'))
								->where($db->qn('element') . ' = ' . $db->q($plugin))
								->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}
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

	/**
	 * Override the Modules
	 *
	 * @return  boolean
	 *
	 * @since   1.3.0
	 */
	public function updatePaymentGatewayConfig()
	{
		$db = JFactory::getDBO();
		$config   = JFactory::getConfig();
		$dbprefix = $config->get('dbprefix');
		$query = "SHOW TABLES LIKE '" . $dbprefix . "vendor_client_xref';";
		$db->setQuery($query);
		$tableExists = $db->loadResult();

		if (empty($tableExists))
		{
			return false;
		}

		$query = "SHOW COLUMNS FROM `#__vendor_client_xref` LIKE 'payment_gateway'";
		$db->setQuery($query);
		$result = $db->loadResult();

		if (!isset($result))
		{
			return false;
		}

		$query = $db->getQuery(true);
		$query->select(array('*'));
		$query->from($db->quoteName('#__vendor_client_xref'));
		$query->where($db->quoteName('payment_gateway') . "=" . "'paypal'", 'OR');
		$query->where($db->quoteName('payment_gateway') . "=" . "'adaptive_paypal'");
		$db->setQuery($query);
		$vendorList = $db->loadObjectList();

		foreach ($vendorList as $key => $value)
		{
			$param1 = new stdClass;
			$param1->payment_gateways = $value->payment_gateway;

			$param2 = json_decode($value->params);

			$params = (object) array_merge((array) $param1, (array) $param2);

			$paymentArray = array();
			$paymentArray['payment_gateway0'] = $params;
			$paymentArrayList['payment_gateway'] = $paymentArray;

			$vendorParams = json_encode($paymentArrayList);

			$vendorData = new stdClass;
			$vendorData->id        = $value->id;
			$vendorData->vendor_id = $value->vendor_id;
			$vendorData->params    = $vendorParams;

			JFactory::getDbo()->updateObject('#__vendor_client_xref', $vendorData, 'id');
		}

		return true;
	}

	/**
	 * Add subform layout for payment form
	 *
	 * @param   object  $parent  table name
	 *
	 * @return  void
	 */
	private function _addLayout($parent)
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		$src = $parent->getParent()->getPath('source');
		$VendorSubformLayouts = $src . "/layouts/com_tjvendors";

		if (JFolder::exists(JPATH_SITE . '/layouts/com_tjvendors'))
		{
			JFolder::delete(JPATH_SITE . '/layouts/com_tjvendors');
		}

		JFolder::copy($VendorSubformLayouts, JPATH_SITE . '/layouts/com_tjvendors');
	}
}
