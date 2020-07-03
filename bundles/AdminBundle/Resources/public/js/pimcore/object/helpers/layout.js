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



pimcore.registerNS("pimcore.object.helpers.layout");
pimcore.object.helpers.layout = {

    /**
     * specify which childs a layout can have
     * @param source
    */
    getAllowedTypes : function (source) {
        // specify which childs a layout can have
        var allowedTypes = {
            accordion: ["panel","region","tabpanel","text","iframe"],
            fieldset: ["data","text","iframe"],
            fieldcontainer: ["data","text","iframe"],
            panel: ["data","region","tabpanel","button","accordion","fieldset", "fieldcontainer","panel","text","html", "iframe"],
            region: ["panel","accordion","tabpanel","text","localizedfields","iframe"],
            tabpanel: ["panel", "region", "accordion","text","localizedfields","iframe", "tabpabel"],
            button: [],
            text: [],
            root: ["panel","region","tabpanel","accordion","text","iframe"],
            localizedfields: ["panel","tabpanel","accordion","fieldset", "fieldcontainer", "text","region","button","iframe"],
            block: ["panel","tabpanel","accordion","fieldset", "fieldcontainer", "text","region","button","iframe"]
        };

        pimcore.plugin.broker.fireEvent("prepareClassLayoutContextMenu", allowedTypes, source);
        return allowedTypes;
    }
};
