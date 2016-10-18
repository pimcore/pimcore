/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */
/*global google */
pimcore.registerNS('pimcore.object.tags.geobounds');
pimcore.object.tags.geobounds = Class.create(pimcore.object.tags.geo.abstract, {

    type: 'geobounds',

    dirty: false,

    getLayoutEdit: function () {

        if(!this.isMapsAvailable()) {
            return this.getErrorLayout();
        }

        this.mapImageID = uniqid();

        if (this.data) {
            this.data.ne = new google.maps.LatLng(this.data.NElatitude,this.data.NElongitude);
            this.data.sw = new google.maps.LatLng(this.data.SWlatitude,this.data.SWlongitude);
        }

        this.component = new Ext.Panel({
            title: this.fieldConfig.title,
            border: true,
            style: "margin-bottom: 10px",
            height: 370,
            width: 650,
            componentCls: 'object_field object_geo_field',
            html: '<div id="google_maps_container_' + this.mapImageID + '" align="center"></div>',
            bbar: [{
                xtype: 'button',
                text: t('empty'),
                iconCls: "pimcore_icon_empty",
                handler: function () {
                    this.data = null;
                    this.updatePreviewImage();
                    this.dirty = true;
                }.bind(this)
            },"->",{
                xtype: "button",
                text: t("open_select_editor"),
                iconCls: "pimcore_icon_search",
                handler: this.openPicker.bind(this)
            }]
        });

        this.component.on('afterrender', function () {
            this.updatePreviewImage();
        }.bind(this));

        return this.component;
    },

    getMapUrl: function (fieldConfig, data, width, height) {
        if(data && !data.ne) {
            data.ne = new google.maps.LatLng(data.NElatitude,data.NElongitude);
            data.sw = new google.maps.LatLng(data.SWlatitude,data.SWlongitude);
        }

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

        try {
            if (data) {

                var bounds = new google.maps.LatLngBounds(data.sw, data.ne);
                var center = bounds.getCenter();

                mapZoom = this.getBoundsZoomLevel(bounds, {width: px, height: py});

                var path = 'weight:0|fillcolor:0x00000073|' + data.ne.lat() + ',' + data.ne.lng()
                    + '|' + data.sw.lat() + ',' + data.ne.lng() + '|'
                    + data.sw.lat() + ',' + data.sw.lng() + '|' + data.ne.lat()
                    + ',' + data.sw.lng() + '|' + data.ne.lat() + ','
                    + data.ne.lng();
                mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center=' + center.y + ','
                    + center.x + '&zoom=' + mapZoom + '&size=' + px + 'x' + py
                    + '&path=' + path + '&maptype=' + fieldConfig.mapType;
            }
            else {
                mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center='
                    + fieldConfig.lat + ',' + fieldConfig.lng
                    + '&zoom=' + mapZoom + '&size='
                    + px + 'x' + py + '&maptype=' + fieldConfig.mapType;
            }

            if (pimcore.settings.google_maps_api_key) {
                mapUrl += '&key=' + pimcore.settings.google_maps_api_key;
            }
        }
        catch (e) {
            console.log(e);
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

        this.mapPanel = new Ext.Panel({
            plain: true
        });

        this.searchWindow = new Ext.Window({
            modal: true,
            width: 600,
            height: 500,
            resizable: false,
            tbar: [{
                xtype: 'button',
                text: t('empty'),
                iconCls: "pimcore_icon_empty",
                handler: this.removeOverlay.bind(this)
            }],
            bbar: [this.searchfield, {
                xtype: 'button',
                text: t('search'),
                iconCls: "pimcore_icon_search",
                handler: this.geocode.bind(this)
            },"->",{
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

                    this.data = null;

                    if (this.overlay) {
                        this.data = {
                            ne: this.overlay.getBounds().getNorthEast(),
                            sw: this.overlay.getBounds().getSouthWest()
                        };
                    }

                    this.updatePreviewImage();
                    this.dirty = true;
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
                rectangleOptions: {
                    strokeWeight: 0,
                    fillOpacity: 0.45,
                    editable: true,
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
                this.gmap.fitBounds(this.overlay.getBounds());
            } else {
                this.drawingManager.setDrawingMode(google.maps.drawing.OverlayType.RECTANGLE);
            }

            this.geocoder = new google.maps.Geocoder();

        }.bind(this));

        this.searchWindow.on('beforeclose', function () {
            delete this.overlay;
            delete this.gmap;
            delete this.geocoder;
        }.bind(this));

        this.searchWindow.show();
    },

    renderOverlay: function () {
        this.overlay = new google.maps.Rectangle({
            bounds: new google.maps.LatLngBounds(this.data.sw, this.data.ne),
            strokeWeight: 0,
            fillOpacity: 0.45,
            editable: true,
            draggable: true,
            map: this.gmap
        });
    },

    removeOverlay: function() {
        if (this.overlay) {
            this.overlay.setMap(null);
            delete this.overlay;
        }
        this.drawingManager.setDrawingMode(google.maps.drawing.OverlayType.RECTANGLE);
    },

    getValue: function () {
        if (this.data) {
            return {
                NElatitude: this.data.ne.lat(),
                NElongitude: this.data.ne.lng(),
                SWlatitude: this.data.sw.lat(),
                SWlongitude: this.data.sw.lng()
            };
        }

        return null;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {
        var value = this.getValue();

        // @TODO
        /*if (value.longitude && value.latitude) {
            return false;
        }*/

        return true;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }

});
