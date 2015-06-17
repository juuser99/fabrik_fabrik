<?php
/**
 * Fabrik Admin Home Page View
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\Home;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JToolBarHelper as JToolBarHelper;
use \JHtmlSidebar as JHtmlSidebar;
use \Fabrik\Helpers\HTML as HelperHTML;
use Fabrik\Helpers\Worker;
use Fabrik\Admin\Helpers\Fabrik;

/**
 * Fabrik Admin Home Page View
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
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
	 * @return  string
	 */

	public function render()
	{
		$srcs = HelperHTML::framework();
		HelperHTML::script($srcs);
		$db = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('*')->from('#__fabrik_log')->where('message_type != ""')->order('timedate_created DESC');
		$db->setQuery($query, 0, 10);
		$this->logs = $db->loadObjectList();

		$this->feed = $this->model->getRSSFeed();
		$this->addToolbar();
		Fabrik::addSubmenu('home');

		$this->sidebar = JHtmlSidebar::render();
		$this->setLayout('bootstrap');

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/fabrik.php';
		$canDo = Fabrik::getActions();

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_fabrik');
		}
	}
}
