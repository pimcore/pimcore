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

pimcore.registerNS("pimcore.object.classes.data.user");
pimcore.object.classes.data.user = Class.create(pimcore.object.classes.data.data, {

    type: "user",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: true,
        block: true,
        encryptedField: true
    },

    initialize: function (treeNode, initData) {
        this.type = "user";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("user");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_user";
    },

    getLayout: function ($super) {
        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        var items = [],
            possibleOptions,
            roleStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: '/admin/user/role-tree-get-childs-by-id',
                reader: {
                    type: 'json'
                }
            },
            fields: ['id', 'text'],
            listeners: {
                load: function() {
                    if (datax.restrictTo) {
                        possibleOptions.setValue(datax.restrictTo);
                    }
                }.bind(this)
            }
        });

        roleStore.load();

        var options = {
            name: "restrictTo",
            triggerAction: "all",
            editable: false,
            fieldLabel: t("restrict_selection_to_roles"),
            store: roleStore,
            componentCls: "object_field",
            height: 200,
            width: 300,
            valueField: 'id',
            displayField: 'text',
            disabled: !inEncryptedField && this.isInCustomLayoutEditor()
        };

        possibleOptions = new Ext.ux.form.MultiSelect(options);
        items.push(possibleOptions);
        items.push(
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: datax.width
            }
        );

        return items;
    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }

            Ext.apply(
                this.datax,
                {
                    unique: source.datax.unique
                }
            );
        }
    },

    supportsUnique: function () {
        return true;
    }

});
