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

pimcore.registerNS('pimcore.object.classes.data.geobounds');
pimcore.object.classes.data.geobounds = Class.create(pimcore.object.classes.data.geo.abstract, {

    type: "geobounds",

    initialize: function (treeNode, initData) {
        this.type = 'geobounds';

        this.initData(initData);

        // overwrite default settings
        this.availableSettingsFields = ["name","title","mandatory","noteditable","invisible","index","style"];

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t('geobounds');
    },

    getGroup: function () {
            return 'geo';
    },

    getIconClass: function () {
        return 'pimcore_icon_geobounds';
    }

});
