<?php
/**
 * @package     TJvendors
 * @subpackage  com_tjvendors
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * TJvendors email notification migrations
 *
 * @since  1.4.2
 */
class TjHouseKeepingEmailTemplate extends TjModelHouseKeeping
{
	public $title       = "Migrate email notification templates";

	public $description = "Replace single curly brackets with double curly brackets in email notification templates";

	/**
	 * This function will replace single curly brackets with double curly brackets in email notification templates
	 *
	 * @return  array $result
	 *
	 * @since  1.4.2
	 */
	public function migrate()
	{
		$result = array();

		try
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjnotifications/tables');

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select("temp.id as tempId");
			$query->select("conf.id as confId");
			$query->select("conf.subject");
			$query->select("conf.body");
			$query->from($db->qn('#__tj_notification_templates', 'temp'));
			$query->join('LEFT', $db->qn('#__tj_notification_template_configs', 'conf') .
			' ON (' . $db->qn('temp.id') . ' = ' . $db->qn('conf.template_id') . ')');
			$query->where($db->qn('temp.client') . ' = ' . $db->quote("com_tjvendors"));
			$query->where($db->qn('conf.backend') . ' = ' . $db->quote("email"));
			$db->setQuery($query);
			$emailArray = $db->loadAssocList();

			if (!empty($emailArray))
			{
				foreach ($emailArray as $key => $value)
				{
					if ((!empty($value['subject'])) || (!empty($value['body'])))
					{
						$table   = Table::getInstance('Template', 'TjnotificationTable', array());
						$obj     = new stdClass;
						$obj->id = $value['confId'];

						if (strpos($value['subject'], '{{') === false)
						{
							$subjectBracketReplace  = str_replace("{", "{{", $value['subject']);
							$subjectBracketReplace  = str_replace("}", "}}", $subjectBracketReplace);
							$obj->subject           = $subjectBracketReplace;
						}

						if (strpos($value['body'], '{{') === false)
						{
							$bodyBracketReplace  = str_replace("{", "{{", $value['body']);
							$bodyBracketReplace  = str_replace("}", "}}", $bodyBracketReplace);
							$obj->body           = $bodyBracketReplace;
						}

						$table->save($obj);
					}
				}

				$result['status']  = true;
				$result['message'] = "Migration is done successfully";

				return $result;
			}
		}
		catch (Exception $e)
		{
			$result['err_code'] = '';
			$result['status']   = false;
			$result['message']  = $e->getMessage();
		}

		return $result;
	}
}
