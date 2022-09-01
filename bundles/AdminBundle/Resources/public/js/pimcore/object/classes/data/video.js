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

pimcore.registerNS("pimcore.object.classes.data.video");
pimcore.object.classes.data.video = Class.create(pimcore.object.classes.data.data, {

    type: "image",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore : false,
        block: true
    },

    initialize: function (treeNode, initData) {
        this.type = "video";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("video");
    },

    getIconClass: function () {
        return "pimcore_icon_video";
    },

    getGroup: function () {
        return "media";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "textfield",
                fieldLabel: t("width"),
                name: "width",
                value: this.datax.width
            },
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('width_explanation')
            },
            {
                xtype: "textfield",
                fieldLabel: t("height"),
                name: "height",
                value: this.datax.height
            },
            {
                xtype: "displayfield",
                hideLabel: true,
                value: t('height_explanation')
            }
        ]);

        this.supportedTypesStore = new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('pimcore_admin_dataobject_class_videosupportedTypestypes')
            },
            autoDestroy: true,
            autoLoad: true,
            fields: ["key", "value"]
        });

        this.allowedTypes = new Ext.ux.form.MultiSelect({
            fieldLabel: t("allowed_video_types") + '<br />' + t('allowed_types_hint'),
            name: "allowedTypes",
            id: 'allowedTypes',
            store: this.supportedTypesStore,
            value: this.datax.allowedTypes,
            displayField: "value",
            valueField: "key",
            width: 400
        });

        this.specificPanel.add(this.allowedTypes);

        return this.layout;
    },

    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax =  {};
            }
            Ext.apply(this.datax,
                {
                    width: source.datax.width,
                    height: source.datax.height
                });
        }
    }

});
