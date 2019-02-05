<?php
/**
 * Google Chart default filter tmpl
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.chart
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Fabrik\Helpers\Html;

if (!$this->showFilters):
	return;
endif;
?>
<form method="post" name="filter">
<?php
	foreach ($this->filters as $table => $filters) :
		if (empty($filters)) :
            continue;
		endif;
		?>
	<table class="filtertable table table-striped">
		<tbody>
	  	<?php
			$c = 0;
			foreach ($filters as $filter) :
			?>
	        <tr>
                <td><?php echo $filter->label ?></td>
                <td><?php echo $filter->element ?></td>
            </tr>
	        <?php endforeach; ?>
        </tbody>
        <thead>
            <tr>
                <th colspan="2"><?php echo Text::_($table) ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th colspan="2" style="text-align:right;">
                    <?php echo Html::icon('icon-filter'); ?>
                    <button type="submit" class="btn btn-primary">
                        <?php echo Text::_('GO') ?>
                    </button>
                </th>
            </tr>
        </tfoot>
        </table>
    <?php endforeach; ?>
</form>