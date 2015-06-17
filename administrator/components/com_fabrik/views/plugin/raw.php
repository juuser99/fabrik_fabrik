<?php
/**
 * View to grab plugin form fields.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Plugin;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JFactory as JFactory;
use \FText as FText;
use \JForm as JForm;

/**
 * View to grab plugin form fields.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Raw extends \JViewHtml
{
	/**
	 * Render the view
	 *
	 * @return  void
	 */
	public function render()
	{
		$model = $this->model;
		$app   = JFactory::getApplication();
		$this->setStates();

		if ($app->input->get('task') == 'top')
		{
			echo $this->top();

			return;
		}

		echo $model->render();
	}

	/**
	 * Render the top part of the plugin form
	 *
	 * @return string
	 */
	protected function top()
	{
		$data                   = $this->model->getData();
		$type                   = $this->model->get('type');
		$c                      = $this->model->get('c') + 1;
		$str                    = array();
		$str[]                  = '<div class="pane-slider content pane-down accordion-inner">';
		$str[]                  = '<fieldset class="form-horizontal pluginContainer" id="formAction_' . $c . '"><ul>';
		$formName               = 'com_fabrik.' . $type . '-plugin';
		$topForm                = new JForm($formName, array('control' => 'jform'));
		$topForm->repeatCounter = $c;
		$xmlFile                = JPATH_SITE . '/administrator/components/com_fabrik/models/forms/' . $type . '-plugin.xml';

		// Add the plugin specific fields to the form.
		$topForm->loadFile($xmlFile, false);
		$topForm->bind($data);
		$topForm->model = $this->model;

		// Filter the forms fieldsets for those starting with the correct $searchName prefix
		foreach ($topForm->getFieldsets() as $fieldset)
		{
			if ($fieldset->label != '')
			{
				$str[] = '<legend>' . $fieldset->label . '</legend>';
			}
			foreach ($topForm->getFieldset($fieldset->name) as $field)
			{
				$str[] = '<div class="control-group"><div class="control-label">' . $field->label;
				$str[] = '</div><div class="controls">' . $field->input . '</div></div>';
			}
		}

		$str[] = '</ul>';
		$str[] = '<div class="pluginOpts" style="clear:left"></div>';
		$str[] = '<div class="form-actions"><a href="#" class="btn btn-danger" data-button="removeButton">';
		$str[] = '<i class="icon-delete"></i> ' . FText::_('COM_FABRIK_DELETE') . '</a></div>';
		$str[] = '</fieldset>';
		$str[] = '</div>';

		return implode("\n", $str);
	}

	/**
	 * Set the model state from request
	 *
	 * @return  void
	 */
	protected function setStates()
	{
		$model = $this->model;
		$app   = JFactory::getApplication();
		$input = $app->input;
		$model->set('type', $input->get('type'));
		$model->set('plugin', $input->get('plugin'));
		$model->set('c', $input->getInt('c'));
		$model->set('id', $input->getString('id', ''));
		$model->set('plugin_published', $input->get('plugin_published'));
		$model->set('show_icon', $input->get('show_icon'));
		$model->set('validate_in', $input->get('validate_in'));
		$model->set('validation_on', $input->get('validation_on'));
	}
}
