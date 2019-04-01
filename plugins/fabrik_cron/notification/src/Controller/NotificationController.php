<?php
/**
 * Cron Notification Fabrik Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikCron\Notification\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Controller\AbstractSiteController;
use Fabrik\Plugin\FabrikCron\Notification\Model\NotificationModel;
use Joomla\CMS\Language\Text;

/**
 * Cron Notification Fabrik Plug-in Controller
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       4.0
 */
class NotificationController extends AbstractSiteController
{
	/**
	 * Display the view
	 *
	 * @param boolean $cachable  If true, the view output will be cached - NOTE not actually used to control caching!!!
	 * @param array   $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  $this  A JController object to support chaining.
	 *
	 * @since 4.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$document = $this->app->getDocument();
		$viewName = 'notification';
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view
		if ($model = $this->getModel(NotificationModel::class))
		{
			$view->setModel($model, true);
		}

		// Display the view
		$view->error = $this->getError();
		$view->display();

		return $this;
	}

	/**
	 * Delete a notification
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function delete()
	{
		$model = $this->getModel('notification');
		$model->delete();
		$this->setRedirect('index.php?option=com_fabrikn&task=cron.notification', Text::_('NOTIFICATIONS_REMOVED'));
	}
}
