/* global Ext, expect */

topSuite("Ext.menu.Bar",
    ['Ext.Button', 'Ext.layout.container.boxOverflow.Menu'],
function() {
    var menu;

    function makeMenuBar(config) {
        config = Ext.apply({
            renderTo: Ext.getBody(),
            width: 300,
            items: [{
                text: 'File',
                menu: [
                    { text: 'Open' }, '-', { text: 'Close', disabled: true }, { text: 'Save' }
                ]
            }, {
                text: 'Edit',
                disabled: true,
                menu: [
                    { text: 'Cut' }, { text: 'Copy' }, { text: 'Paste' }
                ]
            }, {
                text: 'View'
            }]
        }, config);

        return menu = new Ext.menu.Bar(config);
    }

    afterEach(function() {
        Ext.destroy(menu);
    });

    describe("keyboard interaction", function() {
        var beforeBtn, afterBtn, fileItem, fileMenu, openItem,
            editItem, editMenu, cutItem, viewItem;

        beforeEach(function() {
            beforeBtn = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'before'
            });

            makeMenuBar();

            afterBtn = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'after'
            });

            fileItem = menu.down('[text=File]');
            editItem = menu.down('[text=Edit]');
            viewItem = menu.down('[text=View]');

            fileMenu = fileItem.menu;
            editMenu = editItem.menu;

            openItem = fileMenu.down('[text=Open]');
            cutItem = editMenu.down('[text=Cut]');
        });

        afterEach(function() {
            Ext.destroy(beforeBtn, afterBtn);
            beforeBtn = afterBtn = fileItem = fileMenu = openItem = null;
            editItem = editMenu = cutItem = viewItem = null;
        });

        describe("arrow keys", function() {
            describe("right", function() {
                it("should focus Edit menu", function() {
                    pressKey(fileItem, 'right');

                    expectFocused(editItem);
                });

                it("should focus View item", function() {
                    pressKey(editItem, 'right');

                    expectFocused(viewItem);
                });

                it("should wrap over", function() {
                    pressKey(viewItem, 'right');

                    expectFocused(fileItem);
                });
            });

            describe("left", function() {
                it("should focus File menu", function() {
                    pressKey(editItem, 'left');

                    expectFocused(fileItem);
                });

                it("should focus Edit menu", function() {
                    pressKey(viewItem, 'left');

                    expectFocused(editItem);
                });

                it("should wrap over", function() {
                    pressKey(fileItem, 'left');

                    expectFocused(viewItem);
                });
            });

            describe("up", function() {
                it("should open File menu", function() {
                    pressKey(fileItem, 'up');

                    runs(function() {
                        expect(fileMenu.isVisible()).toBe(true);
                    });
                });

                it("should focus Open item", function() {
                    pressKey(fileItem, 'up');

                    runs(function() {
                        expectFocused(openItem, true);
                    });
                });

                it("should not open disabled Edit menu", function() {
                    pressKey(editItem, 'up');

                    runs(function() {
                        expect(editMenu.isVisible()).toBe(false);
                    });
                });

                it("should not move focus from disabled Edit item", function() {
                    pressKey(editItem, 'up');

                    expectFocused(editItem);
                });

                it("should not move focus from View item", function() {
                    pressKey(viewItem, 'up');

                    expectFocused(viewItem);
                });
            });

            describe("down", function() {
                it("should open File menu", function() {
                    pressKey(fileItem, 'down');

                    runs(function() {
                        expect(fileMenu.isVisible()).toBe(true);
                    });
                });

                it("should focus Open item", function() {
                    pressKey(fileItem, 'down');

                    runs(function() {
                        expectFocused(openItem, true);
                    });
                });

                it("should not open disabled Edit menu", function() {
                    pressKey(editItem, 'down');

                    runs(function() {
                        expect(editMenu.isVisible()).toBe(false);
                    });
                });

                it("should not move focus from disabled Edit item", function() {
                    pressKey(editItem, 'down');

                    expectFocused(editItem);
                });

                it("should not move focus from View item", function() {
                    pressKey(viewItem, 'down');

                    expectFocused(viewItem);
                });
            });
        });

        describe("Space key", function() {
            it("should open File menu", function() {
                pressKey(fileItem, 'space');

                runs(function() {
                    expect(fileMenu.isVisible()).toBe(true);
                });
            });

            it("should focus Open item", function() {
                pressKey(fileItem, 'space');

                runs(function() {
                    expectFocused(openItem, true);
                });
            });

            it("should not open disabled Edit menu", function() {
                pressKey(editItem, 'space');

                runs(function() {
                    expect(editMenu.isVisible()).toBe(false);
                });
            });

            it("should not move focus from disabled Edit item", function() {
                pressKey(editItem, 'space');

                expectFocused(editItem);
            });

            it("should not move focus from View item", function() {
                pressKey(viewItem, 'space');

                expectFocused(viewItem);
            });
        });

        describe("Enter key", function() {
            it("should open File menu", function() {
                pressKey(fileItem, 'enter');

                runs(function() {
                    expect(fileMenu.isVisible()).toBe(true);
                });
            });

            it("should focus Open item", function() {
                pressKey(fileItem, 'enter');

                runs(function() {
                    expectFocused(openItem, true);
                });
            });

            it("should not open disabled Edit menu", function() {
                pressKey(editItem, 'enter');

                runs(function() {
                    expect(editMenu.isVisible()).toBe(false);
                });
            });

            it("should not move focus from disabled Edit item", function() {
                pressKey(editItem, 'enter');

                expectFocused(editItem);
            });

            it("should not move focus from View item", function() {
                pressKey(viewItem, 'enter');

                expectFocused(viewItem);
            });
        });
    });

    describe("ARIA", function() {
        beforeEach(function() {
            makeMenuBar();
        });

        it("should have menubar role", function() {
            expect(menu).toHaveAttr('role', 'menubar');
        });
    });
});
