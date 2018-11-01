<?php
/**
 * Fabrik Admin Home Page View
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
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
 * @since       4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Recently logged activity
	 *
	 * @var  array
	 *
	 * @since 4.0
	 */
	protected $logs;

	/**
	 * RSS feed
	 *
	 * @var  array
	 *
	 * @since 4.0
	 */
	protected $feed;

	/**
	 * @var \JHtmlSidebar
	 *
	 * @since since 4.0
	 */
	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string $tpl template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$srcs = Html::framework();
		Html::script($srcs);

		$db    = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*')->from('#__{package}_log')->where('message_type != ""')->order('timedate_created DESC');
		$db->setQuery($query, 0, 10);

		$this->logs = $db->loadObjectList();
		$this->feed = $this->get('RSSFeed');

		\FabrikAdminHelper::addSubmenu('home');
		\FabrikAdminHelper::setViewLayout($this);

		$this->sidebar = \JHtmlSidebar::render();

		Html::iniRequireJS();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since 4.0
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
