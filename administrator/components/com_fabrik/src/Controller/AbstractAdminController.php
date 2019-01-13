<?php
/**
 * Extends JControllerAdmin allowing for confirmation of removal of
 * items, along with call to model to perform additional
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\AdminController as BaseAdminController;

/**
 * Admin controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class AbstractAdminController extends BaseAdminController
{
	use ModelTrait;

	/**
	 * Component name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	public $option = 'com_fabrik';
}
