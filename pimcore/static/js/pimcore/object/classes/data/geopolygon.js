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

pimcore.registerNS("pimcore.object.classes.data.geopolygon");
pimcore.object.classes.data.geopolygon = Class.create(pimcore.object.classes.data.data, {

    type: "geopolygon",
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
        this.type = "geopolygon";

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","noteditable","invisible","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t("geopolygon");
    },

    getGroup: function () {
            return "geo";
    },

    getIconClass: function () {
        return "pimcore_icon_geopolygon";
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: 'spinnerfield',
                fieldLabel: t('latitude'),
                name: 'lat',
                value: this.datax.lat || 0,
                decimalPrecision: 8,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.01
            },{
                xtype: 'spinnerfield',
                fieldLabel: t('longitude'),
                name: 'lng',
                value: this.datax.lng || 0,
                decimalPrecision: 8,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.01
            },{
                xtype: 'spinnerfield',
                fieldLabel: t('zoom_level'),
                name: 'zoom',
                value: this.datax.zoom || 1,
                decimalPrecision: 0,
                minValue: 1,
                incrementValue: 1
            }
        ]);

        return this.layout;
    }

});
