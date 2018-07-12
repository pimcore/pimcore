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
        this.divImageID = uniqid();

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
        var leafletMap;
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
            position: 'topright',
            edit: {
                featureGroup: editableLayers
            },
            draw: false
        });

        try {
            if(data) {
                document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML ='<div id="polygonmap'+ this.divImageID +'" style="height:400px;width:650px;"></div>';
                leafletMap = L.map('polygonmap'+ this.divImageID).setView([lat, lng], mapZoom);
                L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetleafletMap</a> contributors'
                }).addTo(leafletMap);

                for (var i = 0; i < data.length; i++) {
                    this.latlngs.push([data[i].latitude,data[i].longitude]);
                }
                var polygon = L.polygon(this.latlngs, {color: '0x00000073'}).addTo(leafletMap);
                leafletMap.fitBounds(polygon.getBounds());

            } else {
                document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = '<div id="polygonmap'+ this.divImageID +'" style="height:400px;width:650px;"></div>';
                leafletMap = L.map('polygonmap'+this.divImageID).setView([lat, lng], mapZoom);
                L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetleafletMap</a> contributors'
                }).addTo(leafletMap);
            }
            leafletMap.addLayer(editableLayers);
            leafletMap.addControl(drawControlFull);
            leafletMap.on(L.Draw.Event.CREATED, function (e) {
                this.dirty = true;
                if(polygon === null) {
                    leafletMap.removeLayer(polygon);
                }
                var layer = e.layer;
                type = e.layerType;
                editableLayers.addLayer(layer);
                if (editableLayers.getLayers().length === 1) {
                    this.data = [];
                    drawControlFull.remove(leafletMap);
                    drawControlEditOnly.addTo(leafletMap);
                    latlngs = layer.getLatLngs();
                    for (var i=0; i< latlngs[0].length; i++) {
                            this.data.push({
                                latitude: latlngs[0][i].lat,
                                longitude: latlngs[0][i].lng
                            });
                        }
                }
            }.bind(this));

            leafletMap.on("draw:deleted", function(e) {
                drawControlEditOnly.remove(leafletMap);
                drawControlFull.addTo(leafletMap);
            });
        }
        catch (e) {
            console.log(e);
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

