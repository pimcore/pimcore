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

pimcore.registerNS("pimcore.object.tags.abstractRelations");
pimcore.object.tags.abstractRelations = Class.create(pimcore.object.tags.abstract, {

    getFilterEditToolbarItems: function () {
        return [
            {
                xtype: 'textfield',
                hidden: true,
                cls: 'relations_grid_filter_input',
                width: '250px',
                listeners:
                    {
                        keyup: {
                            fn: this.filterStore.bind(this),
                            element: "el"
                        },
                        blur: function (filterField) {
                            /* do not hide filter if filter is active */
                            if (filterField.getValue().length === 0) {
                                this.hideFilterInput(filterField);
                            }
                        }.bind(this)
                    }
            },
            {
                xtype: "button",
                iconCls: "pimcore_icon_filter",
                cls: "relations_grid_filter_btn",
                handler: this.showFilterInput.bind(this)
            }
        ];
    },

    showFilterInput: function (filterBtn) {
        var filterInput = filterBtn.previousSibling("field[cls~=relations_grid_filter_input]");
        filterInput.show();
        filterInput.focus();
        filterBtn.hide();
    },

    hideFilterInput: function (filterInput) {
        var filterBtn = filterInput.nextSibling("button[cls~=relations_grid_filter_btn]");
        filterBtn.show();
        filterInput.hide();
    },

    filterStore: function (e) {
        var visibleFieldDefinitions = this.fieldConfig.visibleFieldDefinitions || {};
        var visibleFields = Ext.Object.getKeys(visibleFieldDefinitions);
        var metaDataFields = this.fieldConfig.columnKeys || [];
        var searchColumns = Ext.Array.merge(visibleFields, metaDataFields);

        /* always search in path (relations), fullpath (object relations) and id */
        searchColumns.push("path");
        searchColumns.push("fullpath");
        searchColumns.push("id");

        searchColumns = Ext.Array.unique(searchColumns);

        var q = Ext.get(e.target).getValue().toLowerCase();
        var searchFilter = new Ext.util.Filter({
            filterFn: function (item) {
                for (var column in item.data) {
                    var value = item.data[column];
                    /* skip none-search columns and null values */
                    if (searchColumns.indexOf(column) < 0 || !value) {
                        continue;
                    }
                    /* links */
                    if (!!visibleFieldDefinitions[column] && visibleFieldDefinitions[column].fieldtype === "link") {
                        value = [value.text, value.title, value.path].join(" ");
                    }
                    /* numbers, texts */
                    value = String(value).toLowerCase();
                    if (value.indexOf(q) >= 0) {
                        return true;
                    }
                }
                return false;
            }
        });
        this.store.clearFilter();
        this.store.filter(searchFilter);
    }

});
