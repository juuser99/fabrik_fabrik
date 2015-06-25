<?php
/**
 * Is Unique Value Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isuniquevalue
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Validation;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Is Unique Value Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isuniquevalue
 * @since       3.5
 */
class IsUniqueValue extends Validation
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isuniquevalue';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$elementModel = $this->elementModel;

		// Could be a dropdown with multivalues
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params = $this->getParams();
		$element = $elementModel->getElement();
		$listModel = $elementModel->getListModel();
		$table = $listModel->getTable();
		$db = $listModel->getDb();
		$lookuptable = $db->qn($table->get('list.db_table_name'));
		$data = $db->q($data);
		$query = $db->getQuery(true);
		$cond = $params->get('isuniquevalue-caseinsensitive') == 1 ? 'LIKE' : '=';
		$query->select('COUNT(*)')->from($lookuptable)
			->where($db->qn($element->get('name')) . ' ' . $cond . ' ' . $data);

		/* $$$ hugh - need to check to see if we're editing a record, otherwise
		 * will fail 'cos it finds the original record (assuming this element hasn't changed)
		 * @TODO - is there a better way getting the rowid?  What if this is form a joined table?
		 * $rowId = $input->get('rowid');
		 * Have to do it by grabbing PK from request, 'cos rowid isn't set on AJAX validation
		 *
		 * Paul - if pk is an input field, then input pk may not be original so should use rowid
		 * to match the record in the DB that matches THIS record, rather than the user changed pk.
		 * Hugh rightly points out that this does not handle joined tables correctly, but this is
		 * true if we use:
		 * $rowId = $input->get('rowid','');    or
		 * $rowId = $input->get($pk,'');
		 */
		$rowId = $input->get('rowid', '');

		if (!empty($rowId))
		{
			$pk = $db->qn($table->get('list.db_primary_key'));
			$query->where($pk . ' != ' . $db->q($rowId));
		}

		$db->setQuery($query);
		$c = $db->loadResult();

		return ($c === '0') ? true : false;
	}
}
