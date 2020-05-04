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

pimcore.registerNS("pimcore.asset.metadata.data.data");
pimcore.asset.metadata.data.data = Class.create({

    allowIn: {
        predefined: true,
        custom: true
    },


    initData: function (config) {
        config = config || {};
    },

    getSpecificPanelItems: function (datax, inEncryptedField) {
        var specificItems = [{
            xtype: "numberfield",
            fieldLabel: t("width"),
            name: "width",
            value: datax.width
        }
        ];

        return specificItems;
    },

    getLayout: function ($super) {

        $super();

        this.specificPanel.removeAll();
        var specificItems = this.getSpecificPanelItems(this.datax);
        this.specificPanel.add(specificItems);

        return this.layout;
    }
});
