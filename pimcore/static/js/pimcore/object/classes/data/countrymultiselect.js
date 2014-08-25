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

pimcore.registerNS("pimcore.object.classes.data.countrymultiselect");
pimcore.object.classes.data.countrymultiselect = Class.create(pimcore.object.classes.data.multiselect, {

    type: "countrymultiselect",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "countrymultiselect";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("countrymultiselect");
    },

    getIconClass: function () {
        return "pimcore_icon_countrymultiselect";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "spinnerfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            }
        ]);

        var countryProxy = new Ext.data.HttpProxy({
            url:'/admin/settings/get-available-countries'
        });
        var countryReader = new Ext.data.JsonReader({
            totalProperty:'total',
            successProperty:'success',
            root: "data",
            fields: [
                {name:'key'},
                {name:'value'}
            ]
        });

        var countryStore = new Ext.data.Store({
            proxy:countryProxy,
            reader:countryReader,
            listeners: {
                load: function() {
                    if (this.datax.restrictTo) {
                        this.possibleOptions.setValue(this.datax.restrictTo);
                    }
                }.bind(this)
            }
        });

        var options = {
            name: "restrictTo",
            triggerAction: "all",
            editable: false,
            fieldLabel: t("restrict_selection_to"),
            store: countryStore,
            itemCls: "object_field",
            height: 200,
            width: 300,
            valueField: 'value',
            displayField: 'key'
        };
        if (this.isInCustomLayoutEditor()) {
            options.disabled = true;
        }

        this.possibleOptions = new Ext.ux.form.MultiSelect(options);

        this.specificPanel.add(this.possibleOptions);
        countryStore.load();



        return this.layout;
    },

    applyData: function ($super) {
        $super();
        delete this.datax.options;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    restrictTo: source.datax.restrictTo
                });
        }
    }

});
