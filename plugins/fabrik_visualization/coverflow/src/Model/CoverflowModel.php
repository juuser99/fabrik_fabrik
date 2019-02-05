<?php
/**
 * Fabrik Coverflow Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Coverflow\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Administrator\Model\VisualizationModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Helpers\Html;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\String\StringHelper;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;

/**
 * Fabrik Coverflow Plug-in Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.coverflow
 * @since       4.0
 */
class CoverflowModel extends VisualizationModel
{
	/**
	 * Internally render the plugin, and add required script declarations
	 * to the document
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function render()
	{
		$params = $this->getParams();
		/** @var CMSApplication $app */
		$app      = Factory::getApplication();
		$document = $app->getDocument();
		$document->addScript("http://api.simile-widgets.org/runway/1.0/runway-api.js");
		$c         = 0;
		$images    = (array) $params->get('coverflow_image');
		$titles    = (array) $params->get('coverflow_title');
		$subtitles = (array) $params->get('coverflow_subtitle');

		$listIds   = (array) $params->get('coverflow_table');
		$eventData = array();

		foreach ($listIds as $listId)
		{
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($listId);
			$list = $listModel->getTable();
			$listModel->getPagination(0, 0, 0);
			$image    = $images[$c];
			$title    = $titles[$c];
			$subtitle = $subtitles[$c];
			$data     = $listModel->getData();

			if ($listModel->canView() || $listModel->canEdit())
			{
				$elements     = $listModel->getElements();
				$imageElement = FArrayHelper::getValue($elements, FStringHelper::safeColName($image));

				foreach ($data as $group)
				{
					if (is_array($group))
					{
						foreach ($group as $row)
						{
							$event = new \stdClass;

							if (!method_exists($imageElement, 'getStorage'))
							{
								switch (get_class($imageElement))
								{
									case 'FabrikModelFabrikImage':
										$rootFolder   = $imageElement->getParams()->get('selectImage_root_folder');
										$rootFolder   = StringHelper::ltrim($rootFolder, '/');
										$rootFolder   = StringHelper::rtrim($rootFolder, '/');
										$event->image = COM_FABRIK_LIVESITE . 'images/stories/' . $rootFolder . '/' . $row->{$image . '_raw'};
										break;
									default:
										$event->image = isset($row->{$image . '_raw'}) ? $row->{$image . '_raw'} : '';
										break;
								}
							}
							else
							{
								$event->image = $imageElement->getStorage()->pathToURL($row->{$image . '_raw'});
							}

							$event->title    = $title === '' ? '' : (string) strip_tags($row->$title);
							$event->subtitle = $subtitle === '' ? '' : (string) strip_tags($row->$subtitle);
							$eventData[]     = $event;
						}
					}
				}
			}

			$c++;
		}

		$json              = json_encode($eventData);
		$str               = "var coverflow = new FbVisCoverflow($json);";
		$srcs              = Html::framework();
		$srcs['Coverflow'] = $this->srcBase . 'coverflow/coverflow.js';
		Html::script($srcs, $str);
	}

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params        = $this->getParams();
			$this->listids = (array) $params->get('coverflow_table');
		}
	}
}
