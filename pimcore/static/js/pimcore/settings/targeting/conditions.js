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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

/* CONDITION TYPES */

pimcore.registerNS("pimcore.settings.targeting.conditions");
pimcore.settings.targeting.conditions = {

    detectBlockIndex: function (blockElement, container) {
        // detect index
        var index;

        for(var s=0; s<container.items.items.length; s++) {
            if(container.items.items[s].getId() == blockElement.getId()) {
                index = s;
                break;
            }
        }
        return index;
    },

    getTopBar: function (name, index, parent, data) {

        var toggleGroup = "g_" + index + parent.data.id;
        if(!data["operator"]) {
            data.operator = "and";
        }

        return [{
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        },"-",{
            iconCls: "pimcore_icon_up",
            handler: function (blockId, parent) {

                var container = parent.conditionsContainer;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.settings.targeting.conditions.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                var newIndex = index-1;
                if(newIndex < 0) {
                    newIndex = 0;
                }

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(newIndex, blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                parent.recalculateButtonStatus();
                parent.recalculateBracketIdent(parent.conditionsContainer.items);

                pimcore.layout.refresh();
            }.bind(window, index, parent)
        },{
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {

                var container = parent.conditionsContainer;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.settings.targeting.conditions.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(index+1, blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                parent.recalculateButtonStatus();
                parent.recalculateBracketIdent(parent.conditionsContainer.items);

                pimcore.layout.refresh();
            }.bind(window, index, parent)
        },"-", {
            text: t("AND"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_and",
            pressed: (data.operator == "and") ? true : false
        },{
            text: t("OR"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_or",
            pressed: (data.operator == "or") ? true : false
        },{
            text: t("AND_NOT"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_and_not",
            pressed: (data.operator == "and_not") ? true : false
        },"->",{
            iconCls: "pimcore_icon_delete",
            handler: function (index, parent) {
                parent.conditionsContainer.remove(Ext.getCmp(index));
                parent.recalculateButtonStatus();
                parent.recalculateBracketIdent(parent.conditionsContainer.items);
            }.bind(window, index, parent)
        }];
    },

    itemUrl: function (panel, data, getName) {

        var niceName = "URL (RegExp)";
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'textfield',
                fieldLabel: "URL (RegExp)",
                name: "url",
                value: data.url,
                width: 400
            },{
                xtype: "hidden",
                name: "type",
                value: "url"
            }]
        });

        return item;
    },

    itemBrowser: function (panel, data, getName) {

        var niceName = t("browser");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype: "combo",
                fieldLabel: t("browser"),
                store: [
                    ["ie", "Internet Explorer"],
                    ["firefox", "Firefox"],
                    ["chrome", "Google Chrome"],
                    ["safari", "Safari"],
                    ["opera", "Opera"]
                ],
                name: "browser",
                mode: "local",
                width: 200,
                value: data.browser,
                triggerAction: "all"
            },{
                xtype: "hidden",
                name: "type",
                value: "browser"
            }]
        });

        return item;
    },

    itemCountry: function (panel, data, getName) {

        var niceName = t("country");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'combo',
                fieldLabel: t('country'),
                displayField: 'name',
                valueField: 'code',
                name: "country",
                store: new Ext.data.JsonStore({
                    autoDestroy: true,
                    url: "/admin/misc/country-list",
                    root: "data",
                    fields: ["code", "name"]
                }),
                triggerAction: "all",
                mode: "local",
                width: 250,
                value: data.country,
                listeners: {
                    afterrender: function (el) {
                        el.getStore().load();
                    }
                }
            },{
                xtype: "hidden",
                name: "type",
                value: "country"
            }]
        });

        return item;
    },

    itemLanguage: function (panel, data, getName) {

        var niceName = t("language");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'combo',
                fieldLabel: t('language'),
                displayField: 'name',
                valueField: 'code',
                name: "language",
                store: new Ext.data.JsonStore({
                    autoDestroy: true,
                    url: "/admin/misc/language-list",
                    root: "data",
                    fields: ["code", "name"]
                }),
                triggerAction: "all",
                mode: "local",
                width: 250,
                value: data.language,
                listeners: {
                    afterrender: function (el) {
                        el.getStore().load();
                    }
                }
            },{
                xtype: "hidden",
                name: "type",
                value: "language"
            }]
        });

        return item;
    },

    itemEvent: function (panel, data, getName) {

        var niceName = t("event");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'textfield',
                fieldLabel: t('key'),
                name: "key",
                value: data.key,
                width: 200
            },{
                xtype:'textfield',
                fieldLabel: t('value'),
                name: "value",
                value: data.value,
                width: 100
            },{
                xtype: "hidden",
                name: "type",
                value: "event"
            }]
        });

        return item;
    },

    itemGeopoint: function (panel, data, getName) {

        var niceName = t("geopoint");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var longitude = new Ext.form.NumberField({
            decimalPrecision: 20,
            fieldLabel: t('longitude'),
            name: "longitude",
            value: data.longitude,
            width: 250
        });

        var latitude = new Ext.form.NumberField({
            decimalPrecision: 20,
            fieldLabel: t('latitude'),
            name: "latitude",
            value: data.latitude,
            width: 250
        });

        var radius = new Ext.form.NumberField({
            decimalPrecision: 0,
            fieldLabel: t('radius_in_km'),
            name: "radius",
            value: data.radius,
            width: 50
        });

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [longitude, latitude,radius,{
                xtype: "button",
                text: t("open_search_editor"),
                iconCls: "pimcore_icon_search",
                handler: function () {

                    var gmap, marker, mapPanel, circle;

                    var searchfield = new Ext.form.TextField({
                        width: 300,
                        name: "mapSearch",
                        style: "float: left;",
                        fieldLabel: t("search")
                    });

                    var currentLocationTextNode = new Ext.Toolbar.TextItem({
                        text: " - "
                    });

                    var searchWindow = new Ext.Window({
                        modal: true,
                        width: 600,
                        height: 500,
                        resizable: false,
                        tbar: [currentLocationTextNode],
                        bbar: [searchfield,{
                            xtype: "button",
                            text: t("search"),
                            icon: "/pimcore/static/img/icon/magnifier.png",
                            handler: function () {

                                var geocoder = new google.maps.Geocoder();
                                if (geocoder) {
                                    var address = searchfield.getValue();
                                    geocoder.geocode( { 'address': address}, function(results, status) {
                                        if (status == google.maps.GeocoderStatus.OK) {
                                            marker.setPosition(results[0].geometry.location);
                                            gmap.setCenter(results[0].geometry.location, 16);
                                            gmap.setZoom(7);
                                        }
                                    });
                                }
                            }
                        },"->",{
                            xtype: "button",
                            text: t("cancel"),
                            icon: "/pimcore/static/img/icon/cancel.png",
                            handler: function () {
                                searchWindow.close();
                            }
                        },{
                            xtype: "button",
                            text: "OK",
                            icon: "/pimcore/static/img/icon/tick.png",
                            handler: function () {
                                var point = marker.getPosition();
                                latitude.setValue(point.lat());
                                longitude.setValue(point.lng());
                                radius.setValue(Math.round(circle.getRadius()/1000));

                                searchWindow.close();
                            }
                        }],
                        plain: true
                    });


                    searchWindow.on("afterrender", function () {

                        var latitudeMap = 0;
                        var longitudeMap = 0;
                        var radiusMap = 100000;
                        var mapZoom = 1;
                        if (latitude.getValue() && longitude.getValue()) {
                            latitudeMap = latitude.getValue();
                            longitudeMap = longitude.getValue();
                            mapZoom = 14;
                        }
                        if(radius.getValue()) {
                            radiusMap = radius.getValue() * 1000;
                        }

                        var startingPoint = new google.maps.LatLng(latitudeMap,longitudeMap);

                        gmap = new google.maps.Map(searchWindow.body.dom, {
                            zoom: mapZoom,
                            center: startingPoint,
                            mapTypeId: google.maps.MapTypeId.ROADMAP
                        });

                        marker =  new google.maps.Marker({
                            position: startingPoint,
                            map: gmap,
                            draggable: true
                        });

                        circle = new google.maps.Circle({
                            map: gmap,
                            center: startingPoint,
                            editable: true,
                            radius:radiusMap,
                            fillColor: "#ff6600",
                            fillOpacity: 0.5,
                            strokeWeight: 2,
                            strokeOpacity: 1,
                            strokeColor: "#000000"
                        });

                        google.maps.event.addListener(marker, "position_changed", function () {
                            circle.setCenter(marker.getPosition());
                        });

                        var GLOBE_WIDTH = 256; // a constant in Google's map projection
                        var west = circle.getBounds().getSouthWest().lng();
                        var east = circle.getBounds().getNorthEast().lng();
                        var angle = east - west;
                        if (angle < 0) {
                          angle += 360;
                        }
                        var zoom = Math.round(Math.log(searchWindow.body.getWidth()
                                                                            * 360 / angle / GLOBE_WIDTH) / Math.LN2);
                        gmap.setZoom(zoom-1);
                    });

                    searchWindow.show();
                }
            },{
                xtype: "hidden",
                name: "type",
                value: "geopoint"
            }, {
                xtype: "displayfield",
                style: "margin-top:10px;",
                html: 'This product includes GeoLite2 data created by MaxMind, available from <a href="http://www.maxmind.com" target="_blank">http://www.maxmind.com</a>.'
            }]
        });

        return item;
    },

    itemReferringsite: function (panel, data, getName) {

        var niceName = t("referring_site");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'textfield',
                fieldLabel: t('referrer'),
                name: "referrer",
                value: data.referrer,
                width: 350
            },{
                xtype: "hidden",
                name: "type",
                value: "referringsite"
            }]
        });

        return item;
    },

    itemSearchengine: function (panel, data, getName) {

        var niceName = t("searchengine");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'combo',
                fieldLabel: t('searchengine'),
                name: "searchengine",
                disableKeyFilter: true,
                store: [["",t("all")],["google","Google"],["bing","Bing"],["yahoo", "Yahoo!"]],
                triggerAction: "all",
                mode: "local",
                width: 250,
                value: data.searchengine ? data.searchengine : ""
            },{
                xtype: "hidden",
                name: "type",
                value: "searchengine"
            }]
        });

        return item;
    },

    itemVistitedpagebefore: function (panel, data, getName) {

        var niceName = t("visited_page_before");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'textfield',
                fieldLabel: "URL",
                name: "url",
                value: data.url,
                width: 350
            },{
                xtype: "hidden",
                name: "type",
                value: "vistitedpagebefore"
            }]
        });

        return item;
    },

    itemVistitedpagesbefore: function (panel, data, getName) {

        var niceName = t("visited_pages_before_number");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'spinnerfield',
                fieldLabel: t("number"),
                name: "number",
                value: data.number,
                width: 40
            },{
                xtype: "hidden",
                name: "type",
                value: "vistitedpagesbefore"
            }]
        });

        return item;
    },

    itemTimeonsite: function (panel, data, getName) {

        var niceName = t("time_on_site");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'spinnerfield',
                fieldLabel: t("hours"),
                name: "hours",
                value: data.hours ? data.hours : 0,
                width: 40
            },{
                xtype:'spinnerfield',
                fieldLabel: t("minutes"),
                name: "minutes",
                value: data.minutes ? data.minutes : 0,
                width: 40
            },{
                xtype:'spinnerfield',
                fieldLabel: t("seconds"),
                name: "seconds",
                value: data.seconds ? data.seconds : 0,
                width: 40
            },{
                xtype: "hidden",
                name: "type",
                value: "timeonsite"
            }]
        });

        return item;
    },

    itemLinkclicked: function (panel, data, getName) {

        var niceName = t("link_clicked");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'textfield',
                fieldLabel: "URL",
                name: "url",
                value: data.url,
                width: 350
            },{
                xtype: "hidden",
                name: "type",
                value: "linkclicked"
            }]
        });

        return item;
    },

    itemLinksclicked: function (panel, data, getName) {

        var niceName = t("number_of_links_clicked");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'spinnerfield',
                fieldLabel: t("number"),
                name: "number",
                value: data.number,
                width: 40
            },{
                xtype: "hidden",
                name: "type",
                value: "linksclicked"
            }]
        });

        return item;
    },

    itemHardwareplatform: function (panel, data, getName) {

        var niceName = t("hardware_platform");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'combo',
                fieldLabel: t('hardware_platform'),
                name: "platform",
                disableKeyFilter: true,
                store: [["",t("all")],["desktop",t("desktop")],["tablet", t("tablet")], ["mobile", t("mobile")]],
                triggerAction: "all",
                mode: "local",
                width: 250,
                value: data.platform ? data.platform : "all"
            },{
                xtype: "hidden",
                name: "type",
                value: "hardwareplatform"
            }]
        });

        return item;
    },

    itemOperatingsystem: function (panel, data, getName) {

        var niceName = t("operating_system");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype:'combo',
                fieldLabel: t('operating_system'),
                name: "system",
                disableKeyFilter: true,
                store: [["",t("all")],["windows","Windows"],["macos", "Mac OS"], ["linux", "Linux"],
                                                                ["android","Android"], ["ios", "iOS"]],
                triggerAction: "all",
                mode: "local",
                width: 250,
                value: data.system ? data.system : "all"
            },{
                xtype: "hidden",
                name: "type",
                value: "operatingsystem"
            }]
        });

        return item;
    },

    itemPersona: function (panel, data, getName) {

        var niceName = t("personas");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            items: [{
                xtype: "combo",
                name: "persona",
                displayField:'text',
                valueField: "id",
                store: pimcore.globalmanager.get("personas"),
                editable: false,
                width: 300,
                triggerAction: 'all',
                listWidth: 200,
                mode: "local",
                value: data["persona"],
                emptyText: t("select_a_persona"),
                fieldLabel: t("select_a_persona")
            },{
                xtype: "hidden",
                name: "type",
                value: "persona"
            }]
        });

        return item;
    }
};

