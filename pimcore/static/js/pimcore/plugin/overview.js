pimcore.registerNS("pimcore.plugins.overview");
pimcore.plugins.overview = Class.create({


    initialize: function () {

        this.downloadMask = new Ext.LoadMask(Ext.getBody(), {msg:t('downloading_plugin')});

        this.availableStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/plugin/admin/get-plugins',
            root: 'plugins',
            idProperty: 'pluginName',
            autoload:false,
            listeners: {
                load: function() {
                    this.availableLoaded = true;
                    this.dataLoaded();
                    if(this.currentIndex){
                        var description = this.availableStore.getAt(this.currentIndex).data.pluginDescription+ '<br/><br/>' + t('plugin_build') + ': ' + this.availableStore.getAt(this.currentIndex).data.pluginRevision + '<br/><br/>' + this.availableStore.getAt(this.currentIndex).data.pluginState ;
                        this.availableInfo.update('<div class="plugin_info">' + description + '</div>');
                        this.availableInfo.setTitle(this.availableStore.getAt(index).data.pluginName+' '+this.availableStore.getAt(this.currentIndex).data.pluginVersion);

                    }
                }.bind(this) ,
                exception:function(){
                    pimcore.helpers.showNotification(t("error"), t("plugin_misconfiguration"), "error");
                    pimcore.globalmanager.remove("plugins_overview");
                }.bind(this)
            },
            sortInfo: {
                field    : 'pluginName',
                direction: 'ASC'

            } ,
            fields: ['pluginRevision', 'pluginVersion', 'pluginName', 'pluginNiceName','pluginIcon', 'pluginServer', 'pluginVersion','pluginRevision','pluginState','pluginDescription', 'pluginIframeSrc','pluginClassName','pluginClassName','namespaceString','jsPathString','cssPathString','isInstalled','isUpdateAvailable']
        });
        this.downloadableStore = new Ext.data.JsonStore({
            autoDestroy: true,
            url: '/admin/plugin/download/get-downloads',
            root: 'plugins',
            idProperty: 'pluginName',
            autoload:false,
            listeners: {      
                load: function() {
                    this.downloadableLoaded = true;
                    this.dataLoaded();
                }.bind(this)  ,
                exception:function(){
                    
                }.bind(this)
            },
            sortInfo: {
                field    : 'pluginName',
                direction: 'ASC'

            } ,
            fields: ['pluginRevision', 'pluginVersion', 'pluginName', 'pluginNiceName','pluginIcon','pluginServer', 'pluginVersion','pluginRevision','pluginState','pluginDescription', 'pluginIframeSrc','pluginClassName','pluginClassName','namespaceString','jsPathString','cssPathString','isInstalled','isUpdateAvailable']
        });

        this.downloadableStore.load();
        this.availableStore.load();
        
    },

    dataLoaded: function() {
        if (this.availableLoaded && this.downloadableLoaded) {

            if(this.availableStore.data.items.length == 0 && this.downloadableStore.data.items.length==0){
                        pimcore.helpers.showNotification(t("error"), t("plugin_misconfiguration"), "error");
                        pimcore.globalmanager.remove("plugins_overview");
            } else {
                this.getTabPanel();    
            }
        }
    },

    getTabPanel: function () {

        if (!this.panel) {


            this.availablePlugins = this.getAvailablePanel();
            this.downloadPlugins = this.getDownloadPanel();


            this.panel = new Ext.Panel({
                id: "plugins_overview",
                iconCls: "pimcore_icon_plugin",
                title: t("plugins"),
                border: false,
                layout: "fit",
                closable:true

            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("plugins_overview");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("plugins_overview");
            }.bind(this));


            this.available = new Ext.Panel({
                title: t('available_plugins'),
                border: false,
                layout: "border",
                id: 'available_plugins_tab',
                items : [this.availablePlugins,this.availableInfo],
                listeners: {
                    activate: function() {

                        this.availablePlugins.getStore().load({callback:function() {
                            this.availablePlugins.refresh();


                        }.bind(this)});
                       
                    }.bind(this)
                }
            });

            this.downloads = new Ext.Panel({
                title: t('download_plugins'),
                border: false,
                layout: "border",
                id: 'download_plugins_tab',
                items : [this.downloadPlugins,this.downloadInfo],
                listeners: {
                    activate: function() {

                        this.downloadPlugins.getStore().load({callback:function() {
                            this.downloadPlugins.refresh();
                        }.bind(this)});
                        
                    }.bind(this)
                }
            });


            this.layout = new Ext.TabPanel({
                activeTab: 0,
                items: [this.available,this.downloads]
            });


            this.panel.add(this.layout);

            pimcore.layout.refresh();


            //perform an initial sort
            //this.availableStore.sort("pluginName", "DESC");
            //this.downloadableStore.sort("pluginName", "DESC");

        }

        return this.panel;

    },


    getDownloadPanel: function() {
        this.downloadInfo = new Ext.Panel({
            region: 'east',
            width: 200,
            layout: 'fit',
            title: t('plugin_info'),
            id: "plugin_download_info",
            html: ""
        });

        var downloadPlugins = new Ext.DataView({
            store: this.downloadableStore,
            region: 'center',
            layout: 'fit',
            tpl  :  new Ext.XTemplate(
                    '<ul>',
                    '<tpl for=".">',
                    '<li class="plugin">',
                    '<img width="64" height="64" src="{pluginIcon}" />',

                    '<strong>{pluginNiceName}</strong>',
                    '<span class="buttons">',
                    '<tpl><input type="button" class="download" name="download" value="{[t(\'download\')]}"/></tpl>',
                    '</span>',
                    '</li>',
                    '</tpl>',
                    '</ul>'
                    ),
            /*plugins : [
                new Ext.ux.DataViewTransition({
                    duration  : 550,
                    idProperty: 'pluginName'
                })
            ], */
            id: 'downloadable_plugins',
            itemSelector: 'li.plugin',
            overClass   : 'plugin-hover',
            singleSelect: true,
            multiSelect : true,
            autoScroll  : true,
            listeners:{
                click: function(dataView, index, node, e) {
                    var description = dataView.getStore().getAt(index).data.pluginDescription;
                    this.downloadInfo.update('<div class="plugin_info">' + description + '</div>');
                    this.downloadInfo.setTitle(dataView.getStore().getAt(index).data.pluginName);
                    var target = e.getTarget();
                    if (target.name == "download") {
                        this.download(index, dataView.getStore().getAt(index).data);
                    }
                }.bind(this)
            }
        });
        return downloadPlugins;
    },

    getAvailablePanel:function() {

        this.availableInfo = new Ext.Panel({
            region: 'east',
            width: 200,
            layout: 'fit',
            title: t('plugin_info'),
            id: "plugin_available_info",
            html: ""
        });


        var availablePlugins = new Ext.DataView({
            store: this.availableStore,
            region: 'center',
            layout: 'fit',
            tpl  : new Ext.XTemplate(
                    '<ul>',
                    '<tpl for=".">',
                    '<li class="plugin">',
                    '<img width="64" height="64" src="{pluginIcon}" />',

                    '<strong>{pluginNiceName}</strong>',
                    '<span class="buttons">',
                    '<tpl if="pluginIframeSrc!=\'\'"><input type="button" class="settings" name="settings" value="{[t(\'settings_plugins\')]}"/></tpl>',
                    '<tpl if="!isInstalled"><input type="button" name="install" value="{[t(\'install\')]}"/></tpl>',

                    '<tpl if="isInstalled"><input type="button" class="uninstall" name="uninstall" value="{[t(\'uninstall\')]}"/><tpl if="isUpdateAvailable"><input type="button" class="update" name="update" value="{[t(\'update\')]}"/></tpl></tpl>',
                    '</span>',
                    '</li>',
                    '</tpl>',
                    '</ul>'
                    ),
           /* plugins : [
                new Ext.ux.DataViewTransition({
                    duration  : 550,
                    idProperty: 'pluginName'
                })
            ],      */
            id: 'available_plugins',
            itemSelector: 'li.plugin',
            overClass   : 'plugin-hover',
            singleSelect: true,
            multiSelect : true,
            autoScroll  : true,
            listeners:{
                click: function(dataView, index, node, e) {


                    var description = dataView.getStore().getAt(index).data.pluginDescription+ '<br/><br/>' + t('plugin_build') + ': ' + dataView.getStore().getAt(index).data.pluginRevision + '<br/><br/>' + dataView.getStore().getAt(index).data.pluginState ;
                    this.availableInfo.update('<div class="plugin_info">' + description + '</div>');
                    this.availableInfo.setTitle(dataView.getStore().getAt(index).data.pluginName+' '+dataView.getStore().getAt(index).data.pluginVersion);
                    var target = e.getTarget();


                    if (target.name == "update") {
                        this.update(index, dataView.getStore().getAt(index).data);
                    } else if (target.name == "install") {
                        this.install(index, dataView.getStore().getAt(index).data);
                    } else if (target.name == "uninstall") {
                        this.uninstallwarning(index, dataView.getStore().getAt(index).data);
                    } else if (target.name == "settings") {

                        var pluginName = dataView.getStore().getAt(index).data.pluginName;
                        try {
                            pimcore.globalmanager.get("plugin_settings_"+pluginName).activate();
                        }
                        catch (e) {
                            pimcore.globalmanager.add("plugin_settings_"+pluginName,new pimcore.plugin.settings(pluginName,dataView.getStore().getAt(index).data.pluginIframeSrc));
                        }

                        /*
                        var win = new Ext.Window({
                            title: t("settings_plugins") + '-' + dataView.getStore().getAt(index).data.pluginName,
                            width: 600,
                            height: 400,
                            layout: "fit",
                            items: [
                                {
                                    xtype: "panel",
                                    html: '<iframe frameborder="0" style="width:600px; height: 400px" src="' + dataView.getStore().getAt(index).data.pluginIframeSrc + '" id="plugin_iframe_' + dataView.getStore().getAt(index).data.pluginName + '"></iframe>'

                                }
                            ]
                        });
                        win.show();
                        */
                    }
                }.bind(this)
            }

        });
        return availablePlugins;

    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("plugins_overview");
    },

    update: function(index, plugin) {
        var update = new pimcore.settings.pluginupdate(plugin.pluginName, plugin.pluginRevision, plugin.pluginServer,this,index);

    },

    updateAvailablePluginInfo: function(index){
    
        this.currentIndex = index;
        this.availableStore.reload();


    },

    reloadPromt: function() {

        Ext.MessageBox.show({
            title:t('reload'),
            msg: t('plugin_needs_reload'),
            buttons: Ext.Msg.OKCANCEL,
            fn: function(buttonId) {
                if (buttonId == "ok") {
                    window.location.reload();
                }
            }.bind(this)
        });
    },

    loadingMask: new Ext.LoadMask(Ext.getBody(), {
        msg:t('plugin_installing_please_wait')
    }),

    install: function (index, plugin) {
        //var loadingMask = new Ext.LoadMask(Ext.getBody(), {msg:t('installing_please_wait')});
        this.loadingMask.show();        
        
        Ext.Ajax.request({
            url: "/admin/plugin/admin/install",
            params: {
                className: plugin.pluginClassName
            },
            success: function (response) {
                this.loadingMask.hide();
                pluginResponse = Ext.decode(response.responseText);

                if (pluginResponse.status.installed == true) {
                    this.availablePlugins.getStore().load({callback:function() {
                        this.availablePlugins.refresh();
                    }.bind(this)});

                }

                if (pluginResponse.reload == "true" || pluginResponse.reload == true || pluginResponse.reload == 1 || pluginResponse.reload == "1") {
                    this.reloadPromt();
                } else {
                    var notification = new Ext.ux.Notification({
                        iconCls:    'x-icon-success',
                        title:      t('install'),
                        html:        pluginResponse.message,
                        autoDestroy: true,
                        hideDelay:  1000
                    });
                    notification.show(document);
                }


            }.bind(this)
        });
    },

    uninstallwarning: function(index, plugin) {

        Ext.MessageBox.show({
            title:t('uninstall'),
            msg: t('plugin_uninstall_warning'),
            buttons: Ext.Msg.OKCANCEL,
            fn: function(index, plugin, buttonId) {
                if (buttonId == "ok") {
                    this.uninstall(index, plugin);
                }
            }.bind(this, index, plugin)
        });
    },

    uninstall: function (index, plugin) {
        Ext.Ajax.request({
            url: "/admin/plugin/admin/uninstall",
            params: {
                className: plugin.pluginClassName
            },
            success: function (response) {

                pluginResponse = Ext.decode(response.responseText);

                if (pluginResponse.status.installed == false) {
                    this.availablePlugins.getStore().load({callback:function() {
                        this.availablePlugins.refresh();
                    }.bind(this)});

                }


                var notification = new Ext.ux.Notification({
                    iconCls:    'x-icon-success',
                    title:      t('uninstall'),
                    html:        pluginResponse.message,
                    autoDestroy: true,
                    hideDelay:  1000
                });

                var registeredPlugins = pimcore.plugin.broker.getPlugins();
                registeredPlugins.each(function(item) {
                    if (pluginResponse.pluginJsClassName == item.getClassName()) {
                        item.uninstall();
                    }
                });
                notification.show(document);

            }.bind(this)
        });
    },


    download: function(index, plugin) {

        this.downloadMask.show();
        Ext.Ajax.request({
            url: "/admin/plugin/download/new-download",
            params: {
                plugin: plugin.pluginName,
                host: plugin.pluginServer,
                revision: plugin.pluginRevision
            },
            success: this.downloadcomplete.bind(this, index, plugin)
        });
    },

    downloadcomplete: function(i, plugin, response) {

        this.downloadMask.hide();
        var status = Ext.decode(response.responseText);
        if (status.success) {


            this.downloadPlugins.getStore().load({callback:function() {
                this.downloadPlugins.refresh();
            }.bind(this)});
            this.downloadInfo.update('');
            this.downloadInfo.setTitle(t('plugin_info'));



            Ext.MessageBox.show({
                title:t('plugin_downloaded'),
                msg: t('plugin_downloaded_info'),
                buttons: Ext.Msg.OK,
                fn: function(i, buttonId) {
                  
                    
                }.bind(this, i)
            });


        } else {
            Ext.MessageBox.show({
                title:t('plugin_download_failed'),
                msg: t('plugin_download_failed_info'),
                buttons: Ext.Msg.OK,
                fn: function(i, buttonId) {
                }.bind(this, i)
            });
        }


    }


});