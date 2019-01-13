<?php
/**
 * Fabrik Home Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Component Controller
 *
 * @since  4.0
 */
class DisplayController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 *
	 * @since  4.0
	 */
	protected $default_view = 'home';


	/**
	 * @param string $name
	 * @param string $type
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return \Joomla\CMS\MVC\View\AbstractView
	 *
	 * @since version
	 * @throws \Exception
	 */
	public function getView($name = '', $type = '', $prefix = '', $config = array())
	{
		if ('list' === $name) {
			// Workaround for PHP reserved word that doesn't allow us to name the view folder "List"
			$name = "listView";
		}

		return parent::getView($name, $type, '', $config);
	}
}
