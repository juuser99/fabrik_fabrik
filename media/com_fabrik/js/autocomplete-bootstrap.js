/**
 * Bootstrap Auto-Complete
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, Encoder:true */

var FbAutocomplete = my.Class({

	options: {
		menuclass: 'auto-complete-container dropdown',
		classes: {
			'ul': 'dropdown-menu',
			'li': 'result'
		},
		url: 'index.php',
		max: 10,
		onSelection: Class.empty,
		autoLoadSingleResult: true,
		minTriggerChars: 1,
		storeMatchedResultsOnly: false // Only store a value if selected from picklist
	},

	initialize: function (element, options) {
		var self = this;
		window.addEvent('domready', function () {
			self.matchedResult = false;
			self.setOptions(options);
			element = element.replace('-auto-complete', '');
			self.options.labelelement = $('#' + element + '-auto-complete').length === 0 ? $(element + '-auto-complete') : $('#' + element + '-auto-complete');
			this.cache = {};
			this.selected = -1;
			this.mouseinsde = false;
			$(document).on('keydown', function (e) {
				self.doWatchKeys(e);
			});
			this.element = $('#' + element).length === 0 ? $(element) : $('#' + element);
			this.buildMenu();
			if (!this.getInputElement()) {
				fconsole('autocomplete didn\'t find input element');
				return;
			}
			this.getInputElement().prop('autocomplete', 'off');
			this.getInputElement().on('keyup', function (e) {
				self.search(e);
			});

			this.getInputElement().on('blur', function (e) {
				if (self.options.storeMatchedResultsOnly) {
					if (!self.matchedResult) {
						if (typeof(self.data) === 'undefined' || !(self.data.length === 1 && self.options.autoLoadSingleResult)) {
							self.element.value = '';
						}
					}
				}
			});
		});
	},

	search: function (e) {
		var self = this;
		if (!this.isMinTriggerlength()) {
			return;
		}
		if (e.key === 'tab' || e.key === 'enter') {
			e.stopPropagation();
			this.closeMenu();
			if (this.ajax) {
				this.ajax.cancel();
			}
			this.element.trigger('change', new jQuery.Event('change'), 500);
			return;
		}
		this.matchedResult = false;
		var v = this.getInputElement().val();
		if (v === '') {
			this.element.value = '';
		}
		if (v !== this.searchText && v !== '') {
			if (this.options.storeMatchedResultsOnly === false) {
				this.element.value = v;
			}
			this.positionMenu();
			if (this.cache[v]) {
				if (this.populateMenu(this.cache[v])) {
					this.openMenu();
				}
			} else {
				if (this.ajax) {
					this.closeMenu();
					this.ajax.cancel();
				}
				this.ajax = $.ajax({
					url: this.options.url,
					data: {
						value: v
					}
				}).fail(function (jqxhr, textStatus, error) {
					Fabrik.loader.stop(self.getInputElement());
					self.ajax = null;
					fconsole('Fabrik autocomplete: Ajax failure: Code ' + textStatus + ': ' + error);
					var elModel = Fabrik.getBlock(self.options.formRef).formElements.get(this.element.prop('id'));
					elModel.setErrorMessage(Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR'), 'fabrikError', true);
				}).always(function () {
					Fabrik.loader.start(self.getInputElement());
				}).done(function (e) {
					Fabrik.loader.stop(self.getInputElement());
					self.ajax = null;
					if (typeOf(e) === 'null') {
						fconsole('Fabrik autocomplete: Ajax response empty');
						var elModel = Fabrik.getBlock(this.options.formRef).formElements.get(this.element.prop('id'));
						elModel.setErrorMessage(Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR'), 'fabrikError', true);
						return;
					}
					this.completeAjax(e, v);
				});
			}
		}
		this.searchText = v;
	},

	completeAjax: function (r, v) {
		r = JSON.decode(r);
		this.cache[v] = r;
		if (this.populateMenu(r)) {
			this.openMenu();
		}
	},

	buildMenu: function ()
	{
		var self = this;
		this.menu = $('<ul />').addClass('dropdown-menu').attr({'role': 'menu'}).css({'z-index': 1056});
		this.menu.inject(document.body);
		this.menu.addEvent('mouseenter', function () {
			self.mouseinsde = true;
		});
		this.menu.on('mouseleave', function () {
			self.mouseinsde = false;
		});
		this.menu.on('click', 'a', function (e, target) {
			self.makeSelection(e, target);
		});
	},

	getInputElement: function () {
		return this.options.labelelement ? this.options.labelelement : this.element;
	},

	positionMenu: function () {
		var coords = this.getInputElement().getCoordinates();
		// var pos = this.getInputElement().getPosition();
		this.menu.setStyles({ 'left': coords.left, 'top': (coords.top + coords.height) - 1, 'width': coords.width});
	},

	populateMenu: function (data) {
		// $$$ hugh - added decoding of things like &amp; in the text strings
		var li, a, form, elModel, blurEvent, pair;
		data.map(function (item, index) {
			item.text = Encoder.htmlDecode(item.text);
			return item;
		});
		this.data = data;
		var max = this.getListMax(),
			ul = this.menu;
		ul.empty();
		if (data.length === 1 && this.options.autoLoadSingleResult) {
			this.element.value = data[0].value;
			this.getInputElement().value = data[0].text;
			// $$$ Paul - The selection event is for text being selected in an input field not for a link being selected
			this.closeMenu();
			this.trigger('selection', [this, this.element.value]);
			// $$$ hugh - need to fire change event, in case it's something like a join element
			// with a CDD that watches it.
			form = Fabrik.getBlock(this.options.formRef);
			if (form !== false) {
				elModel = form.formElements.get(this.element.id);
				blurEvent = elModel.getBlurEvent();
				this.element.trigger(blurEvent, jQuery.Event(blurEvent), 700);
			}

			// $$$ hugh - fire a Fabrik event, just for good luck.  :)
			Fabrik.trigger('fabrik.autocomplete.selected', [this, this.element.value]);
			return false;
		}
		if (data.length === 0) {
			li = $('<li />').adopt($('<div />').addClass(' alert alert-info')
				.adopt($('<i>').text(Joomla.JText._('COM_FABRIK_NO_RECORDS'))));
			li.inject(ul);
		}
		for (var i = 0; i < max; i ++) {
			pair = data[i];
			a = $('<a>').attr({'href': '#', tabindex: '-1'})
				.data('value', pair.value).text(pair.text);
			li = $('<li>').adopt(a);
			li.inject(ul);
		}
		if (data.length > this.options.max) {
			$('<li>').text('....').inject(ul);
		}
		return true;
	},

	/**
	 * Make selection
	 * @param {object} e  Event
	 * @param {jQuery} li Li node
	 */
	makeSelection: function (e, li) {
		e.preventDefault();
		// $$$ tom - make sure an item was selected before operating on it.
		if (li.length !== 0) {
			this.getInputElement().value = li.text();
			this.element.value = li.data('value');
			this.closeMenu();
			this.trigger('selection', [this, this.element.value]);
			// $$$ hugh - need to fire change event, in case it's something like a join element
			// with a CDD that watches it.
			this.element.trigger('change', jQuery.Event('change'), 700);
			this.element.trigger('blur', jQuery.Event('blur'), 700);
			// $$$ hugh - fire a Fabrik event, just for good luck.  :)
			Fabrik.trigger('fabrik.autocomplete.selected', [this, this.element.value]);
		} else {
			/**
			 * $$$ Paul - The Fabrik event below makes NO sense.
			 * This is a code error condition not an event because typeOf(li) should never be null
			 **/
			//  $$$ tom - fire a notselected event to let developer take appropriate actions.
			Fabrik.trigger('fabrik.autocomplete.notselected', [this, this.element.value]);
		}
	},

	closeMenu: function () {
		if (this.shown) {
			this.shown = false;
			this.menu.hide();
			this.selected = -1;
			document.removeEvent('click', function (e) {
				this.doTestMenuClose(e);
			}.bind(this));
		}
	},

	openMenu: function () {
		var self = this;
		if (!this.shown) {
			if (this.isMinTriggerlength()) {
				this.menu.show();
				this.shown = true;
				$(document).on('click', function (e) {
					self.doTestMenuClose(e);
				});
				this.selected = 0;
				this.highlight();
			}
		}
	},

	isMinTriggerlength: function () {
		var v = this.getInputElement().val();
		return v.length >= this.options.minTriggerChars;
	},

	doTestMenuClose: function () {
		if (!this.mouseinsde) {
			this.closeMenu();
		}
	},

	getListMax: function () {
		if (typeOf(this.data) === 'null') {
			return 0;
		}
		return this.data.length > this.options.max ? this.options.max : this.data.length;
	},

	/**
	 * Deal with the open / close of the menu based on the key down event
	 * @param {object} e Key event
	 */
	doWatchKeys: function (e) {
		if (document.activeElement !== this.getInputElement()) {
			return;
		}

		if (!this.shown) {
			// Stop enter from submitting when in in-line edit form.
			if (parseInt(e.code, 10) === 13) {
				e.stopPropagation();
			}
			if (parseInt(e.code, 10) === 40) {
				this.openMenu();
			}
		} else {
			if (!this.isMinTriggerlength()) {
				e.stopPropagation();
				this.closeMenu();
			}
			else {
				this.handleWatchKey(e);
				e.stopPropagation();
			}
		}
	},

	/**
	 * Handle the key down event
	 * @param {object} e Key event
	 */
	handleWatchKey: function (e) {
		var max = this.getListMax(), selected, selectEvnt;
		if (e.key === 'enter' || e.key === 'tab') {
			$(window).trigger('blur');
		}
		switch (e.code) {
			case 40://down
				if (!this.shown) {
					this.openMenu();
				}
				if (this.selected + 1 <= max) {
					this.selected ++;
				}
				this.highlight();

				break;
			case 38: //up
				if (this.selected - 1 >= -1) {
					this.selected --;
					this.highlight();
				}
				e.stopPropagation();
				break;
			case 13://enter
			case 9://tab
				e.stopPropagation();
				selected = this.getSelected();
				if (selected) {
					selectEvnt = jQuery.Event('click');
					this.makeSelection(selectEvnt, selected);
					this.closeMenu();
				}
				break;
			case 27://escape
				e.stopPropagation();
				this.closeMenu();
				break;
		}
	},

	/**
	 * Get the selected <a> tag
	 *
	 * @return  DOM Node <a>
	 */
	getSelected: function () {
		var all = this.menu.find('li'),
		lis = all.filter(function (li, i) {
			return i === this.selected;
		}.bind(this));

		if (typeOf(lis[0]) === 'element') {
			return lis[0].find('a');
		} else if (all.length > 0) {
			// Can occur if autocomplete generated but not clicked on / keyed into.
			return all[0].find('a');
		}

		return false;
	},

	highlight: function () {
		this.matchedResult = true,
		self = this;
		this.menu.find('li').each(function (i) {
			if (i === self.selected) {
				$(this).addClass('selected').addClass('active');
			} else {
				$(this).removeClass('selected').removeClass('active');
			}
		});
	}

});

var FabCddAutocomplete = my.Class(FbAutocomplete, {

	search: function (e) {
		var key,
			self = this,
			observer,
			v = this.getInputElement().val();
		if (v === '') {
			this.element.value = '';
		}
		if (v !== this.searchText && v !== '') {
			observer = $('#' + this.options.observerid);
			if (observer.length !== 0) {
				if (this.options.formRef) {
					observer = Fabrik.getBlock(this.options.formRef).formElements[this.options.observerid];
				}
				key = observer.val() + '.' + v;
			} else {

				FabCddAutocomplete.Super.prototype.search.call(this, e);
				return;
			}
			this.positionMenu();
			if (this.cache[key]) {
				if (this.populateMenu(this.cache[key])) {
					this.openMenu();
				}
			} else {
				if (this.ajax) {
					this.closeMenu();
					this.ajax.cancel();
				}
				this.ajax = $.ajax({
					url : this.options.url,
					data: {
						value: v,
						fabrik_cascade_ajax_update: 1,
						v: observer.val()
					}
				}).always(function () {
					Fabrik.loader.start(self.getInputElement());
				}).success(function (e) {
					Fabrik.loader.stop(self.getInputElement());
					self.ajax = null;
					self.completeAjax(e);
				}).fail(function (jqXHR, textStatus) {
					Fabrik.loader.stop(self.getInputElement());
					self.ajax = null;
					fconsole('Fabrik autocomplete: Ajax failure: Code ' + jqXHR.status + ': ' + textStatus);
					var elModel = Fabrik.getBlock(self.options.formRef).formElements.get(this.element.id);
					elModel.setErrorMessage(Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR'), 'fabrikError', true);
				}).done(function () {
					Fabrik.loader.stop(self.getInputElement());
				});
			}
		}
		this.searchText = v;
	}
});