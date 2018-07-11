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
pimcore.registerNS('pimcore.object.tags.geopolygon');
pimcore.object.tags.geopolygon = Class.create(pimcore.object.tags.geo.abstract, {

    type: 'geopolygon',
    dirty: false,

    getLayoutEdit: function () {

        this.mapImageID = uniqid();

        this.component = new Ext.Panel({
            title: this.fieldConfig.title,
            height: 370,
            width: 650,
            border: true,
            style: "margin-bottom: 10px",
            componentCls: 'object_field object_geo_field',
            html: '<div id="leaflet_maps_container_' + this.mapImageID + '"></div>',
            bbar: [{
                xtype: 'button',
                text: t('empty'),
                iconCls: "pimcore_icon_empty",
                handler: function () {
                    this.data = null;
                    this.updateMap();
                    this.dirty = true;
                }.bind(this)
            }, '->', {
                xtype: 'button',
                text: t('open_select_editor'),
                iconCls: "pimcore_icon_search",
                handler: this.openPicker.bind(this)
            }]
        });

        this.component.on('afterrender', function () {
            this.updateMap();
        }.bind(this));

        return this.component;
    },

    getMapUrl: function (fieldConfig, data, width, height) {
        var mapZoom = fieldConfig.zoom;
        var lat = fieldConfig.lat;
        var lng = fieldConfig.lng;
        this.data = null;
        this.latlngs = [];
        var Map;
        var editableLayers = new L.FeatureGroup();
        var drawControlFull = new L.Control.Draw({
                 position: 'topright',
                draw: {
                    circle: false,
                    marker: false,
                    circlemarker: false,
                    rectangle: false,
                    polyline: false
                },
                edit: {
                    featureGroup: editableLayers, //REQUIRED!!
                    remove: true
                }
            });

            var drawControlEditOnly = new L.Control.Draw({
                edit: {
                    featureGroup: editableLayers
                },
                draw: false
        });

        try {
            if(data) {

                document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = "<div id='polygonmap' style='height:400px;width:650px;'></div>";
                Map = L.map('polygonmap').setView([lat, lng], mapZoom);
                L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(Map);

                for (var i = 0; i < data.length; i++) {
                    this.latlngs.push([data[i].latitude,data[i].longitude]);
                }

                var polygon = L.polygon(this.latlngs, {color: '0x00000073'}).addTo(Map);
                Map.fitBounds(polygon.getBounds());

            } else {
 
                document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = "<div id='polygonmap' style='height:400px;width:650px;'></div>";
                Map = L.map('polygonmap').setView([lat, lng], mapZoom);
                L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(Map);
            }
            Map.addLayer(editableLayers);
            Map.addControl(drawControlFull);
            Map.on(L.Draw.Event.CREATED, function (e) {
                this.dirty = true;
                Map.removeLayer(polygon);
                layer = e.layer;
                type = e.layerType;
                editableLayers.addLayer(layer);
                if (editableLayers.getLayers().length === 1) {
                    this.data = [];
                    drawControlFull.remove(Map);
                    drawControlEditOnly.addTo(Map);
                    latlngs = layer.getLatLngs();
                    for (var i=0; i< latlngs[0].length; i++) {
                            this.data.push({
                                latitude: latlngs[0][i].lat,
                                longitude: latlngs[0][i].lng
                            });
                        }
                }
            }.bind(this));

            Map.on("draw:deleted", function(e) {
                drawControlEditOnly.remove(Map);
                drawControlFull.addTo(Map);
            });
        }
        catch (e) {
            console.log(e);
        }
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
                iconCls: "pimcore_icon_empty",
                handler: this.removePolygon.bind(this)
            }],
            bbar: [this.searchfield, {
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

        this.alreadyIntitialized = false;

        this.searchWindow.on('afterlayout', function () {
            if (this.alreadyIntitialized) {
                return;
            }

            this.alreadyIntitialized = true;

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

