/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        localizedfield:true
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
        return "pimcore_icon_date";
    },

    getLayout:function ($super) {

        $super();

        this.defaultValue = new Ext.form.Hidden({
            xtype:"hidden",
            name:"defaultValue",
            value: this.datax.defaultValue
        });

        var date = {

            itemCls:"object_field",
            width:100
        };

        var time = {
            format:"H:i",
            emptyText:"",
            width:60
        };


        if (this.datax.defaultValue) {
            var tmpDate;
            if(typeof this.datax.defaultValue === 'object'){
                tmpDate = this.datax.defaultValue;
            } else {
                tmpDate = new Date(this.datax.defaultValue * 1000);
            }

            date.value = tmpDate;
            time.value = tmpDate.format("H:i");
        }

        this.datefield = new Ext.form.DateField(date);
        this.timefield = new Ext.form.TimeField(time);

        this.datefield.addListener("change", this.setDefaultValue.bind(this));
        this.timefield.addListener("change", this.setDefaultValue.bind(this));

        if(this.datax.useCurrentDate){
            this.datefield.setDisabled(true);
            this.timefield.setDisabled(true);
        }

        this.component = new Ext.form.CompositeField({
            xtype:'compositefield',
            fieldLabel:t("default_value"),
            combineErrors:false,
            items:[this.datefield, this.timefield],
            itemCls:"object_field"
        });


        this.specificPanel.removeAll();
        this.specificPanel.add([
            this.component,
            this.defaultValue,
            {
                xtype:"checkbox",
                fieldLabel:t("use_current_date"),
                name:"useCurrentDate",
                value:this.datax.defaultValue,
                checked:this.datax.useCurrentDate,
                disabled: this.isInCustomLayoutEditor(),
                listeners:{
                    check:this.toggleDefaultDate.bind(this)
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
            var dateString = this.datefield.getValue().format("Y-m-d");

            if (this.timefield.getValue()) {
                dateString += " " + this.timefield.getValue();
            }
            else {
                dateString += " 00:00";
            }

            this.defaultValue.setValue((Date.parseDate(dateString, "Y-m-d H:i").getTime())/1000);

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
