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
        this.searchfield = new Ext.form.TextField({
            width: 200,
            name: 'mapSearch',
            style: 'float:left;margin-top:0px;'
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
        this.mapZoom = fieldConfig.zoom;
        this.leafletMap = null;
        this.mapId = "map" + this.divImageID;
        this.marker = null;
        var markerIcon = L.icon({
            iconUrl: '/pimcore/static6/img/leaflet/marker-icon.png',
            shadowUrl: '/pimcore/static6/img/leaflet/marker-shadow.png'
        });

        this.editableLayers = new L.FeatureGroup();
        this.drawControlFull = new L.Control.Draw({
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

        if (data) {
            this.mapZoom = 15;
            this.lat = data.latitude;
            this.lng = data.longitude;
            this.getLeafletMap();
            this.marker = L.marker([this.lat, this.lng], {icon: markerIcon}).addTo(this.leafletMap);
            this.reverseGeocode(this.marker);
        } else {
            this.lat = fieldConfig.lat;
            this.lng = fieldConfig.lng;
            this.getLeafletMap();
        }
        this.getLeafletToolbar();

    },

    getLeafletToolbar: function () {
        this.leafletMap.addLayer(this.editableLayers);
        this.leafletMap.addControl(this.drawControlFull);
        this.leafletMap.on(L.Draw.Event.CREATED, function (e) {
            if (this.marker !== null) {
                this.leafletMap.removeLayer(this.marker);
            }
            this.layer = e.layer;
            this.editableLayers.addLayer(this.layer);

            if (this.editableLayers.getLayers().length === 1) {
                this.latitude.setValue(this.layer.getLatLng().lat);
                this.longitude.setValue(this.layer.getLatLng().lng);
                this.reverseGeocode(this.layer);
                this.drawControlFull.remove(this.leafletMap);
                this.drawControlEditOnly.addTo(this.leafletMap);

            }
        }.bind(this));

        this.leafletMap.on("draw:deleted", function (e) {
            this.drawControlEditOnly.remove(this.leafletMap);
            this.drawControlFull.addTo(this.leafletMap);
            this.latitude.setValue(null);
            this.longitude.setValue(null);
            if (this.marker !== null) {
                this.marker = null;
            }
        }.bind(this));

        this.leafletMap.on("draw:editmove", function (e) {
            this.latitude.setValue(e.layer.getLatLng().lat);
            this.longitude.setValue(e.layer.getLatLng().lng);
            this.reverseGeocode(e.layer);
        }.bind(this));

    },

    geocode: function () {
        var address = this.searchfield.getValue();
        jQuery.getJSON(this.getSearchUrl(address), function (json) {
            this.latitude.setValue(json[0].lat);
            this.longitude.setValue(json[0].lon);
            this.updateMap();
        }.bind(this));

    },

    reverseGeocode: function (layerObj) {
        if (this.latitude.getValue() !== null && this.longitude.getValue() !== null) {
            var url = pimcore.settings.reverse_geocoding_url_template.replace('{lat}', this.latitude.getValue()).replace('{lon}', this.longitude.getValue());
            jQuery.getJSON(url, function (json) {
                this.currentLocationText = json.display_name;
                layerObj.bindTooltip(this.currentLocationText);
                layerObj.openTooltip();
            }.bind(this));
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
                    return value.latitude + "," + value.longitude;
                }
            }.bind(this, field.key)
        };
    },

    getCellEditValue: function () {
        return this.getValue();
    },

});
