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

pimcore.registerNS("pimcore.object.classes.data.password");
pimcore.object.classes.data.password = Class.create(pimcore.object.classes.data.data, {

    type: "password",
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
	statics : {
		CONFIG_DATA : [
			['front', 'Front'],
			['back', 'Back']
		]
	},
	algorithmsStore: {},

    initialize: function (treeNode, initData) {
        this.type = "password";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","noteditable","invisible","visibleGridView",
                                        "visibleSearch","index","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("password");
    },

    getGroup: function () {
        return "text";
    },

    getIconClass: function () {
        return "pimcore_icon_password";
    },

    getLayout: function ($super) {

        $super();

        var algorithmsProxy = {
            type: 'ajax',
            url:'/admin/settings/get-available-algorithms',
            reader: {
                type: 'json',
                totalProperty:'total',
                successProperty:'success',
                rootProperty: "data"
            }
        }

        this.algorithmsStore = new Ext.data.Store({
            proxy: algorithmsProxy,
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

        var saltCombo = new Ext.form.field.ComboBox({
            xtype: "combo",
            width: 300,
            fieldLabel: t("saltlocation"),
            hidden: this.datax.algorithm == "password_hash",
            itemId: "saltlocation",
            name: "saltlocation",
            value: this.datax.saltlocation || 'back',
            triggerAction: 'all',
            lazyRender:true,
            mode: 'local',
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'value',
                    'key'
                ],
                data: this.statics.CONFIG_DATA
            }),
            valueField: 'value',
            displayField: 'key',
            disabled: this.isInCustomLayoutEditor()
        });

        var salt =  new Ext.form.field.Text({
            xtype: 'textfield',
            fieldLabel: t("salt"),
            hidden: this.datax.algorithm == "password_hash",
            width: 300,
            itemId: "salt",
            name: "salt",
            value: this.datax.salt,
            emptyText: '',
            disabled: this.isInCustomLayoutEditor()
        });

        var algorithmsCombo = new Ext.form.field.ComboBox({
            xtype: "combo",
            width: 300,
            fieldLabel: t("algorithm"),
            itemId: "algorithm",
            name: "algorithm",
            value: this.datax.algorithm || 'md5',
            triggerAction: 'all',
            lazyRender:true,
            mode: 'local',
            store: this.algorithmsStore,
            valueField: 'value',
            displayField: 'key',
            editable: false,
            disabled: this.isInCustomLayoutEditor(),
            listeners: {
                select: function (combo, record, index) {
                    if (record.data.key == "password_hash") {
                        saltCombo.hide();
                        salt.hide();

                    } else {
                        saltCombo.show();
                        salt.show();
                    }

                }.bind(this)
            }
        });


        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "numberfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            algorithmsCombo,
            salt,
            saltCombo

        ]);

        this.algorithmsStore.load();

        return this.layout;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    algorithm: source.datax.algorithm,
                    salt: source.datax.salt,
                    saltlocation: source.datax.saltlocation
                });
        }
    }

});
