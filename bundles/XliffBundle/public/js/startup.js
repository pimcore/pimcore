/**
* This source file is available under the terms of the
* Pimcore Open Core License (POCL)
* Full copyright and license information is available in
* LICENSE.md which is distributed with this source code.
*
*  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.com)
*  @license    Pimcore Open Core License (POCL)
*/
pimcore.registerNS("pimcore.bundle.xliff.startup");


pimcore.bundle.xliff.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
        document.addEventListener(pimcore.events.onPerspectiveEditorLoadPermissions, this.onPerspectiveEditorLoadPermissions.bind(this));
    },

    preMenuBuild: function (e) {

        let menu = e.detail.menu;
        let that = this;
        const user = pimcore.globalmanager.get('user');
        const perspectiveCfg = pimcore.globalmanager.get("perspective");

        if (user.isAllowed("xliff_import_export") && perspectiveCfg.inToolbar("extras.xliff_import_export")) {

            menu.extras.items.some(function(item, index) {
                if (item.itemId === 'pimcore_menu_extras_translations'){
                    menu.extras.items[index].menu.items.push({
                        text: "XLIFF " + t("export") + "/" + t("import"),
                        iconCls: "pimcore_nav_icon_translations",
                        itemId: 'pimcore_menu_extras_translations_xliff',
                        handler: that.xliffImportExport,
                        priority: 20,
                    });
                    return true;
                }
            });
        }
    },

    xliffImportExport: function() {
        try {
            pimcore.globalmanager.get("bundle_xliff").activate();
        } catch (e) {
            pimcore.globalmanager.add("bundle_xliff", new pimcore.bundle.xliff.settings());
        }
    },

    onPerspectiveEditorLoadPermissions: function (e) {
        let context = e.detail.context;
        let menu = e.detail.menu;
        let permissions = e.detail.permissions;

        if(context === 'toolbar' && menu === 'extras' &&
            permissions[context][menu].indexOf('items.xliff_import_export') === -1) {
            permissions[context][menu].push('items.xliff_import_export');
        }
    }
})

const bundle_xliff = new pimcore.bundle.xliff.startup();