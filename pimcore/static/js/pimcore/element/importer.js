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

pimcore.registerNS("pimcore.element.importer");
pimcore.element.importer = Class.create({

    initialize: function(type, id) {

        this.parentId = id;
        this.type = type;

        this.user = pimcore.globalmanager.get("user");
        this.upload();
    },


    upload: function () {


        if (!this.uploadWindow) {
            this.uploadWindow = new Ext.Window({
                layout: 'fit',
                title: 'Upload',
                closeAction: 'hide',
                width:400,
                height:170,
                modal: true
            });

            var uploadPanel = new Ext.ux.SwfUploadPanel({
                border: false,
                upload_url: '/admin/import/upload/?pimcore_admin_sid=' + pimcore.settings.sessionId,
                debug: false,
                flash_url: "/pimcore/static/js/lib/ext-plugins/SwfUploadPanel/swfupload.swf",
                single_select: false,
                file_queue_limit: 1,
                file_types: "*.zip",
                single_file_select: true,
                confirm_delete: false,
                remove_completed: true,
                post_params: { id: this.user.username },
                listeners: {
                    "fileUploadComplete": function (win) {
                        win.hide();
                        this.getUploadInfo();
                    }.bind(this, this.uploadWindow)
                }
            });

            this.uploadWindow.add(uploadPanel);
        }

        this.uploadWindow.show();
        this.uploadWindow.setWidth(401);
        this.uploadWindow.doLayout();
    },

    getUploadInfo: function () {

        //this.loadingMask = new Ext.LoadMask(this.layout.getEl(), {msg:t('dealercenter_loading_data')});
        //this.loadingMask.show();

        Ext.Ajax.request({
            url: "/admin/import/get-upload-info",
            params: {
                id: this.user.username,
                method: "post"
            },
            success: function(response) {
                var res = Ext.decode(response.responseText);
                this.jobs = res.jobs;
                var success = res.success;
                if (success) {

                     this.window = new Ext.Window({
                        width: 450,
                        height: 250,
                        modal: true,
                        layout: "fit",
                        tbar:  {
                            items: []
                        }
                    });


                    this.importForm = new Ext.form.FormPanel({
                        title: t('element_import_settings'),
                        bodyStyle: "padding: 20px;",
                        layout: "pimcoreform",
                        labelWidth: 200,
                        items:[
                            {
                                xtype: "displayfield",
                                hideLabel:true,
                                value: '<span style="color: red">WARNING! The archive import feature is experimental and might not yet be fully functional!</span>'
                            },{
                                xtype: 'radiogroup',
                                hideLabel: true,
                                itemCls: 'x-check-group-alt',
                                columns: 1,
                                items: [
                                    {boxLabel: t('element_import_import'), name: 'overwrite', inputValue: 0, checked: true},
                                    {boxLabel: t('element_import_overwrite'), name: 'overwrite', inputValue: 1}
                                ]
                            },{
                                xtype: "hidden",
                                name: 'parentId',
                                value: this.parentId
                            },
                            {
                                xtype: "hidden",
                                name: 'type',
                                value: this.type
                            }
                        ],
                        buttons:["->",
                            {
                                xtype: "button",
                                iconCls: "pimcore_icon_apply",
                                text: t('import'),
                                handler: this.setImportParams.bind(this)
                            }
                        ]
                    });

                    this.window.add(this.importForm);

                    this.window.show();


                } else {
                    pimcore.helpers.showNotification(t('error'), t('element_archive _upload_failed'), "error", this.data.message);
                }

            }.bind(this)
        });
    },

    setImportParams: function() {

        this.progressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        var importFormValues =  this.importForm.getForm().getValues();


        this.window.removeAll();
        this.window.add(new Ext.Panel({
            title: t('element_import'),
            bodyStyle: "padding: 20px;",
            items: [
                {
                    border:false,
                    html: "<b>" + t("importing_elements") + "<br />",
                    style: "padding: 0 0 20px 0;"
                },
                this.progressBar
            ]
        }));
        this.window.doLayout();

        this.startImportJobs(importFormValues);
    },


    startImportJobs: function(importFormValues) {
        this.proceduralJobsRunning = 0;
        this.proceduralJobsFinished = 0;
        this.proceduralJobsStarted = 0;
        this.proceduralJobsTotal = this.jobs.length;


        this.proceduralJobsInterval = window.setInterval(function () {

            if (this.proceduralJobsFinished == this.proceduralJobsTotal) {
                clearInterval(this.proceduralJobsInterval);
                this.window.removeAll();
                this.window.add(new Ext.Panel({
                    title: t('element_import_finished'),
                    bodyStyle: "padding: 20px;",
                    autoScroll: true,
                    html: t('element_import_finished_hint')
                }));
                this.window.doLayout();

                return;
            }

            if (this.proceduralJobsRunning < 1) {

                this.proceduralJobsRunning++;
               //console.log(this.importForm.getForm().getValues());
                Ext.Ajax.request({
                    url: "/admin/import/do-import-jobs",
                    method: "post",
                    success: function (response) {


                        var response = Ext.decode(response.responseText);
                        if (!response.success) {
                            // if import fails, stop all activity
                            clearInterval(this.proceduralJobsInterval);
                            pimcore.helpers.showNotification(t("error"), t("element_import"), "error", t(response.message));
                        }

                        this.proceduralJobsFinished++;
                        this.proceduralJobsRunning -= 1;

                        // update progress bar
                        var status = this.proceduralJobsFinished / this.proceduralJobsTotal;
                        var percent = Math.ceil(status * 100);

                        try {
                            this.progressBar.updateProgress(status, percent + "%");
                        } catch (e) {
                        }

                    }.bind(this),
                    failure: function () {
                        clearInterval(this.proceduralJobsInterval);
                        pimcore.helpers.showNotification(t("error"), "Import failed, see debug.log for more details.", "error", "");
                    }.bind(this),
                    params: array_merge(this.jobs[this.proceduralJobsStarted],importFormValues)
                });

                this.proceduralJobsStarted++;
            }
        }.bind(this), 500);

    }
  



});