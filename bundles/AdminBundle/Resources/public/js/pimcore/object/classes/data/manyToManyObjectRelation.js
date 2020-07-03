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

pimcore.registerNS("pimcore.object.classes.data.manyToManyObjectRelation");
pimcore.object.classes.data.manyToManyObjectRelation = Class.create(pimcore.object.classes.data.data, {

    type: "manyToManyObjectRelation",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : false,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "manyToManyObjectRelation";

        this.initData(initData);

        pimcore.helpers.sanitizeAllowedTypes(this.datax, "classes");

        // overwrite default settings
        this.availableSettingsFields = ["name","title","tooltip","mandatory","noteditable","invisible",
            "visibleGridView","visibleSearch","style"];

        this.treeNode = treeNode;
    },

    getGroup: function () {
        return "relation";
    },

    getTypeName: function () {
        return t("many_to_many_object_relation");
    },

    getIconClass: function () {
        return "pimcore_icon_manyToManyObjectRelation";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();

        this.uniqeFieldId = uniqid();

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
            },{
                xtype: "numberfield",
                fieldLabel: t("maximum_items"),
                name: "maxItems",
                value: this.datax.maxItems,
                disabled: this.isInCustomLayoutEditor(),
                minValue: 0
            },
            {
                xtype: 'textfield',
                width: 600,
                fieldLabel: t("path_formatter_service"),
                name: 'pathFormatterClass',
                value: this.datax.pathFormatterClass
            }
        ]);

        var classes = [];
        if(typeof this.datax.classes == "object") {
            // this is when it comes from the server
            for(var i=0; i<this.datax.classes.length; i++) {
                classes.push(this.datax.classes[i]);
            }
        } else if(typeof this.datax.classes == "string") {
            // this is when it comes from the local store
            classes = this.datax.classes.split(",");
        }

        var classesStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_dataobject_class_gettree')
            },
            autoDestroy: true,
            fields: ["text"]
        });
        classesStore.load({
            "callback": function (classes, success) {
                if (success) {
                    Ext.getCmp('class_allowed_object_classes_' + this.uniqeFieldId).setValue(classes.join(","));
                }
            }.bind(this, classes)
        });


        this.specificPanel.add(new Ext.ux.form.MultiSelect({
            fieldLabel: t("allowed_classes"),
            id: "class_allowed_object_classes_" + this.uniqeFieldId,
            name: "classes",
            value: classes.join(","),
            displayField: "text",
            valueField: "text",
            store: classesStore,
            width: 600,
            disabled: this.isInCustomLayoutEditor(),
            listeners: {
                change: function(field, classNameValue, oldValue) {
                    this.datax.allowedClassId = classNameValue;
                    if (classNameValue != null) {
                        var submitValue = classNameValue.join(',');
                        this.fieldStore.load({params:{classes:submitValue}});
                    }
                }.bind(this)
            }
        }));

        this.fieldStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_dataobject_dataobjecthelper_getavailablevisiblefields'),
                extraParams: {
                    // no_brick_columns: "true",
                    // gridtype: 'all',
                    classes: classes
                },
                reader: {
                    type: 'json',
                    rootProperty: "availableFields"
                }
            },
            fields: ['key', 'label'],
            autoLoad: false,
            forceSelection:true,
            listeners: {
                load: function() {
                    this.fieldSelect.setValue(this.datax.visibleFields);
                }.bind(this)

            }
        });
        this.fieldStore.load();

        this.fieldSelect = new Ext.ux.form.MultiSelect({
            name: "visibleFields",
            triggerAction: "all",
            editable: false,
            fieldLabel: t("objectsMetadata_visible_fields"),
            store: this.fieldStore,
            value: this.datax.visibleFields,
            displayField: "key",
            valueField: "key",
            width: 400,
            height: 300
        });
        this.specificPanel.add(this.fieldSelect);

        if(this.context == 'class') {
            this.specificPanel.add({
                xtype: "checkbox",
                boxLabel: t("allow_to_create_new_object"),
                name: "allowToCreateNewObject",
                value: this.datax.allowToCreateNewObject
            });
            this.specificPanel.add({
                xtype: "checkbox",
                boxLabel: t("enable_admin_async_load"),
                name: "optimizedAdminLoading",
                value: this.datax.optimizedAdminLoading
            });
            this.specificPanel.add({
                xtype: "displayfield",
                hideLabel: true,
                value: t('async_loading_warning_block'),
                cls: "pimcore_extra_label_bottom"
            });
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
                    height: source.datax.height,
                    maxItems: source.datax.maxItems,
                    relationType: source.datax.relationType,
                    remoteOwner: source.datax.remoteOwner,
                    classes: source.datax.classes,
                    visibleFields: source.datax.visibleFields,
                    optimizedAdminLoading: source.datax.optimizedAdminLoading,
                    pathFormatterClass: source.datax.pathFormatterClass,
                    allowToCreateNewObject: source.datax.allowToCreateNewObject
                });
        }
    }

});

// @TODO BC layer, to be removed in v7.0
pimcore.object.classes.data.objects = pimcore.object.classes.data.manyToManyObjectRelation;
