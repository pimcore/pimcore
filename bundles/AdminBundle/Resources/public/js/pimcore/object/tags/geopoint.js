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
        this.divImageID = uniqid();
        this.mapId = "map" + this.divImageID;

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
            border: true,
            style: "margin-bottom: 10px",
            height: this.fieldConfig.height,
            width: this.fieldConfig.width,
            componentCls: 'object_field object_geo_field object_field_type_' + this.type,
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
            ],
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

    updateMap: function ($super) {
        var data = this.getValue();
        if (data.latitude && data.longitude) {
            this.data = data;
        } else {
            this.data = null;
        }
        $super();
    },

    getMap: function (fieldConfig, data) {
        this.marker = null;

        this.editableLayers = new L.FeatureGroup();

        var leafletMap = null;
        if (data) {
            leafletMap = this.getLeafletMap(
                data.latitude,
                data.longitude,
                15
            );
            this.marker = L.marker([data.latitude, data.longitude], {});
            leafletMap.addLayer(this.marker);
            this.editableLayers.addLayer(this.marker);
            this.reverseGeocode(this.marker);
        } else {
            leafletMap = this.getLeafletMap(
                fieldConfig.lat,
                fieldConfig.lng,
                fieldConfig.zoom
            );
        }
        this.getLeafletToolbar(leafletMap);

    },

    getLeafletToolbar: function (leafletMap) {
        leafletMap.addLayer(this.editableLayers);

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
                featureGroup: this.editableLayers,
                remove: false
            }
        });
        leafletMap.addControl(drawControlFull);

        leafletMap.on(L.Draw.Event.CREATED, function (e) {
            this.dirty = true;

            this.editableLayers.clearLayers();
            if (this.marker !== null) {
                this.marker.remove();
            }

            var layer = e.layer;
            this.editableLayers.addLayer(layer);

            if (this.editableLayers.getLayers().length === 1) {
                this.latitude.setValue(layer.getLatLng().lat);
                this.longitude.setValue(layer.getLatLng().lng);
                this.reverseGeocode(layer);
            }
        }.bind(this));

        leafletMap.on("draw:deleted", function (e) {
            this.dirty = true;
            this.latitude.setValue(null);
            this.longitude.setValue(null);
            if (this.marker !== null) {
                this.marker = null;
            }
        }.bind(this));

        leafletMap.on("draw:editmove", function (e) {
            this.dirty = true;
            this.latitude.setValue(e.layer.getLatLng().lat);
            this.longitude.setValue(e.layer.getLatLng().lng);
            this.reverseGeocode(e.layer);
        }.bind(this));

    },

    geocode: function () {
        var address = this.searchfield.getValue();
        Ext.Ajax.request({
            url: this.getSearchUrl(address),
            method: "GET",
            success: function (response, opts) {
                var data = Ext.decode(response.responseText);
                this.latitude.setValue(data[0].lat);
                this.longitude.setValue(data[0].lon);
                this.updateMap();
            }.bind(this),
        });

    },

    reverseGeocode: function (layerObj) {
        if (this.latitude.getValue() !== null && this.longitude.getValue() !== null) {
            var url = pimcore.settings.reverse_geocoding_url_template.replace('{lat}', this.latitude.getValue()).replace('{lon}', this.longitude.getValue());
            Ext.Ajax.request({
                url: url,
                method: "GET",
                success: function (response, opts) {
                    var data = Ext.decode(response.responseText);
                    this.currentLocationText = data.display_name;
                    layerObj.bindTooltip(this.currentLocationText);
                    layerObj.openTooltip();
                }.bind(this),
            });
        }
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
            text: t(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            getEditor: this.getWindowCellEditor.bind(this, field),
            renderer: function (key, value, metaData, record) {

                this.applyPermissionStyle(key, value, metaData, record);

                if (record.data.inheritedFields && record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                    metaData.tdCls += ' grid_value_inherited';
                }
                if (value) {
                    return value.latitude + "," + value.longitude;
                }
            }.bind(this, field.key)
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    },

});
