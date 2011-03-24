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

pimcore.registerNS("pimcore.object.classes.data.data");
pimcore.object.classes.data.data = Class.create({

    initData: function (d) {
        this.datax = {
            name: "",
            datatype: "data",
            fieldtype: this.getType()
        };

        if (d) {
            if (d.datatype && d.fieldtype && d.name) {
                var keys = Object.keys(d);
                for (var i = 0; i < keys.length; i++) {
                    this.datax[keys[i]] = d[keys[i]];
                }
            }
        }
    },

    getGroup: function () {
        return "other";   
    },

    getType: function () {
        return this.type;
    },

    getLayout: function () {

        this.specificPanel = new Ext.form.FormPanel({
            title: t(this.getType() + "_settings"),
            bodyStyle: "padding: 10px;",
            style: "margin: 10px 0 10px 0",
            layout: "pimcoreform",
            items: {}
        });

        var standardSettings = [
            {
                xtype: "textfield",
                fieldLabel: t("name"),
                name: "name",
                width: 300,
                enableKeyEvents: true,
                value: this.datax.name
            },
            {
                xtype: "textfield",
                fieldLabel: t("title") + " (" + t("label") + ")",
                name: "title",
                width: 300,
                value: this.datax.title
            },
            {
                xtype: "textarea",
                fieldLabel: t("tooltip"),
                name: "tooltip",
                width: 300,
                height: 100,
                value: this.datax.tooltip
            },
            {
                xtype: "checkbox",
                fieldLabel: t("mandatoryfield"),
                name: "mandatory",
                checked: this.datax.mandatory
            },
            {
                xtype: "checkbox",
                fieldLabel: t("not_editable"),
                name: "noteditable",
                checked: this.datax.noteditable
            },
            {
                xtype: "checkbox",
                fieldLabel: t("invisible"),
                name: "invisible",
                checked: this.datax.invisible
            },
            {
                xtype: "checkbox",
                fieldLabel: t("visible_in_gridview"),
                name: "visibleGridView",
                checked: this.datax.visibleGridView
            },
            {
                xtype: "checkbox",
                fieldLabel: t("visible_in_searchresult"),
                name: "visibleSearch",
                checked: this.datax.visibleSearch
            }
        ];

        var layoutSettings = [
            {
                xtype: "textfield",
                fieldLabel: t("css_style") + " (float: left; margin:10px; ...)",
                name: "style",
                value: this.datax.style,
                width: 400
            }
        ];

        if (this.allowIndex) {
            standardSettings.push({
                xtype: "checkbox",
                fieldLabel: t("index"),
                name: "index",
                checked: this.datax.index
            });
        }

        this.layout = new Ext.Panel({
            bodyStyle: "padding: 10px;",
            items: [
                {
                    xtype: "form",
                    title: t("general_settings"),
                    bodyStyle: "padding: 10px;",
                    style: "margin: 10px 0 10px 0",
                    labelWidth: 140,
                    items: standardSettings
                },
                {
                    xtype: "form",
                    title: t("layout_settings"),
                    bodyStyle: "padding: 10px;",
                    style: "margin: 10px 0 10px 0",
                    labelWidth: 230,
                    items: layoutSettings
                },
                this.specificPanel/*,{
                 xtype: "form",
                 title: t("display_field_to_users"),
                 bodyStyle: "padding: 10px;",
                 style: "margin: 10px 0 10px 0",
                 items: [new Ext.ux.form.SuperField({
                 allowEdit: true,
                 name: "permissions",
                 values:this.datax.permissions,
                 stripeRows:false,
                 items: [
                 new Ext.form.ComboBox({
                 fieldLabel: t("username"),
                 name: "username",
                 triggerAction: 'all',
                 editable: false,
                 store: new Ext.data.JsonStore({
                 url: '/admin/user/get-all-users',
                 fields: ["username"],
                 root: "users"
                 }),
                 displayField: "username",
                 valueField: "username",
                 summaryDisplay:true
                 })
                 ]
                 })
                 ]
                 }*/
            ]/*,
             buttons: [{
             text: t("apply"),
             handler: this.applyData.bind(this)
             }]*/
        });

        this.layout.on("render", this.layoutRendered.bind(this));


        return this.layout;
    },

    layoutRendered: function (layout) {

        var items = this.layout.findBy(function() {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (items[i].name == "name") {
                items[i].on("keyup", this.updateName.bind(this));
                break;
            }
        }
    },

    updateName: function () {

        var items = this.layout.findBy(function() {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (items[i].name == "name") {
                this.treeNode.setText(items[i].getValue());
                break;
            }
        }
    },

    getData: function () {
        return this.datax;
    },

    applyData: function () {

        var items = this.layout.findBy(function() {
            return true;
        });

        for (var i = 0; i < items.length; i++) {
            if (typeof items[i].getValue == "function") {
                this.datax[items[i].name] = items[i].getValue();
            }
        }

        this.datax.fieldtype = this.getType();
        this.datax.datatype = "data";
    }
});
