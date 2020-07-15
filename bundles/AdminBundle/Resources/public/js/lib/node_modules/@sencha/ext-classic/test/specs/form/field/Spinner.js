/* global expect, spyOn, Ext, jasmine */

topSuite("Ext.form.field.Spinner", function() {
    var component, makeComponent;

    beforeEach(function() {
        makeComponent = function(config) {
            config = config || {};
            Ext.applyIf(config, {
                name: 'test',
                onSpinUp: jasmine.createSpy(),
                onSpinDown: jasmine.createSpy()
            });
            component = new Ext.form.field.Spinner(config);
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });
        it("should have spinUpEnabled = true", function() {
            expect(component.spinUpEnabled).toBe(true);
        });
        it("should have spinDownEnabled = true", function() {
            expect(component.spinDownEnabled).toBe(true);
        });
        it("should have keyNavEnabled = true", function() {
            expect(component.keyNavEnabled).toBe(true);
        });
    });

    describe("rendering", function() {
        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
        });

        it("should create a 'spinUpEl' trigger button", function() {
            expect(component.spinUpEl).toBeDefined();
        });
        it("should give the spinUpEl class='x-form-spinner-up'", function() {
            expect(component.spinUpEl.hasCls('x-form-spinner-up')).toBe(true);
        });
        it("should create a 'spinDownEl' trigger button", function() {
            expect(component.spinDownEl).toBeDefined();
        });
        it("should give the spinDownEl class='x-form-spinner-down'", function() {
            expect(component.spinDownEl.hasCls('x-form-spinner-down')).toBe(true);
        });
    });

    describe("trigger click", function() {
        function fireClick(el) {
            jasmine.fireMouseEvent(el, 'click');
        }

        it("should invoke the 'onSpinUp' method when clicking the up trigger", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            fireClick(component.spinUpEl);
            expect(component.onSpinUp).toHaveBeenCalled();
        });
        it("should not invoke the 'onSpinUp' method if spinUpEnabled = false", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                spinUpEnabled: false
            });
            fireClick(component.spinUpEl);
            expect(component.onSpinUp).not.toHaveBeenCalled();
        });
        it("should invoke the 'onSpinDown' method when clicking the down trigger", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            fireClick(component.spinDownEl);
            expect(component.onSpinDown).toHaveBeenCalled();
        });
        it("should not invoke the 'onSpinDown' method if spinDownEnabled = false", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                spinDownEnabled: false
            });
            fireClick(component.spinDownEl);
            expect(component.onSpinDown).not.toHaveBeenCalled();
        });
    });

    describe("setSpinUpEnabled", function() {
        describe("false", function() {
            beforeEach(function() {
                makeComponent({
                    spinUpEnabled: true,
                    renderTo: Ext.getBody()
                });
                component.setSpinUpEnabled(false);
            });

            it("should set the spinUpEnabled property to false", function() {
                expect(component.spinUpEnabled).toBe(false);
            });

            it("should add the 'x-form-spinner-up-disabled' class", function() {
                expect(component.spinUpEl.hasCls('x-form-spinner-up-disabled')).toBe(true);
            });
        });

        describe("true", function() {
            beforeEach(function() {
                makeComponent({
                    spinUpEnabled: false,
                    renderTo: Ext.getBody()
                });
                component.setSpinUpEnabled(true);
            });

            it("should set the spinUpEnabled property to true", function() {
                expect(component.spinUpEnabled).toBe(true);
            });

            it("should remove the 'x-form-spinner-up-disabled' class", function() {
                expect(component.spinUpEl.hasCls('x-form-spinner-up-disabled')).toBe(false);
            });
        });
    });

    describe("setSpinDownEnabled", function() {
        describe("false", function() {
            beforeEach(function() {
                makeComponent({
                    spinDownEnabled: true,
                    renderTo: Ext.getBody()
                });
                component.setSpinDownEnabled(false);
            });

            it("should set the spinDownEnabled property to false", function() {
                expect(component.spinDownEnabled).toBe(false);
            });

            it("should add the 'x-form-spinner-down-disabled' class", function() {
                expect(component.spinDownEl.hasCls('x-form-spinner-down-disabled')).toBe(true);
            });
        });

        describe("true", function() {
            beforeEach(function() {
                makeComponent({
                    spinDownEnabled: false,
                    renderTo: Ext.getBody()
                });
                component.setSpinDownEnabled(true);
            });

            it("should set the spinDownEnabled property to true", function() {
                expect(component.spinDownEnabled).toBe(true);
            });

            it("should remove the 'x-form-spinner-down-disabled' class", function() {
                expect(component.spinDownEl.hasCls('x-form-spinner-down-disabled')).toBe(false);
            });
        });
    });

    describe("key nav", function() {
        function fireKey(key) {
            jasmine.fireKeyEvent(component.inputEl, 'keydown', key);
            jasmine.fireKeyEvent(component.inputEl, 'keypress', key);
        }

        it("should call onSpinUp when the up arrow is pressed", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            fireKey(Ext.event.Event.UP);
            expect(component.onSpinUp).toHaveBeenCalled();
        });

        it("should not call onSpinUp if keyNavEnabled = false", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                keyNavEnabled: false
            });
            fireKey(Ext.event.Event.UP);
            expect(component.onSpinUp).not.toHaveBeenCalled();
        });

        it("should not call onSpinUp if spinUpEnabled = false", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                spinUpEnabled: false
            });
            fireKey(Ext.event.Event.UP);
            expect(component.onSpinUp).not.toHaveBeenCalled();
        });

        it("should call onSpinDown when the down arrow is pressed", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            fireKey(Ext.event.Event.DOWN);
            expect(component.onSpinDown).toHaveBeenCalled();
        });

        it("should not call onSpinDown if keyNavEnabled = false", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                keyNavEnabled: false
            });
            fireKey(Ext.event.Event.DOWN);
            expect(component.onSpinDown).not.toHaveBeenCalled();
        });

        it("should not call onSpinDown if spinDownEnabled = false", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                spinDownEnabled: false
            });
            fireKey(Ext.event.Event.DOWN);
            expect(component.onSpinDown).not.toHaveBeenCalled();
        });
    });

    describe("spin events", function() {
        describe("spinning up", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                spyOn(component, "fireEvent").andCallThrough();
                component.spinUp();
            });

            it("should fire the 'spin' event with the 'up' direction parameter", function() {
                expect(component.fireEvent).toHaveBeenCalledWith("spin", component, "up");
            });

            it("should fire the 'spinup' event", function() {
                expect(component.fireEvent).toHaveBeenCalledWith("spinup", component);
            });
        });

        describe("spinning down", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                spyOn(component, "fireEvent").andCallThrough();
                component.spinDown();
            });

            it("should fire the 'spin' event with the 'down' direction parameter", function() {
               expect(component.fireEvent).toHaveBeenCalledWith("spin", component, "down");
            });

            it("should fire the 'spindown' event", function() {
                expect(component.fireEvent).toHaveBeenCalledWith("spindown", component);
            });
        });

        describe('spinend', function() {
            var spinUpsToDo = Ext.isIE8 ? 20 : 100,
                spinUpCount = 0,
                spinEndCount = 0,
                idleSpy, spinEndSpy;

            beforeEach(function() {
                component = new Ext.form.field.Spinner({
                    renderTo: Ext.getBody(),
                    spinUpEnabled: true,
                    listeners: {
                        spinup: function() {
                            spinUpCount++;
                        },
                        spinend: function() {
                            spinEndCount++;
                        }
                    }
                });

                idleSpy = jasmine.createSpy('idle listener');
                spinEndSpy = jasmine.createSpy('spinEnd listener');
            });

            afterEach(function() {
                Ext.GlobalEvents.un('idle', idleSpy);
                idleSpy = spinEndSpy = null;
            });

            it('should fire a spinend event when the spin stops', function() {
                waitsFor(function() {
                    if (spinUpCount === spinUpsToDo) {
                        jasmine.fireKeyEvent(component.inputEl, 'keyup', Ext.event.Event.UP);

                        return true;
                    }

                    jasmine.fireKeyEvent(component.inputEl, 'keydown', Ext.event.Event.UP);
                }, 'Spinner to fire all events', 5000);

                // The firing of spinend is buffered because of the repeating, so it will fire soon.
                runs(function() {
                    expect(spinEndCount).toBe(0);
                    component.on('spinend', spinEndSpy);
                    Ext.GlobalEvents.on('idle', idleSpy);
                });

                waitForSpy(spinEndSpy);

                // Only one spinend event must fire, so wait for any extraneous ones.
                waitForSpy(idleSpy);

                runs(function() {
                    expect(spinEndCount).toBe(1);
                });
            });
        });
    });

});
