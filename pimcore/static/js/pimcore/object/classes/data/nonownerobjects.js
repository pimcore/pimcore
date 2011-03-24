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

pimcore.registerNS("pimcore.object.classes.data.nonownerobjects");
pimcore.object.classes.data.nonownerobjects = Class.create(pimcore.object.classes.data.data, {

    type: "nonownerobjects",
    allowIndex: false,

    initialize: function (treeNode, initData) {
        this.type = "nonownerobjects";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getGroup: function () {
        return "relation";
    },

    getTypeName: function () {
        return t("nonownerobjects");
    },

    getIconClass: function () {
        return "pimcore_icon_object";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();

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





        this.classCombo = new Ext.form.ComboBox({
            typeAhead: true,
            triggerAction: 'all',
            store: pimcore.globalmanager.get("object_types_store"),
            valueField: 'id',
            displayField: 'text',
            listWidth: 'auto',
            fieldLabel: t('owner_class'),
            name: 'ownerClassId',
            value: this.datax.ownerClassId,
            forceSelection:true,
            listeners: {
                change: function(field, classNamevalue, oldValue) {
                    this.datax.ownerClassId=classNamevalue;
                }.bind(this)
            }

        });

          console.log(this.datax);
        this.fieldComboStore = new Ext.data.JsonStore({
            url: '/admin/object/grid-get-column-config',
            baseParams: {
                types: 'objects',
                id: this.datax.ownerClassId
            },
            fields: ['key', 'label',],
            autoLoad: false,
            forceSelection:true
        });


        this.fieldCombo = new Ext.form.ComboBox({
            fieldLabel: t('owner_field'),
            name: 'objects' ,
            value: this.datax.ownerFieldName,
            store: this.fieldComboStore,
            listWidth: 'auto',
            displayField: 'key',
            valueField: 'key' ,
            lastQuery: '',
            name: 'ownerFieldName',
            listeners: {
                focus: function(){
                    if (this.datax.ownerClassId != null) {
                        this.fieldCombo.store.load({params:{id:this.datax.ownerClassId}});
                    }   
                }.bind(this) 
            }
        });



        this.specificPanel.add(this.classCombo);
        this.specificPanel.add(this.fieldCombo);

        this.specificPanel.add(new Ext.form.DisplayField({
            hideLabel: true,
            value: t('non_owner_description'),
            cls: "pimcore_extra_label_bottom"
        }));

        return this.layout;
    }



});
