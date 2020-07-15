topSuite("Ext.form.FieldSet",
    ['Ext.window.Window', 'Ext.form.Panel', 'Ext.form.field.Text', 'Ext.data.Session'],
function() {
    var component;

    function makeComponent(config, preventRender) {
        config = config || {};

        Ext.apply(config, {
            renderTo: preventRender ? undefined : Ext.getBody(),
            name: 'test'
        });

        component = new Ext.form.FieldSet(config);
    }

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = null;
    });

    describe('collapsibility', function() {
        var ct,
            failedLayouts;

        beforeEach(function() {
            failedLayouts = Ext.failedLayouts;
        });

        afterEach(function() {
            Ext.destroy(ct);
            ct = null;
        });

        it("should update the hierarchy state when expanding", function() {
            var c = new Ext.Component();

            makeComponent({
                collapsed: true,
                collapsible: true,
                title: 'Foo',
                items: c
            });
            expect(c.isVisible(true)).toBe(false);
            component.expand();
            expect(c.isVisible(true)).toBe(true);
        });

        it("should update the hierarchy state when collapsing", function() {
            var c = new Ext.Component();

            makeComponent({
                collapsible: true,
                title: 'Foo',
                items: c
            });
            expect(c.isVisible(true)).toBe(true);
            component.collapse();
            expect(c.isVisible(true)).toBe(false);
        });

        it('should allow creating as collapsed', function() {
            ct = Ext.widget({
                xtype: 'window',
                title: 'Test',
                autoShow: true,
                shadow: false,
                items: {
                    xtype: 'fieldset',
                    collapsed: true,
                    collapsible: true,
                    title: 'Text',
                    items: {
                        width: 100,
                        height: 100
                    }
                }
            });

            // eslint-disable-next-line eqeqeq
            if (failedLayouts != Ext.failedLayouts) {
                expect('failedLayout=true').toBe('false');
            }
        });

        it("should be able to start collapsed with a minHeight", function() {
            ct = new Ext.container.Container({
                width: 550,
                height: 300,
                items: [{
                    xtype: 'fieldset',
                    title: 'Show Panel',
                    collapsible: true,
                    collapsed: true,
                    minHeight: 200,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: 'Text'
                    }]
                }],
                renderTo: Ext.getBody()
            });

            // eslint-disable-next-line eqeqeq
            if (failedLayouts != Ext.failedLayouts) {
                expect('failedLayout=true').toBe('false');
            }

            ct.destroy();
        });

        it("should be able to collapse with a minHeight", function() {
            ct = new Ext.container.Container({
                width: 550,
                height: 300,
                items: [{
                    xtype: 'fieldset',
                    title: 'Show Panel',
                    collapsible: true,
                    collapsed: true,
                    minHeight: 200,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: 'Text'
                    }]
                }],
                renderTo: Ext.getBody()
            });

            ct.items.first().collapse();

            // eslint-disable-next-line eqeqeq
            if (failedLayouts != Ext.failedLayouts) {
                expect('failedLayout=true').toBe('false');
            }

            ct.destroy();
        });

        it("should expand to the minHeight after being collapsed", function() {
            ct = new Ext.container.Container({
                width: 550,
                height: 300,
                items: [{
                    xtype: 'fieldset',
                    title: 'Show Panel',
                    collapsible: true,
                    collapsed: true,
                    minHeight: 200,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: 'Text'
                    }]
                }],
                renderTo: Ext.getBody()
            });

            var fs = ct.items.first();

            fs.collapse();
            fs.expand();
            expect(fs.getHeight()).toBe(200);
            ct.destroy();
        });

        it("should be able to be shrink wrap collapsed in a box layout", function() {
            var ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                layout: 'vbox',
                items: [{
                    xtype: 'fieldset',
                    title: '<div style="width: 180px;">a</div>',
                    collapsed: true
                }]
            });

            var fs = ct.items.first(),
                legend = fs.legend;

            var w = 180 + legend.getEl().getPadding('lr');

            expect(legend.getWidth()).toBeApprox(w, 1);
            expect(fs.getWidth()).toBeApprox(w + fs.getEl().getPadding('lr') + fs.getEl().getBorderWidth('lr'), 1);
            ct.destroy();
        });
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent({});
        });
        it("should default to no title", function() {
            expect(component.title).not.toBeDefined();
        });
        it("should default to falsy checkboxToggle", function() {
            expect(component.checkboxToggle).toBeFalsy();
        });
        it("should default to no checkboxName", function() {
            expect(component.checkboxName).not.toBeDefined();
        });
        it("should default to not collapsible", function() {
            expect(component.collapsible).toBeFalsy();
        });
        it("should default to not collapsed", function() {
            expect(component.collapsed).toBeFalsy();
        });
        it("should default to anchor layout", function() {
            expect(component.layout.type).toEqual('anchor');
        });
    });

    describe("rendering", function() {
        beforeEach(function() {
            makeComponent({ title: 'foo' });
        });

        it("should have a fieldset as the main element", function() {
            expect(component.el.dom.tagName.toLowerCase()).toEqual("fieldset");
        });

        it("should give the fieldset a class of 'x-fieldset'", function() {
            expect(component.el.dom.tagName.toLowerCase()).toEqual("fieldset");
        });

        it("should create a body element with class 'x-fieldset-body'", function() {
            expect(component.body).toBeDefined();
            expect(component.body.hasCls('x-fieldset-body')).toBeTruthy();
        });

        it("should have the group role", function() {
            expect(component).toHaveAttr('role', 'group');
        });

        it("should have aria-label", function() {
            expect(component).toHaveAttr('aria-label', 'foo field set');
        });

        it("should have aria-expanded", function() {
            expect(component).toHaveAttr('aria-expanded', 'true');
        });
    });

    describe("legend", function() {
        it("should not create the legend component by default", function() {
            makeComponent({});
            expect(component.legend).not.toBeDefined();
        });

        it("should create a legend component when the 'title' config is set", function() {
            makeComponent({
                title: "Foo"
            });
            expect(component.legend).toBeDefined();
        });

        it("should create a legend component when the 'checkboxToggle' config is true", function() {
            makeComponent({
                checkboxToggle: true
            });
            expect(component.legend).toBeDefined();
        });

        it("should create a legend element for the legend component", function() {
            makeComponent({
                title: "Foo"
            });
            expect(component.legend.el.dom.tagName.toLowerCase()).toEqual('legend');
        });

        it("should give the legend element a class of 'x-fieldset-header'", function() {
            makeComponent({
                title: "Foo"
            });
            expect(component.legend.el.hasCls('x-fieldset-header')).toBeTruthy();
        });

        describe("title", function() {
            it("should create a title component when title config is used", function() {
                makeComponent({
                    title: "Foo"
                });
                expect(component.titleCmp).toBeDefined();
            });
            it("should set the title component's content to the title config value", function() {
                makeComponent({
                    title: "Foo"
                });
                expect(component.titleCmp.el.dom).hasHTML("Foo");
            });
            it("should give the title component's element a class of 'x-fieldset-header-text'", function() {
                makeComponent({
                    title: "Foo"
                });
                expect(component.titleCmp.el.hasCls('x-fieldset-header-text')).toBeTruthy();
            });

            it("should set a new title if not rendered and configured with a title", function() {
                makeComponent({
                    title: 'Foo'
                }, true);
                component.setTitle('Bar');
                component.render(Ext.getBody());
                expect(component.titleCmp.el.dom).hasHTML('Bar');
                expect(component.hasCls('x-fieldset-with-title')).toBe(true);
                expect(component.hasCls('x-fieldset-with-legend')).toBe(true);
            });

            it("should set a new title if not rendered and configured without a title", function() {
                makeComponent({
                }, true);
                component.setTitle('Foo');
                component.render(Ext.getBody());
                expect(component.titleCmp.el.dom).hasHTML('Foo');
                expect(component.hasCls('x-fieldset-with-title')).toBe(true);
                expect(component.hasCls('x-fieldset-with-legend')).toBe(true);
            });

            it("should set a new title if rendered and configured with a title", function() {
                makeComponent({
                    title: 'Foo'
                }, true);
                component.setTitle('Bar');
                component.render(Ext.getBody());
                expect(component.titleCmp.el.dom).hasHTML('Bar');
                expect(component.hasCls('x-fieldset-with-title')).toBe(true);
                expect(component.hasCls('x-fieldset-with-legend')).toBe(true);
            });

            it("should set a new title if rendered and configured without a title", function() {
                makeComponent({
                }, true);
                component.setTitle('Foo');
                component.render(Ext.getBody());
                expect(component.titleCmp.el.dom).hasHTML('Foo');
                expect(component.hasCls('x-fieldset-with-title')).toBe(true);
                expect(component.hasCls('x-fieldset-with-legend')).toBe(true);
            });
        });

        describe("checkbox", function() {
            it("should allow the checkbox value to be set before render", function() {
                component = new Ext.form.FieldSet({
                    checkboxToggle: true,
                    checkboxName: 'a'
                });
                component.checkboxCmp.setValue(false);
                component.render(Ext.getBody());
                expect(component.checkboxCmp.getValue()).toBe(false);
            });

            it("should not create a checkbox component by default", function() {
                makeComponent({
                    title: 'Foo'
                });
                expect(component.legend.down('checkboxfield')).toBeNull();
            });

            it("should create a checkbox component when the checkboxToggle config is true", function() {
                makeComponent({
                    title: 'Foo',
                    checkboxToggle: true
                });
                expect(component.legend.down('checkboxfield')).not.toBeNull();
            });

            it("should give the checkbox a class of 'x-fieldset-header-checkbox'", function() {
                makeComponent({
                    title: 'Foo',
                    checkboxToggle: true
                });
                expect(component.legend.down('checkboxfield').el.hasCls('x-fieldset-header-checkbox')).toBeTruthy();
            });

            it("should set the checkbox's name to the 'checkboxName' config", function() {
                makeComponent({
                    title: 'Foo',
                    checkboxToggle: true,
                    checkboxName: 'theCheckboxName'
                });
                expect(component.legend.down('checkboxfield').name).toEqual('theCheckboxName');
            });

            it("should set the checkbox's name to '[fieldset_id]-checkbox' if the 'checkboxName' config is not set", function() {
                makeComponent({
                    title: 'Foo',
                    checkboxToggle: true
                });
                expect(component.legend.down('checkboxfield').name).toEqual(component.id + '-checkbox');
            });

            it("should set the checkbox to checked by default if the collapsed config is not true", function() {
                makeComponent({
                    title: 'Foo',
                    checkboxToggle: true
                });
                expect(component.legend.down('checkboxfield').getValue()).toBeTruthy();
            });

            it("should set the checkbox to unchecked by default if the collapsed config is true", function() {
                makeComponent({
                    title: 'Foo',
                    checkboxToggle: true,
                    collapsed: true
                });
                expect(component.legend.down('checkboxfield').getValue()).toBeFalsy();
            });

            it("should default the checkbox value to 'on' when checked", function() {
                makeComponent({
                    checkboxToggle: true
                });

                expect(component.checkboxCmp.getSubmitValue()).toBe('on');
            });

            it("should be able to configure the values of the checkbox", function() {
                makeComponent({
                    checkboxToggle: true,
                    collapsed: true,
                    checkbox: {
                        uncheckedValue: 'foo',
                        inputValue: 'bar'
                    }
                });

                expect(component.checkboxCmp.getSubmitValue()).toBe('foo');
                component.expand();
                expect(component.checkboxCmp.getSubmitValue()).toBe('bar');
            });

            it("should set checkbox aria-label", function() {
                makeComponent({ checkboxToggle: true });

                var cb = component.legend.down('checkboxfield');

                expect(cb).toHaveAttr('aria-label', 'Expand field set');
            });
        });

        describe("toggle tool", function() {
            var tool;

            beforeEach(function() {
                makeComponent({
                    title: 'foo',
                    collapsible: true
                });

                tool = component.legend.down('tool');
            });

            afterEach(function() {
                tool = null;
            });

            it("should have checkbox role", function() {
                expect(tool).toHaveAttr('role', 'checkbox');
            });

            it("should have aria-label", function() {
                expect(tool).toHaveAttr('aria-label', 'Expand field set');
            });

            it("should have aria-checked", function() {
                expect(tool).toHaveAttr('aria-checked', 'true');
            });

            it("should update aria-checked when fieldset is collapsed", function() {
                component.collapse();

                expect(tool).toHaveAttr('aria-checked', 'false');
            });
        });

        it("should be included in ComponentQuery searches from the fieldset container", function() {
            makeComponent({
                title: "Foo",
                checkboxToggle: true,
                checkboxName: 'theCheckboxName'
            });
            expect(component.down('[name=theCheckboxName]')).not.toBeNull();
        });

        it("should be available before the component is rendered", function() {
            var myWindow = Ext.create("Ext.window.Window", {
                width: 400,
                height: 300,
                items: [
                    {
                        xtype: "form",
                        items: [
                            {
                                id: "myFieldSet",
                                xtype: "fieldset",
                                checkboxToggle: true,
                                checkboxName: "a",
                                title: "test"
                            }
                        ]
                    }
                ]
            });

            expect(Ext.getCmp("myFieldSet").legend).toBeDefined();

            myWindow.destroy();
        });
    });

    describe("collapse method", function() {
        it("should set the 'collapsed' property to true", function() {
            makeComponent({ collapsed: false });
            component.collapse();
            expect(component.collapsed).toBeTruthy();
        });

        it("should uncheck the checkboxToggle", function() {
            makeComponent({ collapsed: false, checkboxToggle: true });
            component.collapse();
            expect(component.legend.down('checkboxfield').getValue()).toBeFalsy();
        });

        it("should give the main element a class of 'x-fieldset-collapsed'", function() {
            makeComponent({ collapsed: false });
            component.collapse();
            expect(component.el.hasCls('x-fieldset-collapsed')).toBeTruthy();
        });

        it("should set aria-expanded attribute", function() {
            makeComponent({ collapsed: false });
            component.collapse();

            expect(component).toHaveAttr('aria-expanded', 'false');
        });
    });

    describe("expand method", function() {
        it("should set the 'collapsed' property to false", function() {
            makeComponent({ collapsed: true });
            component.expand();
            expect(component.collapsed).toBeFalsy();
        });

        it("should check the checkboxToggle", function() {
            makeComponent({ collapsed: true, checkboxToggle: true });
            component.expand();
            expect(component.legend.down('checkboxfield').getValue()).toBeTruthy();
        });

        it("should remove the 'x-fieldset-collapsed' class from the main element", function() {
            makeComponent({ collapsed: true });
            component.expand();
            expect(component.el.hasCls('x-fieldset-collapsed')).toBeFalsy();
        });

        it("should set aria-expanded attribute", function() {
            makeComponent({ collapsed: true });
            component.expand();

            expect(component).toHaveAttr('aria-expanded', 'true');
        });
    });

    describe('toggle method', function() {
        it("should collapse the fieldset if it is expanded", function() {
            makeComponent({ collapsed: false });
            component.toggle();
            expect(component.el.hasCls('x-fieldset-collapsed')).toBeTruthy();
        });

        it("should expand the fieldset if it is collapsed", function() {
            makeComponent({ collapsed: true });
            component.toggle();
            expect(component.el.hasCls('x-fieldset-collapsed')).toBeFalsy();
        });
    });

    describe("FieldAncestor", function() {
        it("should fire an event whenever validitychange fires on a child item", function() {
            var called;

            makeComponent({
                items: [{
                    xtype: 'textfield',
                    allowBlank: false
                }]
            });
            component.on('fieldvaliditychange', function() {
                called = true;
            });
            component.items.first().setValue('Foo');
            expect(called).toBe(true);
        });

        it("should fire an event whenever errorchange fires on a child item", function() {
            var called;

            makeComponent({
                items: [{
                    xtype: 'textfield',
                    allowBlank: false
                }]
            });
            component.on('fielderrorchange', function() {
                called = true;
            });
            component.items.first().markInvalid('Foo');
            expect(called).toBe(true);
        });
    });

    xdescribe("setTitle method", function() {
        it("should set the legend title to the argument value", function() {
            makeComponent({ title: 'Old and busted' });
            component.setTitle('New hotness');
            expect(component.titleCmp.el.dom).hasHTML('New hotness');
        });
    });

    describe("session", function() {
        it("should get the schema from the parent session when it has a title and is being created and added", function() {
            Ext.define('spec.TestSchema', {
                extend: 'Ext.data.schema.Schema',
                alias: 'schema.test',

                namespace: 'spec'
            });

            makeComponent({
                session: true,
                title: 'Title'
            }, true);

            var ct = new Ext.container.Container({
                session: {
                    schema: 'test'
                },
                renderTo: Ext.getBody()
            });

            ct.add(component);
            expect(component.getSession().getSchema()).toBe(ct.getSession().getSchema());
            ct.destroy();

            Ext.undefine('spec.TestSchema');
            Ext.data.schema.Schema.clearInstance('test');
        });
    });

});
