<?php
/**
 * Media viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Media\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Controller\VisualizationController;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Media viz Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @since       4.0
 */
class MediaController extends VisualizationController
{
	/**
	 * Get Playlist
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function getPlaylist()
	{
		$model = $this->getModel('media');
		$conf  = ComponentHelper::getParams('com_fabrik');
		$id    = $this->input->getInt('id', $conf->get('visualizationid', $this->input->getInt('visualizationid', 0)));
		$model->setId($id);
		$model->getVisualization();
		echo $model->getPlaylist();
	}
}
