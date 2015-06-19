<?php
defined('JPATH_BASE') or die;
use Fabrik\Helpers\HTML;
$d    = $displayData;

echo implode("\n", HTML::grid($d->values, $d->labels, $d->default, $d->name,
	'checkbox', 1, array('input' => array('fabrik_filter'))));
