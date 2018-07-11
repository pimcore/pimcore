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
pimcore.registerNS('pimcore.object.tags.geopoint');
pimcore.object.tags.geopoint = Class.create(pimcore.object.tags.geo.abstract, {


    type: 'geopoint',

    getLayoutEdit: function () {

        this.mapImageID = uniqid();

        var coordConf = {
            decimalPrecision: 15,
            enableKeyEvents: true,
            width: 95
        };

        this.longitude = new Ext.form.NumberField(coordConf);
        this.latitude = new Ext.form.NumberField(coordConf);

        if (this.data) {
            //set raw values to stop values being initially dirty
            this.longitude.setRawValue(this.data.longitude);
            this.longitude.resetOriginalValue();
            this.latitude.setRawValue(this.data.latitude);
            this.latitude.resetOriginalValue();
        }

        this.longitude.on('keyup', this.updateMap.bind(this));
        this.latitude.on('keyup', this.updateMap.bind(this));

        this.component = new Ext.Panel({
            title: this.fieldConfig.title,
            border: true,
            style: "margin-bottom: 10px",
            height: 370,
            width: 650,
            componentCls: "object_field object_geo_field",
            html: '<div id="leaflet_maps_container_' + this.mapImageID + '"></div>',
            bbar: [
                  t('latitude'),
                    this.latitude,
                    '-',
                    t('longitude'),
                    this.longitude,
                     '-', {
                        xtype: 'button',
                        text: t('empty'),
                        iconCls: "pimcore_icon_empty",
                        handler: function () {
                            this.latitude.setValue(null);
                            this.longitude.setValue(null);
                            this.updateMap();
                        }.bind(this)
                    },
            ]
        });

        this.component.on('afterrender', function () {
            this.updateMap();
        }.bind(this));

        return this.component;
    },

    updateMap: function ($super) {
        var data = this.getValue();
        if (data.latitude && data.longitude) {
            this.data = data;
        } else {
            this.data = null;
        }
        $super();
    },

    getMapUrl: function (fieldConfig, data) {
        var mapZoom = fieldConfig.zoom;
        var lat = fieldConfig.lat;
        var lng = fieldConfig.lng;
        var Map;
        var myIcon = L.icon({
                iconUrl: '/pimcore/static6/img/marker-icon.png'
            });

        var editableLayers = new L.FeatureGroup();
        var drawControlFull = new L.Control.Draw({
            position: 'topright',
            draw: {
                polyline: false,
                polygon: false,
                circle: false,
                rectangle: false,
                circlemarker: false
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

        if (data) {
            lat = data.latitude;
            lng = data.longitude;
            mapZoom = 15;
            document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = "<div id='map' style='height:400px;width:650px;'></div>";
            Map =  L.map('map').setView([lat, lng], mapZoom);

            L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(Map);

        } else {
            document.getElementById('leaflet_maps_container_' + this.mapImageID).innerHTML = "<div id='map' style='height:400px;width:650px;'></div>";
            Map =  L.map('map').setView([lat, lng], mapZoom);

            L.tileLayer('https://a.tile.openstreetmap.org/{z}/{x}/{y}.png ', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(Map);

        }
        marker = L.marker([lat, lng], {icon: myIcon}).addTo(Map);
        Map.addLayer(editableLayers);
        Map.addControl(drawControlFull);
        Map.on(L.Draw.Event.CREATED, function (e) {
            Map.removeLayer(marker);
            layer = e.layer;
            editableLayers.addLayer(layer);
            if (editableLayers.getLayers().length === 1) {
                drawControlFull.remove(Map);
                drawControlEditOnly.addTo(Map);
                this.latitude.setValue(layer.getLatLng().lat);
                this.longitude.setValue(layer.getLatLng().lng)
            }
        }.bind(this));

        Map.on("draw:deleted", function (e) {
            drawControlEditOnly.remove(Map);
            drawControlFull.addTo(Map);
        });

    },

    getValue: function () {
        return {
            longitude: this.longitude.getValue(),
            latitude: this.latitude.getValue()
        };
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isInvalidMandatory: function () {

        // no render check is necessary because the input component returns the right values even if it is not
        // rendered
        var value = this.getValue();
        if (value.longitude && value.latitude) {
            return false;
        }
        return true;
    },

    isDirty: function () {
        if (!this.isRendered()) {
            return false;
        }

        if (this.longitude && this.latitude) {
            return this.longitude.isDirty() || this.latitude.isDirty();
        }

        return false;
    },

    getGridColumnConfig: function (field) {
        return {
            text: ts(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {
 
                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += ' grid_value_inherited';
                }
                if (value) {
                   return value.latitude + "," + value.longitude ;
                }
            }.bind(this, field.key)
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    }
});
