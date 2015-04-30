<?php
/**
 * View to edit a cron.
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Cron;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * View to load up a specific cron plugin's html
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Raw extends \JViewBase
{
	/**
	 * Render the view
	 *
	 * @return  string
	 */

	public function render()
	{
		$model = $this->model;
		echo $model->getPluginHTML();
	}
}
