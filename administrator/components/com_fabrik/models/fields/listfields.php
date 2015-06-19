<?php
/**
 * Renders a list of elements found in a fabrik list
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Admin\Helpers\AdminElement;
use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper;
use Fabrik\Admin\Models\Group as Group;
use Fabrik\Helpers\HTML;
use Fabrik\Helpers\String;
use Fabrik\Helpers\Text;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a list of elements found in a fabrik list
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldListfields extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	protected $name = 'Listfields';

	/**
	 * Objects resulting from this elements queries - keyed on identifying hash
	 *
	 * @var  array
	 */
	protected $results = null;

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 */

	protected function getInput()
	{
		if (is_null($this->results))
		{
			$this->results = array();
		}

		$this->app     = JFactory::getApplication();
		$input         = $this->app->input;
		$controller    = $input->get('view', $input->get('task'));
		$formModel     = false;
		$pluginFilters = trim($this->element['filter']) == '' ? array() : explode('|', $this->element['filter']);
		$connection    = $this->element['connection'];
		/*
		 * 27/08/2011 - changed from default table-element to id - for juser form plugin - might cause havoc
		 * else where but loading elements by id as default seems more robust (and is the default behaviour in f2.1
		 */
		$valueFormat    = (string) $this->getAttribute('valueformat', 'id');
		$onlyListFields = (int) $this->getAttribute('onlylistfields', 0);
		$showRaw        = Worker::toBoolean($this->getAttribute('raw', false), false);
		$labelMethod    = (string) $this->getAttribute('label_method');
		$noJoins        = Worker::toBoolean($this->getAttribute('nojoins', false), false);
		$mode           = (string) $this->getAttribute('mode', false);
		$useStep        = Worker::toBoolean($this->getAttribute('usestep', false), false);

		switch ($controller)
		{
			case 'validationrule':
			case 'validation':
				$res = $this->loadFromGroupId(null);
				break;
			case 'visualization':
			case 'element':
				$res = $this->_elementOptions($connection);
				break;
			case 'listform':
			case 'list':
			case 'module':
			case 'item':
			case 'lizt':
				// Menu item
				$res = $this->_listOptions($controller, $valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
				break;
			case 'form':
				$res = $this->_formOptions($valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
				break;
			case 'group':
				$res = $this->_groupOptions($useStep, $valueFormat, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
				break;
			default:
				return Text::_('The ListFields element is only usable by lists and elements');
				break;
		}

		$return = '';

		if (is_array($res))
		{
			$aEls = $this->_formatOptions($res, $valueFormat);

			// For pk fields - we are no longer storing the key with '`' as that's mySQL specific
			$this->value = str_replace('`', '', $this->value);

			// Some elements were stored as names but subsequently changed to ids (need to check for old values an substitute with correct ones)
			if ($valueFormat == 'id' && !is_numeric($this->value) && $this->value != '')
			{
				if ($formModel)
				{
					$elementModel = $formModel->getElement($this->value);
					$this->value  = $elementModel ? $elementModel->getId() : $this->value;
				}
			}

			if ($mode === 'gui')
			{
				$this->js($aEls);
				$return = $this->gui();
			}
			else
			{
				$return = JHTML::_('select.genericlist', $aEls, $this->name, 'class="inputbox" size="1" ', 'value', 'text', $this->value, $this->id);
				$return .= '<img style="margin-left:10px;display:none" id="' . $this->id
					. '_loader" src="components/com_fabrik/images/ajax-loader.gif" alt="' . Text::_('LOADING') . '" />';
			}
		}

		HTML::framework();
		HTML::iniRequireJS();

		return $return;
	}

	/**
	 * Format options
	 *
	 * @param array $res
	 * @param       $valueFormat
	 *
	 * @return array
	 * @throws Exception
	 */
	private function _formatOptions(array $res, $valueFormat)
	{
		$aEls       = array();
		$input      = JFactory::getApplication()->input;
		$controller = $input->get('view', $input->get('task'));

		if ($controller == 'element')
		{
			foreach ($res as $o)
			{
				$s = new stdClass;

				// Element already contains correct key
				if ($controller != 'element')
				{
					$s->value = $valueFormat == 'tableelement' ? $o->table_name . '.' . $o->text : $o->value;
				}
				else
				{
					$s->value = $o->value;
				}

				$s->text = String::getShortDdLabel($o->text);
				$aEls[]  = $s;
			}
		}
		else
		{
			foreach ($res as &$o)
			{
				$o->text = String::getShortDdLabel($o->text);
			}

			$aEls = $res;
		}

		// Paul - Prepend rather than append "none" option.
		array_unshift($aEls, JHTML::_('select.option', '', '-'));

		return $aEls;
	}

	/**
	 * Get element options
	 *
	 * @param $connection
	 *
	 * @return array
	 */
	private function _elementOptions($connection)
	{
		if ($connection == '')
		{
			$res = $this->loadFromGroupId(null);
		}
		else
		{
			$this->js();
			$o             = new stdClass;
			$o->table_name = '';
			$o->name       = '';
			$o->value      = '';
			$o->text       = Text::_('COM_FABRIK_SELECT_A_TABLE_FIRST');
			$res[]         = $o;
		}

		return $res;
	}

	/**
	 * Get list options
	 *
	 * @param $controller
	 * @param $valueFormat
	 * @param $useStep
	 * @param $onlyListFields
	 * @param $showRaw
	 * @param $pluginFilters
	 * @param $labelMethod
	 * @param $noJoins
	 *
	 * @return array|void
	 * @throws Exception
	 */
	private function _listOptions($controller, $valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins)
	{
		$app = JFactory::getApplication();

		if (!isset($this->form->model))
		{
			if (!in_array($controller, array('item', 'module')))
			{
				// Seems to work anyway in the admin module page - so lets not raise notice
				$app->enqueueMessage('Model not set in listfields field ' . $this->id, 'notice');
			}

			return;
		}

		$listModel = $this->form->model;
		$valField  = $valueFormat == 'tableelement' ? 'name' : 'id';
		$res       = $listModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);

		return $res;
	}

	/**
	 * Form options
	 *
	 * @param $valueFormat
	 * @param $useStep
	 * @param $onlyListFields
	 * @param $showRaw
	 * @param $pluginFilters
	 * @param $labelMethod
	 * @param $noJoins
	 *
	 * @return array
	 */
	private function _formOptions($valueFormat, $useStep, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins)
	{
		if (!isset($this->form->model))
		{
			throw new RuntimeException('Model not set in listfields field ' . $this->id);

			return;
		}

		$formModel = $this->form->model;
		$valField  = $valueFormat == 'tableelement' ? 'name' : 'id';
		$res       = $formModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);

		$jsRes = $formModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
		array_unshift($jsRes, JHTML::_('select.option', '', Text::_('COM_FABRIK_PLEASE_SELECT')));
		$this->js($jsRes);

		return $res;
	}

	/**
	 * Get group view options
	 *
	 * @param $useStep
	 * @param $valueFormat
	 * @param $onlyListFields
	 * @param $showRaw
	 * @param $pluginFilters
	 * @param $labelMethod
	 * @param $noJoins
	 *
	 * @return array
	 */
	private function _groupOptions($useStep, $valueFormat, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins)
	{
		$valField   = $valueFormat == 'tableelement' ? 'name' : 'id';
		$id         = $this->form->getValue('id');
		$groupModel = new Group;
		$groupModel->get('groupid', $id);
		$formModel = $groupModel->getFormModel();

		return $formModel->getElementOptions($useStep, $valField, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
	}

	/**
	 * Get JS
	 *
	 * @param array $res
	 */
	private function js($res = array())
	{
		$connection        = $this->getAttribute('connection');
		$repeat            = Worker::toBoolean($this->getAttribute('repeat', false), false);
		$repeat            = AdminElement::getRepeat($this) || $repeat;
		$c                 = (int) AdminElement::getRepeatCounter($this);
		$mode              = $this->getAttribute('mode');
		$connectionDd      = $repeat ? $connection . '-' . $c : $connection;
		$highlightPk       = Worker::toBoolean($this->getAttribute('highlightpk', false), false);
		$tableDd           = $this->getAttribute('table');
		$opts              = new stdClass;
		$opts->table       = ($repeat) ? 'jform_' . $tableDd . '-' . $c : 'jform_' . $tableDd;
		$opts->conn        = 'jform_' . $connectionDd;
		$opts->value       = $this->value;
		$opts->repeat      = $repeat;
		$opts->showAll     = (int) $this->getAttribute('showall', '1');
		$opts->highlightpk = (int) $highlightPk;
		$opts->mode        = $mode;
		$opts->defaultOpts = $res;
		$opts->addBrackets = Worker::toBoolean($this->getAttribute('addbrackets', false), false);
		$opts              = json_encode($opts);
		$script            = array();
		$script[]          = "if (typeOf(FabrikAdmin.model.fields.listfields) === 'null') {";
		$script[]          = "FabrikAdmin.model.fields.listfields = {};";
		$script[]          = "}";
		$script[]          = "if (FabrikAdmin.model.fields.listfields['$this->id'] === undefined) {";
		$script[]          = "FabrikAdmin.model.fields.listfields['$this->id'] = new ListFieldsElement('$this->id', $opts);";
		$script[]          = "}";
		$script            = implode("\n", $script);

		$srcs   = array();
		$srcs[] = 'media/com_fabrik/js/fabrik.js';
		$srcs[] = 'administrator/components/com_fabrik/models/fields/listfields.js';
		HTML::script($srcs, $script);
	}

	/**
	 * Build GUI for adding in elements
	 *
	 * @return  string  Textarea GUI
	 */

	private function gui()
	{
		$str       = array();
		$modeField = (string) $this->getAttribute('modefield', 'textarea');

		if ($modeField === 'textarea')
		{
			$str[] = '<textarea cols="20" row="3" id="' . $this->id . '" name="' . $this->name . '">' . $this->value . '</textarea>';
		}
		else
		{
			$str[] = '<input class="input" id="' . $this->id . '" name="' . $this->name . '" value="' . $this->value . '" />';
		}

		$str[] = '<button class="button btn"><span class="icon-arrow-left"></span> ' . Text::_('COM_FABRIK_ADD') . '</button>';
		$str[] = '<select class="elements"></select>';

		return implode("\n", $str);
	}

	/**
	 * Load the element list from the group id
	 *
	 * @param   int $groupId Group id
	 *
	 * @since   3.0.6
	 *
	 * @return array
	 */

	protected function loadFromGroupId($groupId)
	{

		$input          = $this->app->input;
		$controller     = $input->get('view', $input->get('task'));
		$valueFormat    = (string) ArrayHelper::getValue($this->element, 'valueformat', 'id');
		$onlyListFields = (int) ArrayHelper::getValue($this->element, 'onlylistfields', 0);
		$pluginFilters  = trim($this->element['filter']) == '' ? array() : explode('|', $this->element['filter']);
		$labelMethod    = (string) ArrayHelper::getValue($this->element, 'label_method');
		$noJoins        = (bool) ArrayHelper::getValue($this->element, 'nojoins', false);

		$bits    = array();
		$showRaw = Worker::toBoolean($this->getAttribute('raw', false), false);
		$formModel = $this->form->model->getFormModel();
		$optsKey   = $valueFormat == 'tableelement' ? 'name' : 'id';
		$useStep   = Worker::toBoolean($this->getAttribute('usestep', false), false);
		$res       = $formModel->getElementOptions($useStep, $optsKey, $onlyListFields, $showRaw, $pluginFilters, $labelMethod, $noJoins);
		$hash      = $controller . '.' . implode('.', $bits);

		if (array_key_exists($hash, $this->results))
		{
			$res = $this->results[$hash];
		}
		else
		{
			$this->results[$hash] = &$res;
		}

		return $res;
	}
}
