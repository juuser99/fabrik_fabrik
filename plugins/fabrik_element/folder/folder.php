<?php
/**
 * Plugin element to render folder list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.folder
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Fabrik\Site\Plugin\AbstractElementPlugin;

/**
 * Plugin element to render folder list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.folder
 * @since       3.0
 */
class PlgFabrik_ElementFolder extends AbstractElementPlugin
{
	/**
	 * Draws the html form element
	 *
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 *
	 * @since 4.0
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name      = $this->getHTMLName($repeatCounter);
		$id        = $this->getHTMLId($repeatCounter);
		$params    = $this->getParams();
		$selected  = $this->getValue($data, $repeatCounter);
		$errorCss  = $this->elementError != '' ? " elementErrorHighlight" : '';
		$aRoValues = array();
		$path      = JPATH_ROOT . '/' . $params->get('fbfolder_path');
		$opts      = array();

		if ($params->get('folder_allownone', true))
		{
			$opts[] = HTMLHelper::_('select.option', '', Text::_('NONE'));
		}

		if ($params->get('folder_listfolders', true))
		{
			$folders = Folder::folders($path);

			foreach ($folders as $folder)
			{
				$opts[] = HtmlHelper::_('select.option', $folder, $folder);

				if ($selected === $folder)
				{
					$aRoValues[] = $folder;
				}
			}
		}

		if ($params->get('folder_listfiles', false))
		{
			$files = Folder::files($path);

			foreach ($files as $file)
			{
				$opts[] = HtmlHelper::_('select.option', $file, $file);

				if ($selected === $folder)
				{
					$aRoValues[] = $file;
				}
			}
		}

		if (!$this->isEditable())
		{
			return implode(', ', $aRoValues);
		}

		$layout                = $this->getLayout('form');
		$displayData           = new \stdClass;
		$displayData->options  = $opts;
		$displayData->name     = $name;
		$displayData->selected = $selected;
		$displayData->id       = $id;
		$displayData->errorCss = $errorCss;

		return $layout->render($displayData);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	public function elementJavascript($repeatCounter)
	{
		$id               = $this->getHTMLId($repeatCounter);
		$params           = $this->getParams();
		$element          = $this->getElement();
		$path             = JPATH_ROOT . '/' . $params->get('fbfbfolder_path');
		$folders          = Folder::folders($path);
		$opts             = $this->getElementJSOptions($repeatCounter);
		$opts->defaultVal = $element->default;
		$opts->data       = $folders;

		return array('FbFolder', $id, $opts);
	}
}
