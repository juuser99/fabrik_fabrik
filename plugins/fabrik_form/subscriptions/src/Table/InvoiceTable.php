<?php
/**
 *  JTable For Subscriptions Invoices
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
use Joomla\Component\Fabrik\Administrator\Table\FabTable;
use Joomla\Database\DatabaseDriver;

/**
 *  JTable For Subscriptions Invoices
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.subscriptions
 * @since       4.0
 */
class InvoiceTable extends FabTable
{
	/**
	 * Constructor
	 *
	 * @param DatabaseDriver $db database object
	 *
	 * @since 4.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__fabrik_subs_invoices', 'id', $db);
	}

	/**
	 * Update the invoice based on the request data
	 *
	 * @param   array $request posted invoice data
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function update($request)
	{
		$now                     = Factory::getDate()->toSQL();
		$this->transaction_date  = $now;
		$this->pp_txn_id         = $request['txn_id'];
		$this->pp_payment_status = $request['payment_status'];
		$this->pp_payment_amount = $request['mc_gross'];
		$this->pp_txn_type       = $request['txn_type'];
		$this->pp_fee            = $request['mc_fee'];
		$this->pp_payer_email    = $request['payer_email'];
		$this->paid              = 1;
		$this->store();
	}
}
