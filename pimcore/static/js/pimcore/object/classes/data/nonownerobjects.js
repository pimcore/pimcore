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

pimcore.registerNS("pimcore.object.classes.data.nonownerobjects");
pimcore.object.classes.data.nonownerobjects = Class.create(pimcore.object.classes.data.data, {

    type: "nonownerobjects",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: false,
        fieldcollection: false,
        localizedfield: false
    },        

    initialize: function (treeNode, initData) {
        this.type = "nonownerobjects";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","noteditable","invisible","style"];

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
            valueField: 'text',
            displayField: 'text',
            listWidth: 'auto',
            fieldLabel: t('owner_class'),
            name: 'ownerClassName',
            value: this.datax.ownerClassName,
            disabled: this.isInCustomLayoutEditor(),
            forceSelection:true,
            listeners: {
                change: function(field, classNamevalue, oldValue) {
                    this.datax.ownerClassName=classNamevalue;
                }.bind(this)
            }

        });



        this.fieldComboStore = new Ext.data.JsonStore({
            url: '/admin/object-helper/grid-get-column-config',
            baseParams: {
                types: 'objects',
                name: this.datax.ownerClassName
            },
            root: "availableFields",
            fields: ['key', 'label'],
            disabled: this.isInCustomLayoutEditor(),
            autoLoad: false,
            forceSelection:true
        });


        this.fieldCombo = new Ext.form.ComboBox({
            fieldLabel: t('owner_field'),
//            name: 'objects' ,
            value: this.datax.ownerFieldName,
            store: this.fieldComboStore,
            listWidth: 'auto',
            displayField: 'key',
            valueField: 'key' ,
            lastQuery: '',
            name: 'ownerFieldName',
            disabled: this.isInCustomLayoutEditor(),
            listeners: {
                focus: function(){
                    if (this.datax.ownerClassName != null) {
                        this.fieldCombo.store.load({params:{name:this.datax.ownerClassName}});
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
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    remoteOwner: source.datax.remoteOwner
                });
        }
    }



});
