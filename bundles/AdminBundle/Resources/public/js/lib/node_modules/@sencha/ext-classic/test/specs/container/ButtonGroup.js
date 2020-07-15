topSuite("Ext.container.ButtonGroup", ['Ext.button.Button', 'Ext.button.Split'], function() {
    var group;

    afterEach(function() {
        if (group) {
            group.destroy();
            group = null;
        }
    });

    function createButtonGroup(config) {
        // ARIA warnings are expected
        spyOn(Ext.log, 'warn');

        group = new Ext.container.ButtonGroup(config || {});

        return group;
    }

    function makeGroup(cfg) {
        cfg = Ext.apply({
            renderTo: Ext.getBody()
        }, cfg);

        return createButtonGroup(cfg);
    }

    describe("alternate class name", function() {
        it("should have Ext.ButtonGroup as the alternate class name", function() {
            expect(Ext.container.ButtonGroup.prototype.alternateClassName).toEqual("Ext.ButtonGroup");
        });

        it("should allow the use of Ext.ButtonGroup", function() {
            expect(Ext.ButtonGroup).toBeDefined();
        });
    });

    describe("Structure and creation", function() {
        it("should extend Ext.Panel", function() {
            expect(createButtonGroup() instanceof Ext.Panel).toBeTruthy();
        });

        it("should allow instantiation via the 'buttongroup' xtype", function() {
            var panel = new Ext.Panel({
                items: [{
                    xtype: 'buttongroup'
                }]
            });

            expect(panel.items.getAt(0) instanceof Ext.container.ButtonGroup).toBeTruthy();

            panel.destroy();
        });
    });

    describe("Layout", function() {
        it("should default to table layout", function() {
            createButtonGroup();
            expect(group.getLayout() instanceof Ext.layout.container.Table).toBeTruthy();
        });

        it("should allow overriding the layout", function() {
            createButtonGroup({
                layout: { type: 'hbox' }
            });
            expect(group.getLayout() instanceof Ext.layout.container.HBox).toBeTruthy();
        });

        // TODO: move this spec to a future TableLayout test suite
        xit("should default to one table row", function() {
            createButtonGroup({
                items: [{}, {}, {}, {}, {}, {}, {}, {}, {}, {}],
                renderTo: Ext.getBody()
            });

            expect(group.el.select('tr').getCount()).toEqual(1);
            expect(group.el.select('tr').item(0).select('td').getCount()).toEqual(10);
        });

        it("should honor a 'columns' config property", function() {
            createButtonGroup({
                columns: 5,
                items: [{}, {}, {}, {}, {}, {}, {}, {}, {}, {}],
                renderTo: Ext.getBody()
            });

            expect(group.getLayout().columns).toEqual(5);
        });
    });

    describe("Children", function() {
        it("should default child items to an xtype of 'button'", function() {
            createButtonGroup({
                items: [
                    {},
                    { xtype: 'splitbutton' }
                ]
            });

            expect(group.items.getAt(0).xtype).toEqual('button');
            expect(group.items.getAt(1).xtype).toEqual('splitbutton');
        });
    });

    describe("Title", function() {
        it("should have no title by default", function() {
            createButtonGroup({
                renderTo: Ext.getBody()
            });

            expect(group.title).toBeNull();
            expect(group.el.select('.x-btn-group-header').getCount()).toEqual(0);
        });

        it("should allow configuring a title", function() {
            createButtonGroup({
                title: 'Group Title',
                renderTo: Ext.getBody()
            });

            expect(group.header.getTitle().getText()).toEqual('Group Title');
            expect(group.el.select('.x-btn-group-header').getCount()).toEqual(1);
        });
    });

    describe("Element classes", function() {
        it("should have a className of 'x-btn-group-notitle' when no title is configured", function() {
            createButtonGroup({
                renderTo: Ext.getBody()
            });

            expect(group.el).toHaveCls('x-btn-group-notitle');
        });

        it("should not have a className of 'x-btn-group-notitle' when a title is configured", function() {
            createButtonGroup({
                title: 'Group Title',
                renderTo: Ext.getBody()
            });

            expect(group.el).not.toHaveCls('x-btn-group-notitle');
        });

        it("should have a className of 'x-btn-group' by default", function() {
            createButtonGroup({
                renderTo: Ext.getBody()
            });

            expect(group.el).toHaveCls('x-btn-group');
        });

        it("should allow overriding the baseCls", function() {
            createButtonGroup({
                baseCls: 'x-test',
                // x-test doesn't support sass framing we must deactivate frame to do this test in IE
                frame: false,
                renderTo: Ext.getBody()
            });

            expect(group.el).not.toHaveCls('x-btn-group');
            expect(group.el).toHaveCls('x-test');
        });
    });

    describe("Framing", function() {
        it("should default to having a frame", function() {
            createButtonGroup({
                renderTo: Ext.getBody()
            });

            expect(group.frame).toBeTruthy();

            expect(group.el).toHaveCls('x-btn-group-default-framed');
        });

        it("should allow turning off the frame", function() {
            createButtonGroup({
                frame: false,
                renderTo: Ext.getBody()
            });

            expect(group.frame).toBeFalsy();
            expect(group.el).not.toHaveCls('x-btn-group-default-framed');
        });
    });

    describe("ARIA", function() {
        describe("general", function() {
            beforeEach(function() {
                makeGroup();
            });

            it("should be a FocusableContainer", function() {
                expect(group.focusableContainer).toBe(true);
            });

            it("should have presentation role on main el", function() {
                expect(group.el).toHaveAttr('role', 'presentation');
            });

            it("should have toolbar role on body el", function() {
                expect(group.body).toHaveAttr('role', 'toolbar');
            });
        });

        describe("labels", function() {
            describe("with header", function() {
                beforeEach(function() {
                    makeGroup({
                        title: 'frobbe'
                    });
                });

                it("should have aria-labelledby", function() {
                    expect(group.body).toHaveAttr('aria-labelledby', group.header.titleCmp.textEl.id);
                });

                it("should not have aria-label", function() {
                    expect(group.body).not.toHaveAttr('aria-label');
                });
            });

            describe("with title but no header", function() {
                beforeEach(function() {
                    makeGroup({
                        title: 'bonzo',
                        header: false
                    });
                });

                it("should have aria-label", function() {
                    expect(group.body).toHaveAttr('aria-label', 'bonzo');
                });

                it("should not have aria-labelledby", function() {
                    expect(group.body).not.toHaveAttr('aria-labelledby');
                });
            });

            describe("with title but header updated to none", function() {
                beforeEach(function() {
                    makeGroup({
                        title: 'throbbe',
                        header: false
                    });

                    group.setIconCls('guzzard');
                });

                it("should have aria-label", function() {
                    expect(group.body).toHaveAttr('aria-label', 'throbbe');
                });

                it("should not have aria-labelledby", function() {
                    expect(group.body).not.toHaveAttr('aria-labelledby');
                });
            });

            describe("no header no title", function() {
                beforeEach(function() {
                    makeGroup();
                });

                it("should not have aria-labelledby", function() {
                    expect(group.body).not.toHaveAttr('aria-labelledby');
                });

                it("should not have aria-label", function() {
                    expect(group.body).not.toHaveAttr('aria-label');
                });
            });
        });
    });
});
