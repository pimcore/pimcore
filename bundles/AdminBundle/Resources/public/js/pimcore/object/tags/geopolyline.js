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
pimcore.registerNS('pimcore.object.tags.geopolyline');
pimcore.object.tags.geopolyline = Class.create(pimcore.object.tags.geo.abstract, {

    type: 'geopolyline',

    dirty: false,

    getLayoutEdit: function () {

        this.mapImageID = uniqid();
        this.divImageID = uniqid();
        this.mapId = "linemap" + this.divImageID;

        this.searchfield = new Ext.form.TextField({
            width: 200,
            name: 'mapSearch',
            style: 'float:left;margin-top:0px;',
            listeners: {
                render: function (cmp) {
                    cmp.getEl().on('keypress', function (e) {
                        if (e.getKey() === e.ENTER) {
                            this.geocode();
                        }
                    }.bind(this));
                }.bind(this)
            }
        });

        this.component = new Ext.Panel({
            border: true,
            style: "margin-bottom: 10px",
            height: this.fieldConfig.height,
            width: this.fieldConfig.width,
            componentCls: 'object_field object_geo_field object_field_type_' + this.type,
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
            }],
            tbar: [
                this.fieldConfig.title,
                "->",
                this.searchfield,
                {
                    xtype: 'button',
                    iconCls: "pimcore_icon_search",
                    handler: this.geocode.bind(this)
                }
            ]
        });

        this.component.on('afterrender', function () {
            this.updateMap();
        }.bind(this));

        return this.component;
    },

    getMap: function (fieldConfig, data) {
        this.polyline = null;
        this.latlngs = [];

        this.editableLayers = new L.FeatureGroup();

        try {
            var leafletMap = this.getLeafletMap(
                fieldConfig.lat,
                fieldConfig.lng,
                fieldConfig.zoom
            );
            if (data) {
                for (var i = 0; i < data.length; i++) {
                    this.latlngs.push([data[i].latitude, data[i].longitude]);
                }

                this.polyline = L.polyline(this.latlngs, {stroke: true, color: "#3388ff", opacity: 0.5, fillOpacity: 0.2, weight: 4});

                leafletMap.addLayer(this.polyline);
                leafletMap.fitBounds(this.polyline.getBounds());
                this.editableLayers.addLayer(this.polyline);
            }
            this.getLeafletToolbar(leafletMap);
        } catch (e) {
            console.log(e);
        }
    },

    getLeafletToolbar: function (leafletMap) {
        leafletMap.addLayer(this.editableLayers);

        var drawControlFull = new L.Control.Draw({
            position: 'topright',
            draw: {
                rectangle: false,
                polygon: false,
                circle: false,
                marker: false,
                circlemarker: false
            },
            edit: {
                featureGroup: this.editableLayers,
                remove: false
            }
        });
        leafletMap.addControl(drawControlFull);

        leafletMap.on(L.Draw.Event.CREATED, function (e) {
            this.dirty = true;

            this.editableLayers.clearLayers();
            if (this.polyline !== null) {
                this.polyline.remove();
            }

            var layer = e.layer;
            this.editableLayers.addLayer(layer);
            if (this.editableLayers.getLayers().length === 1) {
                this.data = [];
                var latlngs = layer.getLatLngs();
                for (var i = 0; i < latlngs.length; i++) {
                    this.data.push({
                        latitude: latlngs[i].lat,
                        longitude: latlngs[i].lng
                    });
                }
            }
        }.bind(this));

        leafletMap.on(L.Draw.Event.DELETED, function (e) {
            this.data = null;
            this.dirty = true;
            this.updateMap();
        }.bind(this));

        leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
            this.dirty = true;

            var layer1;
            var newPolyLatLngArray;
            this.data = [];
            for (layer1 in e.target._layers) {
                if (e.target._layers.hasOwnProperty(layer1)) {
                    if (e.target._layers[layer1].hasOwnProperty("edited")) {
                        newPolyLatLngArray = e.target._layers[layer1].editing.latlngs[0];
                    }
                }
            }
            for (var i = 0; i < newPolyLatLngArray.length; i++) {
                this.data.push({
                    latitude: newPolyLatLngArray[i].lat,
                    longitude: newPolyLatLngArray[i].lng
                });
            }

        }.bind(this));
    },

    getValue: function () {
        return this.data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        return this.dirty;
    }
});
