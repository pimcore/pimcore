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

pimcore.registerNS("pimcore.object.tags.geobounds");
pimcore.object.tags.geobounds = Class.create(pimcore.object.tags.abstract, {

    type: "geobounds",

    dirty: false,

    initialize: function (data, layoutConf) {
        this.data = data;
        this.layoutConf = layoutConf;

    },

    getLayoutEdit: function () {

        if (pimcore.settings.google_maps_api_key) {
            
            this.mapImageID = uniqid();
            
            if (!this.data) {
                this.data = {};
            }
            else {
                this.data.ne = new GLatLng(this.data.NElatitude,this.data.NElongitude);
                this.data.sw = new GLatLng(this.data.SWlatitude,this.data.SWlongitude);
            }
            
            this.layout = new Ext.Panel({
                title: this.layoutConf.title,
                height: 370,
                width: 490,
                cls: "object_field",
                html: '<div id="google_maps_container_' + this.mapImageID + '" align="center"><img align="center" width="300" height="300" src="' + this.getMapUrl() + '" /></div>',
                bbar: [{
                    xtype: "button",
                    text: t("empty"),
                    icon: "/pimcore/static/img/icon/bin.png",
                    handler: function () {
                        this.data = {};
                        this.dirty = true;
                        this.updatePreviewImage();
                    }.bind(this)
                },"->",{
                    xtype: "button",
                    text: t("open_select_editor"),
                    icon: "/pimcore/static/img/icon/magnifier.png",
                    handler: this.openPicker.bind(this)
                }]
            });
    
            this.layout.on("afterrender", function () {
                this.updatePreviewImage();
            }.bind(this))
        }
        else {
            // gmaps is not configured
            this.layout = new Ext.Panel({
                title: this.layoutConf.title,
                width: 490,
                bodyStyle: "padding: 10px;",
                cls: "object_field",
                html: 'Please set the Google Maps API Key in the System Settings to use this widget.'
            });
        }


        return this.layout;
    },

    getLayoutShow: function () {

        this.layout = this.getLayoutEdit();
        this.layout.disable();

        return this.layout;
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
            if (this.data.ne && this.data.sw) {
                
                var bounds = new GLatLngBounds(this.data.sw, this.data.ne);
                var center = bounds.getCenter();
                
                // calculate zoom level without using the gmap2-object       
                var s = 1.35; 
                var xZoom = -(Math.log((this.data.ne.x - this.data.sw.x)/(px*s))/Math.log(2)); 
                var yZoom = -(Math.log(((this.data.ne.y - this.data.sw.y)*Math.sec( center.y*Math.PI/180))/(py*s))/Math.log(2)); 
                mapZoom = Math.min(Math.floor(xZoom), Math.floor(yZoom));
                
                var path = "rgba:0xff0000ff,weight:2|" + this.data.ne.y + "," + this.data.ne.x + "|" + this.data.sw.y + "," + this.data.ne.x + "|" + this.data.sw.y + "," + this.data.sw.x + "|" + this.data.ne.y + "," + this.data.sw.x + "|" + this.data.ne.y + "," + this.data.ne.x;
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
        
        this.NWmarker = null;
        this.SEmarker = null;
        
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
                    
                    this.data.ne = new GLatLng(this.NWmarker.getLatLng().y,this.SEmarker.getLatLng().x);
                    this.data.sw = new GLatLng(this.SEmarker.getLatLng().y,this.NWmarker.getLatLng().x);
                    this.dirty = true;
                    
                    this.updatePreviewImage();
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
            
            if (this.data.ne && this.data.sw) {
               this.NWmarker = this.getMarker(new GLatLng(this.data.ne.y,this.data.sw.x),"nw");
               this.SEmarker = this.getMarker(new GLatLng(this.data.sw.y,this.data.ne.x),"se");
               
               var bounds = new GLatLngBounds(this.data.sw, this.data.ne);
               
               this.redrawShape();
               
               mapZoom = this.gmap.getBoundsZoomLevel(bounds);
               center = bounds.getCenter();
            }

            this.gmap.setCenter(center, mapZoom);
            this.geocoder = new GClientGeocoder();
            
            GEvent.addListener(this.gmap,"click",this.createOnClickMarker.bind(this));

        }.bind(this))

        this.searchWindow.on("beforeclose", function () {
            delete this.gmap;
            delete this.geocoder;
            delete this.SEmarker;
            delete this.NWmarker;            
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
    
    getMarker: function (point, type) {
        var marker = new GMarker(point, {draggable: true});
        
        //GEvent.addListener(marker,"drag",this.redrawShape.bind(this));
        GEvent.addListener(marker,"drag",this.observePosition.bind(this, type));
        this.gmap.addOverlay(marker);
        
        return marker;
    },

    createOnClickMarker: function (tmp,point) {
        
        if(typeof this.NWmarker == "undefined" || this.NWmarker == null) {
            this.NWmarker = this.getMarker(point, "nw");
        }
        else if (typeof this.SEmarker == "undefined" || this.SEmarker == null) {
            this.SEmarker = this.getMarker(point, "se");
            this.redrawShape();
        }
    },
    
    getRectanglePoints: function (nw,se) {
        var points = [];
        
        points.push(nw);
        points.push(new GLatLng(nw.y,se.x));  
        points.push(se);
        points.push(new GLatLng(se.y,nw.x));  
        points.push(nw);
        
        return points; 
    },
    
    observePosition: function (positionType) {
        if(positionType == "nw") {
            if(this.NWmarker.getLatLng().x >= this.SEmarker.getLatLng().x) {
                this.NWmarker.setLatLng(new GLatLng(this.NWmarker.getLatLng().y,this.SEmarker.getLatLng().x));
            }
            if(this.NWmarker.getLatLng().y <= this.SEmarker.getLatLng().y) {
                this.NWmarker.setLatLng(new GLatLng(this.SEmarker.getLatLng().y,this.NWmarker.getLatLng().x));
            }
        }
        else {
            if(this.SEmarker.getLatLng().x <= this.NWmarker.getLatLng().x) {
                this.SEmarker.setLatLng(new GLatLng(this.SEmarker.getLatLng().y,this.NWmarker.getLatLng().x));
            }
            if(this.SEmarker.getLatLng().y >= this.NWmarker.getLatLng().y) {
                this.SEmarker.setLatLng(new GLatLng(this.NWmarker.getLatLng().y,this.SEmarker.getLatLng().x));
            }
        }
        
        this.redrawShape();
    },
    
    redrawShape: function () {
        
        if(typeof this.polygon != "undefined") {
            this.gmap.removeOverlay(this.polygon);
        }
        
        this.polygon = new GPolygon(this.getRectanglePoints(this.NWmarker.getLatLng(),this.SEmarker.getLatLng()), "#f33f00", 2, 1, "#ff0000", 0.2);
        this.gmap.addOverlay(this.polygon);
    },
    
    
    getValue: function () {
        if(this.data.ne) {
            return {
                NElatitude: this.data.ne.y,
                NElongitude: this.data.ne.x,
                SWlatitude: this.data.sw.y,
                SWlongitude: this.data.sw.x
            };
        }
        
        return null;
    },

    getName: function () {
        return this.layoutConf.name;
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
        if(!this.layout.rendered) {
            return false;
        }
        
        return this.dirty;
    }
});