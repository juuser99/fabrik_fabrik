<?php
/**
 * Fabrik Calendar Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Calendar\View\Calendar;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Plugin\FabrikVisualization\Calendar\Model\CalendarModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Fabrik Calendar Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       4.0
 */
class RawView extends BaseView
{
	/**
	 * Display the view
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function display($tmpl = null)
	{
		/** @var CalendarModel $model */
		$model       = $this->getModel();
		$app         = Factory::getApplication();
		$input       = $app->input;
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		echo $model->getEvents();
	}
}
