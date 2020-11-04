<?php
/**
 * @package     TJVendors
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

jimport('techjoomla.tjnotifications.tjnotifications');
include_once JPATH_SITE . '/components/com_tjvendors/includes/tjvendors.php';

/**
 * Class TjvendorMailsHelper
 *
 * @since  2.1
 */
class TjvendorsMailsHelper
{
	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$app = Factory::getApplication();
		$this->tjvendorsparams = ComponentHelper::getParams('com_tjvendors');
		$this->siteConfig = Factory::getConfig();
		$this->sitename = $this->siteConfig->get('sitename');
		$this->siteadminname = $this->siteConfig->get('fromname');
		$this->user = Factory::getUser();
		$this->client = "com_tjvendors";
		$this->tjnotifications = new Tjnotifications;
		$this->siteinfo = new stdClass;
		$this->siteinfo->sitename	= $this->sitename;
		$this->siteinfo->adminname = Text::_('COM_TJVENDORS_SITEADMIN');

		JLoader::import('components.com_tjvendors.helpers.fronthelper', JPATH_SITE);
		$this->tjvendorFrontHelper = new TjvendorFrontHelper;
	}

	/**
	 * Send mails when campaign is created
	 *
	 * @param   OBJECT  $vendorDetails  Vender Detail
	 *
	 * @return void
	 */
	public function onAfterVendorCreate($vendorDetails)
	{
		$adminRecipients = array();
		$adminUsers = TJVendors::utilities()->getAdminUsers();

		foreach ($adminUsers as $user)
		{
			$adminRecipients['email']['to'][] = $user->email;
		}

		$adminEmail = $this->tjvendorsparams->get('email');

		$adminEmailArray = explode(',', $adminEmail);
		$adminRecipients['email']['cc'] = $adminEmailArray;
		$userIdArray = $this->getUserIdFromEmail($adminEmailArray);

		foreach ($userIdArray as $userId)
		{
			array_unshift($adminRecipients,Factory::getUser($userId));
		}

		$vendor_approval = $this->tjvendorsparams->get('vendor_approval');
		$adminkey = ($vendor_approval) ? "createVendorMailToAdminWaitingForApproval" : "createVendorMailToAdmin";
		$vendorerkey = ($vendor_approval) ? "createVendorMailToOwnerWaitingForApproval" : "createVendorMailToOwner";

		$vendorer = Factory::getUser($vendorDetails->user_id);
		$promoterEmailArray = array();
		$promoterContactArray = array();
		$promoterEmail = $vendorer->email;
		$promoterEmailArray[] = $promoterEmail;

		if (!empty($vendorDetails->phone_number))
		{
			$promoterContactArray[] = $vendorDetails->phone_number;
		}

		$promoterRecipients = array('email' => array('to' => $promoterEmailArray));

		if (!empty($promoterContactArray))
		{
			$promoterRecipients['sms'] = $promoterContactArray;
		}

		$allVendors = 'index.php?option=com_tjvendors&view=vendors&client=' . $vendorDetails->vendor_client;
		$allVendorsLink = Uri::root() . 'administrator/' . $allVendors;
		$vendorDetails->allVendors = $allVendorsLink;

		$vendorItemID = $this->tjvendorFrontHelper->getItemId(
		'index.php?option=com_tjvendors&view=vendor&layout=edit&client=' . $vendorDetails->vendor_client
		);
		$myVendor = 'index.php?option=com_tjvendors&view=vendor&layout=edit&client='
		. $vendorDetails->vendor_client . '&vendor_id=' . $vendorDetails->vendor_id . '&Itemid=' . $vendorItemID;
		$myVendorLink = Uri::root() . substr(Route::_($myVendor), strlen(Uri::base(true)) + 1);
		$vendorDetails->myVendor = $myVendorLink;

		$replacements = new stdClass;
		$vendorDetails->sitename = $this->sitename;
		$vendorDetails->adminname = Text::_('COM_TJVENDORS_SITEADMIN');
		$replacements->info = $vendorDetails;
		$replacements->vendorer = $vendorer;

		$ccMail = $this->siteConfig->get('mailfrom');
		$options = new Registry;
		$options->set('info', $vendorDetails);

		// Mail to site admin
		$this->tjnotifications->send($this->client, $adminkey, $adminRecipients, $replacements, $options);

		// Mail to Promoter
		$this->tjnotifications->send($this->client, $vendorerkey, $promoterRecipients, $replacements, $options);

		return;
	}

	/**
	 * Send mails when vendor is editted
	 *
	 * @param   OBJECT  $vendorDetails  vendor details
	 *
	 * @return void
	 */
	public function onAfterVendorEdit($vendorDetails)
	{
		$replacements = new stdClass;
		$vendorDetails->sitename = $this->sitename;
		$vendorDetails->adminname = Text::_('COM_TJVENDORS_SITEADMIN');
		$loggedInUser = Factory::getUser()->id;

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjvendors/tables');
		$vendorData = Table::getInstance('Vendorclientxref', 'TjvendorsTable');
		$vendorData->load(array('vendor_id' => $vendorDetails->vendor_id));
		$vendor_client = $vendorData->client;

		$vendorDetails->vendorClient = $this->tjvendorFrontHelper->getClientName($vendor_client);
		$replacements->info = $vendorDetails;
		$replacements->vendorer = Factory::getUser($vendorDetails->user_id);

		$ccMail = $this->siteConfig->get('mailfrom');
		$options = new Registry;
		$options->set('info', $vendorDetails);

		$vendor_approval = $this->tjvendorsparams->get('vendor_approval');

		// Find admin has approved vendor, and add a new key
		if ($vendor_approval && $vendorDetails->approved == 1)
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjvendors/tables');
			$vendorData = Table::getInstance('Vendor', 'TjvendorsTable');
			$vendorData->load(array('vendor_id' => $vendorDetails->vendor_id));
			$vendorUserDetails = Factory::getUser($vendorData->user_id);

			$approvalkey = "approvalOnVendorMailToOwner";
			$promoterEmailArray = array();
			$promoterContactArray = array();
			$promoterEmail = $vendorUserDetails->email;
			$promoterEmailArray[] = $promoterEmail;

			if (!empty($vendorDetails->phone_number))
			{
				$promoterContactArray[] = $vendorDetails->phone_number;
			}

			$promoterRecipients = array('email' => array('to' => $promoterEmailArray));

			if (!empty($promoterContactArray))
			{
				$promoterRecipients['sms'] = $promoterContactArray;
			}

			$replacements->vendor_user = $vendorUserDetails;
			$replacements->vendor_data = $vendorData;
			$options->set('vendor_data', $vendorData);

			$this->tjnotifications->send($this->client, $approvalkey, $promoterRecipients, $replacements, $options);
		}
		elseif ($vendorDetails->user_id === $loggedInUser)
		{
			$adminRecipients = array();
			$adminUsers = TJVendors::utilities()->getAdminUsers();

			foreach ($adminUsers as $user)
			{
				$adminRecipients['email']['to'][] = $user->email;
			}

			$adminEmail      = $this->tjvendorsparams->get('email');
			$adminEmailArray = explode(',', $adminEmail);
			$adminRecipients['email']['cc'] = $adminEmailArray;

			foreach ($adminUsers as $user)
			{
				array_unshift($adminRecipients, Factory::getUser($user->id));
			}

			$adminkey = "editVendorMailToAdmin";

			$this->tjnotifications->send($this->client, $adminkey, $adminRecipients, $replacements, $options);
		}

		return;
	}

	/**
	 * Method to create recipient array
	 *
	 * @param   ARRAY  $emailObject  Contains email object
	 *
	 * @return  array  User Id Array
	 *
	 * @since	1.4.2
	 */
	public function getUserIdFromEmail($adminRecipients)
	{
		$finalUserIdRecipient = [];

		if (!empty($adminRecipients))
		{
			$db = JFactory::getDbo();

			foreach ($adminRecipients as $adminRecipient)
			{
				$query = $db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__users'))
					->where($db->quoteName('email') . ' = ' . $db->quote($adminRecipient));
				$db->setQuery($query);
				$userId = $db->loadResult();

				$finalUserIdRecipient[] = $userId;
			}
		}

		return $finalUserIdRecipient;
	}
}
