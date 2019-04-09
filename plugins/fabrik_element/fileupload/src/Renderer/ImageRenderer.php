<?php
/**
 * Fileupload adaptor to render uploaded images
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikElement\Fileupload\Renderer;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\Registry\Registry;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;

/**
 * Fileupload adaptor to render uploaded images
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       4.0
 */
class ImageRenderer implements RendererInterface
{
	/**
	 * Render output
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	public $output = '';

	/**
	 * In list view
	 *
	 * @var bool
	 *
	 * @since 4.0
	 */
	protected $inTableView = false;

	/**
	 * @param \PlgFabrik_ElementFileupload $plugin
	 * @param Registry                     $params
	 * @param string                       $file
	 * @param \stdClass|null               $thisRow
	 *
	 *
	 * @since 4.0
	 */
	public function renderListData(\PlgFabrik_ElementFileupload $plugin, Registry $params, string $file, ?\stdClass $thisRow = null): void
	{
		$this->inTableView = true;
		$this->render($plugin, $params, $file, $thisRow);
	}

	/**
	 * @param \PlgFabrik_ElementFileupload $plugin
	 * @param Registry                     $params
	 * @param string                       $file
	 * @param \stdClass|null               $thisRow
	 *
	 *
	 * @since 4.0
	 */
	public function render(\PlgFabrik_ElementFileupload $plugin, Registry $params, string $file, ?\stdClass $thisRow = null): void
	{
		/*
		 * $$$ hugh - added this hack to let people use elementname__title as a title element
		 * for the image, to show in the lightbox popup.
		 * So we have to work out if we're being called from a table or form
		 */
		$formModel = $plugin->getFormModel();
		$listModel = $plugin->getListModel();
		$title     = basename($file);

		if ($params->get('fu_title_element') == '')
		{
			$title_name = $plugin->getFullName(true, false) . '__title';
		}
		else
		{
			$title_name = str_replace('.', '___', $params->get('fu_title_element'));
		}

		if ($this->inTableView)
		{
			if (array_key_exists($title_name, $thisRow))
			{
				$title = $thisRow->$title_name;
			}
		}
		else
		{
			if (is_object($formModel))
			{
				if (is_array($formModel->data))
				{
					$title = FArrayHelper::getValue($formModel->data, $title_name, '');
				}
			}
		}

		$bits  = Worker::JSONtoData($title, true);
		$title = FArrayHelper::getValue($bits, $plugin->getRepeatGroupCounter(), $title);
		$title = htmlspecialchars(strip_tags($title, ENT_NOQUOTES));
		$file  = $plugin->getStorage()->getFileUrl($file);

		$fullSize = $file;

		if (!$this->fullImageInRecord($params))
		{
			if ($params->get('fileupload_crop'))
			{
				$file = $plugin->getStorage()->getCropped($fullSize);
			}
			else
			{
				$file = $plugin->getStorage()->getThumb($file);
			}
		}

		list($width, $height) = $this->imageDimensions($params);

		$file = $plugin->getStorage()->preRenderPath($file);

		$n = $this->inTableView ? '' : $plugin->getElement()->name;

		if ($params->get('restrict_lightbox', 1) == 0)
		{
			$n = '';
		}

		$layout                     = $plugin->getLayout('image');
		$displayData                = new \stdClass;
		$displayData->lightboxAttrs = Html::getLightboxAttributes($title, $n);
		$displayData->fullSize      = $plugin->getStorage()->preRenderPath($fullSize);
		$displayData->file          = $file;
		$displayData->makeLink      = $params->get('make_link', true)
			&& !$this->fullImageInRecord($params)
			&& $listModel->getOutPutFormat() !== 'feed';
		$displayData->title         = $title;
		$displayData->isJoin        = $plugin->isJoin();
		$displayData->width         = $width;
		$displayData->showImage     = $params->get('fu_show_image');
		$displayData->inListView    = $this->inTableView;
		$displayData->height        = $height;
		$displayData->isSlideShow   = ($this->inTableView && $params->get('fu_show_image_in_table', '0') == '2')
			|| (!$this->inTableView && !$formModel->isEditable() && $params->get('fu_show_image', '0') == '3');

		$this->output = $layout->render($displayData);
	}

	/**
	 * Get the image width / height
	 *
	 * @param Registry $params Params
	 *
	 * @return  array ($width, $height)
	 * @since   3.1rc2
	 *
	 */
	private function imageDimensions(Registry $params)
	{
		$width  = $params->get('fu_main_max_width');
		$height = $params->get('fu_main_max_height');

		if (!$this->fullImageInRecord($params))
		{
			if ($params->get('fileupload_crop'))
			{
				$width  = $params->get('fileupload_crop_width');
				$height = $params->get('fileupload_crop_height');
			}
			else
			{
				$width  = $params->get('thumb_max_width');
				$height = $params->get('thumb_max_height');
			}
		}

		return array($width, $height);
	}

	/**
	 * When in form or detailed view, do we want to show the full image or thumbnail/link?
	 *
	 * @param Registry $params params
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	private function fullImageInRecord(Registry $params)
	{
		if ($this->inTableView)
		{
			return ($params->get('make_thumbnail') || $params->get('fileupload_crop')) ? false : true;
		}

		if (($params->get('make_thumbnail') || $params->get('fileupload_crop')) && $params->get('fu_show_image') == 1)
		{
			return false;
		}

		return true;
	}

	/**
	 * @param string                            $id
	 * @param array                             $data
	 * @param \PlgFabrik_ElementFileupload|null $plugin
	 * @param Registry|null                     $params
	 * @param array|null                        $thisRow
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function renderCarousel($id = 'carousel', $data = array(), ?\PlgFabrik_ElementFileupload $plugin = null, ?Registry $params = null, ?array $thisRow = null): string
	{
		$id             .= '_carousel';
		$layout         = $plugin->getLayout('carousel');
		$layoutData     = new \stdClass;
		$layoutData->id = $id;
		list($layoutData->width, $layoutData->height) = $this->imageDimensions($params);

		if (!empty($data))
		{
			$imgs = array();
			$i    = 0;

			foreach ($data as $img)
			{
				$plugin->setRepeatGroupCounter($i++);
				$this->renderListData($plugin, $params, $img, $thisRow);
				$imgs[] = $this->output;
			}

			if (count($imgs) == 1)
			{
				return $imgs[0];
			}
		}

		$layoutData->imgs = $imgs;

		return $layout->render($layoutData);
	}
}
