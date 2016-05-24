<?php
/**
 * Created by PhpStorm.
 * User: rob
 * Date: 09/05/2016
 * Time: 18:56
 */

use Fabrik\Helpers\Text;

$d = $displayData;

if (array_key_exists('all', $d->filters) || $d->action != 'onchange')
{
	?>
	<ul class="nav pull-right">
		<li>
			<div <?php echo $d->action != 'onchange' ? 'class="input-append"' : ''; ?>>
				<?php if (array_key_exists('all', $d->filters)) :
					echo $d->filters['all']->element;
					if ($d->action != 'onchange') : ?>

						<input type="button" class="btn fabrik_filter_submit button" value="<?php echo Text::_('COM_FABRIK_GO'); ?>" name="filter">

						<?php
					endif;
				endif;
				?>
			</div>
		</li>
	</ul>
	<?php
}