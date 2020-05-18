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

pimcore.registerNS("pimcore.element.tag.configuration");
pimcore.element.tag.configuration = Class.create({

    dataUrl: '/admin/tags/get-translations-for-tag/',
    updateUrl: '/admin/tags/update-translation-for-tag',

    initialize: function() {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.getLayout());
        tabPanel.setActiveItem("tag_configuration");

        this.getLayout().on("destroy", function () {
            pimcore.globalmanager.remove("element_tag_configuration");
        });

        pimcore.layout.refresh();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("tag_configuration");
    },

    getLayout: function () {

        if (this.layout == null) {

            var tree = new pimcore.element.tag.tree();

            treeLayout = tree.getLayout();
            treeLayout.addListener({
                itemclick: function(tree, record){
                    this.fillPanelRow(record);
                }.bind(this),
            });

            this.layout = new Ext.Panel({
                id: "tag_configuration",
                title: t('element_tag_configuration'),
                iconCls: "pimcore_icon_element_tags",
                items: [
                    treeLayout,
                    this.getEditTableLayout(),
                ],
                layout: "border",
                closable: true,
            });

            tree.setFilterFieldWidth(250);
        }

        return this.layout;
    },

    fillPanelRow: function(record){


        this.store = pimcore.helpers.grid.buildDefaultStore(
            this.dataUrl + record.id,
            this.columnConfig.map(function (item) {
                return item.dataIndex;
            }),
            pimcore.helpers.grid.getDefaultPageSize(-1)
        );
        this.store.load();

        this.gridPanel.setStore(this.store);
    },

    getEditTableLayout: function(){
        if(!this.editPanel){
            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    validateedit: function(editor, context) {
                        editor.editors.each(function (e) {
                            Ext.Ajax.request({
                                url: this.updateUrl,
                                method: 'POST',
                                params: {
                                    id: context.record.id,
                                    language: context.field,
                                    translation: e.getValue(),
                                },
                                success: function(){
                                    this.store.reload();
                                }.bind(this)
                            });
                        }.bind(this));

                        editor.editors.clear();
                    }.bind(this)
                }
            });

            var languages = pimcore.settings.websiteLanguages;
            var dateRenderer = function (d) {
                return d ? Ext.Date.format(new Date(d * 1000), "Y-m-d H:i:s") : '-';
            };

            this.columnConfig = [{text: 'id', dataIndex: 'id', hidden: true,}];
            for(var i = 0; i < languages.length; i++){
                this.columnConfig.push({
                    cls: 'x-column-header_' + languages[i].toLowerCase(),
                    text: pimcore.available_languages[languages[i]],
                    dataIndex: '_' + languages[i],
                    sortable: false,
                    getEditor: function(){ return new Ext.form.TextField({}); },
                });
            }

            this.columnConfig.push({
                text: t('creationDate'), dataIndex: 'creationDate', editable: false, renderer: dateRenderer, sortable: false,
            });
            this.columnConfig.push({
                text: t('modificationDate'), dataIndex: 'modificationDate', editable: false, renderer: dateRenderer, sortable: false,
            });

            this.gridPanel = new Ext.grid.Panel({
                frame: false,
                bodyCls: "pimcore_editable_grid",
                autoScroll: true,
                columnLines: true,
                plugins: [
                    this.cellEditing,
                ],
                columns: {
                    items: this.columnConfig,
                    defaults: {
                        flex: 1,
                    }
                }
            });

            this.editPanel = new Ext.Panel({
                region: 'east',
                width: '75%',
                split: true,
                items: [
                    this.gridPanel,
                ]
            });
        }

        return this.editPanel;
    },
});
