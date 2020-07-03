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

pimcore.registerNS("pimcore.document.printcontainer");
pimcore.document.printcontainer = Class.create(pimcore.document.printabstract, {
    type: "printcontainer",

    init: function () {

        var user = pimcore.globalmanager.get("user");

        if (this.isAllowed("settings")) {
            this.settings = new pimcore.document.snippets.settings(this, "printpage");
        }

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "document");
        }

        if (this.isAllowed("properties")) {
            this.properties = new pimcore.document.properties(this, "document");
        }
        if (this.isAllowed("versions")) {
            this.versions = new pimcore.document.versions(this);
        }

        this.pdfpreview = new pimcore.document.printpages.pdfpreview(this);
        this.workflows = new pimcore.element.workflows(this, "document");
    },

    getTabPanel: function () {

        var items = [];
        var user = pimcore.globalmanager.get("user");

        items.push(this.pdfpreview.getLayout());
        if (this.isAllowed("settings")) {
            items.push(this.settings.getLayout());
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }

        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("workflow_details") && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            defaults: {autoScroll:true},
            border: false,
            items: items,
            activeTab: 0
        });
        return this.tabbar;
    }

});

