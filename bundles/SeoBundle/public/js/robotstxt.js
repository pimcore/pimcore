/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.bundle.seo.robotstxt");
/**
 * @private
 */
pimcore.bundle.seo.robotstxt = Class.create({
    onFileSystem: false,
    data: {},
    textEditors: [],

    initialize: function(id) {
        this.getTabPanel();
        this.load();
    },

    load: function () {
        this.panel.setLoading(true);

        Ext.Ajax.request({
            url: Routing.generate('pimcore_bundle_seo_settings_robotstxtget'),
            success: function (response) {

                try {
                    var data = Ext.decode(response.responseText);
                    if(data.success) {
                        this.data = data.data;
                        this.onFileSystem = data.onFileSystem;

                        this.loadSites();
                    }
                } catch (e) {

                }
            }.bind(this)
        });
    },

    loadSites: function() {
        this.formPanel = new Ext.form.Panel({
            layout: 'fit'
        });

        var items = [];

        pimcore.globalmanager.get("sites").load(function(records) {
            Ext.each(records, function(record) {
                items.push(this.getEditPanel(record))
            }.bind(this));


            var buttons = [];

            if (this.onFileSystem) {
                buttons.push(t("robots_txt_exists_on_filesystem"));
            }

            buttons.push({
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                disabled: this.onFileSystem,
                handler: this.save.bind(this)
            });

            this.formPanel.add({
                xtype: 'tabpanel',
                layout: 'fit',
                items: items,
                buttons: buttons
            });

            this.panel.add(this.formPanel);
            this.panel.setLoading(false);
        }.bind(this));
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_robotstxt");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_robotstxt",
                title: t("robots.txt"),
                iconCls: "pimcore_icon_robots",
                border: false,
                layout: "fit",
                closable:true,
                items: []
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_robotstxt");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("bundle_seo_robotstxt");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getEditPanel: function (siteRecord) {
        let editorId = 'editor' + siteRecord.get('id');
        var editorContainer = new Ext.Component({
            html: '<div id="' + editorId + '" style="height:100%;width:100%"></div>',
            listeners: {
                afterrender: function (cmp) {
                    var editor = ace.edit(editorId);
                    editor.setTheme('ace/theme/chrome');

                    //set editor file mode
                    editor.getSession().setMode("ace/mode/text");

                    editor.setOptions({
                        showLineNumbers: true,
                        showPrintMargin: false,
                        wrap: true,
                        fontFamily: 'Courier New, Courier, monospace;'
                    });

                    //set data
                    if (this.data.hasOwnProperty(siteRecord.get('id'))) {
                        editor.setValue(this.data[siteRecord.getId('id')]);
                        editor.clearSelection();
                        editor.resize();
                    }

                    let textEditor = this.textEditors.find(e => e.key === siteRecord.get('id'));
                    if (textEditor) {
                        textEditor.editor = editor;
                    } else {
                        this.textEditors.push({
                            'key': siteRecord.get('id'),
                            'editor': editor
                        });
                    }

                }.bind(this)
            }
        });

        var editPanel = new Ext.Panel({
            title: siteRecord.get('domain'),
            layout: 'fit',
            iconCls: 'pimcore_icon_robots',
            bodyStyle: "padding: 10px;",
            items: [editorContainer]
        });

        editPanel.on('resize', function (el, width, height) {
            let textEditor = this.textEditors.find(e => e.key === siteRecord.get('id'));
            if (textEditor) {
                textEditor.editor.resize();
            }
        }.bind(this));

        return editPanel;
    },


    save : function () {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_bundle_seo_settings_robotstxtput'),
            method: "PUT",
            params: this.getValues(),
            success: function (response) {
                try {
                    var data = Ext.decode(response.responseText);
                    if(data.success) {
                        pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                    } else {
                        throw "save error";
                    }
                } catch (e) {
                    pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }.bind(this)
        });
    },

    getValues:  function () {
        let res = [];
        for(var i = 0; i < this.textEditors.length; i++) {
            res['data['+this.textEditors[i].key+']'] = this.textEditors[i].editor.getValue();
        }

        return Ext.urlEncode(res);
    }
});

