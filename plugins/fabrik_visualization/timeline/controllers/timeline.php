<?php
/**
 * Fabrik Timeline Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Timeline;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Fabrik\Controllers\Visualization as VizController;
use \JFactory;

/**
 * Fabrik Time-line Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.timeline
 * @since       3.0
 */
class Controller extends VizController
{
	/**
	 * Get a series of time-line events
	 *
	 * @return  void
	 */
	public function ajax_getEvents()
	{
		$viewName = 'timeline';
		$model    = $this->getModel($viewName);
		$id       = $this->input->getInt('visualizationid', 0);
		$model->setId($id);
		$model->onAjax_getEvents();
	}
}
