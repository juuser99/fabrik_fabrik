<?php
/**
 * Renders a list which will toggle visibility of a specified group
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

/**
 * Renders a list which will toggle visibility of a specified group
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class ToggleOptionsListField extends ListField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'toggleoptionslist';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'ToggleOptionsList';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		$script = "window.addEvent('domready', function() {

		if (document.id('" . $this->id . "').get('value') == '" . $this->element['hide'] . "') {
			document.id('" . $this->element['toggle'] . "').hide();
		}
			document.id('" . $this->id . "').addEvent('change', function (e) {
				var v = e.target.get('value');
				if (v == '" . $this->element['show'] . "') {
					document.id('" . $this->element['toggle'] . "').show();
				} else {
					if(v == '" . $this->element['hide'] . "') {
						document.id('" . $this->element['toggle'] . "').hide();
					}
				}
			});
		})";
		Html::addScriptDeclaration($script);

		return parent::getInput();
	}
}
