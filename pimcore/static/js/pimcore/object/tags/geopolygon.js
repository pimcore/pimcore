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
pimcore.registerNS('pimcore.object.tags.geopolygon');
pimcore.object.tags.geopolygon = Class.create(pimcore.object.tags.geo.abstract, {

    type: 'geopolygon',
    dirty: false,

    getLayoutEdit: function () {

        this.mapImageID = uniqid();

        this.component = new Ext.Panel({
            title: this.fieldConfig.title,
            height: 370,
            width: 490,
            cls: 'object_field',
            html: '<div id="google_maps_container_' + this.mapImageID + '" align="center">'
                        + '<img align="center" width="300" height="300" src="' + this.getMapUrl() + '" /></div>',
            bbar: [{
                xtype: 'button',
                text: t('empty'),
                icon: '/pimcore/static/img/icon/bin.png',
                handler: function () {
                    this.data = null;
                    this.updatePreviewImage();
                    this.dirty = true;
                }.bind(this)
            }, '->', {
                xtype: 'button',
                text: t('open_select_editor'),
                icon: '/pimcore/static/img/icon/magnifier.png',
                handler: this.openPicker.bind(this)
            }]
        });

        this.component.on('afterrender', function () {
            this.updatePreviewImage();
        }.bind(this));

        return this.component;
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

        try {
            if (this.data) {

                var pointConfig = [];

                var bounds = new google.maps.LatLngBounds();

                for (var i = 0; i < this.data.length; i++) {
                    bounds.extend(new google.maps.LatLng(this.data[i].latitude, this.data[i].longitude));
                    pointConfig.push(this.data[i].latitude + "," + this.data[i].longitude);
                }

                // add startpoint also as endpoint
                pointConfig.push(this.data[0].latitude + "," + this.data[0].longitude);

                var center = bounds.getCenter();
                mapZoom = this.getBoundsZoomLevel(bounds, {width: px, height: py});

                var path = 'weight:0|fillcolor:0x00000073|' + pointConfig.join('|');
                mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center=' + center.lat() + ','
                    + center.lng() + '&zoom=' + mapZoom + '&size=' + px + 'x' + py
                    + '&path=' + path + '&sensor=false&maptype=' + this.fieldConfig.mapType;
            }
            else {
                mapUrl = 'https://maps.googleapis.com/maps/api/staticmap?center='
                    + this.fieldConfig.lat + ',' + this.fieldConfig.lng
                    + '&zoom=' + mapZoom + '&size='
                    + px + 'x' + py + '&sensor=false&maptype=' + this.fieldConfig.mapType;
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

        this.polygon;

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
                icon: '/pimcore/static/img/icon/bin.png',
                handler: this.removePolygon.bind(this)
            }],
            bbar: [this.searchfield, {
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

                    this.data = null;

                    if (this.polygon) {
                        var points = this.polygon.getPath();

                        if(points.length > 0) {
                            this.data = [];
                        }

                        for (var i=0; i<points.length; i++) {
                            this.data.push({
                                latitude: points.getAt(i).lat(),
                                longitude: points.getAt(i).lng()
                            });
                        }
                    }

                    this.updatePreviewImage();
                    this.dirty = true;
                    this.searchWindow.close();
                }.bind(this)
            }],
            plain: true
        });

        this.searchWindow.on('afterrender', function () {

            var center = new google.maps.LatLng(this.fieldConfig.lat, this.fieldConfig.lng);
            var mapZoom = this.fieldConfig.zoom;
            this.polygonPoints = new google.maps.MVCArray();

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
                polygonOptions: {
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

                this.polygon = e.overlay;
                this.bindPolygonEvent();
            }.bind(this));

            if (this.data) {
                for (var i = 0; i < this.data.length; i++) {
                    this.polygonPoints.push(new google.maps.LatLng(this.data[i].latitude, this.data[i].longitude));
                }
            }

            if(this.polygonPoints.length > 0) {
                this.renderPolygon();
            } else {
                this.drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
            }

            if(this.data) {
                this.gmap.fitBounds(this.polygon.getBounds());
                this.gmap.setCenter(this.polygon.getBounds().getCenter());
            }

            this.geocoder = new google.maps.Geocoder();
        }.bind(this));

        this.searchWindow.on('beforeclose', function () {
            delete this.polygon;
            delete this.gmap;
            delete this.geocoder;
        }.bind(this));

        this.searchWindow.show();
    },

    renderPolygon: function () {

        this.polygon = new google.maps.Polygon({
            paths: this.polygonPoints,
            strokeWeight: 0,
            fillOpacity: 0.45,
            editable: true,
            draggable: true,
            map: this.gmap
        });
        this.bindPolygonEvent();
    },

    removePolygon: function() {
        if (this.polygon) {
            this.polygon.setMap(null);
            delete this.polygon;
        }
        this.drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
    },

    bindPolygonEvent: function () {
        google.maps.event.addListener(this.polygon, 'click', function (e) {
            if (e.vertex !== undefined) {
                var path = this.polygon.getPaths().getAt(e.path);
                path.removeAt(e.vertex);
                if (path.length < 3) {
                    this.removePolygon();
                }
            }
        }.bind(this));
    },

    addPoint : function (event) {
        this.polygonPoints.insertAt(this.polygonPoints.length, event.latLng);

        if(this.polygonPoints.length == 1) {
            this.renderPolygon();
        }
    },

    getValue: function () {
        return this.data;
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

