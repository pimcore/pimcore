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

pimcore.registerNS("pimcore.object.preview");
/**
 * @private
 */
Ext.define('pimcore.object.preview', {
    extend: pimcore.element.abstractPreview,

    getLayout: function () {
        if (this.framePanel == null) {

            this.frameId = 'object_preview_iframe_' + this.element.id;
            var toolbar = this.getToolbar();

            if(this.element.data.general.previewConfig) {
                let paramPanel = this.getParamsPanel();
                toolbar.push(paramPanel);
            }
            this.framePanel = Ext.create('Ext.panel.Panel', {
                title: t('preview'),
                border: false,
                autoScroll: false,
                closable: false,
                iconCls: "pimcore_material_icon_devices pimcore_material_icon",
                tbar: toolbar,
                bodyStyle: "background:#323232;",
                html: '<iframe src="about:blank" style="width: 100%;" frameborder="0" id="' + this.frameId + '"></iframe>',
                listeners: {
                    afterrender: function () {
                        Ext.get(this.getIframe()).on('load', function () {
                            this.iFrameLoaded();
                        }.bind(this));
                    }.bind(this)
                }
            });

            this.framePanel.on("resize", this.onLayoutResize.bind(this));
            this.framePanel.on("activate", this.refresh.bind(this));
        }

        return this.framePanel;
    },

    iFrameLoaded: function () {
        if (this.loadMask) {
            this.loadMask.hide();
        }
    },

    loadCurrentPreview: function () {
        let params = {};
        if(this.paramSelects && this.paramSelects.length) {
            for(let i = 0; i < this.paramSelects.length; i++) {
                if(this.paramSelects[i].getValue()) {
                    params[this.paramSelects[i].name] = this.paramSelects[i].getValue();
                }
            }
        }

        var date = new Date();
        params['id'] = this.element.data.general.id;
        params['_dc'] = date.getTime();

        var url = Routing.generate('pimcore_admin_dataobject_dataobject_preview', params);

        try {
            this.getIframe().dom.src = url;
        }
        catch (e) {
            console.log(e);
        }
    },

    getParamsPanel: function() {
        var that = this;
        this.paramSelects = [];

        let params = this.element.data.general.previewConfig;
        for (let i = 0; i < params.length; i++) {
            let selectOptions = Object.entries(params[i].values);
            selectOptions.forEach(el => el.reverse());

            let paramSelect = Ext.create('Ext.form.ComboBox', {
                fieldLabel: params[i].label ? params[i].label : params[i].name,
                value: params[i].defaultValue ? params[i].defaultValue : '',
                name: params[i].name,
                store: selectOptions,
                queryMode: 'local',
                displayField: 'name',
                valueField: 'abbr',
                margin: "10 10 10 10",
                labelWidth: '',
                listeners: {
                    select: function(combo, records, eOpts) {
                        that.loadCurrentPreview();
                    }
                },
            });

            this.paramSelects.push(paramSelect);
        }

        return Ext.create('Ext.panel.Panel', {
            layout: {
                type: 'hbox',
                align: 'stretch',
            },
            items: this.paramSelects,
        });
    },

    setLayoutFrameDimensions: function (width, height) {
        this.getIframe().setStyle({
            height: (height - 48) + "px"
        });
    }
});
