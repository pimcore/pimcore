topSuite('Ext.grid.header.Container', ['Ext.grid.Panel', 'Ext.form.field.Text'], function() {
    var createGrid = function(storeCfg, gridCfg) {
            store = Ext.create('Ext.data.Store', Ext.apply({
                storeId: 'simpsonsStore',
                fields: ['name', 'email', 'phone'],
                data: { 'items': [
                    { 'name': 'Lisa',  "email": "lisa@simpsons.com",  "phone": "555-111-1224"  },
                    { 'name': 'Bart',  "email": "bart@simpsons.com",  "phone": "555-222-1234"  },
                    { 'name': 'Homer', "email": "homer@simpsons.com", "phone": "555-222-1244"  },
                    { 'name': 'Marge', "email": "marge@simpsons.com", "phone": "555-222-1254"  }
                ] },
                proxy: {
                    type: 'memory',
                    reader: {
                        type: 'json',
                        rootProperty: 'items'
                    }
                }
            }, storeCfg));

            grid = Ext.create('Ext.grid.Panel', Ext.apply({
                title: 'Simpsons',
                store: store,
                columns: [
                    { header: 'Name',  dataIndex: 'name', width: 100 },
                    { header: 'Email', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
                ],
                height: 200,
                width: 400,
                renderTo: Ext.getBody()
            }, gridCfg));
        },
        store, grid;

    afterEach(function() {
        store.destroy();
        grid = store = Ext.destroy(grid);
        Ext.state.Manager.clear('foo');
    });

    describe('column menu showing', function() {
        it('should show the menu on trigger click on mouse platforms and longpress on touch platforms', function() {
            var col,
                menu,
                showSpy;

            createGrid({}, {
                renderTo: Ext.getBody()
            });

            col = grid.columns[0];
            menu = col.getRootHeaderCt().getMenu();
            showSpy = spyOnEvent(menu, 'show');

            // Fire the real events depending on platform capabilities
            if (jasmine.supportsTouch) {
                Ext.testHelper.touchStart(col.el.dom);
            }
            else {
                jasmine.doFireMouseEvent(col.titleEl, 'mouseover', null, null, null, false, false, false, document.body);
                jasmine.doFireMouseEvent(col.triggerEl.dom, 'click');
            }

            waitsForSpy(showSpy);

            runs(function() {
                expect(menu.isVisible()).toBe(true);
                expect(menu.containsFocus).toBeFalsy();

                jasmine.fireMouseEvent(col.titleEl, 'mousedown');
                expect(menu.isVisible()).toBe(false);
            });

            waitsForFocus(col);

            runs(function() {
                // Opening the menu with down arrow focuses it
                jasmine.fireKeyEvent(col.el.dom, 'keydown', Ext.event.Event.DOWN);
            });

            waitsForFocus(menu);

            expectFocused(menu.down('menuitem'));

            runs(function() {
                jasmine.fireMouseEvent(col.titleEl, 'mouseup');
            });
        });
    });

    describe('columnManager delegations', function() {
        it('should allow columns to call methods on the ColumnManager', function() {
            var col;

            createGrid({}, {
                renderTo: Ext.getBody()
            });

            col = grid.columns[0];

            expect(col.getHeaderIndex(col)).toBe(0);
            expect(col.getHeaderAtIndex(0)).toBe(col);
            expect(col.getVisibleHeaderClosestToIndex(0)).toBe(col);
        });
    });

    describe('gridVisibleColumns', function() {
        it('should keep track of state information for visible grid columns', function() {
            var columns = [
                // It's necessary to pass in columns with a headerId property for this test.
                { header: 'Name',  headerId: 'a', dataIndex: 'name', width: 100 },
                { header: 'Email', headerId: 'b', dataIndex: 'email', flex: 1 },
                { header: 'Phone', headerId: 'c', dataIndex: 'phone', flex: 1, hidden: true }
            ];

            createGrid({}, {
                columns: columns,
                stateful: true,
                stateId: 'foo'
            });

            // Update state information.
            grid.columns[2].show();

            grid.saveState();

            Ext.destroy(grid);

            createGrid({}, {
                columns: columns,
                stateful: true,
                stateId: 'foo'
            });

            expect(grid.headerCt.gridVisibleColumns.length).toBe(3);
        });

        it('should constrain the grid view width to the visible columns width when enableLocking is true', function() {
            var columns = [
                // It's necessary to pass in columns with a headerId property for this test.
                { header: 'Name',  id: 'a', dataIndex: 'name', width: 200 },
                { header: 'Email', id: 'b', dataIndex: 'email', width: 200 },
                { header: 'Phone', id: 'c', dataIndex: 'phone', width: 200 }
            ];

            createGrid({}, {
                width: 400,
                enableLocking: true,
                columns: columns,
                stateful: true,
                stateId: 'foo',
                listeners: {
                    beforerender: {
                        fn: function() {
                            var state = [{
                                id: 'a'
                            }, {
                                id: 'b'
                            }, {
                                id: 'c',
                                hidden: true
                            }];

                            this.applyState({
                                columns: state
                            });
                        }
                    }
                }
            });

            expect(grid.normalGrid.getView().el.dom.scrollWidth).toBe(400);
        });

        it('should keep track of state information for visible grid columns when moved', function() {
            // This spec simulates a stateful bug: EXTJSIV-10262. This bug occurs when a previously hidden
            // header is shown and then moved. The bug occurs because the gridVisibleColumns cache is created
            // from stale information. This happens when the visible grid columns are retrieved before applying
            // the updated state info.
            var columns = [
                // It's necessary to pass in columns with a headerId property for this test.
                { header: 'Name',  headerId: 'a', dataIndex: 'name', width: 100 },
                { header: 'Email', headerId: 'b', dataIndex: 'email', flex: 1 },
                { header: 'Phone', headerId: 'c', dataIndex: 'phone', flex: 1, hidden: true }
            ];

            createGrid({}, {
                columns: columns,
                stateful: true,
                stateId: 'foo'
            });

            // Update state information.
            grid.columns[2].show();
            grid.headerCt.move(2, 0);

            grid.saveState();

            Ext.destroy(grid);

            createGrid({}, {
                columns: columns,
                stateful: true,
                stateId: 'foo'
            });

            expect(grid.headerCt.gridVisibleColumns.length).toBe(3);
            expect(grid.headerCt.gridVisibleColumns[0].dataIndex).toBe('phone');
        });

        it('should insert new columns into their correct new ordinal position after state restoration', function() {
            // Test ticket EXTJS-15690.
            var initialColumns = [
                    // It's necessary to pass in columns with a headerId property for this test.
                    { header: 'Email', headerId: 'b', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', headerId: 'c', dataIndex: 'phone', flex: 1 }
                ],
                newColumns = [
                    // It's necessary to pass in columns with a headerId property for this test.
                    { header: 'Name',  headerId: 'a', dataIndex: 'name', width: 100 },
                    { header: 'Email', headerId: 'b', dataIndex: 'email', flex: 1 },
                    { header: 'Phone', headerId: 'c', dataIndex: 'phone', flex: 1 }
                ];

            createGrid({}, {
                columns: initialColumns,
                stateful: true,
                stateId: 'foo'
            });

            // Update state information.
            // Should now be Phone,Email
            grid.headerCt.move(1, 0);

            grid.saveState();

            Ext.destroy(grid);

            // Create the grids with a new column in at index 0
            // The stateful columns should be in their stateful *order*
            // But the insertion point of the new column must be honoured.
            createGrid({}, {
                columns: newColumns,
                stateful: true,
                stateId: 'foo'
            });

            // The order of the two initial stateful columns should be restored.
            // And the new, previously unknown column "name" which was configured
            // At index 0 should have been inserted at index 0
            expect(grid.headerCt.gridVisibleColumns[0].dataIndex).toBe('name');
            expect(grid.headerCt.gridVisibleColumns[1].dataIndex).toBe('phone');
            expect(grid.headerCt.gridVisibleColumns[2].dataIndex).toBe('email');
        });
    });

    describe('non-column descendants of headerCt', function() {
        describe('headerCt events', function() {
            var headerCt, field;

            beforeEach(function() {
                createGrid(null, {
                    columns: [
                        { header: 'Name',  dataIndex: 'name', width: 100 },
                        { header: 'Email', dataIndex: 'email', flex: 1,
                            items: [{
                                xtype: 'textfield'
                            }]
                        }
                    ]
                });

                headerCt = grid.headerCt;
                field = headerCt.down('textfield');
            });

            afterEach(function() {
                headerCt = field = null;
            });

            it('should not throw in reaction to a delegated keydown event', function() {
                // Note that unfortunately we're testing a private method since that's where it throws.
                jasmine.fireKeyEvent(field.inputEl, 'keydown', 13);

                expect(function() {
                    var e = {
                        isEvent: true,
                        target: field.inputEl.dom,
                        getTarget: function() {
                            return field.inputEl.dom;
                        }
                    };

                    headerCt.onHeaderActivate(e);
                }).not.toThrow();
            });

            it('should not react to keydown events delegated from the headerCt', function() {
                // For this test, we'll know that the event was short-circuited b/c the sortable column
                // wasn't sorted.
                var wasCalled = false,
                    fn = function() {
                        wasCalled = true;
                    };

                headerCt.on('sortchange', fn);
                jasmine.fireKeyEvent(field.inputEl, 'keydown', 13);

                expect(wasCalled).toBe(false);
            });
        });
    });

    describe("keyboard events", function() {
        var headerCt;

        beforeEach(function() {
            createGrid(null, {
                columns: [{
                    header: 'Name', dataIndex: 'name', width: 100
                }, {
                    header: 'Email', dataIndex: 'email', flex: 1
                }, {
                    header: 'Phone', dataIndex: 'phone', flex: 1
                }]
            });

            headerCt = grid.headerCt;

            focusAndWait(headerCt.down('[dataIndex=email]'));
        });

        afterEach(function() {
            headerCt = null;
        });

        it("should focus first column header on Home key", function() {
            jasmine.syncPressKey(headerCt.el, 'home');
            expectFocused(headerCt.gridVisibleColumns[0]);
        });

        it("should focus last column header on End key", function() {
            jasmine.syncPressKey(headerCt.el, 'end');
            expectFocused(headerCt.gridVisibleColumns[2]);
        });
    });

    describe('Disabling column hiding', function() {
        beforeEach(function() {
            createGrid();
        });

        it('should disable hiding the last visible column', function() {
            var menu,
                col = grid.columns[0],
                colItem,
                colMenu,
                nameItem,
                emailItem;

            // Open the header menu and mouseover the "Columns" item.
            Ext.testHelper.showHeaderMenu(col);

            runs(function() {
                menu = col.activeMenu;
                colItem = menu.child('#columnItem');
                jasmine.fireMouseEvent(colItem.ariaEl.dom, 'mouseover');
                jasmine.fireMouseEvent(colItem.ariaEl.dom, 'click');
            });

            // Wait for the column show/hide menu to appear
            waitsFor(function() {
                colMenu = colItem.menu;

                return colMenu && colMenu.isVisible();
            }, 'column hiding menu to show');

            // Hide the "Name" column, leaving only the "Email" column visible
            runs(function() {
                nameItem = colMenu.child('[text=Name]');
                emailItem = colMenu.child('[text=Email]');
                jasmine.fireMouseEvent(nameItem.ariaEl.dom, 'click');
            });

            // The "Email" column is the last visible column, so its
            // hide menu check item must be disabled.
            waitsFor(function() {
                return emailItem.disabled;
            }, 'last column hiding item to be disabled');
        });
    });

    describe("reconfiguring parent grid", function() {
        it("should activate container after adding columns", function() {
            createGrid({}, { columns: [] });

            expect(grid.headerCt.isFocusableContainerActive()).toBeFalsy();

            grid.reconfigure(null, [
                { header: 'Name',  dataIndex: 'name', width: 100 },
                { header: 'Email', dataIndex: 'email', flex: 1 },
                { header: 'Phone', dataIndex: 'phone', flex: 1, hidden: true }
            ]);

            expect(grid.headerCt.isFocusableContainerActive()).toBeTruthy();
            expect(grid.headerCt.down('gridcolumn')).toHaveAttr('tabIndex', 0);
        });

        it("should deactivate container after removing all columns", function() {
            createGrid();

            expect(grid.headerCt.isFocusableContainerActive()).toBeTruthy();
            expect(grid.headerCt.down('gridcolumn')).toHaveAttr('tabIndex', 0);

            grid.reconfigure(null, []);

            expect(grid.headerCt.isFocusableContainerActive()).toBeFalsy();
        });
    });

    describe('grid panel', function() {
        it('should be notified when adding a column header', function() {
            createGrid({}, { columns: [] });

            grid.headerCt.insert(0, [
                { header: 'Name',  dataIndex: 'name', width: 100 },
                { header: 'Email', dataIndex: 'email', flex: 1 },
                { header: 'Phone', dataIndex: 'phone', flex: 1 }
            ]);

            var view = grid.getView(),
                c0_0 = view.getCellByPosition({ row: 0, column: 0 }, true),
                c0_1 = view.getCellByPosition({ row: 0, column: 1 }, true),
                c0_2 = view.getCellByPosition({ row: 0, column: 2 }, true);

            expect(c0_0).not.toBe(false);
            expect(c0_1).not.toBe(false);
            expect(c0_2).not.toBe(false);

        });

        // EXTJS-21400
        it('should be notified when adding a group header', function() {
            createGrid({}, { columns: [] });

            grid.headerCt.insert(0, { header: 'test', columns: [
                { header: 'Name',  dataIndex: 'name', width: 100 },
                { header: 'Email', dataIndex: 'email', flex: 1 },
                { header: 'Phone', dataIndex: 'phone', flex: 1 }
            ] });

            var view = grid.getView(),
                c0_0 = view.getCellByPosition({ row: 0, column: 0 }, true),
                c0_1 = view.getCellByPosition({ row: 0, column: 1 }, true),
                c0_2 = view.getCellByPosition({ row: 0, column: 2 }, true);

            expect(c0_0).not.toBe(false);
            expect(c0_1).not.toBe(false);
            expect(c0_2).not.toBe(false);
        });
    });
});
