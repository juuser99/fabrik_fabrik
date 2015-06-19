<?php
/**
 * Fabrik JS-Periodical - run JS every x ms
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.jsperiodical
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Element;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \stdClass;

/**
 * Plugin element: js periodical will fire a JavaScript function at a definable interval
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.jsperiodical
 * @since       3.5
 */
class JSPeriodical extends Element
{
	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      elements data
	 * @param   stdClass  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow)
	{
		$params = $this->getParams();
		$format = $params->get('text_format_string');

		if ($format != '')
		{
			$str = sprintf($format, $data);
			$data = eval($str);
		}

		return parent::renderListData($data, $thisRow);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->attributes = $this->inputProperties($repeatCounter);;
		$layoutData->value = $this->getValue($data, $repeatCounter);;
		$layoutData->isEditable = $this->isEditable();
		$layoutData->hidden = $this->getElement()->get('hidden')  == '1';

		return $layout->render($layoutData);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->code = $params->get('jsperiod_code');
		$opts->period = $params->get('jsperiod_period');

		return array('FbJSPeriodical', $id, $opts);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */
	public function getFieldDescription()
	{
		$p = $this->getParams();

		if ($this->encryptMe())
		{
			return 'BLOB';
		}

		switch ($p->get('text_format'))
		{
			case 'text':
			default:
				$type = "VARCHAR(255)";
				break;
			case 'integer':
				$type = "INT(" . $p->get('integer_length', 10) . ")";
				break;
			case 'decimal':
				$type = "DECIMAL(" . $p->get('integer_length', 10) . "," . $p->get('decimal_length', 2) . ")";
				break;
		}

		return $type;
	}
}
