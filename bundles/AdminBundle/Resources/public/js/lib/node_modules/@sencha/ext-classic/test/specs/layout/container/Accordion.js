topSuite("Ext.layout.container.Accordion",
    ['Ext.panel.Panel', 'Ext.button.Button', 'Ext.form.field.Text', 'Ext.layout.container.Fit'],
function() {
    describe("single item", function() {
        var panel, child;

        function makePanel(multi, fill) {
            panel = new Ext.panel.Panel({
                width: 100,
                height: 100,
                layout: {
                    type: 'accordion',
                    animate: false,
                    multi: multi,
                    fill: fill === false ? false : true
                },
                defaults: {
                    animCollapseDuration: 100
                },
                items: [{
                    title: 'Child Panel'
                }],
                renderTo: Ext.getBody()
            });

            child = panel.items.getAt(0);
        }

        afterEach(function() {
            panel.destroy();
        });

        describe("single collapse", function() {
            beforeEach(function() {
                makePanel();
            });

            it("should not allow the item to collapse", function() {
                child.collapse();
                expect(child.collapsed).toBe(false);
            });
        });

        describe("multi collapse", function() {
            beforeEach(function() {
                makePanel(true);
            });

            it("should not allow the item to collapse", function() {
                child.collapse();
                expect(child.collapsed).toBe(false);
            });
        });

    });

    describe("dynamic items", function() {
        var ct, makeCt, expectCollapsed, expectExpanded;

        beforeEach(function() {
            makeCt = function(items, isMulti) {
                ct = new Ext.container.Container({
                    renderTo: document.body,
                    width: 200,
                    height: 400,
                    layout: {
                        type: 'accordion',
                        animate: false,
                        multi: isMulti
                    },
                    items: items
                });
            };

            expectCollapsed = function(index) {
                expect(ct.items.getAt(index).collapsed).toBeTruthy();
            };

            expectExpanded = function(index) {
                expect(ct.items.getAt(index).collapsed).toBeFalsy();
            };
        });

        afterEach(function() {
            Ext.destroy(ct);
            makeCt = ct = expectExpanded = expectCollapsed = null;
        });

        describe("single", function() {
            it("should collapse a dynamic item by default", function() {
                makeCt([{
                    title: 'Default'
                }]);
                var c = new Ext.panel.Panel({
                    title: 'Dynamic'
                });

                ct.add(c);
                expectCollapsed(1);
            });

            it("should be able to expand items that were added dynamically", function() {
                makeCt([{
                    title: 'Default'
                }]);
                var c = new Ext.panel.Panel({
                    title: 'Dynamic'
                });

                ct.add(c);
                c.expand();
                expectCollapsed(0);
                expectExpanded(1);
            });

            it("should not expand other items when adding", function() {
                makeCt([{
                    title: 'Expanded'
                }, {
                    title: 'Static - Collapsed'
                }]);
                ct.add({
                    title: 'Dynamic'
                });
                expectExpanded(0);
                expectCollapsed(1);
                expectCollapsed(2);
            });
        });

        describe("multi", function() {
            it("should leave an item expanded by default", function() {
                makeCt([{
                    title: 'Default'
                }], true);
                var c = new Ext.panel.Panel({
                    title: 'Dynamic'
                });

                ct.add(c);
                expectExpanded(1);
            });

            it("should collapse the item if we specify it explicitly", function() {
                makeCt([{
                    title: 'Default'
                }], true);
                var c = new Ext.panel.Panel({
                    title: 'Dynamic',
                    collapsed: true
                });

                ct.add(c);
                expectCollapsed(1);
            });

            it("should be able to expand items that were added dynamically", function() {
                makeCt([{
                    title: 'Default'
                }], true);
                var c = new Ext.panel.Panel({
                    title: 'Dynamic',
                    collapsed: true
                });

                ct.add(c);
                c.expand();
                expectExpanded(0);
                expectExpanded(1);
            });

            it("should be able to collapse items that were added dynamically", function() {
                makeCt([{
                    title: 'Default'
                }], true);
                var c = new Ext.panel.Panel({
                    title: 'Dynamic',
                    collapsed: true
                });

                ct.add(c);
                c.expand();
                c.collapse();
                expectExpanded(0);
                expectCollapsed(1);
            });
        });
    });

    describe("expand/collapse", function() {

        var ct, makeCt, expectCollapsed, expectExpanded;

        beforeEach(function() {
            makeCt = function(items, multi, fill) {
                ct = new Ext.container.Container({
                    renderTo: document.body,
                    width: 200,
                    height: 400,
                    layout: {
                        type: 'accordion',
                        animate: false,
                        multi: multi,
                        fill: fill === false ? false : true
                    },
                    items: items
                });
            };

            expectCollapsed = function(index) {
                var item = ct.items.getAt(index);

                expect(item.collapsed).toBeTruthy();
            };

            expectExpanded = function(index) {
                var item = ct.items.getAt(index);

                expect(item.collapsed).toBeFalsy();
            };
        });

        afterEach(function() {
            Ext.destroy(ct);
            makeCt = ct = expectExpanded = expectCollapsed = null;
        });

        var tests = function(fill) {
            describe("single", function() {
                it("should expand the first item by default if none are collapsed: false", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3'
                    }], false, fill);
                    expectExpanded(0);
                });

                it("should expand a collapsed: false item by default", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2',
                        collapsed: false
                    }, {
                        title: 'P3'
                    }], false, fill);
                    expectExpanded(1);
                });

                it("should expand the first collapsed: false item by default", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2',
                        collapsed: false
                    }, {
                        title: 'P3'
                    }], false, fill);
                    expectExpanded(1);
                    expectCollapsed(2);
                });

                it("should expand the next item when collapsing an item", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3'
                    }], false, fill);
                    ct.items.first().collapse();
                    expectCollapsed(0);
                    expectExpanded(1);
                });

                it("should expand the previous item when collapsing an item and next is not available", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3',
                        collapsed: false
                    }], false, fill);
                    ct.items.last().collapse();
                    expectCollapsed(2);
                    expectExpanded(1);
                });

                it("should collapse the expanded item when expanding an item", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3'
                    }], false, fill);
                    ct.items.last().expand();
                    expectCollapsed(0);
                    expectExpanded(2);
                });
            });

            describe("multi", function() {
                it("should have each item expanded unless specified as collapsed", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3'
                    }], true, fill);
                    expectExpanded(0);
                    expectExpanded(1);
                    expectExpanded(2);
                });

                it("should collapse any items with collapsed: true", function() {
                    makeCt([{
                        title: 'P1',
                        collapsed: true
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3',
                        collapsed: true
                    }], true, fill);
                    expectExpanded(1);
                });

                it("should not modify other items when collapsing an item", function() {
                    makeCt([{
                        title: 'P1'
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3'
                    }], true, fill);
                    ct.items.getAt(1).collapse();
                    expectExpanded(0);
                    expectCollapsed(1);
                    expectExpanded(2);
                });

                it("should not modify other items when expanding an item", function() {
                    makeCt([{
                        title: 'P1',
                        collapsed: true
                    }, {
                        title: 'P2'
                    }, {
                        title: 'P3',
                        collapsed: true
                    }], true, fill);
                    ct.items.first().expand();
                    expectExpanded(0);
                    expectExpanded(1);
                    expectCollapsed(2);
                });
            });
        };

        // The behaviour for the accordion should be the same for both fill values
        describe("fill: true", function() {
           tests(true);
        });

        describe("fill: false", function() {
            tests(false);
        });
    });

    describe("show/hide", function() {
        var ct, makeCt, expectCollapsed, expectExpanded;

        beforeEach(function() {
            makeCt = function(items) {
                ct = new Ext.container.Container({
                    renderTo: document.body,
                    width: 200,
                    height: 400,
                    layout: {
                        type: 'accordion',
                        animate: false
                    },
                    items: items
                });
            };

            expectCollapsed = function(index) {
                var item = ct.items.getAt(index);

                expect(item.collapsed).toBeTruthy();
                expect(item.getInherited().collapsed).toBeTruthy();
            };

            expectExpanded = function(index) {
                var item = ct.items.getAt(index);

                expect(item.collapsed).toBeFalsy();
                expect(item.getInherited().collapsed).toBeFalsy();
            };
        });

        afterEach(function() {
            Ext.destroy(ct);
            makeCt = ct = expectExpanded = expectCollapsed = null;
        });

        it("should retain the same state when hidden", function() {
            makeCt([{
                title: 'P1'
            }, {
                title: 'P2',
                collapsed: true
            }, {
                title: 'P3',
                collapsed: true
            }]);
            ct.items.first().hide();
            expectExpanded(0);

            ct.items.last().hide();
            expectCollapsed(2);
        });

        it("should not expand when shown when not the first item", function() {
            makeCt([{
                title: 'P1',
                collapsed: true,
                hidden: true
            }, {
                title: 'P2',
                collapsed: true
            }, {
                title: 'P3',
                hidden: true
            }]);
            ct.items.getAt(1).show();
            expectCollapsed(1);

            ct.items.last().show();
            expectCollapsed(2);
        });
    });

    describe("filling", function() {
        var ct,
            h = 300;

        function makeCt(items, multi, fill) {
            ct = new Ext.container.Container({
                width: 100,
                height: h,
                layout: {
                    type: 'accordion',
                    animate: false,
                    multi: multi,
                    fill: fill === false ? false : true
                },
                items: items,
                renderTo: Ext.getBody()
            });
        }

        afterEach(function() {
            ct.destroy();
            ct = null;
        });

        describe("fill: true", function() {
            describe("single", function() {
                it("should stretch the item to the height", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }], false, true);
                    expect(ct.items.first().getHeight()).toBe(h);
                });

                it("should stretch the item to the height - the other panel headers", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }, {
                        title: 'Item 2',
                        html: 'I2'
                    }], false, true);

                    var left = ct.items.last().getHeight();

                    expect(ct.items.first().getHeight()).toBe(h - left);
                });
            });

            describe("multi", function() {
                it("should stretch the item to the height", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }], true, true);
                    expect(ct.items.first().getHeight()).toBe(h);
                });

                it("should stretch the item to the height - the other panel headers", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }, {
                        title: 'Item 2',
                        html: 'I2'
                    }], true, true);
                    var left = ct.items.last().getHeight();

                    expect(ct.items.first().getHeight()).toBe(h - left);
                });

                it("should stretch the both items evenly", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1',
                        collapsed: false
                    }, {
                        title: 'Item 2',
                        html: 'I2',
                        collapsed: false
                    }], true, true);
                    expect(ct.items.first().getHeight()).toBe(h / 2);
                    expect(ct.items.last().getHeight()).toBe(h / 2);
                });
            });
        });

        describe("fill: false", function() {
            describe("single", function() {
                it("should not stretch the item to the height", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }], false, false);
                    // We don't know the exact height, but it should be smaller
                    expect(ct.items.first().getHeight()).toBeLessThan(100);
                });

                it("should not stretch either item height", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }, {
                        title: 'Item 2',
                        html: 'I2'
                    }], false, false);
                    // We don't know the exact height, but it should be smaller
                    expect(ct.items.first().getHeight()).toBeLessThan(100);
                    expect(ct.items.last().getHeight()).toBeLessThan(100);
                });
            });

            describe("multi", function() {
                it("should not stretch the item to the height", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }], true, false);
                    // We don't know the exact height, but it should be smaller
                    expect(ct.items.first().getHeight()).toBeLessThan(100);
                });

                it("should not stretch either item height", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1'
                    }, {
                        title: 'Item 2',
                        html: 'I2'
                    }], true, false);
                    // We don't know the exact height, but it should be smaller
                    expect(ct.items.first().getHeight()).toBeLessThan(100);
                    expect(ct.items.last().getHeight()).toBeLessThan(100);
                });

                it("should not stretch either item", function() {
                    makeCt([{
                        title: 'Item 1',
                        html: 'I1',
                        collapsed: false
                    }, {
                        title: 'Item 2',
                        html: 'I2',
                        collapsed: false
                    }], true, false);
                    expect(ct.items.first().getHeight()).toBeLessThan(100);
                    expect(ct.items.last().getHeight()).toBeLessThan(100);
                });
            });
        });
    });

    describe("collapseFirst", function() {
        var makePanel, panel,
            tools = [{
                type: 'print'
            }, {
                type: 'refresh'
            }];

        beforeEach(function() {
            makePanel = function(items, collapseFirst) {
                panel = new Ext.panel.Panel({
                    width: 100,
                    height: 100,
                    layout: {
                        type: 'accordion',
                        animate: false,
                        collapseFirst: collapseFirst
                    },
                    items: items,
                    renderTo: Ext.getBody()
                });
            };
        });

        afterEach(function() {
            Ext.destroy(panel);
            makePanel = panel = null;
        });

        it("should use the collapseFirst option on the child items as a default", function() {
            makePanel([{
                collapseFirst: true,
                title: 'A',
                tools: tools
            }, {
                collapseFirst: false,
                title: 'B',
                tools: tools
            }]);
            var p1 = panel.items.first(),
                p2 = panel.items.last();

            expect(p1.tools[0].type).toBe('collapse-top');
            expect(p1.tools[1].type).toBe('print');
            expect(p1.tools[2].type).toBe('refresh');

            expect(p2.tools[0].type).toBe('print');
            expect(p2.tools[1].type).toBe('refresh');
            expect(p2.tools[2].type).toBe('expand-bottom');
        });

        it("should use the collapseFirst: false on the layout", function() {
             makePanel([{
                title: 'A',
                tools: tools
            }, {
                title: 'B',
                tools: tools
            }], false);

            var p1 = panel.items.first(),
                p2 = panel.items.last();

            expect(p1.tools[0].type).toBe('print');
            expect(p1.tools[1].type).toBe('refresh');
            expect(p1.tools[2].type).toBe('collapse-top');

            expect(p2.tools[0].type).toBe('print');
            expect(p2.tools[1].type).toBe('refresh');
            expect(p2.tools[2].type).toBe('expand-bottom');
        });

        it("should use the collapseFirst: true on the layout", function() {
             makePanel([{
                title: 'A',
                tools: tools
            }, {
                title: 'B',
                tools: tools
            }], true);

            var p1 = panel.items.first(),
                p2 = panel.items.last();

            expect(p1.tools[0].type).toBe('collapse-top');
            expect(p1.tools[1].type).toBe('print');
            expect(p1.tools[2].type).toBe('refresh');

            expect(p2.tools[0].type).toBe('expand-bottom');
            expect(p2.tools[1].type).toBe('print');
            expect(p2.tools[2].type).toBe('refresh');
        });

    });

    describe("activeOnTop", function() {

        var makePanel, panel;

        beforeEach(function() {
            makePanel = function(items, collapseFirst) {
                panel = new Ext.panel.Panel({
                    width: 100,
                    height: 100,
                    layout: {
                        type: 'accordion',
                        animate: false,
                        activeOnTop: true
                    },
                    items: items,
                    renderTo: Ext.getBody()
                });
            };
        });

        afterEach(function() {
            Ext.destroy(panel);
            makePanel = panel = null;
        });

        it("should move initial active item to the top", function() {
            var c1 = new Ext.panel.Panel({
                    title: 'A'
                }),
                c2 = new Ext.panel.Panel({
                    title: 'B'
                }),
                c3 = new Ext.panel.Panel({
                    title: 'C',
                    collapsed: false
                });

            makePanel([c1, c2, c3]);
            expect(panel.items.indexOf(c3)).toBe(0);
        });

        it("should move the item to the top when expanded", function() {
            var c1 = new Ext.panel.Panel({
                    title: 'A'
                }),
                c2 = new Ext.panel.Panel({
                    title: 'B'
                }),
                c3 = new Ext.panel.Panel({
                    title: 'C'
                });

            makePanel([c1, c2, c3]);
            c3.expand();
            expect(panel.items.indexOf(c3)).toBe(0);
        });

        it("should move the active item to the top when a new item is inserted above it", function() {
            var c1 = new Ext.panel.Panel({
                    title: 'A'
                }),
                c2 = new Ext.panel.Panel({
                    title: 'B'
                }),
                c3 = new Ext.panel.Panel({
                    title: 'C'
                }),
                newItem;

            makePanel([c1, c2, c3]);
            newItem = panel.insert(0, {});
            expect(panel.items.indexOf(c1)).toBe(0);
            expect(panel.items.indexOf(newItem)).toBe(1);
        });

    });

    describe("removing items", function() {
        it("should expand the first item with multi: false & removing the expanded item", function() {
            var ct = new Ext.container.Container({
                width: 200,
                height: 200,
                layout: {
                    type: 'accordion',
                    animate: false
                },
                items: [{
                    title: 'A'
                }, {
                    title: 'B'
                }, {
                    title: 'C'
                }]
            });

            ct.remove(0);
            expect(ct.items.first().collapsed).toBe(false);
            ct.destroy();
        });

        it("should not attempt to expand any items when destroying the container", function() {
            var count = 0;

            var ct = new Ext.container.Container({
                width: 200,
                height: 200,
                layout: {
                    type: 'accordion',
                    animate: false
                },
                items: [{
                    title: 'A'
                }, {
                    title: 'B'
                }, {
                    title: 'C'
                }]
            });

            ct.items.each(function(item) {
                item.on('expand', function() {
                    ++count;
                });
            });

            ct.destroy();
            expect(count).toBe(0);
        });
    });

    describe("misc", function() {
        it("should expand inside a panel", function() {
            var p = new Ext.panel.Panel({
                layout: {
                    type: 'accordion',
                    animate: false
                },
                items: [{
                    title: 'P1'
                }, {
                    title: 'P2'
                }, {
                    title: 'P3'
                }]
            });

            var outer = new Ext.panel.Panel({
                width: 200,
                height: 200,
                layout: 'fit',
                renderTo: Ext.getBody(),
                items: p
            });

            p.getComponent(1).expand();
            expect(p.getComponent(0).collapsed).toBe('top');
            outer.destroy();
        });
    });

    describe("ARIA attributes", function() {
        function makeSuite(name, animate, options) {
            describe(name + ", animate: " + !!animate, function() {
                var ct, foo, bar, pinTool, closeTool;

                beforeAll(function(done) {
                    ct = new Ext.container.Container({
                        renderTo: Ext.getBody(),
                        width: 400,
                        height: 200,

                        style: {
                            border: '1px solid red'
                        },

                        layout: {
                            type: 'accordion',
                            animate: !!animate
                        },
                        defaults: {
                            animCollapseDuration: 100
                        },
                        items: [{
                            title: 'foo',
                            collapsible: true,
                            animCollapse: animate,
                            tools: [{
                                type: 'pin'
                            }]
                        }, {
                            title: 'bar',
                            collapsible: true,
                            animCollapse: animate,
                            closable: true
                        }]
                    });

                    foo = ct.down('[title=foo]');
                    bar = ct.down('[title=bar]');

                    pinTool = foo.down('tool[type=pin]');
                    closeTool = bar.down('tool[type=close]');

                    if (options.collapse) {
                        var cb = function() {
                            // Let the stack unwind!
                            var setTimeout = jasmine._setTimeout;

                            setTimeout(done, 1);
                        };

                        if (!options.expand) {
                            foo.on('collapse', cb, null, { single: true });
                        }
                        else {
                            foo.on('collapse', function() {
                                foo.on('expand', cb, null, { single: true });
                                foo.expand();
                            }, null, { single: true });
                        }

                        foo.collapse();
                    }
                    else {
                        done();
                    }
                });

                afterAll(function() {
                    Ext.destroy(ct);
                    ct = foo = bar = pinTool = closeTool = null;
                });

                describe("container", function() {
                    it("should have presentation role", function() {
                        expect(ct).toHaveAttr('role', 'presentation');
                    });

                    describe("innerCt", function() {
                        it("should have tablist role", function() {
                            expect(ct.layout.innerCt).toHaveAttr('role', 'tablist');
                        });

                        it("should have aria-multiselectable", function() {
                            expect(ct.layout.innerCt).toHaveAttr('aria-multiselectable', 'true');
                        });
                    });
                });

                describe("foo panel", function() {
                    it("should have presentation role on main el", function() {
                        expect(foo.el).toHaveAttr('role', 'presentation');
                    });

                    describe("header", function() {
                        describe("title", function() {
                            it("should have tab role", function() {
                                expect(foo.header.titleCmp).toHaveAttr('role', 'tab');
                            });

                            it("should have tabindex", function() {
                                expect(foo.header.titleCmp.ariaEl.isTabbable()).toBe(true);
                            });

                            it("should have aria-expanded", function() {
                                expect(foo.header.titleCmp).toHaveAttr('aria-expanded', !foo.collapsed + '');
                            });
                        });

                        describe("collapse tool", function() {
                            it("should have presentation role", function() {
                                expect(foo.collapseTool).toHaveAttr('role', 'presentation');
                            });

                            it("should not be tabbable", function() {
                                expect(foo.collapseTool.el.isTabbable()).toBe(false);
                            });

                            it("should not be focusable, either", function() {
                                expect(foo.collapseTool.isFocusable()).toBe(false);
                            });
                        });

                        describe("pin tool", function() {
                            it("should have button role", function() {
                                expect(pinTool).toHaveAttr('role', 'button');
                            });

                            it("should be tabbable", function() {
                                expect(pinTool.el.isTabbable()).toBe(true);
                            });
                        });
                    });

                    describe("body", function() {
                        it("should have tabpanel role", function() {
                            expect(foo.body).toHaveAttr('role', 'tabpanel');
                        });

                        it("should be aria-labelledby the header title", function() {
                            expect(foo.body).toHaveAttr('aria-labelledby', foo.header.titleCmp.id);
                        });

                        it("should have aria-hidden", function() {
                            expect(foo.body).toHaveAttr('aria-hidden', !!foo.collapsed + '');
                        });

                        it("should not have tabindex", function() {
                            expect(foo.body).not.toHaveAttr('tabIndex');
                        });
                    });
                });

                describe("bar panel", function() {
                    it("should have presentation role on main el", function() {
                        expect(bar.el).toHaveAttr('role', 'presentation');
                    });

                    describe("header", function() {
                        describe("title", function() {
                            it("should have tab role", function() {
                                expect(bar.header.titleCmp).toHaveAttr('role', 'tab');
                            });

                            it("should have tabindex", function() {
                                expect(bar.header.titleCmp.ariaEl.isTabbable()).toBe(true);
                            });

                            it("should have aria-expanded", function() {
                                expect(bar.header.titleCmp).toHaveAttr('aria-expanded', !bar.collapsed + '');
                            });
                        });

                        describe("collapse tool", function() {
                            it("should have presentation role", function() {
                                expect(bar.collapseTool).toHaveAttr('role', 'presentation');
                            });

                            it("should not be tabbable", function() {
                                expect(bar.collapseTool.el.isTabbable()).toBe(false);
                            });

                            it("should not be focusable, either", function() {
                                expect(bar.collapseTool.isFocusable()).toBe(false);
                            });
                        });

                        describe("close tool", function() {
                            it("should have presentation role", function() {
                                expect(closeTool).toHaveAttr('role', 'presentation');
                            });

                            it("should not be tabbable", function() {
                                expect(closeTool.ariaEl.isTabbable()).toBe(false);
                            });

                            it("should not be focusable, either", function() {
                                expect(closeTool.ariaEl.isFocusable()).toBe(false);
                            });
                        });
                    });

                    describe("body", function() {
                        it("should have tabpanel role", function() {
                            expect(bar.body).toHaveAttr('role', 'tabpanel');
                        });

                        it("should be aria-labelledby the header title", function() {
                            expect(bar.body).toHaveAttr('aria-labelledby', bar.header.titleCmp.id);
                        });

                        it("should have aria-hidden attribute", function() {
                            expect(bar.body).toHaveAttr('aria-hidden', !!bar.collapsed + '');
                        });

                        it("should not have tabindex", function() {
                            expect(bar.body).not.toHaveAttr('tabIndex');
                        });
                    });
                });
            });
        }

        makeSuite('rendered', false,  {});
        makeSuite('collapsed', 100,   { collapse: true });
        makeSuite('collapsed', false, { collapse: true });
        makeSuite('expanded',  100,   { collapse: true, expand: true });
        makeSuite('expanded',  false, { collapse: true, expand: true });
    });

    describe("interaction", function() {
        function makeSuite(animate) {
            describe("animate: " + !!animate, function() {
                var ct, foo, bar, fooHdr, barHdr, pinTool, closeTool, okBtn,
                    beforeInput, afterInput, fooInnerInput, barInnerInput,
                    collapseSpy, expandSpy;

                beforeEach(function() {
                    collapseSpy = jasmine.createSpy('collapse');
                    expandSpy = jasmine.createSpy('expand');

                    beforeInput = new Ext.form.field.Text({
                        renderTo: Ext.getBody(),
                        fieldLabel: 'before'
                    });

                    ct = new Ext.container.Container({
                        renderTo: Ext.getBody(),
                        width: 400,
                        height: 150,

                        style: {
                            border: '1px solid red'
                        },

                        layout: {
                            type: 'accordion',
                            animate: animate
                        },
                        defaults: {
                            animCollapseDuration: 100
                        },
                        items: [{
                            title: 'foo',
                            collapsible: true,
                            animCollapse: animate,
                            tools: [{
                                type: 'pin'
                            }],
                            listeners: {
                                collapse: collapseSpy,
                                expand: expandSpy
                            },
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: 'foo inner'
                            }],
                            buttons: [{
                                text: 'OK'
                            }]
                        }, {
                            title: 'bar',
                            collapsible: true,
                            animCollapse: animate,
                            closable: true,
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: 'bar inner'
                            }]
                        }]
                    });

                    afterInput = new Ext.form.field.Text({
                        renderTo: Ext.getBody(),
                        fieldLabel: 'after'
                    });

                    foo = ct.down('[title=foo]');
                    bar = ct.down('[title=bar]');

                    fooHdr = foo.header;
                    barHdr = bar.header;

                    pinTool = foo.down('tool[type=pin]');
                    closeTool = bar.down('tool[type=close]');

                    fooInnerInput = foo.down('textfield');
                    okBtn = foo.down('button[text=OK]');
                    barInnerInput = bar.down('textfield');
                });

                afterEach(function() {
                    Ext.destroy(beforeInput, ct, afterInput);
                    ct = foo = bar = pinTool = closeTool = okBtn = null;
                    beforeInput = afterInput = fooInnerInput = barInnerInput = null;
                    collapseSpy = expandSpy = null;
                });

                describe("pointer", function() {
                    describe("title collapse", function() {
                        beforeEach(function() {
                            jasmine.fireMouseEvent(barHdr, 'click');

                            waitsForSpy(collapseSpy, 'collapse');
                        });

                        it("should collapse foo", function() {
                            expect(!!foo.collapsed).toBe(true);
                        });

                        it("should expand bar", function() {
                            expect(!!bar.collapsed).toBe(false);
                        });

                        describe("expand", function() {
                            beforeEach(function() {
                                jasmine.fireMouseEvent(barHdr, 'click');

                                waitsForSpy(expandSpy, 'expand');
                            });

                            it("should expand foo", function() {
                                expect(!!foo.collapsed).toBe(false);
                            });

                            it("should collapse bar", function() {
                                expect(!!bar.collapsed).toBe(true);
                            });
                        });
                    });

                    describe("tool collapse", function() {
                        beforeEach(function() {
                            jasmine.fireMouseEvent(foo.collapseTool, 'click');

                            waitsForSpy(collapseSpy, 'collapse');
                        });

                        it("should collapse foo", function() {
                            expect(!!foo.collapsed).toBe(true);
                        });

                        it("should expand bar", function() {
                            expect(!!bar.collapsed).toBe(false);
                        });
                    });

                    describe("tool close", function() {
                        it("should close bar", function() {
                            jasmine.fireMouseEvent(closeTool, 'click');

                            expect(ct.items.length).toBe(1);
                        });
                    });
                });

                describe("keyboard", function() {
                    var fooTitle, barTitle;

                    beforeEach(function() {
                        fooTitle = foo.header.titleCmp;
                        barTitle = bar.header.titleCmp;
                    });

                    afterEach(function() {
                        fooTitle = barTitle = null;
                    });

                    describe("arrow keys", function() {
                        describe("down arrow", function() {
                            it("should go from foo to bar", function() {
                                pressKey(fooTitle, 'down');

                                expectFocused(barTitle);
                            });

                            it("should wrap over from bar to foo", function() {
                                pressKey(barTitle, "down");

                                expectFocused(fooTitle);
                            });

                            it("should not wrap over when accordionWrapOver == false", function() {
                                bar.accordionWrapOver = false;

                                pressKey(barTitle, 'down');

                                expectFocused(barTitle);
                            });
                        });

                        describe("right arrow", function() {
                            it("should go from foo to bar", function() {
                                pressKey(fooTitle, 'right');

                                expectFocused(barTitle);
                            });

                            it("should wrap over from bar to foo", function() {
                                pressKey(barTitle, "right");

                                expectFocused(fooTitle);
                            });

                            it("should not wrap over when accordionWrapOver == false", function() {
                                bar.accordionWrapOver = false;

                                pressKey(barTitle, 'right');

                                expectFocused(barTitle);
                            });
                        });

                        describe("up arrow", function() {
                            it("should go from bar to foo", function() {
                                pressKey(barTitle, 'up');

                                expectFocused(fooTitle);
                            });

                            it("should wrap over from foo to bar", function() {
                                pressKey(fooTitle, 'up');

                                expectFocused(barTitle);
                            });

                            it("should not wrap over when accordionWrapOver == false", function() {
                                foo.accordionWrapOver = false;

                                pressKey(fooTitle, 'up');

                                expectFocused(fooTitle);
                            });
                        });

                        describe("left arrow", function() {
                            it("should go from bar to foo", function() {
                                pressKey(barTitle, 'left');

                                expectFocused(fooTitle);
                            });

                            it("should wrap over from foo to bar", function() {
                                pressKey(fooTitle, 'left');

                                expectFocused(barTitle);
                            });

                            it("should not wrap over when accordionWrapOver == false", function() {
                                foo.accordionWrapOver = false;

                                pressKey(fooTitle, 'up');

                                expectFocused(fooTitle);
                            });
                        });
                    });

                    describe("home/end keys", function() {
                        it("should go to foo", function() {
                            pressKey(barTitle, 'home');

                            expectFocused(fooTitle);
                        });

                        it("should stay on foo", function() {
                            pressKey(fooTitle, 'home');

                            expectFocused(fooTitle);
                        });

                        it("should go to bar", function() {
                            pressKey(fooTitle, 'end');

                            expectFocused(barTitle);
                        });

                        it("should stay on bar", function() {
                            pressKey(barTitle, 'end');

                            expectFocused(barTitle);
                        });
                    });

                    describe("del key", function() {
                        // Del with no modifiers should be ignored
                        describe("no modifiers", function() {
                            it("should not close foo", function() {
                                pressKey(fooTitle, 'delete');

                                runs(function() {
                                    expect(ct.items.length).toBe(2);
                                    expect(foo.destroyed).toBe(false);
                                });
                            });

                            it("should not close bar", function() {
                                pressKey(barTitle, 'delete');

                                runs(function() {
                                    expect(ct.items.length).toBe(2);
                                    expect(bar.destroyed).toBe(false);
                                });
                            });
                        });

                        describe("alt-del", function() {
                            it("should not close foo", function() {
                                pressKey(fooTitle, 'delete', { alt: true });

                                // foo is not closable, so ignore alt-del
                                runs(function() {
                                    expect(ct.items.length).toBe(2);
                                    expect(foo.destroyed).toBe(false);
                                });
                            });

                            it("should close bar", function() {
                                pressKey(barTitle, 'delete', { alt: true });

                                runs(function() {
                                    expect(ct.items.length).toBe(1);
                                    expect(bar.destroyed).toBe(true);
                                });
                            });
                        });
                    });

                    describe("within panel body", function() {
                        describe("Ctrl-Up", function() {
                            it("should go to foo title from foo input", function() {
                                pressKey(fooInnerInput, 'up', { ctrl: true });

                                expectFocused(fooTitle);
                            });

                            it("should go to foo title from ok button", function() {
                                pressKey(okBtn, 'up', { ctrl: true });

                                expectFocused(fooTitle);
                            });

                            it("should go to bar title from bar input", function() {
                                foo.collapse();

                                waitForSpy(collapseSpy);

                                pressKey(barInnerInput, 'up', { ctrl: true });

                                expectFocused(barTitle);
                            });
                        });
                    });

                    describe("tab key", function() {
                        describe("forward", function() {
                            describe("foo expanded", function() {
                                it("should go from before input to foo title", function() {
                                    pressTabKey(beforeInput, true);

                                    expectFocused(fooTitle);
                                });

                                it("should go from foo title to pin tool", function() {
                                    pressTabKey(fooTitle, true);

                                    expectFocused(pinTool);
                                });

                                it("should go from pin tool to foo inner input", function() {
                                    pressTabKey(pinTool, true);

                                    expectFocused(fooInnerInput);
                                });

                                it("should go from foo inner input to ok button", function() {
                                    pressTabKey(fooInnerInput, true);

                                    expectFocused(okBtn);
                                });

                                it("should go from ok button to bar title", function() {
                                    pressTabKey(okBtn, true);

                                    expectFocused(barTitle);
                                });

                                it("should go from bar title to after input", function() {
                                    pressTabKey(barTitle, true);

                                    expectFocused(afterInput);
                                });
                            });

                            describe("foo collapsed", function() {
                                beforeEach(function() {
                                    foo.collapse();

                                    waitForSpy(collapseSpy, 'collapse');
                                });

                                it("should go from before input to foo title", function() {
                                    pressTabKey(beforeInput, true);

                                    expectFocused(fooTitle);
                                });

                                it("should go from foo title to pin tool", function() {
                                    pressTabKey(fooTitle, true);

                                    expectFocused(pinTool);
                                });

                                it("should go from pin tool to bar title", function() {
                                    pressTabKey(pinTool, true);

                                    expectFocused(barTitle);
                                });

                                it("should go from bar title to bar inner input", function() {
                                    pressTabKey(barTitle, true);

                                    expectFocused(barInnerInput);
                                });

                                it("should go from bar inner input to after input", function() {
                                    pressTabKey(barInnerInput, true);

                                    expectFocused(afterInput);
                                });
                            });
                        });

                        describe("backward", function() {
                            describe("foo expanded", function() {
                                it("should go from after input to bar title", function() {
                                    pressTabKey(afterInput, false);

                                    expectFocused(barTitle);
                                });

                                it("should go from bar title to ok button", function() {
                                    pressTabKey(barTitle, false);

                                    expectFocused(okBtn);
                                });

                                it("should go from ok button to foo inner input", function() {
                                    pressTabKey(okBtn, false);

                                    expectFocused(fooInnerInput);
                                });

                                it("should go from foo inner input to pin tool", function() {
                                    pressTabKey(fooInnerInput, false);

                                    expectFocused(pinTool);
                                });

                                it("should go from pin tool to foo title", function() {
                                    pressTabKey(pinTool, false);

                                    expectFocused(fooTitle);
                                });

                                it("should go from foo title to before input", function() {
                                    pressTabKey(fooTitle, false);

                                    expectFocused(beforeInput);
                                });
                            });

                            describe("foo collapsed", function() {
                                beforeEach(function() {
                                    foo.collapse();

                                    waitForSpy(collapseSpy, 'collapse');
                                });

                                it("should go from after input to bar inner input", function() {
                                    pressTabKey(afterInput, false);

                                    expectFocused(barInnerInput);
                                });

                                it("should go from bar inner input to bar title", function() {
                                    pressTabKey(barInnerInput, false);

                                    expectFocused(barTitle);
                                });

                                it("should go from bar title to pin tool", function() {
                                    pressTabKey(barTitle, false);

                                    expectFocused(pinTool);
                                });

                                it("should go from pin tool to foo title", function() {
                                    pressTabKey(pinTool, false);

                                    expectFocused(fooTitle);
                                });

                                it("should go from foo title to before input", function() {
                                    pressTabKey(fooTitle, false);

                                    expectFocused(beforeInput);
                                });
                            });
                        });
                    });
                });
            });
        }

        makeSuite(100);
        makeSuite(false);
    });
});
