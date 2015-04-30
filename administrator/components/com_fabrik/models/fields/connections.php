<?php
/**
 * Renders a list of connections
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_fabrik/helpers/element.php';

use Fabrik\Admin\Models\Connections;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Renders a list of connections
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       3.0
 */
class JFormFieldConnections extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @var        string
	 */
	protected $name = 'Connections';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	protected function getOptions()
	{
		$model       = new Connections;
		$connections = $model->getItems();
		$options     = array();

		foreach ($connections as $id => $connection)
		{
			$options[] = (object) array('value' => $id, 'text' => $connection->description, 'default' => '');
		}

		$sel          = JHtml::_('select.option', '', FText::_('COM_FABRIK_PLEASE_SELECT'));
		$sel->default = false;
		array_unshift($options, $sel);

		return $options;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 */

	protected function getInput()
	{
		if ((int) $this->form->getValue('id') == 0 && $this->value == '')
		{
			// Default to default connection on new form where no value specified
			$options = (array) $this->getOptions();

			foreach ($options as $opt)
			{
				if ($opt->default == 1)
				{
					$this->value = $opt->value;
				}
			}
		}

		if ((int) $this->form->getValue('id') == 0 || !$this->element['readonlyonedit'])
		{
			return parent::getInput();
		}
		else
		{
			$options = (array) $this->getOptions();
			$v       = '';

			foreach ($options as $opt)
			{
				if ($opt->value == $this->value)
				{
					$v = $opt->text;
				}
			}
		}

		return '<input type="hidden" value="' . $this->value . '" name="' . $this->name . '" />' . '<input type="text" value="' . $v
		. '" name="connection_justalabel" class="readonly" readonly="true" />';
	}
}
