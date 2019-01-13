<?php
/**
 * Upgrade controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Upgrade controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class UpgradeController extends AbstractAdminController
{
	/**
	 * Delete all data from fabrik
	 *
	 * @return  null
	 */
	public function check()
	{
		$model = $this->getModel(UpgradeMo);
	}
}
