<?php
/**
 * Fabrik Admin Home Page View
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Home;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;

/**
 * Fabrik Admin Home Page View
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */

class HtmlView extends BaseHtmlView
{
	/**
	 * Recently logged activity
	 * @var  array
	 */
	protected $logs;

	/**
	 * RSS feed
	 * @var  array
	 */
	protected $feed;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  template
	 *
	 * @return  void
	 */

	public function display($tpl = null)
	{
		$srcs = Html::framework();
		Html::script($srcs);
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*')->from('#__{package}_log')->where('message_type != ""')->order('timedate_created DESC');
		$db->setQuery($query, 0, 10);
		$this->logs = $db->loadObjectList();
		$this->feed = $this->get('RSSFeed');
		$this->addToolbar();
		\FabrikAdminHelper::addSubmenu('home');
		\FabrikAdminHelper::setViewLayout($this);

		if (Worker::j3())
		{
			$this->sidebar = \JHtmlSidebar::render();
		}

		Html::iniRequireJS();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		$canDo = FabrikAdminHelper::getActions();

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::divider();
			ToolBarHelper::preferences('com_fabrik');
		}
	}
}
