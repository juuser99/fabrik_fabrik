<?php
/**
 * Fabrik Google Map JSON View
 *
 * #### Needed for Joomla's smart search indexer ####
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\GoogleMap\View\GoogleMap;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Text;
use Fabrik\Plugin\FabrikVisualization\GoogleMap\Model\GoogleMapModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Fabrik Google Map JSON View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       3.0
 */

class JsonView extends BaseView
{
	/**
	 * Display the view
	 *
	 * @param   string $tmpl template
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function display($tmpl = null)
	{
		$app         = Factory::getApplication();
		$input       = $app->input;
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		/** @var GoogleMapModel $model */
		$model       = $this->getModel();
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		echo json_encode($model->getJSIcons());
	}
}
