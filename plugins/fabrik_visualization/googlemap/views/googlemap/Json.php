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

namespace Fabrik\Plugins\Visualization\Googlemap\Views;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Text;
use \JFactory;
use \JHtml;
use \JViewLegacy;
use \JComponentHelper;

/**
 * Fabrik Google Map JSON View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.googlemap
 * @since       3.0
 */
class Json extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @param   string $tmpl template
	 *
	 * @return void
	 */

	public function display($tmpl = 'default')
	{
		$app         = JFactory::getApplication();
		$input       = $app->input;
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model       = $this->getModel();
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		echo $model->getJSIcons();
	}
}

