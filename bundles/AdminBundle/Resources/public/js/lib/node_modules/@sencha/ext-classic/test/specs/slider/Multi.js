topSuite("Ext.slider.Multi", function() {
    var slider, createSlider;

    beforeEach(function() {
        createSlider = function(config) {
            slider = new Ext.slider.Multi(Ext.apply({
                renderTo: Ext.getBody(),
                name: "test",
                width: 219,
                labelWidth: 0,
                hideEmptyLabel: false,
                minValue: 0,
                maxValue: 100,
                animate: false
            }, config));
        };

    });

    afterEach(function() {
        if (slider) {
            slider.destroy();
        }

        slider = null;
    });

    describe("component initialization", function() {
        describe("keyIncrement", function() {
            describe("if keyIncrement > increment", function() {
                it("should equal passed keyIncrement", function() {
                    createSlider({
                        keyIncrement: 10,
                        increment: 4
                    });

                    expect(slider.keyIncrement).toEqual(10);
                });
            });

            describe("if keyIncrement < increment", function() {
                it("should equal passed keyIncrement", function() {
                    createSlider({
                        keyIncrement: 7,
                        increment: 11
                    });

                    expect(slider.keyIncrement).toEqual(11);
                });
            });
        });

        describe("if horizontal", function() {
            beforeEach(function() {
                createSlider();
            });

            it("should set aria-orientation attribute", function() {
                expect(slider).toHaveAttr('aria-orientation', 'horizontal');
            });
        });

        describe("if vertical", function() {
            beforeEach(function() {
                createSlider({
                    vertical: true,
                    height: 214
                });
            });

            specFor(Ext.slider.Multi.Vertical, function(key, value) {
                it("should override " + key + " method", function() {
                    expect(slider[key]).toBe(value);
                });
            });

            it("should set aria-orientation attribute", function() {
                expect(slider).toHaveAttr('aria-orientation', 'vertical');
            });
        });

        describe("thumbs", function() {
            describe("if there is no value in configuration", function() {
                beforeEach(function() {
                    createSlider();
                });

                it("should create one thumb", function() {
                    expect(slider.thumbs.length).toEqual(1);
                });

                it("should set the thumb value to 0", function() {
                    expect(slider.thumbs[0].value).toEqual(0);
                });
            });

            describe("if there is an array of values in configuration", function() {
                describe("with values [0, 10, 20, 30]", function() {
                    var values = [0, 10, 20, 30],
                        spy = jasmine.createSpy();

                    beforeEach(function() {
                        createSlider({
                            values: values,
                            listeners: {
                                change: spy
                            }
                        });
                    });

                    specFor(values, function(property, value) {
                        it("should set the thumb " + property + " value to " + value, function() {
                            expect(slider.thumbs[property].value).toEqual(value);
                        });
                    });

                    it("should not fire the change event", function() {
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not be marked as dirty", function() {
                        expect(slider.isDirty()).toBe(false);
                    });
                });
            });
        });

        describe("ARIA attributes", function() {
            beforeEach(function() {
                createSlider({
                    value: 42
                });
            });

            it("should set aria-valuemin", function() {
                expect(slider).toHaveAttr('aria-valuemin', '0');
            });

            it("should set aria-valuemax", function() {
                expect(slider).toHaveAttr('aria-valuemax', '100');
            });

            it("should set aria-valuenow", function() {
                expect(slider).toHaveAttr('aria-valuenow', '42');
            });
        });
    });

    describe("addThumbs", function() {
        beforeEach(function() {
            createSlider();
            spyOn(slider, "addThumb").andCallThrough();
            spyOn(Ext.slider.Thumb.prototype, "render").andCallThrough();
        });

        it("should return the thumb", function() {
            expect(slider.addThumb(17) instanceof Ext.slider.Thumb).toBe(true);
        });

        it("should add the thumb to the slider", function() {
            slider.addThumb(17);
            expect(slider.thumbs.length).toEqual(2);
        });

        it("should render the thumb if slider is rendered", function() {
            slider.addThumb(17);
            expect(Ext.slider.Thumb.prototype.render).toHaveBeenCalled();
        });

        it("should not render the thumb is slider isn't rendered", function() {
            var thumb;

            slider.rendered = false;
            thumb = slider.addThumb(17);
            expect(Ext.slider.Thumb.prototype.render).not.toHaveBeenCalled();
            slider.rendered = true;
            thumb.render();
        });
    });

    describe("removeThumbs", function() {
        beforeEach(function() {
            createSlider({
                values: [10, 20, 30]
            });
        });

        it("should remove the thumb from the slider", function() {
            slider.removeThumb(2);
            expect(slider.thumbs.length).toBe(2);
            expect(slider.thumbStack.length).toBe(2);
            expect(slider.getValues()).toEqual([10, 20]);
        });

        it("should destroy the thumb instance", function() {
            var thumb = slider.thumbs[2];

            slider.removeThumb(thumb);
            expect(thumb.destroyed).toBe(true);
        });

        it("should remove a thumb by index", function() {
            slider.removeThumb(0);
            expect(slider.getValues()).toEqual([20, 30]);
        });

        it("should remove a thumb by instance", function() {
            var thumb = slider.thumbs[1];

            slider.removeThumb(thumb);
            expect(slider.getValues()).toEqual([10, 30]);
        });
    });

    (Ext.isIE9m ? xdescribe : describe)("thumb slide", function() {
        describe("horizontal", function() {
            var thumb0, thumb60, thumb90,
                setupSlider = function(config) {
                    createSlider(Ext.apply({
                        values: [0, 60, 90]
                    }, config));

                    thumb0 = slider.thumbs[0];
                    thumb60 = slider.thumbs[1];
                    thumb90 = slider.thumbs[2];

                    spyOn(slider, "fireEvent").andCallThrough();
                };

            describe("mouse events", function() {
                describe("on slider mousedown", function() {
                    describe("on thumb", function() {
                        describe("no drag (mousedown/mouseup)", function() {
                            beforeEach(function() {
                                setupSlider();
                                var xy = thumb0.el.getXY();

                                jasmine.fireMouseEvent(thumb0.el, 'mousedown', xy[0], xy[1]);
                                jasmine.fireMouseEvent(thumb0.el, 'mouseup', xy[0], xy[1]);
                            });

                            it("should not fire any events", function() {
                                var calls = slider.fireEvent.calls,
                                    length = calls.length;

                                expect(length).toEqual(0);
                            });
                        });

                        var dragConfig = {};

                        dragConfig["drag without snapping"] = {
                            config: {},
                            expected: 3
                        };

                        dragConfig["drag with snapping"] = {
                            config: {
                                increment: 5
                            },
                            expected: 5
                        };

                        specFor(dragConfig, function(key, value) {
                           describe(key, function() {
                                beforeEach(function() {
                                    setupSlider(value.config);
                                    var xy = thumb0.el.getXY(),
                                        innerEl = slider.innerEl,
                                        trackLength = innerEl.getWidth(),
                                        // Work out the exact correct mousemove offset based difference between cur value and expected value
                                        xOffset = trackLength * (slider.calculateThumbPosition(value.expected - slider.getValue(0)) / 100);

                                    // Mousedown on edge of thumb
                                    jasmine.fireMouseEvent(thumb0.el, 'mousedown', xy[0], xy[1]);
                                    xy[0] += xOffset;
                                    jasmine.fireMouseEvent(thumb0.el.dom.ownerDocument, 'mousemove', xy[0], xy[1]);
                                    jasmine.fireMouseEvent(thumb0.el.dom.ownerDocument, 'mouseup', xy[0], xy[1]);
                                });

                                it("should call dragstart event", function() {
                                    expect(slider.fireEvent.calls[0].args[0]).toBe("dragstart");
                                    expect(slider.fireEvent.calls[0].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[0].args[3].el.id).toBe(thumb0.el.id);
                                });

                                it("should fire beforechange event", function() {
                                    expect(slider.fireEvent.calls[1].args[0]).toBe("beforechange");
                                    expect(slider.fireEvent.calls[1].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[1].args[2]).toBe(value.expected);
                                    expect(slider.fireEvent.calls[1].args[4].el.id).toBe(thumb0.el.id);
                                });

                                it("should fire change event", function() {
                                    expect(slider.fireEvent.calls[2].args[0]).toBe("change");
                                    expect(slider.fireEvent.calls[2].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[2].args[2]).toBe(value.expected);
                                    expect(slider.fireEvent.calls[2].args[3].el.id).toBe(thumb0.el.id);
                                });

                                it("should fire the dirtychange event", function() {
                                    expect(slider.fireEvent.calls[3].args[0]).toBe("dirtychange");
                                    expect(slider.fireEvent.calls[3].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[3].args[2]).toBe(true);
                                });

                                it("should call drag event", function() {
                                    expect(slider.fireEvent.calls[4].args[0]).toBe("drag");
                                    expect(slider.fireEvent.calls[4].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[4].args[3].el.id).toBe(thumb0.el.id);
                                });

                                it("should call dragend event", function() {
                                    expect(slider.fireEvent.calls[5].args[0]).toBe("dragend");
                                    expect(slider.fireEvent.calls[5].args[1].id).toBe(slider.id);
                                });

                                it("should fire changecomplete event", function() {
                                    expect(slider.fireEvent.calls[6].args[0]).toBe("changecomplete");
                                    expect(slider.fireEvent.calls[6].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[6].args[2]).toBe(value.expected);
                                    expect(slider.fireEvent.calls[6].args[3].el.id).toBe(thumb0.el.id);
                                });
                            });
                        });
                    });

                    describe("outside thumbs", function() {
                        beforeEach(function() {
                            setupSlider();
                        });

                        describe("if slider enabled", function() {
                            beforeEach(function() {
                                var xy = slider.innerEl.getXY();

                                jasmine.fireMouseEvent(slider.el, 'click', xy[0] + 100, xy[1] + 8);
                            });

                            it("should fire beforechange event", function() {
                                expect(slider.fireEvent).toHaveBeenCalledWith("beforechange", slider, 50, 60, thumb60, 'update');
                            });

                            it("should fire change event", function() {
                                expect(slider.fireEvent).toHaveBeenCalledWith("change", slider, 50, thumb60, 'update');
                            });

                            it("should fire changecomplete event", function() {
                                expect(slider.fireEvent).toHaveBeenCalledWith("changecomplete", slider, 50, thumb60);
                            });
                        });

                        describe("if slider disabled", function() {
                            beforeEach(function() {
                                slider.disable();
                                var xy = slider.innerEl.getXY();

                                jasmine.fireMouseEvent(slider.el, 'mousedown', xy[0] + 10, xy[1] + 10);
                            });

                            afterEach(function() {
                                var xy = slider.innerEl.getXY();

                                jasmine.fireMouseEvent(slider.el, 'mouseup', xy[0] + 10, xy[1] + 10);
                            });

                            it("should not fire any *change* events", function() {
                                var calls = slider.fireEvent.calls,
                                    length = calls.length,
                                    call, i;

                                for (i = 0; i < length; i++) {
                                    call = calls[i];
                                    expect(call.args[0].search("change")).toEqual(-1);
                                }
                            });
                        });
                    });
                });
            });
        });

        describe("vertical", function() {
            var thumb0, thumb60, thumb90,
                setupSlider = function(config) {
                    createSlider(Ext.apply({
                        values: [0, 60, 90],
                        height: 214,
                        vertical: true
                    }, config));

                    thumb0 = slider.thumbs[0];
                    thumb60 = slider.thumbs[1];
                    thumb90 = slider.thumbs[2];

                    spyOn(slider, "fireEvent").andCallThrough();
                };

            describe("mouse events", function() {
                describe("on slider mousedown", function() {
                    describe("on thumb", function() {
                        describe("no drag (mousedown/mouseup)", function() {
                            beforeEach(function() {
                                setupSlider();
                                var xy = thumb0.el.getXY();

                                jasmine.fireMouseEvent(thumb0.el, 'mousedown', xy[0], xy[1] - 17);
                                jasmine.fireMouseEvent(thumb0.el, 'mouseup', xy[0], xy[1] - 17);
                            });

                            it("should not fire any events", function() {
                                var calls = slider.fireEvent.calls,
                                    length = calls.length;

                                expect(length).toEqual(0);
                            });
                        });

                        var dragConfig = {};

                        dragConfig["drag without snapping"] = {
                            config: {},
                            expected: 12
                        };

                        dragConfig["drag with snapping"] = {
                            config: {
                                increment: 10
                            },
                            expected: 10
                        };

                        specFor(dragConfig, function(key, value) {
                           describe(key, function() {
                                beforeEach(function() {
                                    setupSlider(value.config);
                                    var xy = thumb0.el.getXY(),
                                        innerEl = slider.innerEl,
                                        trackLength = innerEl.getHeight(),
                                        // Work out the exact correct mousemove offset based on new value.
                                        yOffset = trackLength * (slider.calculateThumbPosition(slider.getValue(0) - value.expected) / 100);

                                    // Mousedown on edge of thumb
                                    jasmine.fireMouseEvent(thumb0.el, 'mousedown', xy[0], xy[1]);
                                    xy[1] += yOffset;
                                    jasmine.fireMouseEvent(thumb0.el.dom.ownerDocument, 'mousemove', xy[0], xy[1]);
                                    jasmine.fireMouseEvent(thumb0.el.dom.ownerDocument, 'mouseup', xy[0], xy[1]);
                                });

                                it("should call dragstart event", function() {
                                    expect(slider.fireEvent.calls[0].args[0]).toBe("dragstart");
                                    expect(slider.fireEvent.calls[0].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[0].args[3].el.id).toBe(thumb0.el.id);
                                });

                                it("should fire beforechange event", function() {
                                    expect(slider.fireEvent.calls[1].args[0]).toBe("beforechange");
                                    expect(slider.fireEvent.calls[1].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[1].args[2]).toBe(value.expected);
                                    expect(slider.fireEvent.calls[1].args[4].el.id).toBe(thumb0.el.id);
                                });

                                it("should fire change event", function() {
                                    expect(slider.fireEvent.calls[2].args[0]).toBe("change");
                                    expect(slider.fireEvent.calls[2].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[2].args[2]).toBe(value.expected);
                                    expect(slider.fireEvent.calls[2].args[3].el.id).toBe(thumb0.el.id);
                                });

                                it("should fire dirtychange event", function() {
                                    expect(slider.fireEvent.calls[3].args[0]).toBe("dirtychange");
                                    expect(slider.fireEvent.calls[3].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[3].args[2]).toBe(true);
                                });

                                it("should call drag event", function() {
                                    expect(slider.fireEvent.calls[4].args[0]).toBe("drag");
                                    expect(slider.fireEvent.calls[4].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[4].args[3].el.id).toBe(thumb0.el.id);
                                });

                                it("should call dragend event", function() {
                                    expect(slider.fireEvent.calls[5].args[0]).toBe("dragend");
                                    expect(slider.fireEvent.calls[5].args[1].id).toBe(slider.id);
                                });

                                it("should fire changecomplete event", function() {
                                    expect(slider.fireEvent.calls[6].args[0]).toBe("changecomplete");
                                    expect(slider.fireEvent.calls[6].args[1].id).toBe(slider.id);
                                    expect(slider.fireEvent.calls[6].args[2]).toBe(value.expected);
                                    expect(slider.fireEvent.calls[6].args[3].el.id).toBe(thumb0.el.id);
                                });
                            });
                        });
                    });

                    describe("outside thumbs", function() {
                        beforeEach(function() {
                            setupSlider();

                            slider.on('focusenter', jasmine.createSpy('focusenter'));
                        });

                        describe("if slider enabled", function() {
                            beforeEach(function() {
                                var xy = slider.innerEl.getXY(),
                                    offset = Math.floor(slider.innerEl.getHeight() / 2);

                                jasmine.fireMouseEvent(slider.el, 'click', xy[0] + 8, xy[1] + offset);
                            });

                            it("should fire the focus event", function() {
                                expect(slider.fireEvent.calls[0].args[0]).toBe("focus");
                            });

                            it("should fire the focusenter event", function() {
                                expect(slider.fireEvent.calls[1].args[0]).toBe("focusenter");
                            });

                            it("should fire beforechange event", function() {
                                expect(slider.fireEvent.calls[2].args[0]).toBe("beforechange");
                                expect(slider.fireEvent.calls[2].args[1].id).toBe(slider.id);
                                expect(slider.fireEvent.calls[2].args[2]).toBe(50);
                                expect(slider.fireEvent.calls[2].args[3]).toBe(60);
                                expect(slider.fireEvent.calls[2].args[4].el.id).toBe(thumb60.el.id);
                            });

                            it("should fire change event", function() {
                                expect(slider.fireEvent.calls[3].args[0]).toBe("change");
                                expect(slider.fireEvent.calls[3].args[1].id).toBe(slider.id);
                                expect(slider.fireEvent.calls[3].args[2]).toBe(50);
                                expect(slider.fireEvent.calls[3].args[3].el.id).toBe(thumb60.el.id);
                            });

                            it("should fire dirtychange event", function() {
                                expect(slider.fireEvent.calls[4].args[0]).toBe("dirtychange");
                                expect(slider.fireEvent.calls[4].args[1].id).toBe(slider.id);
                                expect(slider.fireEvent.calls[4].args[2]).toBe(true);
                            });

                            it("should fire changecomplete event", function() {
                                expect(slider.fireEvent.calls[5].args[0]).toBe("changecomplete");
                                expect(slider.fireEvent.calls[5].args[1].id).toBe(slider.id);
                                expect(slider.fireEvent.calls[5].args[2]).toBe(50);
                                expect(slider.fireEvent.calls[5].args[3].el.id).toBe(thumb60.el.id);
                            });

                            it("should change the thumb value", function() {
                                expect(thumb60.value).toEqual(50);
                            });
                        });

                        describe("if slider disabled", function() {
                            beforeEach(function() {
                                slider.disable();
                                var xy = slider.innerEl.getXY();

                                jasmine.fireMouseEvent(slider.el, 'mousedown', xy[0], xy[1] - 93);
                            });

                            afterEach(function() {
                                var xy = slider.innerEl.getXY();

                                jasmine.fireMouseEvent(slider.el, 'mouseup', xy[0], xy[1] - 93);
                            });

                            it("should not fire any *change* events", function() {
                                var calls = slider.fireEvent.calls,
                                    length = calls.length,
                                    call, i;

                                for (i = 0; i < length; i++) {
                                    call = calls[i];
                                    expect(call.args[0].search("change")).toEqual(-1);
                                }
                            });

                            it("should not change the thumb value", function() {
                                expect(thumb0.value).toEqual(0);
                            });
                        });
                    });
                });
            });
        });
    });

    describe("readOnly", function() {
        it("should disable the thumb if configured with readOnly: true", function() {
            createSlider({
                renderTo: Ext.getBody(),
                readOnly: true,
                value: 0
            });
            expect(slider.thumbs[0].disabled).toBe(true);
        });

        it("should disable all thumbs if configured with readOnly: true", function() {
            createSlider({
                renderTo: Ext.getBody(),
                readOnly: true,
                values: [1, 2, 3]
            });
            expect(slider.thumbs[0].disabled).toBe(true);
            expect(slider.thumbs[1].disabled).toBe(true);
            expect(slider.thumbs[2].disabled).toBe(true);
        });

        it("should disable thumbs if setReadOnly(true) is called after render", function() {
            createSlider({
                renderTo: Ext.getBody(),
                values: [1, 2, 3]
            });
            slider.setReadOnly(true);
            expect(slider.thumbs[0].disabled).toBe(true);
            expect(slider.thumbs[1].disabled).toBe(true);
            expect(slider.thumbs[2].disabled).toBe(true);
        });

        it("should enable thumbs if setReadOnly(false) is called after render", function() {
            createSlider({
                renderTo: Ext.getBody(),
                readOnly: true,
                values: [1, 2, 3]
            });
            slider.setReadOnly(false);
            expect(slider.thumbs[0].disabled).toBe(false);
            expect(slider.thumbs[1].disabled).toBe(false);
            expect(slider.thumbs[2].disabled).toBe(false);
        });
    });

    describe("snapping", function() {
        it("should not alter the max value when specifying an increment", function() {
            createSlider({
                width: 200,
                value: 1000,
                increment: 100,
                minValue: 50,
                maxValue: 1000,
                renderTo: Ext.getBody()
            });
            expect(slider.maxValue).toBe(1000);
        });
    });

    describe("setting and getting values", function() {
        beforeEach(function() {
            createSlider({
                values: [10, 20, 30],
                minValue: 5,
                maxValue: 100,
                decimalPrecision: 2
            });
        });

        describe("getValue", function() {
            it("should return the value for the thumb at the given index", function() {
                expect(slider.getValue(1)).toEqual(20);
            });
            it("should return an array of all thumb values if no index passed", function() {
                expect(slider.getValue()).toEqual([10, 20, 30]);
            });
        });

        describe("getValues", function() {
            it("should return an array of all thumb values", function() {
                expect(slider.getValues()).toEqual([10, 20, 30]);
            });
        });

        describe("getSubmitValue", function() {
            it("should return an array of all thumb values", function() {
                expect(slider.getSubmitValue()).toEqual([10, 20, 30]);
            });
            it("should return null if the field is disabled", function() {
                slider.disable();
                expect(slider.getSubmitValue()).toBeNull();
            });
            it("should return null if the field has submitValue:false", function() {
                slider.submitValue = false;
                expect(slider.getSubmitValue()).toBeNull();
            });
        });

        describe("setValue", function() {

            describe("single value", function() {
                it("should set the value of the thumb at the given index", function() {
                    slider.setValue(1, 50);
                    expect(slider.thumbs[1].value).toEqual(50);
                });
                it("should normalize the value according to the minValue", function() {
                    slider.setValue(1, 2);
                    expect(slider.thumbs[1].value).toEqual(5);
                });
                it("should normalize the value according to the maxValue", function() {
                    slider.setValue(1, 200);
                    expect(slider.thumbs[1].value).toEqual(100);
                });
                it("should round the value according to the decimalPrecision", function() {
                    slider.setValue(1, 20.253764);
                    expect(slider.thumbs[1].value).toEqual(20.25);
                });
                xit("should set the aria-valuenow attribute", function() {
                    slider.setValue(1, 23);
                    expect(slider.inputEl.dom.getAttribute('aria-valuenow') + '').toEqual('23');
                });
                xit("should set the aria-valuetext attribute", function() {
                    slider.setValue(1, 23);
                    expect(slider.inputEl.dom.getAttribute('aria-valuetext') + '').toEqual('23');
                });
                it("should fire the beforechange event", function() {
                    var spy = jasmine.createSpy("beforechange handler");

                    slider.on('beforechange', spy);
                    slider.setValue(1, 23);

                    expect(spy.calls[0].args[0].id).toBe(slider.id);
                    expect(spy.calls[0].args[1]).toBe(23);
                    expect(spy.calls[0].args[2]).toBe(20);
                    expect(spy.calls[0].args[3].el.id).toBe(slider.thumbs[1].el.id);
                });
                it("should fire the change event", function() {
                    var changeSpy = jasmine.createSpy('change handler');

                    slider.on('change', changeSpy);
                    slider.setValue(1, 23);
                    expect(changeSpy.calls[0].args[0].id).toBe(slider.id);
                    expect(changeSpy.calls[0].args[1]).toBe(23);
                    expect(changeSpy.calls[0].args[2].el.id).toBe(slider.thumbs[1].el.id);
                    expect(changeSpy).toHaveBeenCalledWith(slider, 23, slider.thumbs[1], 'update');
                });
                it("should move the thumb", function() {
                    var thumbSpy = spyOn(slider.thumbs[1], 'move');

                    slider.setValue(1, 23);
                    expect(thumbSpy).toHaveBeenCalled(); // should check parameters too
                });

                it("should not perform the change if the beforechange handler returns false", function() {
                    var changeSpy = jasmine.createSpy('change handler'),
                        thumbSpy = spyOn(slider.thumbs[1], 'move');

                    slider.on('beforechange', function() { return false; });
                    slider.on('change', changeSpy);
                    slider.setValue(1, 23);
                    expect(slider.thumbs[1].value).toEqual(20);
                    expect(changeSpy).not.toHaveBeenCalled();
                    expect(thumbSpy).not.toHaveBeenCalled();
                });
            });

            describe("multiple values", function() {
                it("should set the value for multiple thumbs", function() {
                    slider.setValue([40, 50, 60]);
                    var thumbs = slider.thumbs;

                    expect(thumbs[0].value).toBe(40);
                    expect(thumbs[1].value).toBe(50);
                    expect(thumbs[2].value).toBe(60);
                });

                describe("with thumbPerValue:false", function() {
                    it("should only set the values passed", function() {
                        slider.setValue([40, 50]);
                        var thumbs = slider.thumbs;

                        expect(thumbs[0].value).toBe(40);
                        expect(thumbs[1].value).toBe(50);
                        expect(thumbs[2].value).toBe(30);
                        expect(thumbs.length).toBe(3);
                    });

                    it("should ignore extraneous values", function() {
                        slider.setValue([40, 50, 60, 70, 80]);
                        var thumbs = slider.thumbs;

                        expect(thumbs[0].value).toBe(40);
                        expect(thumbs[1].value).toBe(50);
                        expect(thumbs[2].value).toBe(60);
                        expect(thumbs.length).toBe(3);
                    });
                });

                describe("with thumbPerValue:true", function() {
                    beforeEach(function() {
                        slider.thumbPerValue = true;
                    });

                    it("should add thumbs for extra values", function() {
                        slider.setValue([10, 20, 30, 40]);
                        // values array length is greater than the number of thumbs; should add 1
                        var thumbs = slider.thumbs;

                        expect(thumbs[3].value).toBe(40);
                        expect(thumbs.length).toBe(4);
                    });

                    it("should remove thumbs for missing values", function() {
                        slider.setValue([10, 20]);
                        // values array length is less than the number of thumbs; should remove 1
                        var thumbs = slider.thumbs;

                        expect(thumbs.length).toBe(2);
                    });

                    it("should fire change event for added thumb", function() {
                        var changeSpy = jasmine.createSpy('change handler');

                        slider.on('change', changeSpy);
                        slider.setValue([10, 20, 30, 40]);
                        expect(changeSpy.calls[0].args[0].id).toBe(slider.id);
                        expect(changeSpy.calls[0].args[1]).toBe(40);
                        expect(changeSpy.calls[0].args[2].el.id).toBe(slider.thumbs[3].el.id);
                        expect(changeSpy).toHaveBeenCalledWith(slider, 40, slider.thumbs[3], 'add');
                    });

                    it("should fire change event for removed thumb", function() {
                        var changeSpy = jasmine.createSpy('change handler');

                        slider.on('change', changeSpy);
                        slider.setValue([10, 20]);
                        expect(changeSpy.calls[0].args[0].id).toBe(slider.id);
                        expect(changeSpy.calls[0].args[1]).toBe(null);
                        expect(changeSpy.calls[0].args[2]).toBe(null);
                        expect(changeSpy).toHaveBeenCalledWith(slider, null, null, 'remove');
                    });
                });
            });
        });

        describe('reset', function() {
            it("should reset all values to the original value", function() {
                slider.setValue(0, 40);
                slider.setValue(1, 50);
                slider.setValue(2, 60);
                slider.reset();
                expect(slider.thumbs[0].value).toEqual(10);
                expect(slider.thumbs[1].value).toEqual(20);
                expect(slider.thumbs[2].value).toEqual(30);
            });
        });

        describe("dirty", function() {
            it("should fire the dirtychange event when the value is modified", function() {
                var fired = 0;

                slider.on('dirtychange', function() {
                    ++fired;
                });
                slider.setValue(0, 40);
                expect(fired).toBe(1);
            });

            it("should fire the dirtychange event when the value is reset", function() {
                var fired = 0;

                slider.on('dirtychange', function() {
                    ++fired;
                });
                slider.setValue(0, 40);
                expect(fired).toBe(1);
                slider.setValue(0, 10);
                expect(fired).toBe(2);
            });
        });
    });

    describe("setMinValue/setMaxValue", function() {
        var getLeft = function() {
            return parseFloat(slider.thumbs[0].el.getStyle('left'));
        };

        describe("setMinValue", function() {
            it("should limit the value to the minimum", function() {
                createSlider();
                slider.setMinValue(50);
                slider.setValue(0, 25);
                expect(slider.getValue()[0]).toBe(50);
            });

            it("should adjust existing values", function() {
                createSlider();
                slider.setValue(0, 50);
                slider.setMinValue(60);
                expect(slider.getValue()[0]).toBe(60);
            });

            it("should update the thumb position if value is < minValue", function() {
                createSlider();
                slider.setValue(0, 50);
                var oldLeft = getLeft();

                slider.setMinValue(60);
                var newLeft = getLeft();

                // Should move to the leftmost since it will be at the min
                expect(newLeft).toBeLessThan(oldLeft);
            });

            it("should update the thumb position for values > minValue", function() {
                createSlider();
                slider.setValue(0, 50);
                var oldLeft = getLeft();

                slider.setMinValue(-50);
                var newLeft = getLeft();

                // Should move to the right because the minValue got smaller
                expect(newLeft).toBeGreaterThan(oldLeft);
            });

            it("should not fire the change event if the value stays the same", function() {
                var called = false;

                createSlider();
                slider.setValue(0, 50);
                slider.on('change', function() {
                    called = true;
                });
                slider.setMinValue(10);
                expect(called).toBe(false);
            });

            it("should fire the change event if the value changes", function() {
                var called = false;

                createSlider();
                slider.setValue(0, 50);
                slider.on('change', function() {
                    called = true;
                });
                slider.setMinValue(60);
                expect(called).toBe(true);
            });

            it("should set aria-valuemin attribute", function() {
                createSlider();
                slider.setMinValue(42);

                expect(slider).toHaveAttr('aria-valuemin', '42');
            });
        });

        describe("setMaxValue", function() {
            it("should limit the value to the maximum", function() {
                createSlider();
                slider.setMaxValue(50);
                slider.setValue(0, 75);
                expect(slider.getValue()[0]).toBe(50);
            });

            it("should adjust existing values", function() {
                createSlider();
                slider.setValue(0, 50);
                slider.setMaxValue(40);
                expect(slider.getValue()[0]).toBe(40);
            });

            it("should update the thumb position if value is < minValue", function() {
                createSlider();
                slider.setValue(0, 50);
                var oldLeft = getLeft();

                slider.setMaxValue(40);
                var newLeft = getLeft();

                // Should move to the rightmost since it will be at the max
                expect(newLeft).toBeGreaterThan(oldLeft);
            });

            it("should update the thumb position for values < maxValue", function() {
                createSlider();
                slider.setValue(0, 50);
                var oldLeft = getLeft();

                slider.setMaxValue(150);
                var newLeft = getLeft();

                // Should move to the left because the maxValue got bigger
                expect(newLeft).toBeLessThan(oldLeft);
            });

            it("should not fire the change event if the value stays the same", function() {
                var called = false;

                createSlider();
                slider.setValue(0, 50);
                slider.on('change', function() {
                    called = true;
                });
                slider.setMaxValue(90);
                expect(called).toBe(false);
            });

            it("should fire the change event if the value changes", function() {
                var called = false;

                createSlider();
                slider.setValue(0, 50);
                slider.on('change', function() {
                    called = true;
                });
                slider.setMaxValue(40);
                expect(called).toBe(true);
            });

            it("should set aria-valuemax attribute", function() {
                createSlider();
                slider.setMaxValue(42);

                expect(slider).toHaveAttr('aria-valuemax', '42');
            });
        });
    });

    describe("getNearest should always keep thumbs in order", function() {
        it("should work when all thumbs have the max value and we click on the left", function() {
            createSlider({
                values: [30, 70],
                minValue: 0,
                maxValue: 100
            });
            slider.setValue([100, 100]);
            jasmine.fireMouseEvent(slider.el, 'click', Math.max(slider.el.getX(), 1), 0);
            waitsFor(function() {
                return slider.getValue(0) === 0;
            }, "Slider value incorrect");
        });
        it("should work when all thumbs have the min value and we click on the right", function() {
            createSlider({
                values: [30, 70],
                minValue: 0,
                maxValue: 100
            });
            slider.setValue([0, 0]);
            jasmine.fireMouseEvent(slider.el, 'click', slider.el.getWidth(), 0);
            waitsFor(function() {
                return slider.getValue(1) === 100;
            }, "Slider value incorrect");
        });
    });

    describe("configuring invalid values", function() {
        it("should constrain configured value within minimum", function() {
            createSlider({
                value: -10
            });
            expect(slider.getValue(0)).toBe(0);
        });
        it("should constrain configured value within maximum", function() {
            createSlider({
                value: 120
            });
            expect(slider.getValue(0)).toBe(100);
        });
        it("should constrain configured value to snap points", function() {
            createSlider({
                increment: 2,
                value: 3
            });
            expect(slider.getValue(0)).toBe(4);
        });
    });
});
