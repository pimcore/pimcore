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
        this.searchfield = new Ext.form.TextField({
            width: 200,
            name: 'mapSearch',
            style: 'float:left;margin-top:0px;'
        });

        this.component = new Ext.Panel({
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

    getMap: function (fieldConfig, data, width, height) {
        this.mapZoom = fieldConfig.zoom;
        this.leafletMap = null;
        this.rectangle = null;
        this.mapId = "boundmap" + this.divImageID;
        this.editableLayers = new L.FeatureGroup();
        this.drawControlFull = new L.Control.Draw({
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
                remove: true
            }
        });
        this.drawControlEditOnly = new L.Control.Draw({
            position: 'topright',
            edit: {
                featureGroup: this.editableLayers
            },
            draw: false
        });
        if (!width) {
            width = 300;
        }
        if (!height) {
            height = 300;
        }

        var py = height;
        var px = width;
        try {
            if (data) {
                var bounds = L.latLngBounds(L.latLng(data.NElatitude, data.NElongitude), L.latLng(data.SWlatitude, data.SWlongitude));
                this.lat = bounds.getCenter().lat;
                this.lng = bounds.getCenter().lng;
                this.mapZoom = this.getBoundsZoomLevel(bounds, {width: px, height: py});
                this.getLeafletMap();
                this.rectangle = L.rectangle(bounds, {color: "0x00000073", weight: 1}).addTo(this.leafletMap);
                this.leafletMap.fitBounds(bounds);

            } else {
                this.lat = fieldConfig.lat;
                this.lng = fieldConfig.lng;
                this.getLeafletMap();
            }
            this.getLeafletToolbar();
        } catch (e) {
            console.log(e);
        }
    },

    getLeafletToolbar: function () {
        this.leafletMap.addLayer(this.editableLayers);
        this.leafletMap.addControl(this.drawControlFull);
        this.leafletMap.on(L.Draw.Event.CREATED, function (e) {
            this.dirty = true;
            this.leafletMap.addLayer(this.editableLayers);
            this.leafletMap.addControl(this.drawControlFull);
            this.leafletMap.on(L.Draw.Event.CREATED, function (e) {
                this.dirty = true;
                if (this.rectangle !== null) {
                    this.leafletMap.removeLayer(this.rectangle);
                }
                var layer = e.layer;
                this.editableLayers.addLayer(layer);
                if (this.editableLayers.getLayers().length === 1) {
                    this.drawControlFull.remove(this.leafletMap);
                    this.drawControlEditOnly.addTo(this.leafletMap);
                    this.data = {
                        ne: layer.getBounds().getNorthEast(),
                        sw: layer.getBounds().getSouthWest()
                    };
                }
            }.bind(this));

            this.leafletMap.on("draw:deleted", function (e) {
                this.drawControlEditOnly.remove(this.leafletMap);
                this.drawControlFull.addTo(this.leafletMap);
            });
            if (this.rectangle !== null) {
                this.leafletMap.removeLayer(this.rectangle);
            }
            var layer = e.layer;
            this.editableLayers.addLayer(layer);
            if (this.editableLayers.getLayers().length === 1) {
                this.drawControlFull.remove(this.leafletMap);
                this.drawControlEditOnly.addTo(this.leafletMap);
                this.data = {
                    ne: layer.getBounds().getNorthEast(),
                    sw: layer.getBounds().getSouthWest()
                };
            }
        }.bind(this));

        this.leafletMap.on("draw:deleted", function (e) {
            this.drawControlEditOnly.remove(this.leafletMap);
            this.drawControlFull.addTo(this.leafletMap);
        });
    },
    
    geocode: function () {
        var address = this.searchfield.getValue();
        jQuery.getJSON(this.getSearchUrl(address), function(json) {
          if( json[0].lat !== null && json[0].lon !== null) {
                this.lat = json[0].lat;
                this.lng = json[0].lon;
                this.getLeafletMap();   
                this.getLeafletToolbar();
            }
        }.bind(this));
       
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
