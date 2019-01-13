<?php
/**
 * Execute PHP Code on any list event
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.phpevents
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Component\Fabrik\Site\Plugin\AbstractListPlugin;
use Fabrik\Helpers\Worker;

/**
 * Execute PHP Code on any list event
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.phpevents
 * @since       3.0
 */
class PlgFabrik_ListPhp_Events extends AbstractListPlugin
{
	/**
	 * onFiltersGot method - run after the list has created filters
	 *
	 * @return bool currently ignored
	 *
	 * @since 4.0
	 */
	public function onFiltersGot()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onfiltersgot'));
	}

	/**
	 * onStoreRequestData method - run when filter data is stored to the session
	 *
	 * @param array &$args Additional options passed into the method when the plugin is called
	 *
	 * @return bool currently ignored
	 *
	 * @since 4.0
	 */
	public function onStoreRequestData(&$args)
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onstorerequestdata'), $args);
	}

	/**
	 * Called when the list HTML filters are loaded
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function onMakeFilters()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onmakefilters'));
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array $opts Custom options
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function process($opts = array())
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_process'));
	}

	/**
	 * Run before the list loads its data
	 *
	 * @return bool currently ignored
	 *
	 * @since 4.0
	 */
	public function onPreLoadData(&$args)
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onpreloaddata'));
	}

	/**
	 * onGetData method
	 *
	 * @param array  &$args Additional options passed into the method when the plugin is called
	 *
	 * @return bool currently ignored
	 *
	 * @since 4.0
	 */
	public function onLoadData(&$args)
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onloaddata'), $args);
	}

	/**
	 * Called when the model deletes rows
	 *
	 * @return  bool  false if fail
	 *
	 * @since 4.0
	 */
	public function onDeleteRows()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_ondeleterows'));
	}

	/**
	 * Called after the model has deleted rows
	 *
	 * @return  bool  false if fail
	 *
	 * @since 4.0
	 */
	public function onAfterDeleteRows()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onafterdeleterows'));
	}

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args Arguments
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Build the HTML for the plug-in button
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function button_result()
	{
		return '';
	}

	/**
	 * Determine if we use the plugin or not
	 * both location and event criteria have to be match when form plug-in
	 *
	 * @param   string $location Location to trigger plugin on
	 * @param   string $event    Event to trigger plugin on
	 *
	 * @return  bool  true if we should run the plugin otherwise false
	 *
	 * @since 4.0
	 */
	public function canUse($location = null, $event = null)
	{
		return true;
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array $args Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function onLoadJavascriptInstance($args)
	{
		return true;
	}

	/**
	 * On build query where
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onBuildQueryWhere()
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_onbuildquerywhere'));
	}

	/**
	 * On build query where
	 *
	 * @param array  &$args Additional options passed into the method when the plugin is called
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function onGetPluginRowHeadings(&$args)
	{
		$params = $this->getParams();

		return $this->doEvaluate($params->get('list_phpevents_ongetpluginrowheadings'), $args);
	}

	/**
	 * Evaluate supplied PHP
	 *
	 * @param array  & $args Additional options passed into the method when the plugin is called
	 *
	 * @param   string $code Php code
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	protected function doEvaluate($code, &$args = array())
	{
		$model = $this->getModel();
		$w     = new Worker;
		$code  = $w->parseMessageForPlaceHolder($code);

		if ($code != '')
		{
			if (eval($code) === false)
			{
				return false;
			}
		}

		return true;
	}
}
