<?php
/**
 * Fabrik Calendar HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Fullcalendar\View\Fullcalendar;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Fabrik Calendar HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.calendar
 * @since       4.0
 */
class PartialView extends BaseView
{
	/**
	 * Choose which list to add an event to
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function chooseAddEvent()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$this->setLayout('chooseAddEvent');
		$model       = $this->getModel();
		$usersConfig = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$rows = $model->getEventLists();

		foreach ($rows as $rowkey => $row)
		{
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($row->value);
			if (!$listModel->canAdd())
			{
				unset($rows[$rowkey]);
			}
		}

		$model->getVisualization();
		$options   = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('PLG_VISUALIZATION_FULLCALENDAR_PLEASE_SELECT'));

		$model->getEvents();
		$attribs            = 'class="inputbox" size="1" ';
		$options            = array_merge($options, $rows);
		$this->_eventTypeDd = HTMLHelper::_('select.genericlist', $options, 'event_type', $attribs, 'value', 'text', '', 'fabrik_event_type');

		/*
		 * Tried loading in iframe and as an ajax request directly - however
		 * in the end decided to set a call back to the main calendar object (via the package manager)
		 * to load up the new add event form
		 */
		$ref      = $model->getJSRenderContext();
		$script   = array();
		$script[] = "document.id('fabrik_event_type').addEvent('change', function(e) {";
		$script[] = "var fid = e.target.get('value');";
		$script[] = "var o = ({'id':'','listid':fid,'rowid':0});";
		$script[] = "o.title = Joomla.JText._('PLG_VISUALIZATION_FULLCALENDAR_ADD_EVENT');";

		$script[] = "Fabrik.blocks['" . $ref . "'].addEvForm(o);";
		$script[] = "Fabrik.Windows.chooseeventwin.close();";
		$script[] = "});";

		echo '<h2>' . Text::_('PLG_VISUALIZATION_FULLCALENDAR_PLEASE_CHOOSE_AN_EVENT_TYPE') . ':</h2>';
		echo $this->_eventTypeDd;
		Html::addScriptDeclaration(implode("\n", $script));
	}
}
