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

pimcore.registerNS("pimcore.document.page");
pimcore.document.page = Class.create(pimcore.document.page_snippet, {

    initialize: function(id) {

        this.setType("page");

        pimcore.plugin.broker.fireEvent("preOpenDocument", this, "page");

        this.addLoadingPanel();
        this.id = intval(id);
        this.getData();

    },

    init: function () {

        if (this.isAllowed("save") || this.isAllowed("publish")) {
            this.edit = new pimcore.document.edit(this);
        }
        if (this.isAllowed("settings")) {
            this.settings = new pimcore.document.pages.settings(this);
            this.scheduler = new pimcore.element.scheduler(this, "document");
            this.notes = new pimcore.element.notes(this, "document");
            this.targeting = new pimcore.document.pages.targeting(this);
        }
        if (this.isAllowed("properties")) {
            this.properties = new pimcore.document.properties(this, "document");
        }
        if (this.isAllowed("versions")) {
            this.versions = new pimcore.document.versions(this);
        }

        this.dependencies = new pimcore.element.dependencies(this, "document");
        this.preview = new pimcore.document.pages.preview(this);
        this.reports = new pimcore.report.panel("document_page", this);
    },

    getTabPanel: function () {

        var items = [];

        if (this.isAllowed("save") || this.isAllowed("publish")) {
            items.push(this.edit.getLayout());
        }
        items.push(this.preview.getLayout());

        if (this.isAllowed("settings")) {
            items.push(this.settings.getLayout());
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("settings") && pimcore.settings.targeting_enabled) {
            items.push(this.targeting.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }

        items.push(this.dependencies.getLayout());

        var reportLayout = this.reports.getLayout();
        if(reportLayout) {
            items.push(reportLayout);
        }

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
        }


        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    getSaveData : function (only) {

        var parameters = {};
        parameters.id = this.id;

        // get only scheduled tasks
        if (only == "scheduler") {
            try {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
                return parameters;
            }
            catch (e) {
                console.log("scheduler not available");
                return;
            }
        }


        // save all data allowed

        // data
        try {
            parameters.data = Ext.encode(this.edit.getValues());
        }
        catch (e) {
            //console.log(e);
        }

        if (this.isAllowed("properties")) {
            // properties
            try {
                parameters.properties = Ext.encode(this.properties.getValues());
            }
            catch (e) {
                //console.log(e);
            }
        }

        if (this.isAllowed("settings")) {
            // settings
            try {
                var settings = this.settings.getValues();
                settings.published = this.data.published;
                parameters.settings = Ext.encode(settings);
            }
            catch (e) {
                //console.log(e);
            }

            // scheduler
            try {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
            }
            catch (e) {
                //console.log(e);
            }
        }

        return parameters;
    }

});

