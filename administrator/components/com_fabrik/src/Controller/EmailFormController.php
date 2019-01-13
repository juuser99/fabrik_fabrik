<?php
/**
 * Fabrik Email From Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Component\Fabrik\Administrator\Model\FabrikModel;
use Joomla\Component\Fabrik\Site\Model\FormModel;

/**
 * Fabrik Email From Controller
 *
 * @package  Fabrik
 * @since    4.0
 */
class EmailFormController extends AbstractAdminController
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $context = 'emailform';

	/**
	 * Typical view method for MVC based architecture
	 *
	 * This function is provide as a default implementation, in most cases
	 * you will need to override it in your own controllers.
	 *
	 * @param   boolean $cachable  If true, the view output will be cached
	 * @param   array   $urlparams An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @since   4.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$document = Factory::getDocument();
		$app      = Factory::getApplication();
		$input    = $app->input;
		$viewName = $input->get('view', 'emailform');
		$viewType = $document->getType();

		// Set the default view name from the Request
		$view = $this->getView($viewName, $viewType);

		// Push a model into the view (may have been set in content plugin already)
		/** @var FormModel $model */
		if ($model = FabrikModel::getInstance(FormModel::class))
		{
			$view->setModel($model, true);
		}
		// Display the view
		$view->error = $this->getError();
		$view->display();
	}
}
