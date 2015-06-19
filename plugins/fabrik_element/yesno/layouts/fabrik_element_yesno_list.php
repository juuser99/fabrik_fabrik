<?php
/**
 * Layout: Yes/No field list view
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2014 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.2
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\HTML;
use Fabrik\Helpers\Text;

$d = $displayData;
$data = $d->value;
$tmpl = $d->tmpl;

if ($data == '1') :
	echo HTML::image('checkmark.png', 'list', $tmpl, array('alt' => Text::_('JYES')));
else :
	echo HTML::image('remove.png', 'list', $tmpl, array('alt' => Text::_('JNO')));
endif;
