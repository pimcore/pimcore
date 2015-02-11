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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.report.analytics.elementnavigation");
pimcore.report.analytics.elementnavigation = Class.create(pimcore.report.abstract, {

    matchType: function (type) {        
        var types = ["document_page"];
        if (pimcore.report.abstract.prototype.matchTypeValidate(type, types)
                                                            && pimcore.settings.google_analytics_enabled) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "navigation";
    },

    getIconCls: function () {
        return "pimcore_icon_analytics_navigation";
    },

    getPanel: function () {
        var panel = new Ext.Panel({
            title: t("navigation"),
            layout: "border",
            height: 680,
            border: false,
            items: [this.getFilterPanel(),this.getContentPanel()]
        });
        
        panel.on("afterrender", function (panel) {
            this.loadMask = new Ext.LoadMask(panel.getEl(), {msg: t("please_wait")});
            this.loadMask.enable();
            
        }.bind(this));
        
        return panel;
    },
    
    getContentPanel: function () {
  
        this.flashContainerId = "analytics_navigation_" + this.type + "_" + this.reference.id;
        
        this.contentPanel = new Ext.Panel({
            region: "center",
            layout: "fit",
            autoScroll: true,
            html: '<div id="' + this.flashContainerId + '"></div>',
            listeners: {
                afterrender: function () {
                    window.setTimeout(this.embedFlash.bind(this),2000);
                }.bind(this)
            }
        });
        
        return this.contentPanel;  
    },
    
    embedFlash: function () {
        var flashvars = {
          xmlFile: this.getConfigFile()
        };
        var params = {
          wmode: "opaque"
        };
        
        var height = this.contentPanel.getHeight()-10;
        
        swfobject.embedSWF("/pimcore/static/swf/analytics_navigation.swf", this.flashContainerId, "100%",
                                height, "10.0.0","/pimcore/static/swf/expressInstall.swf", flashvars, params);
    },
    
    getConfigFile: function () {
        
        var id = "";
        var path = "";
        var type = "";
        
        if (this.type == "document_page") {
            id = this.reference.id;
            path = this.reference.data.path + this.reference.data.key;
            type = "document";
        }
        
        var formValues = {
            dateFrom: "",
            dateTo: "",
            site: ""
        };
        try {
            formValues = this.filterPanel.getForm().getFieldValues();
        } catch (e) {}
        
        return "/admin/reports/analytics/navigation?path=" + path + "&id=" + id + "&type=" + type + "&dateFrom="
                            + formValues.dateFrom + "&dateTo=" + formValues.dateTo + "&site=" + formValues.site;
    },
    
    getFilterPanel: function () {

        if (!this.filterPanel) {


            var today = new Date();
            var fromDate = new Date(today.getTime() - (86400000 * 31));


            this.filterPanel = new Ext.FormPanel({
                region: 'north',
                labelWidth: 40,
                height: 40,
                layout: 'form',
                bodyStyle: 'padding:7px 0 0 5px',
                items: [{
                        xtype: "datefield",
                        fieldLabel: t('from'),
                        name: 'dateFrom',
                        value: fromDate,
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "datefield",
                        fieldLabel: t('to'),
                        name: 'dateTo',
                        value: today,
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "combo",
                        store: pimcore.globalmanager.get("sites"),
                        valueField: "id",
                        displayField: "domain",
                        triggerAction: "all",
                        name: "site",
                        fieldLabel: t("site"),
                        itemCls: "pimcore_analytics_filter_form_item"
                    },{
                        xtype: "button",
                        text: t("apply"),
                        itemCls: "pimcore_analytics_filter_form_item",
                        handler: function () {
                            this.embedFlash();
                        }.bind(this)
                    }
                ]
            });
        }

        return this.filterPanel;
    }
});

// add to report broker
pimcore.report.broker.addGroup("analytics", "google_analytics", "pimcore_icon_report_analytics_group");
pimcore.report.broker.addReport(pimcore.report.analytics.elementnavigation, "analytics");
