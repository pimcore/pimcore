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

Ext.define('pimcore.object.helpers.metadataMultiselectEditor', {
    extend: 'Ext.grid.CellEditor',

    constructor: function(config) {
        this.callParent(arguments);
    },

    initComponent: function() {
        this.callParent();
    },

    getValue: function() {
        return Math.random();
    },

    startEdit: function(el, value, /* private: false means don't focus*/
                        doFocus) {


        Ext.WindowManager.each(function(window, idx, length) {
            window.destroy();

        });

        value = Ext.clone(value);

        var fieldConfig = this.config.fieldInfo;


        var selectData = [];
        if (fieldConfig.value) {
            var selectDataRaw = fieldConfig.value.split(";");
            for (var j = 0; j < selectDataRaw.length; j++) {
                selectData.push([selectDataRaw[j], ts(selectDataRaw[j])]);
            }
        }

        var store = new Ext.data.ArrayStore({
            fields: [
                'id',
                'label'
            ],
            data: selectData
        });

        this.context = this.editingPlugin.context;

        var options = {
            triggerAction: "all",
            editable: false,
            store: store,
            componentCls: "object_field",
            height: '100%',
            valueField: 'id',
            displayField: 'label',
            value: value
        };

        var multiselect = Ext.create('Ext.ux.form.MultiSelect', options);

        this.editWin = new Ext.Window({
            modal: false,
            layout : 'fit',
            title: fieldConfig.label ? fieldConfig.label : fieldConfig.key,
            items: [multiselect],
            bodyStyle: "background: #fff;",
            width: 700,
            maxHeight: 600,
            listeners:{
                close:function(){
                    this.cancelEdit(false);
                }.bind(this)
            },
            buttons: [
                {
                    text: t("save"),
                    iconCls: 'pimcore_icon_save',
                    handler: function() {
                        var newValue = multiselect.getValue();

                        this.setValue(newValue);
                        this.completeEdit(false);
                        this.editWin.close();
                    }.bind(this)
                },
                {
                    text: t("cancel"),
                    iconCls: 'pimcore_icon_cancel',
                    handler: function() {
                        this.cancelEdit(false);
                        this.editWin.close();
                    }.bind(this)
                }
            ]
        });
        this.editWin.show();
        this.editWin.updateLayout();
    },

    getValue: function() {
        return this.value;
    },

    setValue: function(value) {
        this.value = value;
    },

    completeEdit: function(remainVisible) {
        var me = this,
            field = me.field,
            startValue = me.startValue,
            value;

        value = me.getValue();

        if (me.fireEvent('beforecomplete', me, value, startValue) !== false) {
            // Grab the value again, may have changed in beforecomplete
            value = me.getValue();
            if (me.updateEl && me.boundEl) {
                me.boundEl.setHtml(value);
            }
            me.onEditComplete(remainVisible);
            me.fireEvent('complete', me, value, startValue);
        }
    },

    destroy: function() {
        if (this.editWin) {
            this.editWin.destroy();
        }
        this.callParent(arguments);
    }
});