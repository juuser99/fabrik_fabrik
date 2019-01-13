<?php
/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\Form;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\OaiModel;

/**
 * Fabrik Raw Form View
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class OaiView extends BaseView
{
	/**
	 * Access value
	 *
	 * @var  int
	 *
	 * @since 4.0
	 */
	public $access = null;

	/**
	 * @var OaiModel
	 *
	 * @since 4.0
	 */
	private $oaiModel;

	/**
	 * Constructor
	 *
	 * @param   array $config A named configuration array for object construction.
	 *
	 * @since 4.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->oaiModel = FabrikModel::getInstance(OaiModel::class);
	}

	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$this->doc->setMimeEncoding('application/xml');
		$model = $this->getModel('form');
		$model->render();

		// @TODO replace with OAI errors.
		if (!$this->canAccess())
		{
			return false;
		}

		$listModel = $model->getListModel();
		$this->oaiModel->setListModel($listModel);
		$this->oaiModel->setRecord($model->getData());
		$dom = $this->oaiModel->getRecord();

		echo $dom->saveXML();
	}
}
