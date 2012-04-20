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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.document.edit.dnd");
pimcore.document.edit.dnd = Class.create({

    dndManager: null,

    initialize: function(parentExt, body, iframeElement, dndZones) {

        this.dndManager = parentExt.dd.DragDropMgr;
        var iFrameElement = parent.Ext.get('document_iframe_' + window.editWindow.document.id);
        
        parentExt.EventManager.on(body, 'mousemove', this.ddMouseMove.bind(this));
        parentExt.EventManager.on(body, 'mouseup', this.ddMouseUp.bind(this));

        var dd = new parent.Ext.dd.DropZone(iframeElement, {
            ddGroup: "element",
            validElements: dndZones,

            getTargetFromEvent: function(e) {
                var element = null;

                for (var i = 0; i < this.validElements.length; i++) {
                    element = this.validElements[i];
                    if (element.dndOver) {
                        if (element.reference) {
                            this.onNodeDrop = element.reference.onNodeDrop.bind(element.reference);
                            this.onNodeOver = element.reference.onNodeOver.bind(element.reference);
                            return element;
                        }
                    }
                }
            }
        });
        
        window.setInterval(this.setIframeOffset.bind(this),1000);
        this.setIframeOffset();
    },


    ddMouseMove: function (e) {
        // update the xy of the event if necessary
        this.setDDPos(e);
        // *** Note that the 'this' scope is the drag drop manager
        this.dndManager.handleMouseMove(e);
    },

    ddMouseUp : function (e) {
        // update the xy of the event if necessary
        this.setDDPos(e);
        // *** Note that the 'this' scope is the drag drop manager
        this.dndManager.handleMouseUp(e);
    },


    setDDPos: function (e) {

        var scrollTop = 0;
        var scrollLeft = 0;

        var doc = (window.contentDocument || window.document);
        scrollTop = doc.documentElement.scrollTop || doc.body.scrollTop;
        scrollLeft = doc.documentElement.scrollLeft || doc.body.scrollLeft;

        e.xy = [e.xy[0] + this.iframeOffset[0] - scrollLeft, e.xy[1] + this.iframeOffset[1] - scrollTop];
    },
    
    setIframeOffset: function () {
        try {
            this.iframeOffset = parent.Ext.get('document_iframe_' + window.editWindow.document.id).getOffsetsTo(parent.Ext.getBody());
        } catch (e) {
            
        }
    }

});
