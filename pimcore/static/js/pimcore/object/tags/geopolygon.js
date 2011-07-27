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

pimcore.registerNS("pimcore.object.tags.geopolygon");
pimcore.object.tags.geopolygon = Class.create(pimcore.object.tags.abstract, {

    type: "geopolygon",
    dirty: false,

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getLayoutEdit: function () {

        if (pimcore.settings.google_maps_api_key) {
            
            this.mapImageID = uniqid();
            
            this.component = new Ext.Panel({
                title: this.fieldConfig.title,
                height: 370,
                width: 490,
                cls: "object_field",
                html: '<div id="google_maps_container_' + this.mapImageID + '" align="center"><img align="center" width="300" height="300" src="' + this.getMapUrl() + '" /></div>',
                bbar: [{
                    xtype: "button",
                    text: t("empty"),
                    icon: "/pimcore/static/img/icon/bin.png",
                    handler: function () {
                        this.data = null;
                        this.updatePreviewImage();
                        this.dirty = true;
                    }.bind(this)
                },"->",{
                    xtype: "button",
                    text: t("open_select_editor"),
                    icon: "/pimcore/static/img/icon/magnifier.png",
                    handler: this.openPicker.bind(this)
                }]
            });
    
            this.component.on("afterrender", function () {
                this.updatePreviewImage();
            }.bind(this))
        }
        else {
            // gmaps is not configured
            this.component = new Ext.Panel({
                title: this.fieldConfig.title,
                width: 490,
                bodyStyle: "padding: 10px;",
                cls: "object_field",
                html: 'Please set the Google Maps API Key in the System Settings to use this widget.'
            });
        }


        return this.component;
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    updatePreviewImage: function () {
        
        var width = Ext.get("google_maps_container_" + this.mapImageID).getWidth();

        if (width > 640) {
            width = 640;
        }
        if (width < 10) {
            window.setTimeout(this.updatePreviewImage.bind(this), 1000);
        }
        
        Ext.get("google_maps_container_" + this.mapImageID).dom.innerHTML = '<img align="center" width="' + width + '" height="300" src="' + this.getMapUrl(width) + '" />';
    },

    getMapUrl: function (width) {
        
        // static maps api image url
        var mapZoom = 14;
        var mapUrl;
        
        if (!width) {
            width = 300;
        }
        
        var py = 300;
        var px = width; 
        
        try {
            if (this.data) {
                
                var pointConfig = [];
                var polygonPoints = [];
                for (var i=0; i<this.data.length; i++) {
                    polygonPoints.push(new GLatLng(this.data[i].latitude, this.data[i].longitude));
                    pointConfig.push(this.data[i].latitude + "," + this.data[i].longitude);
                }
                var polygon = new GPolygon(polygonPoints, "#f33f00", 2, 1, "#ff0000", 0.2);
                
                var bounds = polygon.getBounds();
                var center = bounds.getCenter();
                var ne = bounds.getNorthEast();
                var sw = bounds.getSouthWest();
                
                // calculate zoom level without using the gmap2-object       
                var s = 1.35; 
                var xZoom = -(Math.log((ne.x - sw.x)/(px*s))/Math.log(2)); 
                var yZoom = -(Math.log(((ne.y - sw.y)*Math.sec( center.y*Math.PI/180))/(py*s))/Math.log(2)); 
                mapZoom = Math.min(Math.floor(xZoom), Math.floor(yZoom));
                
                var path = "rgba:0xff0000ff,weight:2|" + pointConfig.join("|");
                mapUrl = "http://maps.google.com/staticmap?center=" + center.y + "," + center.x + "&zoom=" + mapZoom + "&size=" + px + "x" + py + "&path=" + path + "&sensor=false&key=" + pimcore.settings.google_maps_api_key;
            }
            else {
                mapUrl = "http://maps.google.com/staticmap?center=0,0&zoom=1&size=" + px + "x" + py + "&sensor=false&key=" + pimcore.settings.google_maps_api_key;
            }
        }
        catch (e) {
            console.log(e);
        }
        return mapUrl;
    },

    openPicker: function () {
        
        this.polygon;
        
        this.searchfield = new Ext.form.TextField({
            width: 300,
            name: "mapSearch",
            style: "float: left;",
            fieldLabel: t("search")
        });

        this.mapPanel = new Ext.Panel({
            plain: true
        });

        this.searchWindow = new Ext.Window({
            modal: true,
            width: 600,
            height: 500,
            resizable: false,
            tbar: [{
                xtype: "button",
                text: t("empty"),
                icon: "/pimcore/static/img/icon/bin.png",
                handler: function () {
                    this.gmap.clearOverlays();
                    delete this.polygon;
                    
                    this.polygon = new GPolygon([], "#f33f00", 2, 1, "#ff0000", 0.2);
                    this.gmap.addOverlay(this.polygon);
                    
                    this.polygon.enableDrawing();
                    this.polygon.enableEditing({onEvent: "mouseover"});
                    this.polygon.disableEditing({onEvent: "mouseout"});
                    
                }.bind(this)
            }],
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
                    
                    this.data = null;
                    if(this.polygon.getVertexCount() > 0) {
                        this.data = [];
                    }
                    
                    for (var i=0; i<this.polygon.getVertexCount(); i++) {
                        this.data.push({
                            latitude: this.polygon.getVertex(i).y,
                            longitude: this.polygon.getVertex(i).x 
                        });
                    }
                    
                    this.updatePreviewImage();
                    this.dirty = true;
                    this.searchWindow.close();
                }.bind(this)
            }],
            plain: true
        });

        this.searchWindow.on("afterrender", function () {
            this.gmap = new GMap2(this.searchWindow.body.dom);
            var customUI = this.gmap.getDefaultUI();
            this.gmap.setUI(customUI);

            var center = new GLatLng(0,0);
            var mapZoom = 1;
            var polygonPoints = [];
            
            if (this.data) {
                for (var i=0; i<this.data.length; i++) {
                    polygonPoints.push(new GLatLng(this.data[i].latitude, this.data[i].longitude));
                }
            }
            
            this.polygon = new GPolygon(polygonPoints, "#f33f00", 2, 1, "#ff0000", 0.2);
            this.gmap.addOverlay(this.polygon);
            
            if(this.data) {
                mapZoom = this.gmap.getBoundsZoomLevel(this.polygon.getBounds());
                center = this.polygon.getBounds().getCenter();
            } else {
                this.polygon.enableDrawing();
            }
            
            this.polygon.enableEditing({onEvent: "mouseover"});
            this.polygon.disableEditing({onEvent: "mouseout"});
            
            
            this.gmap.setCenter(center, mapZoom);
            this.geocoder = new GClientGeocoder();
        }.bind(this))

        this.searchWindow.on("beforeclose", function () {
            delete this.polygon;
            delete this.gmap;
            delete this.geocoder;
        }.bind(this));

        this.searchWindow.show();
    },

    geocode: function () {
        if (this.geocoder) {
            this.geocoder.getLatLng(
                    this.searchfield.getValue(),
                    function(point) {
                        if (point) {
                            this.gmap.setCenter(point, 16);
                        }
                    }.bind(this)
                    );
        }
    },

    reverseGeocode: function () {

        if (this.marker) {

            var latlng = this.marker.getLatLng();
            if (latlng != this.lastPosition) {
                if (latlng) {
                    this.lastPosition = latlng;
                    this.geocoder.getLocations(latlng, function (response) {
                        try {
                            var place = response.Placemark[0];
                            this.currentLocationTextNode.setText("<b>" + t("current_address") + ": </b>" + place.address);
                        }
                        catch (e) {
                            console.log(e);
                        }
                    }.bind(this));
                }
            }
        }
    },
    
    getValue: function () {
        return this.data;
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

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }
        
        return this.dirty;
    }    
});