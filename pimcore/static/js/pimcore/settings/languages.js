pimcore.registerNS("pimcore.settings.languages");
pimcore.settings.languages = Class.create({


    initialize: function () {

        this.downloadMask = new Ext.LoadMask(Ext.getBody(), {msg:t('downloading_language')});

        this.availableStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/update/index/get-languages',
            root: 'languages',
            idProperty: 'key',
            autoload:true,
            listeners: {
                load: this.getTabPanel.bind(this),
                exception:function() {
                    pimcore.helpers.showNotification(t("error"), t("languages_download_error"), "error");
                    pimcore.globalmanager.remove("languages_overview");
                }.bind(this)
            },
            sortInfo: {
                field    : 'key',
                direction: 'ASC'

            } ,
            fields: ['key', 'name', 'download','percent','exists']
        });

        this.availableStore.load();
    },

    getTabPanel: function () {

        if (!this.panel) {

            this.layout = new Ext.grid.GridPanel({
                hideHeaders: true,
                store: this.availableStore,
                columns: [
                    {header: "", sortable: true, dataIndex: 'key', editable: false, width: 40},
                    {header: "", sortable: true, dataIndex: 'key', editable: false, width: 40,
                                    renderer: function(data){
                                        return '<img src="/admin/misc/get-language-flag?language=' + data + '" alt="" />';
                                    }
                    },

                    {header: "", sortable: true, dataIndex: 'name', editable: false, width: 200},
                    {header: "", sortable: true, dataIndex: 'percent', editable: false, width: 150,
                                    renderer: function(data){
                                        return data+'% '+t('language_translation_percentage');
                                    }
                    },
                    {
                        xtype: 'actioncolumn',
                        width: 30,
                        tooltip: 'language_download',
                        items: [
                            {

                                getClass: function(v, meta, rec) {
                                                        if (rec.get('exists') > 0) {
                                                            return 'pimcore_icon_language_update';
                                                        } else {
                                                            return 'pimcore_icon_language_download';
                                                        }
                                                    },

                                handler: function(grid, rowIndex, colIndex) {
                                        this.download(rowIndex);
                                }.bind(this)
                            }
                        ]
                    }

                ]
            });

            this.panel = new Ext.Panel({
                id: "languages_overview",
                title: t("language_download"),
                iconCls: "pimcore_icon_languages",
                border: false,
                layout: "fit",
                closable:true

            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("languages_overview");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("languages_overview");
            }.bind(this));
            this.panel.add(this.layout);
            pimcore.layout.refresh();

        }
        return this.panel;

    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("languages_overview");
    },


    download: function(index) {

        this.downloadMask.show();
        var downloadLink = this.availableStore.getAt(index).data.download;
        var language =  this.availableStore.getAt(index).data.key;
        
        Ext.Ajax.request({
            url: "/admin/update/index/download-language",
            method: "post",
            params: {
                language : language
            },
            success: this.downloadcomplete.bind(this)
        });
    },

    downloadcomplete: function(response) {

        this.downloadMask.hide();
      
        var status = Ext.decode(response.responseText);
        if (status.success) {

            this.layout.getStore().load({callback:function() {
                this.layout.getView().refresh();
            }.bind(this)});

            Ext.MessageBox.show({
                title:t('language_downloaded'),
                msg: t('language_downloaded_info'),
                buttons: Ext.Msg.OKCANCEL,
                fn: function(buttonId) {
                    if (buttonId == "ok") {
                        window.location.reload();
                    }
                }.bind(this)
            });


        } else {
            Ext.MessageBox.show({
                title:t('language_download_failed'),
                msg: t('language_download_failed_info'),
                buttons: Ext.Msg.OK
            });
        }
    }
});