<?php
/**
 * Renders a list of installed image libraries
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Joomla\Component\Fabrik\Administrator\Field;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Image;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Renders a list of installed image libraries
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       3.0
 */
class ImageLibsField extends ListField
{
	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'imagelibs';

	/**
	 * Element name
	 * @access    protected
	 * @var        string
	 *
	 * @since     4.0
	 */
	protected $name = 'Imagelibs';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since 4.0
	 */
	protected function getOptions()
	{
		$imageLibs = Image::getLibs();

		if (empty($imageLibs))
		{
			return HTMLHelper::_('select.option', Text::_('COM_FABRIK_IMAGELIBS_NOT_FOUND'));
		}

		return $imageLibs;
	}
}
