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

pimcore.registerNS("pimcore.object.abstract");
pimcore.object.abstract = Class.create(pimcore.element.abstract, {

    selectInTree: function (type) {

        if(type != "variant" || this.data.general.showVariants) {
            try {
                Ext.getCmp("pimcore_panel_tree_objects").expand();
                var tree = pimcore.globalmanager.get("layout_object_tree");
                pimcore.helpers.selectPathInTree(tree.tree, this.data.idPath);
            } catch (e) {
                console.log(e);
            }
        }
    }
});