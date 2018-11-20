<?php
/**
 * Raw Visualization Admin View
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\View\Visualization;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\FormView;

/**
 * Raw Visualization Admin View
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class RawView extends FormView
{
	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		echo "Fabrik Visualization admin raw display";
	}
}
