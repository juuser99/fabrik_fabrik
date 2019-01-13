<?php
/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Profiler\Profiler;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractElementPlugin;

/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @since       3.0
 */
class PlgFabrik_ElementTimestamp extends AbstractElementPlugin
{
	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 *
	 * @var bool  True, ignore in extended search all.
	 *
	 * @since 4.0
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Does the element's data get recorded in the db
	 *
	 * @var bool
	 *
	 * @since 4.0
	 */
	protected $recordInDatabase = false;

	/**
	 * States if the element contains data which is recorded in the database
	 * some elements (e.g. buttons) don't
	 *
	 * @param   array $data posted data
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function recordInDatabase($data = null)
	{
		return false;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          To pre-populate element with
	 * @param   int   $repeatCounter Repeat group counter
	 *
	 * @return  string    elements html
	 *
	 * @since 4.0
	 */
	public function render($data, $repeatCounter = 0)
	{
		$date = Factory::getDate();
		$tz   = new \DateTimeZone($this->config->get('offset'));
		$date->setTimezone($tz);
		$params     = $this->getParams();
		$gmtOrLocal = $params->get('gmt_or_local');
		$gmtOrLocal += 0;

		$layout         = $this->getLayout('form');
		$layoutData     = new \stdClass;
		$layoutData->id = $this->getHTMLId($repeatCounter);;
		$layoutData->name = $this->getHTMLName($repeatCounter);;
		$layoutData->value = $date->toSql($gmtOrLocal);

		return $layout->render($layoutData);
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data    Elements data
	 * @param   \stdClass $thisRow All the data in the lists current row
	 * @param   array     $opts    Rendering options
	 *
	 * @return  string    formatted value
	 *
	 * @since 4.0
	 */
	public function renderListData($data, \stdClass $thisRow, $opts = array())
	{
		$profiler = Profiler::getInstance('Application');
		JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

		$params    = $this->getParams();
		$tz_offset = $params->get('gmt_or_local', '0') == '0';
		$data      = HTMLHelper::_('date', $data, FText::_($params->get('timestamp_format', 'DATE_FORMAT_LC2')), $tz_offset);

		return parent::renderListData($data, $thisRow, $opts);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 *
	 * @since 4.0
	 */
	public function getFieldDescription()
	{
		$params = $this->getParams();

		if ($params->get('encrypt', false))
		{
			return 'BLOB';
		}

		if ($params->get('timestamp_update_on_edit'))
		{
			return "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
		}
		else
		{
			return "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
		}
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function isHidden()
	{
		return true;
	}
}
