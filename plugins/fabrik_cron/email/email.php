<?php
/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractCronPlugin;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;

/**
 * A cron task to email records to a give set of users
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.email
 * @since       3.0
 */
class PlgFabrik_Cronemail extends AbstractCronPlugin
{
	/**
	 * Check if the user can use the plugin
	 *
	 * @param   string $location To trigger plugin on
	 * @param   string $event    To trigger plugin on
	 *
	 * @return  bool can use or not
	 *
	 * @since 4.0
	 */
	public function canUse($location = null, $event = null)
	{
		return true;
	}

	/**
	 * Do the plugin action
	 *
	 * @param   array &$data data
	 * @param   ListModel  $listModel  List model
	 * @return  int  number of records updated
	 *
	 * @since 4.0
	 */
	public function process(&$data, ListModel $listModel)
	{
		$params = $this->getParams();
		$msg    = $params->get('message');
		Html::runContentPlugins($msg, false);
		$to = explode(',', $params->get('to'));

		$w = new Worker;
		($params->get('cronemail_return', '') != '') ? $MailFrom = $params->get('cronemail_return') : $MailFrom = $this->app->get('mailfrom');
		($params->get('cronemail_from', '') != '') ? $FromName = $params->get('cronemail_from') : $FromName = $this->app->get('fromname');
		($params->get('cronemail_replyto', '') != '') ? $replyTo = $params->get('cronemail_replyto') : $replyTo = $this->app->get('replyto');
		($params->get('cronemail_replytoname', '') != '') ? $replyToName = $params->get('cronemail_replytoname') : $replyToName = $this->app->get('replytoname');
		$subject   = $params->get('subject', 'Fabrik cron job');
		$eval      = $params->get('cronemail-eval');
		$condition = $params->get('cronemail_condition', '');
		$nodups    = $params->get('cronemail_no_dups', '0') === '1';
		$testMode  = $this->isTestMode();
		$sentIds   = array();
		$failedIds   = array();
		$sentTos = array();
		$this->log = '';
		$x = 0;

		foreach ($data as $group)
		{
			if (is_array($group))
			{
				foreach ($group as $row)
				{
					$x++;
					$row = ArrayHelper::fromObject($row);

					if (!empty($condition))
					{
						$this_condition = $w->parseMessageForPlaceHolder($condition, $row);

						if (eval($this_condition) === false)
						{
							if ($testMode)
							{
								$this->app->enqueueMessage($x . ': Condition returned false');
							}

							continue;
						}
					}

					foreach ($to as $thisTo)
					{
						$thisTo = trim($w->parseMessageForPlaceHolder($thisTo, $row));

						if ($nodups)
						{
							if (in_array($thisTo, $sentTos))
							{
								if ($testMode)
								{
									$this->app->enqueueMessage($x . ': Found dupe, skipping: ' . $thisTo);
								}

								continue;
							}
							else
							{
								$sentTos[] = $thisTo;
							}
						}

						if (Worker::isEmail($thisTo))
						{
							$thisMsg = $w->parseMessageForPlaceHolder($msg, $row);

							if ($eval)
							{
								$thisMsg = eval($thisMsg);
							}

							$thisSubject = $w->parseMessageForPlaceHolder($subject, $row);

							if ($testMode)
							{
								$this->app->enqueueMessage($x . ': Would send subject: ' . $thisSubject);
								$this->app->enqueueMessage($x . ': Would send to: ' . $thisTo);
							}
							else
							{
								$res = Worker::sendMail(
									$MailFrom,
									$FromName,
									$thisTo,
									$thisSubject,
									$thisMsg,
									true,
									null,
									null,
									null,
									$replyTo,
									$replyToName
								);

								if (!$res)
								{
									//$this->log .= "\n failed sending to $thisTo";
									Worker::log('plg.cron.email.information', 'Failed sending to: ' . $thisTo);
									$failedIds[] = $row['__pk_val'];
								}
								else
								{
									//$this->log .= "\n sent to $thisTo";
									Worker::log('plg.cron.email.information', 'Sent to: ' . $thisTo);
									$sentIds[] = $row['__pk_val'];
								}
							}
						}
						else
						{
							if ($testMode)
							{
								$this->app->enqueueMessage('Not an email address: ' . $thisTo);
							}
							else
							{
								Worker::log('plg.cron.email.information', 'Not an email address: ' . $thisTo);
								$failedIds[] = $row['__pk_val'];
							}
						}
					}
				}
			}
		}

		$sentIds = array_unique($sentIds);
		$field   = $params->get('cronemail-updatefield');

		if (!empty($sentIds) && trim($field) != '')
		{
			// Do any update found
			/** @var ListModel $listModel */
			$listModel = FabModel::getInstance(ListModel::class);
			$listModel->setId($params->get('table'));
			$table = $listModel->getTable();
			$field = $params->get('cronemail-updatefield');
			$value = $params->get('cronemail-updatefield-value');

			if ($params->get('cronemail-updatefield-eval', '0') == '1')
			{
				$value = @eval($value);
			}

			$field    = str_replace('___', '.', $field);
			$fabrikDb = $listModel->getDb();
			$query    = $fabrikDb->getQuery(true);
			$query
				->update($table->db_table_name)
				->set($field . ' = ' . $fabrikDb->quote($value))
				->where($table->db_primary_key . ' IN (' . implode(',', $sentIds) . ')');

			if (!$testMode)
			{
				$this->log .= "\n update query: " . (string)$query;
				$fabrikDb->setQuery($query);
				$fabrikDb->execute();
			}
			else
			{
				$this->app->enqueueMessage('Would run update query: ' . (string)$query);
			}
		}

		//$this->log .= "\n mails sent: " . count($sentIds) . " records";

		$field = $params->get('cronemail-update-code');

		if (trim($field) != '')
		{
			if (!$testMode)
			{
				@eval($field);
			}
			else
			{
				$this->app->enqueueMessage('Skipping update code');
			}
		}

		return count($sentIds);
	}

	/**
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	private function isTestMode()
	{
		return $this->app->isClient('administrator') && $this->getParams()->get('cronemail_test_mode', '0') === '1';
	}
}
