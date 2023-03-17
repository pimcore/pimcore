/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.object.classes.data.password");
/**
 * @private
 */
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

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "textfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('width_explanation')
            }
        ]);

        if(!this.isInCustomLayoutEditor()) {
            const algorithmsProxy = {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_settings_getavailablealgorithms'),
                reader: {
                    type: 'json',
                    totalProperty: 'total',
                    successProperty: 'success',
                    rootProperty: "data"
                }
            }

            this.algorithmsStore = new Ext.data.Store({
                proxy: algorithmsProxy,
                fields: [
                    {name: 'key'},
                    {name: 'value'}
                ],
                listeners: {
                    load: function () {
                        if (this.datax.restrictTo) {
                            this.possibleOptions.setValue(this.datax.restrictTo);
                        }
                    }.bind(this)
                }
            });

            const saltCombo = new Ext.form.field.ComboBox({
                xtype: "combo",
                width: 300,
                fieldLabel: t("saltlocation"),
                hidden: this.datax.algorithm == "password_hash",
                itemId: "saltlocation",
                name: "saltlocation",
                value: this.datax.saltlocation || 'back',
                triggerAction: 'all',
                lazyRender: true,
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

            const salt = new Ext.form.field.Text({
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

            const handleSaltFieldsVisibility = function (algorithm) {
                if (algorithm == "password_hash") {
                    saltCombo.hide();
                    salt.hide();
                } else {
                    saltCombo.show();
                    salt.show();
                }
            };

            const algorithmsCombo = new Ext.form.field.ComboBox({
                xtype: "combo",
                width: 300,
                fieldLabel: t("algorithm"),
                itemId: "algorithm",
                name: "algorithm",
                value: this.datax.algorithm || 'password_hash',
                triggerAction: 'all',
                lazyRender: true,
                mode: 'local',
                store: this.algorithmsStore,
                valueField: 'value',
                displayField: 'key',
                editable: false,
                listeners: {
                    select: function (combo, record, index) {
                        handleSaltFieldsVisibility(record.data.key);
                    }.bind(this),

                    render: function (combo) {
                        handleSaltFieldsVisibility(combo.getValue());
                    }.bind(this)
                }
            });


            this.specificPanel.add([
                {
                    xtype: "numberfield",
                    fieldLabel: t("min_length"),
                    name: "minimumLength",
                    minValue: 0,
                    value: this.datax.minimumLength
                },
                algorithmsCombo,
                salt,
                saltCombo

            ]);

            this.algorithmsStore.load();
        }

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
                    minimumLength: source.datax.minimumLength,
                    algorithm: source.datax.algorithm,
                    salt: source.datax.salt,
                    saltlocation: source.datax.saltlocation
                });
        }
    }

});
