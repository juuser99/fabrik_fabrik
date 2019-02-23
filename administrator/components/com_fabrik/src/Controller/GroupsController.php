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
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\GroupModel;

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
	 * @param string $name
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return GroupModel
	 *
	 * @since 4.0
	 */
	public function getModel($name = GroupModel::class, $prefix = '',  $config = array('ignore_request' => true))
	{
		/** @var GroupModel $model */
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
}
