/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        localizedfield: true,
        classificationstore : true,
        block: true
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
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "numberfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            }
        ]);

        var countryProxy = {
            type: 'ajax',
            url:'/admin/settings/get-available-countries',
            reader: {
                type: 'json',
                rootProperty: 'data'
            }
        };

        var countryStore = new Ext.data.Store({
            proxy:countryProxy,
            fields: [
                {name:'key'},
                {name:'value'}
            ],
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
            componentCls: "object_field",
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
