<?php
/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Media\View\Media;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Plugin\FabrikVisualization\Media\Model\MediaModel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Fabrik Media Viz HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.media
 * @since       4.0
 */
class HtmlView extends BaseView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		/** @var CMSApplication $app */
		$app   = Factory::getApplication();
		$input = $app->input;
		/** @var MediaModel $model */
		$model       = $this->getModel();
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row            = $model->getVisualization();
		$params               = $model->getParams();
		$js                   = $model->getJs();
		$srcs                 = Html::framework();
		$srcs['FbListFilter'] = 'media/com_fabrik/js/listfilter.js';
		$srcs['Media']        = 'plugins/fabrik_visualization/media/media.js';

		if ($params->get('media_which_player', 'jw') == 'jw')
		{
			$srcs['JWPlayer'] = 'plugins/fabrik_visualization/media/libs/jw/jwplayer.js';
		}

		Html::iniRequireJs($model->getShim());
		Html::script($srcs, $js);

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		$media               = $model->getRow();
		$this->media         = $model->getMedia();
		$this->params        = $params;
		$viewName            = $this->getName();
		$this->containerId   = $model->getContainerId();
		$this->showFilters   = $model->showFilters();
		$this->filterFormURL = $model->getFilterFormURL();
		$this->filters       = $this->get('Filters');
		$this->params        = $model->getParams();
		$tpl                 = $params->get('media_layout', 'bootstrap');
		$tplpath             = JPATH_ROOT . '/plugins/fabrik_visualization/media/tmpl/media/' . $tpl;
		$this->_setPath('template', $tplpath);
		Html::stylesheetFromPath('plugins/fabrik_visualization/media/tmpl/media/' . $tpl . '/template.css');
		echo parent::display();
	}
}
