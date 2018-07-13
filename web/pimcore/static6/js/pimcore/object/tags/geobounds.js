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
pimcore.registerNS('pimcore.object.tags.geobounds');
pimcore.object.tags.geobounds = Class.create(pimcore.object.tags.geo.abstract, {

    type: 'geobounds',

    dirty: false,

    getLayoutEdit: function () {

        this.mapImageID = uniqid();
        this.divImageID = uniqid();

        this.component = new Ext.Panel({
            title: this.fieldConfig.title,
            border: true,
            style: "margin-bottom: 10px",
            height: 370,
            width: 650,
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

    getMapUrl: function (fieldConfig, data) {
        
        var mapZoom = fieldConfig.zoom;
        var lat = fieldConfig.lat;
        var lng = fieldConfig.lng;
        var leafletMap;
        this.rectangle = null;
        var editableLayers = new L.FeatureGroup();
        var drawControlFull = new L.Control.Draw({
            position: 'topright',
            draw: {
                polyline: false,
                polygon: false,
                circle: false,
                marker: false,
                circlemarker: false
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
                mapZoom = 15;
                document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = '<div id="boundmap'+ this.divImageID +'" style="height:400px;width:650px;"></div>';
                leafletMap =  L.map('boundmap'+ this.divImageID).setView([data.SWlatitude, data.SWlongitude], mapZoom);
                L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetleafletMap</a> contributors'
                }).addTo(leafletMap);

                var bounds = [[data.NElatitude, data.NElongitude], [data.SWlatitude, data.SWlongitude]];
                this.rectangle = L.rectangle(bounds, {color: "0x00000073", weight: 1}).addTo(leafletMap);
                leafletMap.fitBounds(bounds);

            } else {
                document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = '<div id="boundmap'+ this.divImageID +'" style="height:400px;width:650px;"></div>';
                leafletMap = L.map('boundmap'+this.divImageID).setView([lat, lng], mapZoom);
                L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetleafletMap</a> contributors'
                }).addTo(leafletMap);
            }
            leafletMap.addLayer(editableLayers);
            leafletMap.addControl(drawControlFull);
            leafletMap.on(L.Draw.Event.CREATED, function (e) {
                this.dirty = true;
                if(this.rectangle !== null) {
                    leafletMap.removeLayer(this.rectangle);
                }
                var layer = e.layer;
                editableLayers.addLayer(layer);
                if (editableLayers.getLayers().length === 1) {
                    drawControlFull.remove(leafletMap);
                    drawControlEditOnly.addTo(leafletMap);
                   this.data = {
                        ne: layer.getBounds().getNorthEast(),
                        sw: layer.getBounds().getSouthWest()
                    };
                }
            }.bind(this));

            leafletMap.on("draw:deleted", function (e) {
                drawControlEditOnly.remove(leafletMap);
                drawControlFull.addTo(leafletMap);
            });
        } catch (e) {
            console.log(e);
        }
    },

    getValue: function () {
        if (this.data) {

           return {
                NElatitude: this.data.ne.lat,
                NElongitude: this.data.ne.lng,
                SWlatitude: this.data.sw.lat,
               SWlongitude: this.data.sw.lng
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

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }

});
