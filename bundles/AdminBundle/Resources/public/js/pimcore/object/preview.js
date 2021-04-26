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
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.object.preview");
pimcore.object.preview = Class.create({

    initialize: function(object) {
        this.object = object;
    },


    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('object_"
                                        + this.object.data.general.o_id + "').preview.iFrameLoaded()";

            this.frameId = 'object_preview_iframe_' + this.object.id;

            let toolbar = [];
            if(this.object.data.general.previewConfig) {
                let paramPanel = this.getParamsPanel();
                toolbar.push(paramPanel);
            }
            this.layout = Ext.create('Ext.panel.Panel', {
                title: t('preview'),
                border: false,
                autoScroll: false,
                closable: false,
                iconCls: "pimcore_material_icon_devices pimcore_material_icon",
                tbar: toolbar,
                html: '<iframe src="about:blank" style="width: 100%;" onload="' + iframeOnLoad
                    + '" frameborder="0" id="' + this.frameId + '"></iframe>'
            });

            this.layout.on("resize", this.setLayoutFrameDimensions.bind(this));
            this.layout.on("activate", this.refresh.bind(this));
        }

        return this.layout;
    },


    createLoadingMask: function() {
        if (!this.loadMask) {
            this.loadMask = new Ext.LoadMask(
                {
                    target: this.layout,
                    msg:t("please_wait")
                });

             //= new Ext.LoadMask(this.layout.getEl(), {msg: t("please_wait")});
            this.loadMask.enable();
        }
    },

    setLayoutFrameDimensions: function (el, width, height, rWidth, rHeight) {
        Ext.get(this.frameId).setStyle({
            height: (height-7) + "px"
        });
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
        params['id'] = this.object.data.general.o_id;
        params['_dc'] = date.getTime();

        var url = Routing.generate('pimcore_admin_dataobject_dataobject_preview', params);

        try {
            Ext.get(this.frameId).dom.src = url;
        }
        catch (e) {
            console.log(e);
        }
    },

    refresh: function () {
        this.createLoadingMask();
        this.loadMask.enable();
        this.object.saveToSession(function () {
            if (this.preview) {
                this.preview.loadCurrentPreview();
            }
        }.bind(this.object));
    },

    getParamsPanel: function() {

        var that = this;
        this.paramSelects = [];

        let params = this.object.data.general.previewConfig;
        for(let i=0; i<params.length; i++) {
            let selectOptions = Object.entries(params[i].values);
            selectOptions.forEach(el => el.reverse());

            let paramSelect = Ext.create('Ext.form.ComboBox', {
                fieldLabel: params[i].label ? params[i].label : params[i].name,
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
    }
});
