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

pimcore.registerNS("pimcore.settings.staging.disable");
pimcore.settings.staging.disable = Class.create({

    initialize: function () {

        Ext.Msg.show({
            title:'Save Changes?',
            msg: 'You are closing a tab that has unsaved changes. Would you like to save your changes?',
            buttons: Ext.Msg.YESNOCANCEL,
            fn: function (button) {
                if(button == "yes") {
                    this.syncback();
                } else if (button == "no") {
                    this.justdisable();
                } else {
                    return;
                }
            }.bind(this),
            icon: Ext.MessageBox.QUESTION
        });

    },

    justdisable: function () {
        Ext.Ajax.request({
            url: "/admin/staging/disable-remove-config",
            success: function (response) {

                try {
                    var r = Ext.decode(response.responseText);

                    if(r.livedomain) {
                        location.href = location.protocol + "//" + r.livedomain + "/admin/";
                    }
                } catch (e) {
                    console.log(e);
                    alert(e);
                }
            }.bind(this)
        });
    },

    syncback: function () {
        this.errors = 0;
        this.enabled = true;

        this.progressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        this.window = new Ext.Window({
            title: "Staging",
            layout:'fit',
            width:500,
            bodyStyle: "padding: 10px;",
            closable:false,
            plain: true,
            modal: true,
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
            url: "/admin/staging/disable-init",
            success: function (response) {
                var r = Ext.decode(response.responseText);

                try {
                    if (r.steps) {

                        var pj = new pimcore.tool.paralleljobs({
                            success: function () {
                                this.window.removeAll();
                                this.window.add(new Ext.Panel({
                                    bodyStyle: "padding: 20px;",
                                    html: "Staging is now ready!"
                                }));
                                this.window.doLayout();
                            }.bind(this),
                            update: function (currentStep, steps, percent) {
                                var status = currentStep / steps;
                                this.progressBar.updateProgress(status, percent + "%");
                            }.bind(this),
                            failure: function (message) {
                                this.error(message);
                            }.bind(this),
                            jobs: r.steps
                        });
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

    error: function (message) {

        this.window.close();
        Ext.MessageBox.alert(t('error'), message);
        return;
    }

});