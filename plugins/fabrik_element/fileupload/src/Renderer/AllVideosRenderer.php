<?php
/**
 * All Videos
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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

/**
 * Fileupload adaptor to render allvideos
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.fileupload
 * @since       4.0
 */
class AllVideosRenderer  implements RendererInterface
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
	 * @var bool
	 * @since 4.0
	 */
	public $inTableView = false;

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
		$src = $plugin->getStorage()->getFileUrl($file);
		$ext = StringHelper::strtolower(File::getExt($file));

		if (!PluginHelper::isEnabled('content', 'jw_allvideos'))
		{
			$this->output = Text::_('PLG_ELEMENT_FILEUPLOAD_INSTALL_ALL_VIDEOS');

			return;
		}

		$extra   = array();
		$extra[] = $src;

		if ($this->inTableView || $params->get('fu_show_image') < 2)
		{
			$extra[] = $params->get('thumb_max_width');
			$extra[] = $params->get('thumb_max_height');
		}
		else
		{
			$extra[] = $params->get('fu_main_max_width');
			$extra[] = $params->get('fu_main_max_height');
		}

		$src = implode('|', $extra);

		switch ($ext)
		{
			case 'flv':
				$this->output = "{flvremote}$src{/flvremote}";
				break;
			case '3gp':
				$this->output = "{3gpremote}$src{/3gpremote}";
				break;
			case 'divx':
				$this->output = "{divxremote}$src{/divxremote}";
				break;
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
