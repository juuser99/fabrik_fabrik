<?php
/**
 * Media viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Calendar;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Fabrik\Controllers\Visualization as VizController;
use \JComponentHelper;
use \JFactory;

/**
 * Media viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @since       3.0
 */
class Controller extends VizController
{
	/**
	 * Get Playlist
	 *
	 * @return  void
	 */
	public function getPlaylist()
	{
		$model       = $this->getModel('media');
		$app         = JFactory::getApplication();
		$input       = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$id          = $input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0)), 'get');
		$model->setId($id);
		$model->getVisualization();
		echo $model->getPlaylist();
	}
}
