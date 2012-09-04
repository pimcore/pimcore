/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.tags.geopoint");
pimcore.object.tags.geopoint = Class.create(pimcore.object.tags.abstract, {

    type: "geopoint",

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            if(record.data.inheritedFields[key] && record.data.inheritedFields[key].inherited == true) {
                metaData.css += " grid_value_inherited";
            }

            if (value) {
                if (value.latitude && value.longitude) {

                    var width = 140;
                    var mapZoom = 10;

                    var mapUrl = "https://maps.google.com/staticmap?center=" + value.latitude + "," + value.longitude + "&zoom=" + mapZoom + "&size=" + width + "x80&markers=" + value.latitude + "," + value.longitude + ",red&sensor=false";

                    return '<img src="' + mapUrl + '" />';
                }
            }
        }.bind(this, field.key)};
    },

    getLayoutEdit: function () {


        if (!this.data) {
            this.data = {};
            this.data.longitude = 0;
            this.data.latitude = 0;
        }

        this.mapImageID = uniqid();
        var longitudeConf = {
            value: this.data.longitude,
            decimalPrecision: 15,
            enableKeyEvents: true,
            width: 110
        };
        var latitudeConf = {
            value: this.data.latitude,
            decimalPrecision: 15,
            enableKeyEvents: true,
            width: 110
        };

        this.longitude = new Ext.form.NumberField(longitudeConf);
        this.latitude = new Ext.form.NumberField(latitudeConf);


        this.longitude.on("keyup", this.updatePreviewImage.bind(this));
        this.latitude.on("keyup", this.updatePreviewImage.bind(this));

        this.component = new Ext.Panel({
            title: this.fieldConfig.title,
            height: 370,
            width: 490,
            cls: "object_field",
            html: '<div id="google_maps_container_' + this.mapImageID + '" align="center"><img align="center" width="300" height="300" src="' + this.getMapUrl(this.data.latitude, this.data.longitude) + '" /></div>',
            bbar: [t("latitude"), this.latitude, "-", t("longitude"), this.longitude, "-", " ", "-", {
                xtype: "button",
                text: t("open_search_editor"),
                icon: "/pimcore/static/img/icon/magnifier.png",
                handler: this.openPicker.bind(this)
            }]
        });

        this.component.on("afterrender", function () {
            this.updatePreviewImage();
        }.bind(this))

        return this.component;
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    updatePreviewImage: function () {
        var data = this.getValue();
        var width = Ext.get("google_maps_container_" + this.mapImageID).getWidth();

        if (width > 640) {
            width = 640;
        }
        if (width < 10) {
            window.setTimeout(this.updatePreviewImage.bind(this), 1000);
        }

        Ext.get("google_maps_container_" + this.mapImageID).dom.innerHTML = '<img align="center" width="' + width + '" height="300" src="' + this.getMapUrl(data.latitude, data.longitude, width) + '" />';
    },

    getMapUrl: function (latitude, longitude, width) {
        // static maps api image url
        var latitudeMap = 0;
        var longitudeMap = 0;
        var mapZoom = 1;
        if (longitude && latitude) {
            latitudeMap = latitude;
            longitudeMap = longitude;
            mapZoom = 14;
        }

        if (!width) {
            width = 300;
        }

        var mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=" + latitudeMap + "," + longitudeMap + "&zoom=" + mapZoom + "&size=" + width + "x300&markers=color:red|" + latitudeMap + "," + longitudeMap + "&sensor=false";
        return mapUrl;
    },

    openPicker: function () {
        var data = this.getValue();

        this.searchfield = new Ext.form.TextField({
            width: 300,
            name: "mapSearch",
            style: "float: left;",
            fieldLabel: t("search")
        });

        this.currentLocationTextNode = new Ext.Toolbar.TextItem({
            text: " - "
        });

        this.searchWindow = new Ext.Window({
            modal: true,
            width: 600,
            height: 500,
            resizable: false,
            tbar: [this.currentLocationTextNode],
            bbar: [this.searchfield,{
                xtype: "button",
                text: t("search"),
                icon: "/pimcore/static/img/icon/magnifier.png",
                handler: this.geocode.bind(this)
            },"->",{
                xtype: "button",
                text: t("cancel"),
                icon: "/pimcore/static/img/icon/cancel.png",
                handler: function () {
                    this.searchWindow.close();
                }.bind(this)
            },{
                xtype: "button",
                text: "OK",
                icon: "/pimcore/static/img/icon/tick.png",
                handler: function () {
                    var point = this.marker.getPosition();
                    this.latitude.setValue(point.lat());
                    this.longitude.setValue(point.lng());
                    this.updatePreviewImage();

                    this.searchWindow.close();
                }.bind(this)
            }],
            plain: true
        });

        this.searchWindow.on("afterrender", function () {

            var data = this.getValue();
            var latitudeMap = 0;
            var longitudeMap = 0;
            var mapZoom = 1;
            if (data.longitude && data.latitude) {
                latitudeMap = data.latitude;
                longitudeMap = data.longitude;
                mapZoom = 14;
            }
            var startingPoint = new google.maps.LatLng(latitudeMap,longitudeMap);

            this.gmap = new google.maps.Map(this.searchWindow.body.dom, {
                zoom: mapZoom,
                center: startingPoint,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            this.geocoder = new google.maps.Geocoder();

            this.marker =  new google.maps.Marker({
                position: startingPoint,
                map: this.gmap,
                draggable: true
            });

            this.reverseGeocodeInterval = window.setInterval(this.reverseGeocode.bind(this), 500)

        }.bind(this));

        this.searchWindow.on("beforeclose", function () {
            clearInterval(this.reverseGeocodeInterval);
        }.bind(this));

        this.searchWindow.show();
    },

    geocode: function () {

        if (this.geocoder) {
            var address = this.searchfield.getValue();
            this.geocoder.geocode( { 'address': address}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    this.marker.setPosition(results[0].geometry.location);
                    this.gmap.setCenter(results[0].geometry.location, 16);
                    this.gmap.setZoom(14);
                }
            }.bind(this));
        }
    },

    reverseGeocode: function () {
        if (this.marker) {

            var latlng = this.marker.getPosition();
            if (latlng != this.lastPosition) {
                if (latlng) {
                    this.geocoder.geocode({'latLng': latlng}, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                this.currentLocationTextNode.setText(results[1].formatted_address);
                            }
                        }
                    }.bind(this));
                }
            }
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

        // no render check is necessary because the input compontent returns the right values even if it is not rendered
        var value = this.getValue();
        if (value.longitude && value.latitude) {
            return false;
        }
        return true;
    },

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }
        
        return this.longitude.isDirty() || this.latitude.isDirty();
    }
});