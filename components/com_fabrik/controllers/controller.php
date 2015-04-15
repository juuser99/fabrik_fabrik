<?php
/**
 * Fabrik Controller
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\Utilities\ArrayHelper;

abstract class FabrikController extends JControllerLegacy
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 * Recognized key values include 'name', 'default_task', 'model_path', and
	 * 'view_path' (this list is not meant to be comprehensive).
	 *
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->app = ArrayHelper::getValue($config, 'app', JFactory::getApplication());
		$this->input = $this->app->input;
	}
}