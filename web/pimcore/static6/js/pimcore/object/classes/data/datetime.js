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
        block: true
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

        this.defaultValue = new Ext.form.Hidden({
            xtype:"hidden",
            name:"defaultValue",
            value: this.datax.defaultValue
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


        if (this.datax.defaultValue) {
            var tmpDate;
            if(typeof this.datax.defaultValue === 'object'){
                tmpDate = this.datax.defaultValue;
            } else {
                tmpDate = new Date(this.datax.defaultValue * 1000);
            }

            date.value = tmpDate;
            time.value = Ext.Date.format(tmpDate, "H:i");
        }

        this.datefield = new Ext.form.DateField(date);
        this.timefield = new Ext.form.TimeField(time);

        this.datefield.addListener("change", this.setDefaultValue.bind(this));
        this.timefield.addListener("change", this.setDefaultValue.bind(this));

        if(this.datax.useCurrentDate){
            this.datefield.setDisabled(true);
            this.timefield.setDisabled(true);
        }

        this.component = new Ext.form.FieldSet({
            layout: 'hbox',
            title: t("default_value"),
            style: "border: none !important",
            combineErrors:false,
            items:[this.datefield, this.timefield],
            cls:"object_field"
        });


        this.specificPanel.removeAll();
        this.specificPanel.add([
            this.component,
            this.defaultValue,
            {
                xtype:"checkbox",
                fieldLabel:t("use_current_date"),
                name:"useCurrentDate",
                checked:this.datax.useCurrentDate,
                disabled: this.isInCustomLayoutEditor(),
                listeners:{
                    change:this.toggleDefaultDate.bind(this)
                }
            }, {
                xtype: "displayfield",
                hideLabel:true,
                html:'<span class="object_field_setting_warning">' +t('default_value_warning')+'</span>'
            }
        ]);

        return this.layout;
    },

    setDefaultValue:function () {

        if (this.datefield.getValue()) {
            var dateString = Ext.Date.format(this.datefield.getValue(), "Y-m-d");

            if (this.timefield.getValue()) {
                dateString += " " + Ext.Date.format(this.timefield.getValue(), "H:i");
            }
            else {
                dateString += " 00:00";
            }

            this.defaultValue.setValue((Ext.Date.parseDate(dateString, "Y-m-d H:i").getTime())/1000);

        } else {
            this.defaultValue.setValue(null);
        }
    },

    toggleDefaultDate:function (checkbox, checked) {
            if (checked) {
                this.datefield.setValue(null);
                this.timefield.setValue(null);
                this.defaultValue.setValue(null);
                this.datefield.setDisabled(true);
                this.timefield.setDisabled(true);
            } else {
                this.datefield.enable();
                this.timefield.enable();
            }


    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    defaultValue: source.datax.defaultValue,
                    useCurrentDate: source.datax.useCurrentDate
                });
        }
    }

});
