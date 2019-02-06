<?php
/**
 * @package     TJvendors
 * @subpackage  Actionlog.tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// No direct access.
defined('_JEXEC') or die();
jimport('joomla.application.component.model');

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

/**
 * Privacy plugin managing TJvendor user data
 *
 * @since  3.2.11
 */
class PlgPrivacyTjvendors extends PrivacyPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.3.1
	 */
	protected $db;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   1.3.1
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		$this->loadLanguage();

		return array(
			JText::_('PLG_PRIVACY_TJVENDORS') => array(
				JText::_('PLG_PRIVACY_TJVENDORS_PRIVACY_CAPABILITY_USER_DETAIL')
			)
		);
	}

	/**
	 * Processes an export request for TJvendors user data
	 *
	 * This event will collect data for the following tables:
	 *
	 * - #__tjvendors_vendors
	 * - #__vendor_client_xref
	 * - #__tjvendors_fee
	 * - #__tjvendors_passbook
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 *
	 * @since   1.3.1
	 */
	public function onPrivacyExportRequest(PrivacyTableRequest $request, JUser $user = null)
	{
		if (!$user)
		{
			return array();
		}

		/** @var JTableUser $userTable */
		$userTable = JUser::getTable();
		$userTable->load($user->id);

		// Create the domain for the TJVendors vednor data
		// Vendor related data stored in #__tjvendors_vendors table
		$domains[] = $this->createVendorDomain($userTable);

		// Create the domain for the vendor client data
		// Vendor related data stored in #__vendor_client_xref table
		$domains[] = $this->createVendorClientDomain($userTable);

		// Create the domain for the vendor fees data
		// Vendor related data stored in #__tjvendors_fee table
		$domains[] = $this->createVendorFessDomain($userTable);

		// Create the domain for the vendor passbook data
		// Vendor related data stored in #__tjvendors_passbook table
		$domains[] = $this->createVendorPassbookDomain($userTable);

		return $domains;
	}

	/**
	 * Create the domain for the TJvendor user data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createVendorDomain(JTableUser $user)
	{
		$domain = $this->createDomain('TJvendor user', 'TJvendor user data');

		$query = $this->db->getQuery(true)
			->select('vendor_id, user_id, vendor_title, alias, vendor_description, vendor_logo, state, ordering, checked_out, checked_out_time, params')
			->from($this->db->quoteName('#__tjvendors_vendors'))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $user->id);

		$tjvendorsUserData = $this->db->setQuery($query)->loadAssoc();

		if (!empty($tjvendorsUserData))
		{
			$domain->addItem($this->createItemFromArray($tjvendorsUserData, $tjvendorsUserData['vendor_id']));
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJvendor to get vendor client data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createVendorClientDomain(JTableUser $user)
	{
		$domain = $this->createDomain('TJvendor user client', 'TJvendor user clients data');

		$query = $this->db->getQuery(true)
			->select('vendor_id')
			->from($this->db->quoteName('#__tjvendors_vendors'))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $user->id);

		$vendorId = $this->db->setQuery($query)->loadResult();

		if ($vendorId)
		{
			$query1 = $this->db->getQuery(true)
					->select('id, vendor_id, client, approved, state, params')
					->from($this->db->quoteName('#__vendor_client_xref'))
					->where($this->db->quoteName('vendor_id') . ' = ' . (int) $vendorId);
			$userClientData = $this->db->setQuery($query1)->loadAssocList();

			if (!empty($userClientData))
			{
				foreach ($userClientData as $clientData)
				{
					$domain->addItem($this->createItemFromArray($clientData, $clientData['id']));
				}
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJvendor to get vendor Fee data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createVendorFessDomain(JTableUser $user)
	{
		$domain = $this->createDomain('TJvendor user fees', 'TJvendor user fees data');

		$query = $this->db->getQuery(true)
			->select('vendor_id')
			->from($this->db->quoteName('#__tjvendors_vendors'))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $user->id);

		$vendorId = $this->db->setQuery($query)->loadResult();

		if ($vendorId)
		{
			$query1 = $this->db->getQuery(true)
					->select('id, vendor_id, currency, client, percent_commission, flat_commission')
					->from($this->db->quoteName('#__tjvendors_fee'))
					->where($this->db->quoteName('vendor_id') . ' = ' . (int) $vendorId);
			$userFeesData = $this->db->setQuery($query1)->loadAssocList();

			if (!empty($userFeesData))
			{
				foreach ($userFeesData as $feeData)
				{
					$domain->addItem($this->createItemFromArray($feeData, $feeData['id']));
				}
			}
		}

		return $domain;
	}

	/**
	 * Create the domain for the TJvendor to get vendor passbook data
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since  1.3.1
	 */
	private function createVendorPassbookDomain(JTableUser $user)
	{
		$domain = $this->createDomain('TJvendor user passbook', 'TJvendor user passbook data');

		$query = $this->db->getQuery(true)
			->select('vendor_id')
			->from($this->db->quoteName('#__tjvendors_vendors'))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $user->id);

		$vendorId = $this->db->setQuery($query)->loadResult();

		if ($vendorId)
		{
			$query1 = $this->db->getQuery(true)
					->select('id, vendor_id, currency, total, credit, debit, reference_order_id, transaction_time, client, transaction_id, status, params')
					->from($this->db->quoteName('#__tjvendors_passbook'))
					->where($this->db->quoteName('vendor_id') . ' = ' . (int) $vendorId);
			$userPassbookData = $this->db->setQuery($query1)->loadAssocList();

			if (!empty($userPassbookData))
			{
				foreach ($userPassbookData as $passbookData)
				{
					$domain->addItem($this->createItemFromArray($passbookData, $passbookData['id']));
				}
			}
		}

		return $domain;
	}

	/**
	 * Performs validation to determine if the data associated with a remove information request can be processed
	 *
	 * This event will not allow a super user account to be removed
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyRemovalStatus
	 *
	 * @since   1.3.1
	 */
	public function onPrivacyCanRemoveData(PrivacyTableRequest $request, JUser $user = null)
	{
		$status = new PrivacyRemovalStatus;

		if (!$user)
		{
			return $status;
		}

		$query = $this->db->getQuery(true)
			->select('vendor_id')
			->from($this->db->quoteName('#__tjvendors_vendors'))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $user->id);

		$vendorId = $this->db->setQuery($query)->loadResult();

		if ($vendorId)
		{
			$status->canRemove = false;
			$status->reason    = JText::_('PLG_PRIVACY_TJVENDORS_ERROR_CANNOT_REMOVE_USER_DATA');
		}

		return $status;
	}
}
