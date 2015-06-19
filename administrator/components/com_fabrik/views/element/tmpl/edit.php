<?php
/**
 * Admin Element Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

use Fabrik\Helpers\HTML;
use Fabrik\Helpers\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHTML::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.framework', true);
HTML::formvalidation();
JHtml::_('behavior.keepalive');

Text::script('COM_FABRIK_SUBOPTS_VALUES_ERROR');
?>

<script type="text/javascript">

	Joomla.submitbutton = function (task) {
		if (task !== 'element.cancel' && !Fabrik.controller.canSaveForm()) {
			alert('Please wait - still loading');
			return false;
		}
		if (task == 'element.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
			window.fireEvent('form.save');
			Joomla.submitform(task, document.getElementById('adminForm'));
		} else {
			alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>
<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

	<div class="row-fluid" id="elementFormTable">

		<div class="span2">

			<ul class="nav nav-list">
				<li class="active">
					<a data-toggle="tab" href="#tab-details">
						<?php echo Text::_('COM_FABRIK_DETAILS') ?>
					</a>
				</li>
				<li>
					<a data-toggle="tab" href="#tab-publishing">
						<?php echo Text::_('COM_FABRIK_PUBLISHING') ?>
					</a>
				</li>
				<li>
					<a data-toggle="tab" href="#tab-access">
						<?php echo Text::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS') ?>
					</a>
				</li>
				<li>
					<a data-toggle="tab" href="#tab-listview">
						<?php echo Text::_('COM_FABRIK_LIST_VIEW_SETTINGS') ?>
					</a>
				</li>
				<li>
					<a data-toggle="tab" href="#tab-validations">
						<?php echo Text::_('COM_FABRIK_VALIDATIONS') ?>
					</a>
				</li>
				<li>
					<a data-toggle="tab" href="#tab-javascript">
						<?php echo Text::_('COM_FABRIK_JAVASCRIPT') ?>
					</a>
				</li>
			</ul>
		</div>

		<div class="span10 tab-content">
			<?php
			require '_details.php';
			require '_publishing.php';
			require '_access.php';
			require '_listview.php';
			require '_validations.php';
			require '_javascript.php';
			?>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="redirectto" value="" />
	<?php echo JHtml::_('form.token'); ?>
</form>