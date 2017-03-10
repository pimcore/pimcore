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

pimcore.registerNS("pimcore.document.tags.pdf");
pimcore.document.tags.pdf = Class.create(pimcore.document.tag, {

    initialize: function(id, name, options, data, inherited) {
        this.id = id;
        this.name = name;
        this.data = {};

        this.options = this.parseOptions(options);


        // set width
        if (!this.options["height"]) {
            this.options.height = 100;
        }

        if (data) {
            this.data = data;
        }
        this.setupWrapper();
        this.options.name = id + "_editable";
        this.element = new Ext.Panel(this.options);

        this.element.on("render", function (el) {

            // contextmenu
            el.getEl().on("contextmenu", this.onContextMenu.bind(this));

            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

            el.getEl().setStyle({
                position: "relative"
            });

            var body = this.getBody();
            body.insertHtml("beforeEnd",'<div class="pimcore_tag_droptarget"></div>');
            body.addCls("pimcore_tag_image_empty");
        }.bind(this));

        this.element.render(id);


        // insert image
        if (this.data) {
            this.updateImage();
        }
    },

    onContextMenu: function (e) {

        var menu = new Ext.menu.Menu();

        if(this.data.id) {

            menu.add(new Ext.menu.Item({
                text: t('add_metadata'),
                iconCls: "pimcore_icon_metadata",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.openMetadataWindow();
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('empty'),
                iconCls: "pimcore_icon_delete",
                handler: function (item) {
                    item.parentMenu.destroy();

                    this.empty();

                }.bind(this)
            }));
            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (item) {
                    item.parentMenu.destroy();
                    pimcore.helpers.openAsset(this.data.id, "document");
                }.bind(this)
            }));

            if (pimcore.elementservice.showLocateInTreeButton("document")) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: "pimcore_icon_show_in_tree",
                    handler: function (item) {
                        item.parentMenu.destroy();
                        pimcore.treenodelocator.showInTree(this.data.id, "asset");
                    }.bind(this)
                }));
            }
        }

        menu.add(new Ext.menu.Item({
            text: t('search'),
            iconCls: "pimcore_icon_search",
            handler: function (item) {
                item.parentMenu.destroy();
                this.openSearchEditor();
            }.bind(this)
        }));

        menu.add(new Ext.menu.Item({
            text: t('upload'),
            iconCls: "pimcore_icon_upload",
            handler: function (item) {
                item.parentMenu.destroy();
                this.uploadDialog();
            }.bind(this)
        }));

        menu.showAt(e.getXY());
        e.stopEvent();
    },

    uploadDialog: function () {
        pimcore.helpers.assetSingleUploadDialog(this.options["uploadPath"], "path", function (res) {
            try {
                var data = Ext.decode(res.response.responseText);
                if(data["id"] && data["type"] == "document") {
                    this.resetData();
                    this.data.id = data["id"];

                    this.updateImage();
                    this.reload();
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this));
    },

    onNodeOver: function(target, dd, e, data) {
        var record = data.records[0];
        if (this.dndAllowed(record)) {
            return Ext.dd.DropZone.prototype.dropAllowed;
        }
        else {
            return Ext.dd.DropZone.prototype.dropNotAllowed;
        }
    },

    onNodeDrop: function (target, dd, e, data) {
        var record = data.records[0];

        if (record.data.type == "document") {
            this.resetData();
            this.data.id = record.data.id;

            this.updateImage();
            this.reload();

            return true;
        }
    },

    dndAllowed: function(record) {

        if(record.data.elementType!="asset" || record.data.type!="document"){
            return false;
        } else {
            return true;
        }

    },

    openSearchEditor: function () {
        pimcore.helpers.itemselector(false, this.addDataFromSelector.bind(this), {
            type: ["asset"],
            subtype: {
                asset: ["document"]
            }
        },
            {
                context: this.getContext()
            });
    },

    addDataFromSelector: function (item) {
        if(item) {
            this.resetData();
            this.data.id = item.id;

            this.updateImage();
            this.reload();

            return true;
        }
    },

    resetData: function () {
        this.data = {
            id: null
        };
    },

    empty: function () {

        this.resetData();

        this.updateImage();
        this.getBody().addCls("pimcore_tag_image_empty");
        this.reload();
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html
        // (only in configure)
        var body = Ext.get(this.element.getEl().query("." + Ext.baseCSSPrefix + "autocontainer-innerCt")[0]);
        return body;
    },

    updateImage: function () {

        var path = "";
        var existingImage = this.getBody().dom.getElementsByTagName("img")[0];
        if (existingImage) {
            Ext.get(existingImage).remove();
        }

        if (!this.data.id) {
            return;
        }

        path = "/admin/asset/get-document-thumbnail?id=" + this.data.id + "&width=" + this.element.getEl().getWidth()
                        + "&aspectratio=true&" + Ext.urlEncode(this.data);

        var image = document.createElement("img");
        image.src = path;

        this.getBody().appendChild(image);
        this.getBody().removeCls("pimcore_tag_image_empty");

        this.updateCounter = 0;
        this.updateDimensionsInterval = window.setInterval(this.updateDimensions.bind(this), 1000);
    },

    reload : function () {
        this.reloadDocument();
    },

    updateDimensions: function () {

        var image = this.element.getEl().dom.getElementsByTagName("img")[0];
        if (!image) {
            return;
        }
        image = Ext.get(image);

        var width = image.getWidth();
        var height = image.getHeight();

        if (width > 1 && height > 1) {
            this.element.setWidth(width);
            this.element.setHeight(height);

            clearInterval(this.updateDimensionsInterval);
        }

        if (this.updateCounter > 20) {
            // only wait 20 seconds until image must be loaded
            clearInterval(this.updateDimensionsInterval);
        }

        this.updateCounter++;
    },

    hasMetaData : function(page){
        if(this.hotspotStore[page] || this.chapterStore[page] || this.textStore[page]){
            return true;
        }else{
            return false;
        }
    },

    openMetadataWindow: function() {
        top.pimcore.helpers.editmode.openPdfEditPanel.bind(this)();
    },

    requestTextForCurrentPage : function(){
        Ext.Ajax.request({
            url: "/admin/asset/get-text/",
            params: {
                id: this.data.id,
                page : this.currentPage
            },
            success: function(response) {
                var res = Ext.decode(response.responseText);
                if(res.success){
                    this.textArea.setValue(res.text);
                }
            }.bind(this)
        });
    },

    saveCurrentPage: function () {
        if(this.currentPage) {
            var chapterText = this.metaDataWindow.getComponent("pageContainer").getEl().query('[name="chapter"]')[0].value;
            if(!chapterText){
                            delete this.chapterStore[this.currentPage];
                        }else{
                            this.chapterStore[this.currentPage] = chapterText;
                        }

            var hotspots = this.metaDataWindow.getComponent("pageContainer").body.query(".pimcore_pdf_hotspot");
            var hotspot = null;
            var metaData = null;

            var imgEl = Ext.get(this.metaDataWindow.getComponent("pageContainer").body.query("img")[0]);
            var originalWidth = imgEl.getWidth();
            var originalHeight = imgEl.getHeight();

            this.hotspotStore[this.currentPage] = [];

            for(var i=0; i<hotspots.length; i++) {
                hotspot = Ext.get(hotspots[i]);

                var dimensions = hotspot.getStyle(["top","left","width","height"]);

                metaData = null;
                if(this.hotspotMetaData[hotspot.getAttribute("id")]) {
                    metaData = this.hotspotMetaData[hotspot.getAttribute("id")];
                }

                this.hotspotStore[this.currentPage].push({
                    top: intval(dimensions.top) * 100 / originalHeight,
                    left:  intval(dimensions.left) * 100 / originalWidth,
                    width: intval(dimensions.width) * 100 / originalWidth,
                    height: intval(dimensions.height) * 100 / originalHeight,
                    data: metaData
                });
            }

            if(this.hotspotStore[this.currentPage].length < 1) {
                delete this.hotspotStore[this.currentPage];
            }

            var metaData = this.hasMetaData(this.currentPage);

            Ext.each(this.pagesContainer.body.query('.nr'), function(value) {
                if(parseInt($(value).text()) == this.currentPage){
                    metaData ? $(value).addClass('hasMetadata') : $(value).removeClass('hasMetadata');
               }
            }.bind(this));

        }

    },


    getValue: function () {
        return this.data;
//        return value;
    },

    getType: function () {
        return "pdf" +
            "";
    }
});