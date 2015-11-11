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
		this.options = $.append(this.options, options);
		this.watchSelector();
	},

	watchSelector: function () {
		var self = this;
		$('#jform_plugin').on('change', function (e) {
			self.changePlugin(e);
		});
	},

	changePlugin: function (e) {
		var myAjax = new Request.HTML({
			url: 'index.php',
			'data': {
				'option': 'com_fabrik',
				'task': 'cron.getPluginHTML',
				'format': 'raw',
				'plugin': e.target.get('value')
			},
			'update': $('#plugin-container'),
			'onComplete': function () {
				this.updateBootStrap();
			}.bind(this)

		}).send();
	}
});