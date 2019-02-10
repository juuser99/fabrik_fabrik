<?php
/**
 * Fabrik Google Map Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\GoogleMap\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Controller\VisualizationController;

/**
 * Fabrik Google Map Viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       4.0
 */
class GoogleMapController extends VisualizationController
{
	/**
	 * Ajax markers
	 *
	 * @param   string $tmpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
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
