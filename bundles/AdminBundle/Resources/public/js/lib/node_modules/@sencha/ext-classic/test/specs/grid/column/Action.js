/* global Ext, jasmine, expect, spyOn, topSuite */
/* eslint indent: off */

topSuite("Ext.grid.column.Action",
    ['Ext.grid.Panel', 'Ext.window.MessageBox'],
function() {
    var store, grid, view, actionColumn,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function getCell(rowIdx, colIdx) {
        return grid.getView().getCellInclusive({
            row: rowIdx,
            column: colIdx
        }, true);
    }

    function getActionItem(rowIdx, colIdx, itemIdx) {
        var cell = getCell(rowIdx || 0, colIdx || 1);

        var items = cell.querySelectorAll('.' + Ext.grid.column.Action.prototype.actionIconCls);

        return items[itemIdx || 0];
    }

    function triggerAction(type, row, colIdx) {
        var cell = getCell(row || 0, colIdx || 1);

        jasmine.fireMouseEvent(cell.querySelector('.' + Ext.grid.column.Action.prototype.actionIconCls), type || 'click');

        return cell;
    }

    function makeGrid(gridCfg, storeCfg, actionHandler) {
        store = new Ext.data.Store(Ext.apply({
            fields: ['text', 'actionCls'],
            data: [{
                text: 'text',
                actionCls: 'x-form-clear-trigger'
            }],
            autoDestroy: true
        }, storeCfg || {}));

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: [{
                dataIndex: 'text',
                header: 'Text'
            }, {
                xtype: 'actioncolumn',
                dataIndex: 'actionCls',
                header: 'Action',
                renderer: Ext.emptyFn,
                items: [{
                    handler: actionHandler || Ext.emptyFn,
                    isActionDisabled: Ext.emptyFn
                }]
            }],
            renderTo: Ext.getBody()
        }, gridCfg || {}));

        view = grid.view;
        actionColumn = grid.columnManager.getHeaderByDataIndex('actionCls');
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        store = grid = view = actionColumn = Ext.destroy(grid);
    });

    describe('Actioning items from actionable mode', function() {
        var handlerSpy, actionableSpy, navModel, cellEl, actionItemEl, msgBox;

        beforeEach(function() {
            handlerSpy = jasmine.createSpy('action handler');
            handlerSpy.andCallFake(function() {
                msgBox = Ext.MessageBox.alert('Title', 'Message');
            });

            makeGrid({
                columns: [{
                    dataIndex: 'text',
                    header: 'Text'
                }, {
                    xtype: 'actioncolumn',
                    dataIndex: 'actionCls',
                    header: 'Action',
                    renderer: Ext.emptyFn,
                    items: [{
                        handler: handlerSpy,
                        isActionDisabled: Ext.emptyFn
                    }, {
                        handler: Ext.emptyFn,
                        isActionDisabled: Ext.emptyFn
                    }]
                }]
            });

            actionableSpy = spyOn(grid, 'setActionableMode').andCallThrough();

            navModel = grid.getNavigationModel();
            cellEl = grid.getView().getCell(0, 1);

            // This is a bit hacky but so is Action column :(
            actionItemEl = cellEl.querySelector('[role=button]');
        });

        afterEach(function() {
            if (msgBox) {
                msgBox.hide();
            }

            handlerSpy = actionableSpy = navModel = cellEl = actionItemEl = msgBox = null;
        });

        it('should refocus the action item upon focus reversion when action item focuses outwards', function() {
            // Navigate and enter actionable mode
            pressKey(cellEl, 'enter');

            waitsFor(function() {
                return actionableSpy.callCount === 1 && view.cellFocused;
            }, 'actionable mode to start');

            runs(function() {
                // Check that worked
                expect(grid.actionableMode).toBe(true);
                expect(Ext.Element.getActiveElement()).toBe(actionItemEl);
            });

            // Activate the item.
            pressKey(actionItemEl, 'space');

            waitForSpy(handlerSpy, 'action handler to be called');

            runs(function() {
                expect(msgBox).toBeDefined();
            });

            // MsgBox window must contains focus
            waitsFor(function() {
                return msgBox.isVisible() === true && msgBox.containsFocus;
            }, 'message box to show and focus');

            runs(function() {
                expect(Ext.getCmp(msgBox.id)).toBe(msgBox);

                // Hide the message box
                msgBox.hide();
            });

            waitAWhile();

            // Should revert focus back into grid in same mode that it left.
            waitsFor(function() {
                return grid.actionableMode;
            }, 'grid to return to actionable mode');

            runs(function() {
                // SHould revert focus back into grid in same mode that it left.
                expect(grid.actionableMode).toBe(true);
            });

            // Focus should have reverted back to the action item
            waitsFor(function() {
                return Ext.Element.getActiveElement() === actionItemEl;
            }, 'focus to return to the action item');
        });
    });

    describe("quicktips", function() {
        it("should be able to render html", function() {
            makeGrid({
                columns: [{
                    dataIndex: 'text',
                    header: 'Text'
                }, {
                    xtype: 'actioncolumn',
                    dataIndex: 'actionCls',
                    header: 'Action',
                    renderer: Ext.emptyFn,
                    items: [{
                        iconCls: 'x-fa fa-cog',
                        getTip: function(value, metadata, record) {
                            return record.get('tip');
                        }
                    }]
                }]
            }, {
                data: [{
                    text: 'foo',
                    tip: '<b>foo</b>'
                }]
            });

            expect(getActionItem(0, 1).getAttribute('data-qtip')).toBe('<b>foo</b>');
        });

        it("should not render encoded html as html", function() {
            makeGrid({
                columns: [{
                    dataIndex: 'text',
                    header: 'Text'
                }, {
                    xtype: 'actioncolumn',
                    dataIndex: 'actionCls',
                    header: 'Action',
                    items: [{
                        iconCls: 'x-fa fa-cog',
                        getTip: function(value, metadata, record) {
                            return record.get('tip');
                        }
                    }]
                }]
            }, {
                data: [{
                    text: 'foo',
                    tip: '&lt;b&gt;foo&lt;/b&gt;'
                }]
            });

            expect(getActionItem(0, 1).getAttribute('data-qtip')).toBe('&lt;b&gt;foo&lt;/b&gt;');
        });
    });

    describe('events', function() {
        var handled = false;

        beforeEach(function() {
            makeGrid({
                columns: [{
                    dataIndex: 'text',
                    header: 'Text'
                }, {
                    xtype: 'actioncolumn',
                    dataIndex: 'actionCls',
                    header: 'Action',
                    items: [{
                        handler: function() {
                            handled = true;
                        }
                    }]
                }]
            });
        });

        afterEach(function() {
            handled = false;
        });

        it('should process click events', function() {
            triggerAction('click');
            expect(handled).toBe(true);
        });

        it('should not process mousedown events', function() {
            triggerAction('mousedown');
            expect(handled).toBe(false);
            triggerAction('mouseup');
        });
    });

    it("should not be sortable if there is no dataIndex even if sortable: true", function() {
        makeGrid({
            sortableColumns: true,
            columns: [{
                dataIndex: 'text',
                header: 'Text'
            }, {
                xtype: 'actioncolumn',
                handler: Ext.emptyFn
            }]
        });
        var columns = grid.query('gridcolumn');

        expect(columns[0].sortable).toBe(true);
        expect(columns[1].sortable).toBe(false);
    });

    describe("getClass", function() {
        it("should use the dataIndex and pass it to getClass", function() {
            var handlerSpy = jasmine.createSpy(),
                classSpy = jasmine.createSpy();

            makeGrid({
                columns: [{
                    dataIndex: 'text',
                    header: 'Text'
                }, {
                    xtype: 'actioncolumn',
                    dataIndex: 'actionCls',
                    header: 'Action',
                    items: [{
                        getClass: classSpy.andReturn('x-form-clear-trigger'),
                        handler: handlerSpy
                    }]
                }]
            });

            expect(classSpy.mostRecentCall.args[0]).toBe('x-form-clear-trigger');
            triggerAction();
            expect(handlerSpy).toHaveBeenCalled();
        });
    });

    describe('focus', function() {
        it('should not select and focus the row when clicking the action item', function() {
            // See EXTJSIV-11177.
            var cell;

            makeGrid();
            cell = triggerAction();

            expect(grid.selModel.isSelected(grid.view.getRecord(cell))).toBe(false);
        });
    });

    describe("stopSelection", function() {
        it("should not select the row when clicking the action with stopSelection: true", function() {
            makeGrid({
                columns: [{}, {
                    xtype: 'actioncolumn',
                    stopSelection: true,
                    dataIndex: 'actionCls',
                    header: 'Action',
                    items: [{
                        handler: Ext.emptyFn
                    }]
                }]
            });
            triggerAction();
            expect(grid.getSelectionModel().getSelection().length).toBe(0);
        });

        it("should select the row & focus the cell when clicking the action with stopSelection: false", function() {
            var isTouch;

            makeGrid({
                columns: [{}, {
                    xtype: 'actioncolumn',
                    stopSelection: false,
                    dataIndex: 'actionCls',
                    header: 'Action',
                    items: [{
                        handler: function(view, recordIndex, cellIndex, item, e, record, row) {
                            isTouch = e.pointerType === 'touch';
                        }
                    }]
                }]
            });

            triggerAction();
            expect(grid.getSelectionModel().isSelected(store.first())).toBe(true);

            var pos = grid.view.actionPosition;

            // Touch events do not cause actionable mode
            if (isTouch) {
                expect(pos).not.toBeDefined();
            }
            else {
                expect(pos.record).toBe(store.first());
                expect(pos.column).toBe(grid.down('actioncolumn'));
            }
        });
    });

    describe("handler", function() {
        var spy1, spy2, col, scope1, scope2;

        function makeHandlerGrid(actionCfg) {
            actionCfg = Ext.apply({
                xtype: 'actioncolumn',
                dataIndex: 'actionCls',
                header: 'Action',
                itemId: 'theAction'
            }, actionCfg);
            makeGrid({
                columns: [{
                    dataIndex: 'text',
                    header: 'Text'
                }, actionCfg]
            });
            col = grid.down('#theAction');
        }

        beforeEach(function() {
            spy1 = jasmine.createSpy();
            spy2 = jasmine.createSpy();
            scope1 = {
                foo: function() {}
            };
            scope2 = {
                foo: function() {}
            };
            spyOn(scope1, 'foo');
            spyOn(scope2, 'foo');
        });

        afterEach(function() {
            scope1 = scope2 = col = null;
        });

        it("should not throw an exception if the grid is destroyed in the handler", function() {
            makeHandlerGrid({
                handler: function() {
                    grid.destroy();
                }
            });

            expect(function() {
                triggerAction();
            }).not.toThrow();
         });

        describe("handler priority", function() {
            it("should use a handler on the column", function() {
                makeHandlerGrid({
                    handler: spy1
                });
                triggerAction();
                expect(spy1).toHaveBeenCalled();
            });

            it("should use a handler on the item", function() {
                makeHandlerGrid({
                    items: [{
                        handler: spy1
                    }]
                });
                triggerAction();
                expect(spy1).toHaveBeenCalled();
            });

            it("should favour the handler on the item", function() {
                makeHandlerGrid({
                    handler: spy1,
                    items: [{
                        handler: spy2
                    }]
                });
                triggerAction();
                expect(spy1).not.toHaveBeenCalled();
                expect(spy2).toHaveBeenCalled();
            });
        });

        describe("enabled/disabled state", function() {
            it("should not fire the handler if configured as disabled", function() {
                makeHandlerGrid({
                    handler: spy1,
                    items: [{
                        disabled: true,
                        iconCls: 'icon-pencil'
                    }]
                });

                var view   = grid.getView(),
                    rowEl  = view.getNode(0),
                    img    = rowEl.querySelector('.x-action-col-icon'),
                    imgCls = Ext.fly(img).hasCls('x-item-disabled');

                triggerAction();
                expect(spy1).not.toHaveBeenCalled();
                expect(imgCls).toBe(true);
            });

            it("should fire if enabled dynamically", function() {
                makeHandlerGrid({
                    handler: spy1,
                    items: [{
                        disabled: true,
                        iconCls: 'icon-pencil'
                    }]
                });

                var view   = grid.getView(),
                    rowEl  = view.getNode(0),
                    img    = rowEl.querySelector('.x-action-col-icon'),
                    imgCls = Ext.fly(img).hasCls('x-item-disabled');

                col.enableAction(0);
                triggerAction();
                expect(spy1).toHaveBeenCalled();
                expect(imgCls).toBe(true);
            });

            it("should not fire if disabled dynamically", function() {
                makeHandlerGrid({
                    handler: spy1,
                    items: [{
                    }]
                });

                var view   = grid.getView(),
                    rowEl  = view.getNode(0),
                    img    = Ext.fly(rowEl.querySelector('.x-action-col-icon'));

                expect(img.hasCls('x-item-disabled')).toBe(false);
                col.disableAction(0);
                expect(img.hasCls('x-item-disabled')).toBe(true);

                triggerAction();
                expect(spy1).not.toHaveBeenCalled();
            });
        });

        describe("scoping", function() {
            it("should default the scope to the column", function() {
                makeHandlerGrid({
                    handler: spy1
                });
                triggerAction();
                expect(spy1.mostRecentCall.object).toBe(col);
            });

            describe("with handler on the column", function() {
                it("should use the scope on the column", function() {
                    makeHandlerGrid({
                        handler: spy1,
                        scope: scope1,
                        items: [{
                        }]
                    });
                    triggerAction();
                    expect(spy1.mostRecentCall.object).toBe(scope1);
                });

                it("should use the scope on the item", function() {
                    makeHandlerGrid({
                        handler: spy1,
                        items: [{
                            scope: scope1
                        }]
                    });
                    triggerAction();
                    expect(spy1.mostRecentCall.object).toBe(scope1);
                });

                it("should favour the scope on the item", function() {
                    makeHandlerGrid({
                        handler: spy1,
                        scope: scope1,
                        items: [{
                            scope: scope2
                        }]
                    });
                    triggerAction();
                    expect(spy1.mostRecentCall.object).toBe(scope2);
                });
            });

            describe("with handler on the item", function() {
                it("should use the scope on the column", function() {
                    makeHandlerGrid({
                        scope: scope1,
                        items: [{
                            handler: spy1
                        }]
                    });
                    triggerAction();
                    expect(spy1.mostRecentCall.object).toBe(scope1);
                });

                it("should use the scope on the item", function() {
                    makeHandlerGrid({
                        items: [{
                            handler: spy1,
                            scope: scope1
                        }]
                    });
                    triggerAction();
                    expect(spy1.mostRecentCall.object).toBe(scope1);
                });

                it("should favour the scope on the item", function() {
                    makeHandlerGrid({
                        scope: scope1,
                        items: [{
                            handler: spy1,
                            scope: scope2
                        }]
                    });
                    triggerAction();
                    expect(spy1.mostRecentCall.object).toBe(scope2);
                });
            });
        });

        describe("string handler", function() {
            describe("handler on the column", function() {
                it("should lookup a scope on the column", function() {
                    makeHandlerGrid({
                        scope: scope1,
                        handler: 'foo',
                        items: [{}]
                    });
                    triggerAction();
                    expect(scope1.foo).toHaveBeenCalled();
                });

                it("should lookup a scope on the item", function() {
                    makeHandlerGrid({
                        handler: 'foo',
                        items: [{
                            scope: scope1
                        }]
                    });
                    triggerAction();
                    expect(scope1.foo).toHaveBeenCalled();
                });

                it("should favour the scope on the item", function() {
                    makeHandlerGrid({
                        handler: 'foo',
                        scope: scope1,
                        items: [{
                            scope: scope2
                        }]
                    });
                    triggerAction();
                    expect(scope1.foo).not.toHaveBeenCalled();
                    expect(scope2.foo).toHaveBeenCalled();
                });
            });

            describe("handler on the item", function() {
                it("should lookup a scope on the column", function() {
                    makeHandlerGrid({
                        scope: scope1,
                        items: [{
                            handler: 'foo'
                        }]
                    });
                    triggerAction();
                    expect(scope1.foo).toHaveBeenCalled();
                });

                it("should lookup a scope on the item", function() {
                    makeHandlerGrid({
                        items: [{
                            handler: 'foo',
                            scope: scope1
                        }]
                    });
                    triggerAction();
                    expect(scope1.foo).toHaveBeenCalled();
                });

                it("should favour the scope on the item", function() {
                    makeHandlerGrid({
                        scope: scope1,
                        items: [{
                            handler: 'foo',
                            scope: scope2
                        }]
                    });
                    triggerAction();
                    expect(scope1.foo).not.toHaveBeenCalled();
                    expect(scope2.foo).toHaveBeenCalled();
                });
            });

            describe("no scope", function() {
                it("should resolve the scope", function() {
                    makeHandlerGrid({
                        handler: 'foo'
                    });

                    col.resolveListenerScope = function() {
                        return scope2;
                    };

                    triggerAction();
                    expect(scope2.foo).toHaveBeenCalled();
                });
            });
        });

        it("should pass view, rowIdx, cellIndex, item, e, record, row", function() {
            makeHandlerGrid({
                handler: spy1
            });
            triggerAction();
            var args = spy1.mostRecentCall.args;

            expect(args[0]).toBe(grid.getView());
            expect(args[1]).toBe(0);
            expect(args[2]).toBe(1);
            expect(args[3].dataIndex).toBe('actionCls');
            expect(args[4] instanceof Ext.event.Event).toBe(true);
            expect(args[5]).toBe(store.first());
            expect(args[6]).toBe(grid.getView().getRow(0));
        });
    });

    describe("destroy", function() {
        describe("as a subclass with items on the class", function() {
            var Cls = Ext.define(null, {
                extend: 'Ext.grid.column.Action',
                items: [{
                    iconCls: 'foo'
                }]
            });

            it("should not cause an exception when not rendered", function() {
                makeGrid({
                    renderTo: null,
                    columns: [new Cls()]
                });

                expect(function() {
                    grid.destroy();
                }).not.toThrow();
            });

            it("should not cause an exception when rendered", function() {
                makeGrid({
                    columns: [new Cls()]
                });

                expect(function() {
                    grid.destroy();
                }).not.toThrow();
            });
        });

        describe("as a config with items on the class", function() {
            it("should not cause an exception when not rendered", function() {
                makeGrid({
                    renderTo: null
                });

                expect(function() {
                    grid.destroy();
                }).not.toThrow();
            });

            it("should not cause an exception when rendered", function() {
                makeGrid();

                expect(function() {
                    grid.destroy();
                }).not.toThrow();
            });
        });
    });

    describe('callbacks', function() {
        describe('when the model is updated', function() {
            describe('renderers', function() {
                function runTest(method) {
                    it('should call ' + method, function() {
                        makeGrid();
                        spyOn(actionColumn, method).andCallThrough();
                        store.getAt(0).set('text', 'Kilgore Trout');

                        expect(actionColumn[method].callCount).toBe(1);
                    });
                }

                runTest('origRenderer'); // the defined column.renderer
                runTest('defaultRenderer');
            });

            describe('isActionDisabled on items', function() {
                it('should call isActionDisabled', function() {
                    var item;

                    makeGrid();
                    item = actionColumn.items[0];
                    spyOn(item, 'isActionDisabled').andCallThrough();
                    store.getAt(0).set('text', 'Kilgore Trout');

                    expect(item.isActionDisabled.callCount).toBe(1);
                });
            });
        });
    });

    describe("ARIA", function() {
        describe("tabIndex", function() {
            it("should default to 0", function() {
                makeGrid();

                var item = getActionItem(0, 1);

                expect(item).toHaveAttr('tabIndex', '0');
            });

            it("should be overridable via column config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        itemTabIndex: -1,
                        items: [{}, {}]
                    }]
                });

                var item0 = getActionItem(0, 1, 0),
                    item1 = getActionItem(0, 1, 1);

                expect(item0).toHaveAttr('tabIndex', '-1');
                expect(item0).toHaveAttr('tabIndex', '-1');
            });

            it("should be removable via column config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        itemTabIndex: null,
                        items: [{}, {}]
                    }]
                });

                var item0 = getActionItem(0, 1, 0),
                    item1 = getActionItem(0, 1, 1);

                expect(item0).not.toHaveAttr('tabIndex');
                expect(item0).not.toHaveAttr('tabIndex');
            });

            it("should be overridable via item config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        items: [{
                            tabIndex: -1
                        }]
                    }]
                });

                var item = getActionItem(0, 1);

                expect(item).toHaveAttr('tabIndex', '-1');
            });

            it("should be removable via item config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        items: [{
                            tabIndex: null
                        }]
                    }]
                });

                var item = getActionItem(0, 1);

                expect(item).not.toHaveAttr('tabIndex');
            });

            it("should be overridable for multiple items separately", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        items: [{
                            tabIndex: null
                        }, {
                            tabIndex: 42
                        }, {
                            tabIndex: -1
                        }]
                    }]
                });

                var item0 = getActionItem(0, 1, 0),
                    item1 = getActionItem(0, 1, 1),
                    item2 = getActionItem(0, 1, 2);

                expect(item0).not.toHaveAttr('tabIndex');
                expect(item1).toHaveAttr('tabIndex', '42');
                expect(item2).toHaveAttr('tabIndex', '-1');
            });
        });

        describe("role", function() {
            it("should default to 'button'", function() {
                makeGrid();

                var item = getActionItem(0, 1);

                expect(item).toHaveAttr('role', 'button');
            });

            it("should be overridable via column config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        itemAriaRole: 'bork',
                        items: [{}, {}]
                    }]
                });

                var item0 = getActionItem(0, 1, 0),
                    item1 = getActionItem(0, 1, 1);

                expect(item0).toHaveAttr('role', 'bork');
                expect(item0).toHaveAttr('role', 'bork');
            });

            it("should be removable via column config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        itemAriaRole: null,
                        itemTabIndex: null,
                        items: [{}, {}]
                    }]
                });

                var item0 = getActionItem(0, 1, 0),
                    item1 = getActionItem(0, 1, 1);

                expect(item0).toHaveAttr('role', 'presentation');
                expect(item0).toHaveAttr('role', 'presentation');
            });

            it("should be overridable via item config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        items: [{
                            ariaRole: 'blerg'
                        }]
                    }]
                });

                var item = getActionItem(0, 1);

                expect(item).toHaveAttr('role', 'blerg');
            });

            it("should be removable via item config", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        items: [{
                            ariaRole: null,
                            tabIndex: null
                        }]
                    }]
                });

                var item = getActionItem(0, 1);

                expect(item).toHaveAttr('role', 'presentation');
            });

            it("should be overridable for multiple items separately", function() {
                makeGrid({
                    columns: [{
                        dataIndex: 'text',
                        header: 'Text'
                    }, {
                        xtype: 'actioncolumn',
                        dataIndex: 'actionCls',
                        header: 'Action',
                        items: [{
                            ariaRole: null
                        }, {
                            ariaRole: ''
                        }, {
                            ariaRole: 'throbbe'
                        }]
                    }]
                });

                var item0 = getActionItem(0, 1, 0),
                    item1 = getActionItem(0, 1, 1),
                    item2 = getActionItem(0, 1, 2);

                expect(item0).toHaveAttr('role', 'presentation');
                expect(item1).toHaveAttr('role', 'presentation');
                expect(item2).toHaveAttr('role', 'throbbe');
            });
        });
    });

    it("should not fail when using contains()", function() {
        makeGrid({
            columns: [{
                xtype: 'actioncolumn',
                dataIndex: 'actionCls'
            }]
        });
        expect(actionColumn.contains(grid)).toBe(false);
    });
});
