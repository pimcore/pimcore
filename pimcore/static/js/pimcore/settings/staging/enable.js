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

pimcore.registerNS("pimcore.settings.staging.enable");
pimcore.settings.staging.enable = Class.create({

    initialize: function () {

        Ext.Msg.show({
           title: t("development_stage_mode"),
           msg: t("development_stage_mode_enable_question"),
           buttons: Ext.Msg.YESNO,
           fn: function (answer) {
                if(answer == "yes") {
                    this.start();
                }
            }.bind(this),
           icon: Ext.MessageBox.QUESTION
        });
    },

    start: function () {
        this.errors = 0;
        this.enabled = true;

        this.progressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        this.window = new Ext.Window({
            title: t("development_stage_mode"),
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
            url: "/admin/staging/enable-init",
            success: function (response) {

                try {
                    var r = Ext.decode(response.responseText);

                    if (r.steps) {

                        var pj = new pimcore.tool.paralleljobs({
                            success: function () {
                                this.window.removeAll();
                                this.window.add(new Ext.Panel({
                                    bodyStyle: "padding: 20px;",
                                    html: t("development_stage_mode_ready")
                                }));
                                this.window.doLayout();

                                window.setTimeout(function () {
                                    location.reload();
                                },2000);

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
                        this.error(response.responseText);
                    }
                }
                catch (e) {
                    this.error(response.responseText);
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