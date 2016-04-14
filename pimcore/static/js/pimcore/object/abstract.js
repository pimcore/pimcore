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