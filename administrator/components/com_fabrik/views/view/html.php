<?php
/**
 * View to edit a list.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Admin\Views\View;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \JFactory as JFactory;
use Fabrik\Admin\Helpers\Fabrik;
use Fabrik\Helpers\Text;
use \JToolBarHelper as JToolBarHelper;
use \stdClass as stdClass;
use Fabrik\Helpers\Worker;
use \JHTML as JHTML;

/**
 * View to edit a list.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html extends \Fabrik\Admin\Views\Html
{
	/**
	 * Render the list
	 *
	 * @return  string
	 */
	public function render()
	{
		JToolBarHelper::title(Text::_('COM_FABRIK_MANAGER_LISTS'), 'lists.png');
		return parent::render();
	}


	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */

	protected function addToolbar()
	{

	}

}
