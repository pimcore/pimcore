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

Ext.define('pimcore.object.helpers.gridCellEditor', {
    extend: 'Ext.grid.CellEditor',

    constructor: function(config) {
        console.log("constructor");
        // this.config = config;
        this.callParent(arguments);
    },

    initComponent: function() {
        console.log("init");
        this.callParent();
    },

    getValue: function() {
        return Math.random();
    },

    startEdit: function(el, value, /* private: false means don't focus*/
                        doFocus) {


        var fieldInfo = this.config.fieldInfo;

        if(!fieldInfo || !fieldInfo.layout) {
            return;
        }

        if(fieldInfo.layout.noteditable) {
            Ext.MessageBox.alert(t('error'), t('this_element_cannot_be_edited'));
            return;
        }

        this.context = this.editingPlugin.context;
        // this.callParent(arguments);

        var tagType = fieldInfo.layout.fieldtype;

        var tag = new pimcore.object.tags[tagType](value, fieldInfo.layout);

        var formPanel = Ext.create('Ext.form.Panel', {
            xtype: "form",
            border: false,
            items: [tag.getLayoutEdit()],
            bodyStyle: "padding: 10px;",
            buttons: [
                {
                    text: t("save"),
                    iconCls: 'pimcore_icon_save',
                    handler: function() {
                        var newValue = tag.getCellEditValue();
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
        this.editWin = new Ext.Window({
            modal: false,
            title: t("edit"),
            items: [formPanel],
            bodyStyle: "background: #fff;",
            width: 700,
            maxHeight: 600,
            listeners:{
                close:function(){
                    this.cancelEdit(false);
                }.bind(this)
            }
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
    }

});