<?php
/**
 * Fabrik Google Map Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Googlemap;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Fabrik\Controllers\Visualization as VizController;
use \JFactory;

/**
 * Fabrik Google Map Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       3.0
 */
class Controller extends VizController
{
	/**
	 * Ajax markers
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  void
	 */
	public function ajax_getMarkers($tmpl = 'default')
	{
		$viewName = 'googlemap';
		$model    = $this->getModel($viewName);
		$id       = $this->input->getInt('visualizationid', 0);
		$model->setId($id);
		$model->onAjax_getMarkers();
	}
}
