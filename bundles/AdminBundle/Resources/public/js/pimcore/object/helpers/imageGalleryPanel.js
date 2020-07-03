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
Ext.define('pimcore.object.helpers.ImageGalleryPanel', {
    extend: 'Ext.panel.Panel',

    requires: [
        'pimcore.object.helpers.ImageGalleryDropZone'
    ],

    cls: 'x-portal',
    // bodyCls: 'x-portal-body',

    manageHeight: true,

    initComponent : function() {
        // Implement a Container beforeLayout call from the layout to this Container
        this.layout = {
            type : 'column'
        };
        this.callParent();
    },

    // private
    initEvents : function(){
        this.callParent();
        this.dd = Ext.create('pimcore.object.helpers.ImageGalleryDropZone', this, {}, this.proxyConfig);
    },

    // private
    beforeDestroy : function() {
        if (this.dd) {
            this.dd.unreg();
        }
        this.callParent();
    }
});
