/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

/*global google */
pimcore.registerNS('pimcore.object.tags.geopoint');
pimcore.object.tags.geopoint = Class.create(pimcore.object.tags.geo.abstract, {

    type: 'geopoint',

    getGridColumnConfig: function(field) {
        return {
            header: ts(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.css += ' grid_value_inherited';
                }

                if (value && value.latitude && value.longitude) {
                    var width = 140;
                    var mapZoom = 10;

                    var mapUrl = 'https://maps.google.com/staticmap?center=' + value.latitude + ','
                            + value.longitude + '&zoom=' + mapZoom + '&size=' + width + 'x80&markers='
                            + value.latitude + ',' + value.longitude
                            + ',red&sensor=false';

                    if (pimcore.settings.google_maps_api_key) {
                        mapUrl += '&key=' + pimcore.settings.google_maps_api_key;
                    }

                    return '<img src="' + mapUrl + '" />';
                }
            }.bind(this, field.key)
        };
    },

    getLayoutEdit: function () {

        this.mapImageID = uniqid();

        var coordConf = {
            decimalPrecision: 15,
            enableKeyEvents: true,
            width: 95
        };

        this.longitude = new Ext.form.NumberField(coordConf);
        this.latitude = new Ext.form.NumberField(coordConf);

        if (this.data) {
            this.longitude.setValue(this.data.longitude);
            this.latitude.setValue(this.data.latitude);
        }


        this.longitude.on('keyup', this.updatePreviewImage.bind(this));
        this.latitude.on('keyup', this.updatePreviewImage.bind(this));

        this.component = new Ext.Panel({
            title: this.fieldConfig.title,
            height: 370,
            width: 490,
            cls: "object_field",
            html: '<div id="google_maps_container_' + this.mapImageID + '" align="center">'
                  + '<img align="center" width="300" height="300" src="'
                  + this.getMapUrl() + '" /></div>',
            bbar: [
                t('latitude'),
                this.latitude,
                '-',
                t('longitude'),
                this.longitude,
                '-', {
                    xtype: 'button',
                    text: t('empty'),
                    icon: '/pimcore/static/img/icon/bin.png',
                    handler: function () {
                        this.latitude.setValue(null);
                        this.longitude.setValue(null);
                        this.updatePreviewImage();
                    }.bind(this)
                }, '->', {
                    xtype: 'button',
                    text: t('open_search_editor'),
                    icon: '/pimcore/static/img/icon/magnifier.png',
                    handler: this.openPicker.bind(this)
                }
            ]
        });

        this.component.on('afterrender', function () {
            this.updatePreviewImage();
        }.bind(this));

        return this.component;
    },

    updatePreviewImage: function ($super) {
        var data = this.getValue();
        if (data.latitude && data.longitude) {
            this.data = data;
        } else {
            this.data = null;
        }
        $super();
    },

    getMapUrl: function (width) {

        // static maps api image url
        var mapZoom = this.fieldConfig.zoom;
        var mapUrl;

        if (!width) {
            width = 300;
        }

        var py = 300;
        var px = width;

        // static maps api image url
        var lat = this.fieldConfig.lat;
        var lng = this.fieldConfig.lng;
        if (this.data) {
            lat = this.data.latitude;
            lng = this.data.longitude;
            mapZoom = 15;

            mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center='
                + lat + "," + lng + "&zoom=" + mapZoom +
                '&size=' + px + 'x' + py
                + '&markers=color:red|' + lat + ',' + lng
                + '&sensor=false&maptype=' + this.fieldConfig.mapType;
        } else {
            mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center='
                + lat + "," + lng + "&zoom=" + mapZoom +
                '&size=' + px + 'x' + py
                + '&sensor=false&maptype=' + this.fieldConfig.mapType;
        }

        if (pimcore.settings.google_maps_api_key) {
            mapUrl += '&key=' + pimcore.settings.google_maps_api_key;
        }

        return mapUrl;
    },

    openPicker: function () {

        this.searchfield = new Ext.form.TextField({
            width: 300,
            name: 'mapSearch',
            style: 'float: left;',
            fieldLabel: t('search')
        });

        this.currentLocationTextNode = new Ext.Toolbar.TextItem({
            text: '&nbsp;'
        });

        this.searchWindow = new Ext.Window({
            modal: true,
            width: 600,
            height: 500,
            resizable: false,
            tbar: [this.currentLocationTextNode],
            bbar: [this.searchfield,{
                xtype: 'button',
                text: t('search'),
                icon: '/pimcore/static/img/icon/magnifier.png',
                handler: this.geocode.bind(this)
            }, '->', {
                xtype: 'button',
                text: t('cancel'),
                icon: '/pimcore/static/img/icon/cancel.png',
                handler: function () {
                    this.searchWindow.close();
                }.bind(this)
            },{
                xtype: 'button',
                text: 'OK',
                icon: '/pimcore/static/img/icon/tick.png',
                handler: function () {
                    if (this.overlay) {
                        var point = this.overlay.getPosition();
                        this.latitude.setValue(point.lat());
                        this.longitude.setValue(point.lng());
                    }
                    this.updatePreviewImage();
                    this.searchWindow.close();
                }.bind(this)
            }],
            plain: true
        });

        this.searchWindow.on('afterrender', function () {

            var center = new google.maps.LatLng(this.fieldConfig.lat, this.fieldConfig.lng);
            var mapZoom = this.fieldConfig.zoom;

            if (this.data) {
                center = new google.maps.LatLng(this.data.latitude, this.data.longitude);
                mapZoom = 15;
            }

            this.gmap = new google.maps.Map(this.searchWindow.body.dom, {
                zoom: mapZoom,
                center: center,
                streetViewControl: false,
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
                },
                mapTypeId: this.fieldConfig.mapType
            });

            this.drawingManager = new google.maps.drawing.DrawingManager({
                drawingControl: false,
                markerOptions: {
                    draggable: true
                },
                map: this.gmap
            });

            google.maps.event.addListener(this.drawingManager, 'overlaycomplete', function (e) {
                // Switch back to non-drawing mode after drawing a shape.
                this.drawingManager.setDrawingMode(null);

                this.overlay = e.overlay;
            }.bind(this));

            if (this.data) {
                this.renderOverlay();
            } else {
                this.drawingManager.setDrawingMode(google.maps.drawing.OverlayType.MARKER);
            }

            this.geocoder = new google.maps.Geocoder();
            this.reverseGeocodeInterval = window.setInterval(this.reverseGeocode.bind(this), 500);

        }.bind(this));

        this.searchWindow.on('beforeclose', function () {
            clearInterval(this.reverseGeocodeInterval);
        }.bind(this));

        this.searchWindow.show();
    },

    renderOverlay: function() {
        if (this.data) {
            this.overlay =  new google.maps.Marker({
                position: new google.maps.LatLng(this.data.latitude, this.data.longitude),
                map: this.gmap,
                draggable: true
            });
        }
    },

    geocode: function () {
        if (!this.geocoder) {
            return;
        }

        var address = this.searchfield.getValue();
        this.geocoder.geocode( { 'address': address}, function(results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                var point = results[0].geometry.location;
                this.gmap.setCenter(point, 16);
                this.gmap.setZoom(15);
                this.drawingManager.setDrawingMode(null);
                this.data = {
                    latitude: point.lat(),
                    longitude: point.lng()
                };
                this.renderOverlay();
            }
        }.bind(this));
    },

    reverseGeocode: function () {
        if (this.overlay) {

            var latlng = this.overlay.getPosition();
            if (latlng && latlng !== this.lastPosition) {
                this.lastPosition = latlng;
                this.geocoder.geocode({'latLng': latlng}, function(results, status) {
                    if (status === google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            this.currentLocationTextNode.setText(results[0].formatted_address);
                        }
                    }
                }.bind(this));
            }
        }
    },

    getValue: function () {
        return {
            longitude: this.longitude.getValue(),
            latitude: this.latitude.getValue()
        };
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {

        // no render check is necessary because the input compontent returns the right values even if it is not
        // rendered
        var value = this.getValue();
        if (value.longitude && value.latitude) {
            return false;
        }
        return true;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        return this.longitude.isDirty() || this.latitude.isDirty();
    }
});
