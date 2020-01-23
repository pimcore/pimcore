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

pimcore.registerNS("pimcore.object.classes.data.urlSlug");
pimcore.object.classes.data.urlSlug = Class.create(pimcore.object.classes.data.data, {

    type: "urlSlug",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: true,
        block: false,
        encryptedField: false
    },

    initialize: function (treeNode, initData) {
        this.type = "urlSlug";

        this.availableSettingsFields = ["name", "title", "tooltip", "mandatory", "noteditable", "invisible",
            "visibleGridView", "visibleSearch", "style"];

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("url_slug");
    },

    getGroup: function () {
        return "other";
    },

    getIconClass: function () {
        return "pimcore_icon_urlSlug";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax) {
        var specificItems = [
            {
                xtype: "textfield",
                fieldLabel: t("controller_action"),
                name: "action",
                value: datax.action,
                width: 740
            },
            {
                xtype: 'container',
                html: t('url_slug_datatype_info'),
                style: 'margin-bottom:10px'
            }
        ];

        return specificItems;

    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    action: source.datax.action
                });
        }
    }
});
