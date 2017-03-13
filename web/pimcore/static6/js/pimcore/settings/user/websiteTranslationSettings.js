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


pimcore.registerNS("pimcore.settings.user.websiteTranslationSettings");
pimcore.settings.user.websiteTranslationSettings = Class.create({

    initialize:function (panel, validLanguages, userRole) {
        this.panel = panel;

        this.validLanguages = validLanguages;
        this.userRole = userRole;
    },

    getPanel:function () {

        var items = [];


        var nrOfLanguages = this.validLanguages.length;



        var data = [];
        for (var i = 0; i < nrOfLanguages; i++) {
            var language = this.validLanguages[i];
            data.push([
                language,
                ts(pimcore.available_languages[language]),
                this.userRole.websiteTranslationLanguagesView.indexOf(language) >= 0,
                this.userRole.websiteTranslationLanguagesEdit.indexOf(language) >= 0
            ]);
        }

        this.store = new Ext.data.ArrayStore({
                fields: ["key", "value", "view", "edit"],
                data: data
            }
        );

        var editColumn = new Ext.grid.column.Check({
            header: t("edit"),
            dataIndex: "edit",
            width: 50,
            flex: 1
        });
        var viewColumn = new Ext.grid.column.Check({
            header: t("view"),
            dataIndex: "view",
            width: 50,
            flex: 1
        });

        this.valueGrid = Ext.create('Ext.grid.Panel', {
            tbar: [{
                xtype: "tbtext",
                text: t("language_permissions")
            }],
            style: "margin-top: 10px",
            store: this.store,
            columnLines: true,
            width: 500,
            columns: [
                {
                    header: t("language"), sortable: true, dataIndex: 'value', editor: new Ext.form.TextField({}),
                    width: 200
                },
                {
                    header: t("abbreviation"), sortable: true, dataIndex: 'key', editor: new Ext.form.TextField({}),
                    width: 200
                },
                viewColumn,
                editColumn
            ],
            listeners: {
                'cellclick': function( panel , td, cellindex, record , tr , rowIndex , e , eOpts ) {
                    if(cellindex == 2 && record.data.view == false) {
                        record.set('edit', false);
                    }

                    if(cellindex == 3 && record.data.edit == true) {
                        record.set('view', true);
                    }
                }
            },
            autoHeight: true
        });


        items.push(this.valueGrid);

        this.container = new Ext.form.FieldSet({
            title:t("website_translation_settings"),
            collapsible: true,
            items: items
        });

        return this.container;
    },

    getLanguages: function (type) {
        var languages = [];

        this.store.commitChanges();
        this.store.each(function (rec) {
            if(rec.get(type)) {
                languages.push(rec.get("key"));
            }
        });

        return languages;
    }

});