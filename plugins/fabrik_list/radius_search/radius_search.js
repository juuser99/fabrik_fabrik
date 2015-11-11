/**
 * List Radius Search
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var doGeoCode = function (btn) {
	var uberC = btn.retrieve('uberC'),
		mapid = btn.retrieve('mapid'),
		address =  btn.retrieve('fld').value,
		geocoder = new google.maps.Geocoder();

	if (!Fabrik.radiusSearchResults) {
		Fabrik.radiusSearchResults = {};
	}

	if (Fabrik.radiusSearchResults[address]) {
		parseGeoCodeResult(uberC, mapid, Fabrik.radiusSearchResults[address]);
	}
	geocoder.geocode({'address': address}, function (results, status) {
		if (status === google.maps.GeocoderStatus.OK) {
			parseGeoCodeResult(uberC, mapid, results[0].geometry.location);
			Fabrik.radiusSearchResults[address] = results[0].geometry.location;
		} else {
			alert(Joomla.JText._('PLG_LIST_RADIUS_SEARCH_GEOCODE_ERROR').replace('%s', status));
		}
	});
};

/**
 * Parse a google geocode result.
 * @param {domnode} uberC Radius search container div
 * @param {string} mapid  Map id
 * @param {object} loc
 */
var parseGeoCodeResult = function (uberC, mapid, loc) {
	uberC.find('input[name^=radius_search_geocode_lat]').value = loc.lat();
	uberC.find('input[name^=radius_search_geocode_lon]').value = loc.lng();
	Fabrik.radiusSearch[mapid].map.setCenter(loc);
	Fabrik.radiusSearch[mapid].marker.setPosition(loc);
}


function geoCode() {
	// Tell fabrik that the google map script has loaded and the callback has run
	Fabrik.googleMap = true;

	$(window).on('domready', function () {
		var latlng = new google.maps.LatLng(Fabrik.radiusSearch.geocode_default_lat, Fabrik.radiusSearch.geocode_default_long);
		var mapOptions = {
			zoom: 4,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		Fabrik.radiusSearch = typeOf(Fabrik.radiusSearch) === 'null' ? {} : Fabrik.radiusSearch;
		var radiusSearchMaps = $('.radius_search_geocode_map');
		radiusSearchMaps.each(function (map) {
			var c = map.closest('.radius_search_geocode');
			var btn = c.find('button');
			var trigger = btn ? btn : c.find('.radius_search_geocode_field');
			if (trigger.retrieve('events-added', 0).toInt() !== 1) {
				Fabrik.radiusSearch[map.id] = typeOf(Fabrik.radiusSearch[map.id]) === 'null' ? {} : Fabrik.radiusSearch[map.id];
				Fabrik.radiusSearch[map.id].map = new google.maps.Map(map, mapOptions);

				var uberC = c.closest('.radius_search');

				trigger.store('events-added', 1);
				trigger.store('uberC', uberC);
				trigger.store('mapid', map.id);


				var fld = c.find('.radius_search_geocode_field');
				trigger.store('fld', fld);

				if (btn.length !== 0) {
					btn.on('click', function (e) {
						e.stop();
						doGeoCode(trigger);
					});
					fld.on('keyup', function (e) {
						if (e.key === 'enter') {
							doGeoCode(trigger);
						}
					});
				} else {
					var timer;
					fld.on('keyup', function (e) {
						if (timer) {
							clearTimeout(timer);
						}
						if (e.key === 'enter') {
							doGeoCode(trigger);
						}
						timer = window.setTimeout(function () {
							doGeoCode(trigger);
						}, 1000);
					});
				}

				var zoom = uberC.find('input[name=geo_code_def_zoom]').get('value').toInt();
				var lat = uberC.find('input[name=geo_code_def_lat]').get('value').toFloat();
				var lon = uberC.find('input[name=geo_code_def_lon]').get('value').toFloat();
				Fabrik.trigger('google.radiusmap.loaded', [map.id, zoom, lat, lon]);
			}
		});
	});
}


var FbListRadiusSearch = my.Class(FbListPlugin, {

	options: {
		geocode_default_lat: '0',
		geocode_default_long: '0',
		geocode_default_zoom: 4,
		prefilter: true,
		prefilterDistance: 1000,
		prefilterDone: false,
		offset_y: 0
	},

	geocoder: null,
	map: null,

	constructor : function (options) {
		var self = this;
		this.parent(options);
		Fabrik.radiusSearch = Fabrik.radiusSearch ? Fabrik.radiusSearch  : {};

		var mapid = 'radius_search_geocode_map' + this.options.renderOrder;
		if (Fabrik.radiusSearch[mapid] === undefined) {
			Fabrik.radiusSearch[mapid] = {};

			Fabrik.radiusSearch[mapid].geocode_default_lat = this.options.geocode_default_lat;
			Fabrik.radiusSearch[mapid].geocode_default_long = this.options.geocode_default_long;
			Fabrik.radiusSearch[mapid].geocode_default_zoom = this.options.geocode_default_zoom;
			Fabrik.addEvent('google.radiusmap.loaded', function (mapid, zoom, lat, lon) {

				var latlng = new google.maps.LatLng(lat, lon);
				if (Fabrik.radiusSearch[mapid].loaded) {
					return;
				}
				Fabrik.radiusSearch[mapid].loaded = true;
				Fabrik.radiusSearch[mapid].map.setCenter(latlng);
				Fabrik.radiusSearch[mapid].map.setZoom(zoom);
				Fabrik.radiusSearch[mapid].marker = new google.maps.Marker({
					map: Fabrik.radiusSearch[mapid].map,
					draggable: true,
					position: latlng
				});

				google.maps.event.addListener(Fabrik.radiusSearch[mapid].marker, "dragend", function () {
					var loc = Fabrik.radiusSearch[mapid].marker.getPosition();
					var uberC = $('#' + mapid).closest('.radius_search');
					var geocodeLat = uberC.find('input[name^=radius_search_geocode_lat]');
					if (geocodeLat.length !== 0) {
						geocodeLat.val(loc.lat());
						uberC.find('input[name^=radius_search_geocode_lon]').val(loc.lng());
					}
				});

			});

			Fabrik.loadGoogleMap(true, 'geoCode');

			if (typeOf(this.options.value) === 'null') {
				this.options.value = 0;
			}

			if (typeOf(this.listform) !== 'null') {
				this.listform = this.listform.find('#radius_search' + this.options.renderOrder);
				if (typeOf(this.listform) === 'null') {
					fconsole('didnt find element #radius_search' + this.options.renderOrder);
					return;
				}

				var select = this.listform.find('select[name^=radius_search_type]');
				select.on('change', function (e) {
					self.toggleFields(e);
				});

				this.listform.find('input.cancel').addEvent('click', function () {
					this.win.close();
				}.bind(this));

				this.active = false;
				this.listform.find('.fabrik_filter_submit').addEvent('mousedown', function (e) {
					this.active = true;
					this.listform.find('input[name^=radius_search_active]').value = 1;
				}.bind(this));

			}

			this.options.value = this.options.value.toInt();
			if (typeOf(this.listform) === 'null') {
				return;
			}
			var output = this.listform.find('.radius_search_distance');
			var output2 = this.listform.find('.slider_output');
			this.mySlide = new Slider(this.listform.find('.fabrikslider-line'), this.listform.find('.knob'), {
				onChange : function (pos) {
					output.value = pos;
					output2.text(pos + this.options.unit);
				}.bind(this),
				steps : this.options.steps
			}).set(0);

			this.mySlide.set(this.options.value);
			output.value = this.options.value;
			output2.text(this.options.value);

			if (this.options.myloc && !this.options.prefilterDone) {
				if (geo_position_js.init()) {
					geo_position_js.getCurrentPosition(function (p) {
						this.setGeoCenter(p);
					}.bind(this),
					function (e) {
						this.geoCenterErr(e);
					}.bind(this), {
						enableHighAccuracy : true
					});
				}
			}
		}

		// Ensure that if in a map viz clearing the list filter is run.
		Fabrik.addEvent('listfilter.clear', function (caller) {
			if (caller.contains(this.options.ref)) {
				this.clearFilter();
			}
		}.bind(this));
		this.makeWin(mapid);
	},

	/**
	 * Moves the interface into a window and injects a search button to open it.
	 */
	makeWin: function (mapid) {
		var c = $('#' + mapid).closest('.radius_search'),
			b = $(document.createElement('button')).addClass('btn button').html('<i class="icon-location"></i> ' + Joomla.JText._('COM_FABRIK_SEARCH'));
		c.parent().adopt(b);
		var offset_y = this.options.offset_y > 0 ? this.options.offset_y : null;
		var winOpts = {
				'id': 'win_' + mapid,
				'title': Joomla.JText._('PLG_LIST_RADIUS_SEARCH'),
				'loadMethod': 'html',
				'content': c,
				'width': 500,
				'height': 540,
				'offset_y': offset_y,
				'visible': false,
				'destroy': false,
				'onContentLoaded': function () {
					this.center();
				},
				'onClose': function (e, x) {
					var active;
					if (!this.active && confirm(Joomla.JText._('PLG_LIST_RADIUS_SEARCH_CLEAR_CONFIRM'))) {
						active = 0;
					} else {
						active = 1;
					}
					this.win.window.find('input[name^=radius_search_active]').value = active;
				}.bind(this)
			};
		var win = Fabrik.getWindow(winOpts);

		b.on('click', function (e) {
			e.stop();

			// Show the map.
			c.css({'position': 'relative', 'left': 0});
			var w = b.retrieve('win');
			w.center();
			w.open();
		});

		b.store('win', win);
		this.button = b;
		this.win = win;

		// When submitting the filter re-injet the window content back into the <form>
		Fabrik.addEvent('list.filter', function (list) {
			return this.injectIntoListForm();
		}.bind(this));
	},

	/**
	 * Re-inject the radius search form back into the list's form. Needed when filtering or
	 * clearing filters
	 */
	injectIntoListForm: function () {
		var win = this.button.retrieve('win');
		var c = win.contentEl.clone();
		c.hide();
		this.button.parent().adopt(c);
		return true;
	},

	setGeoCenter: function (p) {
		this.geocenterpoint = p;
		this.geoCenter(p);
		this.prefilter();
	},

	/**
	 * The list is set to prefilter
	 */
	prefilter: function () {
		if (this.options.prefilter) {
			this.mySlide.set(this.options.prefilterDistance);

			this.listform.find('input[name^=radius_search_active]').value = 1;
			this.listform.find('input[value=mylocation]').checked = true;
			if (!this.list) {
				// In a viz
				this.listform.closest('form').submit();
			} else {
				this.getList().submit('filter');
			}
		}
	},

	geoCenter: function (p) {
		if (typeOf(p) === 'null') {
			alert(Joomla.JText._('PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE'));
		} else {
			this.listform.find('input[name*=radius_search_lat]').value = p.coords.latitude.toFixed(2);
			this.listform.find('input[name*=radius_search_lon]').value = p.coords.longitude.toFixed(2);
		}
	},

	geoCenterErr: function (p) {
		fconsole('geo location error=' + p.message);
	},

	toggleActive: function (e) {

	},

	toggleFields: function (e) {
		var c = e.target.closest('.radius_search');

		switch (e.target.get('value')) {
		case 'latlon':
			c.find('.radius_search_place_container').hide();
			c.find('.radius_search_coords_container').show();
			c.find('.radius_search_geocode').css({'position': 'absolute', 'left': '-100000px'});

			break;
		case 'mylocation':
			c.find('.radius_search_place_container').hide();
			c.find('.radius_search_coords_container').hide();
			c.find('.radius_search_geocode').css({'position': 'absolute', 'left': '-100000px'});
			this.setGeoCenter(this.geocenterpoint);
			break;
		case 'place':
			c.find('.radius_search_place_container').show();
			c.find('.radius_search_coords_container').hide();
			c.find('.radius_search_geocode').css({'position': 'absolute', 'left': '-100000px'});
			break;
		case 'geocode':
			c.find('.radius_search_place_container').hide();
			c.find('.radius_search_coords_container').hide();
			c.find('.radius_search_geocode').css({'position': 'relative', 'left': 0});
			break;
		}
	},

	clearFilter: function () {
		this.listform.find('input[name^=radius_search_active]').value = 0;
		//return this.injectIntoListForm();
		return true;
	}

});