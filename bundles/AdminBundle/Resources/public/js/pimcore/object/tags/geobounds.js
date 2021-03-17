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
        this.mapId = "boundmap" + this.divImageID;

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
        this.rectangle = null;

        this.editableLayers = new L.FeatureGroup();


        try {
            var leafletMap = null;
            if (data) {
                var bounds = L.latLngBounds(L.latLng(data.NElatitude, data.NElongitude), L.latLng(data.SWlatitude, data.SWlongitude));

                leafletMap = this.getLeafletMap(
                    bounds.getCenter().lat,
                    bounds.getCenter().lng,
                    fieldConfig.zoom
                );

                this.rectangle = L.rectangle(bounds, {stroke: true, color: "#3388ff", opacity: 0.5, fillOpacity: 0.2, weight: 4});
                leafletMap.addLayer(this.rectangle);
                leafletMap.fitBounds(bounds);
                this.editableLayers.addLayer(this.rectangle);

            } else {
                leafletMap = this.getLeafletMap(
                    fieldConfig.lat,
                    fieldConfig.lng,
                    fieldConfig.zoom
                );
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
                polyline: false,
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
            if (this.rectangle !== null) {
                this.rectangle.remove();
            }

            var layer = e.layer;
            this.editableLayers.addLayer(layer);
            if (this.editableLayers.getLayers().length === 1) {
                let ne = layer.getBounds().getNorthEast();
                let sw = layer.getBounds().getSouthWest();
                this.data = {
                    NElatitude: ne.lat,
                    NElongitude: ne.lng,
                    SWlatitude: sw.lat,
                    SWlongitude: sw.lng
                };
            }
        }.bind(this));

        leafletMap.on("draw:deleted", function (e) {
            this.data = null;
            this.dirty = true;
            this.updateMap();
        }.bind(this));

        leafletMap.on("draw:editresize draw:editmove", function (e) {
            this.dirty = true;
            let ne = e.layer.getBounds().getNorthEast();
            let sw = e.layer.getBounds().getSouthWest();
            this.data = {
                NElatitude: ne.lat,
                NElongitude: ne.lng,
                SWlatitude: sw.lat,
                SWlongitude: sw.lng
            };
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

