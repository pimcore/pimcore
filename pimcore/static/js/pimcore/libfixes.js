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

// fixes for composite field => getFieldValues() doesn't work
// read more here http://www.sencha.com/forum/showthread.php?99021&mode=linear
var _initComponent = Ext.form.CompositeField.prototype.initComponent;
Ext.override(Ext.form.CompositeField, {
    initComponent: function(){
        _initComponent.apply(this, arguments);
        this.innerCt.onwerCt = this;
    },
    bubble : Ext.Container.prototype.bubble,
    cascade : Ext.Container.prototype.cascade,
    findById : Ext.Container.prototype.findById,
    findByType : Ext.Container.prototype.findByType,
    find : Ext.Container.prototype.find,
    findBy : Ext.Container.prototype.findBy,
    get : Ext.Container.prototype.get,
    setValue : undefined
});


// fixes Drag & Drop#
Ext.dd.DragDropMgr.getZIndex = function(element) {
    var body = document.body,
        z,
        zIndex = -1;
    var overTargetEl = element;

    element = Ext.getDom(element);
    while (element !== body) {

        // this fixes the problem
        if(!element) {
            this._remove(overTargetEl); // remove the drop target from the manager
            break;
        }
        // fix end

        if (!isNaN(z = Number(Ext.fly(element).getStyle('zIndex')))) {
            zIndex = z;
        }
        element = element.parentNode;
    }
    return zIndex;
};
