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

pimcore.registerNS("pimcore.object.classes.data.language");
pimcore.object.classes.data.language = Class.create(pimcore.object.classes.data.data, {

    type: "language",
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
    },

    initialize: function (treeNode, initData) {
        this.type = "language";

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("language");
    },

    getGroup: function () {
        return "select";
    },

    getIconClass: function () {
        return "pimcore_icon_language";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: "checkbox",
                labelStyle: "width: 350px",
                fieldLabel: t("only_configured_languages"),
                name: "onlySystemLanguages",
                checked: this.datax.onlySystemLanguages
            }
        ]);

        return this.layout;
    },

    applySpecialData: function(source) {
    if (source.datax) {
        if (!this.datax) {
            this.datax =  {};
        }
        Ext.apply(this.datax,
            {
                onlySystemLanguages: source.datax.onlySystemLanguages
            });
    }
}
});
