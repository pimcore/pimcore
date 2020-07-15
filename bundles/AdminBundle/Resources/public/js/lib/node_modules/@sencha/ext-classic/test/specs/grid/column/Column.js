topSuite("Ext.grid.column.Column",
    ['Ext.grid.Panel', 'Ext.grid.plugin.CellEditing', 'Ext.form.field.Text'],
function() {
    var defaultColumns = [
            { header: 'Name',  dataIndex: 'name', width: 100 },
            { header: 'Email', dataIndex: 'email', flex: 1 },
            { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
        ],
        grid, store, colRef;

    function createGrid(storeCfg, gridCfg) {
        store = new Ext.data.Store(Ext.apply({
            fields: ['name', 'email', 'phone'],
            data: [
                { 'name': 'Lisa',  "email": "lisa@simpsons.com",  "phone": "555-111-1224"  },
                { 'name': 'Bart',  "email": "bart@simpsons.com",  "phone": "555-222-1234"  },
                { 'name': 'Homer', "email": "homer@simpsons.com", "phone": "555-222-1244"  },
                { 'name': 'Marge', "email": "marge@simpsons.com", "phone": "555-222-1254"  }
            ]
        }, storeCfg));

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: defaultColumns,
            height: 200,
            width: 400,
            renderTo: Ext.getBody()
        }, gridCfg));
        colRef = grid.getColumnManager().getColumns();
    }

    afterEach(function() {
        grid = Ext.destroy(grid);
    });

    describe("construction", function() {
        var col;

        afterEach(function() {
            col = Ext.destroy(col);
        });

        it("should not throw an exception when constructing a group header outside of a grid", function() {
            expect(function() {
                col = new Ext.grid.column.Column({
                    columns: [{
                        text: 'Foo'
                    }, {
                        text: 'Bar'
                    }]
                });
            }).not.toThrow();
        });
    });

    describe("headerId generation", function() {
        it("should generate ids in flat order", function() {
            createGrid();
            expect(colRef[0].headerId).toBe('h1');
            expect(colRef[1].headerId).toBe('h2');
            expect(colRef[2].headerId).toBe('h3');
        });

        it("should generate ids in top down order", function() {
            createGrid(null, {
                columns: [{
                    text: 'A',
                    columns: [{
                        text: 'A1'
                    }, {
                        text: 'A2'
                    }, {
                        text: 'A3'
                    }]
                }, {
                    text: 'B',
                    columns: [{
                        text: 'B1',
                        columns: [{
                            text: 'B11'
                        }, {
                            text: 'B12'
                        }]
                    }, {
                        text: 'B2',
                        columns: [{
                            text: 'B21'
                        }]
                    }]
                }, {
                    text: 'C'
                }]
            });

            expect(grid.down('gridcolumn[text=A1]').headerId).toBe('h1');
            expect(grid.down('gridcolumn[text=A2]').headerId).toBe('h2');
            expect(grid.down('gridcolumn[text=A3]').headerId).toBe('h3');
            expect(grid.down('gridcolumn[text=A]').headerId).toBe('h9');

            expect(grid.down('gridcolumn[text=B11]').headerId).toBe('h4');
            expect(grid.down('gridcolumn[text=B12]').headerId).toBe('h5');
            expect(grid.down('gridcolumn[text=B1]').headerId).toBe('h7');
            expect(grid.down('gridcolumn[text=B21]').headerId).toBe('h6');
            expect(grid.down('gridcolumn[text=B2]').headerId).toBe('h8');
            expect(grid.down('gridcolumn[text=B]').headerId).toBe('h10');

            expect(grid.down('gridcolumn[text=C]').headerId).toBe('h11');
        });

        it("should generate headerId for dynamically created columns", function() {
            var col = new Ext.grid.column.Column({
                text: 'A',
                columns: [{
                    text: 'A1'
                }, {
                    text: 'A2'
                }, {
                    text: 'A3'
                }]
            });

            createGrid(null, {
                columns: [col, {
                    text: 'B',
                    columns: [{
                        text: 'B1',
                        columns: [{
                            text: 'B11'
                        }, {
                            text: 'B12'
                        }]
                    }, {
                        text: 'B2',
                        columns: [{
                            text: 'B21'
                        }]
                    }]
                }, {
                    text: 'C'
                }]
            });

            expect(grid.down('gridcolumn[text=A1]').headerId).toBe('h6');
            expect(grid.down('gridcolumn[text=A2]').headerId).toBe('h7');
            expect(grid.down('gridcolumn[text=A3]').headerId).toBe('h8');
            expect(grid.down('gridcolumn[text=A]').headerId).toBe('h9');

            expect(grid.down('gridcolumn[text=B11]').headerId).toBe('h1');
            expect(grid.down('gridcolumn[text=B12]').headerId).toBe('h2');
            expect(grid.down('gridcolumn[text=B1]').headerId).toBe('h4');
            expect(grid.down('gridcolumn[text=B21]').headerId).toBe('h3');
            expect(grid.down('gridcolumn[text=B2]').headerId).toBe('h5');
            expect(grid.down('gridcolumn[text=B]').headerId).toBe('h10');

            expect(grid.down('gridcolumn[text=C]').headerId).toBe('h11');
        });
    });

    describe('Text field in column header', function() {
        it('should not sort when clicking into the text field', function() {
            var columns = Ext.clone(defaultColumns);

            columns[1].items = {
                xtype: 'textfield',
                flex: 1,
                margin: '2'
            };
            createGrid(
                null, {
                    columns: columns
                }
            );
            var textField = colRef[1].down('textfield');

            // Ensure we do not click onLeftEdge, because that would not sort anyway. Move 20px into field.
            jasmine.fireMouseEvent(textField.inputEl, 'click', textField.inputEl.getX() + 20);

            // That click into the text field should not have sorted the columns
            expect(store.getSorters().length).toBe(0);
        });
    });

    describe("layout", function() {
        it("should layout grouped columns correctly", function() {
            grid = new Ext.grid.Panel({
                header: false,
                border: false,
                columns: [{
                    text: 'Column A'
                },
                {
                    text: 'Column B',
                    columns: [{
                        text: 'Column C'
                    }]
                },
                {
                    text: 'Column D',
                    columns: [{
                        text: 'Column E'
                    }, {
                        text: 'Column<br/>F',
                        columns: [{
                            text: 'Column G'
                        }]
                    }]
                }],
                width: 400,
                renderTo: Ext.getBody(),
                style: 'position:absolute;top:0;left:0'
            });

            // IE9m appears to need some time to correct the table layout of the headers.
            // so let's force it with a repaint
            if (Ext.isIE9m) {
                grid.el.repaint();
            }

            expect(grid.headerCt).toHaveLayout({
                el: { xywh: '0 0 400 80' },
                items: {
                   0: {
                     el: { xywh: '0 0 100 80' },
                     textEl: { xywh: '6 33 87 13' },
                     titleEl: { xywh: '0 0 99 80' }
                  },
                  1: {
                     el: { xywh: '100 0 100 80' },
                     textEl: { xywh: '106 4 87 13' },
                     titleEl: { xywh: '100 0 99 22' },
                     items: {
                        0: {
                           el: { xywh: '0 22 [99,100] 58' },
                           textEl: { xywh: '6 44 [87,88] 13' },
                           titleEl: { xywh: '0 23 [99,100] 57' }
                        }
                     }
                  },
                  2: {
                     el: { xywh: '200 0 200 80' },
                     textEl: { xywh: '206 4 187 13' },
                     titleEl: { xywh: '200 0 199 22' },
                     items: {
                        0: {
                           el: { xywh: '0 22 100 58' },
                           textEl: { xywh: '6 44 87 13' },
                           titleEl: { xywh: '0 23 99 57' }
                        },
                        1: {
                           el: { xywh: '100 22 100 58' },
                           textEl: { xywh: '106 26 87 26' },
                           titleEl: { xywh: '100 23 99 34' },
                           items: {
                              0: {
                                 el: { xywh: '0 35 100 22' },
                                 textEl: { xywh: '6 39 87 13' },
                                 titleEl: { xywh: '0 36 99 21' }
                              }
                           }
                        }
                     }
                  }
               }
            });
        });
    });

    describe("destruction", function() {
        var grid, store, cellEditingPlugin;

        beforeEach(function() {
            cellEditingPlugin = new Ext.grid.plugin.CellEditing();
            store = new Ext.data.Store({
                fields: ['name'],
                data: {
                    'items': [ { 'name': 'A' } ]
                },
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json',
                        rootProperty: 'items'
                    }
                }
            });
            grid = new Ext.grid.Panel({
                store: store,
                columns: [
                    { dataIndex: 'name', editor: { xtype: 'textfield', id: 'nameEditor' } }
                ],
                plugins: [cellEditingPlugin],
                renderTo: Ext.getBody()
            });
        });

        it("should destroy the editor field that was created using the column's getEditor method", function() {
            var field = grid.headerCt.items.getAt(0).getEditor();

            grid.destroy();
            expect(field.destroyed).toBe(true);
            expect(Ext.ComponentMgr.get('nameEditor')).toBeUndefined();
        });

        it("should destroy the editor field that was created using the editing plugin's getEditor method", function() {
            var field = cellEditingPlugin.getEditor(store.getAt(0), grid.headerCt.items.getAt(0));

            grid.destroy();
            expect(field.destroyed).toBe(true);
            expect(Ext.ComponentMgr.get('nameEditor')).toBeUndefined();
        });
    });

    describe('column properties', function() {
        it('should only have one header as the root header when columns is a config', function() {
            createGrid();

            expect(grid.query('[isRootHeader]').length).toBe(1);
        });

        it('should only have one header as the root header when columns config is an instance', function() {
            createGrid({}, {
                columns: new Ext.grid.header.Container({
                    items: [
                        { header: 'Name',  columns: {
                            header: 'Foo', dataIndex: 'foo'
                        } },
                        { header: 'Email', dataIndex: 'email', flex: 1 },
                        { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                    ]
                })
            });

            expect(grid.query('[isRootHeader]').length).toBe(1);
        });

        it('should have as many isColumn matches as there are defined columns', function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    { header: 'Email', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ]
            });

            expect(grid.query('[isColumn]').length).toBe(3);
        });

        it('should have as many isGroupHeader matches as there are defined column groups', function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  columns: {
                        header: 'Foo', dataIndex: 'foo'
                    } },
                    { header: 'Email', columns: {
                        header: 'Bar', dataIndex: 'bar'
                    } },
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ]
            });

            expect(grid.query('[isGroupHeader]').length).toBe(2);
        });

        it('should seal all grouped columns when the grid is configured with sealedColumns true', function() {
            var grouped, i;

            createGrid({}, {
                sealedColumns: true,
                columns: [
                    { header: 'Name',  columns: {
                        header: 'Foo', dataIndex: 'foo'
                    } },
                    { header: 'Email', columns: {
                        header: 'Bar', dataIndex: 'bar'
                    } },
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ]
            });

            grouped = grid.query('[isGroupHeader]');

            for (i = 0; i < grouped.length; i++) {
                expect(grouped[i].isSealed()).toBe(true);
            }
        });

        it('should not have any isGroupHeader matches if there are no column groups', function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    { header: 'Email', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ]
            });

            expect(grid.query('[isGroupHeader]').length).toBe(0);
        });
    });

    describe("align", function() {
        it("should align text to right when set to end", function() {
            createGrid({}, {
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    { header: 'Email', dataIndex: 'email', flex: 1, align: 'end' }
                ]
            });
            expect(Ext.fly(grid.view.getCell(0, colRef[1]).firstChild).getStyle('text-align')).toBe('right');
        });
    });

    describe("setText", function() {
        it("should update the textInnerEl", function() {
            createGrid();
            colRef[0].setText('NewName');
            expect(colRef[0].textInnerEl.dom).hasHTML('NewName');
        });

        describe("empty value", function() {
            var emptyValues = ['', ' ', null, undefined, '&#160;'],
                i;

            for (i = 0; i < emptyValues.length; i++) {
                it("should remove the emtpy cls when initially hidden (" + emptyValues[i] + ")", function() {
                    createGrid({}, {
                        columns: [
                            { text: emptyValues[i], dataIndex: 'phone', flex: 1 }
                        ]
                    });

                    expect(colRef[0].titleEl).toHaveCls(Ext.baseCSSPrefix + 'column-header-inner-empty');

                    colRef[0].setText('Phone');

                    expect(colRef[0].titleEl).not.toHaveCls(Ext.baseCSSPrefix + 'column-header-inner-empty');
                });

                it("should add the emtpy cls when setting text to an empty value (" + emptyValues[i] + ")", function() {
                    createGrid({}, {
                        columns: [
                            { text: 'Foo', dataIndex: 'phone', flex: 1 }
                        ]
                    });

                    expect(colRef[0].titleEl).not.toHaveCls(Ext.baseCSSPrefix + 'column-header-inner-empty');

                    colRef[0].setText(emptyValues[i]);

                    expect(colRef[0].titleEl).toHaveCls(Ext.baseCSSPrefix + 'column-header-inner-empty');
                });
            }
        });
    });
});

