topSuite('grid-moving-columns',
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.form.Label'],
function() {
    var transformStyleName = 'webkitTransform' in document.documentElement.style ? 'webkitTransform' : 'transform',
        GridModel = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: [
                'field1',
                'field2',
                'field3',
                'field4',
                'field5',
                'field6',
                'field7',
                'field8',
                'field9',
                'field10',
                'field11',
                'field12',
                'field13',
                'field14',
                'field15',
                'field16',
                'field17',
                'field18'
            ]
        }),
        headerText = [],
        rowText = [],
        grid, headerCt, locked, view, store, visibleColumns, groupHeader,
        subGroupHeader, colChangeSpy, colMoveSpy, headerCtMoveSpy;

    // Pass a reference to the cmp not an index!
    function dragColumn(from, to, onRight) {
        var fromBox = from.titleEl.getBox(),
            fromMx = fromBox.x + fromBox.width / 2,
            fromMy = fromBox.y + fromBox.height / 2,
            toBox = to.titleEl.getBox(),
            toMx = onRight ? toBox.right - 10 : toBox.left + 10,
            toMy = toBox.y + toBox.height / 2,
            dragThresh = onRight ? Ext.dd.DragDropManager.clickPixelThresh + 1 : -Ext.dd.DragDropManager.clickPixelThresh - 1;

        // Mousedown on the header to drag
        jasmine.fireMouseEvent(from.el.dom, 'mouseover', fromMx, fromMy);
        jasmine.fireMouseEvent(from.titleEl.dom, 'mousedown', fromMx, fromMy);

        // The initial move which tiggers the start of the drag
        jasmine.fireMouseEvent(from.el.dom, 'mousemove', fromMx + dragThresh, fromMy);

        if (locked) {
            // Locked grids need an additional mousemove because the drop won't be valid if the target headerCt isn't the same as
            // the target headerCt of the last mousemove event. So, we need to hack around this by firing an additional event so
            // the two mouseevents can be seen as having the same target headerCt.
            jasmine.fireMouseEvent(to.el.dom, 'mousemove', (onRight ? toMx + 1 : toMx - 1), toMy);
        }

        // The move to left of the centre of the target element
        jasmine.fireMouseEvent(to.el.dom, 'mousemove', toMx, toMy);

        // Drop to left of centre of target element
        jasmine.fireMouseEvent(to.el.dom, 'mouseup', toMx, toMy);

        refreshHeaderCache();
    }

    function makeGrid(columns, data, cfg) {
        var i;

        if (!columns) {
            columns = [];

            for (i = 1; i < 19; i++) {
                columns.push({
                    dataIndex: 'field' + i,
                    text: 'Field ' + i,
                    width: 90
                });
            }
        }

        store = new Ext.data.Store({
            model: GridModel,
            data: data || [{
                field1: 1,
                field2: 2,
                field3: 3,
                field4: 4,
                field5: 5,
                field6: 6,
                field7: 7,
                field8: 8,
                field9: 9,
                field10: 10,
                field11: 11,
                field12: 12,
                field13: 13,
                field14: 14,
                field15: 15,
                field16: 16,
                field17: 17,
                field18: 18
            }]
        });

        grid = new Ext.grid.Panel(Ext.apply({
            columns: columns,
            store: store,
            width: 2000,
            height: 500,
            border: false,
            viewConfig: {
                mouseOverOutBuffer: 0
            }
        }, cfg));

        // Don't use renderTo since that may throw and we won't set "grid" and will then leak the component.
        if (!cfg || cfg.renderTo === undefined) {
            grid.render(Ext.getBody());
        }

        headerCt = grid.headerCt;
        refreshHeaderCache();
        view = grid.getView();
    }

    // Make sure we get a fresh copy of the columns cache after every column move.
    function refreshHeaderCache() {
        // Use the first group header as a default header. Use grid since we operate on locked grids, too.
        var deepGroupHeader = grid.down('[isGroupHeader]'),
            headerCt = grid.headerCt;

        visibleColumns = headerCt.gridVisibleColumns || headerCt.visibleColumnManager.getColumns();

        // TODO: add comment here!
        if ((typeof groupHeader !== 'number') &&
            (!groupHeader || (groupHeader.items && groupHeader.items.length))) {
            groupHeader = deepGroupHeader;
        }
    }

    function spyOnEvent(object, eventName, fn) {
        var obj = {
            fn: fn || Ext.emptyFn
        },
        spy = spyOn(obj, 'fn');

        object.addListener(eventName, obj.fn);

        return spy;
    }

    // Test that the header text matches what we expect after a column move.
    // Shouldn't need to be called by itself. Call testUI() instead.
    function testHeaderContainer(order) {
        var headerString;

        headerText.length = 0;

        // Gather header texts.
        Ext.Array.each(grid.getVisibleColumnManager().getColumns(), function(c) {
            headerText.push(c.text);
        });

        // Prepend 'Field' to each number to match the header string.
        headerString = order.replace(/(\d+,?)/g, function(a, $1) {
            return 'Field' + $1;
        });

        // Check reordering has been done and that the top HeaderContainer has refreshed its cache.
        expect(headerText.join(',')).toEqual(headerString);
    }

    function testSpies(count) {
        // The remove from group header and insertion into another group header should not both trigger the grid to fire this event.
        expect(colChangeSpy.callCount).toBe(count[0]);
        // One columnmove event should have been fired.
        expect(colMoveSpy.callCount).toBe(count[1]);
        expect(headerCtMoveSpy.callCount).toBe(count[1]);
    }

    // Test that the view data column text matches what we expect after a column move.
    // Shouldn't need to be called by itself.  Call testUI() instead.
    function testView(order) {
        rowText.length = 0;

        if (!locked) {
            Ext.Array.each(view.getRow(view.all.item(0)).childNodes, function(n) {
                rowText.push(n.textContent || n.innerText);
            });
        }
        else {
            // For locked grids, we must loop over both views of the locking partners, lockedView first.
            Ext.Array.each(view.getRow(view.lockedView.all.item(0)).childNodes, function(n) {
                rowText.push(n.textContent || n.innerText);
            });

            Ext.Array.each(view.getRow(view.normalView.all.item(0)).childNodes, function(n) {
                rowText.push(n.textContent || n.innerText);
            });
        }

        // Check reordering has been done on the data and that the top HeaderContainer has refreshed its cache.
        expect(rowText.join(',')).toEqual(order);
    }

    // All this needs is the order of the header text and the view data column text that is expected after a
    // column move.  The testHeaderText() function will append 'Field' to each field of the CSV.
    function testUI(order, testRowText) {
        testHeaderContainer(order);

        if (testRowText !== false) {
            testView(order);
        }
    }

    afterEach(function() {
        Ext.destroy(grid, store);
        grid = store = locked = visibleColumns = groupHeader = subGroupHeader = colChangeSpy = colMoveSpy = headerCtMoveSpy = headerCt = null;
        Ext.data.Model.schema.clear();
        headerText.length = rowText.length = 0;
    });

    describe('destroy during column header drag', function() {
        it('should move columns', function() {
            makeGrid([{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                dataIndex: 'field3',
                header: 'Field3'
            }, {
                dataIndex: 'field4',
                header: 'Field4'
            }, {
                dataIndex: 'field5',
                header: 'Field5'
            }, {
                dataIndex: 'field6',
                header: 'Field6'
            }, {
                dataIndex: 'field7',
                header: 'Field7'
            }], null, {
                enableColumnResize: false,
                header: false
            });
            var c0 = grid.getColumnManager().getColumns()[0],
                headerReorderer = grid.headerCt.findPlugin('gridheaderreorderer'),
                proxyEl;

            runs(function() {
                jasmine.fireMouseEvent(c0.el.dom, 'mouseover');
                jasmine.fireMouseEvent(c0.titleEl.dom, 'mousedown');
                jasmine.fireMouseEvent(document.body, 'mousemove', 100, 0);
            });

            // Wait for the event to be processed
            waits(1);

            runs(function() {
                proxyEl = headerReorderer.dragZone.proxy.el;

                // The drag zone's proxy should be visible
                expect(proxyEl.isVisible()).toBe(true);

                // Destroy should be tolerated
                grid.destroy();

                // The element has been destroyed
                expect(proxyEl.dom).toBeFalsy();

                // All listeners should be removed, and mousemoves should be ignored.
                // These should throw no errors.
                jasmine.fireMouseEvent(document.body, 'mousemove', 100, 0);
                jasmine.fireMouseEvent(document.body, 'mouseup');
            });
        });
    });

    describe('destroying a component in the midst of a drag operation', function() {
        function beginColumnDrag(from, to, onRight) {
            var fromBox = from.el.getBox(),
                fromMx = fromBox.x + fromBox.width / 2,
                fromMy = fromBox.y + fromBox.height / 2,
                toBox = to.el.getBox(),
                toMx = toBox.x,
                toMy = toBox.y + toBox.height / 2,
                offset = onRight ? toBox.width - 6 : 5,
                moveOffset = toMx + offset,
                dragThresh = onRight ? Ext.dd.DragDropManager.clickPixelThresh + 1 : -Ext.dd.DragDropManager.clickPixelThresh - 1;

            // Mousedown on the header to drag
            jasmine.fireMouseEvent(from.el.dom, 'mouseover', fromMx, fromMy);
            jasmine.fireMouseEvent(from.titleEl.dom, 'mousedown', fromMx, fromMy);
            from.el.focus();

            // The initial move which tiggers the start of the drag
            jasmine.fireMouseEvent(from.el.dom, 'mousemove', fromMx + dragThresh, fromMy);

            if (locked) {
                // Locked grids need an additional mousemove because the drop won't be valid if the target headerCt isn't the same as
                // the target headerCt of the last mousemove event. So, we need to hack around this by firing an additional event so
                // the two mouseevents can be seen as having the same target headerCt.
                //
                // Note: Do not change the value stored in the moveOffset var!
                jasmine.fireMouseEvent(to.el.dom, 'mousemove', (onRight ? moveOffset + 1 : moveOffset - 1), toMy);
            }

            // The move to left of the centre of the target element
            jasmine.fireMouseEvent(to.el.dom, 'mousemove', moveOffset, toMy);
        }

        // The trick to reproducing this bug is to initiate a drag operation but not complete it.
        // Then, destroy the grid and recreate it. As soon as another drag operation is initiated,
        // the DragDropManager will attempt to complete the last drag, calling DragSource:onDragOut
        // with the id of the recently-destroyed drag zone.  The fix ensures that this call will
        // be pre-empted by checking the new .destroyed property on the dd object.
        // See EXTJSIV-11386.
        beforeEach(function() {
            // Create the grid, start the drag and destroy the grid before the drag operation is completed.
            makeGrid();
            beginColumnDrag(visibleColumns[3], visibleColumns[1], true);
            grid.destroy();
            Ext.data.Model.schema.clear();
        });

        it('should not try to complete the drag operation', function() {
            var dragZone,
                errorSpy = jasmine.createSpy(),
                old = window.onerror;

            makeGrid();
            dragZone = grid.headerCt.reorderer.dragZone;
            spyOn(dragZone, 'onDragOut').andCallThrough();

            window.onerror = errorSpy.andCallFake(function() {
                if (old) {
                    old();
                }
            });

            dragColumn(visibleColumns[3], visibleColumns[1]);

            expect(errorSpy).not.toHaveBeenCalled();

            window.onerror = old;

            expect(dragZone.onDragOut).toHaveBeenCalled();
        });

        it('should not cache any references to the destroyed drop zone object in the DragDropManager', function() {
            makeGrid();
            dragColumn(visibleColumns[3], visibleColumns[1]);

            expect(Ext.dd.DragDropManager.dragOvers[grid.headerCt.reorderer.dropZone.id]).toBeUndefined();
        });
    });

    describe('Header movement using the UI', function() {
        it('should move columns', function() {
            makeGrid([{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                dataIndex: 'field3',
                header: 'Field3',
                hidden: true
            }, {
                dataIndex: 'field4',
                header: 'Field4'
            }, {
                dataIndex: 'field5',
                header: 'Field5'
            }], null, {
                enableColumnResize: false,
                header: false
            });

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            dragColumn(visibleColumns[3], visibleColumns[1]);
            // [colChange, colMove]
            testSpies([1, 1]);
            testUI('1,5,2,4');

            // Wait for the mouse event blocking set during drags within the headerCt to be lifted.
            // Need to allow the asap timer to fire to expose https://sencha.jira.com/browse/EXTJS-22839
            // which was that the mouse blocking was not being lifted the second time.
            waits(100);

            runs(function() {
                dragColumn(visibleColumns[3], visibleColumns[1]);
                // [colChange, colMove]
                testSpies([2, 2]);
                testUI('1,4,5,2');

                // For devices we know deal with focus, test that focus is preserved.
                if (!jasmine.supportsTouch && !Ext.supports.AsyncFocusEvents) {
                    expect(Ext.Element.getActiveElement()).toBe(visibleColumns[1].el.dom);
                }
            });

            // Wait for the mouse event blocking set during drags within the headerCt to be lifted.
            // We're testing https://sencha.jira.com/browse/EXTJS-22839 below
            waits(100);

            runs(function() {
                // Tapping on a column header should still sort
                var sortSpy = spyOn(store, 'sort');

                Ext.testHelper.tap(visibleColumns[0].titleEl);
                expect(sortSpy).toHaveBeenCalled();
            });
        });

        it('should move columns to the end of the header container', function() {
            makeGrid([{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                dataIndex: 'field3',
                header: 'Field3',
                hidden: true
            }, {
                dataIndex: 'field4',
                header: 'Field4'
            }, {
                dataIndex: 'field5',
                header: 'Field5'
            }], null, {
                enableColumnResize: false,
                header: false
            });

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            // Use mouse events to move the column *to the right* of column 3
            dragColumn(visibleColumns[0], visibleColumns[3], true);
            // [colChange, colMove]
            testSpies([1, 1]);
            testUI('2,4,5,1');

            // Use mouse events to move the column *to the right* of column 3
            dragColumn(visibleColumns[0], visibleColumns[3], true);
            // [colChange, colMove]
            testSpies([2, 2]);
            testUI('4,5,1,2');

            grid.destroy();
        });

        it('should move grouped columns with subitems to the end of the header container', function() {
            makeGrid([{
                header: 'Field1',
                columns: [{
                    dataIndex: 'field2',
                    header: 'Field2',
                    items: [{
                        xtype: 'label',
                        text: 'Foo'
                    }]
                }, {
                    dataIndex: 'field3',
                    header: 'Field3',
                    items: [{
                        xtype: 'label',
                        text: 'Bar'
                    }]
                }]
            }, {
                dataIndex: 'field4',
                header: 'Field4'
            }, {
                dataIndex: 'field5',
                header: 'Field5'
            }], null, {
                enableColumnResize: false,
                header: false
            });

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            // Use mouse events to move the column *to the right* of column 3
            expect(function() {
                dragColumn(visibleColumns[0], visibleColumns[2], true);
            }).not.toThrow();

            // [colChange, colMove]
            testSpies([1, 1]);
            testUI('3,4,2,5');

            // Use mouse events to move the column *to the right* of column 3
            expect(function() {
                dragColumn(visibleColumns[0], visibleColumns[2], true);
            }).not.toThrow();
            // [colChange, colMove]
            testSpies([2, 2]);
            testUI('4,2,3,5');

            grid.destroy();
        });

        it('should move columns to the start of the header container', function() {
            makeGrid([{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                dataIndex: 'field3',
                header: 'Field3',
                hidden: true
            }, {
                dataIndex: 'field4',
                header: 'Field4'
            }, {
                dataIndex: 'field5',
                header: 'Field5'
            }], null, {
                enableColumnResize: false,
                header: false
            });

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            dragColumn(visibleColumns[3], visibleColumns[0]);
            // [colChange, colMove]
            testSpies([1, 1]);
            testUI('5,1,2,4');

            dragColumn(visibleColumns[3], visibleColumns[0]);
            // [colChange, colMove]
            testSpies([2, 2]);
            testUI('4,5,1,2');

            grid.destroy();
        });

        it('should only fire columnmove once when moving columns between column groups', function() {
            makeGrid([{
                header: 'Group 1',
                columns: [{
                    dataIndex: 'field1',
                    header: 'Field1'
                }, {
                    dataIndex: 'field2',
                    header: 'Field2'
                }, {
                    dataIndex: 'field3',
                    header: 'Field3'
                }]
            }, {
                header: 'Group2',
                columns: [{
                    dataIndex: 'field4',
                    header: 'Field4'
                }, {
                    dataIndex: 'field5',
                    header: 'Field5'
                }, {
                    dataIndex: 'field6',
                    header: 'Field6'
                }, {
                    dataIndex: 'field7',
                    header: 'Field7'
                }]
            }], null, {
                enableColumnResize: false,
                header: false
            });

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            dragColumn(visibleColumns[0], visibleColumns[5]);
            // [colChange, colMove]
            testSpies([1, 1]);
            testUI('2,3,4,5,1,6,7');

            grid.destroy();
        });

        it('should work when columns are hidden', function() {
            var allColumns;

            makeGrid([{
                header: 'Group 1',
                columns: [{
                    dataIndex: 'field1',
                    header: 'Field1'
                }, {
                    dataIndex: 'field2',
                    header: 'Field2'
                }, {
                    dataIndex: 'field3',
                    header: 'Field3'
                }]
            }, {
                header: 'Group2',
                columns: [{
                    dataIndex: 'field4',
                    header: 'Field4'
                }, {
                    dataIndex: 'field5',
                    header: 'Field5'
                }, {
                    dataIndex: 'field6',
                    header: 'Field6'
                }, {
                    dataIndex: 'field7',
                    header: 'Field7'
                }]
            }], null, {
                enableColumnResize: false,
                header: false
            });

            allColumns = grid.getColumnManager().getColumns();

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            allColumns[0].hide();
            allColumns[3].hide();
            // Refresh the cache since column(s) were hidden.
            refreshHeaderCache();

            // [colChange, colMove]
            // The hide operations should both trigger the grid to fire this event.
            // colMove = 0
            testSpies([2, 0]);

            // Refresh the cache since we hid columns.
            refreshHeaderCache();
            dragColumn(visibleColumns[2], visibleColumns[0]);
            testSpies([3, 1]);

            testUI('5,2,3,6,7');

            grid.destroy();
        });

        it('should work moving columns across group columns', function() {
            makeGrid([{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                header: 'Group 1',
                columns: [{
                    dataIndex: 'field3',
                    header: 'Field3'
                }, {
                    dataIndex: 'field4',
                    header: 'Field4'
                }, {
                    dataIndex: 'field5',
                    header: 'Field5'
                }, {
                    dataIndex: 'field6',
                    header: 'Field6'
                }]
            }, {
                dataIndex: 'field7',
                header: 'Field7'
            }, {
                dataIndex: 'field8',
                header: 'Field8'
            }], null, {
                enableColumnResize: false,
                header: false
            });

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            // Use mouse events to move the column to the Left of column 6
            dragColumn(visibleColumns[0], visibleColumns[6]);
            // [colChange, colMove]
            testSpies([1, 1]);
            testUI('2,3,4,5,6,1,7,8');

            // Use mouse events to move the column to the Left of column 0
            dragColumn(visibleColumns[7], visibleColumns[0]);
            // [colChange, colMove]
            testSpies([2, 2]);
            testUI('8,2,3,4,5,6,1,7');

            grid.destroy();
        });

        it('should work moving group columns', function() {
            makeGrid([{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                header: 'Group 1',
                columns: [{
                    dataIndex: 'field3',
                    header: 'Field3'
                }, {
                    dataIndex: 'field4',
                    header: 'Field4'
                }, {
                    dataIndex: 'field5',
                    header: 'Field5'
                }, {
                    dataIndex: 'field6',
                    header: 'Field6'
                }]
            }, {
                dataIndex: 'field7',
                header: 'Field7'
            }, {
                dataIndex: 'field8',
                header: 'Field8'
            }], null, {
                enableColumnResize: false,
                header: false
            });

            colChangeSpy = spyOnEvent(grid, 'columnschanged');
            colMoveSpy = spyOnEvent(grid, 'columnmove');
            headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

            // Use mouse events to move the column to the Left of column 6
            grid.headerCt.move(2, 3);
            // [colChange, colMove]
            testSpies([1, 1]);

            // Don't test the view rows b/c the above move operation will only move the headers.
            testUI('1,2,7,3,4,5,6,8', /* testRowText */ false);

            grid.destroy();
        });

        describe('moving column(s) out of a group to the root container', function() {
            it('should work moving a column out of a group', function() {
                makeGrid([{
                    dataIndex: 'field1',
                    header: 'Field1'
                }, {
                    dataIndex: 'field2',
                    header: 'Field2'
                }, {
                    header: 'Group 1',
                    columns: [{
                        dataIndex: 'field3',
                        header: 'Field3'
                    }, {
                        dataIndex: 'field4',
                        header: 'Field4'
                    }]
                }, {
                    header: 'Group2',
                    columns: [{
                        dataIndex: 'field5',
                        header: 'Field5'
                    }, {
                        dataIndex: 'field6',
                        header: 'Field6'
                    }]
                }, {
                    dataIndex: 'field7',
                    header: 'Field7'
                }, {
                    dataIndex: 'field8',
                    header: 'Field8'
                }], null, {
                    enableColumnResize: false,
                    header: false
                });
                var allColumns = grid.getColumnManager().getColumns();

                colChangeSpy = spyOnEvent(grid, 'columnschanged');
                colMoveSpy = spyOnEvent(grid, 'columnmove');
                headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

                allColumns[0].hide();
                // Refresh the cache since column(s) were hidden.
                refreshHeaderCache();

                // Use mouse events to move the column to the RIGHT of column 0
                dragColumn(visibleColumns[1], visibleColumns[0], true);
                // [colChange, colMove]
                testSpies([2, 1]);
                testUI('2,3,4,5,6,7,8');

                // Use mouse events to move the column to the LEFT of column 2
                dragColumn(visibleColumns[1], visibleColumns[2]);
                // [colChange, colMove]
                testSpies([3, 2]);
                testUI('2,3,4,5,6,7,8');

                grid.destroy();
            });

            it('should work moving columns out of a group when columns are hidden before a group', function() {
                var allColumns;

                makeGrid([{
                    dataIndex: 'field1',
                    header: 'Field1'
                }, {
                    dataIndex: 'field2',
                    header: 'Field2'
                }, {
                    header: 'Group 1',
                    columns: [{
                        dataIndex: 'field3',
                        header: 'Field3'
                    }, {
                        dataIndex: 'field4',
                        header: 'Field4'
                    }]
                }, {
                    header: 'Group2',
                    columns: [{
                        dataIndex: 'field5',
                        header: 'Field5'
                    }, {
                        dataIndex: 'field6',
                        header: 'Field6'
                    }]
                }, {
                    dataIndex: 'field7',
                    header: 'Field7'
                }, {
                    dataIndex: 'field8',
                    header: 'Field8'
                }], null, {
                    enableColumnResize: false,
                    header: false
                });

                allColumns = grid.getColumnManager().getColumns();

                colChangeSpy = spyOnEvent(grid, 'columnschanged');
                colMoveSpy = spyOnEvent(grid, 'columnmove');
                headerCtMoveSpy = spyOnEvent(grid.headerCt, 'columnmove');

                allColumns[0].hide();
                allColumns[1].hide();
                // Refresh the cache since column(s) were hidden.
                refreshHeaderCache();

                // Use mouse events to move the column to the RIGHT of column 3
                dragColumn(visibleColumns[4], visibleColumns[3], true);
                // [colChange, colMove]
                testSpies([3, 1]);
                testUI('3,4,5,6,7,8');

                // Use mouse events to move the column to the LEFT of column 5
                dragColumn(visibleColumns[4], visibleColumns[5]);
                // [colChange, colMove]
                testSpies([4, 2]);
                testUI('3,4,5,6,7,8');

                grid.destroy();
            });

            describe('moving the last header out of a group', function() {
                function fn(expectMore) {
                    makeGrid([{
                        dataIndex: 'field1',
                        header: 'Field1'
                    }, {
                        dataIndex: 'field2',
                        header: 'Field2'
                    }, {
                        header: 'Group1',
                        columns: [{
                            dataIndex: 'field3',
                            header: 'Field3'
                        }, {
                            dataIndex: 'field4',
                            header: 'Field4'
                        }]
                    }, {
                        dataIndex: 'field5',
                        header: 'Field5'
                    }], null, {
                        enableColumnResize: false,
                        header: false
                    });

                    // Use mouse events to move the column to the LEFT of column 1
                    dragColumn(visibleColumns[2], visibleColumns[1]);

                    testUI('1,3,2,4,5');

                    // Use mouse events to move the column to the LEFT of column 4.
                    //
                    // NOTE that after this drag is where the bug would occur, as the headers would be offset +1 ahead of
                    // their respective data columns.
                    dragColumn(visibleColumns[3], groupHeader);

                    testUI('1,3,2,4,5');

                    if (expectMore) {
                        expectMore();
                    }

                    grid.destroy();
                }

                it('should work', function() {
                    fn();
                });

                it('should remove the group header when the last subheader is removed', function() {
                    fn(function() {
                        expect(groupHeader.rendered).toBe(false);
                        expect(groupHeader.ownerCt).toBe(null);
                    });
                });
            });
        });

        describe('locking grids', function() {
            it('should work when configured without any locked columns', function() {
                var visibleColumnManager, column;

                makeGrid(null, null, {
                    enableLocking: true
                });

                visibleColumnManager = grid.lockedGrid.getVisibleColumnManager();
                column = grid.columns[0];

                expect(visibleColumnManager.getColumns().length).toBe(0);
                grid.lock(column);

                expect(visibleColumnManager.getColumns().length).toBe(1);
                expect(visibleColumnManager.getFirst() === column).toBe(true);
            });
        });
    });

    describe('nested groups', function() {
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // !!! READ THIS TO UNDERSTAND HOW TO SET UP THE TESTS !!!
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        //
        // ---------------------------
        // :: Using the runTest API ::
        // ---------------------------
        //
        // The idea is to make several drag movements in a row via the range() or sequence() APIs
        // (although they're not called directly). The point is to abstract as much as possible so the
        // dev doesn't need to call makeGrid(), dragColumn() or any other helper function so a spec
        // can be quickly and easily written.
        //
        // The runTest() API is the only function that the dev needs to worry about.  This function
        // takes two arguments, the second of which is optional:
        //
        //      runTest(Object specConfig, Function additionalTests);
        //
        // In addition, it can take the following configs:
        //      columns = The grid columns.
        //      dropPosition = (defaults to 'after')
        //      locked = Specify `true` for locking grids.
        //      order = The expected order of headers AND view columns after all moves have run.
        //      range = The range of moves. Only specify a range or a sequence, not both.
        //      sequence = The sequence of moves. Only specify a range or a sequence, not both.
        //      subGroupHeader = The sub group header below the groupHeader.
        //      onRight = This is dependent upon the dropPosition, i.e.:
        //
        //          `dropPosition === 'before' ? false : true;`
        //
        //      groupHeader = This will default to the first groupHeader in the grid unless specified here.
        //
        // NOTE: See the section 'Accessing Group Headers' to understand how to reference any group headers
        // needed for any test.
        //
        // --------------
        // :: Examples ::
        // --------------
        //
        // Here is an example of a test that will move a range of columns in order, one after the other:
        //
        //            runTest({
        //                columns: columns,
        //                dropPosition: 'before',
        //                order: '1,2,3,4,5,6,7,8',
        //                range: [2, 5]
        //            }, additionalSpec);
        //
        // Here is an example of a test that will runs a sequence of column moves (most common):
        //
        //            runTest({
        //                columns: columns,
        //                order: '1,2,6,11,12,13,5,4,3,7,8,9,10',
        //                sequence: [
        //                    [8, 12, false],
        //                    [5, 7, false],
        //                    ['groupHeader', 10, false],
        //                    [13, 9, false]
        //                ],
        //            }, additionalSpec);
        //
        // Note that the sequence can be in any order and can reference group headers, as well.
        //
        // -----------------------------
        // :: Accessing Group Headers ::
        // -----------------------------
        //
        // If you want to have access to the subGroupHeader variable, then configure your
        // test so `subGroupHeader` is defined, the ordinal will be the number of levels deep
        // in the CQ query, i.e., rootHeaderCt.query('[isGroupHeader'])[subGroupHeader].
        // You can then use this to reference the contents of the subGroupHeader in your
        // sequence, i.e., [1, 'subGroupHeader', false].
        // NOTE:
        // `groupHeader` will refer to the first group header in the header container.
        // `subGroupHeader` will refer to the group header that subGroupHeader references,
        // otherwise it will also point to groupHeader.
        //
        // If you need to reference nested group headers below the first group header, you'll
        // need to tell the test which nested group header should be referenced by the groupHeader
        // variable.  You can do this by specifying groupHeader in your test config.
        //
        // For example, if you have 3 nested groups, you can reference the deepest group header
        // and the second nested group header like this:
        //
        //              runTest({
        //                  columns: columns,
        //                  order: '1,2,6,7,8,9,3,4,5,10,11,12,13,14,15,16',
        //                  sequence: [
        //                      ['subGroupHeader', 'groupHeader', false]
        //                  ],
        //                  groupHeader: 1,
        //                  subGroupHeader: 2
        //              }, function() {
        //                  expect(groupHeader.ownerCt).toBe(null);
        //              });
        //
        // Note that `dropPosition` only needs to be declared if using a range.
        //
        var columns, dropPosition, order, range, sequence, subGroupHeader, onRight, stateful, skipMove;

        function runTest(cfg, expectMore) {
            setVars(cfg);

            // Note: I didn't include a way to configure the grid for these tests.
            // This could be a TODO item, but I didn't see the necessity of it.
            makeGrid(columns, null, {
                enableColumnResize: false,
                header: false,
                stateful: stateful,
                stateId: 'quux'
            });

            // skipMove is useful when testing grid state for stateful unit tests.
            if (!skipMove) {
                doMove();
            }

            testUI(order);

            if (expectMore) {
                expectMore();
            }

            // Save state for stateful unit tests.
            if (stateful) {
                grid.saveState();
            }

            grid.destroy();

            // Null out the refs in case multiple tests are run in a single 'if' block.
            groupHeader = subGroupHeader = null;
        }

        function setVars(cfg) {
            cfg = cfg || {};
            columns = cfg.columns;
            dropPosition = cfg.dropPosition || 'after';
            locked = cfg.locked;
            order = cfg.order;
            range = cfg.range;
            sequence = cfg.sequence;
            skipMove = cfg.skipMove;
            stateful = cfg.stateful;
            subGroupHeader = cfg.subGroupHeader;
            onRight = dropPosition === 'before' ? false : true;

            if (cfg.groupHeader) {
                groupHeader = cfg.groupHeader;
            }
        }

        function doMove() {
            if (range) {
                dragRange();
            }
            else {
                dragSequence();
            }
        }

        function dragRange() {
            var begin = range[0],
                end = range[1];

            if (begin === end) {
                return;
            }

            setGroupHeaders();

            if (begin > end) {
                for (; begin >= end; begin--) {
                    dragColumn(visibleColumns[begin], subGroupHeader, onRight);
                }
            }
            else {
                for (; begin <= end; begin++) {
                    dragColumn(visibleColumns[begin], subGroupHeader, onRight);
                }
            }
        }

        function dragSequence() {
            var headers, i, len, pos, zero, one, from, to;

            setGroupHeaders();

            // 'groupHeader' and 'subGroupHeader' need to be able to be values in
            // test sequences, so look them up now.
            headers = {
                'groupHeader': groupHeader,
                'subGroupHeader': subGroupHeader
            };

            for (i = 0, len = sequence.length; i < len; i++) {
                pos = sequence[i];
                zero = pos[0];
                one = pos[1];

                from = (typeof zero === 'string') ? headers[zero] : visibleColumns[zero];
                to = (typeof one === 'string') ? headers[one] : visibleColumns[one];

                dragColumn(from, to, pos[2]);
            }
        }

        function setGroupHeaders() {
            groupHeader = (typeof groupHeader === 'number')
                // Use grid since we operate on locked grids, too.
                ? grid.query('[isGroupHeader]')[groupHeader]
                : groupHeader;

            subGroupHeader = (typeof subGroupHeader === 'number')
                // Use grid since we operate on locked grids, too.
                ? grid.query('[isGroupHeader]')[subGroupHeader]
                : groupHeader;
        }

        afterEach(function() {
            columns = dropPosition = subGroupHeader = order = range = sequence = subGroupHeader = onRight = stateful = skipMove = null;
        });

        describe('stateful', function() {
            var columns;

            beforeEach(function() {
                columns = [{
                    dataIndex: 'field1',
                    stateId: 'foo1',
                    header: 'Field1'
                }, {
                    dataIndex: 'field2',
                    stateId: 'foo2',
                    header: 'Field2'
                }, {
                    header: 'Group1',
                    stateId: 'foo3',
                    columns: [{
                        dataIndex: 'field3',
                        stateId: 'foo4',
                        header: 'Field3'
                    }, {
                        dataIndex: 'field4',
                        stateId: 'foo5',
                        header: 'Field4'
                    }, {
                        dataIndex: 'field5',
                        stateId: 'foo6',
                        header: 'Field5'
                    }, {
                        dataIndex: 'field6',
                        stateId: 'foo7',
                        header: 'Field6'
                    }]
                }, {
                    dataIndex: 'field7',
                    stateId: 'foo8',
                    header: 'Field7'
                }, {
                    dataIndex: 'field8',
                    stateId: 'foo9',
                    header: 'Field8'
                }];

                new Ext.state.Provider();
            });

            afterEach(function() {
                Ext.state.Manager.getProvider().clear();
                columns = null;
            });

            it('should work when moving headers within a grouped header', function() {
                runTest({
                    columns: columns,
                    order: '1,2,4,5,6,3,7,8',
                    // Move the first subheader in the first group to be the last subheader in the same group.
                    sequence: [
                        [2, 5, true]
                    ],
                    stateful: true
                });

                runTest({
                    columns: columns,
                    order: '1,2,4,5,6,3,7,8',
                    skipMove: true,
                    stateful: true
                });
            });
        });

        describe('one nested group', function() {
            var columns = [{
                    dataIndex: 'field1',
                    header: 'Field1'
                }, {
                    dataIndex: 'field2',
                    header: 'Field2'
                }, {
                    header: 'Group1',
                    columns: [{
                        dataIndex: 'field3',
                        header: 'Field3'
                    }, {
                        dataIndex: 'field4',
                        header: 'Field4'
                    }, {
                        dataIndex: 'field5',
                        header: 'Field5'
                    }, {
                        dataIndex: 'field6',
                        header: 'Field6'
                    }]
                }, {
                    dataIndex: 'field7',
                    header: 'Field7'
                }, {
                    dataIndex: 'field8',
                    header: 'Field8'
                }];

            describe('dragging all subheaders out of the group', function() {
                describe('when the targetHeader is the groupHeader', function() {
                    function additionalSpec() {
                        expect(groupHeader.rendered).toBe(false);
                        expect(groupHeader.ownerCt).toBe(null);
                    }

                    // Each spec will test that the groupHeader has been removed after the last subheader.
                    it('should work when the move position is before the target header', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'before',
                            order: '1,2,3,4,5,6,7,8',
                            range: [2, 5]
                        }, additionalSpec);
                    });

                    it('should work when the move position is before the target header, in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,6,5,4,3,7,8',
                            sequence: [
                                [5, 'groupHeader', false],
                                [5, 'groupHeader', false],
                                [5, 'groupHeader', false],
                                [5, 'groupHeader', false]
                            ]
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the target header', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,6,5,4,3,7,8',
                            sequence: [
                                [2, 'groupHeader', true],
                                [2, 'groupHeader', true],
                                [2, 'groupHeader', true],
                                [2, 'groupHeader', true]
                            ]
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the target header, in reverse', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'right',
                            order: '1,2,3,4,5,6,7,8',
                            range: [5, 2]
                        }, additionalSpec);
                    });

                    it("should work when the move position alternates between 'before' and 'after'", function() {
                        runTest({
                            columns: columns,
                            order: '1,2,4,3,6,5,7,8',
                            sequence: [
                                // [from, to, onRight]
                                // null === groupHeader
                                [4, 'groupHeader', true],
                                [3, 'groupHeader', false],
                                [3, 'groupHeader', false],
                                [4, 'groupHeader', true]
                            ]
                        }, additionalSpec);
                    });
                });
            });

            describe('when the headers are moved randomly', function() {
                it("should work when the move position is 'before'", function() {
                    runTest({
                        columns: columns,
                        order: '6,1,3,4,2,5,7,8',
                        sequence: [
                            [3, 1, false],
                            [4, 6, false],
                            [4, 0, false],
                            [4, 2, false]
                        ]
                    });
                });

                it("should work when the move position is 'after'", function() {
                    runTest({
                        columns: columns,
                        order: '1,6,2,3,4,7,5,8',
                        sequence: [
                            [3, 1, true],
                            [4, 6, true],
                            [4, 0, true],
                            [4, 2, true]
                        ]
                    });
                });

                it("should work when the move position alternates between 'before' and 'after'", function() {
                    runTest({
                        columns: columns,
                        order: '6,1,4,3,2,7,5,8',
                        sequence: [
                            [3, 1, false],
                            [4, 6, true],
                            [4, 0, false],
                            [4, 2, true]
                        ]
                    });
                });
            });

            describe('moving the group header', function() {
                it('should move the group to the beginning of the root header container, before position', function() {
                    runTest({
                        columns: columns,
                        order: '3,4,5,6,1,2,7,8',
                        sequence: [
                            ['groupHeader', 0, false]
                        ]
                    });
                });

                it('should move the group to the beginning of the root header container, after position', function() {
                    runTest({
                        columns: columns,
                        order: '1,3,4,5,6,2,7,8',
                        sequence: [
                            ['groupHeader', 0, true]
                        ]
                    });
                });

                it('should move the group to the end of the root header container, before position', function() {
                    runTest({
                        columns: columns,
                        order: '1,2,7,3,4,5,6,8',
                        sequence: [
                            ['groupHeader', 7, false]
                        ]
                    });
                });

                it('should move the group to the end of the root header container, after position', function() {
                    runTest({
                        columns: columns,
                        order: '1,2,7,8,3,4,5,6',
                        sequence: [
                            ['groupHeader', 7, true]
                        ]
                    });
                });
            });
        });

        describe('two nested groups', function() {
            var columns = [{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                header: 'Group1',
                columns: [{
                    dataIndex: 'field3',
                    header: 'Field3'
                }, {
                    dataIndex: 'field4',
                    header: 'Field4'
                }, {
                    dataIndex: 'field5',
                    header: 'Field5'
                }, {
                    header: 'Group2',
                    columns: [{
                        dataIndex: 'field6',
                        header: 'Field6'
                    }, {
                        dataIndex: 'field7',
                        header: 'Field7'
                    }, {
                        dataIndex: 'field8',
                        header: 'Field8'
                    }, {
                        dataIndex: 'field9',
                        header: 'Field9'
                    }]
                }, {
                    dataIndex: 'field10',
                    header: 'Field10'
                }]
            }, {
                dataIndex: 'field11',
                header: 'Field11'
            }, {
                dataIndex: 'field12',
                header: 'Field12'
            }, {
                dataIndex: 'field13',
                header: 'Field13'
            }];

            describe('dragging all subheaders out of Group2', function() {
                describe('when the targetHeader is the Group2 groupHeader (so the drag is contiguous to Group2)', function() {
                    // Note: in order to target the correct subgroupheader, define a subGroupHeader config
                    // and then specify 'subGroupHeader' in the sequence.
                    //
                    // The group headers are looked up by:
                    //
                    //      headerCt.query('[isGroupHeader]')[subGroupHeader];
                    //
                    // See setGroupHeaders().
                    //
                    // Similarly, for ranges, specify the subGroupHeader config in addition to the range
                    // config. See an example in the tests below.
                    //
                    // (Remember that groupHeader will refer to the first sub group header!)
                    function additionalSpec() {
                        expect(subGroupHeader.ownerCt).toBe(null);
                        expect(subGroupHeader.rendered).toBe(false);
                    }

                    it('should work when the move position is before the target header', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'before',
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13',
                            range: [5, 8],
                            subGroupHeader: 1
                        });
                    });

                    it('should work when the move position is before the target header, in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,9,8,7,6,10,11,12,13',
                            sequence: [
                                [8, 'subGroupHeader', false],
                                [8, 'subGroupHeader', false],
                                [8, 'subGroupHeader', false],
                                [8, 'subGroupHeader', false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should work when the move position is after the target header', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,9,8,7,6,10,11,12,13',
                            sequence: [
                                [5, 'subGroupHeader', true],
                                [5, 'subGroupHeader', true],
                                [5, 'subGroupHeader', true],
                                [5, 'subGroupHeader', true]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should work when the move position is after the target header, in reverse', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'right',
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13',
                            range: [8, 5],
                            subGroupHeader: 1
                        });
                    });

                    it("should work when the move position alternates between 'before' and 'after'", function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,7,9,6,8,10,11,12,13',
                            sequence: [
                                [6, 'subGroupHeader', false],
                                [7, 'subGroupHeader', true],
                                [6, 'subGroupHeader', true],
                                [6, 'subGroupHeader', false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it("should remove the group header when the last subheader is removed, 'before' move position", function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'before',
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13',
                            range: [5, 8],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });

                    it("should remove the group header when the last subheader is removed, 'after' move position", function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'after',
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13',
                            range: [8, 5],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });
                });

                describe('when the Group2 subheaders are dragged into Group1 (targetHeader is not Group2)', function() {
                    it('should work when the move position is before the first subheader in Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,9,8,7,6,3,4,5,10,11,12,13',
                            sequence: [
                                [5, 2, false],
                                [6, 2, false],
                                [7, 2, false],
                                [8, 2, false]
                            ]
                        });
                    });

                    it('should work when the move position is before the first subheader in Group1, in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,6,7,8,9,3,4,5,10,11,12,13',
                            sequence: [
                                [8, 2, false],
                                [8, 2, false],
                                [8, 2, false],
                                [8, 2, false]
                            ]
                        });
                    });

                    it('should work when the move position is after the last subheader in Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,6,7,8,9,11,12,13',
                            sequence: [
                                [5, 9, true],
                                [5, 9, true],
                                [5, 9, true],
                                [5, 9, true]
                            ]
                        });
                    });

                    it('should work when the move position is after the last subheader in Group1, in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,9,8,7,6,11,12,13',
                            sequence: [
                                [8, 9, true],
                                [7, 9, true],
                                [6, 9, true],
                                [5, 9, true]
                            ]
                        });
                    });

                    it('should work when the move position is before the subheader directly after Group2', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,9,8,7,6,10,11,12,13',
                            sequence: [
                                [5, 9, false],
                                [5, 8, false],
                                [5, 7, false],
                                [5, 6, false]
                            ]
                        });
                    });

                    it('should work when the move position is before the subheader directly after Group2, in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13',
                            sequence: [
                                [8, 9, false],
                                [7, 8, false],
                                [6, 7, false],
                                [5, 6, false]
                            ]
                        });
                    });
                });

                describe('when the Group2 subheaders are dragged into the root header container', function() {
                    function additionalSpec() {
                        expect(grid.down('[isGroupHeader][text=Group2]')).toBe(null);
                    }

                    // Note that not specifying a groupSubHeader with a range means that the groupHeader ref will
                    // be the first group found, which is Group1 and the one we want.
                    //
                    // Note that also we're testing that Group2 has been removed after the last subheader has been
                    // dragged out.
                    it('should work when the move position is before the first group header (Group1)', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'before',
                            order: '1,2,6,7,8,9,3,4,5,10,11,12,13',
                            range: [5, 8]
                        }, additionalSpec);
                    });

                    it('should work when the move position is before the first group header (Group1), in reverse', function() {
                        // Note that specifying null in a sequence and not specifying a subGroupHeader config will
                        // have the value of groupHeader default to be the first group header found, which is
                        // Group1 and the one we want.
                        runTest({
                            columns: columns,
                            order: '1,2,9,8,7,6,3,4,5,10,11,12,13',
                            sequence: [
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false]
                            ]
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the first group header (Group1)', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,9,8,7,6,11,12,13',
                            sequence: [
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true]
                            ]
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the first group header (Group1), in reverse', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'right',
                            order: '1,2,3,4,5,10,6,7,8,9,11,12,13',
                            range: [8, 5]
                        }, additionalSpec);
                    });

                    it("should work when the move position alternates between 'before' and 'after'", function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,7,9,6,8,10,11,12,13',
                            sequence: [
                                [6, 'subGroupHeader', false],
                                [7, 'subGroupHeader', true],
                                [6, 'subGroupHeader', true],
                                [6, 'subGroupHeader', false]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });
                });
            });

            describe('moving the group header', function() {
                describe('Group1', function() {
                    it('should move the group to the beginning of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '3,4,5,6,7,8,9,10,1,2,11,12,13',
                            sequence: [
                                ['groupHeader', 0, false]
                            ]
                        });
                    });

                    it('should move the group to the beginning of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,3,4,5,6,7,8,9,10,2,11,12,13',
                            sequence: [
                                ['groupHeader', 0, true]
                            ]
                        });
                    });

                    it('should move the group to the end of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,11,12,3,4,5,6,7,8,9,10,13',
                            sequence: [
                                ['groupHeader', 12, false]
                            ]
                        });
                    });

                    it('should move the group to the end of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,11,12,13,3,4,5,6,7,8,9,10',
                            sequence: [
                                ['groupHeader', 12, true]
                            ]
                        });
                    });
                });

                describe('Group2', function() {
                    it('should move the group to the beginning of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '6,7,8,9,1,2,3,4,5,10,11,12,13',
                            sequence: [
                                ['subGroupHeader', 0, false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the beginning of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,6,7,8,9,2,3,4,5,10,11,12,13',
                            sequence: [
                                ['subGroupHeader', 0, true]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the end of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,6,7,8,9,13',
                            sequence: [
                                ['subGroupHeader', 12, false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the end of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 12, true]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the beginning of Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,6,7,8,9,3,4,5,10,11,12,13',
                            sequence: [
                                ['subGroupHeader', 2, false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the end of Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,6,7,8,9,11,12,13',
                            sequence: [
                                ['subGroupHeader', 9, true]
                            ],
                            subGroupHeader: 1
                        });
                    });
                });

                describe('when the nested groups are stacked directly on top of each other', function() {
                    var columns = [{
                        dataIndex: 'field1',
                        header: 'Field1'
                    }, {
                        dataIndex: 'field2',
                        header: 'Field2'
                    }, {
                        header: 'Group1',
                        columns: [{
                            header: 'Group2',
                            columns: [{
                                dataIndex: 'field3',
                                header: 'Field3'
                            }, {
                                dataIndex: 'field4',
                                header: 'Field4'
                            }, {
                                dataIndex: 'field5',
                                header: 'Field5'
                            }, {
                                dataIndex: 'field6',
                                header: 'Field6'
                            }]
                        }]
                    }, {
                        dataIndex: 'field7',
                        header: 'Field7'
                    }, {
                        dataIndex: 'field8',
                        header: 'Field8'
                    }, {
                        dataIndex: 'field9',
                        header: 'Field9'
                    }];

                    function additionalSpec() {
                        // Group1 has been removed.
                        expect(groupHeader.ownerCt).toBe(null);
                        expect(groupHeader.rendered).toBe(false);

                        // Group2 is still around.
                        expect(subGroupHeader.ownerCt).not.toBe(null);
                        expect(subGroupHeader.rendered).toBe(true);
                    }

                    it('should remove the Group1 group header when Group2 is moved out of its grouping', function() {
                        runTest({
                            columns: columns,
                            order: '1,3,4,5,6,2,7,8,9',
                            sequence: [
                                ['subGroupHeader', 1, false]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);

                        runTest({
                            columns: columns,
                            order: '1,2,7,8,3,4,5,6,9',
                            sequence: [
                                ['subGroupHeader', 7, true]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });

                    it('should remove the Group1 group header when Group2 is dragged onto it, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', false]
                            ],
                            groupHeader: 0,
                            subGroupHeader: 1
                        }, additionalSpec);
                    });

                    it('should remove the Group1 group header when Group2 is dragged onto it, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', true]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });
                });

                describe('when the nested groups are aligned on either side', function() {
                    describe('aligned on left', function() {
                        var columns = [{
                            dataIndex: 'field1',
                            header: 'Field1'
                        }, {
                            dataIndex: 'field2',
                            header: 'Field2'
                        }, {
                            header: 'Group1',
                            columns: [{
                                header: 'Group2',
                                columns: [{
                                    dataIndex: 'field3',
                                    header: 'Field3'
                                }, {
                                    dataIndex: 'field4',
                                    header: 'Field4'
                                }, {
                                    dataIndex: 'field5',
                                    header: 'Field5'
                                }]
                            }, {
                                dataIndex: 'field6',
                                header: 'Field6'
                            }]
                        }, {
                            dataIndex: 'field7',
                            header: 'Field7'
                        }, {
                            dataIndex: 'field8',
                            header: 'Field8'
                        }, {
                            dataIndex: 'field9',
                            header: 'Field9'
                        }];

                        it('should work when the subgroupheader is dragged onto its ownerCt, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                subGroupHeader: 1
                            });
                        });

                        it('should work when the subgroupheader is dragged onto its ownerCt, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,6,3,4,5,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                subGroupHeader: 1
                            });
                        });
                    });

                    describe('aligned on right', function() {
                        var columns = [{
                            dataIndex: 'field1',
                            header: 'Field1'
                        }, {
                            dataIndex: 'field2',
                            header: 'Field2'
                        }, {
                            header: 'Group1',
                            columns: [{
                                dataIndex: 'field3',
                                header: 'Field3'
                            }, {
                                header: 'Group2',
                                columns: [{
                                    dataIndex: 'field4',
                                    header: 'Field4'
                                }, {
                                    dataIndex: 'field5',
                                    header: 'Field5'
                                }, {
                                    dataIndex: 'field6',
                                    header: 'Field6'
                                }]
                            }]
                        }, {
                            dataIndex: 'field7',
                            header: 'Field7'
                        }, {
                            dataIndex: 'field8',
                            header: 'Field8'
                        }, {
                            dataIndex: 'field9',
                            header: 'Field9'
                        }];

                        it('should work when the subgroupheader is dragged onto its ownerCt, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,4,5,6,3,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                subGroupHeader: 1
                            });
                        });

                        it('should work when the subgroupheader is dragged onto its ownerCt, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                subGroupHeader: 1
                            });
                        });
                    });
                });
            });

            describe('when the headers are moved randomly', function() {
                it("should work when the move position is 'before'", function() {
                    runTest({
                        columns: columns,
                        order: '6,1,9,2,3,7,4,5,8,10,11,12,13',
                        sequence: [
                            [5, 0, false],
                            [7, 9, false],
                            [6, 4, false],
                            [7, 2, false]
                        ]
                    });
                });

                it("should work when the move position is 'after'", function() {
                    runTest({
                        columns: columns,
                        order: '1,6,2,3,4,8,5,10,11,12,9,13,7',
                        sequence: [
                            [6, 12, true],
                            [6, 3, true],
                            [7, 10, true],
                            [6, 0, true]
                        ]
                    });
                });

                it("should work when the move position alternates between 'before' and 'after'", function() {
                    runTest({
                        columns: columns,
                        order: '1,2,9,3,6,4,7,5,8,10,11,12,13',
                        sequence: [
                            [6, 4, false],
                            [7, 9, false],
                            [7, 1, true],
                            [7, 3, true]
                        ]
                    });
                });
            });
        });

        describe('three nested groups', function() {
            var columns = [{
                dataIndex: 'field1',
                header: 'Field1'
            }, {
                dataIndex: 'field2',
                header: 'Field2'
            }, {
                header: 'Group1',
                columns: [{
                    dataIndex: 'field3',
                    header: 'Field3'
                }, {
                    dataIndex: 'field4',
                    header: 'Field4'
                }, {
                    dataIndex: 'field5',
                    header: 'Field5'
                }, {
                    header: 'Group2',
                    columns: [{
                        header: 'Group3',
                        columns: [{
                            dataIndex: 'field6',
                            header: 'Field6'
                        }, {
                            dataIndex: 'field7',
                            header: 'Field7'
                        }, {
                            dataIndex: 'field8',
                            header: 'Field8'
                        }, {
                            dataIndex: 'field9',
                            header: 'Field9'
                        }]
                    }, {
                        dataIndex: 'field10',
                        header: 'Field10'
                    }, {
                        dataIndex: 'field11',
                        header: 'Field11'
                    }, {
                        dataIndex: 'field12',
                        header: 'Field12'
                    }]
                }, {
                    dataIndex: 'field13',
                    header: 'Field13'
                }]
            }, {
                dataIndex: 'field14',
                header: 'Field14'
            }, {
                dataIndex: 'field15',
                header: 'Field15'
            }, {
                dataIndex: 'field16',
                header: 'Field16'
            }];

            describe('dragging all subheaders out of Group3', function() {
                describe('when the targetHeader is the Group3 groupHeader (so the drag is contiguous to Group3)', function() {
                    function additionalSpec() {
                        expect(subGroupHeader.ownerCt).toBe(null);
                        expect(subGroupHeader.rendered).toBe(false);
                    }

                    it('should work when the move position is before the target header', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'before',
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16',
                            range: [5, 8],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is before the target header, in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,9,8,7,6,10,11,12,13,14,15,16',
                            sequence: [
                                [8, 'subGroupHeader', false],
                                [8, 'subGroupHeader', false],
                                [8, 'subGroupHeader', false],
                                [8, 'subGroupHeader', false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the target header', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'right',
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16',
                            range: [8, 5],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the target header, in reverse', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'right',
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16',
                            range: [8, 5],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it("should work when the move position alternates between 'before' and 'after'", function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,7,9,6,8,10,11,12,13,14,15,16',
                            sequence: [
                                [6, 'subGroupHeader', false],
                                [7, 'subGroupHeader', true],
                                [6, 'subGroupHeader', true],
                                [6, 'subGroupHeader', false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });
                });

                describe('when the Group3 subheaders are dragged into Group2 (targetHeader is not Group3)', function() {
                    function additionalSpec() {
                        expect(subGroupHeader.ownerCt).toBe(null);
                        expect(subGroupHeader.rendered).toBe(false);
                    }

                    it('should work when the move position is after the last subheader in Group2', function() {
                        // Even though we're not using the subGroupHeader in the move sequence, we specify it b/c
                        // we're referencing it in the additionalSpec.
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,6,7,8,9,13,14,15,16',
                            sequence: [
                                [5, 11, true],
                                [5, 11, true],
                                [5, 11, true],
                                [5, 11, true]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is before the subheader directly after Group2', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,9,8,7,6,13,14,15,16',
                            sequence: [
                                [5, 12, false],
                                [5, 11, false],
                                [5, 10, false],
                                [5, 9, false]
                            ],
                            subGroupHeader: 2
                        });
                    });
                });

                describe('when the Group3 subheaders are dragged into Group1', function() {
                    function additionalSpec() {
                        expect(subGroupHeader.ownerCt).toBe(null);
                        expect(subGroupHeader.rendered).toBe(false);
                    }

                    it('should work when the move position is after the last subheader in Group1', function() {
                        // Even though we're not using the subGroupHeader in the move sequence, we specify it b/c
                        // we're referencing it in the additionalSpec.
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,6,7,8,9,14,15,16',
                            sequence: [
                                [5, 12, true],
                                [5, 12, true],
                                [5, 12, true],
                                [5, 12, true]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the last subheader in Group1, in reverse', function() {
                        runTest({
                            columns: columns,
                            dropPosition: 'after',
                            order: '1,2,3,4,5,10,11,12,13,6,7,8,9,14,15,16',
                            range: [8, 5]
                        });
                    });

                    it('should work when the move position is before the subheader directly after Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,9,8,7,6,13,14,15,16',
                            sequence: [
                                [5, 12, false],
                                [5, 11, false],
                                [5, 10, false],
                                [5, 9, false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is before the subheader directly after Group1, in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,6,7,8,9,13,14,15,16',
                            sequence: [
                                [8, 12, false],
                                [7, 11, false],
                                [6, 10, false],
                                [5, 9, false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the targetHeader is Group1 and the move position is before', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,9,8,7,6,3,4,5,10,11,12,13,14,15,16',
                            sequence: [
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the targetHeader is Group1 and the move position is after', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,9,8,7,6,14,15,16',
                            sequence: [
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });
                });

                describe('when the Group3 subheaders are dragged into the root header container', function() {
                    // Note that not specifying a groupSubHeader with a range means that the groupHeader ref will
                    // be the first group found, which is Group1 and the one we want.
                    //
                    // Note that also we're testing that Group2 has been removed after the last subheader has been
                    // dragged out.
                    function additionalSpec() {
                        expect(subGroupHeader.rendered).toBe(false);
                        expect(subGroupHeader.ownerCt).toBe(null);
                    }

                    it('should work when the move position is before the first group header (Group1)', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,6,7,8,9,3,4,5,10,11,12,13,14,15,16',
                            sequence: [
                                [5, 'groupHeader', false],
                                [6, 'groupHeader', false],
                                [7, 'groupHeader', false],
                                [8, 'groupHeader', false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is before the first group header (Group1), in reverse', function() {
                        // Note that specifying null in a sequence and not specifying a subGroupHeader config will
                        // have the value of groupHeader default to be the first group header found, which is
                        // Group1 and the one we want.
                        runTest({
                            columns: columns,
                            order: '1,2,9,8,7,6,3,4,5,10,11,12,13,14,15,16',
                            sequence: [
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false],
                                [8, 'groupHeader', false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the first group header (Group1)', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,9,8,7,6,14,15,16',
                            sequence: [
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true],
                                [5, 'groupHeader', true]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it('should work when the move position is after the first group header (Group1), in reverse', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,6,7,8,9,14,15,16',
                            sequence: [
                                [8, 'groupHeader', true],
                                [7, 'groupHeader', true],
                                [6, 'groupHeader', true],
                                [5, 'groupHeader', true]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });

                    it("should work when the move position alternates between 'before' and 'after'", function() {
                        runTest({
                            columns: columns,
                            order: '1,2,7,9,3,4,5,10,11,12,13,6,8,14,15,16',
                            sequence: [
                                [6, 'groupHeader', false],
                                [7, 'groupHeader', true],
                                [6, 'groupHeader', true],
                                [6, 'groupHeader', false]
                            ],
                            subGroupHeader: 2
                        }, additionalSpec);
                    });
                });
            });

            describe('moving the group header', function() {
                describe('Group1', function() {
                    it('should move the group to the beginning of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '3,4,5,6,7,8,9,10,11,12,13,1,2,14,15,16',
                            sequence: [
                                ['groupHeader', 0, false]
                            ]
                        });
                    });

                    it('should move the group to the beginning of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,3,4,5,6,7,8,9,10,11,12,13,2,14,15,16',
                            sequence: [
                                ['groupHeader', 0, true]
                            ]
                        });
                    });

                    it('should move the group to the end of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,14,15,3,4,5,6,7,8,9,10,11,12,13,16',
                            sequence: [
                                ['groupHeader', 15, false]
                            ]
                        });
                    });

                    it('should move the group to the end of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,14,15,16,3,4,5,6,7,8,9,10,11,12,13',
                            sequence: [
                                ['groupHeader', 15, true]
                            ]
                        });
                    });
                });

                describe('Group2', function() {
                    it('should move the group to the beginning of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '6,7,8,9,10,11,12,1,2,3,4,5,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 0, false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the beginning of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,6,7,8,9,10,11,12,2,3,4,5,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 0, true]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the end of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,13,14,15,6,7,8,9,10,11,12,16',
                            sequence: [
                                ['subGroupHeader', 15, false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the end of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,13,14,15,16,6,7,8,9,10,11,12',
                            sequence: [
                                ['subGroupHeader', 15, true]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the beginning of Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,6,7,8,9,10,11,12,3,4,5,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 2, false]
                            ],
                            subGroupHeader: 1
                        });
                    });

                    it('should move the group to the end of Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,13,6,7,8,9,10,11,12,14,15,16',
                            sequence: [
                                ['subGroupHeader', 12, true]
                            ],
                            subGroupHeader: 1
                        });
                    });
                });

                describe('Group3', function() {
                    it('should move the group to the beginning of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '6,7,8,9,1,2,3,4,5,10,11,12,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 0, false]
                            ],
                            subGroupHeader: 2
                        });
                    });

                    it('should move the group to the beginning of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,6,7,8,9,2,3,4,5,10,11,12,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 0, true]
                            ],
                            subGroupHeader: 2
                        });
                    });

                    it('should move the group to the end of the root header container, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,14,15,6,7,8,9,16',
                            sequence: [
                                ['subGroupHeader', 15, false]
                            ],
                            subGroupHeader: 2
                        });
                    });

                    it('should move the group to the end of the root header container, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,14,15,16,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 15, true]
                            ],
                            subGroupHeader: 2
                        });
                    });

                    it('should move the group to the beginning of Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,6,7,8,9,3,4,5,10,11,12,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 2, false]
                            ],
                            subGroupHeader: 2
                        });
                    });

                    it('should move the group to the end of Group1', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,13,6,7,8,9,14,15,16',
                            sequence: [
                                ['subGroupHeader', 12, true]
                            ],
                            subGroupHeader: 2
                        });
                    });

                    it('should move the group to the beginning of Group2', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', false]
                            ],
                            groupHeader: 1,
                            subGroupHeader: 2
                        });
                    });

                    it('should move the group to the end of Group2', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,10,11,12,6,7,8,9,13,14,15,16',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', true]
                            ],
                            groupHeader: 1,
                            subGroupHeader: 2
                        });
                    });
                });

                describe('when the nested groups are stacked directly on top of each other', function() {
                    var columns = [{
                        dataIndex: 'field1',
                        header: 'Field1'
                    }, {
                        dataIndex: 'field2',
                        header: 'Field2'
                    }, {
                        header: 'Group1',
                        columns: [{
                            header: 'Group2',
                            columns: [{
                                header: 'Group3',
                                columns: [{
                                    dataIndex: 'field3',
                                    header: 'Field3'
                                }, {
                                    dataIndex: 'field4',
                                    header: 'Field4'
                                }, {
                                    dataIndex: 'field5',
                                    header: 'Field5'
                                }, {
                                    dataIndex: 'field6',
                                    header: 'Field6'
                                }]
                            }]
                        }]
                    }, {
                        dataIndex: 'field7',
                        header: 'Field7'
                    }, {
                        dataIndex: 'field8',
                        header: 'Field8'
                    }, {
                        dataIndex: 'field9',
                        header: 'Field9'
                    }];

                    function additionalSpec() {
                        // Group1 has been removed.
                        expect(groupHeader.ownerCt).toBe(null);
                        expect(groupHeader.rendered).toBe(false);

                        // Group2 is still around.
                        expect(subGroupHeader.ownerCt).not.toBe(null);
                        expect(subGroupHeader.rendered).toBe(true);
                    }

                    function nestedSpec() {
                        expect(groupHeader.ownerCt).toBe(null);
                        expect(groupHeader.rendered).toBe(false);
                        expect(headerCt.down('[text=Group1]')).toBe(null);
                    }

                    it('should remove the Group1 group header when Group2 is moved out of its grouping', function() {
                        runTest({
                            columns: columns,
                            order: '1,3,4,5,6,2,7,8,9',
                            sequence: [
                                ['subGroupHeader', 1, false]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);

                        runTest({
                            columns: columns,
                            order: '1,2,7,8,3,4,5,6,9',
                            sequence: [
                                ['subGroupHeader', 7, true]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });

                    it('should remove both the Group1 and Group2 group headers when Group3 is moved out of its grouping', function() {
                        runTest({
                            columns: columns,
                            order: '1,3,4,5,6,2,7,8,9',
                            sequence: [
                                ['subGroupHeader', 1, false]
                            ],
                            subGroupHeader: 2
                        }, nestedSpec);

                        runTest({
                            columns: columns,
                            order: '1,2,7,8,3,4,5,6,9',
                            sequence: [
                                ['subGroupHeader', 7, true]
                            ],
                            subGroupHeader: 2
                        }, nestedSpec);
                    });

                    it('should remove the Group1 group header when Group2 is dragged onto it, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', false]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });

                    it('should remove the Group1 group header when Group2 is dragged onto it, after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', true]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });

                    it('should remove both the Group1 and Group2 group headers when Group3 is dragged onto Group1, before position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', false]
                            ],
                            subGroupHeader: 2
                        }, nestedSpec);
                    });

                    it('should remove both the Group1 and Group2 group headers when Group3 is dragged onto Group1 , after position', function() {
                        runTest({
                            columns: columns,
                            order: '1,2,3,4,5,6,7,8,9',
                            sequence: [
                                ['subGroupHeader', 'groupHeader', true]
                            ],
                            subGroupHeader: 2
                        }, nestedSpec);
                    });
                });

                describe('when the nested groups are aligned on either side', function() {
                    describe('aligned on left', function() {
                        var columns = [{
                            dataIndex: 'field1',
                            header: 'Field1'
                        }, {
                            dataIndex: 'field2',
                            header: 'Field2'
                        }, {
                            header: 'Group1',
                            columns: [{
                                header: 'Group2',
                                columns: [{
                                    header: 'Group3',
                                    columns: [{
                                        dataIndex: 'field3',
                                        header: 'Field3'
                                    }, {
                                        dataIndex: 'field4',
                                        header: 'Field4'
                                    }, {
                                        dataIndex: 'field5',
                                        header: 'Field5'
                                    }]
                                }]
                            }, {
                                dataIndex: 'field6',
                                header: 'Field6'
                            }]
                        }, {
                            dataIndex: 'field7',
                            header: 'Field7'
                        }, {
                            dataIndex: 'field8',
                            header: 'Field8'
                        }, {
                            dataIndex: 'field9',
                            header: 'Field9'
                        }];

                        describe('Group2 and Group3 are aligned left with a header to the right', function() {
                            //           +-----------------------------------+
                            //           |               Group 1             |
                            //           |-----------------------------------|
                            //           |          Group2          |        |
                            //   other   |--------------------------|        |   other
                            //  headers  |          Group3          | Field6 |  headers
                            //           |--------------------------|        |
                            //           | Field3 | Field4 | Field5 |        |
                            //           |===================================|
                            //           |               view                |
                            //           +-----------------------------------+

                            function test1() {
                                // Expect that Group2 has been removed and that Group3 is still a child of Group1.
                                expect(groupHeader.ownerCt).toBe(null);
                                expect(groupHeader.rendered).toBe(false);
                                expect(subGroupHeader.ownerCt).toBe(headerCt.down('[text=Group1]'));
                            }

                            function test2() {
                                // Expect that Group2 has been removed and that Group3 is a sibling of Group1.
                                expect(headerCt.down('[text=Group2]')).toBe(null);
                                expect(subGroupHeader.ownerCt).toBe(groupHeader.ownerCt);
                            }

                            function test3() {
                                // Expect that Group2 is still the parent of Group3 and that Group2 and Group1 are siblings.
                                expect(headerCt.down('[text=Group3]').ownerCt).toBe(subGroupHeader);
                                expect(subGroupHeader.ownerCt).toBe(groupHeader.ownerCt);
                            }

                            it('should work when Group3 is dragged onto Group2, before position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', false]
                                    ],
                                    groupHeader: 1,
                                    subGroupHeader: 2
                                }, test1);
                            });

                            it('should work when Group3 is dragged onto Group2, after position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', true]
                                    ],
                                    groupHeader: 1,
                                    subGroupHeader: 2
                                }, test1);
                            });

                            it('should work when Group3 is dragged onto Group1, before position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', false]
                                    ],
                                    subGroupHeader: 2
                                }, test2);
                            });

                            it('should work when Group3 is dragged onto Group1, after position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,6,3,4,5,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', true]
                                    ],
                                    subGroupHeader: 2
                                }, test2);
                            });

                            it('should work when Group2 is dragged onto Group1, before position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', false]
                                    ],
                                    subGroupHeader: 1
                                }, test3);
                            });

                            it('should work when Group2 is dragged onto Group1, after position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,6,3,4,5,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', true]
                                    ],
                                    subGroupHeader: 1
                                }, test3);
                            });
                        });
                    });

                    describe('aligned on right', function() {
                        var columns = [{
                            dataIndex: 'field1',
                            header: 'Field1'
                        }, {
                            dataIndex: 'field2',
                            header: 'Field2'
                        }, {
                            header: 'Group1',
                            columns: [{
                                dataIndex: 'field3',
                                header: 'Field3'
                            }, {
                                header: 'Group2',
                                columns: [{
                                    header: 'Group3',
                                    columns: [{
                                        dataIndex: 'field4',
                                        header: 'Field4'
                                    }, {
                                        dataIndex: 'field5',
                                        header: 'Field5'
                                    }, {
                                        dataIndex: 'field6',
                                        header: 'Field6'
                                    }]
                                }]
                            }]
                        }, {
                            dataIndex: 'field7',
                            header: 'Field7'
                        }, {
                            dataIndex: 'field8',
                            header: 'Field8'
                        }, {
                            dataIndex: 'field9',
                            header: 'Field9'
                        }];

                        describe('Group2 and Group3 are aligned right with a header to the left', function() {
                            //           +-----------------------------------+
                            //           |               Group 1             |
                            //           |-----------------------------------|
                            //           |        |          Group2          |
                            //   other   |        |--------------------------|   other
                            //  headers  | Field3 |          Group3          |  headers
                            //           |        |--------------------------|
                            //           |        | Field4 | Field5 | Field6 |
                            //           |===================================|
                            //           |               view                |
                            //           +-----------------------------------+

                            function test1() {
                                // Expect that Group2 has been removed and that Group3 is still a child of Group1.
                                expect(groupHeader.ownerCt).toBe(null);
                                expect(groupHeader.rendered).toBe(false);
                                expect(subGroupHeader.ownerCt).toBe(headerCt.down('[text=Group1]'));
                            }

                            function test2() {
                                // Expect that Group2 has been removed and that Group3 is a sibling of Group1.
                                expect(headerCt.down('[text=Group2]')).toBe(null);
                                expect(subGroupHeader.ownerCt).toBe(groupHeader.ownerCt);
                            }

                            function test3() {
                                // Expect that Group2 is still the parent of Group3 and that Group2 and Group1 are siblings.
                                expect(headerCt.down('[text=Group3]').ownerCt).toBe(subGroupHeader);
                                expect(subGroupHeader.ownerCt).toBe(groupHeader.ownerCt);
                            }

                            it('should work when Group3 is dragged onto Group2, before position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', false]
                                    ],
                                    groupHeader: 1,
                                    subGroupHeader: 2
                                }, test1);
                            });

                            it('should work when Group3 is dragged onto Group2, after position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', true]
                                    ],
                                    groupHeader: 1,
                                    subGroupHeader: 2
                                }, test1);
                            });

                            it('should work when Group3 is dragged onto Group1, before position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,4,5,6,3,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', false]
                                    ],
                                    subGroupHeader: 2
                                }, test2);
                            });

                            it('should work when Group3 is dragged onto Group1, after position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', true]
                                    ],
                                    subGroupHeader: 2
                                }, test2);
                            });

                            it('should work when Group2 is dragged onto Group1, before position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,4,5,6,3,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', false]
                                    ],
                                    subGroupHeader: 1
                                }, test3);
                            });

                            it('should work when Group2 is dragged onto Group1, after position', function() {
                                runTest({
                                    columns: columns,
                                    order: '1,2,3,4,5,6,7,8,9',
                                    sequence: [
                                        ['subGroupHeader', 'groupHeader', true]
                                    ],
                                    subGroupHeader: 1
                                }, test3);
                            });
                        });
                    });
                });
            });
        });

        describe('four nested groups', function() {
            describe('when the nested groups are stacked directly on top of each other', function() {
                var columns = [{
                    dataIndex: 'field1',
                    header: 'Field1'
                }, {
                    dataIndex: 'field2',
                    header: 'Field2'
                }, {
                    header: 'Group1',
                    columns: [{
                        header: 'Group2',
                        columns: [{
                            header: 'Group3',
                            columns: [{
                                header: 'Group4',
                                columns: [{
                                    dataIndex: 'field3',
                                    header: 'Field3'
                                }, {
                                    dataIndex: 'field4',
                                    header: 'Field4'
                                }, {
                                    dataIndex: 'field5',
                                    header: 'Field5'
                                }, {
                                    dataIndex: 'field6',
                                    header: 'Field6'
                                }]
                            }]
                        }]
                    }]
                }, {
                    dataIndex: 'field7',
                    header: 'Field7'
                }, {
                    dataIndex: 'field8',
                    header: 'Field8'
                }, {
                    dataIndex: 'field9',
                    header: 'Field9'
                }];

                function additionalSpec() {
                    // Group1 has been removed.
                    expect(groupHeader.ownerCt).toBe(null);
                    expect(groupHeader.rendered).toBe(false);

                    // All the other groups are still around.
                    expect(subGroupHeader.rendered).toBe(true);
                    expect(headerCt.down('[text=Group3]').rendered).toBe(true);
                    expect(headerCt.down('[text=Group4]').rendered).toBe(true);
                }

                function nestedSpec() {
                    // Groups 1 and 2 have been removed.
                    expect(groupHeader.ownerCt).toBe(null);
                    expect(groupHeader.rendered).toBe(false);
                    expect(headerCt.down('[text=Group2]')).toBe(null);

                    // All the other groups are still around.
                    expect(headerCt.down('[text=Group3]').rendered).toBe(true);
                    expect(headerCt.down('[text=Group4]').rendered).toBe(true);
                }

                function nestedSpec2() {
                    // Groups 1, 2 and 3 have been removed.
                    expect(groupHeader.ownerCt).toBe(null);
                    expect(groupHeader.rendered).toBe(false);
                    expect(headerCt.down('[text=Group2]')).toBe(null);
                    expect(headerCt.down('[text=Group3]')).toBe(null);

                    expect(headerCt.down('[text=Group4]').rendered).toBe(true);
                }

                function nestedSpec3() {
                    expect(groupHeader.ownerCt).toBe(null);
                    expect(groupHeader.rendered).toBe(false);
                    expect(headerCt.down('[text=Group2]')).toBe(null);
                    expect(headerCt.down('[text=Group3]').rendered).toBe(true);

                    expect(headerCt.down('[text=Group4]').rendered).toBe(true);
                }

                describe('when its moved out of its stacked grouping', function() {
                    it('should remove the Group1 group header when Group2 is moved out of its grouping', function() {
                        runTest({
                            columns: columns,
                            order: '3,4,5,6,1,2,7,8,9',
                            sequence: [
                                ['subGroupHeader', 0, false]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);

                        runTest({
                            columns: columns,
                            order: '1,2,7,8,3,4,5,6,9',
                            sequence: [
                                ['subGroupHeader', 7, true]
                            ],
                            subGroupHeader: 1
                        }, additionalSpec);
                    });

                    it('should remove both the Group1 and Group2 group headers when Group3 is moved out of its grouping', function() {
                        runTest({
                            columns: columns,
                            order: '1,3,4,5,6,2,7,8,9',
                            sequence: [
                                ['subGroupHeader', 0, true]
                            ],
                            subGroupHeader: 2
                        }, nestedSpec);

                        runTest({
                            columns: columns,
                            order: '1,2,7,3,4,5,6,8,9',
                            sequence: [
                                ['subGroupHeader', 7, false]
                            ],
                            subGroupHeader: 2
                        }, nestedSpec);
                    });

                    it('should remove the Group1, Group2 and Group3 group headers when Group4 is moved out of its grouping', function() {
                        runTest({
                            columns: columns,
                            order: '1,3,4,5,6,2,7,8,9',
                            sequence: [
                                ['subGroupHeader', 1, false]
                            ],
                            subGroupHeader: 3
                        }, nestedSpec2);

                        runTest({
                            columns: columns,
                            order: '1,2,7,3,4,5,6,8,9',
                            sequence: [
                                ['subGroupHeader', 6, true]
                            ],
                            subGroupHeader: 3
                        }, nestedSpec2);
                    });
                });

                describe('when the targetHeader is an ancestor group within the stacked grouping', function() {
                    describe('when Group1 is the targetHeader', function() {
                        it('should remove the Group1 group header when Group2 is dragged onto it, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                subGroupHeader: 1
                            }, additionalSpec);
                        });

                        it('should remove the Group1 group header when Group2 is dragged onto it, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                subGroupHeader: 1
                            }, additionalSpec);
                        });

                        it('should remove both the Group1 and Group2 group headers when Group3 is dragged onto it, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                subGroupHeader: 2
                            }, nestedSpec);
                        });

                        it('should remove both the Group1 and Group2 group headers when Group3 is dragged onto it, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                subGroupHeader: 2
                            }, nestedSpec);
                        });

                        it('should remove the Group1, Group2 and Group3 group headers when Group4 is dragged onto it, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                subGroupHeader: 3
                            }, nestedSpec2);
                        });

                        it('should remove the Group1, Group2 and Group3 group headers when Group4 is dragged onto it, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                subGroupHeader: 3
                            }, nestedSpec2);
                        });
                    });

                    describe('when Group2 is the targetHeader', function() {
                        it('should remove the Group2 group header when Group 3 is dragged onto it, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                groupHeader: 1,
                                subGroupHeader: 2
                            }, additionalSpec);
                        });

                        it('should remove the Group2 group header when Group 3 is dragged onto it, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                groupHeader: 1,
                                subGroupHeader: 2
                            }, additionalSpec);
                        });

                        it('should remove the Group2 and Group3 group headers when Group4 is dragged onto it, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                groupHeader: 1,
                                subGroupHeader: 3
                            }, nestedSpec2);
                        });

                        it('should remove the Group1, Group2 and Group3 group headers when Group4 is dragged onto it, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                groupHeader: 1,
                                subGroupHeader: 3
                            }, nestedSpec2);
                        });
                    });

                    describe('when Group3 is the targetHeader', function() {
                        function additionalSpec() {
                            expect(groupHeader.rendered).toBe(false);
                            expect(subGroupHeader.rendered).toBe(true);
                            expect(headerCt.down('[text=Group1]').rendered).toBe(true);
                            expect(headerCt.down('[text=Group2]').rendered).toBe(true);
                        }

                        it('should remove the Group3 group header when Group4 is dragged onto it, before position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', false]
                                ],
                                groupHeader: 2,
                                subGroupHeader: 3
                            }, additionalSpec);
                        });

                        it('should remove the Group3 group header when Group4 is dragged onto it, after position', function() {
                            runTest({
                                columns: columns,
                                order: '1,2,3,4,5,6,7,8,9',
                                sequence: [
                                    ['subGroupHeader', 'groupHeader', true]
                                ],
                                groupHeader: 2,
                                subGroupHeader: 3
                            }, additionalSpec);
                        });
                    });
                });
            });
        });

        describe('locked grids', function() {
            describe('one nested group', function() {
                var columns = [{
                    dataIndex: 'field1',
                    header: 'Field1',
                    locked: true
                }, {
                    dataIndex: 'field2',
                    header: 'Field2',
                    locked: true
                }, {
                    header: 'Group1',
                    locked: true,
                    columns: [{
                        dataIndex: 'field3',
                        header: 'Field3'
                    }, {
                        dataIndex: 'field4',
                        header: 'Field4'
                    }, {
                        dataIndex: 'field5',
                        header: 'Field5'
                    }, {
                        dataIndex: 'field6',
                        header: 'Field6'
                    }]
                }, {
                    dataIndex: 'field7',
                    header: 'Field7',
                    locked: true
                }, {
                    dataIndex: 'field8',
                    header: 'Field8',
                    locked: true
                }, {
                    dataIndex: 'field9',
                    header: 'Field9'
                }, {
                    header: 'Group2',
                    columns: [{
                        dataIndex: 'field10',
                        header: 'Field10'
                    }, {
                        dataIndex: 'field11',
                        header: 'Field11'
                    }, {
                        dataIndex: 'field12',
                        header: 'Field12'
                    }, {
                        dataIndex: 'field13',
                        header: 'Field13'
                    }]
                }, {
                    dataIndex: 'field14',
                    header: 'Field14'
                }, {
                    dataIndex: 'field15',
                    header: 'Field15'
                }];

                // Note to get a group other than the first one, use the groupHeader cfg. This will always do:
                //
                //      grid.headerCt.query('[isGroupHeader]')[groupHeader];
                //
                describe('moving the group from one locked side to another', function() {
                    it('should work moving from locked to normal', function() {
                        // Note you don't have to specify a groupHeader here since it will default to the first one.
                        runTest({
                            columns: columns,
                            locked: true,
                            order: '1,2,7,8,9,10,11,12,13,3,4,5,6,14,15',
                            sequence: [
                                ['groupHeader', 13, false]
                            ]
                        });
                    });

                    it('should work moving from normal to locked', function() {
                        runTest({
                            columns: columns,
                            locked: true,
                            order: '1,10,11,12,13,2,3,4,5,6,7,8,9,14,15',
                            groupHeader: 1,
                            sequence: [
                                ['groupHeader', 0, true]
                            ]
                        });
                    });
                });

                describe('moving the group from one locked side to another into another group', function() {
                    describe('moving from locked to normal', function() {
                        function additionalSpec() {
                            // TODO: better to use refs here if possible.
                            // Group2 should be the owner of Group1.
                            expect(!!headerCt.down('[text=Group2]').down('[text=Group1]')).toBe(true);
                        }

                        it('should work moving before the first nested header in the target group', function() {
                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,7,8,9,3,4,5,6,10,11,12,13,14,15',
                                sequence: [
                                    ['groupHeader', 9, false]
                                ]
                            });
                        });

                        it('should work moving after the last nested header in the target group', function() {
                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,7,8,9,10,11,12,13,3,4,5,6,14,15',
                                sequence: [
                                    ['groupHeader', 12, true]
                                ]
                            });
                        });

                        it('should work moving into the middle of the target group', function() {
                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,7,8,9,10,11,3,4,5,6,12,13,14,15',
                                sequence: [
                                    ['groupHeader', 10, true]
                                ]
                            }, additionalSpec);

                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,7,8,9,10,11,12,3,4,5,6,13,14,15',
                                sequence: [
                                    ['groupHeader', 12, false]
                                ]
                            }, additionalSpec);
                        });
                    });

                    describe('moving from normal to locked', function() {
                        function additionalSpec() {
                            // TODO: better to use refs here if possible.
                            // Group1 should be the owner of Group2.
                            expect(!!headerCt.down('[text=Group1]').down('[text=Group2]')).toBe(true);
                        }

                        it('should work moving before the first nested header in the target group', function() {
                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,10,11,12,13,3,4,5,6,7,8,9,14,15',
                                sequence: [
                                    ['subGroupHeader', 2, false]
                                ],
                                subGroupHeader: 1
                            });
                        });

                        it('should work moving after the last nested header in the target group', function() {
                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,3,4,5,6,10,11,12,13,7,8,9,14,15',
                                sequence: [
                                    ['subGroupHeader', 5, true]
                                ],
                                subGroupHeader: 1
                            });
                        });

                        it('should work moving into the middle of the target group', function() {
                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,3,4,10,11,12,13,5,6,7,8,9,14,15',
                                sequence: [
                                    ['subGroupHeader', 3, true]
                                ],
                                subGroupHeader: 1
                            }, additionalSpec);

                            runTest({
                                columns: columns,
                                locked: true,
                                order: '1,2,3,4,10,11,12,13,5,6,7,8,9,14,15',
                                sequence: [
                                    ['subGroupHeader', 4, false]
                                ],
                                subGroupHeader: 1
                            }, additionalSpec);
                        });
                    });
                });
            });

            describe('two nested groups', function() {
                var columns = [{
                    dataIndex: 'field1',
                    header: 'Field1',
                    locked: true
                }, {
                    dataIndex: 'field2',
                    header: 'Field2',
                    locked: true
                }, {
                    header: 'Group1',
                    locked: true,
                    columns: [{
                        dataIndex: 'field3',
                        header: 'Field3'
                    }, {
                        header: 'Group3',
                        columns: [{
                            dataIndex: 'field4',
                            header: 'Field4'
                        }, {
                            dataIndex: 'field5',
                            header: 'Field5'
                        }, {
                            dataIndex: 'field6',
                            header: 'Field6'
                        }]
                    }, {
                        dataIndex: 'field7',
                        header: 'Field7'
                    }, {
                        dataIndex: 'field8',
                        header: 'Field8'
                    }]
                }, {
                    dataIndex: 'field9',
                    header: 'Field9',
                    locked: true
                }, {
                    dataIndex: 'field10',
                    header: 'Field10',
                    locked: true
                }, {
                    dataIndex: 'field11',
                    header: 'Field11'
                }, {
                    header: 'Group2',
                    columns: [{
                        header: 'Group4',
                        columns: [{
                            dataIndex: 'field12',
                            header: 'Field12'
                        }, {
                            dataIndex: 'field13',
                            header: 'Field13'
                        }]
                    }, {
                        dataIndex: 'field14',
                        header: 'Field14'
                    }, {
                        dataIndex: 'field15',
                        header: 'Field15'
                    }, {
                        dataIndex: 'field16',
                        header: 'Field16'
                    }]
                }, {
                    dataIndex: 'field17',
                    header: 'Field17'
                }, {
                    dataIndex: 'field18',
                    header: 'Field18'
                }];

                // Note to get a group other than the first one, use the groupHeader cfg. This will always do:
                //
                //      grid.headerCt.query('[isGroupHeader]')[groupHeader];
                //
                describe('moving the group from one locked side to another', function() {
                    it('should work moving from locked to normal, Group1 (1st nested)', function() {
                        runTest({
                            columns: columns,
                            locked: true,
                            order: '1,2,9,10,11,12,13,14,15,16,3,4,5,6,7,8,17,18',
                            sequence: [
                                ['groupHeader', 16, false]
                            ]
                        });
                    });

                    it('should work moving from locked to normal, Group3 (2nd nested)', function() {
                        runTest({
                            columns: columns,
                            groupHeader: 1,
                            locked: true,
                            order: '1,2,3,7,8,9,10,11,12,13,14,15,16,17,18,4,5,6',
                            sequence: [
                                ['groupHeader', 17, true]
                            ]
                        }, function() {
                            // Check Group3 has indeed been moved out of its nesting.
                            expect(headerCt.down('[text=Group3]').ownerCt).not.toBe(headerCt.down('[text=Group1]'));
                        });
                    });

                    it('should work moving from normal to locked, Group1 (1st nested)', function() {
                        runTest({
                            columns: columns,
                            groupHeader: 2,
                            locked: true,
                            order: '1,12,13,14,15,16,2,3,4,5,6,7,8,9,10,11,17,18',
                            sequence: [
                                ['groupHeader', 1, false]
                            ]
                        });
                    });

                    it('should work moving from normal to locked, Group4 (2nd nested)', function() {
                        runTest({
                            columns: columns,
                            groupHeader: 3,
                            locked: true,
                            order: '1,2,3,4,5,6,7,8,9,10,12,13,11,14,15,16,17,18',
                            sequence: [
                                ['groupHeader', 9, true]
                            ]
                        }, function() {
                            // Check Group4 has indeed been moved out of its nesting.
                            expect(headerCt.down('[text=Group4]').ownerCt).not.toBe(headerCt.down('[text=Group2]'));
                        });
                    });
                });

                describe('moving the group from one locked side to another into another group', function() {
                    describe('moving from locked to normal', function() {
                        describe('Group1', function() {
                            describe('moving into Group2', function() {
                                function additionalSpec() {
                                    // Group2 should be the owner of Group1.
                                    expect(!!headerCt.down('[text=Group2]').down('[text=Group1]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,3,4,5,6,7,8,12,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 'subGroupHeader', false]
                                        ],
                                        subGroupHeader: 3
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,12,13,14,15,16,3,4,5,6,7,8,17,18',
                                        sequence: [
                                            ['groupHeader', 15, true]
                                        ]
                                    });
                                }, additionalSpec);

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,12,13,14,15,3,4,5,6,7,8,16,17,18',
                                        sequence: [
                                            ['groupHeader', 14, true]
                                        ]
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,12,13,3,4,5,6,7,8,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 13, false]
                                        ]
                                    }, additionalSpec);
                                });
                            });

                            describe('moving into Group4', function() {
                                function additionalSpec() {
                                    expect(!!headerCt.down('[text=Group2]').down('[text=Group4]').down('[text=Group1]').down('[text=Group3]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,3,4,5,6,7,8,12,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 11, false]
                                        ]
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,12,13,3,4,5,6,7,8,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 12, true]
                                        ]
                                    }, additionalSpec);
                                });

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,12,3,4,5,6,7,8,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 11, true]
                                        ]
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,9,10,11,12,3,4,5,6,7,8,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 12, false]
                                        ]
                                    }, additionalSpec);
                                });
                            });
                        });

                        describe('Group3', function() {
                            describe('moving into Group2', function() {
                                function additionalSpec() {
                                    expect(!!headerCt.down('[text=Group2]').down('[text=Group3]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,4,5,6,12,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 'subGroupHeader', false]
                                        ],
                                        groupHeader: 1,
                                        subGroupHeader: 3
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,12,13,14,15,16,4,5,6,17,18',
                                        sequence: [
                                            ['groupHeader', 15, true]
                                        ],
                                        groupHeader: 1
                                    }, additionalSpec);
                                });

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,12,13,14,15,4,5,6,16,17,18',
                                        sequence: [
                                            ['groupHeader', 14, true]
                                        ],
                                        groupHeader: 1
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,12,13,14,4,5,6,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 14, false]
                                        ],
                                        groupHeader: 1
                                    }, additionalSpec);
                                });
                            });

                            describe('moving into Group4', function() {
                                function additionalSpec() {
                                    expect(!!headerCt.down('[text=Group2]').down('[text=Group4]').down('[text=Group3]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,4,5,6,12,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 11, false]
                                        ],
                                        groupHeader: 1
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,12,13,4,5,6,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 12, true]
                                        ],
                                        groupHeader: 1
                                    }, additionalSpec);
                                });

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,12,4,5,6,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 11, true]
                                        ],
                                        groupHeader: 1
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,7,8,9,10,11,12,4,5,6,13,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 12, false]
                                        ],
                                        groupHeader: 1
                                    }, additionalSpec);
                                });
                            });
                        });
                    });

                    describe('moving from normal to locked', function() {
                        describe('Group2', function() {
                            describe('moving into Group1', function() {
                                function additionalSpec() {
                                    expect(!!headerCt.down('[text=Group1]').down('[text=Group2]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,12,13,14,15,16,3,4,5,6,7,8,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 2, false]
                                        ],
                                        groupHeader: 2
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,6,7,8,12,13,14,15,16,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 7, true]
                                        ],
                                        groupHeader: 2
                                    });
                                }, additionalSpec);

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,6,12,13,14,15,16,7,8,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 'subGroupHeader', true]
                                        ],
                                        groupHeader: 2,
                                        subGroupHeader: 1
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,12,13,14,15,16,4,5,6,7,8,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 'subGroupHeader', false]
                                        ],
                                        groupHeader: 2,
                                        subGroupHeader: 1
                                    }, additionalSpec);
                                });
                            });

                            describe('moving into Group3', function() {
                                function additionalSpec() {
                                    expect(!!headerCt.down('[text=Group1]').down('[text=Group3]').down('[text=Group2]').down('[text=Group4]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,12,13,14,15,16,4,5,6,7,8,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 3, false]
                                        ],
                                        groupHeader: 2
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,6,12,13,14,15,16,7,8,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 5, true]
                                        ],
                                        groupHeader: 2
                                    }, additionalSpec);
                                });

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,12,13,14,15,16,5,6,7,8,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 4, false]
                                        ],
                                        groupHeader: 2
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,12,13,14,15,16,6,7,8,9,10,11,17,18',
                                        sequence: [
                                            ['groupHeader', 4, true]
                                        ],
                                        groupHeader: 2
                                    }, additionalSpec);
                                });
                            });
                        });

                        describe('Group4', function() {
                            describe('moving into Group1', function() {
                                function additionalSpec() {
                                    expect(!!headerCt.down('[text=Group1]').down('[text=Group4]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,12,13,3,4,5,6,7,8,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 2, false]
                                        ],
                                        groupHeader: 3
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,6,7,8,12,13,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 7, true]
                                        ],
                                        groupHeader: 3
                                    }, additionalSpec);
                                });

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,12,13,4,5,6,7,8,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 'subGroupHeader', false]
                                        ],
                                        groupHeader: 3,
                                        subGroupHeader: 1
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,6,12,13,7,8,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 'subGroupHeader', true]
                                        ],
                                        groupHeader: 3,
                                        subGroupHeader: 1
                                    }, additionalSpec);
                                });
                            });

                            describe('moving into Group3', function() {
                                function additionalSpec() {
                                    expect(!!headerCt.down('[text=Group1]').down('[text=Group3]').down('[text=Group4]')).toBe(true);
                                }

                                it('should work moving before the first nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,12,13,4,5,6,7,8,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 3, false]
                                        ],
                                        groupHeader: 3
                                    }, additionalSpec);
                                });

                                it('should work moving after the last nested header', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,6,12,13,7,8,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 5, true]
                                        ],
                                        groupHeader: 3
                                    }, additionalSpec);
                                });

                                it('should work moving into the middle', function() {
                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,12,13,5,6,7,8,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 4, false]
                                        ],
                                        groupHeader: 3
                                    }, additionalSpec);

                                    runTest({
                                        columns: columns,
                                        locked: true,
                                        order: '1,2,3,4,5,12,13,6,7,8,9,10,11,14,15,16,17,18',
                                        sequence: [
                                            ['groupHeader', 5, false]
                                        ],
                                        groupHeader: 3
                                    }, additionalSpec);
                                });
                            });
                        });
                    });
                });
            });
        });

        describe('Dropping before group columns', function() {
            it('should be able to move before the first item of a group column', function() {
                grid = Ext.create('Ext.grid.Panel', {
                    title: 'Simpsons',
                    store: {
                        storeId: 'simpsonsStore',
                        fields: ['name', 'email', 'phone', 'phone1', 'phone2', 'phone3', 'phone4'],
                        data: [{
                            name: 'Lisa',
                            email: 'lisa@simpsons.com',
                            phone: '555-111-1224',
                            phone1: '555-111-1111',
                            phone2: '555-111-2222',
                            phone3: '555-111-3333',
                            phone4: '555-111-4444'
                        }]
                    },
                    columnLines: true,
                    columns: [{
                        text: 'Name',
                        dataIndex: 'name',
                        flex: 1,
                        minWidth: 100
                    }, {
                        text: 'Email',
                        dataIndex: 'email',
                        flex: 1,
                        minWidth: 100
                    }, {
                        text: 'Phone',
                        columns: [{
                            dataIndex: 'phone1',
                            text: 'Phone 1'
                        }, {
                            dataIndex: 'phone2',
                            text: 'Phone 2'
                        }, {
                            dataIndex: 'phone3',
                            text: 'Phone 3'
                        }, {
                            dataIndex: 'phone4',
                            text: 'Phone 4'
                        }]
                    }, {
                        text: 'Phones',
                        columns: [{
                            dataIndex: 'phone1',
                            text: 'Phones 1'
                        }, {
                            dataIndex: 'phone2',
                            text: 'Phones 2'
                        }, {
                            dataIndex: 'phone3',
                            text: 'Phones 3'
                        }, {
                            dataIndex: 'phone4',
                            text: 'Phones 4'
                        }]
                    }],
                    renderTo: Ext.getBody()
                });
                store = grid.store;

                var name = grid.down('[text=Name]'),
                    phone = grid.down('[text=Phone]'),
                    headers = '';

                // Drag to the left of "Phone".
                dragColumn(name, phone);

                // Get new header text order
                Ext.Array.each(grid.getVisibleColumnManager().getColumns(), function(c) {
                    headers += c.text;
                });

                expect(headers).toBe('EmailNamePhone 1Phone 2Phone 3Phone 4Phones 1Phones 2Phones 3Phones 4');
            });
        });
    });
});

