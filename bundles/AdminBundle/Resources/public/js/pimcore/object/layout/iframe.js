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

pimcore.registerNS("pimcore.object.layout.iframe");
pimcore.object.layout.iframe = Class.create(pimcore.object.abstract, {

    initialize: function (config, context) {
        this.config = config;
        this.context = context;
        this.context["renderingData"] = this.config.renderingData;
        this.context["name"] = this.config.name;
    },

    getLayout: function () {

        var queryString = Ext.Object.toQueryString({
            context: Ext.encode(this.context)
        });
        var html = '<iframe src="' + this.config.iframeUrl + "?" + queryString + '"frameborder="0" width="100%" height="' + (this.config.height - 38) + '" style="display: block"></iframe>';

        this.component = new Ext.Panel({
            border: true,
            style: "margin-bottom: 10px",
            cls: "pimcore_layout_iframe_border",
            height: this.config.height,
            width: this.config.width,
            scrollable: true,
            html: html,
            tbar: {
                items: [
                    {
                        xtype: "tbtext",
                        text: this.config.title
                    }, "->",
                    {
                        xtype: 'button',
                        text: t('refresh'),
                        iconCls: 'pimcore_icon_reload',
                        handler: function () {
                            var key = "object_" + this.context.objectId;

                            if (pimcore.globalmanager.exists(key)) {
                                var objectTab = pimcore.globalmanager.get(key);
                                objectTab.saveToSession(function () {
                                    this.component.setHtml(html);
                                }.bind(this));


                            }


                        }.bind(this)
                    }
                ]
            }
        });
        return this.component;

    }
});
