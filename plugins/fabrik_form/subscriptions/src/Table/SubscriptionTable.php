<?php
/**
 *  JTable For Subscriptions
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikForm\Subscriptions\Table;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Fabrik\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Database\DatabaseDriver;

/**
 *  JTable For Subscriptions
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @since       4.0
 */
class SubscriptionTable extends FabTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver $db database object
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__fabrik_subs_subscriptions', 'id', $db);
	}

	/**
	 * Expire the sub
	 *
	 * @param   string $msg reason for expiration
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function expire($msg = 'IPN expireSub')
	{
		$now             = Factory::getDate()->toSql();
		$this->status    = 'Expired';
		$this->eot_date  = $now;
		$this->eot_cause = $msg;

		return $this->store();
	}

	/**
	 * Activate the sub
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function activate()
	{
		$now                = Factory::getDate()->toSql();
		$this->status       = 'Active';
		$this->lastpay_date = $now;

		return $this->store();
	}

	/**
	 * Refund the sub - performed by merchant
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function refund()
	{
		$now               = Factory::getDate()->toSql();
		$this->status      = 'Refunded';
		$this->cancel_date = $now;
		$this->eot_date    = $now;
		$this->eot_cause   = 'IPN Refund';

		return $this->store();
	}

	/**
	 * Cancel the sub - performed by user
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function cancel()
	{
		$now               = Factory::getDate()->toSql();
		$this->status      = 'Cancelled';
		$this->cancel_date = $now;
		$this->eot_cause   = 'IPN Cancel';

		return $this->store();
	}
}
