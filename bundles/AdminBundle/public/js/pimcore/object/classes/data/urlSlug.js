/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.object.classes.data.urlSlug");
/**
 * @private
 */
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
        classificationstore: false,
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
        const stylingItems = [
            {
                xtype: "textfield",
                fieldLabel: t("width"),
                name: "width",
                value: datax.width
            },
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('width_explanation')
            }
        ];

        if (this.isInCustomLayoutEditor()) {
            return stylingItems;
        }

        const sitesStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_settings_getavailablesites', {excludeMainSite: 1}),
            },
            fields: ['id', 'domain']
        });

        return stylingItems.concat([
            {
                xtype: "numberfield",
                fieldLabel: t("domain_label_width"),
                name: "domainLabelWidth",
                value: datax.domainLabelWidth
            },
            {
                xtype: "textfield",
                fieldLabel: t("controller_action"),
                name: "action",
                value: datax.action,
                width: 740,
            },
            {
                xtype: 'container',
                html: t('url_slug_datatype_info'),
                style: 'margin-bottom:10px'
            },
            new Ext.ux.form.MultiSelect({
                fieldLabel: t("available_sites"),
                name: "availableSites",
                value: datax.availableSites,
                displayField: "domain",
                valueField: "id",
                store: sitesStore,
                width: 600,
            })
        ]);
    },

    applySpecialData: function (source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    action: source.datax.action,
                    availableSites: source.datax.availableSites,
                    domainLabelWidth: source.datax.domainLabelWidth,
                    defaultValueGenerator: source.datax.defaultValueGenerator
                });
        }
    }
});
