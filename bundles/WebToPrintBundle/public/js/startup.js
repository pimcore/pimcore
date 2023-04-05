pimcore.registerNS("pimcore.bundle.web2print.startup");

pimcore.bundle.web2print.startup = Class.create({

    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
        document.addEventListener(pimcore.events.prepareDocumentTreeContextMenu, this.onPrepareDocumentTreeContextMenu.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");


        if (user.isAllowed("web2print_settings") && perspectiveCfg.inToolbar("settings.web2print")) {

            menu.settings.items.push({
                text: t("web2print_settings"),
                iconCls: "pimcore_nav_icon_print_settings",
                priority: 55,
                itemId: 'pimcore_menu_settings_web2print_settings',
                handler: this.web2printSettings
            });
        }

    },

    web2printSettings: function () {
        try {
            pimcore.globalmanager.get("bundle_web2print").activate();
        }
        catch (e) {
            pimcore.globalmanager.add("bundle_web2print", new pimcore.bundle.web2print.settings());
        }
    },

    onPrepareDocumentTreeContextMenu: function (e) {
        var document = e.detail.document;
        var menu = e.detail.menu;
        var tree = e.detail.tree;
        var me = this;

        if (tree.tree.getSelectionModel().getSelected().length > 1) {
            return;
        }

        var documentMenu = {
            printPage: [],
        };

        var childSupportedDocument = (document.data.type == "page" || document.data.type == "folder"
            || document.data.type == "link" || document.data.type == "hardlink"
            || document.data.type == "printcontainer" || document.data.type == "headlessdocument");

        if (childSupportedDocument && document.data.permissions.create) {
            documentMenu = this.populatePredefinedDocumentTypes(documentMenu, tree, document);

            // empty print pages
            documentMenu.printPage.push({
                text: "&gt; " + t("add_printpage"),
                iconCls: "pimcore_icon_printpage pimcore_icon_overlay_add",
                handler: me.addDocument.bind(tree, tree, document, "printpage")
            });
            documentMenu.printPage.push({
                text: "&gt; " + t("add_printcontainer"),
                iconCls: "pimcore_icon_printcontainer pimcore_icon_overlay_add",
                handler: me.addDocument.bind(tree, tree, document, "printcontainer")
            });

            if (document.data.type != "email" && document.data.type != "newsletter" && document.data.type != "link") {
                menu.insert(0, new Ext.menu.Item({
                    text: t('add_printpage'),
                    iconCls: "pimcore_icon_printpage pimcore_icon_overlay_add",
                    menu: documentMenu.printPage,
                    hideOnClick: false
                }));
            }
        }
    },



    addDocument : function (tree, record, type, docTypeId) {
        var textKeyTitle = t("add_" + type);
        var textKeyMessage = t("enter_the_name_of_the_new_item");

        Ext.MessageBox.prompt(textKeyTitle, textKeyMessage, function (tree, record, type, docTypeId, button, value) {
            if (button == "ok") {
                if (value) {
                    // check for ident filename in current level
                    if (pimcore.elementservice.isKeyExistingInLevel(record, value)) {
                        return;
                    }

                    if(pimcore.elementservice.isDisallowedDocumentKey(record.id, value)) {
                        return;
                    }

                    let params = {
                        key: pimcore.helpers.getValidFilename(value, "document"),
                        type: type,
                        docTypeId: docTypeId,
                        sourceTree: tree,
                        elementType: "document",
                        index: record.childNodes.length,
                        parentId: record.id,
                        url: Routing.generate('pimcore_bundle_web2print_document_' + type + '_add')
                    };

                    pimcore.elementservice.addDocument(params);
                }
            }
        }.bind(this, tree, record, type, docTypeId));
    },

    populatePredefinedDocumentTypes: function(documentMenu, tree, record) {
        var me = this;
        var document_types = pimcore.globalmanager.get("document_types_store");
        document_types.sort([
            {property: 'priority', direction: 'ASC'},
            {property: 'translatedGroup', direction: 'ASC'},
            {property: 'translatedName', direction: 'ASC'}
        ]);
        document_types.each(function (documentMenu, typeRecord) {
            var text = Ext.util.Format.htmlEncode(typeRecord.get("translatedName"));

            if (typeRecord.get("type") === 'printcontainer') {
                documentMenu['printPage'].push(
                    {
                        text: text,
                        iconCls: "pimcore_icon_printcontainer pimcore_icon_overlay_add",
                        handler: me.addDocument.bind(this, tree, record, "printcontainer", typeRecord.get("id"))
                    }
                );
            }

            if (typeRecord.get("type") === 'printpage') {
                documentMenu['printPage'].push(
                    {
                        text: text,
                        iconCls: "pimcore_icon_printpage pimcore_icon_overlay_add",
                        handler: me.addDocument.bind(this, tree, record, "printpage", typeRecord.get("id"))
                    }
                );
            }

        }.bind(this, documentMenu), documentMenu);

        return documentMenu;
    },
});



const pimcoreBundleWeb2print = new pimcore.bundle.web2print.startup();
