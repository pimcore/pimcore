/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.element.abstractPreview");
/**
 * @private
 */
pimcore.element.abstractPreview = Class.create({

    initialize: function (element) {
        this.element = element;
        this.mode = "full";
        this.availableHeight = null;
    },

    getToolbar: function () {
        return [
            {
                text: t("desktop"),
                iconCls: "pimcore_icon_desktop",
                handler: this.setFullMode.bind(this)
            },
            {
                text: t("tablet"),
                iconCls: "pimcore_icon_tablet",
                handler: this.setMode.bind(this, {device: "tablet", width: 1024, height: 768})
            },
            {
                text: t("phone"),
                iconCls: "pimcore_icon_mobile",
                handler: this.setMode.bind(this, {device: "phone", width: 375, height: 667})
            },
            {
                text: t("phone"),
                iconCls: "pimcore_icon_mobile_landscape",
                handler: this.setMode.bind(this, {device: "phone", width: 667, height: 375})
            }
        ];
    },

    setFullMode: function () {
        this.getIframe().applyStyles({
            position: "relative",
            border: "0",
            width: "100%",
            height: (this.availableHeight - 7) + "px",
            top: "initial",
            left: "initial"
        });

        this.loadCurrentPreview();
    },

    setMode: function (mode) {
        var iframe = this.getIframe();
        var availableWidth = this.framePanel.getWidth() - 10;
        var availableHeight = this.framePanel.getHeight() - 10;

        if (availableWidth < mode["width"]) {
            Ext.MessageBox.alert(t("error"), t("screen_size_to_small"));
            return;
        }

        if (availableHeight < mode["height"]) {
            mode["height"] = availableHeight;
        }

        var top = Math.floor((availableHeight - mode["height"]) / 2);
        var left = Math.floor((availableWidth - mode["width"]) / 2);

        iframe.applyStyles({
            position: "absolute",
            border: "5px solid #323232",
            width: mode["width"] + "px",
            height: mode["height"] + "px",
            top: top + "px",
            left: left + "px"
        });

        this.mode = mode["device"];

        this.loadCurrentPreview();
    },

    onLayoutResize: function (el, width, height) {
        if (this.mode === "full") {
            this.setLayoutFrameDimensions(width, height);
        }

        this.availableHeight = height;
    },

    createLoadingMask: function () {
        if (!this.loadMask) {
            this.loadMask = new Ext.LoadMask({
                target: this.framePanel,
                msg: t("please_wait")
            });

            this.loadMask.enable();
        }
    },

    setLayoutFrameDimensions: function (width, height) {
        this.getIframe().setStyle({
            height: (height - 7) + "px"
        });
    },

    getIframe: function () {
        return Ext.get(this.frameId);
    },

    refresh: function () {
        this.createLoadingMask();
        this.loadMask.show();
        this.element.saveToSession(function () {
            if (this.preview) {
                this.preview.loadCurrentPreview();
            }
        }.bind(this.element));
    }
});
