<?php
/**
 * Connections controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Admin\Controllers;

// No direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\Utilities\ArrayHelper;
/**
 * Connections list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       3.5
 */
class Connections extends Controller
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 */
	protected $text_prefix = 'COM_FABRIK_CONNECTIONS';

	/**
	 * View item name
	 *
	 * @var string
	 */
	protected $view_item = 'connections';

	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   3.5
	 */
	public function execute()
	{
		$app = $this->getApplication();
		list($viewName, $layoutName) = $this->viewLayout();
		$modelClass = 'Fabrik\Admin\Models\\' . ucfirst($viewName);
		$model      = new $modelClass;

		$ids        = $app->input->get('cid', array(), 'array');
		$id         = $app->input->get('id', ArrayHelper::getValue($ids, 0));
		$listUrl    = $this->listUrl($viewName);

		switch ($layoutName)
		{
			case 'unsetDefault':
				$model->setDefault(false, $ids);
				$app->redirect($listUrl);
				break;

			case 'setDefault':
				$model->setDefault(true, $ids);
				$app->redirect($listUrl);
				break;

			case 'test':
				$model->set('id', $id);
				$model->test();
				$app->redirect($listUrl);
				break;
			default:
				parent::execute();
				break;
		}
	}
}
