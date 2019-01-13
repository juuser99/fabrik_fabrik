<?php
/**
 * Raw Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Joomla\CMS\Factory;
use Fabrik\Component\Fabrik\Administrator\Model\ElementModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;

/**
 * Raw Element controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.0
 */
class ElementRawController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_ELEMENT';

	/**
	 * Default view
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $default_view = 'element';

	/**
	 * @var string
	 *
	 * @since since 4.0
	 */
	protected $context = 'element';

	/**
	 * Called via ajax to load in a given plugin's HTML settings
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function getPluginHTML()
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$plugin = $input->get('plugin');
		/** @var ElementModel $model */
		$model  = $this->getModel();
		$model->setState('element.id', $input->getInt('id'));
		$model->getForm();
		$html = $model->getPluginHTML($plugin);
		$html .= HTML::addJoomlaScriptOptions(false);
		echo $html;
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since 4.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$app       = Factory::getApplication();
		$input     = $app->input;
		/** @var ListModel $listModel */
		$listModel = $this->getModel(ListModel::class);
		$listModel->setId($input->getInt('listid'));
		$rowId = $input->get('rowid', '', 'string');
		$key   = $input->get('element');
		$key   = array_pop(explode('___', $key));
		$value = $input->get('value', '', 'string');
		$listModel->storeCell($rowId, $key, $value);
		$this->mode = 'readonly';
		$this->display();
	}
}
