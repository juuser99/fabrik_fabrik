<?php
/**
 * Base HTML view
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.5
 */

namespace Fabrik\Admin\Views;

use \Jmodel as JModel;
use \SplPriorityQueue as SplPriorityQueue;
use \JFactory as JFactory;
use \FabrikHelperHTML as FabrikHelperHTML;
use \JHTML as JHTML;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Base Fabrik HTML view
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Html Extends \JViewHtml
{
	/**
	 * Method to instantiate the view.
	 *
	 * @param   JModel            $model  The model object.
	 * @param   SplPriorityQueue  $paths  The paths queue.
	 *
	 * @since   12.1
	 */
	public function __construct(JModel $model, SplPriorityQueue $paths = null)
	{
		parent::__construct($model, $paths);
		$input = JFactory::getApplication()->input;

		if ($input->get('format', 'html') === 'html')
		{
			FabrikHelperHTML::framework();
		}

		JHTML::stylesheet('administrator/components/com_fabrik/headings.css');
	}
}