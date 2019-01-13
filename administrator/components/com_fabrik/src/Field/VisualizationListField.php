<?php
/**
 * Renders a list of Fabrik visualizations
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Renders a list of Fabrik visualizations
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       4.0
 */
class VisualizationListField extends ListField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'visualizationlist';

	/**
	 * Element name
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Visualizationlist';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$a     = array(HTMLHelper::_('select.option', '', Text::_('COM_FABRIK_PLEASE_SELECT')));
		$db    = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('id AS value, label AS text')->from('#__{package}_visualizations')->where('published = 1')->order('text');
		$db->setQuery($query);
		$elementstypes = $db->loadObjectList();
		$elementstypes = array_merge($a, $elementstypes);

		return $elementstypes;
	}
}
