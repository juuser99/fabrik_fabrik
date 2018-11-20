<?php
/**
 * Admin Home Bootstrap Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::stylesheet('media/com_fabrik/css/admin.css');
ToolbarHelper::title(Text::_('COM_FABRIK_WELCOME'), 'fabrik.png');
?>

<div class="row">
	<div id="j-sidebar-container" class="col-2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="col-10"">
        <div class="row">
            <div class="col-6">
                <div style="float:left;width:250px;">
                    <a href="http://fabrikar.com/" target="_blank">
                        <?php echo HTMLHelper::image('media/com_fabrik/images/logo.png', 'Fabrik'); ?>
                    </a>
                </div>
                <div style="margin-left:200px;">
                    <h1><?php echo Text::_('COM_FABRIK_HOME_SUBSCRIBE_TITLE')?></h1>
                    <div style="margin-left:50px;"><?php echo Text::_('COM_FABRIK_HOME_SUBSCRIBE_FEATURES')?></div>
                    <a href="http://fabrikar.com/" target="_blank"><?php echo HTMLHelper::image('media/com_fabrik/images/visit-fabrikar.png', 'Fabrik'); ?></a><br />
                </div>
            </div>

            <div class="col-6">
                <ul class="nav nav-tabs">
                    <li class="nav-item active">
                        <a class="nav-link" data-toggle="tab" href="#home-about">
                            <?php echo Text::_('COM_FABRIK_HOME_ABOUT'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#home-news">
                            <?php echo Text::_('COM_FABRIK_HOME_NEWS'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#home-stats">
                            <?php echo Text::_('COM_FABRIK_HOME_STATS')?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#home-links">
                            <?php echo Text::_('COM_FABRIK_HOME_USEFUL_LINKS')?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#home-tools">
                            <?php echo Text::_('COM_FABRIK_HOME_TOOLS')?>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="home-about">
                        <?php echo Text::_('COM_FABRIK_HOME_ABOUT_TEXT'); ?>
                    </div>

                    <div class="tab-pane" id="home-news">
                        <?php echo $this->feed;?>
                    </div>

                    <div class="tab-pane" id="home-stats">
                        <table class='adminlist'>
                        <thead>
                            <tr>
                                <th style="width:20%"><?php echo Text::_('COM_FABRIK_HOME_DATE')?></th>
                                <th><?php echo Text::_('COM_FABRIK_HOME_ACTION')?></th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach ($this->logs as $log) :?>
                                <tr>
                                    <td>
                                    <?php echo $log->timedate_created;?>
                                    </td>
                                    <td>
                                    <span class="editlinktip hasTip" title="<?php echo $log->message_type . "::" . $log->message; ?>">
                                        <?php echo $log->message_type;?>
                                    </span>
                                    </td>
                                </tr>
                                <?php
                                endforeach;?>
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane" id="home-links">
                        <ul class="adminlist">
                            <li><a href="http://fabrikar.com/"><?php echo Text::_('COM_FABRIK_HOME_FABRIK_WEB_SITE')?></a></li>
                            <li><a href="http://fabrikar.com/forums"><?php echo Text::_('COM_FABRIK_HOME_FORUM')?></a>
                            <li><a href="http://fabrikar.com/forums/index.php?wiki/index/"><?php echo Text::_('COM_FABRIK_HOME_DOCUMENTATION_WIKI')?></a></li>
                        </ul>
                    </div>

                    <div class="tab-pane" id="home-tools">
                        <ul class="adminlist">
                            <li><a href="index.php?option=com_fabrik&task=home.installSampleData">
                            <?php echo Text::_('COM_FABRIK_HOME_INSTALL_SAMPLE_DATA')?></a>
                            </li>
                            <li>
                                <a onclick="return confirm('<?php echo Text::_('COM_FABRIK_HOME_CONFIRM_WIPE', true);?>')" href="index.php?option=com_fabrik&task=home.reset">
                                    <?php echo Text::_('COM_FABRIK_HOME_RESET_FABRIK') ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>