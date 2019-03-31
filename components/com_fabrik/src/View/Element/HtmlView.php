<?php
/**
 * Fabrik Front End Element View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\Element;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\View\AbstractView;

/**
 * Fabrik Front End Element View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       3.0
 */
class HtmlView extends AbstractView
{
	/**
	 * Element id (not used?)
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	protected $id = null;

	/**
	 * Set id
	 *
	 * @param   int  $id  Element id
	 *
	 * @deprecated ?
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Display the template
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
	}
}
