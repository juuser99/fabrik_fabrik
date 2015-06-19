<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.attending
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Fabrik\Plugins\Element;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

use Fabrik\Helpers\Worker;
use \stdClass;
use \Exception;
use \JFactory;

/**
 * Plugin element to allow user to attend events, join groups etc.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.attending
 * @since       3.5
 */
class Attending extends Element
{
	/**
	 * Db table field type
	 *
	 * @var string
	 */
	protected $fieldDesc = 'TINYINT(%s)';

	/**
	 * Db table field size
	 *
	 * @var string
	 */
	protected $fieldSize = '1';

	/**
	 * Draws the html form element
	 *
	 * @param   array $data          to pre-populate element with
	 * @param   int   $repeatCounter repeat group counter
	 *
	 * @return  string    elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$id = $this->getHTMLId($repeatCounter);

		$layout            = $this->getLayout('form');
		$displayData              = new stdClass;
		$displayData->attendees = $this->getAttendees();
		$displayData->id        = $id;

		return $layout->render($displayData);
	}

	/**
	 * Get attendees
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	protected function getAttendees()
	{
		$input     = $this->app->input;
		$listModel = $this->getListModel();
		$list      = $listModel->getTable();
		$listId    = $list->get('id');
		$formId    = $listModel->getFormModel()->getId();
		$db        = $listModel->getDb();
		$query     = $db->getQuery(true);
		$rowId    = $input->get('row_id');

		$query->select('*')->from('#__fabrik_attending')->where('list_id = ' . (int) $listId)
			->where('form_id = ' . (int) $formId)
			->where('row_id = ' . $db->q($rowId))
			->where('element_id = ' . (int) $this->getId());

		$attending = $db->setQuery($query)->loadObjectList();

		foreach ($attending as &$attend)
		{
			$attend->user = \JFactory::getUser($attend->user_id);
		}

		return $attending;
	}

	/**
	 * Called via widget ajax, stores the selected rating and returns the average
	 *
	 * @return  void
	 */
	public function onAjax_rate()
	{
		$input = $this->app->input;
		$this->setId($input->getInt('element_id'));
		$this->loadMeForAjax();
		$listModel = $this->getListModel();
		$list      = $listModel->getTable();
		$listId    = $list->get('id');
		$formId    = $listModel->getFormModel()->getId();
		$rowId    = $input->get('row_id');
		$rating    = $input->getInt('rating');
		$this->doRating($listId, $formId, $rowId, $rating);

		if ($input->get('mode') == 'creator-rating')
		{
			// @todo FIX for joins as well

			// Store in elements table as well
			$db      = $listModel->getDb();
			$element = $this->getElement();
			$query   = $db->getQuery(true);
			$pk = $db->qn($list->get('list.db_primary_key'));
			$query->update($db->qn($list->get('list.db_table_name')))
				->set($element->get('name') . '=' . $rating)->where($pk . ' = ' . $db->q($rowId));
			$db->setQuery($query);
			$db->execute();
		}

		$this->getRatingAverage('', $listId, $formId, $rowId);

		echo $this->avg;
	}

	/**
	 * Main method to store a rating
	 *
	 * @param   int    $listId List id
	 * @param   int    $formId Form id
	 * @param   string $rowId Row reference
	 * @param   int    $rating Rating
	 *
	 * @return  void
	 */
	private function doRating($listId, $formId, $rowId, $rating)
	{
		$this->createRatingTable();
		$db        = Worker::getDbo(true);
		$tzOffset  = $this->config->get('offset');
		$date      = JFactory::getDate('now', $tzOffset);
		$strDate   = $db->q($date->toSql());
		$userId    = $this->user->get('id');
		$elementId = (int) $this->getElement()->get('id');
		$formId    = (int) $formId;
		$listId    = (int) $listId;
		$rating    = (int) $rating;
		$rowId    = $db->q($rowId);
		$db
			->setQuery(
				"INSERT INTO #__fabrik_ratings (user_id, listid, formid, row_id, rating, date_created, element_id)
		values ($userId, $listId, $formId, $rowId, $rating, $strDate, $elementId)
			ON DUPLICATE KEY UPDATE date_created = $strDate, rating = $rating"
			);
		$db->execute();
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int $repeatCounter Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$input  = $this->app->input;
		$user   = $this->user;
		$id      = $this->getHTMLId($repeatCounter);
		$rowId  = $input->get('rowid', '', 'string');
		$opts         = new stdClass;
		$opts->row_id = $rowId;
		$opts->elid   = $this->getElement()->get('id');
		$opts->userid = (int) $user->get('id');
		$opts->view   = $input->get('view');

		return array('FbAttending', $id, $opts);
	}

	/**
	 * Get the class to manage the form element
	 * to ensure that the file is loaded only once
	 *
	 * @param   array  &$srcs  Scripts previously loaded
	 * @param   string $script Script to load once class has loaded
	 * @param   array  &$shim  Dependant class names to load before loading the class - put in requirejs.config shim
	 *
	 * @return void
	 */
	public function formJavascriptClass(&$srcs, $script = '', &$shim = array())
	{
		$s                                   = new stdClass;
		$s->deps                             = array('fab/elementlist');
		$shim['element/attending/attending'] = $s;

		parent::formJavascriptClass($srcs, $script, $shim);
	}
}
