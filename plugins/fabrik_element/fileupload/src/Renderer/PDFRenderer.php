<?php
/**
 * Fileupload adaptor to render uploaded PDFs
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
 * Fileupload adaptor to render uploaded PDFs
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       4.0
 */
class PDFRenderer implements RendererInterface
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
	 * File extension for PDF thumbnails
	 *
	 * @var  string
	 *             
	 * @since 4.0
	 */
	protected $pdf_thumb_type = 'png';

	/**
	 * Is the element in a list view
	 *
	 * @var  bool
	 *           
	 * @since 4.0
	 */
	protected $inTableView = false;

	/**
	 * When in form or detailed view, do we want to show the full image or thumbnail/link?
	 *
	 * @param   \PlgFabrik_ElementFileupload  $plugin  Element model
	 * @param   Registry $params Element params
	 * @param   string $file    Element's data
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	private function getThumbnail(\PlgFabrik_ElementFileupload $plugin, Registry $params, string $file)
	{
		if ($this->inTableView || ($params->get('make_thumbnail') == '1' && $params->get('fu_show_image') == 1))
		{
			if (!$params->get('make_thumbnail', false))
			{
				return false;
			}
			else
			{
				$thumb_url      = $plugin->getStorage()->getThumb($file);
				$thumb_file     = $plugin->getStorage()->urlToPath($thumb_url);
				$thumb_url_info = pathinfo($thumb_url);

				if (StringHelper::strtolower($thumb_url_info['extension'] == 'pdf'))
				{
					$thumb_url       = $thumb_url_info['dirname'] . '/' . $thumb_url_info['filename'] . '.' . $this->pdf_thumb_type;
					$thumb_file_info = pathinfo($thumb_file);
					$thumb_file      = $thumb_file_info['dirname'] . '/' . $thumb_file_info['filename'] . '.' . $this->pdf_thumb_type;
				}

				if ($plugin->getStorage()->exists($thumb_file))
				{
					return $thumb_url;
				}
				else
				{
					// If file specific thumb doesn't exist, try the generic per-type image in media folder
					$thumb_file = COM_FABRIK_BASE . 'media/com_fabrik/images/pdf.png';

					if (File::exists($thumb_file))
					{
						//return thumb_url
						return COM_FABRIK_LIVESITE . 'media/com_fabrik/images/pdf.png';
					}
					else
					{
						// Nope, nothing we can use as a thumb
						return false;
					}
				}
			}
		}

		return false;
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
	public function renderListData(\PlgFabrik_ElementFileupload $plugin, Registry $params, string $file, ?\stdClass $thisRow = null): void
	{
		$this->inTableView = true;
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
		$layout      = $plugin->getLayout('pdf');
		$displayData = new \stdClass;
		$filename    = basename($file);
		$filename    = strip_tags($filename);

		if (!strstr($file, 'http://') && !strstr($file, 'https://'))
		{
			// $$$rob only add in livesite if we don't already have a full url (e.g. from amazons3)
			// $$$ hugh trim / or \ off the start of $file
			$file = StringHelper::ltrim($file, '/\\');
			$file = COM_FABRIK_LIVESITE . $file;
		}

		$file                  = str_replace("\\", "/", $file);
		$file                  = $plugin->getStorage()->preRenderPath($file);
		$displayData->file     = $file;
		$displayData->filename = $filename;
		$displayData->thumb    = $this->getThumbnail($plugin, $params, $file);

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
