<?php
/**
 * Fabrik Calendar Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Fullcalendar\Views;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Text;
use \JComponentHelper;
use \JFactory;
use \JViewLegacy;

/**
 * Fabrik Calendar Raw View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       3.0
 */

class Raw extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @param   string  $tmpl  Template
	 *
	 * @return  void
	 */
	public function display($tmpl = 'default')
	{
		$model = $this->getModel();
		$app = JFactory::getApplication();
		$input = $app->input;
		$listId = $input->get('listid', '');
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		echo $model->getEvents($listId);
	}
}
