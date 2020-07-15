topSuite("Ext.mixin.FocusableContainer",
    Ext.isModern
        ? ['Ext.Container', 'Ext.Button', 'Ext.form.Text', 'Ext.form.Select', 'Ext.form.Slider']
        : ['Ext.Container', 'Ext.Button', 'Ext.form.field.Text', 'Ext.form.field.ComboBox',
           'Ext.slider.Single'],
function() {
    var isModern = Ext.toolkit === 'modern',
        forward = true,
        backward = false,
        autoId = 0,
        fc, fcEl;

    function makeButton(config) {
        config = config || {};

        Ext.applyIf(config, {
            // It is easier to troubleshoot test failures when error message
            // says "expected fooBtn-xyz to be afterBtn-xyz", rather than
            // "expected button-xyz to be button-zyx"; since these
            // messages often display element id's, it's easier to set component
            // id here than guesstimate later.
            id: (config.text || 'button') + '-' + ++autoId,
            renderTo: Ext.getBody()
        });

        var btn = new Ext.Button(config);

        return btn;
    }

    function makeContainer(config) {
        var items, i, len, item;

        config = Ext.apply({
            id: 'focusableContainer-' + ++autoId,
            focusableContainer: true,
            width: 1000,
            height: 50,
            style: {
                'background-color': 'green'
            },
            layout: 'hbox',
            defaults: {
                xtype: 'button'
            },
            renderTo: Ext.getBody()
        }, config);

        items = config.items;

        if (items) {
            for (i = 0, len = items.length; i < len; i++) {
                item = items[i];

                if (item.xtype === 'button') {
                    item.id = (item.text || 'button') + '-' + ++autoId;

                    if (isModern && !('tabIndex' in item)) {
                        item.tabIndex = 0;
                    }
                }
            }
        }

        fc = new Ext.Container(config);
        fcEl = fc.focusableContainerEl;

        return fc;
    }

    afterEach(function() {
        if (fc && !fc.$protected) {
            fc.destroy();
            fc = fcEl = null;
        }
    });

    describe("init/destroy", function() {
        var proto, first, second, third,
            initSpy, destroySpy;

        function setupContainer(config) {
            config = Ext.apply({
                activeChildTabIndex: 42,
                items: [{
                    xtype: 'button',
                    itemId: 'first',
                    text: 'first'
                }, {
                    xtype: 'button',
                    itemId: 'second',
                    text: 'second',
                    disabled: true
                }, {
                    xtype: 'button',
                    itemId: 'third',
                    text: 'third'
                }]
            }, config);

            makeContainer(config);

            first = fc.down('#first');
            second = fc.down('#second');
            third = fc.down('#third');

            return fc;
        }

        beforeEach(function() {
            proto = Ext.Container.prototype;

            initSpy = spyOn(proto, 'doInitFocusableContainer').andCallThrough();
            destroySpy = spyOn(proto, 'destroyFocusableContainer').andCallThrough();
        });

        afterEach(function() {
            proto = first = second = third = initSpy = destroySpy = null;
        });

        describe("focusableContainer === true (default)", function() {
            describe("focusableContainer stays true", function() {
                beforeEach(function() {
                    setupContainer();
                });

                it("should call init", function() {
                    expect(initSpy).toHaveBeenCalled();
                });

                it("should activate container", function() {
                    expect(fc.isFocusableContainerActive()).toBeTruthy();
                });

                it("should set tabindex on the first child", function() {
                    expect(first).toHaveAttr('tabIndex', 42);
                });

                it("should NOT set tabindex on the second child", function() {
                    expect(second).not.toHaveAttr('tabIndex');
                });

                it("should set tabindex on the third child", function() {
                    expect(third).toHaveAttr('tabIndex', -1);
                });

                it("should call destroy", function() {
                    fc.destroy();

                    expect(destroySpy).toHaveBeenCalled();
                });
            });

            describe("focusableContainer stays true with no enabled children", function() {
                beforeEach(function() {
                    setupContainer({ renderTo: undefined });

                    first.disable();
                    third.disable();

                    fc.render(Ext.getBody());
                });

                it("should call init", function() {
                    expect(initSpy).toHaveBeenCalled();
                });

                it("should not activate container", function() {
                    expect(fc.isFocusableContainerActive()).toBeFalsy();
                });

                it("should not set tabindex on the first child", function() {
                    if (isModern) {
                        expect(first.buttonElement.dom.disabled).toBeTruthy();
                        expect(first.isTabbable()).toBeFalsy();
                    }
                    else {
                        expect(first).not.toHaveAttr('tabIndex');
                    }
                });

                it("should not set tabindex on the second child", function() {
                    expect(second).not.toHaveAttr('tabIndex');
                });

                it("should not set tabindex on the third child", function() {
                    if (isModern) {
                        expect(third.buttonElement.dom.disabled).toBeTruthy();
                        expect(third.isTabbable()).toBeFalsy();
                    }
                    else {
                        expect(third).not.toHaveAttr('tabIndex');
                    }
                });

                it("should call destroy", function() {
                    fc.destroy();

                    expect(destroySpy).toHaveBeenCalled();
                });
            });

            // This is common case when a toolbar needs to make a late decision to bail out
            // of being a FocusableContainer because one or more of its children needs to handle
            // arrow key presses. See https://sencha.jira.com/browse/EXTJS-17458
            describe("focusableContainer changes to false before rendering", function() {
                beforeEach(function() {
                    setupContainer({ renderTo: undefined });
                    fc.focusableContainer = false;
                    fc.render(Ext.getBody());
                });

                it("should not call init", function() {
                    expect(initSpy).not.toHaveBeenCalled();
                });

                it("should not activate container", function() {
                    expect(fc.isFocusableContainerActive()).toBeFalsy();
                });

                it("should not add tabindex to second child", function() {
                    expect(second).not.toHaveAttr('tabIndex');
                });

                it("should not alter tabindex on last child", function() {
                    expect(third).toHaveAttr('tabIndex', 0);
                });

                it("should not call destroy", function() {
                    fc.destroy();

                    expect(destroySpy).not.toHaveBeenCalled();
                });
            });

            // Used in Grid header containers
            describe("init called after adding or removing children", function() {
                describe("no children at start, some added later", function() {
                    beforeEach(function() {
                        setupContainer({ items: [] });

                        fc.add([
                            { xtype: 'button', text: 'foo' },
                            { xtype: 'button', text: 'bar' }
                        ]);

                        fc.initFocusableContainer();
                    });

                    it("should activate container", function() {
                        expect(fc.isFocusableContainerActive()).toBeTruthy();
                    });
                });

                describe("some children at start, all removed later", function() {
                    beforeEach(function() {
                        setupContainer();

                        fc.removeAll();
                        fc.initFocusableContainer();
                    });

                    it("should deactivate container", function() {
                        expect(fc.isFocusableContainerActive()).toBeFalsy();
                    });
                });
            });
        });

        describe("focusableContainer === false", function() {
            beforeEach(function() {
                setupContainer({ focusableContainer: false });
            });

            it("should not call init", function() {
                expect(initSpy).not.toHaveBeenCalled();
            });

            it("should not activate container", function() {
                expect(fc.isFocusableContainerActive()).toBeFalsy();
            });

            it("should not alter tabindex on first child", function() {
                expect(first).toHaveAttr('tabIndex', 0);
            });

            it("should not add tabindex to second child", function() {
                expect(second).not.toHaveAttr('tabIndex');
            });

            it("should not alter tabindex on last child", function() {
                expect(third).toHaveAttr('tabIndex', 0);
            });

            it("should not call destroy", function() {
                fc.destroy();

                expect(destroySpy).not.toHaveBeenCalled();
            });
        });
    });

    describe("add/remove children", function() {
        describe("adding", function() {
            beforeEach(function() {
                makeContainer();
            });

            it("should not activate container after adding non-focusable child", function() {
                fc.add({
                    xtype: 'component',
                    html: 'foo'
                });

                expect(fc.isFocusableContainerActive()).toBeFalsy();
            });

            it("should activate container after adding non-focusable and then focusable child", function() {
                fc.add({
                    xtype: 'component',
                    html: 'foo'
                });

                fc.add({
                    xtype: 'button',
                    text: 'bar'
                });

                expect(fc.isFocusableContainerActive()).toBeTruthy();
            });

            it("should activate container after adding focusable child", function() {
                fc.add({
                    xtype: 'button',
                    text: 'bar'
                });

                expect(fc.isFocusableContainerActive()).toBeTruthy();
            });

            it("should not deactivate container when adding non-focusable child after focusable", function() {
                fc.add({
                    xtype: 'button',
                    text: 'bar'
                });

                fc.add({
                    xtype: 'component',
                    html: 'foo'
                });

                expect(fc.isFocusableContainerActive()).toBeTruthy();
            });
        });

        describe("removing", function() {
            beforeEach(function() {
                makeContainer({
                    items: [{
                        xtype: 'component',
                        html: 'zumbo'
                    }, {
                        xtype: 'button',
                        text: 'throbbe'
                    }]
                });
            });

            it("should not deactivate container when with focusable children left", function() {
                fc.remove(0);

                expect(fc.isFocusableContainerActive()).toBeTruthy();
            });

            it("should deactivate container when no focusable children left", function() {
                fc.remove(1);

                expect(fc.isFocusableContainerActive()).toBeFalsy();
            });

            it("should deactivate container when no children left", function() {
                fc.removeAll();

                expect(fc.isFocusableContainerActive()).toBeFalsy();
            });
        });
    });

    describe("child lookup", function() {
        describe("first/last child", function() {
            function makeSuite(name, config) {
                describe(name, function() {
                    var foo, bar;

                    beforeEach(function() {
                        makeContainer(config);

                        foo = fc.down('[text=foo]');
                        bar = fc.down('[text=bar]');
                    });

                    it("finds foo going forward", function() {
                        var child = fc.findNextFocusableChild({ step: true });

                        expect(child).toBe(foo);
                    });

                    it("finds bar going backward", function() {
                        var child = fc.findNextFocusableChild({ step: false });

                        expect(child).toBe(bar);
                    });
                });
            }

            makeSuite('focusable child', {
                items: [
                    { xtype: 'button', text: 'foo' },
                    { xtype: 'button', text: 'bar' }
                ]
            });

            makeSuite('non-focusable child', {
                items: [
                    { xtype: 'component', html: 'text1'  },
                    { xtype: 'button', text: 'foo' },
                    { xtype: 'button', text: 'bar' },
                    { xtype: 'component', html: 'text2'  }
                ]
            });

            makeSuite('focusable but disabled child', {
                items: [
                    { xtype: 'button', text: 'disabled1', disabled: true },
                    { xtype: 'button', text: 'foo' },
                    { xtype: 'button', text: 'bar' },
                    { xtype: 'button', text: 'disabled2', disabled: true }
                ]
            });

            // TODO Revisit when Modern has floating Menus
            if (!isModern) {
                makeSuite('focusable/disabled child when disabled are allowed', {
                    allowFocusingDisabledChildren: true,
                    items: [
                        // Can't use buttons here, they're *stubbornly* unfocusable when disabled
                        { xtype: 'menuitem', text: 'foo', disabled: true },
                        { xtype: 'menuitem', text: 'bar', disabled: true }
                    ]
                });
            }

            makeSuite('focusable/disabled AND non-focusable child', {
                items: [
                    { xtype: 'component', text: 'text1'  },
                    { xtype: 'button', text: 'disabled1', disabled: true },
                    { xtype: 'button', text: 'foo' },
                    { xtype: 'button', text: 'bar' },
                    { xtype: 'component', html: 'text2'  },
                    { xtype: 'button', text: 'disabled2', disabled: true }
                ]
            });

            // TODO Revisit when Modern has floating Menus
            if (!isModern) {
                makeSuite('focusable/disabled AND non-focusable, disabled are allowed', {
                    allowFocusingDisabledChildren: true,
                    items: [
                        { xtype: 'component', html: 'text1' },
                        { xtype: 'menuitem', text: 'foo', disabled: true },
                        { xtype: 'menuitem', text: 'bar', disabled: true },
                        { xtype: 'component', html: 'text2' }
                    ]
                });
            }
        });

        // TODO Revisit when https://sencha.jira.com/browse/EXT-205 is merged
        (isModern ? xdescribe : describe)("from existing child", function() {
            var fooBtn, barBtn, fooInput, barInput, disabled1, disabled2;

            function expectToFind(whatNext, whereFrom, goingForward) {
                var child = fc.findNextFocusableChild({ child: whereFrom, step: goingForward });

                expect(child).toBe(whatNext);
            }

            beforeEach(function() {
                makeContainer({
                    items: [
                        { xtype: 'component', html: 'text1' },
                        { xtype: 'menuitem', text: 'disabled1', disabled: true },
                        { xtype: 'button', text: 'fooBtn' },
                        { xtype: 'component' },
                        { xtype: 'textfield', fieldLabel: 'foo field' },
                        { xtype: 'menuitem', text: 'disabled2', disabled: true },
                        { xtype: 'button', text: 'barBtn' },
                        { xtype: 'component' },
                        { xtype: isModern ? 'selectfield' : 'combobox', fieldLabel: 'bar combo' }
                    ]
                });

                fooBtn = fc.down('button[text=fooBtn]');
                barBtn = fc.down('button[text=barBtn]');

                disabled1 = fc.down('[text=disabled1]');
                disabled2 = fc.down('[text=disabled2]');

                fooInput = fc.down('textfield');
                barInput = fc.down(isModern ? 'selectfield' : 'combobox');
            });

            afterEach(function() {
                fooBtn = barBtn = fooInput = barInput = disabled1 = disabled2 = null;
            });

            describe("forward", function() {
                describe("disabled buttons not changed", function() {
                    it("finds fooBtn as the first item", function() {
                        expectToFind(fooBtn, null, forward);
                    });

                    it("finds fooInput from fooBtn", function() {
                        expectToFind(fooInput, fooBtn, forward);
                    });

                    it("finds barBtn from fooInput", function() {
                        expectToFind(barBtn, fooInput, forward);
                    });

                    it("finds barInput from barBtn", function() {
                        expectToFind(barInput, barBtn, forward);
                    });

                    it("finds fooBtn from barInput (wraps over)", function() {
                        expectToFind(fooBtn, barInput, forward);
                    });
                });

                describe("allowFocusingDisabledChildren = true", function() {
                    beforeEach(function() {
                        fc.allowFocusingDisabledChildren = true;
                    });

                    it("finds disabled1 as the first item", function() {
                        expectToFind(disabled1, null, forward);
                    });

                    it("finds fooBtn from disabled1", function() {
                        expectToFind(fooBtn, disabled1, forward);
                    });

                    it("finds fooInput from fooBtn", function() {
                        expectToFind(fooInput, fooBtn, forward);
                    });

                    it("finds disabled2 from fooInput", function() {
                        expectToFind(disabled2, fooInput, forward);
                    });

                    it("finds barBtn from disabled2", function() {
                        expectToFind(barBtn, disabled2, forward);
                    });

                    it("finds barInput from barBtn", function() {
                        expectToFind(barInput, barBtn, forward);
                    });

                    it("finds disabled1 from barInput (wraps over)", function() {
                        expectToFind(disabled1, barInput, forward);
                    });
                });

                describe("disabled1 state changed", function() {
                    beforeEach(function() {
                        disabled1.enable();
                    });

                    it("finds disabled1 as the first item", function() {
                        expectToFind(disabled1, null, forward);
                    });

                    it("finds fooBtn from disabled1", function() {
                        expectToFind(fooBtn, disabled1, forward);
                    });

                    it("finds fooInput from fooBtn", function() {
                        expectToFind(fooInput, fooBtn, forward);
                    });

                    it("finds barBtn from fooInput", function() {
                        expectToFind(barBtn, fooInput, forward);
                    });

                    it("finds barInput from barBtn", function() {
                        expectToFind(barInput, barBtn, forward);
                    });

                    it("finds disabled1 from barInput (wraps over)", function() {
                        expectToFind(disabled1, barInput, forward);
                    });
                });

                describe("disabled2 state changed", function() {
                    beforeEach(function() {
                        disabled2.enable();
                    });

                    it("finds fooBtn as the first item", function() {
                        expectToFind(fooBtn, null, forward);
                    });

                    it("finds fooInput from fooBtn", function() {
                        expectToFind(fooInput, fooBtn, forward);
                    });

                    it("finds disabled2 from fooInput", function() {
                        expectToFind(disabled2, fooInput, forward);
                    });

                    it("finds barBtn from disabled2", function() {
                        expectToFind(barBtn, disabled2, forward);
                    });

                    it("finds barInput from barBtn", function() {
                        expectToFind(barInput, barBtn, forward);
                    });

                    it("finds fooBtn from barInput (wraps over)", function() {
                        expectToFind(fooBtn, barInput, forward);
                    });
                });
            });

            describe("backward", function() {
                describe("disabled buttons not changed", function() {
                    it("finds barInput as the first item", function() {
                        expectToFind(barInput, null, backward);
                    });

                    it("finds barBtn from barInput", function() {
                        expectToFind(barBtn, barInput, backward);
                    });

                    it("finds fooInput from barBtn", function() {
                        expectToFind(fooInput, barBtn, backward);
                    });

                    it("finds fooBtn from fooInput", function() {
                        expectToFind(fooBtn, fooInput, backward);
                    });

                    it("finds barInput from fooBtn (wraps over)", function() {
                        expectToFind(barInput, fooBtn, backward);
                    });
                });

                describe("allowFocusingDisabledChildren = true", function() {
                    beforeEach(function() {
                        fc.allowFocusingDisabledChildren = true;
                    });

                    it("finds barInput as the first item", function() {
                        expectToFind(barInput, null, backward);
                    });

                    it("finds barBtn from barInput", function() {
                        expectToFind(barBtn, barInput, backward);
                    });

                    it("finds disabled2 from barBtn", function() {
                        expectToFind(disabled2, barBtn, backward);
                    });

                    it("finds fooInput from disabled2", function() {
                        expectToFind(fooInput, disabled2, backward);
                    });

                    it("finds fooBtn from fooInput", function() {
                        expectToFind(fooBtn, fooInput, backward);
                    });

                    it("finds disabled1 from fooBtn", function() {
                        expectToFind(disabled1, fooBtn, backward);
                    });

                    it("finds barInput from disabled1 (wraps over)", function() {
                        expectToFind(barInput, disabled1, backward);
                    });
                });

                describe("disabled1 state changed", function() {
                    beforeEach(function() {
                        disabled1.enable();
                    });

                    it("finds barInput as the first item", function() {
                        expectToFind(barInput, null, backward);
                    });

                    it("finds barBtn from barInput", function() {
                        expectToFind(barBtn, barInput, backward);
                    });

                    it("finds fooInput from barBtn", function() {
                        expectToFind(fooInput, barBtn, backward);
                    });

                    it("finds fooBtn from fooInput", function() {
                        expectToFind(fooBtn, fooInput, backward);
                    });

                    it("finds disabled1 from fooBtn", function() {
                        expectToFind(disabled1, fooBtn, backward);
                    });

                    it("finds barInput from disabled1 (wraps over)", function() {
                        expectToFind(barInput, disabled1, backward);
                    });
                });

                describe("disabled2 state changed", function() {
                    beforeEach(function() {
                        disabled2.enable();
                    });

                    it("finds barInput as the first item", function() {
                        expectToFind(barInput, null, backward);
                    });

                    it("finds barBtn from barInput", function() {
                        expectToFind(barBtn, barInput, backward);
                    });

                    it("finds disabled2 from barBtn", function() {
                        expectToFind(disabled2, barBtn, backward);
                    });

                    it("finds fooInput from disabled2", function() {
                        expectToFind(fooInput, disabled2, backward);
                    });

                    it("finds fooBtn from fooInput", function() {
                        expectToFind(fooBtn, fooInput, backward);
                    });

                    it("finds barInput from fooBtn (wraps over)", function() {
                        expectToFind(barInput, fooBtn, backward);
                    });
                });
            });
        });
    });

    describe("container state handling", function() {
        describe("initially enabled", function() {
            beforeEach(function() {
                makeContainer({
                    activeChildTabIndex: 42,
                    items: [{
                        xtype: 'button',
                        itemId: 'first',
                        text: 'first'
                    }, {
                        xtype: 'button',
                        itemId: 'second',
                        text: 'second',
                        disabled: true
                    }, {
                        xtype: 'button',
                        itemId: 'third',
                        text: 'third'
                    }]
                });
            });

            describe("disable with masking", function() {
                beforeEach(function() {
                    fc.disable();
                });

                it("should deactivate container", function() {
                    expect(fc.isFocusableContainerActive()).toBeFalsy();
                });

                it("should not have tabbable items", function() {
                    var tabbables = fcEl.findTabbableElements();

                    expect(tabbables.length).toBe(0);
                });

                describe("re-enable", function() {
                    beforeEach(function() {
                        fc.enable();
                    });

                    it("should activate container", function() {
                        expect(fc.isFocusableContainerActive()).toBeTruthy();
                    });

                    it("should have tabbable item", function() {
                        var tabbables = fcEl.findTabbableElements();

                        expect(tabbables.length).toBe(1);
                    });
                });
            });

            describe("disable without masking", function() {
                beforeEach(function() {
                    fc.maskOnDisable = false;
                    fc.disable();
                });

                it("should deactivate container", function() {
                    expect(fc.isFocusableContainerActive()).toBeFalsy();
                });

                it("should not have tabbable items", function() {
                    var tabbables = fcEl.findTabbableElements();

                    expect(tabbables.length).toBe(0);
                });

                describe("re-enable", function() {
                    beforeEach(function() {
                        fc.enable();
                    });

                    it("should activate container", function() {
                        expect(fc.isFocusableContainerActive()).toBeTruthy();
                    });

                    it("should have tabbable item", function() {
                        var tabbables = fcEl.findTabbableElements();

                        expect(tabbables.length).toBe(1);
                    });
                });
            });
        });

        describe("initially disabled", function() {
            describe("with masking", function() {
                beforeEach(function() {
                    makeContainer({
                        disabled: true,
                        activeChildTabIndex: 42,
                        items: [{
                            xtype: 'button',
                            itemId: 'first',
                            text: 'first'
                        }, {
                            xtype: 'button',
                            itemId: 'second',
                            text: 'second',
                            disabled: true
                        }, {
                            xtype: 'button',
                            itemId: 'third',
                            text: 'third'
                        }]
                    });
                });

                it("should not activate container", function() {
                    expect(fc.isFocusableContainerActive()).toBeFalsy();
                });

                it("should not have tabbable items", function() {
                    var tabbables = fcEl.findTabbableElements();

                    expect(tabbables.length).toBe(0);
                });

                describe("enable", function() {
                    beforeEach(function() {
                        fc.enable();
                    });

                    it("should activate container", function() {
                        expect(fc.isFocusableContainerActive()).toBeTruthy();
                    });

                    it("should have tabbable item", function() {
                        var tabbables = fcEl.findTabbableElements();

                        expect(tabbables.length).toBe(1);
                    });

                    describe("re-disable", function() {
                        beforeEach(function() {
                            fc.disable();
                        });

                        it("should deactivate container", function() {
                            expect(fc.isFocusableContainerActive()).toBeFalsy();
                        });

                        it("should not have tabbable items", function() {
                            var tabbables = fcEl.findTabbableElements();

                            expect(tabbables.length).toBe(0);
                        });
                    });
                });
            });

            describe("without masking", function() {
                beforeEach(function() {
                    makeContainer({
                        maskOnDisable: false,
                        disabled: true,
                        activeChildTabIndex: 42,
                        items: [{
                            xtype: 'button',
                            itemId: 'first',
                            text: 'first'
                        }, {
                            xtype: 'button',
                            itemId: 'second',
                            text: 'second',
                            disabled: true
                        }, {
                            xtype: 'button',
                            itemId: 'third',
                            text: 'third'
                        }]
                    });
                });

                it("should not activate container", function() {
                    expect(fc.isFocusableContainerActive()).toBeFalsy();
                });

                it("should not have tabbable items", function() {
                    var tabbables = fcEl.findTabbableElements();

                    expect(tabbables.length).toBe(0);
                });

                describe("enable", function() {
                    beforeEach(function() {
                        fc.enable();
                    });

                    it("should activate container", function() {
                        expect(fc.isFocusableContainerActive()).toBeTruthy();
                    });

                    it("should have tabbable item", function() {
                        var tabbables = fcEl.findTabbableElements();

                        expect(tabbables.length).toBe(1);
                    });

                    describe("re-disable", function() {
                        beforeEach(function() {
                            fc.disable();
                        });

                        it("should deactivate container", function() {
                            expect(fc.isFocusableContainerActive()).toBeFalsy();
                        });

                        it("should not have tabbable items", function() {
                            var tabbables = fcEl.findTabbableElements();

                            expect(tabbables.length).toBe(0);
                        });
                    });
                });
            });
        });
    });

    describe("child state handling", function() {
        var itemIds = [null, 'first', 'second', 'third'],
            items = [],
            afterBtn;

        function setup(containerConfig) {
            containerConfig = Ext.apply({
                $protected: true,
                items: [{
                    itemId: 'first',
                    text: 'primus'
                }, {
                    itemId: 'second',
                    text: 'secundus'
                }, {
                    itemId: 'third',
                    text: 'tertius'
                }]
            }, containerConfig);

            makeContainer(containerConfig);

            items.length = 0;
            items.push(
                fc.down('#first'),
                fc.down('#second'),
                fc.down('#third')
            );

            afterBtn = makeButton({
                text: 'reliquus'
            });

            items[0].findFocusTarget = items[1].findFocusTarget =
            items[2].findFocusTarget = function() {
                return afterBtn;
            };
        }

        function teardown() {
            fc = fcEl = afterBtn = Ext.destroy(fc, afterBtn);
            items.length = 0;
        }

        function makeSpecs(fcActive, expectations, focused) {
            var desc, itemId, itFn, want, i, len;

            desc = fcActive ? "should keep container active" : "should deactivate container";

            it(desc, function() {
                expect(!!fc.isFocusableContainerActive()).toBe(!!fcActive);
            });

            // expectations array is 0-based, item ids are 1-based!
            for (i = 0, len = expectations.length; i < len; i++) {
                want = expectations[i];
                itemId = itemIds[i + 1];

                if (want == null) {
                    desc = "should not reset " + itemId + " child tabIndex";

                    // Close over item index
                    it(desc, (function(itemIndex) {
                        var index = itemIndex;

                        if (isModern) {
                            return function() {
                                expect(items[index].ariaEl.isTabbable()).toBeFalsy();
                            };
                        }
                        else {
                            return function() {
                                expect(items[index]).not.toHaveAttr('tabIndex');
                            };
                        }
                    })(i));
                }
                else if (typeof want === 'number') {
                    /* eslint-disable-next-line multiline-ternary */
                    desc = want === -1 ? "should keep " + itemId + " child inactive"
                         :               "should activate " + itemId + " child"
                         ;

                    // Close over item index *and* expected value
                    it(desc, (function(itemIndex, value) {
                        var index = itemIndex,
                            expected = value;

                        return function() {
                            var item = items[index];

                            expect(item).toHaveAttr('tabIndex', expected);

                            if (expected > -1) {
                                expect(item.isTabbable()).toBe(true);
                            }
                        };
                    })(i, want));
                }
                else {
                    // eslint-disable-next-line multiline-ternary
                    desc = want ? "should keep " + itemId + " child tabbable"
                         :        "should make " + itemId + " child non-tabbable"
                         ;

                    // Ditto
                    it(desc, (function(itemIndex, value) {
                        return function() {
                            expect(items[itemIndex].isTabbable()).toBe(value);
                        };
                    })(i, want));
                }
            }

            if (focused !== undefined) {
                if (focused === null) {
                    // Focus is outside the container
                    it("should not keep focus inside container", function() {
                        expect(fc.hasFocus).toBeFalsy();
                    });
                }
                else if (typeof focused === 'number') {
                    itemId = itemIds[focused];
                    desc = "should have " + itemId + " child focused";

                    itFn = (function(itemIndex) {
                        return function() {
                            expectFocused(items[itemIndex]);
                        };
                    })(focused - 1);
                }
                else {
                    it("has wrong focused expectation: " + focused, function() {
                        expect(true).toBe(false);
                    });
                }
            }
        }

        function makeSuite(actions, fcActive, expectations, focused, containerCfg) {
            var beforeAlls = [],
                methods = [],
                chunks = [],
                action, desc, keys, sequence, method, i, j;

            beforeAlls.push(function() {
                setup(containerCfg);
            });

            if (actions) {
                if (!Ext.isArray(actions)) {
                    actions = [actions];
                }

                for (i = 0; i < actions.length; i++) {
                    action = actions[i];

                    if (typeof action === 'function') {
                        // This is to avoid action fn being passed done() callback
                        beforeAlls.push((function(func) {
                            return function() {
                                func(items);
                            };
                        })(action));

                        if (action.desc) {
                            chunks.push(action.desc);
                        }
                    }
                    else {
                        keys = Ext.Object.getKeys(action);

                        if (keys.length > 1) {
                            throw new Error("Can't do more than one method in action object!");
                        }

                        method = keys[0];
                        sequence = action[method];

                        methods.push(method);

                        if (typeof sequence === 'number') {
                            chunks.push(method + ': ' + itemIds[sequence]);
                        }
                        else {
                            chunks.push(method + ': ' + itemIds[sequence[0]]);

                            for (j = 1; j < sequence.length; j++) {
                                chunks.push(itemIds[sequence[j]]);
                            }
                        }

                        beforeAlls.push((function(method, itemIndices) {
                            return function() {
                                var item, i, j;

                                if (typeof itemIndices === 'number') {
                                    itemIndices = [itemIndices];
                                }

                                for (j = 0; j < itemIndices.length; j++) {
                                    item = items[itemIndices[j] - 1];

                                    if (item) {
                                        (function(cmp, methodName) {
                                            runs(function() {
                                                cmp[methodName]();
                                            });

                                            waitAWhile();
                                        })(item, method);
                                    }
                                }
                            };
                        })(method, sequence));
                    }
                }
            }

            if (!chunks.length) {
                desc = "initial state";
            }
            else {
                desc = chunks.join(' then ');
            }

            describe(desc, function() {
                for (var i = 0; i < beforeAlls.length; i++) {
                    beforeAll(beforeAlls[i]);
                }

                afterAll(function() {
                    teardown();
                });

                makeSpecs(fcActive, expectations, focused);
            });
        }

        function focusFirst(items) {
            focusAndWait(items[0], null, "first item to receive focus");
        }

        focusFirst.desc = 'focus first item';

        function focusSecond(items) {
            focusAndWait(items[1], null, "second item to receive focus");
        }

        focusSecond.desc = 'focus second item';

        function focusThird(items) {
            focusAndWait(items[2], null, "third item to receive focus");
        }

        focusThird.desc = 'focus third item';

        function disableItems(items) {
            for (var i = 0; i < items.length; i++) {
                items[i].disable();
            }
        }

        disableItems.desc = 'disable all items';

        function hideItems(items) {
            for (var i = 0; i < items.length; i++) {
                items[i].hide();
            }
        }

        hideItems.desc = 'hide all items';

        describe("enable/disable", function() {
            describe("initially enabled children", function() {
                makeSuite(null, true, [0, -1, -1]);

                describe("none focused", function() {
                    makeSuite({ disable: 1 }, true, [null, 0, -1]);
                    makeSuite({ disable: [1, 2] }, true, [null, null, 0]);
                    makeSuite({ disable: [1, 2, 3] }, false, [null, null, null]);
                    makeSuite({ disable: [1, 3] }, true, [null, 0, null]);
                    makeSuite({ disable: [1, 3, 2] }, false, [null, null, null]);
                    makeSuite({ disable: 2 }, true, [0, null, -1]);
                    makeSuite({ disable: [2, 3] }, true, [0, null, null]);
                    makeSuite({ disable: [2, 3, 1] }, false, [null, null, null]);
                    makeSuite({ disable: [2, 1] }, true, [null, null, 0]);
                    makeSuite({ disable: [2, 1, 3] }, false, [null, null, null]);
                    makeSuite({ disable: 3 }, true, [0, -1, null]);
                    makeSuite({ disable: [3, 2] }, true, [0, null, null]);
                    makeSuite({ disable: [3, 2, 1] }, false, [null, null, null]);
                    makeSuite({ disable: [3, 1] }, true, [null, 0, null]);
                    makeSuite({ disable: [3, 1, 2] }, false, [null, null, null]);

                    describe("re-enable", function() {
                        makeSuite([disableItems, { enable: 1 }], true, [0, null, null]);
                        makeSuite([disableItems, { enable: [1, 2] }], true, [0, -1, null]);
                        makeSuite([disableItems, { enable: [1, 2, 3] }], true, [0, -1, -1]);
                        makeSuite([disableItems, { enable: [1, 3] }], true, [0, null, -1]);
                        makeSuite([disableItems, { enable: [1, 3, 2] }], true, [0, -1, -1]);
                        makeSuite([disableItems, { enable: 2 }], true, [null, 0, null]);
                        makeSuite([disableItems, { enable: [2, 1] }], true, [0, -1, null]);
                        makeSuite([disableItems, { enable: [2, 1, 3] }], true, [0, -1, -1]);
                        makeSuite([disableItems, { enable: [2, 3] }], true, [null, 0, -1]);
                        makeSuite([disableItems, { enable: [2, 3, 1] }], true, [0, -1, -1]);
                        makeSuite([disableItems, { enable: 3 }], true, [null, null, 0]);
                        makeSuite([disableItems, { enable: [3, 1] }], true, [0, null, -1]);
                        makeSuite([disableItems, { enable: [3, 1, 2] }], true, [0, -1, -1]);
                        makeSuite([disableItems, { enable: [3, 2] }], true, [null, 0, -1]);
                        makeSuite([disableItems, { enable: [3, 2, 1] }], true, [0, -1, -1]);
                    });
                });

                describe("with focus", function() {
                    describe("resetFocusPosition == false", function() {
                        makeSuite([focusFirst], true, [0, -1, -1], 1);
                        makeSuite([focusFirst, { disable: 1 }], true, [null, 0, -1], 2);
                        makeSuite([focusFirst, { disable: [1, 2] }], true, [null, null, 0], 3);
                        makeSuite([focusFirst, { disable: [1, 2, 3] }], false, [null, null, null], null);
                        makeSuite([focusFirst, { disable: [1, 2, 3] }, { enable: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusFirst, { disable: [1, 3] }], true, [null, 0, null], 2);
                        makeSuite([focusFirst, { disable: [1, 3, 2] }], false, [null, null, null], null);
                        makeSuite([focusFirst, { disable: [1, 3, 2] }, { enable: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusFirst, { disable: 2 }], true, [0, null, -1], 1);
                        makeSuite([focusFirst, { disable: [2, 3] }], true, [0, null, null], 1);
                        makeSuite([focusFirst, { disable: [2, 3, 1] }], false, [null, null, null], null);
                        makeSuite([focusFirst, { disable: [2, 3, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusFirst, { disable: [2, 1] }], true, [null, null, 0], 3);
                        makeSuite([focusFirst, { disable: [2, 1, 3] }], false, [null, null, null], null);
                        makeSuite([focusFirst, { disable: [2, 1, 3] }, { enable: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusFirst, { disable: 3 }], true, [0, -1, null], 1);
                        makeSuite([focusFirst, { disable: [3, 2] }], true, [0, null, null], 1);
                        makeSuite([focusFirst, { disable: [3, 2, 1] }], false, [null, null, null], null);
                        makeSuite([focusFirst, { disable: [3, 2, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusFirst, { disable: [3, 1] }], true, [null, 0, null], 2);
                        makeSuite([focusFirst, { disable: [3, 1, 2] }], false, [null, null, null], null);
                        makeSuite([focusFirst, { disable: [3, 1, 2] }, { enable: [1, 2, 3 ] }], true, [-1, 0, -1], null);
                        makeSuite([focusSecond], true, [-1, 0, -1], 2);
                        makeSuite([focusSecond, { disable: 1 }], true, [null, 0, -1], 2);
                        makeSuite([focusSecond, { disable: [1, 2] }], true, [null, null, 0], 3);
                        makeSuite([focusSecond, { disable: [1, 2, 3] }],  false, [null, null, null], null);
                        makeSuite([focusSecond, { disable: [1, 2, 3] }, { enable: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusSecond, { disable: [1, 3] }], true, [null, 0, null], 2);
                        makeSuite([focusSecond, { disable: [1, 3, 2] }], false, [null, null, null], null);
                        makeSuite([focusSecond, { disable: [1, 3, 2] }, { enable: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusSecond, { disable: 2 }], true, [-1, null, 0], 3);
                        makeSuite([focusSecond, { disable: [2, 3] }], true, [0, null, null], 1);
                        makeSuite([focusSecond, { disable: [2, 3, 1] }], false, [null, null, null], null);
                        makeSuite([focusSecond, { disable: [2, 3, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusSecond, { disable: [2, 1] }], true, [null, null, 0], 3);
                        makeSuite([focusSecond, { disable: [2, 1, 3] }], false, [null, null, null], null);
                        makeSuite([focusSecond, { disable: [2, 1, 3] }, { enable: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusSecond, { disable: 3 }], true, [-1, 0, null], 2);
                        makeSuite([focusSecond, { disable: [3, 2] }], true, [0, null, null], 1);
                        makeSuite([focusSecond, { disable: [3, 2, 1] }], false, [null, null, null], null);
                        makeSuite([focusSecond, { disable: [3, 2, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusSecond, { disable: [3, 1] }], true, [null, 0, null], 2);
                        makeSuite([focusSecond, { disable: [3, 1, 2] }], false, [null, null, null], null);
                        makeSuite([focusSecond, { disable: [3, 1, 2] }, { enable: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusThird], true, [-1, -1, 0], 3);
                        makeSuite([focusThird, { disable: 1 }], true, [null, -1, 0], 3);
                        makeSuite([focusThird, { disable: [1, 2] }], true, [null, null, 0], 3);
                        makeSuite([focusThird, { disable: [1, 2, 3] }], false, [null, null, null], null);
                        makeSuite([focusThird, { disable: [1, 2, 3] }, { enable: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusThird, { disable: [1, 3] }], true, [null, 0, null], 2);
                        makeSuite([focusThird, { disable: [1, 3, 2] }], false, [null, null, null], null);
                        makeSuite([focusThird, { disable: [1, 3, 2] }, { enable: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusThird, { disable: 2 }], true, [-1, null, 0], 3);
                        makeSuite([focusThird, { disable: [2, 3] }], true, [0, null, null], 1);
                        makeSuite([focusThird, { disable: [2, 3, 1] }], false, [null, null, null], null);
                        makeSuite([focusThird, { disable: [2, 3, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusThird, { disable: [2, 1] }], true, [null, null, 0], 3);
                        makeSuite([focusThird, { disable: [2, 1, 3] }], false, [null, null, null], null);
                        makeSuite([focusThird, { disable: [2, 1, 3] }, { enable: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusThird, { disable: 3 }], true, [0, -1, null], 1);
                        makeSuite([focusThird, { disable: [3, 2] }], true, [0, null, null], 1);
                        makeSuite([focusThird, { disable: [3, 2, 1] }], false, [null, null, null], null);
                        makeSuite([focusThird, { disable: [3, 2, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusThird, { disable: [3, 1] }], true, [null, 0, null], null);
                        makeSuite([focusThird, { disable: [3, 1, 2] }], false, [null, null, null], null);
                        makeSuite([focusThird, { disable: [3, 1, 2] }, { enable: [1, 2, 3] }], true, [-1, 0, -1], null);
                    });

                    describe("resetFocusPosition == true", function() {
                        function makeResetSuite(actions, fcActive, expectations, focused) {
                            makeSuite(actions, fcActive, expectations, focused, { resetFocusPosition: true });
                        }

                        makeResetSuite([focusFirst], true, [0, -1, -1], 1);
                        makeResetSuite([focusFirst, { disable: 1 }], true, [null, 0, -1], 2);
                        makeResetSuite([focusFirst, { disable: [1, 2] }],  true, [null, null, 0], 3);
                        makeResetSuite([focusFirst, { disable: [1, 2, 3] }], false, [null, null, null], null);
                        makeResetSuite([focusFirst, { disable: [1, 2, 3] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { disable: [1, 3] }], true, [null, 0, null], 2);
                        makeResetSuite([focusFirst, { disable: [1, 3, 2] }], false, [null, null, null], null);
                        makeResetSuite([focusFirst, { disable: [1, 3, 2] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { disable: 2 }], true, [0, null, -1], 1);
                        makeResetSuite([focusFirst, { disable: [2, 3] }], true, [0, null, null], 1);
                        makeResetSuite([focusFirst, { disable: [2, 3, 1] }], false, [null, null, null], null);
                        makeResetSuite([focusFirst, { disable: [2, 3, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { disable: [2, 1] }], true, [null, null, 0], 3);
                        makeResetSuite([focusFirst, { disable: [2, 1, 3] }], false, [null, null, null], null);
                        makeResetSuite([focusFirst, { disable: [2, 1, 3] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { disable: 3 }], true, [0, -1, null], 1);
                        makeResetSuite([focusFirst, { disable: [3, 2] }], true, [0, null, null], 1);
                        makeResetSuite([focusFirst, { disable: [3, 2, 1] }], false, [null, null, null], null);
                        makeResetSuite([focusFirst, { disable: [3, 2, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { disable: [3, 1] }], true, [null, 0, null], 2);
                        makeResetSuite([focusFirst, { disable: [3, 1, 2] }], false, [null, null, null], null);
                        makeResetSuite([focusFirst, { disable: [3, 1, 2] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond], true, [-1, 0, -1], 2);
                        makeResetSuite([focusSecond, { disable: 1 }], true, [null, 0, -1], 2);
                        makeResetSuite([focusSecond, { disable: [1, 2] }], true, [null, null, 0], 3);
                        makeResetSuite([focusSecond, { disable: [1, 2, 3] }], false, [null, null, null], null);
                        makeResetSuite([focusSecond, { disable: [1, 2, 3] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { disable: [1, 3] }], true, [null, 0, null], 2);
                        makeResetSuite([focusSecond, { disable: [1, 3, 2] }], false, [null, null, null], null);
                        makeResetSuite([focusSecond, { disable: [1, 3, 2] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { disable: 2 }], true, [-1, null, 0], 3);
                        makeResetSuite([focusSecond, { disable: [2, 3] }], true, [0, null, null], 1);
                        makeResetSuite([focusSecond, { disable: [2, 3, 1] }], false, [null, null, null], null);
                        makeResetSuite([focusSecond, { disable: [2, 3, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { disable: [2, 1] }], true, [null, null, 0], 3);
                        makeResetSuite([focusSecond, { disable: [2, 1, 3] }], false, [null, null, null], null);
                        makeResetSuite([focusSecond, { disable: [2, 1, 3] }, { enable: [1, 2, 3] } ], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { disable: 3 }], true, [-1, 0, null], 2);
                        makeResetSuite([focusSecond, { disable: [3, 2] }], true, [0, null, null], 1);
                        makeResetSuite([focusSecond, { disable: [3, 2, 1] }], false, [null, null, null], null);
                        makeResetSuite([focusSecond, { disable: [3, 2, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { disable: [3, 1] }], true, [null, 0, null], 2);
                        makeResetSuite([focusSecond, { disable: [3, 1, 2] }], false, [null, null, null], null);
                        makeResetSuite([focusSecond, { disable: [3, 1, 2] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird], true, [-1, -1, 0], 3);
                        makeResetSuite([focusThird, { disable: 1 }], true, [null, -1, 0], 3);
                        makeResetSuite([focusThird, { disable: [1, 2] }], true, [null, null, 0], 3);
                        makeResetSuite([focusThird, { disable: [1, 2, 3] }], false, [null, null, null], null);
                        makeResetSuite([focusThird, { disable: [1, 2, 3] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { disable: [1, 3] }], true, [null, 0, null], 2);
                        makeResetSuite([focusThird, { disable: [1, 3, 2] }], false, [null, null, null], null);
                        makeResetSuite([focusThird, { disable: [1, 3, 2] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { disable: 2 }], true, [-1, null, 0], 3);
                        makeResetSuite([focusThird, { disable: [2, 3] }], true, [0, null, null], 1);
                        makeResetSuite([focusThird, { disable: [2, 3, 1] }], false, [null, null, null], null);
                        makeResetSuite([focusThird, { disable: [2, 3, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { disable: [2, 1] }], true, [null, null, 0], 3);
                        makeResetSuite([focusThird, { disable: [2, 1, 3] }], false, [null, null, null], null);
                        makeResetSuite([focusThird, { disable: [2, 1, 3] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { disable: 3 }], true, [0, -1, null], 1);
                        makeResetSuite([focusThird, { disable: [3, 2] }], true, [0, null, null], 1);
                        makeResetSuite([focusThird, { disable: [3, 2, 1] }], false, [null, null, null], null);
                        makeResetSuite([focusThird, { disable: [3, 2, 1] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { disable: [3, 1] }], true, [null, 0, null], 2);
                        makeResetSuite([focusThird, { disable: [3, 1, 2] }], false, [null, null, null], null);
                        makeResetSuite([focusThird, { disable: [3, 1, 2] }, { enable: [1, 2, 3] }], true, [0, -1, -1], null);
                    });
                });
            });

            describe("initially disabled children", function() {
                function makeDSuite(actions, fcActive, expectations, focused, config) {
                    makeSuite(actions, fcActive, expectations, focused, Ext.apply({
                        items: [{
                            itemId: 'first',
                            text: 'primus',
                            disabled: true
                        }, {
                            itemId: 'second',
                            text: 'secundus',
                            disabled: true
                        }, {
                            itemId: 'third',
                            text: 'tertius',
                            disabled: true
                        }]
                    }, config));
                }

                describe("no focusing", function() {
                    makeDSuite(null, false, [null, null, null], null);
                    makeDSuite({ enable: 1 }, true, [0, null, null]);
                    makeDSuite({ enable: [1, 2] }, true, [0, -1, null]);
                    makeDSuite({ enable: [1, 2, 3] }, true, [0, -1, -1]);
                    makeDSuite({ enable: [1, 3] }, true, [0, null, -1]);
                    makeDSuite({ enable: [1, 3, 2] }, true, [0, -1, -1]);
                    makeDSuite({ enable: 2 }, true, [null, 0, null]);
                    makeDSuite({ enable: [2, 1] }, true, [0, -1, null]);
                    makeDSuite({ enable: [2, 1, 3] }, true, [0, -1, -1]);
                    makeDSuite({ enable: [2, 3] }, true, [null, 0, -1]);
                    makeDSuite({ enable: [2, 3, 1] }, true, [0, -1, -1]);
                    makeDSuite({ enable: 3 }, true, [null, null, 0]);
                    makeDSuite({ enable: [3, 2] }, true, [null, 0, -1]);
                    makeDSuite({ enable: [3, 2, 1] }, true, [0, -1, -1]);
                    makeDSuite({ enable: [3, 1] }, true, [0, null, -1]);
                    makeDSuite({ enable: [3, 1, 2] }, true, [0, -1, -1]);
                });

                describe("with focusing", function() {
                    describe("resetFocusPosition == false", function() {
                        makeDSuite([{ enable: 1 }, focusFirst], true, [0, null, null], 1);
                        makeDSuite([{ enable: 1 }, focusFirst, { enable: 2 }], true, [0, -1, null], 1);
                        makeDSuite([{ enable: 1 }, focusFirst, { enable: [2, 3] }], true, [0, -1, -1], 1);
                        makeDSuite([{ enable: 1 }, focusFirst, { enable: 3 }], true, [0, null, -1], 1);
                        makeDSuite([{ enable: 1 }, focusFirst, { enable: [3, 2] }], true, [0, -1, -1], 1);
                        makeDSuite([{ enable: 2 }, focusSecond], true, [null, 0, null], 2);
                        makeDSuite([{ enable: 2 }, focusSecond, { enable: 1 }], true, [-1, 0, null], 2);
                        makeDSuite([{ enable: 2 }, focusSecond, { enable: [1, 3] }], true, [-1, 0, -1], 2);
                        makeDSuite([{ enable: 2 }, focusSecond, { enable: 3 }], true, [null, 0, -1], 2);
                        makeDSuite([{ enable: 2 }, focusSecond, { enable: [3, 1] }], true, [-1, 0, -1], 2);
                        makeDSuite([{ enable: 3 }, focusThird], true, [null, null, 0], 3);
                        makeDSuite([{ enable: 3 }, focusThird, { enable: 1 }], true, [-1, null, 0], 3);
                        makeDSuite([{ enable: 3 }, focusThird, { enable: [1, 2] }], true, [-1, -1, 0], 3);
                        makeDSuite([{ enable: 3 }, focusThird, { enable: 2 }], true, [null, -1, 0], 3);
                        makeDSuite([{ enable: 3 }, focusThird, { enable: [2, 1] }], true, [-1, -1, 0], 3);
                    });

                    describe("resetFocusPosition == true", function() {
                        function makeRSuite(actions, fcActive, expectations, focused) {
                            makeDSuite(actions, fcActive, expectations, focused, { resetFocusPosition: true });
                        }

                        makeRSuite([{ enable: 1 }, focusFirst], true, [0, null, null], 1);
                        makeRSuite([{ enable: 1 }, focusFirst, { enable: 2 }], true, [0, -1, null], 1);
                        makeRSuite([{ enable: 1 }, focusFirst, { enable: [2, 3] }], true, [0, -1, -1], 1);
                        makeRSuite([{ enable: 1 }, focusFirst, { enable: 3 }], true, [0, null, -1], 1);
                        makeRSuite([{ enable: 1 }, focusFirst, { enable: [3, 2] }], true, [0, -1, -1], 1);
                        makeRSuite([{ enable: 2 }, focusSecond], true, [null, 0, null], 2);
                        makeRSuite([{ enable: 2 }, focusSecond, { enable: 1 }], true, [-1, 0, null], 2);
                        makeRSuite([{ enable: 2 }, focusSecond, { enable: [1, 3] }], true, [-1, 0, -1], 2);
                        makeRSuite([{ enable: 2 }, focusSecond, { enable: 3 }], true, [null, 0, -1], 2);
                        makeRSuite([{ enable: 2 }, focusSecond, { enable: [3, 1] }], true, [-1, 0, -1], 2);
                        makeRSuite([{ enable: 3 }, focusThird], true, [null, null, 0], 3);
                        makeRSuite([{ enable: 3 }, focusThird, { enable: 1 }], true, [-1, null, 0], 3);
                        makeRSuite([{ enable: 3 }, focusThird, { enable: [1, 2] }], true, [-1, -1, 0], 3);
                        makeRSuite([{ enable: 3 }, focusThird, { enable: 2 }], true, [null, -1, 0], 3);
                        makeRSuite([{ enable: 3 }, focusThird, { enable: [2, 1] }], true, [-1, -1, 0], 3);
                    });
                });
            });
        });

        describe("show/hide", function() {
            describe("initially visible children", function() {
                makeSuite(false, true, [0, -1, -1]);

                describe("none focused", function() {
                    makeSuite({ hide: 1 }, true, [false, 0, -1]);
                    makeSuite({ hide: [1, 2] }, true, [false, false, 0]);
                    makeSuite({ hide: [1, 2, 3] }, false, [false, false, false]);
                    makeSuite({ hide: [1, 3] }, true, [false, 0, false]);
                    makeSuite({ hide: [1, 3, 2] }, false, [false, false, false]);
                    makeSuite({ hide: 2 }, true, [0, false, -1]);
                    makeSuite({ hide: [2, 3] }, true, [0, false, false]);
                    makeSuite({ hide: [2, 3, 1] }, false, [false, false, false]);
                    makeSuite({ hide: [2, 1] }, true, [false, false, 0]);
                    makeSuite({ hide: [2, 1, 3] }, false, [false, false, false]);
                    makeSuite({ hide: 3 }, true, [0, -1, false]);
                    makeSuite({ hide: [3, 2] }, true, [0, false, false]);
                    makeSuite({ hide: [3, 2, 1] }, false, [false, false, false]);
                    makeSuite({ hide: [3, 1] }, true, [false, 0, false]);
                    makeSuite({ hide: [3, 1, 2] }, false, [false, false, false]);

                    describe("re-show", function() {
                        makeSuite([hideItems, { show: 1 }], true, [0, false, false]);
                        makeSuite([hideItems, { show: [1, 2] }], true, [0, -1, false]);
                        makeSuite([hideItems, { show: [1, 2, 3] }], true, [0, -1, -1]);
                        makeSuite([hideItems, { show: [1, 3] }], true, [0, false, -1]);
                        makeSuite([hideItems, { show: [1, 3, 2] }], true, [0, -1, -1]);
                        makeSuite([hideItems, { show: 2 }], true, [false, 0, false]);
                        makeSuite([hideItems, { show: [2, 1] }], true, [0, -1, false]);
                        makeSuite([hideItems, { show: [2, 1, 3] }], true, [0, -1, -1]);
                        makeSuite([hideItems, { show: [2, 3] }], true, [false, 0, -1]);
                        makeSuite([hideItems, { show: [2, 3, 1] }], true, [0, -1, -1]);
                        makeSuite([hideItems, { show: 3 }], true, [false, false, 0]);
                        makeSuite([hideItems, { show: [3, 1] }], true, [0, false, -1]);
                        makeSuite([hideItems, { show: [3, 1, 2] }], true, [0, -1, -1]);
                        makeSuite([hideItems, { show: [3, 2] }], true, [false, 0, -1]);
                        makeSuite([hideItems, { show: [3, 2, 1] }], true, [0, -1, -1]);
                    });
                });

                describe("with focus", function() {
                    describe("resetFocusPosition == false", function() {
                        makeSuite([focusFirst], true, [0, -1, -1], 1);
                        makeSuite([focusFirst, { hide: 1 }], true, [false, 0, -1], 2);
                        makeSuite([focusFirst, { hide: [1, 2] }], true, [false, false, 0], 3);
                        makeSuite([focusFirst, { hide: [1, 2, 3] }], false, [false, false, false], null);
                        makeSuite([focusFirst, { hide: [1, 2, 3] }, { show: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusFirst, { hide: [1, 3] }], true, [false, 0, false], 2);
                        makeSuite([focusFirst, { hide: [1, 3, 2] }], false, [false, false, false], null);
                        makeSuite([focusFirst, { hide: [1, 3, 2] }, { show: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusFirst, { hide: 2 }], true, [0, false, -1], 1);
                        makeSuite([focusFirst, { hide: [2, 3] }], true, [0, false, false], 1);
                        makeSuite([focusFirst, { hide: [2, 3, 1] }], false, [false, false, false], null);
                        makeSuite([focusFirst, { hide: [2, 3, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusFirst, { hide: [2, 1] }], true, [false, false, 0], 3);
                        makeSuite([focusFirst, { hide: [2, 1, 3] }], false, [false, false, false], null);
                        makeSuite([focusFirst, { hide: [2, 1, 3] }, { show: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusFirst, { hide: 3 }], true, [0, -1, false], 1);
                        makeSuite([focusFirst, { hide: [3, 2] }], true, [0, false, false], 1);
                        makeSuite([focusFirst, { hide: [3, 2, 1] }], false, [false, false, false], null);
                        makeSuite([focusFirst, { hide: [3, 2, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusFirst, { hide: [3, 1] }], true, [false, 0, false], 2);
                        makeSuite([focusFirst, { hide: [3, 1, 2] }], false, [false, false, false], null);
                        makeSuite([focusFirst, { hide: [3, 1, 2] }, { show: [1, 2, 3 ] }], true, [-1, 0, -1], null);
                        makeSuite([focusSecond], true, [-1, 0, -1], 2);
                        makeSuite([focusSecond, { hide: 1 }], true, [false, 0, -1], 2);
                        makeSuite([focusSecond, { hide: [1, 2] }], true, [false, false, 0], 3);
                        makeSuite([focusSecond, { hide: [1, 2, 3] }],  false, [false, false, false], null);
                        makeSuite([focusSecond, { hide: [1, 2, 3] }, { show: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusSecond, { hide: [1, 3] }], true, [false, 0, false], 2);
                        makeSuite([focusSecond, { hide: [1, 3, 2] }], false, [false, false, false], null);
                        makeSuite([focusSecond, { hide: [1, 3, 2] }, { show: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusSecond, { hide: 2 }], true, [-1, false, 0], 3);
                        makeSuite([focusSecond, { hide: [2, 3] }], true, [0, false, false], 1);
                        makeSuite([focusSecond, { hide: [2, 3, 1] }], false, [false, false, false], null);
                        makeSuite([focusSecond, { hide: [2, 3, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusSecond, { hide: [2, 1] }], true, [false, false, 0], 3);
                        makeSuite([focusSecond, { hide: [2, 1, 3] }], false, [false, false, false], null);
                        makeSuite([focusSecond, { hide: [2, 1, 3] }, { show: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusSecond, { hide: 3 }], true, [-1, 0, false], 2);
                        makeSuite([focusSecond, { hide: [3, 2] }], true, [0, false, false], 1);
                        makeSuite([focusSecond, { hide: [3, 2, 1] }], false, [false, false, false], null);
                        makeSuite([focusSecond, { hide: [3, 2, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusSecond, { hide: [3, 1] }], true, [false, 0, false], 2);
                        makeSuite([focusSecond, { hide: [3, 1, 2] }], false, [false, false, false], null);
                        makeSuite([focusSecond, { hide: [3, 1, 2] }, { show: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusThird], true, [-1, -1, 0], 3);
                        makeSuite([focusThird, { hide: 1 }], true, [false, -1, 0], 3);
                        makeSuite([focusThird, { hide: [1, 2] }], true, [false, false, 0], 3);
                        makeSuite([focusThird, { hide: [1, 2, 3] }], false, [false, false, false], null);
                        makeSuite([focusThird, { hide: [1, 2, 3] }, { show: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusThird, { hide: [1, 3] }], true, [false, 0, false], 2);
                        makeSuite([focusThird, { hide: [1, 3, 2] }], false, [false, false, false], null);
                        makeSuite([focusThird, { hide: [1, 3, 2] }, { show: [1, 2, 3] }], true, [-1, 0, -1], null);
                        makeSuite([focusThird, { hide: 2 }], true, [-1, false, 0], 3);
                        makeSuite([focusThird, { hide: [2, 3] }], true, [0, false, false], 1);
                        makeSuite([focusThird, { hide: [2, 3, 1] }], false, [false, false, false], null);
                        makeSuite([focusThird, { hide: [2, 3, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusThird, { hide: [2, 1] }], true, [false, false, 0], 3);
                        makeSuite([focusThird, { hide: [2, 1, 3] }], false, [false, false, false], null);
                        makeSuite([focusThird, { hide: [2, 1, 3] }, { show: [1, 2, 3] }], true, [-1, -1, 0], null);
                        makeSuite([focusThird, { hide: 3 }], true, [0, -1, false], 1);
                        makeSuite([focusThird, { hide: [3, 2] }], true, [0, false, false], 1);
                        makeSuite([focusThird, { hide: [3, 2, 1] }], false, [false, false, false], null);
                        makeSuite([focusThird, { hide: [3, 2, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeSuite([focusThird, { hide: [3, 1] }], true, [false, 0, false], null);
                        makeSuite([focusThird, { hide: [3, 1, 2] }], false, [false, false, false], null);
                        makeSuite([focusThird, { hide: [3, 1, 2] }, { show: [1, 2, 3] }], true, [-1, 0, -1], null);
                    });

                    describe("resetFocusPosition == true", function() {
                        function makeResetSuite(actions, fcActive, expectations, focused) {
                            makeSuite(actions, fcActive, expectations, focused, { resetFocusPosition: true });
                        }

                        makeResetSuite([focusFirst], true, [0, -1, -1], 1);
                        makeResetSuite([focusFirst, { hide: 1 }], true, [false, 0, -1], 2);
                        makeResetSuite([focusFirst, { hide: [1, 2] }],  true, [false, false, 0], 3);
                        makeResetSuite([focusFirst, { hide: [1, 2, 3] }], false, [false, false, false], null);
                        makeResetSuite([focusFirst, { hide: [1, 2, 3] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { hide: [1, 3] }], true, [false, 0, false], 2);
                        makeResetSuite([focusFirst, { hide: [1, 3, 2] }], false, [false, false, false], null);
                        makeResetSuite([focusFirst, { hide: [1, 3, 2] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { hide: 2 }], true, [0, false, -1], 1);
                        makeResetSuite([focusFirst, { hide: [2, 3] }], true, [0, false, false], 1);
                        makeResetSuite([focusFirst, { hide: [2, 3, 1] }], false, [false, false, false], null);
                        makeResetSuite([focusFirst, { hide: [2, 3, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { hide: [2, 1] }], true, [false, false, 0], 3);
                        makeResetSuite([focusFirst, { hide: [2, 1, 3] }], false, [false, false, false], null);
                        makeResetSuite([focusFirst, { hide: [2, 1, 3] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { hide: 3 }], true, [0, -1, false], 1);
                        makeResetSuite([focusFirst, { hide: [3, 2] }], true, [0, false, false], 1);
                        makeResetSuite([focusFirst, { hide: [3, 2, 1] }], false, [false, false, false], null);
                        makeResetSuite([focusFirst, { hide: [3, 2, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusFirst, { hide: [3, 1] }], true, [false, 0, false], 2);
                        makeResetSuite([focusFirst, { hide: [3, 1, 2] }], false, [false, false, false], null);
                        makeResetSuite([focusFirst, { hide: [3, 1, 2] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond], true, [-1, 0, -1], 2);
                        makeResetSuite([focusSecond, { hide: 1 }], true, [false, 0, -1], 2);
                        makeResetSuite([focusSecond, { hide: [1, 2] }], true, [false, false, 0], 3);
                        makeResetSuite([focusSecond, { hide: [1, 2, 3] }], false, [false, false, false], null);
                        makeResetSuite([focusSecond, { hide: [1, 2, 3] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { hide: [1, 3] }], true, [false, 0, false], 2);
                        makeResetSuite([focusSecond, { hide: [1, 3, 2] }], false, [false, false, false], null);
                        makeResetSuite([focusSecond, { hide: [1, 3, 2] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { hide: 2 }], true, [-1, false, 0], 3);
                        makeResetSuite([focusSecond, { hide: [2, 3] }], true, [0, false, false], 1);
                        makeResetSuite([focusSecond, { hide: [2, 3, 1] }], false, [false, false, false], null);
                        makeResetSuite([focusSecond, { hide: [2, 3, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { hide: [2, 1] }], true, [false, false, 0], 3);
                        makeResetSuite([focusSecond, { hide: [2, 1, 3] }], false, [false, false, false], null);
                        makeResetSuite([focusSecond, { hide: [2, 1, 3] }, { show: [1, 2, 3] } ], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { hide: 3 }], true, [-1, 0, false], 2);
                        makeResetSuite([focusSecond, { hide: [3, 2] }], true, [0, false, false], 1);
                        makeResetSuite([focusSecond, { hide: [3, 2, 1] }], false, [false, false, false], null);
                        makeResetSuite([focusSecond, { hide: [3, 2, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusSecond, { hide: [3, 1] }], true, [false, 0, false], 2);
                        makeResetSuite([focusSecond, { hide: [3, 1, 2] }], false, [false, false, false], null);
                        makeResetSuite([focusSecond, { hide: [3, 1, 2] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird], true, [-1, -1, 0], 3);
                        makeResetSuite([focusThird, { hide: 1 }], true, [false, -1, 0], 3);
                        makeResetSuite([focusThird, { hide: [1, 2] }], true, [false, false, 0], 3);
                        makeResetSuite([focusThird, { hide: [1, 2, 3] }], false, [false, false, false], null);
                        makeResetSuite([focusThird, { hide: [1, 2, 3] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { hide: [1, 3] }], true, [false, 0, false], 2);
                        makeResetSuite([focusThird, { hide: [1, 3, 2] }], false, [false, false, false], null);
                        makeResetSuite([focusThird, { hide: [1, 3, 2] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { hide: 2 }], true, [-1, false, 0], 3);
                        makeResetSuite([focusThird, { hide: [2, 3] }], true, [0, false, false], 1);
                        makeResetSuite([focusThird, { hide: [2, 3, 1] }], false, [false, false, false], null);
                        makeResetSuite([focusThird, { hide: [2, 3, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { hide: [2, 1] }], true, [false, false, 0], 3);
                        makeResetSuite([focusThird, { hide: [2, 1, 3] }], false, [false, false, false], null);
                        makeResetSuite([focusThird, { hide: [2, 1, 3] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { hide: 3 }], true, [0, -1, false], 1);
                        makeResetSuite([focusThird, { hide: [3, 2] }], true, [0, false, false], 1);
                        makeResetSuite([focusThird, { hide: [3, 2, 1] }], false, [false, false, false], null);
                        makeResetSuite([focusThird, { hide: [3, 2, 1] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                        makeResetSuite([focusThird, { hide: [3, 1] }], true, [false, 0, false], 2);
                        makeResetSuite([focusThird, { hide: [3, 1, 2] }], false, [false, false, false], null);
                        makeResetSuite([focusThird, { hide: [3, 1, 2] }, { show: [1, 2, 3] }], true, [0, -1, -1], null);
                    });
                });
            });

            describe("initially hidden children", function() {
                function makeDSuite(actions, fcActive, expectations, focused, config) {
                    makeSuite(actions, fcActive, expectations, focused, Ext.apply({
                        items: [{
                            itemId: 'first',
                            text: 'primus',
                            hidden: true
                        }, {
                            itemId: 'second',
                            text: 'secundus',
                            hidden: true
                        }, {
                            itemId: 'third',
                            text: 'tertius',
                            hidden: true
                        }]
                    }, config));
                }

                describe("no focusing", function() {
                    makeDSuite(false, false, [false, false, false], null);
                    makeDSuite({ show: 1 }, true, [0, false, false]);
                    makeDSuite({ show: [1, 2] }, true, [0, -1, false]);
                    makeDSuite({ show: [1, 2, 3] }, true, [0, -1, -1]);
                    makeDSuite({ show: [1, 3] }, true, [0, false, -1]);
                    makeDSuite({ show: [1, 3, 2] }, true, [0, -1, -1]);
                    makeDSuite({ show: 2 }, true, [false, 0, false]);
                    makeDSuite({ show: [2, 1] }, true, [0, -1, false]);
                    makeDSuite({ show: [2, 1, 3] }, true, [0, -1, -1]);
                    makeDSuite({ show: [2, 3] }, true, [false, 0, -1]);
                    makeDSuite({ show: [2, 3, 1] }, true, [0, -1, -1]);
                    makeDSuite({ show: 3 }, true, [false, false, 0]);
                    makeDSuite({ show: [3, 2] }, true, [false, 0, -1]);
                    makeDSuite({ show: [3, 2, 1] }, true, [0, -1, -1]);
                    makeDSuite({ show: [3, 1] }, true, [0, false, -1]);
                    makeDSuite({ show: [3, 1, 2] }, true, [0, -1, -1]);
                });

                describe("with focusing", function() {
                    describe("resetFocusPosition == false", function() {
                        makeDSuite([{ show: 1 }, focusFirst], true, [0, false, false], 1);
                        makeDSuite([{ show: 1 }, focusFirst, { show: 2 }], true, [0, -1, false], 1);
                        makeDSuite([{ show: 1 }, focusFirst, { show: [2, 3] }], true, [0, -1, -1], 1);
                        makeDSuite([{ show: 1 }, focusFirst, { show: 3 }], true, [0, false, -1], 1);
                        makeDSuite([{ show: 1 }, focusFirst, { show: [3, 2] }], true, [0, -1, -1], 1);
                        makeDSuite([{ show: 2 }, focusSecond], true, [false, 0, false], 2);
                        makeDSuite([{ show: 2 }, focusSecond, { show: 1 }], true, [-1, 0, false], 2);
                        makeDSuite([{ show: 2 }, focusSecond, { show: [1, 3] }], true, [-1, 0, -1], 2);
                        makeDSuite([{ show: 2 }, focusSecond, { show: 3 }], true, [false, 0, -1], 2);
                        makeDSuite([{ show: 2 }, focusSecond, { show: [3, 1] }], true, [-1, 0, -1], 2);
                        makeDSuite([{ show: 3 }, focusThird], true, [false, false, 0], 3);
                        makeDSuite([{ show: 3 }, focusThird, { show: 1 }], true, [-1, false, 0], 3);
                        makeDSuite([{ show: 3 }, focusThird, { show: [1, 2] }], true, [-1, -1, 0], 3);
                        makeDSuite([{ show: 3 }, focusThird, { show: 2 }], true, [false, -1, 0], 3);
                        makeDSuite([{ show: 3 }, focusThird, { show: [2, 1] }], true, [-1, -1, 0], 3);
                    });

                    describe("resetFocusPosition == true", function() {
                        function makeRSuite(actions, fcActive, expectations, focused) {
                            makeDSuite(actions, fcActive, expectations, focused, { resetFocusPosition: true });
                        }

                        makeRSuite([{ show: 1 }, focusFirst], true, [0, false, false], 1);
                        makeRSuite([{ show: 1 }, focusFirst, { show: 2 }], true, [0, -1, false], 1);
                        makeRSuite([{ show: 1 }, focusFirst, { show: [2, 3] }], true, [0, -1, -1], 1);
                        makeRSuite([{ show: 1 }, focusFirst, { show: 3 }], true, [0, false, -1], 1);
                        makeRSuite([{ show: 1 }, focusFirst, { show: [3, 2] }], true, [0, -1, -1], 1);
                        makeRSuite([{ show: 2 }, focusSecond], true, [false, 0, false], 2);
                        makeRSuite([{ show: 2 }, focusSecond, { show: 1 }], true, [-1, 0, false], 2);
                        makeRSuite([{ show: 2 }, focusSecond, { show: [1, 3] }], true, [-1, 0, -1], 2);
                        makeRSuite([{ show: 2 }, focusSecond, { show: 3 }], true, [false, 0, -1], 2);
                        makeRSuite([{ show: 2 }, focusSecond, { show: [3, 1] }], true, [-1, 0, -1], 2);
                        makeRSuite([{ show: 3 }, focusThird], true, [false, false, 0], 3);
                        makeRSuite([{ show: 3 }, focusThird, { show: 1 }], true, [-1, false, 0], 3);
                        makeRSuite([{ show: 3 }, focusThird, { show: [1, 2] }], true, [-1, -1, 0], 3);
                        makeRSuite([{ show: 3 }, focusThird, { show: 2 }], true, [false, -1, 0], 3);
                        makeRSuite([{ show: 3 }, focusThird, { show: [2, 1] }], true, [-1, -1, 0], 3);
                    });
                });
            });
        });
    });

    describe("focus handling", function() {
        var beforeBtn, fooBtn, barBtn;

        beforeEach(function() {
            // Before button is outside of the container
            beforeBtn = makeButton({ text: 'beforeBtn' });
        });

        afterEach(function() {
            if (beforeBtn) {
                beforeBtn.destroy();
            }

            beforeBtn = null;
        });

        describe("focusableContainer === false", function() {
            beforeEach(function() {
                makeContainer({
                    focusableContainer: false,
                    items: [
                        { xtype: 'button', text: 'foo' }
                    ]
                });

                fooBtn = fc.down('button[text=foo]');

                focusAndWait(fooBtn, null, "fooBtn to recieve focus");
            });

            it("should not activate container on focusleave", function() {
                focusAndWait(beforeBtn, null, "beforeBtn to recieve focus");

                runs(function() {
                    expect(fc.isFocusableContainerActive()).toBeFalsy();
                });
            });
        });

        xdescribe("have focusables", function() {
            beforeEach(function() {
                makeContainer({
                    items: [
                        { xtype: 'button', text: 'fooBtn' },
                        { xtype: 'button', text: 'barBtn' }
                    ]
                });

                fooBtn = fc.down('button[text=fooBtn]');
                barBtn = fc.down('button[text=barBtn]');
            });

            describe("focusing container tab guards", function() {
                describe("before guard", function() {
                    describe("static set of children", function() {
                        beforeEach(function() {
                            focusAndWait(fc.tabGuardBeforeEl, fooBtn, 'tabGuardBeforeEl or fooBtn to recieve focus');
                        });

                        describe("in FocusableContainer", function() {
                            it("should focus first child", function() {
                                expectFocused(fooBtn);
                            });

                            it("should make first child tabbable", function() {
                                expect(fooBtn).toHaveAttr('tabIndex', '0');
                            });

                            it("should deactivate container", function() {
                                expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                                expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                            });
                        });

                        describe("out of FocusableContainer", function() {
                            beforeEach(function() {
                                focusAndWait(beforeBtn, null, "beforeBtn to recieve focus");
                            });

                            it("should keep first child tabbable", function() {
                                expect(fooBtn).toHaveAttr('tabIndex', '0');
                            });

                            it("should not make itself tabbable", function() {
                                expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                                expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                            });
                        });
                    });

                    describe("dynamically added children", function() {
                        var bazBtn;

                        function addButton(cfg) {
                            cfg = Ext.apply({
                                xtype: 'button',
                                text: 'bazBtn'
                            }, cfg);

                            fc.insert(0, cfg);

                            bazBtn = fc.down('button[text=bazBtn]');
                        }

                        afterEach(function() {
                            bazBtn = null;
                        });

                        describe("normal", function() {
                            beforeEach(function() {
                                addButton();
                            });

                            it("should focus new child", function() {
                                focusAndExpect(fc.tabGuardBeforeEl, bazBtn);
                            });

                            it("should not focus new child after disabling", function() {
                                bazBtn.disable();

                                focusAndExpect(fc.tabGuardBeforeEl, fooBtn);
                            });

                            it("should not focus new child after hiding", function() {
                                bazBtn.hide();

                                focusAndExpect(fc.tabGuardBeforeEl, fooBtn);
                            });
                        });

                        describe("disabled", function() {
                            beforeEach(function() {
                                addButton({ disabled: true });
                            });

                            it("should not focus new child", function() {
                                focusAndExpect(fc.tabGuardBeforeEl, fooBtn);
                            });

                            it("should focus new disabled child after enabling", function() {
                                bazBtn.enable();

                                focusAndExpect(fc.tabGuardBeforeEl, bazBtn);
                            });
                        });

                        describe("hidden", function() {
                            beforeEach(function() {
                                addButton({ hidden: true });
                            });

                            it("should not focus a new hidden child", function() {
                                focusAndExpect(fc.tabGuardBeforeEl, fooBtn);
                            });

                            it("should focus new hidden child after showing", function() {
                                bazBtn.show();

                                focusAndExpect(fc.tabGuardBeforeEl, bazBtn);
                            });
                        });
                    });
                });

                describe("after guard", function() {
                    describe("static set of children", function() {
                        beforeEach(function() {
                            focusAndWait(fc.tabGuardAfterEl, fooBtn, null, 'tabguardAfterEl or fooBtn to recieve focus');
                        });

                        describe("in FocusableContainer", function() {
                            it("should focus the first child", function() {
                                expectFocused(fooBtn);
                            });

                            it("should make first child tabbable", function() {
                                expect(fooBtn).toHaveAttr('tabIndex', '0');
                            });

                            it("should deactivate container", function() {
                                expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                                expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                            });
                        });

                        describe("out of FocusableContainer", function() {
                            beforeEach(function() {
                                focusAndWait(beforeBtn, null, "beforeBtn to recieve focus");
                            });

                            it("should keep the first child tabbable", function() {
                                expect(fooBtn).toHaveAttr('tabIndex', '0');
                            });

                            it("should not make itself tabbable", function() {
                                expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                                expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                            });
                        });
                    });

                    describe("dynamically added children", function() {
                        var bazBtn;

                        function addButton(cfg) {
                            cfg = Ext.apply({
                                xtype: 'button',
                                text: 'bazBtn'
                            }, cfg);

                            fc.insert(0, cfg);

                            bazBtn = fc.down('button[text=bazBtn]');
                        }

                        afterEach(function() {
                            bazBtn = null;
                        });

                        describe("normal", function() {
                            beforeEach(function() {
                                addButton();
                            });

                            it("should focus new child", function() {
                                focusAndExpect(fc.tabGuardAfterEl, bazBtn);
                            });

                            it("should not focus new child after disabling", function() {
                                bazBtn.disable();

                                focusAndExpect(fc.tabGuardAfterEl, fooBtn);
                            });

                            it("should not focus new child after hiding", function() {
                                bazBtn.hide();

                                focusAndExpect(fc.tabGuardAfterEl, fooBtn);
                            });
                        });

                        describe("disabled", function() {
                            beforeEach(function() {
                                addButton({ disabled: true });
                            });

                            it("should not focus new child", function() {
                                focusAndExpect(fc.tabGuardAfterEl, fooBtn);
                            });

                            it("should focus new disabled child after enabling", function() {
                                bazBtn.enable();

                                focusAndExpect(fc.tabGuardAfterEl, bazBtn);
                            });
                        });

                        describe("hidden", function() {
                            beforeEach(function() {
                                addButton({ hidden: true });
                            });

                            it("should not focus a new hidden child", function() {
                                focusAndExpect(fc.tabGuardAfterEl, fooBtn);
                            });

                            it("should focus new hidden child after showing", function() {
                                bazBtn.show();

                                focusAndExpect(fc.tabGuardAfterEl, bazBtn);
                            });
                        });
                    });
                });
            });

            describe("focusing container el", function() {
                beforeEach(function() {
                    fc.activateFocusableContainer(false);
                    fcEl.dom.setAttribute('tabIndex', '-1');
                });

                describe("static set of children", function() {
                    beforeEach(function() {
                        focusAndWait(fcEl, fooBtn, 'fcEl or fooBtn to recieve focus');
                    });

                    describe("in FocusableContainer", function() {
                        it("should focus first child", function() {
                            expectFocused(fooBtn);
                        });

                        it("should make first child tabbable", function() {
                            expect(fooBtn).toHaveAttr('tabIndex', '0');
                        });

                        it("should deactivate container", function() {
                            expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                            expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                        });
                    });

                    describe("out of FocusableContainer", function() {
                        beforeEach(function() {
                            focusAndWait(beforeBtn, null, "beforeBtn to recieve focus");
                        });

                        it("should keep first child tabbable", function() {
                            expect(fooBtn).toHaveAttr('tabIndex', '0');
                        });

                        it("should not make itself tabbable", function() {
                            expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                            expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                        });
                    });
                });

                describe("dynamically added children", function() {
                    var bazBtn;

                    function addButton(cfg) {
                        cfg = Ext.apply({
                            xtype: 'button',
                            text: 'bazBtn'
                        }, cfg);

                        fc.insert(0, cfg);

                        bazBtn = fc.down('button[text=bazBtn]');
                    }

                    afterEach(function() {
                        bazBtn = null;
                    });

                    describe("normal", function() {
                        beforeEach(function() {
                            addButton();
                        });

                        it("should focus new child", function() {
                            focusAndExpect(fcEl, bazBtn);
                        });

                        it("should not focus new child after disabling", function() {
                            bazBtn.disable();

                            focusAndExpect(fcEl, fooBtn);
                        });

                        it("should not focus new child after hiding", function() {
                            bazBtn.hide();

                            focusAndExpect(fcEl, fooBtn);
                        });
                    });

                    describe("disabled", function() {
                        beforeEach(function() {
                            addButton({ disabled: true });
                        });

                        it("should not focus new child", function() {
                            focusAndExpect(fcEl, fooBtn);
                        });

                        it("should focus new disabled child after enabling", function() {
                            bazBtn.enable();

                            focusAndExpect(fcEl, bazBtn);
                        });
                    });

                    describe("hidden", function() {
                        beforeEach(function() {
                            addButton({ hidden: true });
                        });

                        it("should not focus a new hidden child", function() {
                            focusAndExpect(fcEl, fooBtn);
                        });

                        it("should focus new hidden child after showing", function() {
                            bazBtn.show();

                            focusAndExpect(fcEl, bazBtn);
                        });
                    });
                });
            });

            describe("focusing children", function() {
                beforeEach(function() {
                    focusAndWait(fooBtn, null, "fooBtn to recieve focus");
                });

                describe("into FocusableContainer", function() {
                    it("should not prevent the child from getting focus", function() {
                        expectFocused(fooBtn);
                    });

                    it("should make the child tabbable", function() {
                        expect(fooBtn).toHaveAttr('tabIndex', '0');
                    });

                    it("should make deactivate container", function() {
                        expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                        expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                    });
                });

                describe("out of FocusableContainer", function() {
                    beforeEach(function() {
                        focusAndWait(beforeBtn, null, "beforeBtn to recieve focus");
                    });

                    it("should not prevent focus from leaving", function() {
                        expectFocused(beforeBtn);
                    });

                    it("should keep the child tabbable", function() {
                        expect(fooBtn).toHaveAttr('tabIndex', '0');
                    });

                    it("should not activate container", function() {
                        expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                        expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                    });
                });
            });

            describe("disabling currently focused child", function() {
                beforeEach(function() {
                    focusAndWait(fooBtn, null, "fooBtn to recieve focus");
                });

                describe("when there are other focusable children remaining", function() {
                    beforeEach(function() {
                        fooBtn.disable();
                    });

                    it("should focus next child", function() {
                        expectFocused(barBtn);
                    });

                    it("should not activate container", function() {
                        expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                        expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                    });

                    it("should update lastFocusedChild", function() {
                        expect(fc.lastFocusedChild).toBe(barBtn);
                    });
                });

                describe("when there are no focusable children remaining", function() {
                    beforeEach(function() {
                        barBtn.disable();

                        fooBtn.findFocusTarget = function() {
                            return beforeBtn;
                        };

                        fooBtn.disable();
                    });

                    it("should focus findFocusTarget result", function() {
                        expectFocused(beforeBtn);
                    });

                    it("should deactivate container", function() {
                        expect(fc.tabGuardBeforeEl).not.toHaveAttr('tabIndex');
                        expect(fc.tabGuardAfterEl).not.toHaveAttr('tabIndex');
                    });

                    it("should not update lastFocusedChild", function() {
                        expect(fc.lastFocusedChild).toBe(fooBtn);
                    });
                });
            });

            describe("focus is outside of the container", function() {
                beforeEach(function() {
//                     makeContainer({
//                         items: [
//                             { xtype: 'button', text: 'fooBtn' },
//                             { xtype: 'button', text: 'barBtn' }
//                         ]
//                     });
//                     
//                     fooBtn = fc.down('button[text=fooBtn]');
//                     barBtn = fc.down('button[text=barBtn]');
//                     
                    focusAndWait(fc.tabGuardBeforeEl, fooBtn, 'tabGuardBeforeEl or fooBtn to recieve focus');
                    focusAndWait(beforeBtn, null, "beforeBtn to recieve focus");
                });

                afterEach(function() {
                    if (fooBtn) {
                        fooBtn.destroy();
                    }
                });

                it("should activate container when last focused child is removed", function() {
                    fc.remove(fooBtn, false);

                    expect(fc.tabGuardBeforeEl).toHaveAttr('tabIndex', '0');
                    expect(fc.tabGuardAfterEl).toHaveAttr('tabIndex', '0');
                });

                it("should activate container when last focused child is disabled", function() {
                    fooBtn.disable();

                    expect(fc.tabGuardBeforeEl).toHaveAttr('tabIndex', '0');
                    expect(fc.tabGuardAfterEl).toHaveAttr('tabIndex', '0');
                });

                it("should activate container when last focused child is hidden", function() {
                    fooBtn.hide();

                    expect(fc.tabGuardBeforeEl).toHaveAttr('tabIndex', '0');
                    expect(fc.tabGuardAfterEl).toHaveAttr('tabIndex', '0');
                });
            });
        });
    });

    describe("keyboard event handling", function() {
        var forward = true,
            backward = false,
            beforeBtn, afterBtn, fooBtn, barBtn, bazBtn, disabledBtn1, disabledBtn2,
            fooInput, barInput, slider;

        function tabAndExpect(from, direction, to) {
            pressTabKey(from, direction);

            expectFocused(to);
        }

        function arrowAndExpect(from, arrow, to) {
            pressKey(from, arrow);

            expectFocused(to);
        }

        afterEach(function() {
            beforeBtn = afterBtn = Ext.destroy(beforeBtn, afterBtn);
            fooBtn = barBtn = bazBtn = disabledBtn1 = disabledBtn2 = null;
            fooInput = barInput = slider = null;
        });

        // Unfortunately we cannot test that the actual problem is solved,
        // which is scrolling the parent container caused by default action
        // on arrow keys. This is because synthetic injected events do not cause
        // default action. The best we can do is to check that event handlers
        // are calling preventDefault() on the events.
        // See https://sencha.jira.com/browse/EXTJS-18186
        describe("preventing parent scroll", function() {
            var upSpy, downSpy, rightSpy, leftSpy;

            beforeEach(function() {
                makeContainer({
                    renderTo: undefined,
                    items: [{
                        xtype: 'button',
                        text: 'fooBtn'
                    }, {
                        xtype: 'button',
                        text: 'barBtn'
                    }]
                });

                fooBtn = fc.down('button[text=fooBtn]');
                barBtn = fc.down('button[text=barBtn]');

                upSpy = spyOn(fc, 'onFocusableContainerUpKey').andCallThrough();
                downSpy = spyOn(fc, 'onFocusableContainerDownKey').andCallThrough();
                rightSpy = spyOn(fc, 'onFocusableContainerRightKey').andCallThrough();
                leftSpy = spyOn(fc, 'onFocusableContainerLeftKey').andCallThrough();

                fc.render(Ext.getBody());
            });

            afterEach(function() {
                fooBtn = barBtn = null;
                upSpy = downSpy = rightSpy = leftSpy = null;
            });

            describe("Up arrow", function() {
                it("should preventDefault on the Up arrow key", function() {
                    pressKey(barBtn, 'up');

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(upSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                    });
                });

                it("should not preventDefault on Shift-Up arrow key", function() {
                    pressKey(barBtn, 'up', { shift: true });

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(upSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Ctrl-Up arrow key", function() {
                    pressKey(barBtn, 'up', { ctrl: true });

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(upSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Alt-Up arrow key", function() {
                    pressKey(barBtn, 'up', { alt: true });

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(upSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("Down arrow", function() {
                it("should preventDefault on the Down arrow key", function() {
                    pressKey(fooBtn, 'down');

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(downSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                    });
                });

                it("should not preventDefault on Shift-Down arrow key", function() {
                    pressKey(fooBtn, 'down', { shift: true });

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(downSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Ctrl-Down arrow key", function() {
                    pressKey(fooBtn, 'down', { ctrl: true });

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(downSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Alt-Down arrow key", function() {
                    pressKey(fooBtn, 'down', { alt: true });

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(downSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("Right arrow", function() {
                it("should preventDefault on the Right arrow key", function() {
                    pressKey(fooBtn, 'right');

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(rightSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                    });
                });

                it("should not preventDefault on Shift-Right arrow key", function() {
                    pressKey(fooBtn, 'right', { shift: true });

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(rightSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Ctrl-Right arrow key", function() {
                    pressKey(fooBtn, 'right', { ctrl: true });

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(rightSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Alt-Right arrow key", function() {
                    pressKey(fooBtn, 'right', { alt: true });

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(rightSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("Left arrow", function() {
                it("should preventDefault on the Left arrow key", function() {
                    pressKey(barBtn, 'left');

                    waitForFocus(fooBtn);

                    runs(function() {
                        expect(leftSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                    });
                });

                it("should not preventDefault on Shift-Left arrow key", function() {
                    pressKey(barBtn, 'left', { shift: true });

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(upSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Ctrl-Left arrow key", function() {
                    pressKey(barBtn, 'left', { ctrl: true });

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(upSpy).not.toHaveBeenCalled();
                    });
                });

                it("should not preventDefault on Alt-Left arrow key", function() {
                    pressKey(barBtn, 'left', { alt: true });

                    waitForFocus(barBtn);

                    runs(function() {
                        expect(upSpy).not.toHaveBeenCalled();
                    });
                });
            });
        });

        describe("focusableContainer === true", function() {
            beforeEach(function() {
                runs(function() {
                    beforeBtn = makeButton({ text: 'beforeBtn' });

                    makeContainer({
                        items: [
                            { xtype: 'component', html: '**' },
                            { xtype: 'button', text: 'disabledBtn1', disabled: true },
                            { xtype: 'button', text: 'fooBtn' },
                            { xtype: 'component' },
                            { xtype: 'component' },
                            { xtype: 'button', text: 'barBtn' },
                            { xtype: 'component' },
                            { xtype: 'button', text: 'bazBtn' },
                            { xtype: 'button', text: 'disabledBtn2', disabled: true },
                            { xtype: 'component', html: '***' }
                        ]
                    });

                    fooBtn = fc.down('button[text=fooBtn]');
                    barBtn = fc.down('button[text=barBtn]');
                    bazBtn = fc.down('button[text=bazBtn]');

                    disabledBtn1 = fc.down('button[text=disabledBtn1]');
                    disabledBtn2 = fc.down('button[text=disabledBtn2]');

                    afterBtn = makeButton({ text: 'afterBtn' });
                });

                jasmine.waitAWhile();
            });

            describe("tabbing", function() {
                describe("clean state in/out", function() {
                    it("should tab from beforeBtn to fooBtn", function() {
                        tabAndExpect(beforeBtn, forward, fooBtn);
                    });

                    it("should shift-tab from fooBtn fo beforeBtn", function() {
                        tabAndExpect(fooBtn, backward, beforeBtn);
                    });

                    it("should tab from fooBtn to afterBtn", function() {
                        tabAndExpect(fooBtn, forward, afterBtn);
                    });

                    it("should shift-tab from afterBtn to fooBtn", function() {
                        tabAndExpect(afterBtn, backward, fooBtn);
                    });
                });

                describe("last focused child", function() {
                    it("should shift-tab back to barBtn from afterBtn", function() {
                        tabAndExpect(barBtn, forward, afterBtn);
                        tabAndExpect(afterBtn, backward, barBtn);
                    });

                    describe("disabled state changes", function() {
                        it("should choose fooBtn when shift-tabbing from afterBtn", function() {
                            tabAndExpect(barBtn, forward, afterBtn);

                            runs(function() {
                                barBtn.disable();
                            });

                            tabAndExpect(afterBtn, backward, fooBtn);
                        });

                        it("should choose fooBtn when tabbing from beforeBtn", function() {
                            tabAndExpect(barBtn, backward, beforeBtn);

                            runs(function() {
                                barBtn.disable();
                            });

                            tabAndExpect(beforeBtn, forward, fooBtn);
                        });
                    });
                });
            });

            describe("arrow keys", function() {
                it("should go right from fooBtn to barBtn", function() {
                    arrowAndExpect(fooBtn, 'right', barBtn);
                });

                it("should go right from barBtn to bazBtn", function() {
                    arrowAndExpect(barBtn, 'right', bazBtn);
                });

                it("should wrap over right from bazBtn to fooBtn", function() {
                    arrowAndExpect(bazBtn, 'right', fooBtn);
                });

                it("should go down from fooBtn to barBtn", function() {
                    arrowAndExpect(fooBtn, 'down', barBtn);
                });

                it("should go down from barBtn to bazBtn", function() {
                    arrowAndExpect(barBtn, 'down', bazBtn);
                });

                it("should wrap over down from bazBtn to fooBtn", function() {
                    arrowAndExpect(bazBtn, 'down', fooBtn);
                });

                it("should wrap over left from fooBtn to bazBtn", function() {
                    arrowAndExpect(fooBtn, 'left', bazBtn);
                });

                it("should go left from bazBtn to barBtn", function() {
                    arrowAndExpect(bazBtn, 'left', barBtn);
                });

                it("should go left from barBtn to fooBtn", function() {
                    arrowAndExpect(barBtn, 'left', fooBtn);
                });

                it("should wrap over up from fooBtn to bazBtn", function() {
                    arrowAndExpect(fooBtn, 'up', bazBtn);
                });

                it("should go up from bazBtn to barBtn", function() {
                    arrowAndExpect(bazBtn, 'up', barBtn);
                });

                it("should go up from barBtn to fooBtn", function() {
                    arrowAndExpect(barBtn, 'up', fooBtn);
                });
            });
        });

        // TODO Revisit after https://sencha.jira.com/browse/EXT-205 is merged
        (isModern ? xdescribe : describe)("focusableContainer === false", function() {
            beforeEach(function() {
                runs(function() {
                    beforeBtn = makeButton({ text: 'beforeBtn' });

                    makeContainer({
                        renderTo: undefined,
                        items: [
                            { xtype: 'component', html: '**' },
                            { xtype: 'button', text: 'disabledBtn1', disabled: true },
                            { xtype: 'button', text: 'fooBtn' },
                            { xtype: 'component' },
                            { xtype: 'textfield', id: 'fooInput-' + ++autoId },
                            { xtype: 'component' },
                            {
                                xtype: 'slider',
                                id: 'slider-' + ++autoId,
                                value: 50,
                                width: 100,
                                animate: false
                            },
                            { xtype: 'component' },
                            { xtype: 'component' },
                            { xtype: 'component' },
                            { xtype: 'button', text: 'barBtn' },
                            { xtype: 'button', text: 'disabledBtn2', disabled: true },
                            { xtype: isModern ? 'selectfield' : 'combobox', id: 'barInput-' + ++autoId },
                            { xtype: 'component', html: '***' }
                        ]
                    });

                    fooBtn = fc.down('button[text=fooBtn]');
                    barBtn = fc.down('button[text=barBtn]');

                    fooInput = fc.down('textfield');
                    barInput = fc.down(isModern ? 'selectfield' : 'combobox');
                    slider   = fc.down('slider');

                    disabledBtn1 = fc.down('button[text=disabledBtn1]');
                    disabledBtn2 = fc.down('button[text=disabledBtn2]');

                    fc.focusableContainer = false;
                    fc.render(Ext.getBody());

                    afterBtn = makeButton({ text: 'afterBtn' });
                });

                jasmine.waitAWhile();
            });

            afterEach(function() {
                beforeBtn.destroy();
                afterBtn.destroy();
            });

            describe("tabbing", function() {
                it("should tab from beforeBtn to fooBtn", function() {
                    tabAndExpect(beforeBtn, forward, fooBtn);
                });

                it("should shift-tab from fooBtn to beforeBtn", function() {
                    tabAndExpect(fooBtn, backward, beforeBtn);
                });

                it("should tab from fooBtn to fooInput", function() {
                    tabAndExpect(fooBtn, forward, fooInput);
                });

                it("should shift-tab from fooInput to fooBtn", function() {
                    tabAndExpect(fooInput, backward, fooBtn);
                });

                it("should tab from fooInput to slider", function() {
                    tabAndExpect(fooInput, forward, slider);
                });

                it("should shift-tab from slider to fooInput", function() {
                    tabAndExpect(slider, backward, fooInput);
                });

                it("should tab from slider to barBtn", function() {
                    tabAndExpect(slider, forward, barBtn);
                });

                it("should shift-tab from barBtn to slider", function() {
                    tabAndExpect(barBtn, backward, slider);
                });

                it("should tab from barBtn to barInput", function() {
                    tabAndExpect(barBtn, forward, barInput);
                });

                it("should shift-tab from barInput to barBtn", function() {
                    tabAndExpect(barInput, backward, barBtn);
                });

                it("should tab from barInput to afterBtn", function() {
                    tabAndExpect(barInput, forward, afterBtn);
                });

                it("should shift-tab from afterBtn to barInput", function() {
                    tabAndExpect(afterBtn, backward, barInput);
                });

                describe("disabled state changes", function() {
                    beforeEach(function() {
                        disabledBtn1.enable();
                        disabledBtn2.enable();
                    });

                    it("should tab from beforeBtn to disabledBtn1", function() {
                        tabAndExpect(beforeBtn, forward, disabledBtn1);
                    });

                    it("should shift-tab from disabledBtn1 to beforeBtn", function() {
                        tabAndExpect(disabledBtn1, backward, beforeBtn);
                    });

                    it("should tab from disabledBtn1 to fooBtn", function() {
                        tabAndExpect(disabledBtn1, forward, fooBtn);
                    });

                    it("should shift-tab from fooBtn to disabledBtn1", function() {
                        tabAndExpect(fooBtn, backward, disabledBtn1);
                    });

                    it("should tab from barBtn to disabledBtn2", function() {
                        tabAndExpect(barBtn, forward, disabledBtn2);
                    });

                    it("should shift-tab from disabledBtn2 to barBtn", function() {
                        tabAndExpect(disabledBtn2, backward, barBtn);
                    });

                    it("should tab from disabledBtn2 to barInput", function() {
                        tabAndExpect(disabledBtn2, forward, barInput);
                    });

                    it("should shift-tab from barInput to disabledBtn2", function() {
                        tabAndExpect(barInput, backward, disabledBtn2);
                    });
                });
            });

            // Arrow keys should not navigate when FocusableContainer is disabled;
            // we have to make sure of that!
            describe("arrow keys", function() {
                describe("fooBtn", function() {
                    it("should stay focused on left arrow", function() {
                        arrowAndExpect(fooBtn, 'left', fooBtn);
                    });

                    it("should stay focused on right arrow", function() {
                        arrowAndExpect(fooBtn, 'right', fooBtn);
                    });

                    it("should stay focused on up arrow", function() {
                        arrowAndExpect(fooBtn, 'up', fooBtn);
                    });

                    it("should stay focused on down arrow", function() {
                        arrowAndExpect(fooBtn, 'down', fooBtn);
                    });
                });

                describe("slider", function() {
                    function makeSpec(key) {
                        it("should not block " + key + " arrow key", function() {
                            var changeSpy = jasmine.createSpy('slider change');

                            slider.on('change', changeSpy);

                            pressKey(slider, key);

                            waitForSpyCalled(changeSpy);
                        });
                    }

                    makeSpec('left');
                    makeSpec('right');
                    makeSpec('up');
                    makeSpec('down');
                });

                describe("combo box", function() {
                    beforeEach(function() {
                        Ext.apply(barInput, {
                            queryMode: 'local',
                            displayField: 'name'
                        });

                        var store = new Ext.data.Store({
                            fields: ['name'],
                            data: [{ name: 'foo' }]
                        });

                        barInput.setStore(store);
                    });

                    it("should not block down arrow key", function() {
                        pressKey(barInput, 'down');

                        runs(function() {
                            expect(barInput.isExpanded).toBeTruthy();
                        });
                    });
                });
            });
        });
    });
});
