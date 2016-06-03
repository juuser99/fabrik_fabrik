<?php
/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Visualization\Media\Views;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Text;
use Fabrik\Helpers\Html as HtmlHelper;
use \JComponentHelper;
use \JFactory;
use \JHtml;
use \JViewLegacy;

/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @since       3.0
 */
class Html extends JViewLegacy
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 */

	public function display($tpl = 'default')
	{
		$app         = JFactory::getApplication();
		$input       = $app->input;
		$model       = $this->getModel();
		$usersConfig = JComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row            = $model->getVisualization();
		$params               = $model->getParams();
		$js                   = $model->getJs();
		$srcs                 = HtmlHelper::framework();
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';
		$srcs['Media']        = 'plugins/fabrik_visualization/media/media.js';

		if ($params->get('media_which_player', 'jw') == 'jw')
		{
			$srcs['JWPlayer'] = 'plugins/fabrik_visualization/media/libs/jw/jwplayer.js';
		}

		HtmlHelper::iniRequireJs($model->getShim());
		HtmlHelper::script($srcs, $js);

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$model->getRow();
		$this->media         = $model->getMedia();
		$this->params        = $params;
		$this->containerId   = $model->getContainerId();
		$this->showFilters   = $model->showFilters();
		$this->filterFormURL = $model->getFilterFormURL();
		$this->filters       = $this->get('Filters');
		$this->params        = $model->getParams();
		$tpl                 = $params->get('media_layout', 'bootstrap');
		$this->_setPath('template', JPATH_ROOT . '/plugins/fabrik_visualization/Media/Views/Media/tmpl/' . $tpl);
		HtmlHelper::stylesheetFromPath('plugins/fabrik_visualization/Media/Views/Media/tmpl/' . $tpl . '/template.css');
		echo parent::display();
	}
}
