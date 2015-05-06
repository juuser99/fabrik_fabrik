<?php
/**
 * Admin List Confirm Copy Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>

<script type="text/javascript">
	Joomla.submitbutton = function (task) {
		switch (task) {
			case 'lists.doCopy':
				jQuery('input[name=view]').val('listcopy');
				Joomla.submitform(task);
				break;
			default:
				Joomla.submitform(task);
				break;
		}
	}
</script>
<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal">
	<?php
	foreach ($this->lists as $list) :
		?>
		<h2><?php echo FText::_('COM_FABRIK_LIST_COPY_RENAME_LIST') ?></h2>

		<div class="control-group">

			<label class="control-label" for="listLabel<?php echo $list->id ?>">
				<?php echo $list->list->label ?>
			</label>

			<div class="controls">
				<input type="text" name="names[<?php echo $list->id ?>][listLabel]" id="listLabel<?php echo $list->id ?>" value="<?php echo $list->list->label ?>" />
			</div>
		</div>
		<h2><?php echo FText::_('COM_FABRIK_LIST_COPY_RENAME_FORM') ?></h2>

		<div class="control-group">
			<label class="control-label" for="formLabel<?php echo $list->id ?>">
				<?php echo $list->form->label ?>
			</label>

			<div class="controls">
				<input type="text" name="names[<?php echo $list->id ?>][formLabel]" id="formLabel<?php echo $list->id ?>" value="<?php echo $list->form->label ?>" />
			</div>
		</div>
		<h2><?php echo FText::_('COM_FABRIK_LIST_COPY_RENAME_GROUPS') ?></h2>
		<?php
		foreach ($list->form->groups as $groupKey => $group) :
			?>
			<div class="control-group">
				<label class="control-label" for="group<?php echo $group->id ?>">
					<?php echo $group->name ?>
				</label>

				<div class="controls">
					<input type="text" name="names[<?php echo $list->id ?>][groupNames][<?php echo $groupKey ?>]"
						id="group<?php echo $group->id ?>" value="<?php echo $group->name ?>" />
				</div>
			</div>
		<?php
		endforeach;
	?>
	<input type="hidden" name="cid[]" value="<?php echo $list->id ?>" />
	<?php
	endforeach;
	?>
	<input type="hidden" name="view" value="" />
	<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="task" value="list.doCopy" />
	<?php echo JHtml::_('form.token'); ?>
</form>