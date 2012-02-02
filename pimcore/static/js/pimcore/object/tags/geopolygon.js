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

    getGridColumnConfig: function(field) {
        return {header: ts(field.label), width: 150, sortable: false, dataIndex: field.key, renderer: function (key, value, metaData, record) {
            return t("not_supported");
        }.bind(this, field.key)};
    },

    getLayoutEdit: function () {

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

                var bounds = new google.maps.LatLngBounds(
                    new google.maps.LatLng(this.data[0].latitude, this.data[0].longitude),
                    new google.maps.LatLng(this.data[1].latitude, this.data[1].longitude)
                );

                for (var i=0; i<this.data.length; i++) {
                    bounds.extend(new google.maps.LatLng(this.data[i].latitude, this.data[i].longitude));
                    pointConfig.push(this.data[i].latitude + "," + this.data[i].longitude);
                }

                // add startpoint also as endpoint
                pointConfig.push(this.data[0].latitude + "," + this.data[0].longitude);

                var center = bounds.getCenter();
                var ne = bounds.getNorthEast();
                var sw = bounds.getSouthWest();
                
                // calculate zoom level without using the gmap2-object       
                var s = 1.35; 
                var xZoom = -(Math.log((ne.lng() - sw.lng())/(px*s))/Math.log(2));
                var yZoom = -(Math.log(((ne.lat() - sw.lat())*Math.sec( center.lat()*Math.PI/180))/(py*s))/Math.log(2));
                mapZoom = Math.min(Math.floor(xZoom), Math.floor(yZoom));
                
                var path = "color:0xff0000ff|weight:2|" + pointConfig.join("|");
                mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=" + center.lat() + "," + center.lng() + "&zoom=" + mapZoom + "&size=" + px + "x" + py + "&path=" + path + "&sensor=false";
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
                    this.polygon.setMap(null);
                    delete this.polygon;
                    
                    this.polygon = new google.maps.Polygon({
                        paths: [],
                        strokeColor: "#f33f00",
                        strokeOpacity: 1,
                        strokeWeight: 2,
                        fillColor: "#ff0000",
                        fillOpacity: 0.2,
                        map: this.gmap
                    });
                    
                    this.polygon.runEdit(true);
                    
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

                    var points = this.polygon.getPath();

                    if(points.length > 0) {
                        this.data = [];
                    }
                    
                    for (var i=0; i<points.length; i++) {
                        this.data.push({
                            latitude: points.getAt(i).lat(),
                            longitude: points.getAt(i).lng()
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

            var center = new google.maps.LatLng(0,0);
            var mapZoom = 1;
            this.markers = [];
            this.polygonPoints = [];

            this.gmap = new google.maps.Map(this.searchWindow.body.dom, {
                zoom: mapZoom,
                center: center,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });
            google.maps.event.addListener(this.gmap, 'click', this.addPoint.bind(this));

            if (this.data) {
                for (var i=0; i<this.data.length; i++) {
                    this.polygonPoints.push(new google.maps.LatLng(this.data[i].latitude, this.data[i].longitude));
                }
            } else {
                this.polygonPoints = new google.maps.MVCArray;
            }

            this.polygon = new google.maps.Polygon({
                paths: new google.maps.MVCArray([this.polygonPoints]),
                strokeColor: "#f33f00",
                strokeOpacity: 1,
                strokeWeight: 2,
                fillColor: "#ff0000",
                fillOpacity: 0.2,
                map: this.gmap
            });

            //this.polygon.enableEditing({onEvent: "mouseover"});
            //this.polygon.disableEditing({onEvent: "mouseout"});
           
            if(this.data) {
                this.gmap.fitBounds(this.polygon.getBounds());
                this.gmap.setCenter(this.polygon.getBounds().getCenter());
            }
            
            this.geocoder = new google.maps.Geocoder();
        }.bind(this))

        this.searchWindow.on("beforeclose", function () {
            delete this.polygon;
            delete this.gmap;
            delete this.geocoder;
        }.bind(this));

        this.searchWindow.show();
    },

    addPoint : function (event) {
        this.polygonPoints.insertAt(this.polygonPoints.length, event.latLng);

        var marker = new google.maps.Marker({
          position: event.latLng,
          map: this.gmap,
          draggable: true
        });
        this.markers.push(marker);
        marker.setTitle("#" + this.polygonPoints.length);

        google.maps.event.addListener(marker, 'click', function() {
          marker.setMap(null);
          for (var i = 0, I = this.markers.length; i < I && this.markers[i] != marker; ++i);
          this.markers.splice(i, 1);
          this.polygonPoints.removeAt(i);
          }.bind(this)
        );

        google.maps.event.addListener(marker, 'dragend', function() {
          for (var i = 0, I = this.markers.length; i < I && this.markers[i] != marker; ++i);
          this.polygonPoints.setAt(i, marker.getPosition());
          }.bind(this)
        );
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




