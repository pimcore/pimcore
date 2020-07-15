topSuite("grid-general-locking-from-no-locking",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore'],
function() {
    var grid, store,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        grid = store = Ext.destroy(grid, store);
    });

    var scrollbarWidth = Ext.getScrollbarSize().width,
        transformStyleName = 'webkitTransform' in document.documentElement.style ? 'webkitTransform' : 'transform',
        scrollbarsTakeSpace = !!scrollbarWidth,
        // Some tests should only be run if the UI shows space-taking scrollbars.
        // Specifically, those tests which test that the presence or not of a scrollbar in one dimension
        // affects the presence of a scrollbar in the other dimension.
        visibleScrollbarsIt = scrollbarsTakeSpace ? it : xit;

        function getViewTop(el) {
            var dom = Ext.getDom(el),
                transform;

            if (Ext.supports.CssTransforms && !Ext.isIE9m) {
                transform = dom.style[transformStyleName];

                return transform ? parseInt(transform.split(',')[1], 10) : 0;
            }
            else {
                return parseInt(dom.style.top || '0', 10);
            }
        }

    describe('Locking a column when grid configured with enableLocking, but no locked columns', function() {
        var scrollY,
            ageColumn,
            nameColumn;

        beforeEach(function() {
            grid = new Ext.grid.Panel({
                renderTo: Ext.getBody(),
                width: 400,
                height: 400,
                title: 'Lock a column',
                columns: [{
                    dataIndex: 'name',
                    text: 'Name'
                }, {
                    dataIndex: 'age',
                    text: 'Age'
                }],
                selType: 'checkboxmodel',

                enableLocking: true,

                store: {
                    fields: [{
                        name: 'name',
                        type: 'string'
                    }, {
                        name: 'age',
                        type: 'int'
                    }],
                    data: (function() {
                        var data = [];

                        var len = 44; // <-- 43 records does not trigger error

                        while (len--) {
                            data.unshift({
                                name: 'User ' + len,
                                age: Ext.Number.randomInt(0, 100)
                            });
                        }

                        return data;
                    })()
                },
                bbar: ['->', Ext.versions.extjs.version]
            });
            ageColumn = grid.down('gridcolumn[text=Age]');
            nameColumn = grid.down('gridcolumn[text=Name]');
        });

        // We are checking that the locked side acquires a scrollbar.
        // This is only when regular DOM scrolling is used and there are visible scrollbars
        if (Ext.getScrollbarSize().height) {
            it("should show a horizontal scrollbar on the locked side when the first column is locked", function() {
                nameColumn.setWidth(400);
                grid.lock(ageColumn);

                // The scrollbar holding element must be visible
                expect(grid.lockedScrollbar.isVisible()).toBe(true);
            });
            it("should NOT show a horizontal scrollbar on the locked side when the first column is locked if the normal side has flexed columns", function() {
                nameColumn.flex = 1;
                grid.lock(ageColumn);

                // The scrollbar holding element must be hidden
                expect(grid.lockedScrollbar.isVisible()).toBe(false);
            });
        }

        it('should display the locked side if all columns are locked', function() {
            var width;

            grid.reconfigure([
                {
                    text: 'Locked',
                    dataIndex: 'name',
                    locked: true
                }
            ]);

            width = grid.lockedGrid.view.getWidth();

            expect(width).not.toBe(0);
            expect(grid.normalGrid.view.getX()).toBeGreaterThan(width);
        });

        describe('scrolling with no locked columns', function() {
            var oldOnError = window.onerror;

            it('should not throw an error when scrolled with no locked columns', function() {
                // We can't catch any exceptions thrown by synthetic events,
                // so a standard toThrow() or even try/catch won't do the job
                // here. They will hit onerror though, so use that.
                var errorSpy = jasmine.createSpy(),
                    scrollFinished = false,
                    scroller = grid.getScrollable();

                window.onerror = errorSpy.andCallFake(function() {
                    if (oldOnError) {
                        oldOnError();
                    }
                });

                scroller.on({
                    scrollend: function() {
                        scrollFinished = true;
                    }
                });

                scroller.scrollBy(0, 100);

                waitsFor(function() {
                    return scrollFinished;
                }, 'scroll to be handled', 500);

                // No errors must have been caught
                expect(errorSpy.callCount).toBe(0);
            });
        });

        it('should not throw an error, and should maintain scroll position', function() {
            // Scroll to end (ensureVisible sanitizes the inputs)
            grid.ensureVisible(100);

            // Scroll must have worked.
            expect(grid.view.normalView.bufferedRenderer.getLastVisibleRowIndex()).toBe(grid.store.getCount() - 1);

            // Locked grid is hidden because there are no locked columns
            expect(grid.lockedGrid.isVisible()).toBe(false);

            // Cache vertical scroll pos
            scrollY = grid.normalGrid.view.getScrollY();

            grid.lock(ageColumn);

            // Should result in showing the locked grid
            expect(grid.lockedGrid.isVisible()).toBe(true);

            // Checkbox should have migrated to the locked side.
            expect(grid.lockedGrid.getVisibleColumnManager().getColumns().length).toBe(2);

            // We want nothing more to happen here.
            // We're waiting for a potential erroneous scroll
            waits(10);

            runs(function() {

                // Scroll position should be preserved
                expect(grid.lockedGrid.view.getScrollY()).toBe(scrollY);

                grid.unlock(ageColumn);

                // Should result in hiding the locked grid
                expect(grid.lockedGrid.isVisible()).toBe(false);

                // Checkbox should have migrated to the normal side.
                expect(grid.normalGrid.getVisibleColumnManager().getColumns().length).toBe(3);
            });

            // We want nothing more to happen here.
            // We're waiting for a potential erroneous scroll
            waits(10);

            runs(function() {

                // Scroll position should be preserved
                expect(grid.normalGrid.view.getScrollY()).toBe(scrollY);
            });
        });
    });

});
