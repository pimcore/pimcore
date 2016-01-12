/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
