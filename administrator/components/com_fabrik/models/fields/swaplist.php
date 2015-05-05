<?php
/**
 * Renders widget for (de)selecting available groups when editing a from
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Worker;

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

/**
 * Renders widget for (de)selecting available groups when editing a from
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */
class JFormFieldSwapList extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access    protected
	 * @var        string
	 */
	protected $name = 'SwapList';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 */

	protected function getInput()
	{
		$from     = $this->id . '-from';
		$add      = $this->id . '-add';
		$remove   = $this->id . '-remove';
		$up       = $this->id . '-up';
		$down     = $this->id . '-down';
		$script[] = "window.addEvent('domready', function () {";
		$script[] = "\tswaplist = new SwapList('$from', '$this->id','$add', '$remove', '$up', '$down');";
		$script[] = "});";

		FabrikHelperHTML::framework();
		FabrikHelperHTML::iniRequireJS();
		FabrikHelperHTML::script('administrator/components/com_fabrik/models/fields/swaplist.js', implode("\n", $script));

		list($this->currentGroups, $this->currentGroupList) = $this->getCurrentGroupList();
		list($this->groups, $this->groupList) = $this->getGroupList();

		if (empty($this->groups) && empty($this->currentGroups))
		{
			return FText::_('COM_FABRIK_NO_GROUPS_AVAILABLE');
		}
		else
		{
			$str = FText::_('COM_FABRIK_AVAILABLE_GROUPS');
			$str .= '<br />' . $this->groupList;
			$str .= '<button class="button btn btn-success btn-small" type="button" id="' . $this->id . '-add">';
			$str .= '<i class="icon-new"></i>' . FText::_('COM_FABRIK_ADD') . '</button>';
			$str .= '<br />' . FText::_('COM_FABRIK_CURRENT_GROUPS');
			$str .= '<br />' . $this->currentGroupList;
			$str .= '<button class="button btn btn-small" type="button" id="' . $this->id . '-up" >';
			$str .= '<i class="icon-arrow-up"></i> ' . FText::_('COM_FABRIK_UP') . '</button> ';
			$str .= '<button class="button btn btn-small" type="button" id="' . $this->id . '-down" >';
			$str .= '<i class="icon-arrow-down"></i> ' . FText::_('COM_FABRIK_DOWN') . '</button> ';
			$str .= '<button class="button btn btn-danger btn-small" type="button" id="' . $this->id . '-remove">';
			$str .= '<i class="icon-delete"></i> ' . FText::_('COM_FABRIK_REMOVE');
			$str .= '</button>';

			return $str;
		}
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 */

	protected function getLabel()
	{
		return '';
	}

	/**
	 * get a list of group templates
	 *
	 * @return  array    list of groups, html list of groups
	 */

	public function getGroupList()
	{
		$templates = JFolder::files(JPATH_COMPONENT_ADMINISTRATOR . '/models/templates/groups', '.json', false, true);

		foreach ($templates as $template)
		{
			$template   = json_decode(file_get_contents($template));
			$opt        = new stdClass;
			$opt->value = 'template_' . uniqid();
			$opt->text  = $template->name;
			$options[]  = $opt;
		}

		$groups = array();
		$list   = JHTML::_('select.genericlist', $options, 'jform[groups]', 'class="inputbox" size="10" ', 'value', 'text', null,
			$this->id . '-from');

		return array($groups, $list);
	}

	/**
	 * Get a list of groups currently used by the form
	 *
	 * @return  array  list of groups, html list of groups
	 */
	public function getCurrentGroupList()
	{
		$item    = $this->form->model->getItem();
		$groups  = $item->get('form.groups');
		$options = array();

		foreach ($groups as $group)
		{
			$opt        = new stdClass;
			$opt->value = $group->id;
			$opt->text  = $group->name;
			$options[]  = $opt;
		}

		$attribs = 'class="inputbox" multiple="multiple" size="10" ';
		$list    = JHTML::_('select.genericlist', $options, $this->name, $attribs, 'value', 'text', '/', $this->id);

		return array($options, $list);
	}
}
