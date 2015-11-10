/**
 * Form Submitter: delays form submission until all elements report that they
 * are ready for submission
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $H:true,unescape:true */

var FbFormSubmit = my.Class({

	/**
	 * Hash of elements js objects
	 */
	elements: {},

	running: false,

	/**
	 * Object of key = this.elements key, value = null|true|false.
	 * Null - no result from onsubmit callback (in a waiting state)
	 * False - callback has returned false - should prevent the form from submitting
	 * True - callback has returned true, once all element callbacks return true the main callback is fired
	 */
	results: {},

	/**
	 * Add form element to the broker
	 *
	 * @param   string   key     Element key
	 * @param   element  Object  Element object
	 *
	 * @return  void
	 */
	addElement: function (key, element) {
		this.elements[key] = element;
	},

	/**
	 * Is the broker already running
	 *
	 * @return  bool
	 */
	enabled: function () {
		return this.running;
	},

	/**
	 * Called from form.js.
	 *
	 * @param   function  cb  Callback - fired once all elements have completed
	 *                        their own onsubmit callbacks and return true
	 *
	 * @return  void
	 */
	submit: function (cb) {
		this.running = true;
		jQuery.each(this.elements, function (key, element) {
			this.results[key] = null;
			element.onsubmit(function (res) {
				this.results[key] = res;
			}.bind(this));
		}.bind(this));
		this.checker = setInterval(function () {
			this.check.call(this, [cb]);
		}, 500);
	},

	/**
	 * Periodical checker on the element callback state (stored in this.results)
	 *
	 * @param   function  cb  Main submit() callback
	 *
	 * @return  void
	 */
	check: function (cb) {
		var values = Object.values(this.results);
		var allPassed = values.every(function (res) {
			return res === true;
		});
		if (allPassed) {
			clearInterval(this.checker);
			this.running = false;
			cb();
		}
		if (values.contains(false)) {
			this.running = false;
			clearInterval(this.checker);
		}

	}
});