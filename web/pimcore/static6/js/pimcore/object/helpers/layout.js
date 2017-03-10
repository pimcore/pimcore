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



pimcore.registerNS("pimcore.object.helpers.layout");
pimcore.object.helpers.layout = {

    /**
     * specify which childs a layout can have
     * @param source
    */
    getAllowedTypes : function (source) {
        // specify which childs a layout can have
        var allowedTypes = {
            accordion: ["panel","region","tabpanel","text"],
            fieldset: ["data","text"],
            fieldcontainer: ["data","text"],
            panel: ["data","region","tabpanel","button","accordion","fieldset", "fieldcontainer","panel","text","html"],
            region: ["panel","accordion","tabpanel","text","localizedfields"],
            tabpanel: ["panel", "region", "accordion","text","localizedfields"],
            button: [],
            text: [],
            root: ["panel","region","tabpanel","accordion","text"],
            localizedfields: ["panel","tabpanel","accordion","fieldset", "fieldcontainer", "text","region","button"],
            block: ["panel","tabpanel","accordion","fieldset", "fieldcontainer", "text","region","button"]
        };

        pimcore.plugin.broker.fireEvent("prepareClassLayoutContextMenu", allowedTypes, source);
        return allowedTypes;
    }
};
