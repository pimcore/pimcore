
pimcore.registerNS("pimcore.document.printcontainer");
pimcore.document.printcontainer = Class.create(pimcore.document.printabstract, {

    urlprefix: "/admin/",
    type: "printcontainer",

    init: function () {
        if (this.isAllowed("settings")) {
            this.settings = new pimcore.document.snippets.settings(this, "printpage");
            this.notes = new pimcore.element.notes(this, "document");
        }
        if (this.isAllowed("properties")) {
            this.properties = new pimcore.document.properties(this, "document");
        }
        if (this.isAllowed("versions")) {
            this.versions = new pimcore.document.versions(this);
        }

        this.pdfpreview = new pimcore.document.printpages.pdfpreview(this);
    },

    getTabPanel: function () {
        var items = [];
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

        if (this.isAllowed("settings")) {
            items.push(this.notes.getLayout());
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

