<?php
/**
 * Fileupload - Plugin element to render Flash files
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikElement\Fileupload\Renderer;

// No direct access
use Fabrik\Helpers\Worker;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

/**
 * Fileupload - Plugin element to render Flash files
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       4.0
 */
class FlashRenderer implements RendererInterface
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
		$this->render($plugin, $params, $file);
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
		$getID3 = Worker::getID3Instance();

		if ($getID3 === false)
		{
			$this->output = Text::_('COM_FABRIK_LIBRARY_NOT_INSTALLED');

			return;
		}

		$fbConfig = ComponentHelper::getParams('com_fabrik');

		// Analyse file and store returned data in $ThisFileInfo
		$relPath      = str_replace("\\", "/", JPATH_SITE . $file);
		$thisFileInfo = $getID3->analyze($relPath);

		$w = $params->get('fu_main_max_width', 0);
		$h = $params->get('fu_main_max_height', 0);

		if ($thisFileInfo && array_key_exists('swf', $thisFileInfo))
		{
			if ($thisFileInfo['swf']['header']['frame_width'] < $w || $thisFileInfo['swf']['header']['frame_height'] < $h)
			{
				$w = $thisFileInfo['swf']['header']['frame_width'];
				$h = $thisFileInfo['swf']['header']['frame_height'];
			}
		}

		if ($w <= 0 || $h <= 0)
		{
			$w = 800;
			$h = 600;
		}

		$layout                      = $plugin->getLayout('flash');
		$displayData                 = new \stdClass;
		$displayData->useThumbs      = !$plugin->inDetailedView && $fbConfig->get('use_mediabox', true) && $params->get('make_thumbnail', false);
		$displayData->width          = $w;
		$displayData->height         = $h;
		$displayData->inDetailedView = $plugin->inDetailedView;
		$displayData->file           = $file;

		if ($displayData->useThumbs)
		{
			// @TODO - work out how to do thumbnails
			$thumb_dir = $params->get('thumb_dir');

			if (!empty($thumb_dir))
			{
				$file     = str_replace("\\", "/", $file);
				$pathinfo = pathinfo($file);

				// $$$ hugh - apparently filename constant only added in PHP 5.2
				if (!isset($pathinfo['filename']))
				{
					$pathinfo['filename'] = explode('.', $pathinfo['basename']);
					$pathinfo['filename'] = $pathinfo['filename'][0];
				}

				$thumb_path = COM_FABRIK_BASE . $thumb_dir . '/' . $pathinfo['filename'] . '.png';

				if (File::exists($thumb_path))
				{
					$thumb_file = COM_FABRIK_LIVESITE . $thumb_dir . '/' . $pathinfo['filename'] . '.png';
				}
				else
				{
					$thumb_file = COM_FABRIK_LIVESITE . "media/com_fabrik/images/flash.jpg";
				}
			}
			else
			{
				$thumb_file = COM_FABRIK_LIVESITE . "media/com_fabrik/images/flash.jpg";
			}

			$file               = str_replace("\\", "/", COM_FABRIK_LIVESITE . $file);
			$displayData->thumb = $thumb_file;
			$displayData->file  = $file;
		}

		$this->output = $layout->render($displayData);
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
		$rendered = '';

		/**
		 * @TODO - build it!
		 */
		return $rendered;
	}
}
