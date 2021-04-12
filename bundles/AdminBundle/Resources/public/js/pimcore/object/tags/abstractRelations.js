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
    },

    batchPrepare: function(columnDataIndex, grid, onlySelected, append, remove){
        var columnIndex = columnDataIndex.fullColumnIndex;
        var editor = grid.getColumns()[columnIndex].getEditor();
        var metaIndex = this.fieldConfig.columnKeys.indexOf(columnDataIndex.dataIndex);
        var columnConfig = this.fieldConfig.columns[metaIndex];

        if (columnConfig.type == 'multiselect') { //create edit layout for multiselect field
            var selectData = [];
            if (columnConfig.value) {
                var selectDataRaw = columnConfig.value.split(";");
                for (var j = 0; j < selectDataRaw.length; j++) {
                    selectData.push([selectDataRaw[j], t(selectDataRaw[j])]);
                }
            }

            var store = new Ext.data.ArrayStore({
                fields: [
                    'id',
                    'label'
                ],
                data: selectData
            });

            var options = {
                triggerAction: "all",
                editable: false,
                store: store,
                componentCls: "object_field",
                height: '100%',
                valueField: 'id',
                displayField: 'label'
            };

            editor = Ext.create('Ext.ux.form.MultiSelect', options);
        } else if (columnConfig.type == 'bool') { //create edit layout for bool meta field
            editor = new Ext.form.Checkbox();
        }

        var editorLabel = Ext.create('Ext.form.Label', {
            text: grid.getColumns()[columnIndex].text + ':',
            style: {
                float: 'left',
                margin: '0 20px 0 0'
            }
        });

        var formPanel = Ext.create('Ext.form.Panel', {
            xtype: "form",
            border: false,
            items: [editorLabel, editor],
            bodyStyle: "padding: 10px;",
            buttons: [
                {
                    text: t("edit"),
                    handler: function() {
                        if(formPanel.isValid()) {
                            this.batchProcess(columnDataIndex.dataIndex, editor, grid, onlySelected);
                        }
                    }.bind(this)
                }
            ]
        });
        var batchTitle = onlySelected ? "batch_edit_field_selected" : "batch_edit_field";
        var title = t(batchTitle) + " " + grid.getColumns()[columnIndex].text;
        this.batchWin = new Ext.Window({
            autoScroll: true,
            modal: false,
            title: title,
            items: [formPanel],
            bodyStyle: "background: #fff;",
            width: 500,
            maxHeight: 400
        });
        this.batchWin.show();
        this.batchWin.updateLayout();

    },

    batchProcess: function (dataIndex, editor, grid, onlySelected) {

        var newValue = editor.getValue();

        if (onlySelected) {
            var selectedRows = grid.getSelectionModel().getSelection();
            for (var i=0; i<selectedRows.length; i++) {
                selectedRows[i].set(dataIndex, newValue);
            }
        } else {
            var items = grid.store.data.items;
            for (var i = 0; i < items.length; i++)
            {
                var record = grid.store.getAt(i);
                record.set(dataIndex, newValue);
            }
        }

        this.batchWin.close();
    },

    gridRowDblClickHandler: function(component, record) {
        var subtype = record.get('subtype');
        if (record.get('type') === "object" && record.get('subtype') !== "folder" && record.get('subtype') !== null) {
            subtype = "object";
        }
        pimcore.helpers.openElement(record.get('id'), record.get('type'), subtype);
    }
});
