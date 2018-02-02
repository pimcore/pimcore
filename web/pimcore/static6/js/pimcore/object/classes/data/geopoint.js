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

pimcore.registerNS('pimcore.object.classes.data.geopoint');
pimcore.object.classes.data.geopoint = Class.create(pimcore.object.classes.data.geo.abstract, {

    type: 'geopoint',

    initialize: function (treeNode, initData) {
        this.type = "geopoint";

        this.initData(initData);

        this.treeNode = treeNode;

        this.checkGoogleMapsAPI();
    },

    getTypeName: function () {
        return t("geopoint");
    },

    getGroup: function () {
            return "geo";
    },

    getIconClass: function () {
        return "pimcore_icon_geopoint";
    }

});
