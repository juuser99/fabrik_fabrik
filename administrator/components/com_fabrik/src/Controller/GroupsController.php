<?php
/**
 * Groups list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
use Fabrik\Component\Fabrik\Administrator\Model\FabModel;
use Fabrik\Component\Fabrik\Administrator\Model\GroupsModel;

defined('_JEXEC') or die('Restricted access');

/**
 * Groups list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class GroupsController extends AbstractAdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_GROUPS';

	/**
	 * View item name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'groups';

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 *
	 * @return  GroupsModel|FabModel
	 *
	 * @since 4.0
	 */
	public function getModel($name = GroupsModel::class, $prefix = '')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}
}
