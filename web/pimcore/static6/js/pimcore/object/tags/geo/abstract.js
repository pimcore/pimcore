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
    },

    getErrorLayout: function() {
        this.component = new Ext.Panel({
            title: t("geo_error_title"),
            height: 370,
            width: 650,
            border: true,
            bodyStyle: "padding: 10px",
            html: '<span style="color:red">' + t("geo_error_message") + '</span>'
        });

        return this.component;
    },

    getGridColumnConfig: function(field) {
        return {
            text: ts(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += ' grid_value_inherited';
                }
               // if (value) {
                    return ts('preview_not_available');
               // }
            }.bind(this, field.key)
        };
    },

    getLayoutShow: function () {
        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    updateMap: function () {
        this.getMapUrl(this.fieldConfig, this.data);
    
    },
    
    getLeafletMap: function() {
        document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = '<div id="map'+ this.divImageID +'" style="height:400px;width:650px;"></div>';
        this.leafletMap =  L.map('map' +this.divImageID).setView([this.lat, this.lng], this.mapZoom);
        L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(this.leafletMap);
    }

});
