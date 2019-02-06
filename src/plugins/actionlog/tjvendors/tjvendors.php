<?php
/**
 * @package     TJVendors
 * @subpackage  Actionlog.tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

JLoader::register('ActionlogsHelper', JPATH_ADMINISTRATOR . '/components/com_actionlogs/helpers/actionlogs.php');
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjvendors/tables');

/**
 * TJVendors Actions Logging Plugin.
 *
 * @since  1.3.1
 */
class PlgActionlogTjvendors extends JPlugin
{
	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  1.3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * On saving vendor data logging method
	 *
	 * Method is called after vendor data is stored in the database.
	 *
	 * @param   Object   $vendorData  Holds the vendor data.
	 * @param   boolean  $isNew       True if a new vendor is stored.
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	public function tjVendorsOnAfterVendorSave($vendorData, $isNew)
	{
		if (!$this->params->get('logActionForVendorSave', 1))
		{
			return;
		}

		$app      = JFactory::getApplication();
		$context  = $app->input->get('option');
		$jUser    = JFactory::getUser();
		$userId   = $jUser->id;
		$userName = $jUser->username;

		if ($vendorData['vendor_client'])
		{
			$language = JFactory::getLanguage();
			$language->load($vendorData['vendor_client']);
		}

		// If admin create user as vendor from backend
		if ($app->isAdmin())
		{
			$vendorInfor    = JFactory::getUser($vendorData['user_id']);
			$vendorID       = $vendorInfor->id;
			$vendorUserName = $vendorInfor->username;
			$action         = ($isNew) ? 'add' : 'update';

			// Create New Vendor
			if ($isNew)
			{
				$messageLanguageKey = ($vendorData['vendor_client']) ? 'PLG_ACTIONLOG_TJVENDORS_VENDOR_CLIENT_SAVE' : 'PLG_ACTIONLOG_TJVENDORS_VENDOR_SAVE';

				if ($vendorData['vendor_client'])
				{
					$message = array(
						'action'         => $action,
						'type'           => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
						'clientname'     => JText::_(strtoupper($vendorData['vendor_client'])),
						'clientlink'     => 'index.php?option=' . $vendorData['vendor_client'],
						'vendorusername' => $vendorUserName,
						'vendoruserlink' => 'index.php?option=com_users&task=user.edit&id=' . $vendorID,
						'userid'         => $userId,
						'username'       => $userName,
						'accountlink'    => 'index.php?option=com_users&task=user.edit&id=' . $userId,
					);
				}
				else
				{
					$message = array(
						'action'         => $action,
						'type'           => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
						'vendorusername' => $vendorUserName,
						'vendoruserlink' => 'index.php?option=com_users&task=user.edit&id=' . $vendorID,
						'userid'         => $userId,
						'username'       => $userName,
						'accountlink'    => 'index.php?option=com_users&task=user.edit&id=' . $userId,
					);
				}
			}
			// Edit Vendor
			else
			{
				$messageLanguageKey = 'PLG_ACTIONLOG_TJVENDORS_VENDOR_CLIENT_UPDATE';
				$message = array(
					'action'           => $action,
					'type'             => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
					'clientname'       => JText::_(strtoupper($vendorData['vendor_client'])),
					'clientlink'       => 'index.php?option=' . $vendorData['vendor_client'],
					'vendorname'	   => $vendorData['vendor_title'],
					'vendorusername'   => $vendorUserName,
					'vendoruserlink'   => 'index.php?option=com_users&task=user.edit&id=' . $vendorID,
					'userid'           => $userId,
					'username'         => $userName,
					'accountlink'      => 'index.php?option=com_users&task=user.edit&id=' . $userId,
				);
			}
		}
		// If register user create vendor profile for self from frontend
		else
		{
			$messageLanguageKey = ($isNew) ? 'PLG_ACTIONLOG_TJVENDORS_REGISTER_VENDOR_SAVE' : 'PLG_ACTIONLOG_TJVENDORS_REGISTER_VENDOR_UPDATE';
			$action = ($isNew) ? 'add' : 'update';

			if ($isNew)
			{
				$message = array(
					'action'      => $action,
					'type'        => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
					'clientname'  => JText::_(strtoupper($vendorData['vendor_client'])),
					'clientlink'  => 'index.php?option=' . $vendorData['vendor_client'],
					'userid'      => $userId,
					'username'    => $userName,
					'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
				);
			}
			else
			{
				$message = array(
					'action'      => $action,
					'type'        => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
					'clientname'  => JText::_(strtoupper($vendorData['vendor_client'])),
					'clientlink'  => 'index.php?option=' . $vendorData['vendor_client'],
					'userid'      => $userId,
					'username'    => $userName,
					'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
				);
			}
		}

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On after changing vendor state logging method
	 *
	 * Method is called after vendor state is changed from  the database.
	 *
	 * @param   Array    $pks     Holds the vendors id
	 * @param   Integer  $state   0-indicate unpublish 1-indicate publish.
	 * @param   String   $client  Client like com_jgive or com_jticketing
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	public function tjVendorsOnAfterVendorStateChange($pks, $state, $client)
	{
		if (!$this->params->get('logActionForVendorStateChange', 1))
		{
			return;
		}

		$jUser                = JFactory::getUser();
		$userId               = $jUser->id;
		$userName             = $jUser->username;
		$context              = JFactory::getApplication()->input->get('option');
		$tjvendorsTableVendor = JTable::getInstance('vendor', 'TjvendorsTable', array());
		$language = JFactory::getLanguage();
		$language->load($client);

		switch ($state)
		{
			case 0:
				$messageLanguageKey = 'PLG_ACTIONLOG_TJVENDORS_VENDOR_UNPUBLISHED';
				$action             = 'unpublish';
				break;
			case 1:
				$messageLanguageKey = 'PLG_ACTIONLOG_TJVENDORS_VENDOR_PUBLISHED';
				$action             = 'publish';
				break;
			default:
				$messageLanguageKey = '';
				$action             = '';
				break;
		}

		foreach ($pks as $vendorID)
		{
			$tjvendorsTableVendor->load(array('vendor_id' => $vendorID));

			$message = array(
				'action'       => $action,
				'type'         => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
				'vendorname'   => $tjvendorsTableVendor->vendor_title,
				'vendorid'     => $tjvendorsTableVendor->user_id,
				'vendorlink'   => 'index.php?option=com_users&task=user.edit&id=' . $tjvendorsTableVendor->user_id,
				'clientname'   => JText::_(strtoupper($client)),
				'clientlink'   => 'index.php?option=' . $client,
				'userid'       => $userId,
				'username'     => $userName,
				'accountlink'  => 'index.php?option=com_users&task=user.edit&id=' . $userId,
			);

			$this->addLog(array($message), $messageLanguageKey, $context, $userId);
		}
	}

	/**
	 * On after deleting vendor data logging method
	 *
	 * Method is called after vendor data is deleted from  the database.
	 *
	 * @param   Object  $vendorData  Holds the vendor data.
	 * @param   String  $client      com_jgive, com_jticketing
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	public function tjvendorOnAfterVendorDelete($vendorData, $client)
	{
		if (!$this->params->get('logActionForVendorDelete', 1))
		{
			return;
		}

		$context            = JFactory::getApplication()->input->get('option');
		$jUser              = JFactory::getUser();
		$messageLanguageKey = 'PLG_ACTIONLOG_TJVENDORS_VENDOR_DELETED';
		$action             = 'delete';
		$userId             = $jUser->id;
		$userName           = $jUser->username;

		if ($client)
		{
			$language = JFactory::getLanguage();
			$language->load($client);
		}

		$message = array(
			'action'      => $action,
			'type'        => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
			'vendorname'  => $vendorData->vendor_title,
			'clientname'  => JText::_(strtoupper($client)),
			'clientlink'  => 'index.php?option=' . $client,
			'userid'      => $userId,
			'username'    => $userName,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * On saving fee against vendor logging method
	 *
	 * Method is called after fee data is stored against vendor in the database.
	 *
	 * @param   Object   $vendorFeeData  Holds the vendor fee data.
	 * @param   boolean  $isNew          True if a new vendor is stored.
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	public function tjVendorsOnAfterVendorFeeSave($vendorFeeData, $isNew)
	{
		if (!$this->params->get('logActionForVendorFeeSave', 1))
		{
			return;
		}

		$context              = JFactory::getApplication()->input->get('option');
		$jUser                = JFactory::getUser();
		$messageLanguageKey   = ($isNew) ? 'PLG_ACTIONLOG_TJVENDORS_VENDOR_FEE_SAVE' : 'PLG_ACTIONLOG_TJVENDORS_VENDOR_FEE_UPDATE';
		$action               = ($isNew) ? 'add' : 'update';
		$userId               = $jUser->id;
		$userName             = $jUser->username;
		$tjvendorsTableVendor = JTable::getInstance('vendor', 'TjvendorsTable', array());
		$tjvendorsTableVendor->load(array('vendor_id' => $vendorFeeData['vendor_id']));

		if ($vendorFeeData['client'])
		{
			$language = JFactory::getLanguage();
			$language->load($vendorFeeData['client']);
		}

		$message = array(
			'action'      => $action,
			'type'        => 'PLG_ACTIONLOG_TJVENDORS_TYPE_VENDOR',
			'vendorname'  => $vendorFeeData['vendor_title'],
			'vendorlink'  => 'index.php?option=com_users&task=user.edit&id=' . $tjvendorsTableVendor->user_id,
			'feelink'     => 'index.php?option=com_tjvendors&view=vendorfee&layout=edit&id=' . $vendorFeeData['id'] . '&vendor_id=' . $vendorFeeData['vendor_id'] . '&client=' . $vendorFeeData['client'],
			'clientlink'  => 'index.php?option=' . $vendorFeeData['client'],
			'clientname'  => JText::_(strtoupper($vendorFeeData['client'])),
			'userid'      => $userId,
			'username'    => $userName,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $userId);
	}

	/**
	 * Proxy for ActionlogsModelUserlog addLog method
	 *
	 * This method adds a record to #__action_logs contains (message_language_key, message, date, context, user)
	 *
	 * @param   array   $messages            The contents of the messages to be logged
	 * @param   string  $messageLanguageKey  The language key of the message
	 * @param   string  $context             The context of the content passed to the plugin
	 * @param   int     $userId              ID of user perform the action, usually ID of current logged in user
	 *
	 * @return  void
	 *
	 * @since   1.3.1
	 */
	protected function addLog($messages, $messageLanguageKey, $context, $userId = null)
	{
		JLoader::register('ActionlogsModelActionlog', JPATH_ADMINISTRATOR . '/components/com_actionlogs/models/actionlog.php');

		/* @var ActionlogsModelActionlog $model */
		$model = JModelLegacy::getInstance('Actionlog', 'ActionlogsModel');
		$model->addLog($messages, $messageLanguageKey, $context, $userId);
	}
}
