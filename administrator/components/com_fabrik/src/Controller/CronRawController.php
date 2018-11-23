<?php
/**
 * Raw:  Cron controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

/**
 * Cron controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class CronRawController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_CRON';

	/**
	 * @var string
	 *
	 * @since since 4.0
	 */
	protected $context = 'cron';

	/**
	 * Called via ajax to load in a given plugin's HTML settings
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function getPluginHTML()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$plugin = $input->getCmd('plugin');
		$model = $this->getModel();
		$model->getForm();
		echo $model->getPluginHTML($plugin);
	}
}
