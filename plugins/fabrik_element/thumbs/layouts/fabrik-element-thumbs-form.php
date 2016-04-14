<?php

defined('JPATH_BASE') or die;

$d = $displayData;
?>
<div class="btn-group">';
	<button <?php echo $d->commentdata; ?> data-fabrik-thumb-formid="<?php echo $d->formId; ?>"
		data-fabrik-thumb="up" class="btn btn-small thumb-up<?php echo $d->upActiveClass; ?>">
		<span class="icon-thumbs-up"></span>
		<span class="thumb-count"><?php echo $d->countUp; ?>
		</span>
	</button>
	<?php
	if ($d->showDown) :
		?>
		<button <?php echo $d->commentdata; ?> data-fabrik-thumb-formid="<?php echo $d->formId; ?>"
			data-fabrik-thumb="down" class="btn btn-small thumb-down<?php echo $d->downActiveClass; ?>">
			<span class="icon-thumbs-down"></span>
			<span class="thumb-count"><?php echo $d->countDown; ?></span>
		</button>
		<?php
	endif;
	?>

</div>

<input type="hidden" name="<?php echo $d->name; ?>"
	id="<?php echo $d->id; ?>" value="<?php echo $d->countDiff; ?>"
	class="<?php echo $d->id; ?>" />
