<?php
/**
 * Used in radios/checkbox elements for adding <options> to the element
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
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

/**
 * Used in radios/checkbox elements for adding <options> to the element
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class SubOptionsField extends FormField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'suboptions';

	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Suboptions';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since 4.0
	 */
	protected function getInput()
	{
		Text::script('COM_FABRIK_SUBOPTS_VALUES_ERROR');

		$default                        = new \stdClass;
		$default->sub_values            = array();
		$default->sub_labels            = array();
		$default->sub_initial_selection = array();
		$opts                           = $this->value == '' ? $default : ArrayHelper::toObject($this->value);

		$delButton = '<div class="btn-group">';
		$delButton .= '<a class="btn btn-success" href="#" data-button="addSuboption"><i class="icon-plus"></i> </a>';
		$delButton .= '<a class="btn btn-danger" href="#" data-button="deleteSuboption"><i class="icon-minus"></i> </a>';
		$delButton .= '</div>';

		if (is_array($opts))
		{
			$opts['delButton'] = $delButton;
		}
		else
		{
			$opts->delButton = $delButton;
		}

		$opts->id         = $this->id;
		$opts->defaultMax = (int) $this->getAttribute('default_max', 0);
		$opts             = json_encode($opts);
		$script[]         = "window.addEvent('domready', function () {";
		$script[]         = "\tnew Suboptions('$this->name', $opts);";
		$script[]         = "});";
		Html::script('administrator/components/com_fabrik/src/Field/suboptions.js', implode("\n", $script));

		$html   = array();
		$html[] = '<table class="table table-striped" style="width: 100%" id="' . $this->id . '">';
		$html[] = '<thead>';
		$html[] = '<tr style="text-align:left">';
		$html[] = '<th style="width: 5%"></th>';
		$html[] = '<th style="width: 30%">' . Text::_('COM_FABRIK_VALUE') . '</th>';
		$html[] = '<th style="width: 30%">' . Text::_('COM_FABRIK_LABEL') . '</th>';
		$html[] = '<th style="width: 10%">' . Text::_('COM_FABRIK_DEFAULT') . '</th>';
		$html[] = '<th style="width: 20%"><a class="btn btn-success" href="#" data-button="addSuboption"><i class="icon-plus"></i> </a></th>';
		$html[] = '</tr>';
		$html[] = '</thead>';
		$html[] = '<tbody></tbody>';
		$html[] = '</table>';

		Html::framework();
		Html::iniRequireJS();

		return implode("\n", $html);
	}
}
