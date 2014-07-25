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

pimcore.registerNS("pimcore.object.classes.data.country");
pimcore.object.classes.data.country = Class.create(pimcore.object.classes.data.data, {

    type: "country",
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
        this.type = "country";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("country");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_country";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();

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
            displayField: 'key',
            disabled: this.isInCustomLayoutEditor()
        };

        this.possibleOptions = new Ext.ux.form.MultiSelect(options);

        this.specificPanel.add(this.possibleOptions);
        countryStore.load();

        return this.layout;
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
