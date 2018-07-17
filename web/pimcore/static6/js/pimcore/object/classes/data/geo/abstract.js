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

pimcore.registerNS("pimcore.object.classes.data.geo.abstract");
pimcore.object.classes.data.geo.abstract = Class.create(pimcore.object.classes.data.data, {

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: false,
        block: true,
        encryptedField: true
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        return [
            {
                xtype: 'numberfield',
                fieldLabel: t('latitude'),
                name: 'lat',
                value: datax.lat || 0,
                decimalPrecision: 8,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.01
            }, {
                xtype: 'numberfield',
                fieldLabel: t('longitude'),
                name: 'lng',
                value: datax.lng || 0,
                decimalPrecision: 8,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.01
            }, {
                xtype: 'numberfield',
                fieldLabel: t('zoom_level'),
                name: 'zoom',
                value: datax.zoom || 1,
                decimalPrecision: 0,
                minValue: 1,
                incrementValue: 1
            }
        ];
    },

    checkGoogleMapsAPI: function () {
        if (!pimcore.settings.google_maps_api_key) {
            Ext.MessageBox.alert(t("geo_error_title"), t("geo_error_message"));
        }
    }
});
