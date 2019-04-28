<?php
/**
 * Plugin element to render fileuploads of file type
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikElement\Fileupload\Renderer;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\File;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Plugin element to render fileuploads of file type
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       4.0
 */
class FileRenderer extends AbstractRenderer
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
	 * @since version
	 */
	public function renderListData(\PlgFabrik_ElementFileupload $plugin, Registry $params, string $file, ?\stdClass $thisRow = null): void
	{
		$this->render($plugin, $params, $file);
	}

	/**
	 * @param \PlgFabrik_ElementFileupload $plugin
	 * @param Registry                     $params
	 * @param string                       $file
	 *
	 *
	 * @since 4.0
	 */
	public function render(\PlgFabrik_ElementFileupload $plugin, Registry $params, string $file, ?\stdClass $thisRow = null): void
	{
		/*
		 * $$$ hugh - TESTING - if $file is empty, we're going to just build an empty bit of DOM
		 * which can then be filled in with the selected image using HTML5 in browser.
		 */
		if (empty($file))
		{
			if ($params->get('make_thumbnail', false))
			{
				$maxWidth     = $params->get('thumb_max_width', 125);
				$maxHeight    = $params->get('thumb_max_height', 125);
				$this->output .= '<img style="width: ' . $maxWidth . 'px;" src="" alt="" />';
			}
		}
		else
		{
			$filename = basename($file);
			$filename = strip_tags($filename);
			$ext      = File::getExt($filename);

			if (!strstr($file, 'http://') && !strstr($file, 'https://'))
			{
				// $$$rob only add in livesite if we don't already have a full url (e.g. from amazons3)

				// Trim / or \ off the start of $file
				$file = StringHelper::ltrim($file, '/\\');
				$file = COM_FABRIK_LIVESITE . $file;
			}

			$file = str_replace("\\", "/", $file);
			$file = $plugin->getStorage()->preRenderPath($file);


			$layout                = $plugin->getLayout('file');
			$displayData           = new \stdClass;
			$displayData->thumb    = COM_FABRIK_LIVESITE . 'media/com_fabrik/images/' . $ext . '.png';
			$displayData->useThumb = $params->get('make_thumbnail', false) && File::exists($displayData->thumb);
			$displayData->ext      = $ext;
			$displayData->filename = $filename;
			$displayData->file     = $file;

			$this->output = $layout->render($displayData);
		}
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
