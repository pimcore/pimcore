topSuite("Ext.event.gesture.Drag", function() {
    var helper = Ext.testHelper,
        recognizer = Ext.event.gesture.Drag.instance,
        minDistance = recognizer.getMinDistance(),
        targetEl, dragstartHandler, dragHandler, dragendHandler, dragcancelHandler,
        dragstartEvent, dragEvent, dragendEvent, dragcancelEvent;

    function start(cfg, target) {
        helper.touchStart(target || targetEl, cfg);
    }

    function move(cfg, target) {
        helper.touchMove(target || targetEl, cfg);
    }

    function end(cfg, target) {
        helper.touchEnd(target || targetEl, cfg);
    }

    function cancel(cfg, target) {
        helper.touchCancel(target || targetEl, cfg);
    }

    function expectInfo(e, info) {
        var name;

        for (name in info) {
            expect(e[name]).toBe(info[name]);
        }
    }

    beforeEach(function() {
        targetEl = Ext.getBody().createChild({
            style: 'width: 200px; height: 200px; border: 1px solid red;'
        });
        dragstartHandler = jasmine.createSpy();
        dragHandler = jasmine.createSpy();
        dragendHandler = jasmine.createSpy();
        dragcancelHandler = jasmine.createSpy();
        dragstartEvent = dragEvent = dragendEvent = dragcancelEvent = null;

        dragstartHandler.andCallFake(function(event) {
            dragstartEvent = event;
        });

        dragHandler.andCallFake(function(event) {
            dragEvent = event;
        });

        dragendHandler.andCallFake(function(event) {
            dragendEvent = event;
        });

        dragcancelHandler.andCallFake(function(event) {
            dragcancelEvent = event;
        });

        targetEl.on('dragstart', dragstartHandler);
        targetEl.on('drag', dragHandler);
        targetEl.on('dragend', dragendHandler);
        targetEl.on('dragcancel', dragcancelHandler);
    });

    afterEach(function() {
        targetEl.destroy();
    });

    it("should fire dragstart, drag, and dragend when the distance exceeds minDistance", function() {
        runs(function() {
            start({ id: 1, x: 100, y: 101 });
            move({ id: 1, x: 99, y: 101 - minDistance });
        });

        waitsForAnimation();

        runs(function() {
            expect(dragstartHandler).toHaveBeenCalled();
            expect(dragHandler).toHaveBeenCalled();

            expectInfo(dragstartEvent, {
                x: 99,
                y: 101 - minDistance,
                pageX: 99,
                pageY: 101 - minDistance,
                startX: 100,
                startY: 101,
                previousX: 100,
                previousY: 101,
                deltaX: -1,
                deltaY: -minDistance,
                absDeltaX: 1,
                absDeltaY: minDistance,
                previousDeltaX: 0,
                previousDeltaY: 0,
                longpress: false
            });

            expectInfo(dragEvent, {
                x: 99,
                y: 101 - minDistance,
                pageX: 99,
                pageY: 101 - minDistance,
                startX: 100,
                startY: 101,
                previousX: 100,
                previousY: 101,
                deltaX: -1,
                deltaY: -minDistance,
                absDeltaX: 1,
                absDeltaY: minDistance,
                previousDeltaX: 0,
                previousDeltaY: 0,
                longpress: false
            });

            move({ id: 1, x: 97, y: 100 - minDistance });
        });

        waitsForAnimation();

        runs(function() {
            expect(dragHandler.callCount).toBe(2);

            expectInfo(dragEvent, {
                x: 97,
                y: 100 - minDistance,
                pageX: 97,
                pageY: 100 - minDistance,
                startX: 100,
                startY: 101,
                previousX: 99,
                previousY: 101 - minDistance,
                deltaX: -3,
                deltaY: -(minDistance + 1),
                absDeltaX: 3,
                absDeltaY: minDistance + 1,
                previousDeltaX: -1,
                previousDeltaY: -minDistance,
                longpress: false
            });

            end({ id: 1, x: 96, y: 99 - minDistance });
        });

        waitsForAnimation();

        runs(function() {
            expect(dragendHandler).toHaveBeenCalled();

            expectInfo(dragendEvent, {
                x: 96,
                y: 99 - minDistance,
                pageX: 96,
                pageY: 99 - minDistance,
                startX: 100,
                startY: 101,
                previousX: 97,
                previousY: 100 - minDistance,
                deltaX: -4,
                deltaY: -(minDistance + 2),
                absDeltaX: 4,
                absDeltaY: minDistance + 2,
                previousDeltaX: -3,
                previousDeltaY: -(minDistance + 1),
                longpress: false
            });
        });
    });

    it("should not fire dragstart, drag, and dragend when the distance is less than minDistance", function() {
        runs(function() {
            start({ id: 1, x: 100, y: 101 });
            move({ id: 1, x: 99, y: 99 + minDistance });
            end({ id: 1, x: 99, y: 99 + minDistance });
        });

        waitsForAnimation();

        runs(function() {
            expect(dragstartHandler).not.toHaveBeenCalled();
            expect(dragHandler).not.toHaveBeenCalled();
            expect(dragendHandler).not.toHaveBeenCalled();
        });
    });

    it("should not fire dragstart when touchstart is stopped and the sequence tap in, tap out, tap in is followed", function() {
        var touchStart = jasmine.createSpy();

        targetEl.on('touchstart', touchStart.andCallFake(function(e) {
            e.stopPropagation();
        }));

        start({ id: 1, x: 100, y: 101 });
        end({ id: 1, x: 100, y: 100 });

        expect(touchStart.callCount).toBe(1);
        expect(dragstartHandler).not.toHaveBeenCalled();

        start({ id: 2, x: 400, y: 400 }, Ext.getBody());
        end({ id: 2, x: 400, y: 400 }, Ext.getBody());

        expect(touchStart.callCount).toBe(1);
        expect(dragstartHandler).not.toHaveBeenCalled();

        start({ id: 3, x: 100, y: 101 });
        end({ id: 3, x: 100, y: 100 });

        expect(touchStart.callCount).toBe(2);
        expect(dragstartHandler).not.toHaveBeenCalled();
    });

    if (jasmine.supportsTouch) {
        it("should fire dragcancel and not dragend if the touch is canceled after dragstart", function() {
            runs(function() {
                start({ id: 1, x: 100, y: 101 });
                move({ id: 1, x: 99, y: 101 - minDistance });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragstartHandler).toHaveBeenCalled();
                expect(dragHandler).toHaveBeenCalled();
                move({ id: 1, x: 97, y: 100 - minDistance });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragHandler.callCount).toBe(2);
                cancel({ id: 1, x: 96, y: 99 - minDistance });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragendHandler).not.toHaveBeenCalled();
                expect(dragcancelHandler).toHaveBeenCalled();

                expectInfo(dragcancelEvent, {
                    x: 96,
                    y: 99 - minDistance,
                    pageX: 96,
                    pageY: 99 - minDistance,
                    startX: 100,
                    startY: 101,
                    previousX: 97,
                    previousY: 100 - minDistance,
                    deltaX: -4,
                    deltaY: -(minDistance + 2),
                    absDeltaX: 4,
                    absDeltaY: minDistance + 2,
                    previousDeltaX: -3,
                    previousDeltaY: -(minDistance + 1),
                    longpress: false
                });
            });
        });

        it("should fire dragcancel when a second touch begins", function() {
            runs(function() {
                start({ id: 1, x: 100, y: 101 });
                move({ id: 1, x: 99, y: 101 - minDistance });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragstartHandler).toHaveBeenCalled();
                expect(dragHandler).toHaveBeenCalled();
                move({ id: 1, x: 97, y: 100 - minDistance });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragHandler.callCount).toBe(2);
                start({ id: 2, x: 200, y: 300 });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragendHandler).not.toHaveBeenCalled();
                expect(dragcancelHandler).toHaveBeenCalled();

                expectInfo(dragcancelEvent, {
                    x: 97,
                    y: 100 - minDistance,
                    pageX: 97,
                    pageY: 100 - minDistance,
                    startX: 100,
                    startY: 101,
                    previousX: 99,
                    previousY: 101 - minDistance,
                    deltaX: -3,
                    deltaY: -(minDistance + 1),
                    absDeltaX: 3,
                    absDeltaY: minDistance + 1,
                    previousDeltaX: -1,
                    previousDeltaY: -minDistance,
                    longpress: false
                });
                end({ id: 1, x: 100, y: 101 });
                end({ id: 2, x: 200, y: 300 });
            });
        });
    }

    it("should have the correct e.target if the mouse is moved off of the target", function() {
        runs(function() {
            start({ id: 1, x: 500, y: 300 });
            helper.touchMove(document.body, { id: 1, x: 200, y: 700 });
        });

        waitsForAnimation();

        runs(function() {
            expect(dragEvent.target).toBe(targetEl.dom);
            end({ id: 1, x: 200, y: 700 });
        });

        waitsForAnimation();
    });

    describe("longpress to drag", function() {
        it("should not initiate drag with longpress by default", function() {
            runs(function() {
                start({ id: 1, x: 100, y: 101 });
            });

            waitsForEvent(targetEl, 'longpress', "longpress handler to be called", 5000);

            runs(function() {
                end({ id: 1, x: 100, y: 101 });
                expect(dragstartHandler).not.toHaveBeenCalled();
            });
        });

        it("should initiate drag with longpress when e.startDrag() is invoked", function() {
            targetEl.on('longpress', function(e) {
                e.startDrag();
            });

            runs(function() {
                start({ id: 1, x: 100, y: 101 });
            });

            waitsForEvent(targetEl, 'longpress', "longpress handler to be called", 5000);

            runs(function() {
                expect(dragstartHandler).toHaveBeenCalled();

                expectInfo(dragstartEvent, {
                    x: 100,
                    y: 101,
                    pageX: 100,
                    pageY: 101,
                    startX: 100,
                    startY: 101,
                    previousX: 100,
                    previousY: 101,
                    deltaX: 0,
                    deltaY: 0,
                    absDeltaX: 0,
                    absDeltaY: 0,
                    previousDeltaX: 0,
                    previousDeltaY: 0,
                    longpress: true
                });
            });

            runs(function() {
                move({ id: 1, x: 99, y: 97 });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragHandler).toHaveBeenCalled();

                expectInfo(dragEvent, {
                    x: 99,
                    y: 97,
                    pageX: 99,
                    pageY: 97,
                    startX: 100,
                    startY: 101,
                    previousX: 100,
                    previousY: 101,
                    deltaX: -1,
                    deltaY: -4,
                    absDeltaX: 1,
                    absDeltaY: 4,
                    previousDeltaX: 0,
                    previousDeltaY: 0,
                    longpress: true
                });

                move({ id: 1, x: 97, y: 100 - minDistance });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragHandler.callCount).toBe(2);

                expectInfo(dragEvent, {
                    x: 97,
                    y: 100 - minDistance,
                    pageX: 97,
                    pageY: 100 - minDistance,
                    startX: 100,
                    startY: 101,
                    previousX: 99,
                    previousY: 97,
                    deltaX: -3,
                    deltaY: -(minDistance + 1),
                    absDeltaX: 3,
                    absDeltaY: minDistance + 1,
                    previousDeltaX: -1,
                    previousDeltaY: -4,
                    longpress: true
                });

                end({ id: 1, x: 96, y: 99 - minDistance });
            });

            waitsForAnimation();

            runs(function() {
                expect(dragendHandler).toHaveBeenCalled();

                expectInfo(dragendEvent, {
                    x: 96,
                    y: 99 - minDistance,
                    pageX: 96,
                    pageY: 99 - minDistance,
                    startX: 100,
                    startY: 101,
                    previousX: 97,
                    previousY: 100 - minDistance,
                    deltaX: -4,
                    deltaY: -(minDistance + 2),
                    absDeltaX: 4,
                    absDeltaY: minDistance + 2,
                    previousDeltaX: -3,
                    previousDeltaY: -(minDistance + 1),
                    longpress: true
                });
            });
        });

        it("should claim the drag gesture when startDrag is called", function() {
            targetEl.on('longpress', function(e) {
                e.startDrag();
            });

            helper.touchStart(targetEl, { id: 1, x: 10, y: 15 });

            waitsForEvent(targetEl, 'longpress', 'longpress to fire', 5000);

            runs(function() {
                expect(Ext.event.gesture.Drag.instance.isActive).toBe(true);

                expect(Ext.event.gesture.DoubleTap.instance.isActive).toBe(false);
                expect(Ext.event.gesture.EdgeSwipe.instance.isActive).toBe(false);
                expect(Ext.event.gesture.LongPress.instance.isActive).toBe(false);
                expect(Ext.event.gesture.Pinch.instance.isActive).toBe(false);
                expect(Ext.event.gesture.Rotate.instance.isActive).toBe(false);
                expect(Ext.event.gesture.Swipe.instance.isActive).toBe(false);
                expect(Ext.event.gesture.Tap.instance.isActive).toBe(false);
                helper.touchEnd(targetEl, { id: 1, x: 10, y: 15 });
            });
        });
    });

    function makeRemoveSuite(useRemoveChild) {
        describe("when the target element is removed from the dom mid-drag " + (useRemoveChild ? "(using removeChild)" : "(using innerHTML)"), function() {
            var parent, target, firingTarget;

            function removeTarget() {
                if (useRemoveChild) {
                    parent.dom.removeChild(target.dom);
                }
                else {
                    parent.dom.innerHTML = '';
                }
            }

            beforeEach(function() {
                parent = Ext.getBody().createChild({
                    id: 'parent'
                });
                target = parent.createChild({
                    id: 'child'
                });

                // the "firingTarget" attempts to emulate the target that the browser uses
                // after an element is removed from the dom
                if (Ext.supports.TouchEvents) {
                    // with touch events, the element remains the target of current touches
                    // even after the element is removed from the dom
                    firingTarget = target;
                }
                else {
                    // with mouse and pointer events, once the element is removed from the dom
                    // we get a new target.  Assume the worst - we removed the element AND
                    // moved the mouse or pointer off of the parent element which has the
                    // listeners.  This is primarily to ensure that things like buffered grids
                    // work correctly when using the touch scroller and dragging past the
                    // edge of the buffer zone.
                    firingTarget = document.body;
                }
            });

            afterEach(function() {
                parent.destroy();
                target.destroy();
            });

            it("should recover gracefully when the listener is attached above the target", function() {
                runs(function() {
                    parent.on('drag', dragHandler);
                    parent.on('dragend', dragendHandler);

                    start({ id: 1, x: 100, y: 100 }, target);
                    move({ id: 1, x: 100, y: 100 + minDistance }, target);
                });

                waitsForAnimation();

                runs(function() {
                    expect(dragHandler.callCount).toBe(1);
                    removeTarget();
                    move({ id: 1, x: 120, y: 150 + minDistance }, firingTarget);
                });

                waitsForAnimation();

                runs(function() {
                    expect(dragHandler.callCount).toBe(2);
                    expect(dragEvent.target).toBe(target.dom);

                    end({ id: 1, x: 120, y: 150 + minDistance }, firingTarget);
                });

                waitsForAnimation();

                runs(function() {
                    expect(dragendHandler).toHaveBeenCalled();
                    expect(dragendEvent.target).toBe(target.dom);
                });
            });

            it("should recover gracefully when the listener is attached to the target", function() {
                runs(function() {
                    target.on('drag', dragHandler);
                    target.on('dragend', dragendHandler);

                    start({ id: 1, x: 100, y: 100 }, target);
                    move({ id: 1, x: 100, y: 100 + minDistance }, target);
                });

                waitsForAnimation();

                runs(function() {
                    expect(dragHandler.callCount).toBe(1);
                    removeTarget();
                    move({ id: 1, x: 120, y: 150 + minDistance }, firingTarget);
                });

                waitsForAnimation();

                runs(function() {
                    expect(dragHandler.callCount).toBe(2);
                    expect(dragEvent.target).toBe(target.dom);

                    end({ id: 1, x: 120, y: 150 + minDistance }, firingTarget);
                });

                waitsForAnimation();

                runs(function() {
                    expect(dragendHandler).toHaveBeenCalled();
                    expect(dragendEvent.target).toBe(target.dom);
                });
            });
        });
    }

    makeRemoveSuite(true); // using removeChild
    makeRemoveSuite(false); // using innerHTML = '' on an ancestor

});
