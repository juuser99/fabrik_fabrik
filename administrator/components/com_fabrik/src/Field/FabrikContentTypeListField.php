<?php
/**
 * Renders a list of Fabrik content types
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

use Fabrik\Helpers\Html;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

FormHelper::loadFieldClass('list');

/**
 * Renders a list of Fabrik content types
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class FabrikContentTypeListField extends ListField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'fabrikcontenttypelist';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'FabrikContentTypeList';

	/**
	 * Method to get the field options.
	 *
	 * @return  string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$base    = JPATH_COMPONENT_ADMINISTRATOR . '/models/content_types';
		$files   = Folder::files($base, '.xml');
		$options = array();

		foreach ($files as $file)
		{
			$xml = file_get_contents($base . '/' . $file);
			$doc = new \DOMDocument();
			$doc->loadXML($xml);
			$xpath = new \DOMXpath($doc);
			$name  = iterator_to_array($xpath->query('/contenttype/name'));

			if (!is_null($name) && count($name) > 0)
			{
				$options[] = HTMLHelper::_('select.option', $file, $name[0]->nodeValue);
			}
		}

		return $options;
	}

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multi-select.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		$str = '<div class="row-fluid">
		<div class="span5">' . parent::getInput() . '<div id="contentTypeListAclUi"></div></div><div class="span7">';
		$str .= '<legend>' . Text::_('COM_FABRIK_PREVIEW') . ': </legend>';
		$str .= '<div class="well" id="contentTypeListPreview"></div>';

		$str    .= '</div>';
		$script = 'new FabrikContentTypeList(\'' . $this->id . '\');';
		$src    = array(
			'Fabrik'          => 'media/com_fabrik/js/fabrik.js',
			'ContentTypeList' => 'administrator/components/com_fabrik/src/Field/fabrikcontenttypelist.js'
		);
		Html::script($src, $script);

		return $str;
	}
}
