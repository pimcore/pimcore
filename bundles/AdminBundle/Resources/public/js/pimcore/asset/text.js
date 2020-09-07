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

pimcore.registerNS("pimcore.asset.text");
pimcore.asset.text = Class.create(pimcore.asset.asset, {

    initialize: function(id, options) {

        this.options = options;
        this.id = intval(id);
        this.setType("text");
        this.addLoadingPanel();

        pimcore.plugin.broker.fireEvent("preOpenAsset", this, "text");

        var user = pimcore.globalmanager.get("user");

        this.properties = new pimcore.element.properties(this, "asset");
        this.versions = new pimcore.asset.versions(this);
        this.scheduler = new pimcore.element.scheduler(this, "asset");
        this.dependencies = new pimcore.element.dependencies(this, "asset");

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "asset");
        }

        this.tagAssignment = new pimcore.element.tag.assignment(this, "asset");
        this.metadata = new pimcore.asset.metadata.editor(this);
        this.workflows = new pimcore.element.workflows(this, "asset");

        this.getData();
    },

    getTabPanel: function () {
        var items = [];
        var user = pimcore.globalmanager.get("user");

        items.push(this.getEditPanel());

        if (this.isAllowed("publish")) {
            items.push(this.metadata.getLayout());
        }
        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }
        if (this.isAllowed("versions")) {
            items.push(this.versions.getLayout());
        }
        if (this.isAllowed("settings")) {
            items.push(this.scheduler.getLayout());
        }

        items.push(this.dependencies.getLayout());

        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
        }

        if (user.isAllowed("workflow_details") && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
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

    getEditPanel: function () {

        if (!this.editPanel) {
            if(this.data.data !== false) {
                this.editArea = new Ext.form.TextArea({
                    xtype: "textarea",
                    name: "data",
                    value: this.data.data,
                    style: "font-family: 'Courier New', Courier, monospace;"
                });

                this.editPanel = new Ext.Panel({
                    title: t("edit"),
                    iconCls: "pimcore_icon_edit",
                    bodyStyle: "padding: 10px;",
                    items: [this.editArea]
                });

                this.editPanel.on("resize", function (el, width, height, rWidth, rHeight) {
                    this.editArea.setWidth(width-20);
                    this.editArea.setHeight(height-20);
                }.bind(this));
            } else {
                this.editPanel = new Ext.Panel({
                    title: t("preview"),
                    html: t("preview_not_available"),
                    bodyCls: "pimcore_panel_body_centered",
                    iconCls: "pimcore_material_icon_devices pimcore_material_icon"
                });
            }
        }

        return this.editPanel;
    },
    
    
    getSaveData : function ($super, only) {
        var parameters = $super(only);
        
        if(!Ext.isString(only) && this.data.data !== false) {
            parameters.data = this.editArea.getValue();
        }
        
        return parameters;
    }
});

