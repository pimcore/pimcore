topSuite("Ext.button.Split", function() {
    var button;

    function makeButton(config) {
        config = Ext.apply({
            renderTo: Ext.getBody(),
            text: 'foo'
        }, config);

        return button = new Ext.button.Split(config);
    }

    afterEach(function() {
        if (button) {
            button.destroy();
        }

        button = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.SplitButton as the alternate class name", function() {
            expect(Ext.button.Split.prototype.alternateClassName).toEqual("Ext.SplitButton");
        });

        it("should allow the use of Ext.SplitButton", function() {
            expect(Ext.SplitButton).toBeDefined();
        });
    });

    describe("arrowEl", function() {
        it("should render arrowEl", function() {
            makeButton();
            expect(button.arrowEl.dom.nodeName).toBe('SPAN');
            expect(button.arrowEl.isVisible()).toBe(true);
        });

        it("should hide arrowEl when arrowVisible:false", function() {
            makeButton({
                arrowVisible: false
            });
            expect(button.arrowEl.dom.nodeName).toBe('SPAN');
            expect(button.arrowEl.isVisible()).toBe(false);
        });
    });

    describe("ARIA attributes", function() {
        describe("tabindex", function() {
            describe("default", function() {
                beforeEach(function() {
                    makeButton();
                });

                it('should have tabindex="0" on the main el', function() {
                    expect(button).toHaveAttr('tabIndex', '0');
                });

                it('should have tabindex="0" on the arrowEl', function() {
                    expect(button.arrowEl).toHaveAttr('tabIndex', '0');
                });
            });

            describe("configured", function() {
                beforeEach(function() {
                    makeButton({ tabIndex: -10 });
                });

                it('should have tabindex="-10" on the main el', function() {
                    expect(button).toHaveAttr('tabIndex', '-10');
                });

                it('should have tabindex="-10" on the arrowEl', function() {
                    expect(button.arrowEl).toHaveAttr('tabIndex', '-10');
                });
            });

            describe("when disabled", function() {
                beforeEach(function() {
                    makeButton({ disabled: true });
                });

                it('should have no tabindex on the main el', function() {
                    expect(button).not.toHaveAttr('tabIndex');
                });

                it("should have no tabindex on the arrowEl", function() {
                    expect(button.arrowEl).not.toHaveAttr('tabIndex');
                });
            });

            describe("setTabIndex", function() {
                beforeEach(function() {
                    makeButton();
                    button.setTabIndex(42);
                });

                it('should have tabindex="42" on the main el', function() {
                    expect(button).toHaveAttr('tabIndex', '42');
                });

                it('should have tabindex="42" on the arrowEl', function() {
                    expect(button.arrowEl).toHaveAttr('tabIndex', '42');
                });
            });

            describe("disabling", function() {
                beforeEach(function() {
                    makeButton({ tabIndex: 99 });
                    button.disable();
                });

                it("should have tabindex removed from the main el", function() {
                    expect(button).not.toHaveAttr('tabIndex');
                });

                it("should have tabindex removed from the arrowEl", function() {
                    expect(button.arrowEl).not.toHaveAttr('tabIndex');
                });

                describe("enabling", function() {
                    beforeEach(function() {
                        button.enable();
                    });

                    it('should have tabindex="99" on the main el', function() {
                        expect(button).toHaveAttr('tabIndex', '99');
                    });

                    it('should have tabindex="99" on the arrowEl', function() {
                        expect(button.arrowEl).toHaveAttr('tabIndex', '99');
                    });
                });
            });
        });

        describe("role", function() {
            beforeEach(function() {
                makeButton();
            });

            it("should have button role on the main el", function() {
                expect(button).toHaveAttr('role', 'button');
            });

            it("should have button role on the arrowEl", function() {
                expect(button.arrowEl).toHaveAttr('role', 'button');
            });
        });

        describe("aria-hidden", function() {
            describe("default", function() {
                beforeEach(function() {
                    makeButton();
                });

                it("should be set to false on the main el", function() {
                    expect(button).toHaveAttr('aria-hidden', 'false');
                });

                it("should be set to false on the arrowEl", function() {
                    expect(button.arrowEl).toHaveAttr('aria-hidden', 'false');
                });

                describe("hiding", function() {
                    beforeEach(function() {
                        button.hide();
                    });

                    it("should be set to true on the main el", function() {
                        expect(button).toHaveAttr('aria-hidden', 'true');
                    });

                    it("should be set to true on the arrowEl", function() {
                        expect(button.arrowEl).toHaveAttr('aria-hidden', 'true');
                    });

                    describe("showing", function() {
                        beforeEach(function() {
                            button.show();
                        });

                        it("should be set to false on the main el", function() {
                            expect(button).toHaveAttr('aria-hidden', 'false');
                        });

                        it("should be set to false on the arrowEl", function() {
                            expect(button.arrowEl).toHaveAttr('aria-hidden', 'false');
                        });
                    });
                });
            });

            describe("configured hidden", function() {
                beforeEach(function() {
                    makeButton({ hidden: true });
                });

                it("should be set to true on the main el", function() {
                    expect(button).toHaveAttr('aria-hidden', 'true');
                });

                it("should be set to true on the arrowEl", function() {
                    expect(button.arrowEl).toHaveAttr('aria-hidden', 'true');
                });
            });
        });

        describe("aria-disabled", function() {
            describe("default", function() {
                beforeEach(function() {
                    makeButton();
                });

                it("should be set to false on the main el", function() {
                    expect(button).toHaveAttr('aria-disabled', 'false');
                });

                it("should be set to false on the arrowEl", function() {
                    expect(button.arrowEl).toHaveAttr('aria-disabled', 'false');
                });

                describe("disabling", function() {
                    beforeEach(function() {
                        button.disable();
                    });

                    it("should be set to true on the main el", function() {
                        expect(button).toHaveAttr('aria-disabled', 'true');
                    });

                    it("should be set to true on the arrowEl", function() {
                        expect(button.arrowEl).toHaveAttr('aria-disabled', 'true');
                    });

                    describe("enabling", function() {
                        beforeEach(function() {
                            button.enable();
                        });

                        it("should be set to false on the main el", function() {
                            expect(button).toHaveAttr('aria-disabled', 'false');
                        });

                        it("should be set to false on the arrowEl", function() {
                            expect(button.arrowEl).toHaveAttr('aria-disabled', 'false');
                        });
                    });
                });
            });

            describe("configured disabled", function() {
                beforeEach(function() {
                    makeButton({ disabled: true });
                });

                it("should be set to true on the main el", function() {
                    expect(button).toHaveAttr('aria-disabled', 'true');
                });

                it("should be set to true on the arrowEl", function() {
                    expect(button.arrowEl).toHaveAttr('aria-disabled', 'true');
                });
            });
        });

        describe("labelling", function() {
            describe("with arrowTooltip", function() {
                beforeEach(function() {
                    makeButton({ arrowTooltip: 'fee fie foe foo' });
                });

                it("should have aria-label", function() {
                    expect(button.arrowEl).toHaveAttr('aria-label', 'fee fie foe foo');
                });

                it("should not have aria-labelledby", function() {
                    expect(button.arrowEl).not.toHaveAttr('aria-labelledby');
                });
            });

            describe("no arrowTooltip", function() {
                beforeEach(function() {
                    makeButton();
                });

                it("should have aria-labelledby", function() {
                    expect(button.arrowEl).toHaveAttr('aria-labelledby', button.el.id);
                });

                it("should not have aria-label", function() {
                    expect(button.arrowEl).not.toHaveAttr('aria-label');
                });
            });
        });
    });

    describe("focus styling", function() {
        var before;

        beforeEach(function() {
            before = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'before'
            });

            makeButton();
        });

        afterEach(function() {
            before.destroy();
            before = null;
        });

        describe("focusing main el", function() {
            beforeEach(function() {
                focusAndWait(button);
            });

            it("should add focusCls", function() {
                expect(button.el.hasCls('x-btn-focus')).toBe(true);
            });

            it("should not add x-arrow-focus", function() {
                expect(button.el.hasCls('x-arrow-focus')).toBe(false);
            });

            describe("blurring main el", function() {
                beforeEach(function() {
                    focusAndWait(before);
                });

                it("should remove x-btn-focus", function() {
                    expect(button.el.hasCls('x-btn-focus')).toBe(false);
                });

                it("should not have x-arrow-focus", function() {
                    expect(button.el.hasCls('x-arrow-focus')).toBe(false);
                });
            });
        });

        describe("focusing arrowEl", function() {
            beforeEach(function() {
                focusAndWait(button.arrowEl);
            });

            it("should add x-arrow-focus", function() {
                expect(button.el.hasCls('x-arrow-focus')).toBe(true);
            });

            it("should not add x-btn-focus", function() {
                expect(button.el.hasCls('x-btn-focus')).toBe(false);
            });

            describe("blurring arrowEl", function() {
                beforeEach(function() {
                    focusAndWait(before);
                });

                it("should remove x-arrow-focus", function() {
                    expect(button.el.hasCls('x-arrow-focus')).toBe(false);
                });

                it("should not have x-btn-focus", function() {
                    expect(button.el.hasCls('x-btn-focus')).toBe(false);
                });
            });
        });
    });

    describe("events", function() {
        var before, focusSpy, blurSpy, elFocusSpy, arrowElFocusSpy, beforeFocusSpy;

        beforeEach(function() {
            beforeFocusSpy = jasmine.createSpy('beforeFocusSpy');

            before = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'before',
                listeners: {
                    focus: beforeFocusSpy
                }
            });

            // Component events
            focusSpy = jasmine.createSpy('focus');
            blurSpy  = jasmine.createSpy('blur');

            makeButton({
                listeners: {
                    focus: focusSpy,
                    blur: blurSpy
                }
            });

            // Element events
            elFocusSpy      = jasmine.createSpy('elFocus');
            arrowElFocusSpy = jasmine.createSpy('arrowElFocus');

            button.el.on('focus', elFocusSpy);
            button.arrowEl.on('focus', arrowElFocusSpy);
        });

        afterEach(function() {
            before.destroy();
            before = focusSpy = blurSpy = beforeFocusSpy = null;
            elFocusSpy = arrowElFocusSpy = null;
        });

        describe("focus", function() {
            beforeEach(function() {
                focusAndWait(before);

                waitForSpy(beforeFocusSpy);
            });

            it("should fire when main el is focused from the outside", function() {
                focusAndWait(button.el);

                waitForSpy(elFocusSpy);

                runs(function() {
                    expect(focusSpy).toHaveBeenCalled();
                });
            });

            it("should fire when arrowEl is focused from the outside", function() {
                focusAndWait(button.arrowEl);

                waitForSpy(arrowElFocusSpy);

                runs(function() {
                    expect(focusSpy).toHaveBeenCalled();
                });
            });

            it("should not fire when focus moved from main el to arrowEl", function() {
                focusAndWait(button.el);
                focusAndWait(button.arrowEl);

                waitForSpy(arrowElFocusSpy);

                runs(function() {
                    // First time is when the main el is focused
                    expect(focusSpy.callCount).toBe(1);
                });
            });

            it("should not fire when focus moved from arrowEl to main el", function() {
                focusAndWait(button.arrowEl);
                focusAndWait(button.el);

                waitForSpy(elFocusSpy);

                runs(function() {
                    // First time is when the arrowEl is focused
                    expect(focusSpy.callCount).toBe(1);
                });
            });
        });

        describe("blur", function() {
            it("should fire when main el is blurring to the outside", function() {
                focusAndWait(button.el);

                waitForSpy(elFocusSpy);

                focusAndWait(before);

                waitForSpy(beforeFocusSpy);

                runs(function() {
                    expect(blurSpy).toHaveBeenCalled();
                });
            });

            it("should fire when arrowEl is blurring to the outside", function() {
                focusAndWait(button.arrowEl);

                waitForSpy(arrowElFocusSpy);

                focusAndWait(before);

                waitForSpy(beforeFocusSpy);

                runs(function() {
                    expect(blurSpy).toHaveBeenCalled();
                });
            });

            it("should not fire when focus moved from main el to arrowEl", function() {
                focusAndWait(button.el);

                waitForSpy(elFocusSpy);

                focusAndWait(button.arrowEl);

                waitForSpy(arrowElFocusSpy);

                runs(function() {
                    expect(blurSpy).not.toHaveBeenCalled();
                });
            });

            it("should not fire when focus moved from arrowEl to main el", function() {
                focusAndWait(button.arrowEl);

                waitForSpy(arrowElFocusSpy);

                focusAndWait(button.el);

                waitForSpy(elFocusSpy);

                runs(function() {
                    expect(blurSpy).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe("dynamic setMenu", function() {
        describe("removing menu", function() {
            beforeEach(function() {
                makeButton({
                    tabIndex: 1,
                    menu: [{
                        text: 'item 1'
                    }, {
                        text: 'item 2'
                    }]
                });

                button.setMenu(null);
            });

            it("should remove tabindex from arrowEl", function() {
                expect(button.arrowEl).not.toHaveAttr('tabIndex');
            });

            it("should set display:none on arrowEl", function() {
                expect(button.arrowEl.dom.style.display).toBe('none');
            });

            describe("re-adding menu", function() {
                beforeEach(function() {
                    button.setMenu({
                        items: [{
                            text: 'foo 1'
                        }, {
                            text: 'foo 2'
                        }]
                    });
                });

                it("should add tabindex to arrowEl", function() {
                    expect(button.arrowEl).toHaveAttr('tabIndex', '1');
                });

                it("should remove display:none from arrowEl", function() {
                    expect(button.arrowEl.isVisible(true)).toBe(true);
                });
            });
        });
    });

    describe("keyboard interaction", function() {
        var pressKey = jasmine.pressKey,
            clickSpy, enterSpy, downSpy;

        afterEach(function() {
            clickSpy = enterSpy = downSpy = null;
        });

        describe("keydown processing", function() {
            beforeEach(function() {
                makeButton({ renderTo: undefined });

                enterSpy = spyOn(button, 'onEnterKey').andCallThrough();
                downSpy  = spyOn(button, 'onDownKey').andCallThrough();
                clickSpy = spyOn(button, 'onClick').andCallThrough();

                button.render(Ext.getBody());
            });

            describe("Space key", function() {
                beforeEach(function() {
                    pressKey(button.arrowEl, 'space');
                });

                it("should call onClick", function() {
                    expect(clickSpy).toHaveBeenCalled();
                });

                it("should stop the keydown event", function() {
                    var args = enterSpy.mostRecentCall.args;

                    expect(args[0].stopped).toBe(true);
                });

                it("should return false to stop propagation", function() {
                    expect(enterSpy.mostRecentCall.result).toBe(false);
                });
            });

            describe("Enter key", function() {
                beforeEach(function() {
                    pressKey(button.arrowEl, 'enter');
                });

                it("should call onClick", function() {
                    expect(clickSpy).toHaveBeenCalled();
                });

                it("should stop the keydown event", function() {
                    var args = enterSpy.mostRecentCall.args;

                    expect(args[0].stopped).toBeTruthy();
                });

                it("should return false to stop propagation", function() {
                    expect(enterSpy.mostRecentCall.result).toBe(false);
                });
            });

            describe("Down arrow key", function() {
                beforeEach(function() {
                    pressKey(button.arrowEl, 'down');
                });

                it("should NOT call onClick", function() {
                    expect(clickSpy).not.toHaveBeenCalled();
                });

                it("should NOT stop the keydown event", function() {
                    var args = downSpy.mostRecentCall.args;

                    expect(args[0].stopped).toBeFalsy();
                });

                it("should NOT return false to stop propagation", function() {
                    expect(downSpy.mostRecentCall.result).not.toBeDefined();
                });
            });
        });

        describe("with menu", function() {
            beforeEach(function() {
                makeButton({
                    renderTo: undefined,
                    menu: [{
                        text: 'foo'
                    }, {
                        text: 'bar'
                    }]
                });

                enterSpy = spyOn(button, 'onEnterKey').andCallThrough();
                downSpy  = spyOn(button, 'onDownKey').andCallThrough();
                clickSpy = spyOn(button, 'onClick').andCallThrough();

                button.render(Ext.getBody());
            });

            it("should open the menu on Space key", function() {
                pressKey(button.arrowEl, 'space');

                waitForSpy(enterSpy);

                runs(function() {
                    expect(button.menu.isVisible()).toBe(true);
                });
            });

            it("should open the menu on Enter key", function() {
                pressKey(button.arrowEl, 'enter');

                waitForSpy(enterSpy);

                runs(function() {
                    expect(button.menu.isVisible()).toBe(true);
                });
            });

            it("should open the menu on Down arrow key", function() {
                pressKey(button.arrowEl, 'down');

                waitForSpy(downSpy);

                runs(function() {
                    expect(button.menu.isVisible()).toBe(true);
                });
            });
        });

        describe("with arrowHandler", function() {
            var handlerSpy;

            beforeEach(function() {
                handlerSpy = jasmine.createSpy('arrowHandler');

                makeButton({
                    renderTo: undefined,
                    arrowHandler: handlerSpy
                });

                enterSpy = spyOn(button, 'onEnterKey').andCallThrough();
                downSpy  = spyOn(button, 'onDownKey').andCallThrough();
                clickSpy = spyOn(button, 'onClick').andCallThrough();

                button.render(Ext.getBody());
            });

            it("should fire the handler on Space key", function() {
                pressKey(button.arrowEl, 'space');

                waitForSpy(enterSpy);

                runs(function() {
                    expect(handlerSpy).toHaveBeenCalled();
                });
            });

            it("should fire the handler on Enter key", function() {
                pressKey(button.arrowEl, 'enter');

                waitForSpy(enterSpy);

                runs(function() {
                    expect(handlerSpy).toHaveBeenCalled();
                });
            });

            it("should not fire the handler on down arrow key", function() {
                pressKey(button.arrowEl, 'down');

                waitForSpy(downSpy);

                runs(function() {
                    expect(handlerSpy).not.toHaveBeenCalled();
                });
            });
        });
    });
});
