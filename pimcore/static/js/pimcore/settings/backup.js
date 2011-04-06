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

pimcore.registerNS("pimcore.settings.backup");
pimcore.settings.backup = Class.create({

    initialize: function () {

        this.errors = 0;
        this.enabled = true;

        this.progressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        this.window = new Ext.Window({
            title: "Backup",
            layout:'fit',
            width:500,
            bodyStyle: "padding: 10px;",
            closeAction:'close',
            plain: true,
            modal: false,
            items: [this.progressBar]
        });

        this.window.show();
        this.window.on("close", function () {
            this.enabled = false;
        }.bind(this))

        window.setTimeout(this.init.bind(this), 500);
    },

    init: function () {
        Ext.Ajax.request({
            url: "/admin/backup/init",
            success: function (response) {
                var r = Ext.decode(response.responseText);

                try {
                    if (r.steps) {
                        this.steps = r.steps;
                        this.stepAmount = this.steps.length;

                        window.setTimeout(this.process.bind(this), 500);
                    }
                    else {
                        this.error();
                    }
                }
                catch (e) {
                    this.error();
                }
            }.bind(this)
        });
    },

    process: function (definedJob) {

        if (!this.enabled) {
            return;
        }

        var status = (1 - (this.steps.length / this.stepAmount));
        var percent = Math.ceil(status * 100);
        var filesize = "";
        var fileAmount = "";

        if (this.lastResponse) {
            if (this.lastResponse.filesize) {
                filesize = " / " + this.lastResponse.filesize;
            }
            if (this.lastResponse.fileAmount) {
                fileAmount = " / Files: " + this.lastResponse.fileAmount;
            }
        }

        this.progressBar.updateProgress(status, percent + "%" + filesize + fileAmount);

        if (this.steps.length > 0) {

            var nextJob;
            if (typeof definedJob == "object") {
                nextJob = definedJob;
            }
            else {
                nextJob = this.steps.shift();
            }

            Ext.Ajax.request({
                url: "/admin/backup/" + nextJob[0],
                params: nextJob[1],
                success: function (job, response) {
                    var r = Ext.decode(response.responseText);

                    try {
                        if (r.success) {
                            this.lastResponse = r;
                            window.setTimeout(this.process.bind(this), 500);
                        }
                        else {
                            this.error(job);
                        }
                    }
                    catch (e) {
                        this.error(job);
                    }
                }.bind(this, nextJob)
            });
        }
        else {
            //this.window.close();
            this.window.removeAll();
            this.window.add(new Ext.Panel({
                bodyStyle: "padding: 20px;",
                html: "The backupfile on the server is located at:<br /><b>" + this.lastResponse.filesystem + '</b><br />Please use a (S)FTP client to download the backup'
            }));
            this.window.doLayout();
        }
    },

    error: function (job) {

        var hasNoJob;
        if (typeof job == "object") {
            hasNoJob = true;
        }

        if (this.errors > 30 || hasNoJob) {
            this.enabled = false;
            this.window.close();
            Ext.MessageBox.alert(t('error'), t("backup_error"));
            return;
        }
        else {
            window.setTimeout(this.process.bind(this, job), 500);
        }

        this.errors++;
    }

});