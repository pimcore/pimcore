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

Ext.onReady(function () {
    const quicksearchMap = new Ext.util.KeyMap({
        target: document,
        binding: [{
            key:  Ext.event.Event.ESC,
            fn: function () {
                pimcore.helpers.hideQuickSearch();
            }
        }, {
            key: Ext.event.Event.SPACE,
            ctrl: true,
            fn: function (keyCode, e) {
                e.stopEvent();
                pimcore.helpers.showQuickSearch();
            }
        }]
    });

    let quicksearchStore = new Ext.data.Store({
        proxy: {
            type: 'ajax',
            url: Routing.generate('pimcore_admin_searchadmin_search_quicksearch'),
            reader: {
                type: 'json',
                rootProperty: 'data'
            }
        },
        listeners: {
            "beforeload": function (store) {
                var previewEl = Ext.get('pimcore_quicksearch_preview');
                if(previewEl) {
                    previewEl.setHtml('');
                }

                store.getProxy().abort();
            }
        },
        fields: ["id", 'type', "subtype", "className", "fullpath"]
    });

    const quickSearchTpl = new Ext.XTemplate(
        '<tpl for=".">',
        '<li role="option" unselectable="on" class="x-boundlist-item">' +
        '<div class="list-icon {iconCls}"><tpl if="icon"><img class="class-icon" src="{icon}"></tpl></div>' +
        '<div class="list-path" title="{fullpath}">{fullpathList}</div>' +
        '</li>',
        '</tpl>'
    );

    const quicksearchContainer = Ext.get('pimcore_quicksearch');
    let quickSearchCombo = Ext.create('Ext.form.ComboBox', {
        width: 900,
        hideTrigger: true,
        border: false,
        shadow: false,
        tpl: quickSearchTpl,
        listConfig: {
            shadow: false,
            border: false,
            cls: 'pimcore_quicksearch_picker',
            navigationModel: 'quicksearch.boundlist',
            listeners: {
                "highlightitem": function (view, node, opts) {
                    var record = quicksearchStore.getAt(node.dataset.recordindex);
                    if (!record.get('preview')) {
                        Ext.Ajax.request({
                            url: Routing.generate('pimcore_admin_searchadmin_search_quicksearch_by_id'),
                            method: 'GET',
                            params: {
                                "id": record.get('id'),
                                "type": record.get('type')
                            },
                            success: function (response) {
                                var result = Ext.decode(response.responseText);

                                record.preview = result.preview;
                                Ext.get('pimcore_quicksearch_preview').setHtml(result.preview);
                            },
                            failure: function () {
                                var previewHtml = '<div class="no_preview">' + t('preview_not_available') + '</div>';

                                Ext.get('pimcore_quicksearch_preview').setHtml(previewHtml);
                            }
                        });
                    } else {
                        var previewHtml = record.get('preview');
                        if(!previewHtml) {
                            previewHtml = '<div class="no_preview">' + t('preview_not_available') + '</div>';
                        }

                        Ext.get('pimcore_quicksearch_preview').setHtml(previewHtml);
                    }
                }
            }
        },
        id: 'quickSearchCombo',
        store: quicksearchStore,
        loadingText: t('searching'),
        queryDelay: 100,
        minChars: 4,
        renderTo: quicksearchContainer,
        enableKeyEvents: true,
        displayField: 'fullpath',
        valueField: "id",
        typeAhead: true,
        listeners: {
            "expand": function (combo) {
                if(!document.getElementById('pimcore_quicksearch_preview')) {
                    combo.getPicker().getEl().insertHtml('beforeEnd', '<div id="pimcore_quicksearch_preview"></div>');
                }
            },
            "keyup": function (field) {
                if(field.getValue()) {
                    quicksearchContainer.addCls('filled');
                }
            },
            "select": function (combo, record, index) {
                pimcore.helpers.openElement(record.get('id'), record.get('type'), record.get('subtype'));
                pimcore.helpers.hideQuickSearch();
            }
        }
    });

    Ext.getBody().on('click', function (event) {
        // hide on click outside
        if(quicksearchContainer && !quicksearchContainer.isAncestor(event.target)) {
            var pickerEl = quickSearchCombo.getPicker().getEl();
            if(!pickerEl || !pickerEl.isAncestor(event.target)) {
                pimcore.helpers.hideQuickSearch();
            }
        }
    });
});