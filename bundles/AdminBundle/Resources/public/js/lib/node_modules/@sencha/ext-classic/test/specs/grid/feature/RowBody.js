topSuite("Ext.grid.feature.RowBody",
    ['Ext.grid.Panel', 'Ext.tree.Panel', 'Ext.grid.feature.Grouping'],
function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        dummyData = [
            ['3m Co', 71.72, 0.02, 0.03, '9/1 12:00am', 'Manufacturing'],
            ['Alcoa Inc', 29.01, 0.42, 1.47, '9/1 12:00am', 'Manufacturing'],
            ['Altria Group Inc', 83.81, 0.28, 0.34, '9/1 12:00am', 'Manufacturing'],
            ['American Express Company', 52.55, 0.01, 0.02, '9/1 12:00am', 'Finance'],
            ['American International Group, Inc.', 64.13, 0.31, 0.49, '9/1 12:00am', 'Services'],
            ['AT&T Inc.', 31.61, -0.48, -1.54, '9/1 12:00am', 'Services'],
            ['Boeing Co.', 75.43, 0.53, 0.71, '9/1 12:00am', 'Manufacturing'],
            ['Caterpillar Inc.', 67.27, 0.92, 1.39, '9/1 12:00am', 'Services'],
            ['Citigroup, Inc.', 49.37, 0.02, 0.04, '9/1 12:00am', 'Finance'],
            ['E.I. du Pont de Nemours and Company', 40.48, 0.51, 1.28, '9/1 12:00am', 'Manufacturing'],
            ['Exxon Mobil Corp', 68.1, -0.43, -0.64, '9/1 12:00am', 'Manufacturing'],
            ['General Electric Company', 34.14, -0.08, -0.23, '9/1 12:00am', 'Manufacturing'],
            ['General Motors Corporation', 30.27, 1.09, 3.74, '9/1 12:00am', 'Automotive'],
            ['Hewlett-Packard Co.', 36.53, -0.03, -0.08, '9/1 12:00am', 'Computer'],
            ['Honeywell Intl Inc', 38.77, 0.05, 0.13, '9/1 12:00am', 'Manufacturing'],
            ['Intel Corporation', 19.88, 0.31, 1.58, '9/1 12:00am', 'Computer'],
            ['International Business Machines', 81.41, 0.44, 0.54, '9/1 12:00am', 'Computer'],
            ['Johnson & Johnson', 64.72, 0.06, 0.09, '9/1 12:00am', 'Medical'],
            ['JP Morgan & Chase & Co', 45.73, 0.07, 0.15, '9/1 12:00am', 'Finance'],
            ['McDonald\'s Corporation', 36.76, 0.86, 2.40, '9/1 12:00am', 'Food'],
            ['Merck & Co., Inc.', 40.96, 0.41, 1.01, '9/1 12:00am', 'Medical'],
            ['Microsoft Corporation', 25.84, 0.14, 0.54, '9/1 12:00am', 'Computer'],
            ['Pfizer Inc', 27.96, 0.4, 1.45, '9/1 12:00am', 'Services', 'Medical'],
            ['The Coca-Cola Company', 45.07, 0.26, 0.58, '9/1 12:00am', 'Food'],
            ['The Home Depot, Inc.', 34.64, 0.35, 1.02, '9/1 12:00am', 'Retail'],
            ['The Procter & Gamble Company', 61.91, 0.01, 0.02, '9/1 12:00am', 'Manufacturing'],
            ['United Technologies Corporation', 63.26, 0.55, 0.88, '9/1 12:00am', 'Computer'],
            ['Verizon Communications', 35.57, 0.39, 1.11, '9/1 12:00am', 'Services'],
            ['Wal-Mart Stores, Inc.', 45.45, 0.73, 1.63, '9/1 12:00am', 'Retail'],
            ['Walt Disney Company (The) (Holding Company)', 29.89, 0.24, 0.81, '9/1 12:00am', 'Services']
        ],
        grid, view, store, rowBody, columns, tree;

    function createGrid(gridCfg, rowBodyCfg, columns, locked) {
        Ext.define('spec.RowBodyCompany', {
            extend: 'Ext.data.Model',
            fields: [
                { name: 'company' },
                { name: 'price', type: 'float' },
                { name: 'change', type: 'float' },
                { name: 'pctChange', type: 'float' },
                { name: 'lastChange', type: 'date',  dateFormat: 'n/j h:ia' },
                { name: 'industry' }
            ]
        });

        store = new Ext.data.Store({
            model: 'spec.RowBodyCompany',
            data: dummyData,
            groupField: 'company',
            autoDestroy: true
        });

        rowBody = new Ext.grid.feature.RowBody(Ext.apply({
            ftype: 'rowbody',
            getAdditionalData: function(data, rowIndex, record, rowValues) {
                // Usually you would style the my-body-class in CSS file.
                var colspan = this.view.headerCt.getColumnCount();

                return {
                    rowBody: '<div style="padding: 1em">' + record.get('company') + '</div>',
                    rowBodyCls: 'my-body-class',
                    rowBodyColspan: colspan
                };
            }
        }, rowBodyCfg));

        columns = columns || [
            { text: 'Company', locked: locked, flex: locked ? undefined : 1, width: locked ? 200 : undefined, dataIndex: 'company' },
            { text: 'Price', renderer: Ext.util.Format.usMoney, dataIndex: 'price' },
            { text: 'Change', dataIndex: 'change' },
            { text: '% Change', dataIndex: 'pctChange' },
            { text: 'Last Updated', renderer: Ext.util.Format.dateRenderer('m/d/Y'), dataIndex: 'lastChange' }
        ];

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: columns,
            width: 600,
            height: 300,
            features: rowBody,
            renderTo: Ext.getBody(),
            selModel: {
                mode: 'MULTI',
                type: 'rowmodel'
            }
        }, gridCfg));

        view = grid.view;
    }

    function createTree(treeCfg, rowBodyCfg) {
        store = new Ext.data.TreeStore({
            root: {
                expanded: true,
                children: [
                    { text: 'detention', leaf: true },
                    { text: 'homework', expanded: true, children: [
                        { text: 'book report', leaf: true },
                        { text: 'algebra', leaf: true }
                    ] },
                    { text: 'buy lottery tickets', leaf: true }
                ]
            }
        });

        rowBody = new Ext.grid.feature.RowBody(Ext.apply({
            getAdditionalData: function(data, idx, record, orig) {
                var headerCt = this.view.headerCt,
                    colspan = headerCt.getColumnCount();

                return {
                    rowBody: '>>>>>>>>>>>>>>>>>>>>> with a rowbody',
                    rowBodyCls: 'ok',
                    rowBodyColspan: colspan
                };
            }
        }, rowBodyCfg));

        tree = new Ext.tree.Panel(Ext.apply({
            width: 200,
            height: 150,
            store: store,
            features: rowBody,
            rootVisible: false,
            renderTo: Ext.getBody()
        }, treeCfg));
    }

    afterEach(function() {
        grid = view = store = rowBody = columns = tree = Ext.destroy(grid, tree);
        Ext.undefine('spec.RowBodyCompany');
        Ext.data.Model.schema.clear();
    });

    describe('grids', function() {
        describe('init', function() {
            describe('rendering', function() {
                var viewBody, rowBodies, items;

                afterEach(function() {
                    viewBody = rowBodies =  items = null;
                });

                it('should render a rowbody row for each wrapped row item', function() {
                    createGrid();

                    viewBody = view.body;
                    items = viewBody.query(view.itemSelector).length;
                    rowBodies = viewBody.query('.my-body-class').length;

                    expect(rowBodies).toBe(items);
                });

                describe('when combined with the grouping feature', function() {
                    // Note that we're checking the markup that the grouping feature injects b/c the colgroup sizing
                    // node was not being injected when rowbody was combined with grouping.
                    // See EXTJS-15265.
                    var ctCls;

                    beforeEach(function() {
                        createGrid({
                            features: [{
                                ftype: 'grouping'
                            }, {
                                ftype: 'rowbody',
                                getAdditionalData: function(data, rowIndex, record, rowValues) {
                                    // Usually you would style the my-body-class in CSS file.
                                    var colspan = this.view.headerCt.getColumnCount();

                                    return {
                                        rowBody: '<div style="padding: 1em">' + record.get('company') + '</div>',
                                        rowBodyCls: 'my-body-class',
                                        rowBodyColspan: colspan
                                    };
                                }
                            }]
                        });

                        viewBody = view.body;
                        ctCls = view.summaryFeature.ctCls;
                    });

                    afterEach(function() {
                        ctCls = null;
                    });

                    it('should render a colgroup for each grouping container to properly size the columns', function() {
                        var colgroups = viewBody.query('colgroup').length,
                            containers = viewBody.query('.' + ctCls).length;

                        expect(colgroups).toBe(containers);
                    });

                    it('should render a rowbody row for each wrapped row item', function() {
                        var viewBody = view.body,
                            items = viewBody.query(view.itemSelector).length,
                            rowBodies = viewBody.query('.my-body-class').length;

                        expect(rowBodies).toBe(items);
                    });
                });

                xdescribe('when combined with the rowexpander plugin', function() {
                    beforeEach(function() {
                        createGrid({
                            plugins: [{
                                ptype: 'rowexpander',
                                rowBodyTpl: new Ext.XTemplate(
                                    '<p><b>Company:</b> {company}</p>',
                                    '<p><b>Price:</b> {price}</p>'
                                )
                            }]
                        });

                        viewBody = view.body;
                    });

                    it('should render a rowbody row for each wrapped row item', function() {
                        var items = viewBody.query(view.itemSelector).length,
                            rowBodies = viewBody.query('.my-body-class').length;

                        expect(rowBodies).toBe(items);
                    });
                });
            });
        });

        it('should be a RowBody feature', function() {
            createGrid();

            expect(rowBody instanceof Ext.grid.feature.RowBody).toBe(true);
        });

        describe('row over, focus and selection', function() {
            var row, rowBody, columns, column0Center;

            beforeEach(function() {
                createGrid();
                columns = grid.getVisibleColumnManager().getColumns();
                column0Center = columns[0].getX() + columns[0].getWidth() / 2;
                row = grid.view.all.item(1);
                rowBody = row.down('div.x-grid-rowbody', true);
            });

            afterEach(function() {
                rowBody = null;
            });

            itNotTouch('should add the row over class to the entire wrapped row when hovering over the row body', function() {
                jasmine.fireMouseEvent(rowBody, 'mouseover');

                expect(row.hasCls('x-grid-item-over')).toBe(true);
            });

            it('should focus the closest cell when clicking the row body', function() {
                jasmine.fireMouseEvent(rowBody, 'click', column0Center);

                expect(grid.view.getCell(1, columns[0])).toHaveCls('x-grid-item-focused');
            });

            it('should select the entire wrapped row when clicking the row body', function() {
                jasmine.fireMouseEvent(rowBody, 'click', column0Center);

                expect(row.hasCls('x-grid-item-selected')).toBe(true);
            });

            it('should capture the selection in the selection model', function() {
                var selModel;

                jasmine.fireMouseEvent(rowBody, 'click', column0Center);

                selModel = grid.selModel;

                expect(selModel.selected.length).toBe(1);
                expect(selModel.getSelection()[0] === store.getAt(1)).toBe(true);

                // Ctrl/Click row 2. Not on touch platforms; they do not recognize CRTL modifiers
                if (!jasmine.supportsTouch) {
                    jasmine.fireMouseEvent(grid.view.all.item(2).down('div.x-grid-rowbody', true), 'click', column0Center, 0, null, false, true);

                    expect(selModel.selected.length).toBe(2);
                    expect(selModel.getSelection()[1] === store.getAt(2)).toBe(true);
                }
            });
        });

        describe('rowbody events', function() {
            var wasCalled = false,
                node;

            beforeEach(function() {
                createGrid({
                    viewConfig: {
                        listeners: {
                            rowbodyclick: function() {
                                wasCalled = true;
                            },
                            rowbodydblclick: function() {
                                wasCalled = true;
                            },
                            rowbodycontextmenu: function() {
                                wasCalled = true;
                            }
                        }
                    }
                });
                node = grid.view.all.item(1).down('tr.x-grid-rowbody-tr', true);
            });

            afterEach(function() {
                wasCalled = false;
                node = null;
            });

            it('should fire the rowbodyclick event', function() {
                jasmine.fireMouseEvent(node, 'click');

                expect(wasCalled).toBe(true);
            });

            it('should fire the rowbodydblclick event', function() {
                jasmine.fireMouseEvent(node, 'dblclick');

                expect(wasCalled).toBe(true);
            });

            itNotTouch('should fire the rowbodycontextmenu event', function() {
                jasmine.fireMouseEvent(node, 'contextmenu');

                expect(wasCalled).toBe(true);
            });
        });

        describe('rowBefore', function() {
            it('should put the expander row before the data row', function() {
                createGrid(null, {
                    bodyBefore: true
                });

                // Access the first row.
                // It should be like
                // <table><tbody>
                //     <tr class="x-grid-rowbody-tr my-body-class">
                //         <td class="x-grid-td x-grid-cell-rowbody" colspan="5">
                //             <div class="x-grid-rowbody ">
                //                 <div style="padding: 1em">3m Co</div>
                //             </div>
                //         </td>
                //     </tr>
                //     <tr class="x-grid-row" tabindex="-1">
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1093 x-grid-cell-first x-unselectable" style="width: 183px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">3m Co</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1094 x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">$71.72</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1095 x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">0.02</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1096 x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">0.03</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1097 x-grid-cell-last x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">09/01/2014</div></td>
                //     </tr>
                // </tbody></table>
                var row0 = grid.view.all.item(0, true),
                    tr0 = row0.tBodies[0].childNodes[0],
                    tr1 = row0.tBodies[0].childNodes[1];

                // tr0 should be the row body row with one child
                expect(Ext.fly(tr0).hasCls('x-grid-rowbody-tr')).toBeTruthy();
                expect(Ext.fly(tr0).hasCls('my-body-class')).toBeTruthy();
                expect(tr0.childNodes.length).toBe(1);

                // tr1 should be the grid data row with five children
                expect(tr1.childNodes.length).toBe(5);
                expect(Ext.fly(tr1).hasCls('x-grid-row')).toBeTruthy();
            });
            it("should put the expander row before the data row when there's a locked column", function() {
                createGrid(null, {
                    bodyBefore: true
                }, null, true);

                // Access the first row.
                // It should be like
                // <table><tbody>
                //     <tr class="x-grid-rowbody-tr my-body-class">
                //         <td class="x-grid-td x-grid-cell-rowbody" colspan="5">
                //             <div class="x-grid-rowbody ">
                //                 <div style="padding: 1em">3m Co</div>
                //             </div>
                //         </td>
                //     </tr>
                //     <tr class="x-grid-row" tabindex="-1">
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1093 x-grid-cell-first x-unselectable" style="width: 183px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">3m Co</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1094 x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">$71.72</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1095 x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">0.02</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1096 x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">0.03</div></td>
                //         <td class="x-grid-cell x-grid-td x-grid-cell-gridcolumn-1097 x-grid-cell-last x-unselectable" style="width:100px;"><div unselectable="on" class="x-grid-cell-inner " style="text-align:left;">09/01/2014</div></td>
                //     </tr>
                // </tbody></table>

                var lockedRow0 = grid.lockedGrid.view.all.item(0, true),
                    lockedTr0 = lockedRow0.tBodies[0].childNodes[0],
                    lockedTr1 = lockedRow0.tBodies[0].childNodes[1],
                    normalRow0 = grid.normalGrid.view.all.item(0, true),
                    normalTr0 = normalRow0.tBodies[0].childNodes[0],
                    normalTr1 = normalRow0.tBodies[0].childNodes[1];

                // lockedTr0 should be the row body row with one child
                expect(Ext.fly(lockedTr0).hasCls('x-grid-rowbody-tr')).toBeTruthy();
                expect(Ext.fly(lockedTr0).hasCls('my-body-class')).toBeTruthy();
                expect(lockedTr0.childNodes.length).toBe(1);

                // lockedTr1 should be the grid data row with one child
                expect(lockedTr1.childNodes.length).toBe(1);
                expect(Ext.fly(lockedTr1).hasCls('x-grid-row')).toBeTruthy();

                // normalTr0 should be the row body row with one child
                expect(Ext.fly(normalTr0).hasCls('x-grid-rowbody-tr')).toBeTruthy();
                expect(Ext.fly(normalTr0).hasCls('my-body-class')).toBeTruthy();
                expect(normalTr0.childNodes.length).toBe(1);

                // normalTr1 should be the grid data row with four children
                expect(normalTr1.childNodes.length).toBe(4);
                expect(Ext.fly(normalTr1).hasCls('x-grid-row')).toBeTruthy();

            });
        });

        describe("updating", function() {
            it("should react to an update", function() {
                createGrid();
                store.first().set('company', '2m Co');
                var el = grid.el.dom.querySelector('.x-grid-rowbody');

                expect(el.childNodes[0]).hasHTML('2m Co');
            });
        });

        // TODO: Locked grids!
    });

    describe('trees', function() {
        describe('collapsing a node', function() {
            // See EXTJSIV-11219.
            var view, record;

            afterEach(function() {
                view = record = null;
            });

            it('should not remove the node from the treeview', function() {
                createTree();

                view = tree.view;
                record = view.store.getAt(1);
                record.collapse();

                // The node is still in the treeview.
                expect(view.getNode(record)).not.toBe(null);
            });

            it('should not remove the node from the treeview (animation off)', function() {
                createTree({
                    animate: false
                });

                view = tree.view;
                record = view.store.getAt(1);
                record.collapse();

                // The node is still in the treeview.
                expect(view.getNode(record)).not.toBe(null);
            });
        });
    });
});
