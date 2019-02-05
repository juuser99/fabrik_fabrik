<?php
/**
 * Approval Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Approvals\View\Approvals;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Approval Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @since       4.0
 */
class RawView extends BaseView
{
	/**
	 * Display view
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tmpl = null)
	{
		$app         = Factory::getApplication();
		$input       = $app->input;
		$model       = $this->getModel();
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$id          = $input->get('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)));
		$model->setId($id);

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$this->plugin = $this->get('Plugin');
		$task         = $input->get('plugintask');
		$model->$task();
	}
}
