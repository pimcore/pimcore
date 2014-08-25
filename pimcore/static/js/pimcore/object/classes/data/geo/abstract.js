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

pimcore.registerNS("pimcore.object.classes.data.geo.abstract");
pimcore.object.classes.data.geo.abstract = Class.create(pimcore.object.classes.data.data, {

    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true
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
            },{
                xtype: 'combo',
                fieldLabel: t('map_type'),
                name: 'mapType',
                mode: 'local',
                allowBlank: false,
                editable: false,
                typeAhead: false,
                allowblank: false,
                triggerAction: 'all',
                store: [
                    ['roadmap', t('roadmap')],
                    ['satellite', t('satellite')],
                    ['hybrid', t('hybrid')]
                ],
                value: this.datax.mapType || 'roadmap'
            }
        ]);

        return this.layout;
    }

});
