<?php
/**
 * Fileupload adaptor to render uploaded videos
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
use Fabrik\Helpers\Text;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

/**
 * Fileupload adaptor to render uploaded videos
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       4.0
 */
class VideoRenderer implements RendererInterface
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

		$src = $plugin->getStorage()->getFileUrl($file);

		// Analyse file and store returned data in $ThisFileInfo
		$relPath = JPATH_SITE . $file;
		$thisFileInfo = $getID3->analyze($relPath);

		if (array_key_exists('video', $thisFileInfo))
		{
			if (array_key_exists('resolution_x', $thisFileInfo['video']))
			{
				$w = $thisFileInfo['video']['resolution_x'];
				$h = $thisFileInfo['video']['resolution_y'];
			}
			else
			{
				// For wmv files
				$w = $thisFileInfo['video']['streams']['2']['resolution_x'];
				$h = $thisFileInfo['video']['streams']['2']['resolution_y'];
			}

			switch ($thisFileInfo['fileformat'])
			{
				// Add in space for controller
				case 'quicktime':
					$h += 16;
					break;
				default:
					$h += 64;
			}
		}

		$displayData = new \stdClass;
		$displayData->width = $w;
		$displayData->height = $h;
		$displayData->src = $src;

		switch ($thisFileInfo['fileformat'])
		{
			case 'asf':
				$layout = $plugin->getLayout('video-asf');
				break;
			default:
				$layout = $plugin->getLayout('video');
				break;
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
	 * @throws \Exception
	 * @since 4.0
	 */
	public function renderCarousel($id = 'carousel', $data = array(), ?\PlgFabrik_ElementFileupload $plugin = null, ?Registry $params = null, ?array $thisRow = null): string
	{
		$rendered = '';
		$id .= '_video_carousel';

		if (!empty($data))
		{
			$rendered = '
			<div id="' . $id . '"></div>
			';

			/** @var Input $input */
			$input = Factory::getApplication()->input;

			if ($input->get('format') != 'raw')
			{
				$js = '
				jwplayer("' . $id . '").setup({
					playlist: [
				';
				$files = array();

				foreach ($data as $file)
				{
					$files[] .= '
						{
							"file": "' . COM_FABRIK_LIVESITE . ltrim($file, '/') . '"
						}
					';
				}

				$js .= implode(',', $files);
				$js .= ']
				});
				';
				Html::script('plugins/fabrik_element/fileupload/lib/jwplayer/jwplayer.js', $js);
			}
		}

		return $rendered;
	}
}
