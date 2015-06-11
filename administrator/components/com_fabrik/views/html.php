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

	/**
	 * Load a template file
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function loadTemplate($template = '')
	{
		$file = $template === '' ? $this->layout : $this->layout . '_' . $template;
		$this->_template = $this->getPath($file);
		ob_start();

		// Include the requested template filename in the local scope
		// (this will execute the view logic).
		include $this->_template;

		// Done with the requested template; get the buffer and
		// clear it.
		$this->_output = ob_get_contents();
		ob_end_clean();

		return $this->_output;
	}

	/**
	 * Get model
	 *
	 * @return JModel
	 */
	public function getModel()
	{
		return $this->model;
	}

	public function setModel()
	{
		return $this->model;
	}
}