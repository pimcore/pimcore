/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
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
        classificationstore : false,
        block: true
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        this.specificPanel.add([
            {
                xtype: 'numberfield',
                fieldLabel: t('latitude'),
                name: 'lat',
                value: this.datax.lat || 0,
                decimalPrecision: 8,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.01
            },{
                xtype: 'numberfield',
                fieldLabel: t('longitude'),
                name: 'lng',
                value: this.datax.lng || 0,
                decimalPrecision: 8,
                minValue: 0,
                allowDecimals: true,
                incrementValue: 0.01
            },{
                xtype: 'numberfield',
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
    },

    /**
     * Check of the Google Map is initialized
     *
     * @returns {boolean}
     */
    checkIfKeyIsAvailable: function(){
        if(typeof gmapInitialize == 'undefined') {
            return false;
        }else{
            return true;
        }
    }

});
