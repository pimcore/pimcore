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

pimcore.registerNS("pimcore.document.printpages.pdfpreview");
pimcore.document.printpages.pdfpreview = Class.create({

    initialize: function(page) {
        this.page = page;
    },

    getLayout: function () {

        if (this.layout == null) {

            var details = [];

            // Generate PDF Panel
            this.publishedWarning = new Ext.form.Label({
                text: t("web2print_only_published"),
                style: "color: red",
                hidden: this.page.data.published
            });

            this.generateButton = new Ext.Button({
                text: t("web2print_generate_pdf"),
                iconCls: "pimcore_material_icon_pdf pimcore_material_icon",
                style: "float: right;  margin-top: 10px",
                disabled: !this.page.data.published,
                handler: this.generatePdf.bind(this)
            });
            this.generateForm = new Ext.form.FormPanel({
                autoHeight: true,
                border: false,
                items: [this.getProcessingOptionsGrid(), this.publishedWarning, this.generateButton]
            });

            this.progressBar = Ext.create('Ext.ProgressBar', {
                style: "margin-bottom: 10px"
            });

            this.statusUpdateBox = Ext.create('Ext.Panel', {
                autoHeight: true,
                border: false,
                hidden: true,
                items: [this.progressBar, {
                    xtype: 'button',
                    style: "float: right;",
                    text: t("web2print_cancel_pdf_creation"),
                    iconCls: "pimcore_icon_cancel",
                    handler: function() {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_document_printpage_cancelgeneration'),
                            method: 'DELETE',
                            params: {id: this.page.id},
                            success: function(response) {
                                var result = Ext.decode(response.responseText);
                                if(!result.success) {
                                    pimcore.helpers.showNotification(t('web2print_cancel_generation'), t('web2print_cancel_generation_error'), "error");
                                }
                            }.bind(this)
                        });
                    }.bind(this)
                }]
            });
            details.push({
                title: t("web2print_generate_pdf"),
                expandable: true,
                bodyStyle: "padding: 10px;",
                border: true,
                items: [
                    this.generateForm, this.statusUpdateBox
                ]
            });

            //Download PDF Panel
            this.downloadButton = new Ext.Button({
                text: t("web2print_download_pdf"),
                iconCls: "pimcore_icon_download",
                style: "float: right; margin-top: 10px",
                handler: function () {
                    var date = new Date();
                    var url = Routing.generate('pimcore_admin_document_printpage_pdfdownload', {id: this.page.id, download: 1, time: date.getTime()});
                    pimcore.helpers.download(url);
                }.bind(this)
            });
            this.generatedDateField = new Ext.form.TextField({
                readOnly: true,
                width: "100%",
                name: "last-generated",
                fieldLabel: t("web2print_last-generated"),
                value: ""
            });
            this.generateMessageField = new Ext.form.TextArea({
                readOnly: true,
                height: 100,
                width: "100%",
                name: "last-generate-message",
                fieldLabel: t("web2print_last-generate-message"),
                value: ""
            });
            this.dirtyLabel = new Ext.form.Label({
                text: t("web2print_documents_changed"),
                style: "color: red",
                hidden: true
            });
            details.push(new Ext.form.FormPanel({
                title: t("web2print_download_pdf"),
                bodyStyle: "padding: 10px;",
                style: "padding-top: 10px",
                border: true,
                items: [this.generatedDateField, this.generateMessageField, this.dirtyLabel, this.downloadButton]
            }));


            this.iframeName = 'document_pdfpreview_iframe_' + this.page.id;

            this.layout = new Ext.Panel({
                title: t('web2print_preview_pdf'),
                layout: "border",
                autoScroll: false,
                iconCls: "pimcore_material_icon_pdf pimcore_material_icon",
                items: [{
                    region: "center",
                    hideMode: "offsets",
                    bodyCls: "pimcore_overflow_scrolling pimcore_preview_body",
                    forceLayout: true,
                    autoScroll: true,
                    border: false,
                    scrollable: false,
                    html: '<iframe src="about:blank" width="100%" frameborder="0" id="' + this.iframeName + '" name="' + this.iframeName + '"></iframe>'
                },{
                    region: "west",
                    width: 350,
                    items: details,
                    style: "padding-right: 10px",
                    bodyStyle: "padding: 10px",
                    autoScroll: true
                }]
            });

            this.layout.on("resize", this.onLayoutResize.bind(this));
            this.layout.on("activate", this.refresh.bind(this));
            this.layout.on("afterrender", function () {
                Ext.get(this.iframeName).on('load', function() {
                    // this is to hide the mask if edit/startup.js isn't executed (eg. in case an error is shown)
                    // otherwise edit/startup.js will disable the loading mask
                    if(!this["frame"]) {
                        this.loadMask.hide();
                    }
                }.bind(this));

                this.loadMask = new Ext.LoadMask({
                    target: this.layout,
                    msg: t("please_wait")
                });

                this.loadMask.show();
            }.bind(this));
        }

        return this.layout;
    },

    getProcessingOptionsGrid: function() {

        this.processingOptionsStore = new Ext.data.JsonStore({
            proxy: {
                url: Routing.generate('pimcore_admin_document_printcontainer_getprocessingoptions'),
                type: 'ajax',
                reader: {
                    type: 'json',
                    rootProperty: "options",
                    idProperty: 'name'
                },
                extraParams: { id: this.page.id }
            },
            fields: ['name','label','type','value','default','values'],
            autoDestroy: true,
            autoLoad: true,
            listeners: {
                load: function() {
                    if(this.processingOptionsStore.count() > 0) {
                        this.processingOptionsGrid.show();
                    }
                }.bind(this)
            },
            sortInfo:{field: 'name', direction: "ASC"}
        });

        this.processingOptionsGrid = Ext.create('Ext.grid.Panel', {
            style: "padding-bottom: 10px",
            autoScroll: true,
            bodyCls: "pimcore_editable_grid",
            autoHeight: true,
            trackMouseOver: true,
            hidden: true,
            store: this.processingOptionsStore,
            clicksToEdit: 1,
            viewConfig: {
                markDirty: false
            },
            plugins: [
                Ext.create('Ext.grid.plugin.CellEditing', {
                    clicksToEdit: 1,
                    listeners: {
                        beforeedit: function(editor, context, eOpts) {
                            editor.editors.each(function (e) {
                                try {
                                    // complete edit, so the value is stored when hopping around with TAB
                                    e.completeEdit();
                                    Ext.destroy(e);
                                } catch (exception) {
                                    // garbage collector was faster
                                    // already destroyed
                                }
                            });

                            editor.editors.clear();
                        }
                    }
                })
            ],
            columnLines: true,
            stripeRows: true,
            columns: [
                {
                    text: t("name"),
                    dataIndex: 'label',
                    editable: false,
                    width: 120,
                    renderer: function(value) {
                        return t("web2print_" + value, value);
                    },
                    sortable: true
                },
                {
                    flex: 1,
                    text: t("value"),
                    dataIndex: 'value',
                    getEditor: this.getCellEditor.bind(this),
                    editable: true,
                    renderer: this.getCellRenderer.bind(this),
                    listeners: {
                        "mousedown": this.cellMousedown.bind(this)
                    }
                }
            ]
        });

        return this.processingOptionsGrid;
    },

    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
        var data = record.data;
        var type = data.type;

        if (type == "bool") {
            if (value) {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
            } else {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
            }
        }

        if (type == "select") {
            return t("web2print_" + value, value);
        }

        return value;
    },

    cellMousedown: function (grid, cell, rowIndex, cellIndex, e) {
        var store = grid.getStore();
        var record = store.getAt(rowIndex);

        var data = record.data;
        var type = data.type;

        if (type == "bool") {
            record.set("data", !data.data);
            record.set("value", !data.value);
        }
    },

    getCellEditor: function (record) {

        var data = record.data;

        var type = data.type;
        var property;

        if (type == "text") {
            property = new Ext.form.TextField();
        }
        else if (type == "bool") {
            //nothing needed there
        }
        else if (type == "select") {
            var values = data.values;
            var storeValues = [];
            for(var i=0; i < values.length; i++) {
                storeValues.push([values[i], t("web2print_" + values[i], values[i])]);
            }

            property = new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                mode: 'local',
                // typeAhead: true,
                lazyRender: true,
                store: new Ext.data.ArrayStore({
                    fields: ["id", "value"],
                    data: storeValues
                }),
                valueField: "id",
                displayField: "value"
            });
        }


        return property;
    },

    generatePdf: function() {

        var params = this.generateForm.getForm().getFieldValues();

        this.processingOptionsStore.each(function(rec) {
            params[rec.data.name] = rec.data.value;
        });
        params.id = this.page.id;

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_printpage_startpdfgeneration'),
            method: 'POST',
            jsonData: params,
            success: function(response) {
                result = Ext.decode(response.responseText);
                if(result.success) {
                    this.checkForActiveGenerateProcess();
                }
            }.bind(this)
        });
    },


    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get(this.iframeName).setStyle({
            height: (height) + "px"
        });
    },

    iFrameLoaded: function () {
        if(this.loadMask){
            this.loadMask.hide();
        }
    },

    loadCurrentPreview: function () {
        var date = new Date();
        var url = Routing.generate('pimcore_admin_document_printpage_pdfdownload', {id: this.page.id, time: date.getTime()});

        try {
            Ext.get(this.iframeName).dom.src = url;
        }
        catch (e) {
            console.log(e);
        }
    },

    refresh: function () {
        if(!this.loaded)  {
            this.checkPdfDirtyState();
            this.checkForActiveGenerateProcess();
            this.loaded = true;
        }
    },

    checkForActiveGenerateProcess: function() {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_printpage_activegenerateprocess'),
            method: 'POST',
            params: {id: this.page.id},
            success: function(response) {
                var result = Ext.decode(response.responseText);
                if(result.activeGenerateProcess) {
                    this.generateForm.hide();
                    this.statusUpdateBox.show();

                    if(result.statusUpdate) {
                        var text = result.statusUpdate.status + "% (" + t("web2print_" + result.statusUpdate.statusUpdate, result.statusUpdate.statusUpdate) + ")";
                        this.progressBar.updateProgress(result.statusUpdate.status / 100, text);
                    }

                    window.setTimeout(function() {
                        this.checkForActiveGenerateProcess();
                    }.bind(this), 2000);
                } else {
                    this.generateForm.show();
                    this.statusUpdateBox.hide();

                    this.downloadButton.setDisabled(!result.downloadAvailable);

                    this.generatedDateField.setValue(result.date);
                    this.generateMessageField.setValue(result.message);

                    if(result.downloadAvailable) {
                        this.loadCurrentPreview();
                    }
                    this.iFrameLoaded();
                    this.checkPdfDirtyState();
                }
            }.bind(this)
        });
    },

    checkPdfDirtyState: function() {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_document_printpage_checkpdfdirty'),
            params: {id: this.page.id},
            success: function(response) {
                result = Ext.decode(response.responseText);
                if(result.pdfDirty) {
                    this.dirtyLabel.setVisible(true);
                } else {
                    this.dirtyLabel.setVisible(false);
                }
            }.bind(this)
        });
    },


    enableGenerateButton: function(enable) {
        if(enable) {
            this.generateButton.enable();
            this.publishedWarning.hide();
        } else {
            this.generateButton.disable();
            this.publishedWarning.show();
        }
    }

});
