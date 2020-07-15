topSuite("grid-events",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.grid.feature.GroupingSummary',
     'Ext.grid.plugin.CellEditing', 'Ext.form.field.Text'],
function() {
    function createSuite(buffered) {
        describe(buffered ? "with buffered rendering" : "without buffered rendering", function() {
            var describeNotTouch = jasmine.supportsTouch ? xdescribe : describe,
                grid, view, store, selModel, args, called,
                GridEventModel = Ext.define(null, {
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
                        'group'
                    ]
                });

            function triggerCellMouseEvent(type, rowIdx, cellIdx, button, x, y) {
                var target = findCell(rowIdx, cellIdx);

                // Touch platforms cannot fire a touchend without a touchstart
                if (type === 'mouseup' && jasmine.supportsTouch) {
                    jasmine.fireMouseEvent(target, 'mousedown', x, y, button);
                }

                jasmine.fireMouseEvent(target, type, x, y, button);
            }

            function triggerItemMouseEvent(type, rowIdx, button, x, y) {
                var target = view.getNode(rowIdx);

                // Touch platforms cannot fire a touchend without a touchstart
                if (type === 'mouseup' && jasmine.supportsTouch) {
                    jasmine.fireMouseEvent(target, 'mousedown', x, y, button);
                }

                jasmine.fireMouseEvent(target, type, x, y, button);
            }

            function triggerCellKeyEvent(type, rowIdx, cellIdx, key) {
                var target = findCell(rowIdx, cellIdx);

                jasmine.fireKeyEvent(target, type, key);
            }

            function getRec(index) {
                return store.getAt(index);
            }

            function findCell(rowIdx, cellIdx) {
                return grid.getView().getCellInclusive({
                    row: rowIdx,
                    column: cellIdx
                }, true);
            }

            function getRowPosition(el) {
                var parent = Ext.fly(el).up('table');

                return Ext.Array.indexOf(parent.dom.rows, el);
            }

            function getCellPosition(el) {
                var parent = Ext.fly(el).up('tr');

                return Ext.Array.indexOf(parent.dom.cells, el);
            }

            function retFalse() {
                return false;
            }

            function setCalled() {
                called = true;
            }

            function setArgs() {
                args = Array.prototype.slice.call(arguments, 0, arguments.length);
            }

            function makeGrid(columns, grouped, gridCfg) {
                var data = [],
                    defaultCols = [],
                    i;

                for (i = 1; i <= 10; ++i) {
                    defaultCols.push({
                        name: 'F' + i,
                        dataIndex: 'field' + i
                    });
                }

                for (i = 1; i <= 10; ++i) {
                    data.push({
                        field1: i + '.' + 1,
                        field2: i + '.' + 2,
                        field3: i + '.' + 3,
                        field4: i + '.' + 4,
                        field5: i + '.' + 5,
                        field6: i + '.' + 6,
                        field7: i + '.' + 7,
                        field8: i + '.' + 8,
                        field9: i + '.' + 9,
                        field10: i + '.' + 10,
                        group: Math.floor((i + 4) / 5) // 1 to 5 are in group 1, 6 to 10 are in group 2
                    });
                }

                store = {
                    model: GridEventModel,
                    data: data
                };

                // If we are creating a grouped grid, configure the Store with a groupField
                if (grouped) {
                    store.groupField = 'group';
                }

                store = new Ext.data.Store(store);

                grid = Ext.apply({
                    columns: columns || defaultCols,
                    store: store,
                    width: 1000,
                    height: 500,
                    bufferedRenderer: buffered,
                    viewConfig: {
                        mouseOverOutBuffer: 0
                    },
                    renderTo: Ext.getBody()
                }, gridCfg || {});

                // If we are creating a grouped grid, use a grouping summary feature
                if (grouped) {
                    grid.features = {
                        ftype: 'groupingsummary'
                    };
                }

                grid = new Ext.grid.Panel(grid);
                view = grid.getView();
                selModel = view.getSelectionModel();
            }

            afterEach(function() {
                Ext.destroy(grid, store);
                grid = store = view = args = null;
                called = false;
                Ext.data.Model.schema.clear();
            });

            describe("all grid events", function() {
                describe("buffered", function() {
                    beforeEach(function() {
                        makeGrid(null, null, {
                            viewConfig: {
                                mouseOverOutBuffer: 1
                            }
                        });
                    });

                    describe('item events', function() {
                        function expectArgs(index, type) {
                            expect(args[0]).toBe(view);
                            expect(args[1]).toBe(getRec(index));
                            expect(args[2]).toBe(view.getNode(index));
                            expect(args[3]).toBe(index);
                            expect(args[4].type).toBe(type);
                        }

                        describe('longpresses', function() {
                            var timeout = Ext.event.gesture.LongPress.instance.getMinDuration() + 200;

                            it('should fire beforeitemlongpress', function() {
                                var test = {
                                    setArgs: setArgs
                                };

                                spyOn(test, 'setArgs').andCallThrough();

                                grid.on('beforeitemlongpress', test.setArgs);
                                triggerCellMouseEvent('mousedown', 1, 3);

                                waitsForEvent(grid, 'beforeitemlongpress', null, timeout);

                                runs(function() {
                                    expectArgs(1, 'longpress');
                                    triggerCellMouseEvent('mouseup', 1, 3);
                                });
                            });

                            it('should fire itemlongpress', function() {
                                var test = {
                                    setArgs: setArgs
                                };

                                spyOn(test, 'setArgs').andCallThrough();
                                grid.on('itemlongpress', test.setArgs);

                                triggerCellMouseEvent('mousedown', 1, 3);

                                waitsForEvent(grid, 'itemlongpress', null, timeout);

                                runs(function() {
                                    expectArgs(1, 'longpress');
                                    triggerCellMouseEvent('mouseup', 1, 3);
                                });
                            });
                        });
                        // For mouseenter the view uses mouseover
                        describeNotTouch("itemmouseenter", function() {
                            it("should fire the beforeitemmouseenter event", function() {
                                grid.on('beforeitemmouseenter', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);

                                waits(1);

                                runs(function() {
                                    expectArgs(1, 'mouseover');
                                });
                            });

                            it("should fire the itemmouseenter event", function() {
                                grid.on('itemmouseenter', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);

                                waits(1);

                                runs(function() {
                                    expectArgs(1, 'mouseover');
                                });
                            });

                            it("should not trigger itemmouseenter if beforeitemmouseenter is vetoed", function() {
                                grid.on({
                                    beforeitemmouseenter: retFalse,
                                    itemmouseenter: setCalled
                                });
                                triggerItemMouseEvent('mouseover', 1, 3);
                                expect(called).toBe(false);
                            });
                        });

                        // For mouseenter the view uses mouseout
                        describeNotTouch("itemmouseleave", function() {
                            it("should fire the beforeitemmouseleave event", function() {
                                grid.on('beforeitemmouseleave', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);
                                triggerItemMouseEvent('mouseout', 1, 3);

                                waits(1);

                                runs(function() {
                                    expectArgs(1, 'mouseout');
                                });
                            });

                            it("should fire the itemmouseleave event", function() {
                                grid.on('itemmouseleave', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);
                                triggerItemMouseEvent('mouseout', 1, 3);

                                waits(1);

                                runs(function() {
                                    expectArgs(1, 'mouseout');
                                });
                            });

                            it("should not trigger itemmouseleave if beforeitemmouseleave is vetoed", function() {
                                grid.on({
                                    beforeitemmouseleave: retFalse,
                                    itemmouseleave: setCalled
                                });
                                triggerItemMouseEvent('mouseover', 1, 3);
                                triggerItemMouseEvent('mouseout', 1, 3);
                                expect(called).toBe(false);
                            });
                        });
                    });

                    describe("container events", function() {
                        function triggerContainerMouseEvent(type, button) {
                            jasmine.fireMouseEvent(view.el.dom, type, 5, 495, button);
                        }

                        function triggerContainerKeyEvent(type, key) {
                            jasmine.fireKeyEvent(view.el.dom, type, key);
                        }

                        function expectArgs(type) {
                            expect(args[0]).toBe(view);
                            expect(args[1].type).toBe(type);
                        }

                        describeNotTouch("containermouseout", function() {
                            it("should fire the beforecontainermouseout event", function() {
                                grid.on('beforecontainermouseout', setArgs);
                                triggerContainerMouseEvent('mouseout');

                                waits(1);

                                runs(function() {
                                    expectArgs('mouseout');
                                });
                            });

                            it("should fire the containermouseout event", function() {
                                grid.on('containermouseout', setArgs);
                                triggerContainerMouseEvent('mouseout');

                                waits(1);

                                runs(function() {
                                    expectArgs('mouseout');
                                });
                            });

                            it("should not trigger containermouseout if beforecontainermouseout is vetoed", function() {
                                grid.on({
                                    beforecontainermouseout: retFalse,
                                    containermouseout: setCalled
                                });
                                triggerContainerMouseEvent('mouseout');

                                waits(1);

                                runs(function() {
                                    expect(called).toBe(false);
                                });
                            });
                        });

                        describeNotTouch("containermouseover", function() {
                            it("should fire the beforecontainermouseover event", function() {
                                grid.on('beforecontainermouseover', setArgs);
                                triggerContainerMouseEvent('mouseover');

                                waits(1);

                                runs(function() {
                                    expectArgs('mouseover');
                                });
                            });

                            it("should fire the containermouseover event", function() {
                                grid.on('containermouseover', setArgs);
                                triggerContainerMouseEvent('mouseover');

                                waits(1);

                                runs(function() {
                                    expectArgs('mouseover');
                                });
                            });

                            it("should not trigger containermouseover if beforecontainermouseover is vetoed", function() {
                                grid.on({
                                    beforecontainermouseover: retFalse,
                                    containermouseover: setCalled
                                });
                                triggerContainerMouseEvent('mouseover');

                                waits(1);

                                runs(function() {
                                    expect(called).toBe(false);
                                });
                            });
                        });
                    });
                });

                describe("not buffered", function() {
                    beforeEach(function() {
                        makeGrid();
                    });

                    describe("item events", function() {
                        function expectArgs(index, type) {
                            expect(args[0]).toBe(view);
                            expect(args[1]).toBe(getRec(index));
                            expect(args[2]).toBe(view.getNode(index));
                            expect(args[3]).toBe(index);
                            expect(args[4].type).toBe(type);
                        }

                        describe("itemclick", function() {
                            it("should fire the beforeitemclick event", function() {
                                grid.on('beforeitemclick', setArgs);
                                triggerCellMouseEvent('click', 2, 4);
                                expectArgs(2, 'click');
                            });

                            it("should fire the itemclick event", function() {
                                grid.on('itemclick', setArgs);
                                triggerCellMouseEvent('click', 2, 4);
                                expectArgs(2, 'click');
                            });

                            it("should not trigger itemclick if beforeitemclick is vetoed", function() {
                                grid.on({
                                    beforeitemclick: retFalse,
                                    itemclick: setCalled
                                });
                                triggerCellMouseEvent('click', 2, 4);
                                expect(called).toBe(false);
                            });
                        });

                        describe("itemcontextmenu", function() {
                            it("should fire the beforeitemcontextmenu event", function() {
                                grid.on('beforeitemcontextmenu', setArgs);
                                triggerCellMouseEvent('contextmenu', 2, 4, 2);
                                expectArgs(2, 'contextmenu');
                            });

                            it("should fire the itemcontextmenu event", function() {
                                grid.on('itemcontextmenu', setArgs);
                                triggerCellMouseEvent('contextmenu', 2, 4, 2);
                                expectArgs(2, 'contextmenu');
                            });

                            it("should not trigger itemcontextmenu if beforeitemcontextmenu is vetoed", function() {
                                grid.on({
                                    beforeitemcontextmenu: retFalse,
                                    itemcontextmenu: setCalled
                                });
                                triggerCellMouseEvent('contextmenu', 2, 4, 2);
                                expect(called).toBe(false);
                            });
                        });

                        describe("itemdblclick", function() {
                            it("should fire the beforeitemdblclick event", function() {
                                grid.on('beforeitemdblclick', setArgs);
                                triggerCellMouseEvent('dblclick', 2, 4);
                                expectArgs(2, 'dblclick');
                            });

                            it("should fire the itemdblclick event", function() {
                                grid.on('itemdblclick', setArgs);
                                triggerCellMouseEvent('dblclick', 2, 4);
                                expectArgs(2, 'dblclick');
                            });

                            it("should not trigger itemdblclick if beforeitemdblclick is vetoed", function() {
                                grid.on({
                                    beforeitemdblclick: retFalse,
                                    itemdblclick: setCalled
                                });
                                triggerCellMouseEvent('dblclick', 2, 4);
                                expect(called).toBe(false);
                            });
                        });

                        describe("itemkeydown", function() {
                            it("should fire the beforeitemkeydown event", function() {
                                grid.on('beforeitemkeydown', setArgs);
                                triggerCellKeyEvent('keydown', 1, 3);
                                expectArgs(1, 'keydown');
                            });

                            it("should fire the itemkeydown event", function() {
                                grid.on('itemkeydown', setArgs);
                                triggerCellKeyEvent('keydown', 1, 3);
                                expectArgs(1, 'keydown');
                            });

                            it("should not trigger itemkeydown if beforeitemkeydown is vetoed", function() {
                                grid.on({
                                    beforeitemkeydown: retFalse,
                                    itemkeydown: setCalled
                                });
                                triggerCellKeyEvent('keydown', 1, 3);
                                expect(called).toBe(false);
                            });
                        });

                        describe("itemmousedown", function() {
                            it("should fire the beforeitemmousedown event", function() {
                                grid.on('beforeitemmousedown', setArgs);
                                triggerCellMouseEvent('mousedown', 1, 3);
                                expectArgs(1, 'mousedown');
                                triggerCellMouseEvent('mouseup', 1, 3);
                            });

                            it("should fire the itemmousedown event", function() {
                                grid.on('itemmousedown', setArgs);
                                triggerCellMouseEvent('mousedown', 1, 3);
                                expectArgs(1, 'mousedown');
                                triggerCellMouseEvent('mouseup', 1, 3);
                            });

                            it("should not trigger itemmousedown if beforeitemmousedown is vetoed", function() {
                                grid.on({
                                    beforeitemmousedown: retFalse,
                                    itemmousedown: setCalled
                                });
                                triggerCellMouseEvent('mousedown', 1, 3);
                                expect(called).toBe(false);
                                triggerCellMouseEvent('mouseup', 1, 3);
                            });
                        });

                        // For mouseenter the view uses mouseover
                        describeNotTouch("itemmouseenter", function() {
                            it("should fire the beforeitemmouseenter event", function() {
                                grid.on('beforeitemmouseenter', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);
                                expectArgs(1, 'mouseover');
                            });

                            it("should fire the itemmouseenter event", function() {
                                grid.on('itemmouseenter', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);
                                expectArgs(1, 'mouseover');
                            });

                            it("should not trigger itemmouseenter if beforeitemmouseenter is vetoed", function() {
                                grid.on({
                                    beforeitemmouseenter: retFalse,
                                    itemmouseenter: setCalled
                                });
                                triggerItemMouseEvent('mouseover', 1, 3);
                                expect(called).toBe(false);
                            });
                        });

                        // For mouseenter the view uses mouseout
                        describeNotTouch("itemmouseleave", function() {
                            it("should fire the beforeitemmouseleave event", function() {
                                grid.on('beforeitemmouseleave', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);
                                triggerItemMouseEvent('mouseout', 1, 3);
                                expectArgs(1, 'mouseout');
                            });

                            it("should fire the itemmouseleave event", function() {
                                grid.on('itemmouseleave', setArgs);
                                triggerItemMouseEvent('mouseover', 1, 3);
                                triggerItemMouseEvent('mouseout', 1, 3);
                                expectArgs(1, 'mouseout');
                            });

                            it("should not trigger itemmouseleave if beforeitemmouseleave is vetoed", function() {
                                grid.on({
                                    beforeitemmouseleave: retFalse,
                                    itemmouseleave: setCalled
                                });
                                triggerItemMouseEvent('mouseover', 1, 3);
                                triggerItemMouseEvent('mouseout', 1, 3);
                                expect(called).toBe(false);
                            });
                        });

                        describe("itemmouseup", function() {
                            it("should fire the beforeitemmouseup event", function() {
                                grid.on('beforeitemmouseup', setArgs);
                                triggerCellMouseEvent('mouseup', 1, 3);
                                expectArgs(1, 'mouseup');
                            });

                            it("should fire the itemmouseup event", function() {
                                grid.on('itemmouseup', setArgs);
                                triggerCellMouseEvent('mouseup', 1, 3);
                                expectArgs(1, 'mouseup');
                            });

                            it("should not trigger itemmouseup if beforeitemmouseup is vetoed", function() {
                                grid.on({
                                    beforeitemmouseup: retFalse,
                                    itemmouseup: setCalled
                                });
                                triggerCellMouseEvent('mouseup', 1, 3);
                                expect(called).toBe(false);
                            });
                        });
                    });

                    describe("cell events", function() {
                        function expectArgs(rowIndex, colIndex, type) {
                            var record = getRec(rowIndex);

                            expect(args[0]).toBe(view);
                            expect(args[1]).toBe(view.getCell(record, view.getHeaderAtIndex(colIndex)));
                            expect(args[2]).toBe(colIndex);
                            expect(args[3]).toBe(record);
                            expect(args[4] === view.getRow(rowIndex)).toBe(true);
                            expect(args[5]).toBe(rowIndex);
                            expect(args[6].type).toBe(type);
                        }

                        describe("cellclick", function() {
                            it("should fire the beforecellclick event", function() {
                                grid.on('beforecellclick', setArgs);
                                triggerCellMouseEvent('click', 3, 6);
                                expectArgs(3, 6, 'click');
                            });

                            it("should fire the cellclick event", function() {
                                grid.on('cellclick', setArgs);
                                triggerCellMouseEvent('click', 3, 6);
                                expectArgs(3, 6, 'click');
                            });

                            it("should not trigger cellclick if beforecellclick is vetoed", function() {
                                grid.on({
                                    beforecellclick: retFalse,
                                    cellclick: setCalled
                                });
                                triggerCellMouseEvent('click', 3, 6);
                                expect(called).toBe(false);
                            });
                        });

                        describe("cellcontextmenu", function() {
                            it("should fire the beforecellcontextmenu event", function() {
                                grid.on('beforecellcontextmenu', setArgs);
                                triggerCellMouseEvent('contextmenu', 3, 6);
                                expectArgs(3, 6, 'contextmenu');
                            });

                            it("should fire the cellcontextmenu event", function() {
                                grid.on('cellcontextmenu', setArgs);
                                triggerCellMouseEvent('contextmenu', 3, 6);
                                expectArgs(3, 6, 'contextmenu');
                            });

                            it("should not trigger cellcontextmenu if beforecellcontextmenu is vetoed", function() {
                                grid.on({
                                    beforecellcontextmenu: retFalse,
                                    cellcontextmenu: setCalled
                                });
                                triggerCellMouseEvent('contextmenu', 3, 6);
                                expect(called).toBe(false);
                            });
                        });

                        describe("celldblclick", function() {
                            it("should fire the beforecelldblclick event", function() {
                                grid.on('beforecelldblclick', setArgs);
                                triggerCellMouseEvent('dblclick', 3, 6);
                                expectArgs(3, 6, 'dblclick');
                            });

                            it("should fire the celldblclick event", function() {
                                grid.on('celldblclick', setArgs);
                                triggerCellMouseEvent('dblclick', 3, 6);
                                expectArgs(3, 6, 'dblclick');
                            });

                            it("should not trigger celldblclick if beforecelldblclick is vetoed", function() {
                                grid.on({
                                    beforecelldblclick: retFalse,
                                    celldblclick: setCalled
                                });
                                triggerCellMouseEvent('dblclick', 3, 6);
                                expect(called).toBe(false);
                            });
                        });

                        describe("cellkeydown", function() {
                            it("should fire the beforecellkeydown event", function() {
                                grid.on('beforecellkeydown', setArgs);
                                triggerCellKeyEvent('keydown', 3, 6);
                                expectArgs(3, 6, 'keydown');
                            });

                            it("should fire the cellkeydown event", function() {
                                grid.on('cellkeydown', setArgs);
                                triggerCellKeyEvent('keydown', 3, 6);
                                expectArgs(3, 6, 'keydown');
                            });

                            it("should not trigger cellkeydown if beforecellkeydown is vetoed", function() {
                                grid.on({
                                    beforecellkeydown: retFalse,
                                    cellkeydown: setCalled
                                });
                                triggerCellKeyEvent('keydown', 3, 6);
                                expect(called).toBe(false);
                            });
                        });

                        describe("cellmousedown", function() {
                            it("should fire the beforecellmousedown event", function() {
                                grid.on('beforecellmousedown', setArgs);
                                triggerCellMouseEvent('mousedown', 3, 6);
                                expectArgs(3, 6, 'mousedown');
                                triggerCellMouseEvent('mouseup', 3, 6);
                            });

                            it("should fire the cellmousedown event", function() {
                                grid.on('cellmousedown', setArgs);
                                triggerCellMouseEvent('mousedown', 3, 6);
                                expectArgs(3, 6, 'mousedown');
                                triggerCellMouseEvent('mouseup', 3, 6);
                            });

                            it("should not trigger cellmousedown if beforecellmousedown is vetoed", function() {
                                grid.on({
                                    beforecellmousedown: retFalse,
                                    cellmousedown: setCalled
                                });
                                triggerCellMouseEvent('mousedown', 3, 6);
                                expect(called).toBe(false);
                                triggerCellMouseEvent('mouseup', 3, 6);
                            });
                        });

                        describe("cellmouseup", function() {
                            it("should fire the beforecellmouseup event", function() {
                                grid.on('beforecellmouseup', setArgs);
                                triggerCellMouseEvent('mouseup', 3, 6);
                                expectArgs(3, 6, 'mouseup');
                            });

                            it("should fire the cellmouseup event", function() {
                                grid.on('cellmouseup', setArgs);
                                triggerCellMouseEvent('mouseup', 3, 6);
                                expectArgs(3, 6, 'mouseup');
                            });

                            it("should not trigger cellmouseup if beforecellmouseup is vetoed", function() {
                                grid.on({
                                    beforecellmouseup: retFalse,
                                    cellmouseup: setCalled
                                });
                                triggerCellMouseEvent('mouseup', 3, 6);
                                expect(called).toBe(false);
                            });
                        });
                    });

                    describe("container events", function() {
                        function triggerContainerMouseEvent(type, button) {
                            // Touch platforms cannot fire a touchend without a touchstart
                            if (type === 'mouseup') {
                                jasmine.fireMouseEvent(view.el.dom, 'mousedown', 5, 495, button);
                            }

                            jasmine.fireMouseEvent(view.el.dom, type, 5, 495, button);
                        }

                        function triggerContainerKeyEvent(type, key) {
                            jasmine.fireKeyEvent(view.el.dom, type, key);
                        }

                        function expectArgs(type) {
                            expect(args[0]).toBe(view);
                            expect(args[1].type).toBe(type);
                        }

                        describe("containerclick", function() {
                            it("should fire the beforecontainerclick event", function() {
                                grid.on('beforecontainerclick', setArgs);
                                triggerContainerMouseEvent('click');
                                expectArgs('click');
                            });

                            it("should fire the containerclick event", function() {
                                grid.on('containerclick', setArgs);
                                triggerContainerMouseEvent('click');
                                expectArgs('click');
                            });

                            it("should not trigger containerclick if beforecontainerclick is vetoed", function() {
                                grid.on({
                                    beforecontainerclick: retFalse,
                                    containerclick: setCalled
                                });
                                triggerContainerMouseEvent('click');
                                expect(called).toBe(false);
                            });
                        });

                        describe("containercontextmenu", function() {
                            it("should fire the beforecontainercontextmenu event", function() {
                                grid.on('beforecontainercontextmenu', setArgs);
                                triggerContainerMouseEvent('contextmenu', 2);
                                expectArgs('contextmenu');
                            });

                            it("should fire the containercontextmenu event", function() {
                                grid.on('containercontextmenu', setArgs);
                                triggerContainerMouseEvent('contextmenu', 2);
                                expectArgs('contextmenu');
                            });

                            it("should not trigger containercontextmenu if beforecontainercontextmenu is vetoed", function() {
                                grid.on({
                                    beforecontainercontextmenu: retFalse,
                                    containercontextmenu: setCalled
                                });
                                triggerContainerMouseEvent('contextmenu', 2);
                                expect(called).toBe(false);
                            });
                        });

                        describe("containerdblclick", function() {
                            it("should fire the beforecontainerdblclick event", function() {
                                grid.on('beforecontainerdblclick', setArgs);
                                triggerContainerMouseEvent('dblclick');
                                expectArgs('dblclick');
                            });

                            it("should fire the containerdblclick event", function() {
                                grid.on('containerdblclick', setArgs);
                                triggerContainerMouseEvent('dblclick');
                                expectArgs('dblclick');
                            });

                            it("should not trigger containerdblclick if beforecontainerdblclick is vetoed", function() {
                                grid.on({
                                    beforecontainerdblclick: retFalse,
                                    containerdblclick: setCalled
                                });
                                triggerContainerMouseEvent('dblclick');
                                expect(called).toBe(false);
                            });
                        });

                        describe("containerkeydown", function() {
                            it("should fire the beforecontainerkeydown event", function() {
                                grid.on('beforecontainerkeydown', setArgs);
                                triggerContainerKeyEvent('keydown');
                                expectArgs('keydown');
                            });

                            it("should fire the containerkeydown event", function() {
                                grid.on('containerkeydown', setArgs);
                                triggerContainerKeyEvent('keydown');
                                expectArgs('keydown');
                            });

                            it("should not trigger containerkeydown if beforecontainerkeydown is vetoed", function() {
                                grid.on({
                                    beforecontainerkeydown: retFalse,
                                    containerkeydown: setCalled
                                });
                                triggerContainerKeyEvent('keydown');
                                expect(called).toBe(false);
                            });
                        });

                        describe("containermousedown", function() {
                            it("should fire the beforecontainermousedown event", function() {
                                grid.on('beforecontainermousedown', setArgs);
                                triggerContainerMouseEvent('mousedown');
                                expectArgs('mousedown');
                                triggerContainerMouseEvent('mouseup');
                            });

                            it("should fire the containermousedown event", function() {
                                grid.on('containermousedown', setArgs);
                                triggerContainerMouseEvent('mousedown');
                                expectArgs('mousedown');
                                triggerContainerMouseEvent('mouseup');
                            });

                            it("should not trigger containermousedown if beforecontainermousedown is vetoed", function() {
                                grid.on({
                                    beforecontainermousedown: retFalse,
                                    containermousedown: setCalled
                                });
                                triggerContainerMouseEvent('mousedown');
                                expect(called).toBe(false);
                                triggerContainerMouseEvent('mouseup');
                            });
                        });

                        describeNotTouch("containermouseout", function() {
                            it("should fire the beforecontainermouseout event", function() {
                                grid.on('beforecontainermouseout', setArgs);
                                triggerContainerMouseEvent('mouseout');
                                expectArgs('mouseout');
                            });

                            it("should fire the containermouseout event", function() {
                                grid.on('containermouseout', setArgs);
                                triggerContainerMouseEvent('mouseout');
                                expectArgs('mouseout');
                            });

                            it("should not trigger containermouseout if beforecontainermouseout is vetoed", function() {
                                grid.on({
                                    beforecontainermouseout: retFalse,
                                    containermouseout: setCalled
                                });
                                triggerContainerMouseEvent('mouseout');
                                expect(called).toBe(false);
                            });
                        });

                        describeNotTouch("containermouseover", function() {
                            it("should fire the beforecontainermouseover event", function() {
                                grid.on('beforecontainermouseover', setArgs);
                                triggerContainerMouseEvent('mouseover');
                                expectArgs('mouseover');
                            });

                            it("should fire the containermouseover event", function() {
                                grid.on('containermouseover', setArgs);
                                triggerContainerMouseEvent('mouseover');
                                expectArgs('mouseover');
                            });

                            it("should not trigger containermouseover if beforecontainermouseover is vetoed", function() {
                                grid.on({
                                    beforecontainermouseover: retFalse,
                                    containermouseover: setCalled
                                });
                                triggerContainerMouseEvent('mouseover');
                                expect(called).toBe(false);
                            });
                        });

                        describeNotTouch("containermouseup", function() {
                            it("should fire the beforecontainermouseup event", function() {
                                grid.on('beforecontainermouseup', setArgs);
                                triggerContainerMouseEvent('mouseup');
                                expectArgs('mouseup');
                            });

                            it("should fire the containermouseup event", function() {
                                grid.on('containermouseup', setArgs);
                                triggerContainerMouseEvent('mouseup');
                                expectArgs('mouseup');
                            });

                            it("should not trigger containermouseup if beforecontainermouseup is vetoed", function() {
                                grid.on({
                                    beforecontainermouseup: retFalse,
                                    containermouseup: setCalled
                                });
                                triggerContainerMouseEvent('mouseup');
                                expect(called).toBe(false);
                            });
                        });
                    });

                    describe("column events", function() {
                        function expectArgs(rowIndex, colIndex, type) {
                            var record = getRec(rowIndex);

                            expect(args[0]).toBe(view);
                            expect(args[1]).toBe(view.getCell(record, view.getHeaderAtIndex(colIndex)));
                            expect(args[2]).toBe(rowIndex);
                            expect(args[3]).toBe(colIndex);
                            expect(args[4].type).toBe(type);
                            expect(args[5]).toBe(record);
                            expect(args[6] === view.getRow(rowIndex)).toBe(true);
                        }

                        it("should relay click events", function() {
                            grid.headerCt.getComponent(6).on('click', setArgs);
                            triggerCellMouseEvent('click', 3, 6);
                            expectArgs(3, 6, 'click');
                        });

                        it("should relay contextmenu events", function() {
                            grid.headerCt.getComponent(6).on('contextmenu', setArgs);
                            triggerCellMouseEvent('contextmenu', 3, 6);
                            expectArgs(3, 6, 'contextmenu');
                        });

                        it("should relay dblclick events", function() {
                            grid.headerCt.getComponent(6).on('dblclick', setArgs);
                            triggerCellMouseEvent('dblclick', 3, 6);
                            expectArgs(3, 6, 'dblclick');
                        });

                        it("should relay keydown events", function() {
                            grid.headerCt.getComponent(6).on('keydown', setArgs);
                            triggerCellKeyEvent('keydown', 3, 6);
                            expectArgs(3, 6, 'keydown');
                        });

                        it("should relay mousedown events", function() {
                            grid.headerCt.getComponent(6).on('mousedown', setArgs);
                            triggerCellMouseEvent('mousedown', 3, 6);
                            expectArgs(3, 6, 'mousedown');
                            triggerCellMouseEvent('mouseup', 3, 6);
                        });

                        it("should relay mouseup events", function() {
                            grid.headerCt.getComponent(6).on('mouseup', setArgs);
                            triggerCellMouseEvent('mouseup', 3, 6);
                            expectArgs(3, 6, 'mouseup');
                        });
                    });
                });
            });

            // This is not intended to be fully featured selection tests, just
            // some basic smoke tests to check whether the events are relayed correctly
            // from the selection model.
            describe("relaying selection events", function() {
                var sm, spy;

                beforeEach(function() {
                    spy = jasmine.createSpy();
                });

                afterEach(function() {
                    spy = sm = null;
                });

                describe("row model", function() {
                    function get(indexes) {
                        var recs = [];

                        if (!Ext.isArray(indexes)) {
                            indexes = [indexes];
                        }

                        Ext.Array.forEach(indexes, function(index) {
                            recs.push(store.getAt(index));
                        });

                        return recs;
                    }

                    beforeEach(function() {
                        makeGrid(null, null, {
                            multiSelect: true
                        });
                        sm = grid.getSelectionModel();
                    });

                    function expectEventArgs(args, record, index) {
                        expect(args[0]).toBe(sm);
                        expect(args[1]).toBe(record);
                        expect(args[2]).toBe(index);
                    }

                    describe("beforeselect/select", function() {
                        it("should fire beforeselect before selecting an item", function() {
                            grid.on('beforeselect', spy);
                            sm.select(0);
                            expect(spy.callCount).toBe(1);
                            expectEventArgs(spy.mostRecentCall.args, store.getAt(0), 0);
                        });

                        it("should fire beforeselect before selecting each item", function() {
                            grid.on('beforeselect', spy);
                            sm.select(get([3, 4, 7]));
                            expect(spy.callCount).toBe(3);

                            expectEventArgs(spy.calls[0].args, store.getAt(3), 3);
                            expectEventArgs(spy.calls[1].args, store.getAt(4), 4);
                            expectEventArgs(spy.calls[2].args, store.getAt(7), 7);
                        });

                        it("should prevent selection when beforeselect returns false", function() {
                            grid.on('beforeselect', spy.andReturn(false));
                            sm.select(0);
                            expect(spy.callCount).toBe(1);
                            expectEventArgs(spy.mostRecentCall.args, store.getAt(0), 0);
                            expect(sm.getCount()).toBe(0);
                        });

                        it("should fire select when an item is selected", function() {
                            grid.on('select', spy);
                            sm.select(0);
                            expect(spy.callCount).toBe(1);
                            expectEventArgs(spy.mostRecentCall.args, store.getAt(0), 0);
                        });

                        it("should fire select for each selected item", function() {
                            grid.on('select', spy);
                            sm.select(get([3, 4, 7]));
                            expect(spy.callCount).toBe(3);

                            expectEventArgs(spy.calls[0].args, store.getAt(3), 3);
                            expectEventArgs(spy.calls[1].args, store.getAt(4), 4);
                            expectEventArgs(spy.calls[2].args, store.getAt(7), 7);
                        });
                    });

                    describe("beforedeselect/deselect", function() {
                        it("should fire beforedeselect before deselecting an item", function() {
                            sm.select(0);
                            grid.on('beforedeselect', spy);
                            sm.deselect(0);
                            expect(spy.callCount).toBe(1);
                            expectEventArgs(spy.mostRecentCall.args, store.getAt(0), 0);
                        });

                        it("should fire beforedeselect before deselecting each item", function() {
                            sm.select(get([3, 4, 7]));
                            grid.on('beforedeselect', spy);
                            sm.deselect(get([3, 4, 7]));
                            expect(spy.callCount).toBe(3);

                            expectEventArgs(spy.calls[0].args, store.getAt(3), 3);
                            expectEventArgs(spy.calls[1].args, store.getAt(4), 4);
                            expectEventArgs(spy.calls[2].args, store.getAt(7), 7);
                        });

                        it("should prevent deselection when beforedeselect returns false", function() {
                            sm.select(0);
                            grid.on('beforedeselect', spy.andReturn(false));
                            sm.deselect(0);
                            expect(spy.callCount).toBe(1);
                            expectEventArgs(spy.mostRecentCall.args, store.getAt(0), 0);
                            expect(sm.getCount()).toBe(1);
                        });

                        it("should fire deselect when an item is deselected", function() {
                            sm.select(0);
                            grid.on('deselect', spy);
                            sm.deselect(0);
                            expect(spy.callCount).toBe(1);
                            expectEventArgs(spy.mostRecentCall.args, store.getAt(0), 0);
                        });

                        it("should fire select for each selected item", function() {
                            sm.select(get([3, 4, 7]));
                            grid.on('deselect', spy);
                            sm.deselect(get([3, 4, 7]));
                            expect(spy.callCount).toBe(3);

                            expectEventArgs(spy.calls[0].args, store.getAt(3), 3);
                            expectEventArgs(spy.calls[1].args, store.getAt(4), 4);
                            expectEventArgs(spy.calls[2].args, store.getAt(7), 7);
                        });
                    });

                    describe("selectionchange", function() {
                        it("should fire a single selectionchange", function() {
                            grid.on('selectionchange', spy);

                            sm.select(get([1, 4, 8]));
                            expect(spy.callCount).toBe(1);

                            var args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(sm);
                            expect(args[1]).toEqual(get([1, 4, 8]));
                            spy.reset();

                            sm.select(9, true);
                            expect(spy.callCount).toBe(1);

                            args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(sm);
                            expect(args[1]).toEqual(get([1, 4, 8, 9]));
                            spy.reset();

                            sm.select(2);
                            expect(spy.callCount).toBe(1);

                            args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(sm);
                            expect(args[1]).toEqual(get([2]));
                        });
                    });
                });
            });

            describe("locking", function() {
                describe("events", function() {
                    beforeEach(function() {
                        makeGrid([{
                            locked: true,
                            text: 'F1',
                            dataIndex: 'field1'
                        }, {
                            locked: true,
                            text: 'F2',
                            dataIndex: 'field2'
                        }, {
                            locked: true,
                            text: 'F3',
                            dataIndex: 'field3'
                        }, {
                            locked: true,
                            text: 'F4',
                            dataIndex: 'field4'
                        }, {
                            locked: true,
                            text: 'F5',
                            dataIndex: 'field5'
                        }, {
                            text: 'F6',
                            dataIndex: 'field6'
                        }, {
                            text: 'F7',
                            dataIndex: 'field7'
                        }, {
                            text: 'F8',
                            dataIndex: 'field8'
                        }, {
                            text: 'F9',
                            dataIndex: 'field9'
                        }, {
                            text: 'F10',
                            dataIndex: 'field10'
                        }]);
                    });

                    describe('selection events', function() {
                        var callCount;

                        function countEvent(eventName) {
                            grid.on(eventName, function() {
                                callCount++;
                            });
                        }

                        function createTest(eventName) {
                            it('should fire the ' + eventName + ' event once', function() {
                                selModel.select(0);
                                countEvent(eventName);
                                selModel.select(1);
                                expect(callCount).toBe(1);
                            });
                        }

                        beforeEach(function() {
                            callCount = 0;
                        });
                        createTest('deselect');
                        createTest('select');
                        createTest('beforeselect');
                        createTest('beforedeselect');
                        createTest('selectionchange');
                    });

                    describe('row events', function() {
                        function expectArgs(view, index, type) {
                            expect(args[0] === view).toBe(true);
                            expect(args[1] === getRec(index)).toBe(true);
                            expect(args[2] === view.getNode(index).getElementsByTagName('tr')[0]).toBe(true);
                            expect(args[3]).toBe(index);
                            expect(args[4].type).toBe(type);
                        }

                        it('should fire the itemclick event when clicking on the locked side', function() {
                            grid.on('rowclick', setArgs);
                            triggerCellMouseEvent('click', 2, 4);
                            expectArgs(grid.lockedGrid.getView(), 2, 'click');
                        });

                        it('should fire the itemclick event when clicking on the unlocked side', function() {
                            grid.on('rowcontextmenu', setArgs);
                            triggerCellMouseEvent('contextmenu', 3, 7, 2);
                            expectArgs(grid.normalGrid.getView(), 3, 'contextmenu');
                        });
                    });

                    describe("item events", function() {
                        function expectArgs(view, index, type) {
                            expect(args[0] === view).toBe(true);
                            expect(args[1] === getRec(index)).toBe(true);
                            expect(args[2] === view.getNode(index)).toBe(true);
                            expect(args[3]).toBe(index);
                            expect(args[4].type).toBe(type);
                        }

                        it("should fire the itemclick event when clicking on the locked side", function() {
                            grid.on('itemclick', setArgs);
                            triggerCellMouseEvent('click', 2, 4);
                            expectArgs(grid.lockedGrid.getView(), 2, 'click');
                        });

                        it("should fire the itemclick event when clicking on the unlocked side", function() {
                            grid.on('itemclick', setArgs);
                            triggerCellMouseEvent('click', 3, 7);
                            expectArgs(grid.normalGrid.getView(), 3, 'click');
                        });
                    });

                    describe("cell events", function() {
                        function expectArgs(view, rowIndex, colIndex, type) {
                            var record = getRec(rowIndex);

                            expect(args[0] === view).toBe(true);
                            expect(args[1] === view.getCell(record, view.getHeaderAtIndex(colIndex))).toBe(true);
                            expect(args[2]).toBe(colIndex);
                            expect(args[3] === record).toBe(true);
                            expect(args[4] === view.getRow(rowIndex)).toBe(true);
                            expect(args[5]).toBe(rowIndex);
                            expect(args[6].type).toBe(type);
                        }

                        it("should fire the cellclick event when clicking on the locked side", function() {
                            grid.on('cellclick', setArgs);
                            triggerCellMouseEvent('click', 1, 0);
                            expectArgs(grid.lockedGrid.getView(), 1, 0, 'click');
                        });

                        it("should fire the cellclick event when clicking on the unlocked side", function() {
                            grid.on('cellclick', setArgs);
                            triggerCellMouseEvent('click', 1, 6);
                            expectArgs(grid.normalGrid.getView(), 1, 1, 'click');
                        });
                    });
                });

                describe("viewready event", function() {
                    var spy;

                    beforeEach(function() {
                        spy = jasmine.createSpy();

                        makeGrid([{
                            locked: true,
                            text: 'F1',
                            dataIndex: 'field1'
                        }, {
                            text: 'F2',
                            dataIndex: 'field2'
                        }, {
                            locked: true,
                            text: 'F3',
                            dataIndex: 'field3'
                        }, {
                            text: 'F4',
                            dataIndex: 'field4'
                        }], false, {
                            listeners: {
                                viewready: spy
                            }
                        });
                    });

                    it("should relay the event from the child grid", function() {
                        waitsFor(function() {
                            return spy.callCount > 0;
                        });

                        runs(function() {
                            expect(spy).toHaveBeenCalled();
                        });
                    });

                    it("should add a gridRelayers property to the owner lockable grid", function() {
                        waitsFor(function() {
                            return spy.callCount > 0;
                        });

                        runs(function() {
                            expect(grid.gridRelayers).toBeDefined();
                        });
                    });
                });
            });

            describe("hidden columns", function() {
                function expectArgs(view, rowIndex, colIndex, visibleColIndex, type) {
                    var record = getRec(rowIndex);

                    expect(args[0] === view).toBe(true);
                    expect(args[1] === view.getCell(record, view.getHeaderAtIndex(colIndex))).toBe(true);
                    expect(args[2]).toBe(visibleColIndex);
                    expect(args[3] === record).toBe(true);
                    expect(args[4] === view.getRow(rowIndex)).toBe(true);
                    expect(args[5]).toBe(rowIndex);
                    expect(args[6].type).toBe(type);
                }

                describe("unlocked", function() {
                    beforeEach(function() {
                        makeGrid([{
                            text: 'F1',
                            dataIndex: 'field1'
                        }, {
                            hidden: true,
                            text: 'F2',
                            dataIndex: 'field2'
                        }, {
                            hidden: true,
                            text: 'F3',
                            dataIndex: 'field3'
                        }, {
                            text: 'F4',
                            dataIndex: 'field4'
                        }, {
                            hidden: true,
                            text: 'F5',
                            dataIndex: 'field5'
                        }, {
                            text: 'F6',
                            dataIndex: 'field6'
                        }]);
                    });

                    it("should fire the events taking into account hidden columns before hidden columns", function() {
                        grid.on('cellclick', setArgs);
                        triggerCellMouseEvent('click', 1, 0);
                        expectArgs(view, 1, 0, 0, 'click');
                    });

                    it("should fire the events taking into account hidden columns in between hidden columns", function() {
                        grid.on('cellclick', setArgs);
                        triggerCellMouseEvent('click', 1, 3);
                        expectArgs(view, 1, 1, 3, 'click');
                    });

                    it("should fire the events taking into account hidden columns at the last column", function() {
                        grid.on('cellclick', setArgs);
                        triggerCellMouseEvent('click', 1, 5);
                        expectArgs(view, 1, 2, 5, 'click');
                    });
                });

                describe("with locking", function() {
                    beforeEach(function() {
                        makeGrid([{
                            locked: true,
                            text: 'F1',
                            dataIndex: 'field1'
                        }, {
                            locked: true,
                            hidden: true,
                            text: 'F2',
                            dataIndex: 'field2'
                        }, {
                            locked: true,
                            hidden: true,
                            text: 'F3',
                            dataIndex: 'field3'
                        }, {
                            locked: true,
                            text: 'F4',
                            dataIndex: 'field4'
                        }, {
                            locked: true,
                            text: 'F5',
                            dataIndex: 'field5'
                        }, {
                            text: 'F6',
                            dataIndex: 'field6'
                        }, {
                            hidden: true,
                            text: 'F7',
                            dataIndex: 'field7'
                        }, {
                            text: 'F8',
                            dataIndex: 'field8'
                        }, {
                            hidden: true,
                            text: 'F9',
                            dataIndex: 'field9'
                        }, {
                            text: 'F10',
                            dataIndex: 'field10'
                        }]);
                    });

                    it("should fire the events taking into account hidden columns from the locked part", function() {
                        grid.on('cellclick', setArgs);
                        triggerCellMouseEvent('click', 1, 3);
                        expectArgs(grid.lockedGrid.getView(), 1, 1, 3, 'click');
                    });

                    it("should fire the events taking into account hidden columns from the unlocked part", function() {
                        grid.on('cellclick', setArgs);
                        triggerCellMouseEvent('click', 1, 5);
                        expectArgs(grid.normalGrid.getView(), 1, 0, 0, 'click');
                    });
                });

            });

            describe('With grouping', function() {
                var selItem;

                beforeEach(function() {
                    makeGrid(null, true);
                });

                describe('mouseover', function() {
                    it('should highlight rows on select', function() {
                        triggerCellMouseEvent('click', 0, 0);
                        selItem = view.all.item(0);

                        // The outer <tr> carries the selected class.
                        // TODO: In 5.x, table-per-row rendering may change this.
                        expect(selItem).toHaveCls(view.selectedItemCls);
                    });
                });
            });

            describe("with editing", function() {
                var cellEditing, col, spy;

                beforeEach(function() {
                    spy = jasmine.createSpy();
                    cellEditing = new Ext.grid.plugin.CellEditing();
                    makeGrid([{
                        name: 'F1',
                        dataIndex: 'field1',
                        field: 'textfield'
                    }], null, {
                        plugins: [cellEditing]
                    });
                    col = grid.getColumnManager().getColumns()[0];
                });

                afterEach(function() {
                    col = spy = cellEditing = null;
                });

                it("should not fire containermousedown when the target is an editor", function() {
                    grid.getView().on('containermousedown', spy);
                    cellEditing.startEditByPosition({
                        row: 0,
                        column: 0
                    });
                    jasmine.fireMouseEvent(col.getEditor().inputEl, 'mousedown');
                    expect(spy).not.toHaveBeenCalled();
                    jasmine.fireMouseEvent(col.getEditor().inputEl, 'mouseup');
                });

                it("should not fire containermouseup when the target is an editor", function() {
                    grid.getView().on('containermouseup', spy);
                    cellEditing.startEditByPosition({
                        row: 0,
                        column: 0
                    });
                    // Touch platforms cannot fire a touchend without a touchstart
                    jasmine.fireMouseEvent(col.getEditor().inputEl, 'mousedown');
                    jasmine.fireMouseEvent(col.getEditor().inputEl, 'mouseup');
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire containerclick when the target is an editor", function() {
                    grid.getView().on('containerclick', spy);
                    cellEditing.startEditByPosition({
                        row: 0,
                        column: 0
                    });
                    jasmine.fireMouseEvent(col.getEditor().inputEl, 'click');
                    expect(spy).not.toHaveBeenCalled();
                });
            });
        });
    }

    createSuite(false);
    createSuite(true);
});
