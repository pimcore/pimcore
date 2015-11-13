/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


pimcore.registerNS("pimcore.plugin.onlineshop.VoucherSeriesTab");

pimcore.plugin.onlineshop.VoucherSeriesTab = Class.create({

    title: t('plugin_onlineshop_vouchertoolkit_tab'),
    iconCls: 'plugin_voucherservice_icon',
    src: '/plugin/OnlineShop/voucher/voucher-code-tab',
    id: null,

    initialize: function(object, type) {
        this.object = object;
        this.id = object.id;
        this.src = this.src + "?id=" + this.id;
        this.type = type;
    },

    getLayout: function () {
        if (this.panel == null) {

            this.reloadButton = new Ext.Button({
                text: t("reload"),
                iconCls: "pimcore_icon_reload",
                handler: this.reload.bind(this)
            });


            this.panel = new Ext.Panel({
                id: "plugin_onlineshop_vouchertoolkit_tab_" + this.id,
                title: this.title,
                iconCls: this.iconCls,
                border: false,
                layout: "fit",
                closable: false,
                bodyStyle: "-webkit-overflow-scrolling:touch;",
                html: '<iframe src="about:blank" frameborder="0" width="100%" id="plugin_onlineshop_vouchertoolkit_tab_frame_' + this.id + '"></iframe>',
                tbar: [this.reloadButton]
            });

            this.panel.on("resize", this.onLayoutResize.bind(this));
            var that = this;
            this.panel.on("afterrender", function(e){
                that.panel.on("activate", function(e){
                    that.reload();
                });
            });

        }
        return this.panel;

    },

    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get("plugin_onlineshop_vouchertoolkit_tab_frame_" + this.id).setStyle({
            height: (height - 50) + "px"
        });
    },

    reload: function () {
        try {
            var d = new Date();
            Ext.get("plugin_onlineshop_vouchertoolkit_tab_frame_" + this.id).dom.src = this.src;
        }
        catch (e) {
            console.log(e);
        }
    }

});