<?php
/**
 * Single element raw view
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\PluginManagerModel;
use Fabrik\Component\Fabrik\Site\View\AbstractView;

/**
 * Single element raw view
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class RawView extends AbstractView
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
	 * Is mambot (not used?)
	 *
	 * @var bool
	 *
	 * @since 4.0
	 */
	public $isMambot = null;

	/**
	 * Set id
	 *
	 * @param int $id Element id
	 *
	 * @return  void
	 *
	 * @deprecated ?
	 *
	 * @since      4.0
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Display the template
	 *
	 * @param string $tpl Template
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$input = $this->app->input;
		/** @var PluginManagerModel $pluginManager */
		$pluginManager = FabrikModel::getInstance(PluginManagerModel::class);
		$ids           = $input->get('plugin', array(), 'array');

		foreach ($ids as $id)
		{
			$plugin = $pluginManager->getElementPlugin($id);
		}
	}
}
