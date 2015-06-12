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
use \Joomla\Registry\Registry as Registry;

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
	 * @var JApplicationCms
	 */
	protected $app = null;

	/**
	 * @var JUser
	 */
	protected $user = null;

	/**
	 * @var JDocument
	 */
	protected $doc = null;

	protected $state = null;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   JModel           $model The model object.
	 * @param   SplPriorityQueue $paths The paths queue.
	 * @param   Registry         $state DI options
	 */
	public function __construct(JModel $model, SplPriorityQueue $paths = null, Registry $state = null)
	{
		parent::__construct($model, $paths);

		if (is_null($state))
		{
			$this->state = new Registry();
		}

		$this->app  = $this->state->get('app', JFactory::getApplication());
		$this->user = $this->state->get('user', JFactory::getUser());
		$this->doc  = $this->state->get('doc', JFactory::getDocument());
		$input      = $this->app->input;

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
		$file     = $template === '' ? $this->layout : $this->layout . '_' . $template;
		$template = $this->getPath($file);
		ob_start();

		// Include the requested template filename in the local scope
		// (this will execute the view logic).
		include $template;

		// Done with the requested template; get the buffer and
		// clear it.
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
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

	/**
	 * Set Model
	 *
	 * @param   JModel $model
	 *
	 * @return  void
	 */
	public function setModel(JModel $model)
	{
		$this->model = $model;
	}
}