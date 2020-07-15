topSuite("grid-general-window",
    [false, 'Ext.grid.Panel', 'Ext.data.ArrayStore', 'Ext.window.Window'],
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

    describe("In a Window", function() {
        var win, grid, cell00;

        afterEach(function() {
            Ext.destroy(win);
        });
        it("should hide the window on ESC in navigable mode", function() {
            win = new Ext.window.Window({
                title: 'Test',
                height: 300,
                width: 400,
                layout: 'fit',
                items: {
                    xtype: 'grid',
                    store: new Ext.data.ArrayStore({
                        data: [
                            [ 1, 'Lorem'],
                            [ 2, 'Ipsum'],
                            [ 3, 'Dolor']
                        ],
                        fields: ['row', 'lorem']
                    }),
                    columns: [{
                        text: 'Row',
                        dataIndex: 'row',
                        locked: true,
                        width: 50
                    }, {
                        text: 'Lorem',
                        dataIndex: 'lorem'
                    }]
                }
            });

            win.show();
            grid = win.child('grid');

            cell00 = new Ext.grid.CellContext(grid.view).setPosition(0, 0);

            // Focus cell 0,0
            cell00.getCell(true).focus();

            // Wait for focus
            waitsFor(function() {
                return Ext.Element.getActiveElement() === cell00.getCell(true);
            }, 'cell 0,0 to be focused');

            runs(function() {
                jasmine.fireKeyEvent(cell00.getCell(true), 'keydown', Ext.event.Event.ESC);

                // ESC should have bubbled to the window and destroyed it
                expect(win.destroyed).toBe(true);
            });
        });
    });

});
