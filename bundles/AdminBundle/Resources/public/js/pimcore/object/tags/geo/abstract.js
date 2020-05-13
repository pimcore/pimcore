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
pimcore.registerNS('pimcore.object.tags.geo.abstract');
pimcore.object.tags.geo.abstract = Class.create(pimcore.object.tags.abstract, {

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

        if (intval(this.fieldConfig.width) < 1) {
            this.fieldConfig.width = 650;
        }
        if (intval(this.fieldConfig.height) < 1) {
            this.fieldConfig.height = 370;
        }
    },

    getGridColumnConfig: function(field) {
        return {
            text: t(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += ' grid_value_inherited';
                }
                if (value) {
                    return t('preview_not_available');
                }
            }.bind(this, field.key)
        };
    },

    getLayoutShow: function () {
        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    updateMap: function () {
        this.getMap(this.fieldConfig, this.data);
    },
    
    getLeafletMap: function(lat, lng, mapZoom) {
        document.getElementById('leaflet_maps_container_' + this.mapImageID)
            .innerHTML = '<div id="'+ this.mapId +'" style="height:' + (this.fieldConfig.height - 74) + 'px;width:' + this.fieldConfig.width + 'px;"></div>';

        var leafletMap =  L.map(this.mapId).setView([lat, lng], mapZoom);
        L.tileLayer(pimcore.settings.tile_layer_url_template, {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(leafletMap);

        return leafletMap;
    },

    getBoundsZoomLevel: function (bounds, mapDim) {
        var WORLD_DIM = { height: 256, width: 256 };
        var ZOOM_MAX = 21;

        function latRad(lat) {
            var sin = Math.sin(lat * Math.PI / 180);
            var radX2 = Math.log((1 + sin) / (1 - sin)) / 2;
            return Math.max(Math.min(radX2, Math.PI), -Math.PI) / 2;
        }

        function zoom(mapPx, worldPx, fraction) {
            return Math.floor(Math.log(mapPx / worldPx / fraction) / Math.LN2);
        }
        var ne = bounds.getNorthEast();
        var sw = bounds.getSouthWest(); 
        var latFraction = (latRad(ne.lat) - latRad(sw.lat)) / Math.PI;

        var lngDiff = ne.lng - sw.lng;
        var lngFraction = ((lngDiff < 0) ? (lngDiff + 360) : lngDiff) / 360;

        var latZoom = zoom(mapDim.height, WORLD_DIM.height, latFraction);
        var lngZoom = zoom(mapDim.width, WORLD_DIM.width, lngFraction);

        return Math.min(latZoom, lngZoom, ZOOM_MAX);
    },

    getSearchUrl: function (query) {
        var url = pimcore.settings.geocoding_url_template.replace('{q}', urlencode(query));
        return url;
    },

    geocode: function () {
        var address = this.searchfield.getValue();
        Ext.Ajax.request({
            url: this.getSearchUrl(address),
            method: "GET",
            success: function (response, opts) {
                var data = Ext.decode(response.responseText);
                if( data[0].lat !== null && data[0].lon !== null) {
                    var map = this.getLeafletMap(data[0].lat, data[0].lon, 15);
                    this.getLeafletToolbar(map);
                }
            }.bind(this),
        });
    }
});
