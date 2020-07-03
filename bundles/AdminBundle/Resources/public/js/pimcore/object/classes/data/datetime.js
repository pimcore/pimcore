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

pimcore.registerNS("pimcore.object.classes.data.datetime");
pimcore.object.classes.data.datetime = Class.create(pimcore.object.classes.data.data, {

    type:"datetime",
    /**
     * define where this datatype is allowed
     */
    allowIn:{
        object:true,
        objectbrick:true,
        fieldcollection:true,
        localizedfield:true,
        classificationstore : true,
        block: true,
        encryptedField: true
    },

    initialize:function (treeNode, initData) {
        this.type = "datetime";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getGroup:function () {
        return "date";
    },


    getTypeName:function () {
        return t("datetime");
    },

    getIconClass:function () {
        return "pimcore_icon_datetime";
    },

    getLayout:function ($super) {

        $super();

        this.specificPanel.removeAll();

        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        var specificItems = [];

        var defaultValue = new Ext.form.Hidden({
            xtype:"hidden",
            name:"defaultValue",
            value: datax.defaultValue
        });

        var date = {
            cls:"object_field",
            width:300
        };

        var time = {
            format:"H:i",
            emptyText:"",
            width:120
        };


        if (datax.defaultValue) {
            var tmpDate;
            if(typeof datax.defaultValue === 'object'){
                tmpDate = datax.defaultValue;
            } else {
                tmpDate = new Date(datax.defaultValue * 1000);
            }

            date.value = tmpDate;
            time.value = Ext.Date.format(tmpDate, "H:i");
        }

        var datefield = new Ext.form.DateField(date);
        var timefield = new Ext.form.TimeField(time);

        datefield.addListener("change", this.setDefaultValue.bind(this, defaultValue, datefield, timefield));
        timefield.addListener("change", this.setDefaultValue.bind(this, defaultValue, datefield, timefield));

        if(datax.useCurrentDate){
            datefield.setDisabled(true);
            timefield.setDisabled(true);
        }

        var defaultComponent = new Ext.form.FieldSet({
            layout: 'hbox',
            title: t("default_value"),
            style: "border: none !important",
            combineErrors:false,
            items:[datefield, timefield],
            cls:"object_field"
        });

        var columnTypeData = [["bigint(20)", "BIGINT"], ["datetime", "DATETIME"]];

        var columnTypeField = new Ext.form.ComboBox({
            name: "columnType",
            mode: 'local',
            autoSelect: true,
            forceSelection: true,
            editable: false,
            fieldLabel: t("column_type"),
            value: datax.columnType != "bigint(20)" && datax.columnType != "datetime" ? 'bigint(20)' : datax.columnType ,
            store: new Ext.data.ArrayStore({
                fields: [
                    'id',
                    'label'
                ],
                data: columnTypeData
            }),
            triggerAction: 'all',
            valueField: 'id',
            displayField: 'label'
        });


        specificItems = specificItems.concat(
            [
                defaultComponent,
                defaultValue,
                {
                    xtype: 'textfield',
                    width: 600,
                    fieldLabel: t("default_value_generator"),
                    labelWidth: 140,
                    name: 'defaultValueGenerator',
                    value: datax.defaultValueGenerator
                },
                {
                    xtype:"checkbox",
                    fieldLabel:t("use_current_date"),
                    name:"useCurrentDate",
                    checked:datax.useCurrentDate,
                    disabled: this.isInCustomLayoutEditor(),
                    listeners:{
                        change:this.toggleDefaultDate.bind(this, datefield, timefield)
                    }
                }, {
                xtype: "displayfield",
                hideLabel:true,
                html:'<span class="object_field_setting_warning">' +t('inherited_default_value_warning')+'</span>'
            },
                columnTypeField
            ]);


        return specificItems;

    },

    setDefaultValue:function (defaultValue, datefield, timefield) {

        if (datefield.getValue()) {
            var dateString = Ext.Date.format(datefield.getValue(), "Y-m-d");

            if (timefield.getValue()) {
                dateString += " " + Ext.Date.format(timefield.getValue(), "H:i");
            }
            else {
                dateString += " 00:00";
            }

            defaultValue.setValue((Ext.Date.parseDate(dateString, "Y-m-d H:i").getTime())/1000);

        } else {
            defaultValue.setValue(null);
        }
    },

    toggleDefaultDate:function (datefield, timefield, checkbox, checked) {
            if (checked) {
                datefield.setValue(null);
                timefield.setValue(null);
                defaultValue.setValue(null);
                datefield.setDisabled(true);
                timefield.setDisabled(true);
            } else {
                datefield.enable();
                timefield.enable();
            }


    },

    applyData: function ($super) {
        $super();
        this.datax.queryColumnType = this.datax.columnType;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    defaultValue: source.datax.defaultValue,
                    useCurrentDate: source.datax.useCurrentDate,
                    defaultValueGenerator: source.datax.defaultValueGenerator,
                    columnType: source.datax.columnType
                });
        }
    }

});
