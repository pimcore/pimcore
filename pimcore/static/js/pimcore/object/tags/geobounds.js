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

    initialize: function (data, fieldConfig) {
        this.data = data;
        this.fieldConfig = fieldConfig;

    },

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            return t("not_supported");
        }.bind(this, field.key)};
    },

    getLayoutEdit: function () {


        this.mapImageID = uniqid();

        if (!this.data) {
            this.data = {};
        }
        else {
            this.data.ne = new google.maps.LatLng(this.data.NElatitude,this.data.NElongitude);
            this.data.sw = new google.maps.LatLng(this.data.SWlatitude,this.data.SWlongitude);
        }

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
                
                var bounds = new google.maps.LatLngBounds(this.data.sw, this.data.ne);
                var center = bounds.getCenter();
                
                // calculate zoom level without using the gmap2-object       
                var s = 1.35; 
                var xZoom = -(Math.log((this.data.ne.lng() - this.data.sw.lng())/(px*s))/Math.log(2));
                var yZoom = -(Math.log(((this.data.ne.lat() - this.data.sw.lat())*Math.sec( center.y*Math.PI/180))/(py*s))/Math.log(2));
                mapZoom = Math.min(Math.floor(xZoom), Math.floor(yZoom));
                
                var path = "color:0xff0000ff|weight:2|" + this.data.ne.lat() + "," + this.data.ne.lng() + "|" + this.data.sw.lat() + "," + this.data.ne.lng() + "|" + this.data.sw.lat() + "," + this.data.sw.lng() + "|" + this.data.ne.lat() + "," + this.data.sw.lng() + "|" + this.data.ne.lat() + "," + this.data.ne.lng();
                mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=" + center.y + "," + center.x + "&zoom=" + mapZoom + "&size=" + px + "x" + py + "&path=" + path + "&sensor=false";
            }
            else {
                mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=0,0&zoom=1&size=" + px + "x" + py + "&sensor=false";
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
                    
                    this.data.ne = new google.maps.LatLng(this.NWmarker.getPosition().lat(),this.SEmarker.getPosition().lng());
                    this.data.sw = new google.maps.LatLng(this.SEmarker.getPosition().lat(),this.NWmarker.getPosition().lng());
                    this.dirty = true;
                    
                    this.updatePreviewImage();
                    this.searchWindow.close();
                }.bind(this)
            }],
            plain: true
        });

        this.searchWindow.on("afterrender", function () {

            var center = new google.maps.LatLng(0,0);
            var mapZoom = 1;
            var bounds;
            
            if (this.data.ne && this.data.sw) {
               bounds = new google.maps.LatLngBounds(this.data.sw, this.data.ne);
               center = bounds.getCenter();
            }

            this.gmap = new google.maps.Map(this.searchWindow.body.dom, {
                zoom: mapZoom,
                center: center,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            if(bounds) {
                this.gmap.fitBounds(bounds);

                this.NWmarker = this.getMarker(new google.maps.LatLng(this.data.ne.lat(),this.data.sw.lng()),"nw");
                this.SEmarker = this.getMarker(new google.maps.LatLng(this.data.sw.lat(),this.data.ne.lng()),"se");
            }


            this.redrawShape();

            this.geocoder = new google.maps.Geocoder();
            
            google.maps.event.addListener(this.gmap,"click",this.createOnClickMarker.bind(this));

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
            var address = this.searchfield.getValue();
            this.geocoder.geocode( { 'address': address}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    this.gmap.setCenter(results[0].geometry.location, 16);
                    this.gmap.setZoom(14);
                }
            }.bind(this));
        }
    },
    
    getMarker: function (point, type) {
        var marker = new google.maps.Marker({
            position: point,
            draggable: true,
            map: this.gmap
        });

        //GEvent.addListener(marker,"drag",this.redrawShape.bind(this));
        google.maps.event.addListener(marker, "drag", this.observePosition.bind(this, type));
        
        return marker;
    },

    createOnClickMarker: function (e) {

        var point = e.latLng;

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
        points.push(new google.maps.LatLng(nw.lat(),se.lng()));
        points.push(se);
        points.push(new google.maps.LatLng(se.lat(),nw.lng()));
        points.push(nw);
        
        return points; 
    },
    
    observePosition: function (positionType) {
        if(positionType == "nw") {
            if(this.NWmarker.getPosition().lng() >= this.SEmarker.getPosition().lng()) {
                this.NWmarker.setPosition(new google.maps.LatLng(this.NWmarker.getPosition().lat(),this.SEmarker.getPosition().lng()));
            }
            if(this.NWmarker.getPosition().lat() <= this.SEmarker.getPosition().lat()) {
                this.NWmarker.setPosition(new google.maps.LatLng(this.SEmarker.getPosition().lat(),this.NWmarker.getPosition().lng()));
            }
        }
        else {
            if(this.SEmarker.getPosition().lng() <= this.NWmarker.getPosition().lng()) {
                this.SEmarker.setPosition(new google.maps.LatLng(this.SEmarker.getPosition().lat(),this.NWmarker.getPosition().lng()));
            }
            if(this.SEmarker.getPosition().y >= this.NWmarker.getPosition().y) {
                this.SEmarker.setPosition(new google.maps.LatLng(this.NWmarker.getPosition().lat(),this.SEmarker.getPosition().lng()));
            }
        }
        
        this.redrawShape();
    },
    
    redrawShape: function () {
        
        if(typeof this.polygon != "undefined") {
            this.polygon.setMap(null);
        }

        if( typeof this.NWmarker != "undefined" && this.NWmarker != null && typeof this.SEmarker != "undefined" && this.SEmarker != null ) {
            this.polygon = new google.maps.Polygon({
                paths: this.getRectanglePoints(this.NWmarker.getPosition(),this.SEmarker.getPosition()),
                strokeColor: "#f33f00",
                strokeOpacity: 1,
                strokeWeight: 2,
                fillColor: "#ff0000",
                fillOpacity: 0.2,
                map: this.gmap
            });
        }
    },
    
    
    getValue: function () {
        if(this.data.ne) {
            return {
                NElatitude: this.data.ne.lat(),
                NElongitude: this.data.ne.lng(),
                SWlatitude: this.data.sw.lat(),
                SWlongitude: this.data.sw.lng()
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

    isDirty: function() {
        if(!this.isRendered()) {
            return false;
        }
        
        return this.dirty;
    }
});