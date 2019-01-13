<?php
/**
 * Visualization list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
use Joomla\Component\Fabrik\Administrator\Model\FabrikModel;
use Joomla\Component\Fabrik\Administrator\Model\VisualizationsModel;

defined('_JEXEC') or die('Restricted access');

/**
 * Visualization list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class VisualizationsController extends AbstractAdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_VISUALIZATIONS';

	/**
	 * View item name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'visualizations';

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    model name
	 * @param   string  $prefix  model prefix
	 *
	 * @return  VisualizationsModel|FabrikModel
	 *
	 * @since 4.0
	 */
	public function getModel($name = VisualizationsModel::class, $prefix = '')
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}
}
