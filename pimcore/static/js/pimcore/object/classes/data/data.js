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

    invalidFieldNames: false,
    forbiddenNames: [
                "id","key","path","type","index","classname","creationdate","userowner","value","class","list","fullpath",
                "childs","values","cachetag","cachetags","parent","published","valuefromparent","userpermissions",
                "dependencies","modificationdate","usermodification","byid","bypath","data","versions","properties",
                "permissions","permissionsforuser","childamount","apipluginbroker","resource","parentClass","definition",
                "locked","language","omitmandatorycheck", "idPath"
            ],

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: false,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false
    },


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

        // per default all settings are available
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible","visibleGridView","visibleSearch","index","style"];
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
                maxLength: 70,
                autoCreate: {tag: 'input', type: 'text', maxlength: '70', autocomplete: 'off'},
                enableKeyEvents: true,
                value: this.datax.name,
                disabled: !in_array("name",this.availableSettingsFields)
            },
            {
                xtype: "textfield",
                fieldLabel: t("title") + " (" + t("label") + ")",
                name: "title",
                width: 300,
                value: this.datax.title,
                disabled: !in_array("title",this.availableSettingsFields)
            },
            {
                xtype: "textarea",
                fieldLabel: t("tooltip"),
                name: "tooltip",
                width: 300,
                height: 100,
                value: this.datax.tooltip,
                disabled: !in_array("tooltip",this.availableSettingsFields)
            },
            {
                xtype: "checkbox",
                fieldLabel: t("mandatoryfield"),
                name: "mandatory",
                checked: this.datax.mandatory,
                disabled: !in_array("mandatory",this.availableSettingsFields)
            },
            {
                xtype: "checkbox",
                fieldLabel: t("not_editable"),
                name: "noteditable",
                checked: this.datax.noteditable,
                disabled: !in_array("noteditable",this.availableSettingsFields)
            },
            {
                xtype: "checkbox",
                fieldLabel: t("invisible"),
                name: "invisible",
                checked: this.datax.invisible,
                disabled: !in_array("invisible",this.availableSettingsFields)
            },
            {
                xtype: "checkbox",
                fieldLabel: t("visible_in_gridview"),
                name: "visibleGridView",
                checked: this.datax.visibleGridView,
                disabled: !in_array("visibleGridView",this.availableSettingsFields)
            },
            {
                xtype: "checkbox",
                fieldLabel: t("visible_in_searchresult"),
                name: "visibleSearch",
                checked: this.datax.visibleSearch,
                disabled: !in_array("visibleSearch",this.availableSettingsFields)
            },{
                xtype: "checkbox",
                fieldLabel: t("index"),
                name: "index",
                checked: this.datax.index,
                disabled: !in_array("index",this.availableSettingsFields)
            }
        ];

        var layoutSettings = [
            {
                xtype: "textfield",
                fieldLabel: t("css_style") + " (float: left; margin:10px; ...)",
                name: "style",
                value: this.datax.style,
                width: 400,
                disabled: !in_array("style",this.availableSettingsFields)
            }
        ];

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
                this.specificPanel
            ]
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

    isValid: function () {


        var data = this.getData();
        data.name = trim(data.name);
        var regresult = data.name.match(/[a-zA-Z0-9_]+/);

        if (data.name.length > 1 && regresult == data.name && in_array(data.name.toLowerCase(), this.forbiddenNames) == false) {
            return true;
        }

        if(in_array(data.name.toLowerCase(), this.forbiddenNames)==true){
            this.invalidFieldNames = true;
        }
        return false;
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
