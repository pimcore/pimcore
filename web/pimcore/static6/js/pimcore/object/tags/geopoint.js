/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

/*global google */
pimcore.registerNS('pimcore.object.tags.geopoint');
pimcore.object.tags.geopoint = Class.create(pimcore.object.tags.geo.abstract, {

    type: 'geopoint',

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
            //set raw values to stop values being initially dirty
            this.longitude.setRawValue(this.data.longitude);
            this.longitude.resetOriginalValue();
            this.latitude.setRawValue(this.data.latitude);
            this.latitude.resetOriginalValue();
        }


        if(this.isMapsAvailable()) {
            this.longitude.on('keyup', this.updatePreviewImage.bind(this));
            this.latitude.on('keyup', this.updatePreviewImage.bind(this));

            this.component = new Ext.Panel({
                title: this.fieldConfig.title,
                border: true,
                style: "margin-bottom: 10px",
                height: 370,
                width: 650,
                componentCls: "object_field object_geo_field",
                html: '<div id="google_maps_container_' + this.mapImageID + '" align="center"></div>',
                bbar: [
                    t('latitude'),
                    this.latitude,
                    '-',
                    t('longitude'),
                    this.longitude,
                    '-', {
                        xtype: 'button',
                        text: t('empty'),
                        iconCls: "pimcore_icon_empty",
                        handler: function () {
                            this.latitude.setValue(null);
                            this.longitude.setValue(null);
                            this.updatePreviewImage();
                        }.bind(this)
                    }, '->', {
                        xtype: 'button',
                        text: t('open_search_editor'),
                        iconCls: "pimcore_icon_search",
                        handler: this.openPicker.bind(this)
                    }
                ]
            });

            this.component.on('afterrender', function () {
                this.updatePreviewImage();
            }.bind(this));
        } else {

            this.longitude.setFieldLabel(t("longitude"));
            this.latitude.setFieldLabel(t("latitude"));
            this.longitude.setWidth(350);
            this.latitude.setWidth(350);

            this.component = new Ext.Panel({
                title: this.fieldConfig.title,
                border: true,
                style: "margin-bottom: 10px",
                bodyStyle: "padding: 10px;",
                height: 370,
                width: 650,
                componentCls: "object_field object_geo_field",
                items: [this.latitude, this.longitude]
            });
        }

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

    getMapUrl: function (fieldConfig, data, width, height) {

        // static maps api image url
        var mapZoom = fieldConfig.zoom;
        var mapUrl;

        if (!width) {
            width = 300;
        }

        if(!height) {
            height = 300;
        }

        var py = height;
        var px = width;

        // static maps api image url
        var lat = fieldConfig.lat;
        var lng = fieldConfig.lng;
        if (data) {
            lat = data.latitude;
            lng = data.longitude;
            mapZoom = 15;

            mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center='
                + lat + "," + lng + "&zoom=" + mapZoom +
                '&size=' + px + 'x' + py
                + '&markers=color:red|' + lat + ',' + lng
                + '&maptype=' + fieldConfig.mapType;
        } else {
            mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center='
                + lat + "," + lng + "&zoom=" + mapZoom +
                '&size=' + px + 'x' + py
                + '&maptype=' + fieldConfig.mapType;
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
                iconCls: "pimcore_icon_search",
                handler: this.geocode.bind(this)
            }, '->', {
                xtype: 'button',
                text: t('cancel'),
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    this.searchWindow.close();
                }.bind(this)
            },{
                xtype: 'button',
                text: 'OK',
                iconCls: "pimcore_icon_save",
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

        this.alreadyIntitialized = false;

        this.searchWindow.on('afterlayout', function () {
            if (this.alreadyIntitialized) {
                return;
            }

            this.alreadyIntitialized = true;

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

        // no render check is necessary because the input component returns the right values even if it is not
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

        if(this.longitude && this.latitude) {
            return this.longitude.isDirty() || this.latitude.isDirty();
        }

        return false;
    },

    getGridColumnConfig: function(field) {
        return {
            header: ts(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            getEditor:this.getCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += ' grid_value_inherited';
                }

                if (value) {
                    var width = 200;

                    var mapUrl = this.getMapUrl(field, value, width, 100);

                    return '<img src="' + mapUrl + '" />';
                }
            }.bind(this, field.key)
        };
    },

    getCellEditor: function ( field, record) {
        return new pimcore.object.helpers.gridCellEditor({
            fieldInfo: field
        });
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});
