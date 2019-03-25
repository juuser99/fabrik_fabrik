<?php
/**
 * Fabrik Calendar Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Fullcalendar\View\Fullcalendar;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
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
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tmpl = null)
	{
		$model        = $this->getModel();
		$app          = Factory::getApplication();
		$input        = $app->input;
		$listid       = $input->get('listid', '');
		$eventListKey = $input->get('eventListKey', '');
		$usersConfig  = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		echo $model->getEvents($listid, $eventListKey);
	}
}
