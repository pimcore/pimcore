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

pimcore.registerNS('pimcore.object.classes.data.geopolygon');
pimcore.object.classes.data.geopolygon = Class.create(pimcore.object.classes.data.geo.abstract, {

    type: 'geopolygon',

    initialize: function (treeNode, initData) {
        this.type = 'geopolygon';

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ['name','title','noteditable','invisible','style'];

        this.treeNode = treeNode;

        this.checkGoogleMapsAPI();
    },

    getTypeName: function () {
        return t('geopolygon');
    },

    getGroup: function () {
            return 'geo';
    },

    getIconClass: function () {
        return 'pimcore_icon_geopolygon';
    }

});
