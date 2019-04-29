<?php
/**
 * Fileupload adaptor to render audio play
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
use Joomla\CMS\Factory;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

/**
 * Fileupload adaptor to render audio play
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       4.0
 */
class AudioRenderer  implements RendererInterface
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
		$layout            = $plugin->getLayout('audio');
		$displayData       = new \stdClass;
		$displayData->file = $plugin->getStorage()->getFileUrl($file);

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
		$id       .= '_audio_carousel';

		if (!empty($data))
		{
			$rendered = '
			<div id="' . $id . '"></div>
			';

			/** @var Input $input */
			$input = Factory::getApplication()->input;

			if ($input->get('format') != 'raw')
			{
				$js    = '
				jwplayer("' . $id . '").setup({
					width: "250",
					height: "30",
					playlist: [
				';
				$files = array();

				foreach ($data as $file)
				{
					$files[] .= '
						{
							"file": "' . COM_FABRIK_LIVESITE . $file . '"
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
