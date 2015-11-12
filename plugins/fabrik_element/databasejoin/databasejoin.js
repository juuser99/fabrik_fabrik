/**
 * Database Join Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbDatabasejoin = my.Class(FbElement, {

	options: {
		'id': 0,
		'formid': 0,
		'key': '',
		'label': '',
		'windowwidth': 360,
		'displayType': 'dropdown',
		'popupform': 0,
		'listid': 0,
		'listRef': '',
		'joinId': 0,
		'isJoin': false,
		'canRepeat': false,
		'fullName': '',
		'show_please_select': false,
		'allowadd': false,
		'autoCompleteOpts': null
	},

	constructor: function (element, options) {
		this.activePopUp = false;
		this.activeSelect = false;
		this.plugin = 'databasejoin';
		FbDatabasejoin.Super.call(this, element, options);
		this.init();
	},

	watchAdd: function () {
		var self = this, c, b;
		if (c = this.getContainer()) {
			b = c.find('.toggle-addoption');

			// If duplicated remove old events

			b.removeEvent('click', function (e) {
				self.start(e);
			});

			b.on('click', function (e) {
				self.start(e);
			});
		}
	},

	/**
	 * Add option via a popup form. Opens a window with the related form
	 * inside
	 * @param {Event} e
	 * @param {bool} force
	 */
	start: function (e, force) {
		if (!this.options.editable) {
			return;
		}

		var visible, destroy,
			c = this.getContainer();

		force = force ? true : false;

		// First time loading - auto close the hidden loaded popup.
		var onContentLoaded = function () {
			this.close();
		};
		visible = false;
		if (e) {
			// Loading from click
			e.stopPropagation();
			onContentLoaded = function (win) {
				win.fitToContent();
			};

			// @FIXME - if set to true, then click addrow, click select rows, click add row => can't submit the form
			// if set to false then there's an issue with loading data in repeat groups: see window.close()
			//destroy = true;
			visible = true;
			this.activePopUp = true;
		}

		destroy = true;

		if (force === false && (this.options.popupform === 0 || this.options.allowadd === false)) {
			return;
		}

		if (typeOf(this.element) === 'null' || typeOf(c) === 'null') {
			return;
		}
		var a = c.find('.toggle-addoption'),
			url = typeOf(a) === 'null' ? e.target.get('href') : a.get('href');


		var id = this.element.id + '-popupwin';
		this.windowopts = {
			'id': id,
			'title': Joomla.JText._('PLG_ELEMENT_DBJOIN_ADD'),
			'contentType': 'xhr',
			'loadMethod': 'xhr',
			'contentURL': url,
			'height': 320,
			'minimizable': false,
			'collapsible': true,
			'visible': visible,
			'onContentLoaded': onContentLoaded,
			destroy: destroy
		};
		var winWidth = this.options.windowwidth;
		if (winWidth !== '') {
			this.windowopts.width = winWidth;
			this.windowopts.onContentLoaded = onContentLoaded;
		}

		this.win = Fabrik.getWindow(this.windowopts);
	},

	getBlurEvent: function () {
		if (this.options.displayType === 'auto-complete') {
			return 'change';
		}
		return FbDatabasejoin.Super.prototype.getBlurEvent(this);
	},

	/**
	 * Adds an option to the db join element, for drop-downs and radio buttons
	 * (where only one selection is possible from a visible list of options)
	 * the new option is only selected if its value = this.options.value
	 *
	 * @param {string} v Option value
	 * @param {string} l Option label
	 * @param {bool} autoCompleteUpdate  Should the auto-complete element set its current label/value to the option
	 * being added - set to false in updateFromServer if not the active element.
	 *
	 * @return  void
	 */
	addOption: function (v, l, autoCompleteUpdate)
	{
		l = Encoder.htmlDecode(l);
		autoCompleteUpdate = typeof(autoCompleteUpdate) !== 'undefined' ? autoCompleteUpdate : true;
		var opt, selected, labelField;

		switch (this.options.displayType) {
		case 'dropdown':
		/* falls through */
		case 'multilist':
			var sel = $.isArray(this.options.value) ? this.options.value : [this.options.value];
			selected = sel.contains(v) ? 'selected' : '';
			opt = $(document.createElement('option')).attr({'value': v, 'selected': selected}).text(l);
			$('#' + this.element.id).adopt(opt);
			if (this.options.advanced)
			{
				$('#' + this.element.id).trigger('liszt:updated');
			}
			break;
		case 'auto-complete':
			if (autoCompleteUpdate) {
				labelField = this.element.closest('.fabrikElement').find('input[name*=-auto-complete]');
				this.element.value = v;
				labelField.value = l;
			}
			break;
		case 'checkbox':
			opt = this.getCheckboxTmplNode().clone();
			this._addOption(opt, l, v);
			break;
		case 'radio':
		/* falls through */
		default:
			var opt = jQuery(Fabrik.jLayouts['fabrik-element-' + this.plugin + '-form-radio'])[0];
			this._addOption(opt, l, v);
			break;
		}
	},

	_addOption: function (opt, l, v) {
		var sel = typeOf(this.options.value) === 'array' ? this.options.value : Array.from(this.options.value),
			i = opt.find('input'),
			last, injectWhere,
			subOpts = this.getSubOptions(),
			checked = sel.contains(v) ? true : false,
			nameInterator = this.options.displayType === 'radio' ? '' : subOpts.length;


		if (this.options.canRepeat) {
			i.name = this.options.fullName + '[' + this.options.repeatCounter + '][' + nameInterator + ']';
		} else {
			i.name = this.options.fullName + '[' + nameInterator + ']';
		}

		opt.find('span').html(l);
		opt.find('input').set('value', v);
		last = subOpts.length === 0 ? this.element : subOpts.getLast();
		injectWhere = subOpts.length === 0 ? 'bottom' : 'after';
		var subOption = subOpts.length === 0 ? last : jQuery(last).closest('div[data-role=suboption]')[0];

		opt.inject(subOption, injectWhere);
		opt.find('input').checked = checked;
	},

	hasSubElements: function () {
		var d = this.options.displayType;
		if (d === 'checkbox' || d === 'radio') {
			return true;
		}
		return FbDatabasejoin.Super.prototype.hasSubElements(this);
	},

	/**
	 * As cdd elements clear out the sub options before repopulating we need
	 * to grab a copy of one of the checkboxes to use as a template node when recreating
	 * the list
	 *
	 * @return  dom node(visible checkbox)
	 */
	getCheckboxTmplNode: function () {
		this.chxTmplNode = jQuery(Fabrik.jLayouts['fabrik-element-' + this.plugin + '-form-checkbox'])[0];

		return this.chxTmplNode;
	},

	/**
	 * Send an ajax request to re-query the element options and update the element if new options found
	 *
	 * @param {string} v (optional) additional value to get the updated value for (used in select)
	 */
	updateFromServer: function (v)
	{
		var data = {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'plugin': 'databasejoin',
				'method': 'ajax_getOptions',
				'element_id': this.options.id,
				'formid': this.options.formid
			};
		// $$$ hugh - don't think we need to fetch values if auto-complete
		// and v is empty, otherwise we'll just fetch every row in the target table,
		// and do thing with it in onComplete?
		if (this.options.displayType === 'auto-complete' && v === '') {
			return;
		}
		if (v) {
			data[this.strElement + '_raw'] = v;

			// Joined elements strElement isnt right so use fullName as well
			data[this.options.fullName + '_raw'] = v;
		}
		new Request.JSON({url: '',
			method: 'post',
			'data': data,
			onSuccess: function (json) {
				var sel, existingValues = this.getOptionValues();

				// If duplicating an element in a repeat group when its auto-complete we dont want to update its value
				if (this.options.displayType === 'auto-complete' && v === '' && existingValues.length === 0) {
					return;
				}
				json.each(function (o) {
					if (!existingValues.contains(o.value) && typeOf(o.value) !== 'null') {
						sel = this.options.value === o.value;
						this.addOption(o.value, o.text, sel);
						this.element.trigger('change');
						this.element.trigger('blur');
					}
				}.bind(this));
				this.activePopUp = false;
			}.bind(this)
		}).post();
	},

	getSubOptions: function () {
		var o;
		switch (this.options.displayType) {
			case 'dropdown':
			/* falls through */
			case 'multilist':
				o = this.element.find('option');
				break;
			case 'checkbox':
				o = this.element.find('[data-role=suboption] input[type=checkbox]');
				break;
			case 'radio':
			/* falls through */
			default:
				o = this.element.find('[data-role=suboption] input[type=radio]');
				break;
		}
		return o;
	},

	getOptionValues: function () {
		var o = this.getSubOptions(),
		values = [];
		o.each(function (o) {
			values.push(o.get('value'));
		});
		return values.unique();
	},

	appendInfo: function (data) {
		var rowId = data.rowid,
			url = 'index.php?option=com_fabrik&view=form&format=raw',
			post = {
			'formid': this.options.popupform,
			'rowid': rowId
		};
		new Request.JSON({url: url,
			'data': post,
			onSuccess: function (r) {
				var v = r.data[this.options.key];
				var l = r.data[this.options.label];

				switch (this.options.displayType) {
				case 'dropdown':
				/* falls through */
				case 'multilist':
					var o = this.element.find('option').filter(function (o, x) {
						if (o.get('value') === v) {
							this.options.displayType === 'dropdown' ? this.element.selectedIndex = x : o.selected = true;
							return true;
						}
					}.bind(this));
					if (o.length === 0) {
						this.addOption(v, l);
					}
					break;
				case 'auto-complete':
					this.addOption(v, l);
					break;
				case 'checkbox':
					this.addOption(v, l);
					break;
				case 'radio':
				/* falls through */
				default:
					o = this.element.find('.fabrik_subelement').filter(function (o, x) {
						if (o.get('value') === v) {
							o.checked = true;
							return true;
						}
					}.bind(this));
					if (o.length === 0) {
						this.addOption(v, l);
					}
					break;
				}

				if (typeOf(this.element) === 'null') {
					return;
				}
				// $$$ hugh - fire change blur event, so things like auto-fill will pick up change
				this.element.trigger('change');
				this.element.trigger('blur');
			}.bind(this)
		}).send();
	},

	watchSelect: function () {
		var c, winId,
			self = this;
		if (c = this.getContainer()) {
			var sel = c.find('.toggle-selectoption');
			if (sel.length > 0) {
				sel.on('click', function (e) {
					self.selectRecord(e);
				});
				Fabrik.addEvent('fabrik.list.row.selected', function (json) {
					if (self.options.listid.toInt() === json.listid.toInt() && self.activeSelect) {
						self.update(json.rowid);
						winId = self.element.id + '-popupwin-select';
						if (Fabrik.Windows[winId]) {
							Fabrik.Windows[winId].close();
						}
					}
				});

				// Used for auto-completes in repeating groups to stop all fields updating when a record
				// is selected
				this.unactiveFn = function () {
					self.activeSelect = false;
				};
				$(window).on('fabrik.dbjoin.unactivate', this.unactiveFn);
				this.selectThenAdd();
			}
			this.selectThenAdd();
		}
	},

	/**
	 * Watch the list load so that its add button will close the window and open the db join add window
	 *
	 * @return void
	 */
	selectThenAdd: function () {
		var self = this;
		Fabrik.addEvent('fabrik.block.added', function (block, blockid) {
			if (blockid === 'list_' + self.options.listid + self.options.listRef) {
				block.form.on('click', '.addbutton', function (event) {
					event.preventDefault();
					var id = self.selectRecordWindowId();
					Fabrik.Windows[id].close();
					self.start(event, true);
				});
			}
		});
	},

	/**
	 * Called when form closed in ajax window
	 * Should remove any events added to Window or Fabrik
	 */
	destroy: function () {
		window.removeEvent('fabrik.dbjoin.unactivate', this.unactiveFn);
	},

	selectRecord: function (e) {
		$(window).trigger('fabrik.dbjoin.unactivate');
		this.activeSelect = true;
		e.stopPropagation();
		var id = this.selectRecordWindowId();
		var url = this.getContainer().find('a.toggle-selectoption').href;
		url += '&triggerElement=' + this.element.id;
		url += '&resetfilters=1';
		url += '&c=' + this.options.listRef;

		this.windowopts = {
			'id': id,
			'title': Joomla.JText._('PLG_ELEMENT_DBJOIN_SELECT'),
			'contentType': 'xhr',
			'loadMethod': 'xhr',
			'evalScripts': true,
			'contentURL': url,
			'width': this.options.windowwidth,
			'height': 320,
			'minimizable': false,
			'collapsible': true,
			'onContentLoaded': function (win) {
				win.fitToContent();
			}
		};
		Fabrik.getWindow(this.windowopts);
	},

	/**
	 * Get the window id for the 'select record' window
	 *
	 * @return  string
	 */
	selectRecordWindowId: function () {
		return this.element.id + '-popupwin-select';
	},

	numChecked: function () {
		if (this.options.displayType !== 'checkbox') {
			return null;
		}
		return this._getSubElements().filter(function (c) {
			return c.value !== "0" ? c.checked : false;
		}).length;
	},

	update: function (val) {
		this.find();
		if (typeOf(this.element) === 'null') {
			return;
		}
		if (!this.options.editable) {
			this.element.html('');
			if (val === '') {
				return;
			}
			if (typeOf(val) === 'string') {
				val = JSON.decode(val);
			}
			var h = this.form.getFormData();

			val.each(function (v) {
				if (h[v] !== null) {
					this.element.html(this.element.html() + h[v] + '<br />');
				} else {
					//for detailed view prev/next pagination v is set via elements
					//getROValue() method and is thus in the correct format - not sure that
					// h.get(v) is right at all but leaving in incase i've missed another scenario
					this.element.html(this.element.html() + v + '<br />');
				}
			}.bind(this));
			return;
		}
		this.setValue(val);
	},

	setValue: function (val) {
		var found = false;
		if (this.element.options !== null) { //needed with repeat group code
			for (var i = 0; i < this.element.options.length; i++) {
				if (this.element.options[i].value === val) {
					this.element.options[i].selected = true;
					found = true;
					break;
				}
			}
		}
		if (!found) {
			if (this.options.displayType === 'auto-complete') {
				this.element.value = val;
				this.updateFromServer(val);
			} else {
				if (this.options.displayType === 'dropdown') {
					if (this.options.show_please_select) {
						this.element.options[0].selected = true;
					}
				} else {
					if (typeOf(val) === 'string') {
						val = val === '' ? [] : JSON.decode(val);
					}
					if (typeOf(val) !== 'array') {
						val = [val];
					}
					this._getSubElements();
					this.subElements.each(function (el) {
						var chx = false;
						val.each(function (v) {
							if (v.toString() === el.value) {
								chx = true;
							}
						}.bind(this));
						el.checked = chx;
					}.bind(this));
				}
			}
		}
		this.options.value = val;
	},

	/**
	 * $$$ hugh - testing being able to set a dropdown join by label rather than value,
	 * needed in corner cases like reverse geocoding in the map element, where (say) the
	 * 'country' element might be a join / CDD, but obviously we only get a label ("Austria")
	 * back from Google.  For now, VERY limited support, only for simple dropdown type.
	 */
	updateByLabel: function (label) {
		this.find();
		if (typeOf(this.element) === 'null') {
			return;
		}
		// If it's not editable or not a drop-down, just punt to a normal update()
		if (!this.options.editable || this.options.displayType !== 'dropdown') {
			this.update(label);
		}
		// OK, it's an editable dropdown, so let's see if we can find a matching option text
		var options = this.element.find('option');
		options.some(function (option) {
			if (option.text === label) {
				this.update(option.value);
				return true;
			}
			else {
				return false;
			}
		}.bind(this));
	},

	/**
	 * Optionally show a description which is another field from the joined table.
	 */

	showDesc: function (e) {
		var v = e.target.selectedIndex;
		var c = this.getContainer().find('.dbjoin-description');
		var show = c.find('.description-' + v);
		c.find('.notice').each(function (d) {
			if (d === show) {
				var myfx = new Fx.Tween(show, {'property': 'opacity',
					'duration': 400,
					'transition': Fx.Transitions.linear
				});
				myfx.set(0);
				d.css('display', '');
				myfx.start(0, 1);
			} else {
				d.css('display', 'none');
			}
		});
	},

	getValue: function () {
		var v = null;
		this.find();
		if (!this.options.editable) {
			return this.options.value;
		}
		if (typeOf(this.element) === 'null') {
			return '';
		}
		switch (this.options.displayType) {
		case 'dropdown':
		/* falls through */
		default:
			if (typeOf(this.element.get('value')) === 'null') {
				return '';
			}
			return this.element.get('value');
		case 'multilist':
			var r = [];
			this.element.find('option').each(function (opt) {
				if (opt.selected) {
					r.push(opt.value);
				}
			});
			return r;
		case 'auto-complete':
			return this.element.value;
		case 'radio':
			v = '';
			this._getSubElements().each(function (sub) {
				if (sub.checked) {
					v = sub.get('value');
					return v;
				}
				return null;
			});
			return v;
		case 'checkbox':
			v = [];
			this.getChxLabelSubElements().each(function (sub) {
				if (sub.checked) {
					v.push(sub.get('value'));
				}
			});
			return v;
		}
	},

	/**
	 * When rendered as a checkbox - the joined to tables values are stored in the visible checkboxes,
	 * for getValue() to get the actual values we only want to select these subElements and not the hidden
	 * ones which if we did would add the lookup lists's ids into the values array.
	 *
	 * @return  array
	 */
	getChxLabelSubElements: function () {
		var subs = this._getSubElements();
		return subs.filter(function (sub) {
			if (!sub.name.contains('___id')) {
				return true;
			}
		});
	},

	/**
	 * Used to find element when form clones a group
	 * WYSIWYG text editor needs to return something specific as options.element has to use name
	 * and not id.
	 */
	getCloneName: function () {
		// Testing for issues with cdd rendered as chx in repeat group when observing autocomplete db join element in main group
		/*if (this.options.isGroupJoin && this.options.isJoin) {
			return this.options.elementName;
		}*/
		return this.options.element;
	},

	getValues: function () {
		var v = [];
		var search = (this.options.displayType !== 'dropdown') ? 'input' : 'option';
		$('#' + this.element.id).find(search).each(function (f) {
			v.push(f.value);
		});
		return v;
	},

	cloned: function (c) {
		//c is the repeat group count
		this.activePopUp = false;
		FbDatabasejoin.Super.prototype.cloned(this, c);
		this.init();
		this.watchSelect();
		if (this.options.displayType === 'auto-complete') {
			this.cloneAutoComplete();
		}
	},

	/**
	 * Update auto-complete fields id and create new autocompleter object for duplicated element
	 */
	cloneAutoComplete: function () {
		var f = this.getAutoCompleteLabelField();
		f.prop('id', this.element.prop('id') + '-auto-complete');
		f.prop('name', this.element.propt('name').replace('[]', '') + '-auto-complete');
		$('#' + f.prop('id')).val('');
		new FbAutocomplete(this.element.id, this.options.autoCompleteOpts);
	},

	init: function () {
		var self = this;
		// Could be in a popup add record form, in which case we don't want to ini on a main page load
		if (typeOf(this.element) === 'null') {
			return;
		}
		if (this.options.editable) {
			this.getCheckboxTmplNode();
		}

		// If users can add records to the database join drop down
		if (this.options.allowadd === true && this.options.editable !== false) {
			this.watchAdd();
			Fabrik.addEvent('fabrik.form.submitted', function (form, json) {

				// Fired when form submitted - enables element to update itself with any new submitted data
				if (this.options.popupform === form.id) {

					// Only set the value if this element has triggered the pop up (ie could not be if in a repeat group)
					if (this.activePopUp) {
						this.options.value = json.rowid;
					}
					// rob previously we we doing appendInfo() but that didnt get the concat labels for the database join
					if (this.options.displayType === 'auto-complete') {

						// Need to get v if auto-complete and updating from posted popup form as we only want to get ONE
						// option back inside update();
						new Request.JSON({
							'url': 'index.php?option=com_fabrik&view=form&format=raw',
							'data': {
								'formid': this.options.popupform,
								'rowid': json.rowid
							},
							'onSuccess': function (json) {
								this.update(json.data[this.options.key]);
							}.bind(this)
						}).send();
					} else {
						this.updateFromServer();
					}
				}
			}.bind(this));
		}

		if (this.options.editable) {
			this.watchSelect();
			if (this.options.showDesc === true) {
				this.element.on('change', function (e) {
					self.showDesc(e);
				});
			}
		}
	},

	/**
	 *
	 * @returns {jQuery}
	 */
	getAutoCompleteLabelField: function () {
		var p = this.element.closest('.fabrikElement');
		var f = p.find('input[name*=-auto-complete]');
		if (f.length === 0) {
			f = p.find('input[id*=-auto-complete]');
		}
		return f;
	},

	addNewEventAux: function (action, js) {
		var self = this;
		switch (this.options.displayType) {
		case 'dropdown':
		/* falls through */
		default:
			if (this.element) {
				this.element.on(action, function (e) {
					if (e) {
						e.stopPropagation();
					}
					(typeof(js) === 'function') ? js.delay(0, this, this) : eval(js);
				}.bind(this));
			}
			break;
		case 'checkbox':
		/* falls through */
		case 'radio':
			this._getSubElements();
			this.subElements.each(function (el) {
				el.on(action, function () {
					(typeof(js) === 'function') ? js.delay(0, this, this) : eval(js);
				}.bind(this));
			}.bind(this));
			break;
		case 'auto-complete':
			var f = this.getAutoCompleteLabelField();
			if (f.length > 0) {
				f.on(action, function (e) {
					if (e) {
						e.stopPropagation();
					}
					(typeof(js) === 'function') ? js.delay(700, this, this) : eval(js);
				}.bind(this));
			}
			if (this.element) {
				this.element.on(action, function (e) {
					if (e) {
						e.stopPropagation();
					}
					(typeof(js) === 'function') ? js.delay(0, this, this) : eval(js);
				}.bind(this));
			}
			break;
		}
	},

	decreaseName: function (delIndex) {
		if (this.options.displayType === 'auto-complete') {
			var f = this.getAutoCompleteLabelField();
				f.prop('name', this._decreaseName(f.prop('name'), delIndex, '-auto-complete'));
				f.prop('id', this._decreaseId(f.prop('id'), delIndex, '-auto-complete'));
		}
		return FbDatabasejoin.Super.prototype.decreaseName(this, delIndex);
	},

	/**
	 * When a form/details view is updating its own data, then should we use the raw data or the html?
	 * Raw is used for cdd/db join elements
	 *
	 * @returns {boolean}
	 */
	updateUsingRaw: function () {
		return true;
	}
});