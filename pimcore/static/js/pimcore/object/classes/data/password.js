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
        localizedfield: true
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
		
        var algorithmsProxy = new Ext.data.HttpProxy({
            url:'/admin/settings/get-available-algorithms'
        });
        
        var algorithmsReader = new Ext.data.JsonReader({
            totalProperty:'total',
            successProperty:'success',
            root: "data",
            fields: [
                {name:'key'},
                {name:'value'}
            ]
        });
        
        this.algorithmsStore = new Ext.data.Store({
            proxy:algorithmsProxy,
            reader:algorithmsReader,
            listeners: {
	            load: function() {
	                if (this.datax.restrictTo) {
	                    this.possibleOptions.setValue(this.datax.restrictTo);
	                }
	            }.bind(this)
            }
        });
        
        
        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "spinnerfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
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
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: 'textfield',
                fieldLabel: t("salt"),
                width: 300,
                itemId: "salt",
                name: "salt",
                value: this.datax.salt,
                emptyText: '',
                disabled: this.isInCustomLayoutEditor()
            },
            {
                xtype: "combo",
                width: 300,
                fieldLabel: t("saltlocation"),
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
            }

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
