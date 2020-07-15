topSuite('Ext.Action', ['Ext.Panel', 'Ext.Button', 'Ext.app.ViewController'], function() {
    var panel, action, cmp1, cmp2;

    function makeAction(cfg) {
        cfg = Ext.apply({
            xtype: 'button',
            text: 'Foo'
        }, cfg);

        action = new Ext.Action(cfg);
    }

    afterEach(function() {
        panel = action = cmp1 = cmp2 = Ext.destroy(panel, cmp1, cmp2, action);
    });

    describe('invoking Action handlers on click', function() {
        it('should work when using "@actionname"', function() {
            var ActionsController = Ext.define(null, {
                    extend: 'Ext.app.ViewController',

                    onOperationClick: function() {
                        Ext.Msg.alert('Click', 'Perform the operation');
                    },

                    onOperationToggle: function(btn, pressed) {
                        this.view.getAction('operation').setDisabled(pressed);
                    }
                }),
                ActionsPanel = Ext.define(null, {
                    extend: 'Ext.panel.Panel',
                    controller: new ActionsController(),

                    title: 'Actions',
                    width: 500,
                    height: 300,

                    // Define the shared Actions.  Each Component below will have the same
                    // display text and icon, and will display the same message on click.
                    actions: {
                        operation: {
                            text: 'Do operation',
                            handler: 'onOperationClick',
                            glyph: 'xf005@FontAwesome',
                            tooltip: 'Perform the operation'
                        },
                        disableOperation: {
                            text: 'Disable operation',
                            enableToggle: true,
                            toggleHandler: 'onOperationToggle',
                            tooltip: 'Disable the operation'
                        }
                    },

                    // Added Actions are interpreted as Buttons in this view
                    defaultActionType: 'button',

                    tools: [
                        '@operation'
                    ],

                    tbar: [
                        // Add the Action directly to a toolbar as a menu button
                        '@operation',
                        {
                            text: 'Action Menu',
                            menu: [
                                // Add the Action to a menu as a text item
                                '@operation'
                            ]
                        }, '@disableOperation'
                    ],

                    bodyPadding: 10,
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    items: [
                        // Add the Action to the panel body.
                        // defaultActionType will ensure it is converted to a Button.
                        '@operation'
                    ]
                });

            panel = new ActionsPanel({
                renderTo: Ext.getBody()
            });

            var toolbar = panel.child('toolbar'),
                tool = panel.tools[0],
                button = toolbar.child('[text=Do operation]'),
                menuButton = toolbar.child('[text=Action Menu]'),
                menuItem = toolbar.down('menuitem'),
                toggleDisable = toolbar.child('[text=Disable operation]');

            spyOn(panel.controller, 'onOperationClick');

            jasmine.fireMouseEvent(tool.el, 'click');
            expect(panel.controller.onOperationClick.callCount).toBe(1);

            jasmine.fireMouseEvent(button.el, 'click');
            expect(panel.controller.onOperationClick.callCount).toBe(2);

            menuButton.onClick();
            jasmine.fireMouseEvent(menuItem.el, 'click');
            expect(panel.controller.onOperationClick.callCount).toBe(3);

            // Click the toggle button to disable the action.
            // All associated components must be disabled.
            jasmine.fireMouseEvent(toggleDisable.el, 'click');
            expect(tool.disabled).toBe(true);
            expect(button.disabled).toBe(true);
            expect(menuItem.disabled).toBe(true);

            // Click the toggle button to enable the action.
            // All associated components must be enabled.
            jasmine.fireMouseEvent(toggleDisable.el, 'click');
            expect(tool.disabled).toBe(false);
            expect(button.disabled).toBe(false);
            expect(menuItem.disabled).toBe(false);
        });

        it('should work when using an Action instance', function() {
            // Define the shared Actions.  Each Component below will have the same
            // display text and icon, and will display the same message on click.
            var operationAction = new Ext.Action({
                    text: 'Do operation',
                    handler: 'onOperationClick',
                    glyph: 'xf005@FontAwesome',
                    tooltip: 'Perform the operation'
                }),
                disableOperationAction = new Ext.Action({
                    text: 'Disable operation',
                    enableToggle: true,
                    toggleHandler: 'onOperationToggle',
                    tooltip: 'Disable the operation'
                }),
                ActionsController = Ext.define(null, {
                    extend: 'Ext.app.ViewController',

                    onOperationClick: function() {
                        Ext.Msg.alert('Click', 'Perform the operation');
                    },

                    onOperationToggle: function(btn, pressed) {
                        operationAction.setDisabled(pressed);
                    }
                }),
                ActionsPanel = Ext.define(null, {
                    extend: 'Ext.panel.Panel',
                    controller: new ActionsController(),

                    title: 'Actions',
                    width: 500,
                    height: 300,

                    // Added Actions are interpreted as Buttons in this view
                    defaultActionType: 'button',

                    tools: [
                        operationAction
                    ],

                    tbar: [
                        // Add the Action directly to a toolbar as a menu button
                        operationAction,
                        {
                            text: 'Action Menu',
                            menu: [
                                // Add the Action to a menu as a text item
                                operationAction
                            ]
                        }, disableOperationAction
                    ],

                    bodyPadding: 10,
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    items: [
                        // Add the Action to the panel body.
                        // defaultActionType will ensure it is converted to a Button.
                        operationAction
                    ]
                });

            panel = new ActionsPanel({
                renderTo: Ext.getBody()
            });

            var toolbar = panel.child('toolbar'),
                tool = panel.tools[0],
                button = toolbar.child('[text=Do operation]'),
                menuButton = toolbar.child('[text=Action Menu]'),
                menuItem = toolbar.down('menuitem'),
                toggleDisable = toolbar.child('[text=Disable operation]');

            spyOn(panel.controller, 'onOperationClick');

            jasmine.fireMouseEvent(tool.el, 'click');
            expect(panel.controller.onOperationClick.callCount).toBe(1);

            jasmine.fireMouseEvent(button.el, 'click');
            expect(panel.controller.onOperationClick.callCount).toBe(2);

            menuButton.onClick();
            jasmine.fireMouseEvent(menuItem.el, 'click');
            expect(panel.controller.onOperationClick.callCount).toBe(3);

            // Click the toggle button to disable the action.
            // All associated components must be disabled.
            jasmine.fireMouseEvent(toggleDisable.el, 'click');
            expect(tool.disabled).toBe(true);
            expect(button.disabled).toBe(true);
            expect(menuItem.disabled).toBe(true);

            // Click the toggle button to enable the action.
            // All associated components must be enabled.
            jasmine.fireMouseEvent(toggleDisable.el, 'click');
            expect(tool.disabled).toBe(false);
            expect(button.disabled).toBe(false);
            expect(menuItem.disabled).toBe(false);
        });
    });

    describe("helpers", function() {
        beforeEach(function() {
            makeAction();
            cmp1 = new Ext.button.Button(action);
        });

        describe("disable", function() {
            it("should disable instances", function() {
                action.disable();
                expect(cmp1.isDisabled()).toBe(true);
            });
        });

        describe("enable", function() {
            it("should enable instances", function() {
                cmp1.setDisabled(true);
                action.enable();
                expect(cmp1.isDisabled()).toBe(false);
            });
        });

        describe("hide", function() {
            it("should hide instances", function() {
                cmp1.setHidden(false);
                action.hide();
                expect(cmp1.isHidden()).toBe(true);
            });
        });

        describe("show", function() {
            it("should show instances", function() {
                cmp1.setHidden(true);
                action.show();
                expect(cmp1.isHidden()).toBe(false);
            });
        });

        describe("each", function() {
            it("should execute the specified function on each instance", function() {
                var spy = jasmine.createSpy();

                cmp2 = new Ext.button.Button(action);
                action.each(spy);

                expect(spy.callCount).toBe(2);
            });
        });

        describe("execute", function() {
            it("should execute the handler on each instance with the passed argments", function() {
                var spy = jasmine.createSpy();

                action.setHandler(spy);
                action.execute('foo', 'bar', 'baz');

                expect(spy).toHaveBeenCalledWith('foo', 'bar', 'baz');
            });
        });
    });

    describe("setters", function() {
        beforeEach(function() {
            makeAction();
            cmp1 = new Ext.button.Button(action);
            cmp2 = new Ext.button.Button(action);
        });

        describe("setText", function() {
            it("should update initialConfig text value", function() {
                action.setText('Bar');

                expect(action.initialConfig.text).toBe('Bar');
            });

            it("should update text of all instances", function() {
                action.setText('Bar');

                expect(cmp1.getText()).toBe('Bar');
                expect(cmp2.getText()).toBe('Bar');
            });
        });

        describe("setDisabled", function() {
            it("should update initialConfig disabled value", function() {
                action.setDisabled(true);

                expect(action.initialConfig.disabled).toBe(true);
            });

            it("should update disabled state of all instances", function() {
                action.setDisabled(true);

                expect(cmp1.isDisabled()).toBe(true);
                expect(cmp2.isDisabled()).toBe(true);
            });
        });

        describe("setHidden", function() {
            it("should update initialConfig hidden value", function() {
                action.setHidden(true);

                expect(action.initialConfig.hidden).toBe(true);
            });

            it("should update hidden state of all instances", function() {
                action.setHidden(true);

                expect(cmp1.isVisible()).toBe(false);
                expect(cmp2.isVisible()).toBe(false);
            });
        });

        describe("setIconCls", function() {
            it("should update initialConfig iconCls value", function() {
                action.setIconCls('fa fa-truck');

                expect(action.initialConfig.iconCls).toBe('fa fa-truck');
            });

            it("should update iconCls of all instances", function() {
                action.setIconCls('fa fa-truck');

                expect(cmp1.iconCls).toBe('fa fa-truck');
                expect(cmp2.iconCls).toBe('fa fa-truck');
            });
        });

        describe("setHandler", function() {
            it("should update initialConfig handler value", function() {
                var fn  = Ext.emptyFn,
                    fn2 = Ext.emptyFn,
                    scope = new Ext.container.Container();

                action.setHandler(fn);
                expect(action.initialConfig.handler).toBe(fn);

                action.setHandler(fn2, scope);
                expect(action.initialConfig.handler).toBe(fn2);
                expect(action.initialConfig.scope).toBe(scope);

                Ext.destroy(scope);
            });

            it("should update the handler of all instances", function() {
                var fn = Ext.emptyFn,
                    scope = new Ext.container.Container();

                // just update handler
                action.setHandler(fn);
                expect(cmp1.handler).toBe(fn);

                // update handler and scope
                action.setHandler(fn, scope);
                expect(cmp1.scope).toBe(scope);

                Ext.destroy(scope);
            });
        });
    });

    describe("component management", function() {
        beforeEach(function() {
            makeAction();
        });

        it("addComponent should add passed instance to items", function() {
            cmp1 = new Ext.button.Button();
            cmp2 = new Ext.button.Button();

            action.addComponent(cmp1);
            action.addComponent(cmp2);

            expect(action.items.length).toBe(2);
        });

        it("removeComponent should removed passed instance from items", function() {
            cmp1 = new Ext.button.Button(action);
            cmp2 = new Ext.button.Button(action);

            action.removeComponent(cmp1);

            expect(action.items.length).toBe(1);
        });

        it("should remove component when component is destroyed", function() {
            cmp1 = new Ext.button.Button(action);
            cmp2 = new Ext.button.Button(action);

            cmp1.destroy();

            expect(action.items.length).toBe(1);
        });
    });

    describe("references", function() {
        it("should be registered if it has a reference", function() {
            var item, ct, vc;

            Ext.define('spec.TestController1', {
                extend: 'Ext.app.ViewController',
                alias: 'controller.myvc'
            });

            makeAction({
                reference: 'foo'
            });

            // test as config
            ct = new Ext.panel.Panel({
                tbar: [action],
                controller: 'myvc'
            });

            item = ct.getDockedItems()[0].items.getAt(0);

            expect(ct.lookupReference('foo')).toBe(item);

            ct = item = Ext.destroy(ct, item);

            // test as instance
            ct = new Ext.panel.Panel({
                items: [new Ext.button.Button(action)],
                controller: 'myvc'
            });

            item = ct.items.getAt(0);

            expect(ct.lookupReference('foo')).toBe(item);

            Ext.destroy(ct, item);
            Ext.undefine('spec.TestController1');
        });
    });
});
