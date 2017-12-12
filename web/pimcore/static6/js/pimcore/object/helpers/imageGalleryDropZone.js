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
Ext.define('pimcore.object.helpers.ImageGalleryDropZone', {
    extend: 'Ext.dd.DropTarget',

    constructor: function(portal, dropConfig, proxyConfig) {
        this.portal = portal;
        this.proxyConfig = proxyConfig;
        Ext.dd.ScrollManager.register(portal.body);
        Portal.view.PortalDropZone.superclass.constructor.call(this, portal.body, dropConfig);

        portal.body.ddScrollConfig = this.ddScrollConfig;
    },

    ddScrollConfig: {
        vthresh: 50,
        hthresh: -1,
        animate: true,
        increment: 200
    },

    createEvent: function(dd, e, data, p, pos) {
        return {
            portal: this.portal,
            panel: data.panel,
            p: p,
            position: pos,
            data: data,
            source: dd,
            rawEvent: e,
            status: this.dropAllowed
        };
    },

    notifyOver: function(dd, e, data) {
        var xy = e.getXY(), portal = this.portal, px = dd.proxy;

        var items = portal.items.items;

        var currentPosition = 0;

        // TODO note that the last one is reserved for the placeholder

        var itemLength = items.length;
        for(var len = itemLength; currentPosition < len; currentPosition++){
            var cur = items[currentPosition];
            if (cur.id == dd.panel.id) {
                break;
            }
        }

        var match = false;
        var pos = 0;
        var col = -1;
        var afterP = false;
        var afterMatch = false;

        for(var len = itemLength; pos < len; pos++){
            var p = items[pos];
            var h = p.el.getHeight();
            var x = p.el.getX();
            var y = p.el.getY();
            var w = p.el.getWidth();

            if(xy[1] >y && (xy[1] < (y + h)) && xy[0] > x && (xy[0] < (x + w))) {
                match = true;
                break;
            }else if (pos == len -1 && currentPosition != len - 1) {
                if(xy[1] >y && xy[0] > (x + w)) {
                    afterMatch = true;
                    pos = false;
                    break;
                }


            }
        }
        
        var overEvent = this.createEvent(dd, e, data, col, p, pos);

        if(portal.fireEvent('validatedrop', overEvent) !== false &&
            portal.fireEvent('beforedragover', overEvent) !== false){

            // make sure proxy width is fluid
            // px.getProxy().setWidth('auto');

            var proxyDom = dd.panelProxy.proxy.dom;

            var proxyStyle = "width:" + this.proxyConfig.width +  "px; height:" + this.proxyConfig.height + "px;float: left;margin-bottom: 0px";
            proxyDom.setAttribute("style", proxyStyle);

            if (match) {
                var parent = p.el.dom.parentNode;
                dd.panelProxy.moveProxy(parent, p.el.dom);
                // console.log("current position = " + currentPosition +  " pos= " + pos);
                if (currentPosition < pos) {
                    pos--;
                }
                this.lastPos = {p: p, pos: pos, parent: p.ownerCt};
            } else if (afterMatch) {
                var parent = p.el.dom.parentNode;
                dd.panelProxy.moveProxy(parent, null);

                if (this.proxyConfig.respectPlaceholder) {
                    this.lastPos = {p: items[itemLength - 2], pos: itemLength - 2, parent: p.ownerCt};

                } else {
                    this.lastPos = {p: p, pos: pos, parent: p.ownerCt};
                }
            }

            portal.fireEvent('dragover', overEvent);

            return overEvent.status;
        }else{
            return overEvent.status;
        }
    },

    notifyOut: function() {
        delete this.grid;
    },

    notifyDrop: function(dd, e, data) {
        delete this.grid;
        if (!this.lastPos) {
            return;
        }
        var p = this.lastPos.p,
            pos = this.lastPos.pos,
            panel = dd.panel,
            parent = this.lastPos.parent,
            dropEvent = this.createEvent(dd, e, data, p, pos);

        if (this.portal.fireEvent('validatedrop', dropEvent) !== false && 
            this.portal.fireEvent('beforedrop', dropEvent) !== false) {

            Ext.suspendLayouts();
            
            // make sure panel is visible prior to inserting so that the layout doesn't ignore it
            panel.el.dom.style.display = '';
            dd.panelProxy.hide();
            dd.proxy.hide();

            if (pos !== false) {
                parent.insert(pos, panel);
            } else {
                parent.add(panel);
            }

            if (this.proxyConfig.callback) {
                this.proxyConfig.callback.notifyDrop();
            }

            Ext.resumeLayouts(true);

            this.portal.fireEvent('drop', dropEvent);

        }
        
        delete this.lastPos;
        return true;
    },


    // unregister the dropzone from ScrollManager
    unreg: function() {
        Ext.dd.ScrollManager.unregister(this.portal.body);
        Portal.view.PortalDropZone.superclass.unreg.call(this);
        delete this.portal.afterLayout;
    }
});
