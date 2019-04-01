<?php
/**
 * The cron notification view, shows a list of the user's current notifications
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikCron\Notification\View\Notification;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\View\AbstractView;
use Fabrik\Plugin\FabrikCron\Notification\Model\NotificationModel;

/**
 * The cron notification view, shows a list of the user's current notifications
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.cron.notification
 * @since       4.0
 */
class HtmlView extends AbstractView
{
	/**
	 * Still a wip access the view of subscribed notifications with url:
	 * http://localhost/fabrik30x/index.php?option=com_fabrik&task=cron.display&id=3
	 *
	 * deletion not routing right yet
	 *
	 * @param string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		/** @var NotificationModel $model */
		$model = $this->getModel();
		$model->loadLang();
		$this->rows = $model->getUserNotifications();
		$this->id   = $model->getId();

		$tmplpath = JPATH_ROOT . '/plugins/fabrik_cron/notification/tmpl/notification/bootstrap';
		$this->_setPath('template', $tmplpath);

		if (null === $tpl)
		{
			$tpl = 'bootstrap';
		}

		// Doesn't exist?
		//Html::stylesheetFromPath('plugins/fabrik_cron/notification/tmpl/notification/' . $tpl . '/template.css');

		echo parent::display();
	}
}
