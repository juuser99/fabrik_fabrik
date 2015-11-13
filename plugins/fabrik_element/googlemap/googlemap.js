/**
 * Googlemap Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/** call back method when maps api is loaded*/
function googlemapload() {
    if (Fabrik.googleMapRadius === undefined) {
        var script2 = document.createElement('script'),
            l = document.location,
            path = l.pathname.split('/'),
            index = path.indexOf('index.php');

        // For URLs such as /index.php/elements/form/4/97 - we only want the segment before index.php
        if (index !== -1) {
            path = path.slice(0, index);
        }
        path.shift();
        path = path.join('/');
        script2.type = 'text/javascript';
        script2.src = Fabrik.liveSite + '/components/com_fabrik/libs/googlemaps/distancewidget.js';
        document.body.appendChild(script2);
        Fabrik.googleMapRadius = true;
    }
    if (document.body) {
        $(window).trigger('google.map.loaded');
    } else {
        console.log('no body');
    }
}

function googleradiusloaded() {
    if (document.body) {
        $(window).trigger('google.radius.loaded');
    } else {
        console.log('no body');
    }
}

var FbGoogleMap = my.Class(FbElement, {

    options: {
        'lat'            : 0,
        'lat_dms'        : 0,
        'key'            : '',
        'lon'            : 0,
        'lon_dms'        : 0,
        'zoomlevel'      : '13',
        'control'        : '',
        'maptypecontrol' : false,
        'overviewcontrol': false,
        'scalecontrol'   : false,
        'drag'           : false,
        'maptype'        : 'G_NORMAL_MAP',
        'geocode'        : false,
        'latlng'         : false,
        'latlng_dms'     : false,
        'staticmap'      : false,
        'auto_center'    : false,
        'scrollwheel'    : false,
        'streetView'     : false,
        'sensor'         : false,
        'center'         : 0,
        'reverse_geocode': false,
        'use_radius'     : false,
        'geocode_on_load': false,
        'traffic'        : false,
        'styles'         : []
    },

    loadScript: function () {
        var s = this.options.sensor === false ? 'false' : 'true';
        Fabrik.loadGoogleMap(s, 'googlemapload');
    },

    constructor: function (element, options) {
        this.mapMade = false;
        FbGoogleMap.Super.call(this, element, options);

        this.loadFn = function () {
            switch (this.options.maptype) {
                case 'G_SATELLITE_MAP':
                    this.options.maptype = google.maps.MapTypeId.SATELLITE;
                    break;
                case 'G_HYBRID_MAP':
                    this.options.maptype = google.maps.MapTypeId.HYBRID;
                    break;
                case 'TERRAIN':
                    this.options.maptype = google.maps.MapTypeId.TERRAIN;
                    break;
                default:
                /* falls through */
                case 'G_NORMAL_MAP':
                    this.options.maptype = google.maps.MapTypeId.ROADMAP;
                    break;
            }
            this.makeMap();

            // @TODO test google object when offline typeOf(google) isnt working
            if (this.options.center === 1 && (this.options.rowid === '' || this.options.rowid === 0)) {
                if (geo_position_js.init()) {
                    geo_position_js.getCurrentPosition(this.geoCenter.bind(this), this.geoCenterErr.bind(this), {
                        enableHighAccuracy: true
                    });
                } else {
                    fconsole('Geo location functionality not available');
                }
            }

        }.bind(this);

        this.radFn = function () {
            this.makeRadius();
        }.bind(this);

        $(window).on('google.map.loaded', this.loadFn);
        $(window).on('google.radius.loaded', this.radFn);

        this.loadScript();
    },

    /**
     * Called when form closed in ajax window
     */
    destroy: function () {
        window.removeEvent('google.map.loaded', this.loadFn);
        window.removeEvent('google.radius.loaded', this.radFn);
    },

    getValue: function () {
        if (typeOf(this.field) !== 'null') {
            return this.field.get('value');
        }
        return false;
    },

    makeMap: function () {
        var self = this;
        if (this.mapMade === true) {
            return;
        }
        this.mapMade = true;

        if (typeof(this.map) !== 'undefined' && this.map !== null) {
            return;
        }
        if (this.element.length === 0) {
            return;
        }
        if (this.options.geocode || this.options.reverse_geocode) {
            this.geocoder = new google.maps.Geocoder();
        }
        // Need to use this.options.element as if loading from ajax popup win in list view for some reason
        // this.element refers to the first loaded row, which should have been removed from the dom
        this.element = $('#' + this.options.element);
        if (this.element.length === 0) {
            return;
        }
        this.field = this.element.find('input.fabrikinput');
        this.watchGeoCode();
        if (this.options.staticmap) {
            var i = this.element.find('img');
            var w = parseInt(i.css('width'), 10);
            var h = parseInt(i.css('height'), 10);
        }

        if (!this.options.staticmap) {

            var zoomControl = this.options.control === '' ? false : true;
            var zoomControlStyle = this.options.control === 'GSmallMapControl' ? google.maps.ZoomControlStyle.SMALL : google.maps.ZoomControlStyle.LARGE;

            var mapOpts = {
                center            : new google.maps.LatLng(this.options.lat, this.options.lon),
                zoom              : parseInt(this.options.zoomlevel, 10),
                mapTypeId         : this.options.maptype,
                scaleControl      : this.options.scalecontrol,
                mapTypeControl    : this.options.maptypecontrol,
                overviewMapControl: this.options.overviewcontrol,
                scrollwheel       : this.options.scrollwheel,
                streetViewControl : this.options.streetView,
                zoomControl       : true,
                zoomControlOptions: {
                    style: zoomControlStyle
                }
            };
            this.map = new google.maps.Map($('#' + this.element).find('.map'), mapOpts);
            this.map.setOptions({'styles': this.options.styles});

            if (this.options.traffic) {
                var trafficLayer = new google.maps.TrafficLayer();
                trafficLayer.setMap(this.map);
            }

            var point = new google.maps.LatLng(this.options.lat, this.options.lon);
            var opts = {
                map     : this.map,
                position: point
            };
            opts.draggable = this.options.drag;

            if (this.options.latlng === true) {
                this.element.find('.lat').on('blur', function (e) {
                    self.updateFromLatLng(e);
                });
                this.element.find('.lng').on('blur', function (e) {
                    self.updateFromLatLng(e);
                });
            }

            if (this.options.latlng_dms === true) {
                this.element.find('.latdms').on('blur', function (e) {
                    self.updateFromDMS(e);
                });
                this.element.find('.lngdms').on('blur', function (e) {
                    self.updateFromDMS(e);
                });
            }

            this.marker = new google.maps.Marker(opts);

            if (this.options.latlng === true) {
                this.element.find('.lat').value = this.marker.getPosition().lat() + '° N';
                this.element.find('.lng').value = this.marker.getPosition().lng() + '° E';
            }

            if (this.options.latlng_dms === true) {
                this.element.find('.latdms').value = this.latDecToDMS();
                this.element.find('.lngdms').value = this.lngDecToDMS();
            }

			google.maps.event.addListener(this.marker, 'dragend', function () {
				this.field.value = this.marker.getPosition() + ":" + this.map.getZoom();
				if (this.options.latlng === true) {
					this.element.find('.lat').val(this.marker.getPosition().lat() + '° N');
					this.element.find('.lng').val(this.marker.getPosition().lng() + '° E');
				}
				if (this.options.latlng_dms === true) {
					this.element.find('.latdms').val(this.latDecToDMS());
					this.element.find('.lngdms').val(this.lngDecToDMS());
				}
				if (this.options.latlng_osref === true) {
					this.element.find('.osref').val(this.latLonToOSRef());
				}
				if (this.options.reverse_geocode) {
					this.reverseGeocode();
				}
			}.bind(this));
			google.maps.event.addListener(this.map, 'zoom_changed', function (oldLevel, newLevel) {
				this.field.value = this.marker.getPosition() + ':' + this.map.getZoom();
			}.bind(this));
			if (this.options.auto_center && this.options.editable) {
				google.maps.event.addListener(this.map, 'dragend', function () {
					this.marker.setPosition(this.map.getCenter());
					this.field.value = this.marker.getPosition() + ':' + this.map.getZoom();
					if (this.options.latlng === true) {
						this.element.find('.lat').val(this.marker.getPosition().lat() + '° N');
						this.element.find('.lng').val(this.marker.getPosition().lng() + '° E');
					}
					if (this.options.latlng_dms === true) {
						this.element.find('.latdms').val(this.latDecToDMS());
						this.element.find('.lngdms').val(this.lngDecToDMS());
					}
				}.bind(this));
			}
		}
		this.watchTab();
		Fabrik.addEvent('fabrik.form.page.change.end', function (form) {
			this.redraw();
		}.bind(this));
		if (this.options.geocode && this.options.geocode_on_load) {
			this.geoCode();
		}
	},

    radiusUpdatePosition: function () {

    },

    radiusUpdateDistance: function () {
        if (this.options.radius_write_element) {
            var distance = this.distanceWidget.get('distance');
            if (this.options.radius_unit === 'm') {
                distance = distance / 1.609344;
            }
            $(this.options.radius_write_element).value = parseFloat(distance).toFixed(2);

        }
    },

    radiusActiveChanged: function () {
        // fired by the radius widget when move / drag operation is complete
        // so let's fire the write element's change event.  Don't do this in updateDistance,
        // as it'll keep firing as they drag.  We don't want to fire 'change' until the changing is finished
        if (this.options.radius_write_element) {
            if (!this.distanceWidget.get('active')) {
                $('#' + this.options.radius_write_element).trigger('change');
            }
        }
    },

    radiusSetDistance: function () {
        if (this.options.radius_read_element) {
            var distance = $('#' + this.options.radius_read_element).val();
            if (this.options.radius_unit === 'm') {
                distance = distance * 1.609344;
            }
            var pos = this.distanceWidget.get('sizer_position');
            this.distanceWidget.set('distance', distance);
            var center = this.distanceWidget.get('center');
            this.distanceWidget.set('center', center);
        }
    },

    makeRadius: function () {
        if (this.options.use_radius) {
            if (this.options.radius_read_element && this.options.repeatCounter > 0) {
                this.options.radius_read_element = this.options.radius_read_element.replace(/_\d+$/, '_' + this.options.repeatCounter);
            }
            if (this.options.radius_write_element && this.options.repeatCounter > 0) {
                this.options.radius_write_element = this.options.radius_write_element.replace(/_\d+$/, '_' + this.options.repeatCounter);
            }
            var distance = this.options.radius_default;
            if (!this.options.editable) {
                distance = this.options.radius_ro_value;
            }
            else {
                if (this.options.radius_read_element) {
                    distance = $('#' + this.options.radius_read_element).val();
                }
                else if (this.options.radius_write_element) {
                    distance = $('#' + this.options.radius_write_element).val();
                }
            }
            if (this.options.radius_unit === 'm') {
                distance = distance * 1.609344;
            }
            this.distanceWidget = new DistanceWidget({
                map            : this.map,
                marker         : this.marker,
                distance       : distance, // Starting distance in km.
                maxDistance    : 2500, // Twitter has a max distance of 2500km.
                color          : '#000000',
                activeColor    : '#5599bb',
                sizerIcon      : new google.maps.MarkerImage(this.options.radius_resize_off_icon),
                activeSizerIcon: new google.maps.MarkerImage(this.options.radius_resize_icon)
            });

            google.maps.event.addListener(this.distanceWidget, 'distance_changed', this.radiusUpdateDistance.bind(this));
            google.maps.event.addListener(this.distanceWidget, 'position_changed', this.radiusUpdatePosition.bind(this));
            google.maps.event.addListener(this.distanceWidget, 'active_changed', this.radiusActiveChanged.bind(this));

            if (this.options.radius_fitmap) {
                this.map.setZoom(20);
                this.map.fitBounds(this.distanceWidget.get('bounds'));
            }
            this.radiusUpdateDistance();
            this.radiusUpdatePosition();
            this.radiusAddActions();
        }
    },

    radiusAddActions: function () {
        if (this.options.radius_read_element) {
            $('#' + this.options.radius_read_element).on('change', this.radiusSetDistance.bind(this));
        }
    },

    updateFromLatLng: function () {
        var lat = this.element.find('.lat').get('value').replace('° N', '');
        lat = lat.replace(',', '.').toFloat();
        var lng = this.element.find('.lng').get('value').replace('° E', '');
        lng = lng.replace(',', '.').toFloat();
        var pnt = new google.maps.LatLng(lat, lng);
        this.marker.setPosition(pnt);
        this.doSetCenter(pnt, this.map.getZoom(), true);
    },

    updateFromDMS: function () {
        var dms = this.element.find('.latdms');
        var latdms = dms.get('value').replace('S', '-');
        latdms = latdms.replace('N', '');
        dms = this.element.find('.lngdms');
        var lngdms = dms.get('value').replace('W', '-');
        lngdms = lngdms.replace('E', '');

        var latdms_d_ms = latdms.split('°');
        var latdms_topnt = latdms_d_ms[0];
        var latdms_m_s = latdms_d_ms[1].split('\'');
        var latdms_m = latdms_m_s[0].toFloat() * 60;
        var latdms_ms = (latdms_m + latdms_m_s[1].replace('"', '').toFloat()) / 3600;
        latdms_topnt = Math.abs(latdms_topnt.toFloat()) + latdms_ms.toFloat();
        if (latdms_d_ms[0].toString().indexOf('-') !== -1) {
            latdms_topnt = -latdms_topnt;
        }

        var lngdms_d_ms = lngdms.toString().split('°');
        var lngdms_topnt = lngdms_d_ms[0];
        var lngdms_m_s = lngdms_d_ms[1].split('\'');
        var lngdms_m = Math.abs(lngdms_m_s[0].toFloat()) * 60;
        var lngdms_ms = (lngdms_m + Math.abs(lngdms_m_s[1].replace('"', '').toFloat())) / 3600;
        lngdms_topnt = Math.abs(lngdms_topnt.toFloat()) + lngdms_ms.toFloat();
        if (lngdms_d_ms[0].toString().indexOf('-') !== -1) {
            lngdms_topnt = -lngdms_topnt;
        }

        var pnt = new google.maps.LatLng(latdms_topnt.toFloat(), lngdms_topnt.toFloat());
        this.marker.setPosition(pnt);
        this.doSetCenter(pnt, this.map.getZoom(), true);
    },

    latDecToDMS: function () {
        var latdec = this.marker.getPosition().lat();
        var dmslat_d = parseInt(Math.abs(latdec), 10);
        var dmslat_m_float = 60 * (Math.abs(latdec).toFloat() - dmslat_d.toFloat());
        var dmslat_m = parseInt(dmslat_m_float, 10);
        var dmslat_s_float = 60 * (dmslat_m_float.toFloat() - dmslat_m.toFloat());
        // var dmslat_s = Math.round(dmslat_s_float.toFloat()*100)/100;
        var dmslat_s = dmslat_s_float.toFloat();

        if (dmslat_s === 60) {
            dmslat_m = dmslat_m.toFloat() + 1;
            dmslat_s = 0;
        }
        if (dmslat_m === 60) {
            dmslat_d = dmslat_d.toFloat() + 1;
            dmslat_m = 0;
        }

        var dmslat_dir = 'N';
        if (latdec.toString().indexOf('-') !== -1) {
            dmslat_dir = 'S';
        } else {
            dmslat_dir = 'N';
        }

        return dmslat_dir + dmslat_d + '°' + dmslat_m + '\'' + dmslat_s + '"';

    },

    lngDecToDMS: function () {
        var lngdec = this.marker.getPosition().lng();
        var dmslng_d = parseInt(Math.abs(lngdec), 10);
        var dmslng_m_float = 60 * (Math.abs(lngdec).toFloat() - dmslng_d.toFloat());
        var dmslng_m = parseInt(dmslng_m_float, 10);
        var dmslng_s_float = 60 * (dmslng_m_float.toFloat() - dmslng_m.toFloat());
        // var dmslng_s = Math.round(dmslng_s_float.toFloat()*100)/100;
        var dmslng_s = dmslng_s_float.toFloat();

        if (dmslng_s === 60) {
            dmslng_m.value = dmslng_m.toFloat() + 1;
            dmslng_s.value = 0;
        }
        if (dmslng_m === 60) {
            dmslng_d.value = dmslng_d.toFloat() + 1;
            dmslng_m.value = 0;
        }

        var dmslng_dir = '';
        if (lngdec.toString().indexOf('-') !== -1) {
            dmslng_dir = 'W';
        } else {
            dmslng_dir = 'E';
        }

        return dmslng_dir + dmslng_d + '°' + dmslng_m + '\'' + dmslng_s + '"';

	},

	latLonToOSRef: function () {
		var ll2 = new LatLng(this.marker.getPosition().lng(), this.marker.getPosition().lng());
		var OSRef = ll2.toOSRef();
		return OSRef.toSixFigureString();
	},

    geoCode: function (e) {
        var address = '';
        if (this.options.geocode === '2') {
            this.options.geocode_fields.each(function (field) {
                var f = this.form.formElements.get(field);
                if (f) {
                    address += f.get('value') + ',';
                }
            }.bind(this));
            address = address.slice(0, -1);
        } else {
            address = this.element.find('.geocode_input').value;
        }
        // Strip HTML
        var d = $(document.createElement('div')).html(address);
        address = d.get('text');
        this.geocoder.geocode({'address': address}, function (results, status) {
            if (status !== google.maps.GeocoderStatus.OK || results.length === 0) {
                fconsole(address + ' not found!');
            } else {
                this.marker.setPosition(results[0].geometry.location);
                this.doSetCenter(results[0].geometry.location, this.map.getZoom(), false);
            }
        }.bind(this));
    },

    watchGeoCode: function () {
        var self = this;
        if (!this.options.geocode || !this.options.editable) {
            return false;
        }
        if (this.options.geocode === '2') {
            if (this.options.geocode_event !== 'button') {
                this.options.geocode_fields.each(function (field) {
                    var f = $('#' + field);
                    f.on('keyup', function (e) {
                        self.geoCode();
                    });

                    // Select lists, radios whatnots
                    f.on('change', function (e) {
                        self.geoCode();
                    });

                });
            } else {
                if (this.options.geocode_event === 'button') {
                    this.element.find('.geocode').on('click', function (e) {
                        self.geoCode(e);
                    });
                }
            }
        }
        if (this.options.geocode === '1' && $('#' + this.element).find('.geocode_input').length > 0) {
            if (this.options.geocode_event === 'button') {
                this.element.find('.geocode').on('click', function (e) {
                    self.geoCode(e);
                });

                // Stop enter in geocode field submitting the form.
                this.element.find('.geocode_input').on('keypress', function (e) {
                    if (e.key === 'enter') {
                        e.stopPropagation();
                    }
                });
            } else {
                this.element.find('.geocode_input').on('keyup', function (e) {
                    e.stopPropagation();
                    self.geoCode(e);
                });
            }
        }
    },

    unclonableProperties: function () {
        return ['form', 'marker', 'map', 'maptype'];
    },

    cloned: function (c) {
        var f = [];
        this.options.geocode_fields.each(function (field) {
            var bits = field.split('_');
            var i = bits.getLast();
            if (isNaN(parseInt(i, 10))) {
                return bits.join('_');
            }
            bits.splice(bits.length - 1, 1, c);
            f.push(bits.join('_'));
        });
        this.options.geocode_fields = f;
        this.mapMade = false;
        this.map = null;
        this.makeMap();
        FbGoogleMap.Super.prototype.cloned(this, c);
    },

    update: function (v) {
        v = v.split(':');
        if (v.length < 2) {
            v[1] = this.options.zoomlevel;
        }
        if (!this.map) {
            return;
        }
        var zoom = parseInt(v[1], 10);
        this.map.setZoom(zoom);
        v[0] = v[0].replace('(', '');
        v[0] = v[0].replace(')', '');
        var pnts = v[0].split(',');
        if (pnts.length < 2) {
            pnts[0] = this.options.lat;
            pnts[1] = this.options.lon;
        }
        // $$$ hugh - updateFromLatLng blows up if not displayinbg lat lng
        // also, not sure why we would do this, as all we want to do is set map back
        // to default
        // location, not read location from lat lng fields?
        // this.updateFromLatLng(pnts[0], pnts[1]);
        // So instead, lets just set marker to default and recenter
        var pnt = new google.maps.LatLng(pnts[0], pnts[1]);
        this.marker.setPosition(pnt);
        this.doSetCenter(pnt, this.map.getZoom(), true);
    },

    geoCenter: function (p) {
        var pnt = new google.maps.LatLng(p.coords.latitude, p.coords.longitude);
        this.marker.setPosition(pnt);
        this.doSetCenter(pnt, this.map.getZoom(), true);
    },

    geoCenterErr: function (p) {
        fconsole('geo location error=' + p.message);
    },

    /**
     * Redraw the map when inside a tab, and the tab is activated. Triggered from element.watchTab()
     */
    redraw: function () {
        google.maps.event.trigger(this.map, 'resize');
        var center = new google.maps.LatLng(this.options.lat, this.options.lon);
        this.map.setCenter(center);
        this.map.setZoom(this.map.getZoom());
    },

    reverseGeocode: function () {
        this.geocoder.geocode({'latLng': this.marker.getPosition()}, function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                if (results[0]) {
                    //infowindow.setContent(results[1].formatted_address);
                    //infowindow.open(map, marker);
                    //alert(results[0].formatted_address);
                    /**
                     * @TODO - simplify this, as we now index the reverse_geocode_fields with the same keys that
                     * Google do.  So no need to go through each possibility and map to our key name.  In other words,
                     * don't need to map "administrative_area_1" on to "state".  However, we do need to fix the handling
                     * of street_number, route and street_address, as it seems that the Goog
                     */
                    var reverseGeoCodes = this.options.reverse_geocode_fields,
                        elements = this.form.formElements;
                    results[0].address_components.each(function (component) {
                        component.types.each(function (type) {
                            switch (type) {
                                case 'street_number':
                                    if (this.options.reverse_geocode_fields.route) {
                                        elements.get(reverseGeoCodes.route).update(component.long_name + ' ');
                                    }
                                    break;
                                case 'route':
                                    if (reverseGeoCodes.route) {
                                        elements.get(reverseGeoCodes.route).update(component.long_name);
                                    }
                                    break;
                                case 'street_address':
                                    if (reverseGeoCodes.route) {
                                        elements.get(reverseGeoCodes.route).update(component.long_name);
                                    }
                                    break;
                                case 'neighborhood':
                                    if (reverseGeoCodes.neighborhood) {
                                        elements.get(reverseGeoCodes.neighborhood).update(component.long_name);
                                    }
                                    break;
                                case 'locality':
                                    if (reverseGeoCodes.locality) {
                                        elements.get(reverseGeoCodes.locality).updateByLabel(component.long_name);
                                    }
                                    break;
                                case 'administrative_area_level_1':
                                    if (reverseGeoCodes.administrative_area_level_1) {
                                        elements.get(reverseGeoCodes.administrative_area_level_1).updateByLabel(component.long_name);
                                    }
                                    break;
                                case 'postal_code':
                                    if (reverseGeoCodes.postal_code) {
                                        elements.get(reverseGeoCodes.postal_code).updateByLabel(component.long_name);
                                    }
                                    break;
                                case  'country':
                                    if (reverseGeoCodes.country) {
                                        elements.get(reverseGeoCodes.country).updateByLabel(component.long_name);
                                    }
                                    break;
                            }
                        }.bind(this));
                    }.bind(this));
                }
                else {
                    window.alert('No results found');
                }
            } else {
                window.alert('Geocoder failed due to: ' + status);
            }
        }.bind(this));
    },

    doSetCenter: function (pnt, zoom, doReverseGeocode) {
        this.map.setCenter(pnt, zoom);
        this.field.value = this.marker.getPosition() + ':' + this.map.getZoom();
        if (this.options.latlng === true) {
            this.element.find('.lat').value = pnt.lat() + '° N';
            this.element.find('.lng').value = pnt.lng() + '° E';
        }
        if (this.options.latlng_dms === true) {
            this.element.find('.latdms').value = this.latDecToDMS();
            this.element.find('.lngdms').value = this.lngDecToDMS();
        }
        if (doReverseGeocode && this.options.reverse_geocode) {
            this.reverseGeocode();
        }
    }

});