/**
 * Admin Cron Editor
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var CronAdmin = my.Class(PluginManager, {

	options: {
		plugin: ''
	},

	constructor: function (options) {
		var plugins = [];
		CronAdmin.Super.call(this, plugins);
		this.options = $.extend(this.options, options);
		this.watchSelector();
	},

	watchSelector: function () {
		var self = this;
		$('#jform_plugin').on('change', function (e) {
			self.changePlugin(e);
		});
	},

	changePlugin: function (e) {
		var self = this;
		$.ajax({
			url: 'index.php',
			'data': {
				'option': 'com_fabrik',
				'task': 'cron.getPluginHTML',
				'format': 'raw',
				'plugin': $(e.target).val()
			}
		}).complete(function (r) {
			$('#plugin-container').html(r);
			self.updateBootStrap();
		});
	}
});