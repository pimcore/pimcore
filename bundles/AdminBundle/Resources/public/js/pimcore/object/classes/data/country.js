/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
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
        localizedfield: true,
        classificationstore : true,
        block: true,
        encryptedField: true
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
        var specificItems = this.getSpecificPanelItems(this.datax, false);

        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {

        var countryProxy = {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_settings_getavailablecountries'),
            reader: {
                type: 'json',
                rootProperty: 'data'
            }
        };

        var possibleOptions;

        var countryStore = new Ext.data.Store({
            proxy:countryProxy,
            fields: [
                {name:'key'},
                {name:'value'}
            ],
            listeners: {
                load: function() {
                    if (datax.restrictTo) {
                        possibleOptions.setValue(datax.restrictTo);
                    }
                }.bind(this)
            }
        });

        countryStore.load();

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
            displayField: 'key',
            disabled: !inEncryptedField && this.isInCustomLayoutEditor()
        };

        possibleOptions = new Ext.ux.form.MultiSelect(options);
        return [possibleOptions];
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
