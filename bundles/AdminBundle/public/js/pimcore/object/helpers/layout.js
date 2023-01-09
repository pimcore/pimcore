/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */



pimcore.registerNS("pimcore.object.helpers.layout");
/**
 * @private
 */
pimcore.object.helpers.layout = {

    /**
     * specify which children a layout can have
     * @param source
    */
    getAllowedTypes : function (source) {
        var allowedTypes = {
            accordion: ["panel","region","tabpanel","text","iframe"],
            fieldset: ["data","text","iframe"],
            fieldcontainer: ["data","text","iframe"],
            panel: ["data","region","tabpanel","button","accordion","fieldset", "fieldcontainer","panel","text","html", "iframe"],
            region: ["panel","accordion","tabpanel","text","localizedfields","iframe"],
            tabpanel: ["panel", "region", "accordion","text","localizedfields","iframe", "tabpabel"],
            button: [],
            text: [],
            root: ["panel","region","tabpanel","accordion","text","iframe", "button","fieldcontainer", "fieldset"],
            localizedfields: ["panel","tabpanel","accordion","fieldset", "fieldcontainer", "text","region","button","iframe"],
            block: ["panel","tabpanel","accordion","fieldset", "fieldcontainer", "text","region","button","iframe"]
        };

        const prepareClassLayoutContextMenu = new CustomEvent(pimcore.events.prepareClassLayoutContextMenu, {
            detail: {
                allowedTypes: allowedTypes,
                source: source
            }
        });

        document.dispatchEvent(prepareClassLayoutContextMenu);

        return allowedTypes;
    }
};
