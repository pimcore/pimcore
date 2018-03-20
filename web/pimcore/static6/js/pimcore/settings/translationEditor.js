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

Ext.define('pimcore.settings.translationEditor', {
    extend: 'Ext.grid.CellEditor',

    constructor: function (config) {
        this.callParent(arguments);
    },

    initComponent: function () {
        this.callParent();
    },

    getValue: function () {
        return Math.random();
    },

    startEdit: function (el, value, /* private: false means don't focus*/
                         doFocus) {

        Ext.WindowManager.each(function(window, idx, length) {
            window.destroy();

        });

        this.oldValue = Ext.clone(value);
        this.setValue(value);

        var outerTitle = this.config.__outerTitle;
        var innerTitle = this.config.__innerTitle;
        var editorType = this.config.__editorType;

        if (editorType == "plain") {
            this.textarea = new Ext.form.TextArea({
                width: '100%',
                height: '100%',
                value: value,
                grow: true
            });

            this.component = new Ext.Panel({
                title: innerTitle,
                items: [this.textarea],
                autoScroll: true,
                layout: 'fit'
            });


        } else {
            this.editableDivId = "translationeditor_" + uniqid();

            var html = '<div class="pimcore_tag_wysiwyg" id="' + this.editableDivId + '" contenteditable="true">' + this.oldValue + '</div>';
            var pConf = {
                title: innerTitle,
                html: html,
                border: true,
                style: "margin-bottom: 10px",
                height: '100%',
                autoScroll: true
            };

            this.component = new Ext.Panel(pConf);

            this.component.on("beforedestroy", function () {
                    if (this.ckeditor) {
                        this.ckeditor.destroy();
                        this.ckeditor = null;
                    }
                }
            );

            this.component.on("afterlayout", this.initCkEditor.bind(this));
        }

        this.context = this.editingPlugin.context;

        // arguments[2]= true;
        // this.callParent(arguments);


        var fConfig = {
            border: false,
            items: [this.component]
            ,
            bodyStyle: "padding: 10px;"
        };



        var formPanel = Ext.create('Ext.form.Panel', fConfig);

        this.editWin = new Ext.Window({
            modal: true,
            title: outerTitle,
            items: [formPanel],
            bodyStyle: "background: #fff;",
            width: 700,
            maxHeight: 600,
            layout: 'fit',
            autoScroll: true,
            preventRefocus: true,      // nasty hack because this is an internal property
                                       // for html grid cell values with hrefs this prevents that the cell
                                       // gets refocused which would then trigger another editor window
                                       // upon close of this instance
            listeners: {
                close: function () {
                    this.cancelEdit(false);
                }.bind(this)
            },
            buttons: [
                {
                    text: t("save"),
                    iconCls: 'pimcore_icon_save',
                    handler: function () {
                        if (editorType == "plain") {
                            newValue = this.textarea.getValue();
                        } else {
                            var newValue = this.oldValue;
                            try {
                                if (this.ckeditor) {
                                    newValue = this.ckeditor.getData();
                                }
                            }
                            catch (e) {
                            }
                        }

                        this.setValue(newValue);
                        this.completeEdit(false);
                        this.editWin.close();
                    }.bind(this)
                },
                {
                    text: t("cancel"),
                    iconCls: 'pimcore_icon_cancel',
                    handler: function () {
                        this.cancelEdit(false);
                        this.editWin.close();
                    }.bind(this)
                }
            ]
        });


        this.editWin.show();
        this.editWin.updateLayout();
    },

    getValue: function () {
        return this.value;
    },

    setValue: function (value) {
        this.value = value;
    },

    completeEdit: function (remainVisible) {
        var me = this,
            field = me.field,
            fieldInfo = me.config.fieldInfo,
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

    destroy: function () {
        if (this.editWin) {
            this.editWin.destroy();
        }
        this.callParent(arguments);
    },

    initCkEditor: function () {

        if (this.ckeditor) {
            return;
        }

        var eConfig = {};

        eConfig.toolbarGroups = [
            {name: 'clipboard', groups: ['sourcedialog', 'clipboard', 'undo', 'find']},
            {name: 'basicstyles', groups: ['basicstyles', 'list']},
            '/',
            {name: 'paragraph', groups: ['align', 'indent']},
            {name: 'blocks'},
            {name: 'links'},
            {name: 'insert'},
            '/',
            {name: 'styles'},
            {name: 'tools', groups: ['colors', 'tools', 'cleanup', 'mode', 'others']}
        ];

        //prevent override important settings!
        eConfig.resize_enabled = false;
        eConfig.entities = false;
        eConfig.entities_greek = false;
        eConfig.entities_latin = false;
        eConfig.extraAllowedContent = "*[pimcore_type,pimcore_id]";
        eConfig.baseFloatZIndex = 40000;   // prevent that the editor gets displayed behind the grid cell editor window

        if (eConfig.hasOwnProperty('removePlugins')) {
            eConfig.removePlugins += ",tableresize";
        }
        else {
            eConfig.removePlugins = "tableresize";
        }


        try {
            this.ckeditor = CKEDITOR.inline(this.editableDivId, eConfig);

            // disable URL field in image dialog
            this.ckeditor.on("dialogShow", function (e) {
                var urlField = e.data.getElement().findOne("input");
                if (urlField && urlField.getValue()) {
                    if (urlField.getValue().indexOf("/image-thumbnails/") > 1) {
                        urlField.getParent().getParent().getParent().hide();
                    }
                } else if (urlField) {
                    urlField.getParent().getParent().getParent().show();
                }
            });
        } catch (e) {
            console.log(e);
        }
    },

});