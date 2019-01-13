<?php
/**
 * FabForm controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

/**
 * FabForm controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class AbstractFormController extends FormController
{
	use ModelTrait;

	/**
	 * JApplication
	 *
	 * @var CMSApplication
	 *
	 * @since 4.0
	 */
	protected $app;

	/**
	 * Option
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $option = 'com_fabrik';

	/**
	 * AbstractFormController constructor.
	 *
	 * @param array                     $config
	 * @param MVCFactoryInterface|null  $factory
	 * @param null                      $app
	 * @param null                      $input
	 * @param FormFactoryInterface|null $formFactory
	 *
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null,
		FormFactoryInterface $formFactory = null)
	{
		$this->app = ArrayHelper::getValue($config, 'app', Factory::getApplication());

		parent::__construct($config, $factory, $app, $input, $formFactory);
	}

	/**
	 * Copy items
	 *
	 * @throws \Exception
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function copy()
	{
		$model = $this->getModel();
		$input = $this->input;
		$cid = $input->get('cid', array(), 'array');

		if (empty($cid))
		{
			throw new \Exception(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'));
		}
		else
		{
			if ($model->copy())
			{
				$nText = $this->text_prefix . '_N_ITEMS_COPIED';
				$this->setMessage(Text::plural($nText, count($cid)));
			}
		}

		$extension = $input->get('extension');
		$extensionURL = ($extension) ? '&extension=' . $extension : '';
		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $extensionURL, false));
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 * (sometimes required to avoid router collisions).
	 *
	 * @since   4.0
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 */
	public function edit($key = null, $urlVar = null)
	{
		$this->option = 'com_fabrik';

		return parent::edit($key, $urlVar);
	}
}
