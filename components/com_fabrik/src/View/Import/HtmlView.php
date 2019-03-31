<?php
/**
 * View class for CSV import
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\View\Import;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Component\Fabrik\Site\View\AbstractView;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\Utilities\ArrayHelper;

/**
 * View class for CSV import
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class HtmlView extends AbstractView
{
	/**
	 * @var ListModel
	 * @since 4.0
	 */
	private $model;

	/**
	 * Display the view
	 *
	 * @param string $tpl template
	 *
	 * @return  $this
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$srcs = Html::framework();
		Html::script($srcs);
		Html::iniRequireJs();
		$input        = $this->app->input;
		$this->listid = $input->getInt('listid', 0);

		/** @var ListModel model */
		$this->model = FabrikModel::getInstance(ListModel::class);
		$this->model->setId($this->listid);
		$this->table = $this->model->getTable();
		$this->form  = $this->get('Form');

		if (!$this->model->canCSVImport())
		{
			throw new \RuntimeException('Naughty naughty!', 400);
		}

		$this->setLayout('bootstrap');
		$this->fieldsets = $this->setFieldSets();

		parent::display($tpl);

		return $this;
	}

	/**
	 * Set which fieldsets should be used
	 *
	 * @return  array  fieldset names
	 * @since   3.0.7
	 */
	private function setFieldSets()
	{
		$input = $this->app->input;

		// From list data view in admin
		$id = $input->getInt('listid', 0);

		// From list of lists checkbox selection
		$cid = $input->get('cid', array(0), 'array');
		$cid = ArrayHelper::toInteger($cid);

		if ($id === 0)
		{
			$id = $cid[0];
		}

		if (($id !== 0))
		{
			$db    = Worker::getDbo();
			$query = $db->getQuery(true);
			$query->select('label')->from('#__{package}_lists')->where('id = ' . $id);
			$db->setQuery($query);
			$this->listName = $db->loadResult();
		}

		$fieldsets = array('details');

		if ($this->model->canEmpty())
		{
			$fieldsets[] = 'drop';
		}

		$fieldsets[] = $id === 0 ? 'creation' : 'append';
		$fieldsets[] = 'format';

		return $fieldsets;
	}
}
