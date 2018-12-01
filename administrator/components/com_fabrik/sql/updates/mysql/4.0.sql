UPDATE `#__fabrik_connections` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
ALTER TABLE `#__fabrik_connections`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_cron` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
UPDATE `#__fabrik_cron` SET `created` = '0000-00-00 00:00:00' WHERE `created` = '';
UPDATE `#__fabrik_cron` SET `modified` = '0000-00-00 00:00:00' WHERE `modified` = '';
UPDATE `#__fabrik_cron` SET `lastrun` = '0000-00-00 00:00:00' WHERE `lastrun` = '';
ALTER TABLE `#__fabrik_cron`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `lastrun` datetime NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_elements` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
UPDATE `#__fabrik_elements` SET `created` = '0000-00-00 00:00:00' WHERE `created` = '';
UPDATE `#__fabrik_elements` SET `modified` = '0000-00-00 00:00:00' WHERE `modified` = '';
ALTER TABLE `#__fabrik_elements`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_forms` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
UPDATE `#__fabrik_forms` SET `created` = '0000-00-00 00:00:00' WHERE `created` = '';
UPDATE `#__fabrik_forms` SET `modified` = '0000-00-00 00:00:00' WHERE `modified` = '';
UPDATE `#__fabrik_forms` SET `publish_up` = '0000-00-00 00:00:00' WHERE `publish_up` = '';
UPDATE `#__fabrik_forms` SET `publish_down` = '0000-00-00 00:00:00' WHERE `publish_down` = '';
ALTER TABLE `#__fabrik_forms`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_groups` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
UPDATE `#__fabrik_groups` SET `created` = '0000-00-00 00:00:00' WHERE `created` = '';
UPDATE `#__fabrik_groups` SET `modified` = '0000-00-00 00:00:00' WHERE `modified` = '';
ALTER TABLE `#__fabrik_groups`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_lists` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
UPDATE `#__fabrik_lists` SET `created` = '0000-00-00 00:00:00' WHERE `created` = '';
UPDATE `#__fabrik_lists` SET `modified` = '0000-00-00 00:00:00' WHERE `modified` = '';
UPDATE `#__fabrik_lists` SET `publish_up` = '0000-00-00 00:00:00' WHERE `publish_up` = '';
UPDATE `#__fabrik_lists` SET `publish_down` = '0000-00-00 00:00:00' WHERE `publish_down` = '';
ALTER TABLE `#__fabrik_lists`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_packages` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
UPDATE `#__fabrik_packages` SET `created` = '0000-00-00 00:00:00' WHERE `created` = '';
UPDATE `#__fabrik_packages` SET `modified` = '0000-00-00 00:00:00' WHERE `modified` = '';
ALTER TABLE `#__fabrik_packages`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_validations` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
ALTER TABLE `#__fabrik_validations`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__fabrik_visualizations` SET `checked_out_time` = '0000-00-00 00:00:00' WHERE `checked_out_time` = '';
UPDATE `#__fabrik_visualizations` SET `created` = '0000-00-00 00:00:00' WHERE `created` = '';
UPDATE `#__fabrik_visualizations` SET `modified` = '0000-00-00 00:00:00' WHERE `modified` = '';
UPDATE `#__fabrik_visualizations` SET `publish_up` = '0000-00-00 00:00:00' WHERE `publish_up` = '';
UPDATE `#__fabrik_visualizations` SET `publish_down` = '0000-00-00 00:00:00' WHERE `publish_down` = '';
ALTER TABLE `#__fabrik_visualizations`
  MODIFY COLUMN `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `publish_up` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  MODIFY COLUMN `publish_down` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';