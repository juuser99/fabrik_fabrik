<?php
/**
 * List Copy Row plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.copy
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Lizt;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Text;

/**
 * Add an action button to the list to copy rows
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.copy
 * @since       3.0
 */
class Copy extends Lizt
{
	/**
	 * Button prefix
	 *
	 * @var string
	 */
	protected $buttonPrefix = 'copy';

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args  Arguments
	 *
	 * @return  bool;
	 */
	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 */
	protected function getAclParam()
	{
		return 'copytable_access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 */
	public function canSelectRows()
	{
		return true;
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array  $opts  Custom options
	 *
	 * @return  bool
	 */
	public function process($opts = array())
	{
		$model = $this->getModel();
		$ids = $this->app->input->get('ids', array(), 'array');

		return $model->copyRows($ids);
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int  $c  plugin render order
	 *
	 * @return  string
	 */
	public function process_result($c)
	{
		$ids = $this->app->input->get('ids', array(), 'array');

		return Text::sprintf('PLG_LIST_ROWS_COPIED', count($ids));
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array  $args  Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 */
	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts = $this->getElementJSOptions();
		$opts = json_encode($opts);
		$this->jsInstance = "new FbListCopy($opts)";

		return true;
	}
}
