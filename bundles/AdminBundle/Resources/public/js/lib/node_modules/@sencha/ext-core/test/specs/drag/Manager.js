// This will test all interaction between drag/drop targets.
topSuite("Ext.drag.Manager", ['Ext.drag.*', 'Ext.dom.Element', 'Ext.scroll.NativeScroller'], function() {
    var helper = Ext.testHelper,
        touchId = 0,
        cursorTrack, source, target,
        dragEl, dropEl;

    function makeEl(cfg) {
        return Ext.getBody().createChild(cfg);
    }

    function makeDragEl(x, y) {
        if (typeof x !== 'number') {
            x = 5;
        }

        if (typeof y !== 'number') {
            y = 5;
        }

        dragEl = makeEl({
            style: {
                position: 'absolute',
                width: '20px',
                height: '20px',
                left: x + 'px',
                top: y + 'px',
                border: '1px solid red'
            }
        });
    }

    function makeDropEl(x, y) {
        if (typeof x !== 'number') {
            x = 50;
        }

        if (typeof y !== 'number') {
            y = 50;
        }

        dropEl = makeEl({
            style: {
                position: 'absolute',
                width: '100px',
                height: '100px',
                left: x + 'px',
                top: y + 'px',
                border: '1px solid blue'
            }
        });
    }

    function makeSource(cfg) {
        cfg = cfg || {};

        if (!cfg.element) {
            if (!dragEl) {
                makeDragEl();
            }

            cfg.element = dragEl;
        }

        source = new Ext.drag.Source(cfg);
    }

    function makeTarget(cfg) {
        cfg = cfg || {};

        if (!cfg.element) {
            if (!dropEl) {
                makeDropEl();
            }

            cfg.element = dropEl;
        }

        target = new Ext.drag.Target(cfg);
    }

    function start(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchStart(target || dragEl, cfg);
    }

    function move(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchMove(target || dragEl, cfg);
    }

    function end(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchEnd(target || dragEl, cfg);
    }

    function cancel(cfg, target) {
        cursorTrack = [cfg.x || 0, cfg.y || 0];
        helper.touchCancel(target || dragEl, cfg);
    }

    function startDrag(x, y, target) {
        runs(function() {
            var xy = source.getElement().getXY();

            x = x || xy[0];
            y = y || xy[1];

            start({
                id: touchId,
                x: x,
                y: y
            }, target);
        });
        waitsForAnimation();
    }

    function startPosDrag(xPos, yPos, target) {
        runs(function() {
            var el = source.getElement(),
                xy = source.getElement().getXY(),
                size = el.getSize(),
                xOffset = 0,
                yOffset = 0;

            if (xPos === 'middle') {
                xOffset = size.width / 2;
            }
            else if (xPos === 'end') {
                xOffset = size.width - 1;
            }

            if (yPos === 'middle') {
                yOffset = size.height / 2;
            }
            else if (yPos === 'end') {
                yOffset = size.height - 1;
            }

            start({
                id: touchId,
                x: xy[0] + xOffset,
                y: xy[1] + yOffset
            }, target);
        });
        waitsForAnimation();
    }

    function moveBy(x, y, target) {
        runs(function() {
            move({
                id: touchId,
                x: cursorTrack[0] + (x || 0),
                y: cursorTrack[1] + (y || 0)
            }, target);
        });
        waitsForAnimation();
    }

    function endDrag(x, y, target) {
        runs(function() {
            x = x || cursorTrack[0];
            y = y || cursorTrack[1];

            end({
                id: touchId,
                x: x,
                y: y
            }, target);
        });
        waitsForAnimation();
        runs(function() {
            ++touchId;
        });
    }

    function runsExpectCallCount(spies, n) {
        runs(function() {
            if (!Ext.isArray(spies)) {
                spies = [spies];
            }

            Ext.Array.forEach(spies, function(spy) {
                expect(spy.callCount).toBe(n);
            });
        });
    }

    beforeEach(function() {
        cursorTrack = null;
        ++touchId;
    });

    afterEach(function() {
        cursorTrack = source = target = dragEl = dropEl = Ext.destroy(dragEl, dropEl, source, target);
    });

    describe("target matching", function() {
        var enterSpy, leaveSpy;

        beforeEach(function() {
            enterSpy = jasmine.createSpy();
            leaveSpy = jasmine.createSpy();
        });

        afterEach(function() {
            leaveSpy = enterSpy = null;
        });

        function setup() {
            makeSource();
            makeTarget();

            target.on('dragenter', enterSpy);
            target.on('dragleave', leaveSpy);
        }

        describe("simple matching", function() {
            it("should match an enter from the top", function() {
                makeDragEl(70, 10);
                makeDropEl(50, 50);
                setup();

                startPosDrag('middle', 'end');
                moveBy(0, 19);
                runsExpectCallCount(enterSpy, 0);
                moveBy(0, 2);
                runsExpectCallCount(enterSpy, 1);
                moveBy(0, 10);
                runsExpectCallCount(enterSpy, 1);
                moveBy(0, -10);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(0, -2);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });

            it("should match an enter from the top right", function() {
                makeDragEl(170, 10);
                makeDropEl(50, 50);
                setup();

                startPosDrag('start', 'end');
                moveBy(-19, 19);
                runsExpectCallCount(enterSpy, 0);
                moveBy(-2, 2);
                runsExpectCallCount(enterSpy, 1);
                moveBy(-10, 10);
                runsExpectCallCount(enterSpy, 1);
                moveBy(10, -10);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(2, -2);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });

            it("should match an enter from the right", function() {
                makeDragEl(170, 80);
                makeDropEl(50, 50);
                setup();

                startPosDrag('start', 'middle');
                moveBy(-19, 0);
                runsExpectCallCount(enterSpy, 0);
                moveBy(-2, 0);
                runsExpectCallCount(enterSpy, 1);
                moveBy(-10, 0);
                runsExpectCallCount(enterSpy, 1);
                moveBy(10, 0);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(2, 0);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });

            it("should match an enter from the bottom right", function() {
                makeDragEl(170, 170);
                makeDropEl(50, 50);
                setup();

                startPosDrag('start', 'start');
                moveBy(-19, -19);
                runsExpectCallCount(enterSpy, 0);
                moveBy(-2, -4);
                runsExpectCallCount(enterSpy, 1);
                moveBy(-10, -10);
                runsExpectCallCount(enterSpy, 1);
                moveBy(10, 10);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(2, 4);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });

            it("should match an enter from the bottom", function() {
                makeDragEl(70, 170);
                makeDropEl(50, 50);
                setup();

                startPosDrag('middle', 'start');
                moveBy(0, -19);
                runsExpectCallCount(enterSpy, 0);
                moveBy(0, -4);
                runsExpectCallCount(enterSpy, 1);
                moveBy(0, -10);
                runsExpectCallCount(enterSpy, 1);
                moveBy(0, 10);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(0, 4);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });

            it("should match an enter from the bottom left", function() {
                makeDragEl(10, 170);
                makeDropEl(50, 50);
                setup();

                startPosDrag('end', 'start');
                moveBy(19, -19);
                runsExpectCallCount(enterSpy, 0);
                moveBy(2, -4);
                runsExpectCallCount(enterSpy, 1);
                moveBy(10, -10);
                runsExpectCallCount(enterSpy, 1);
                moveBy(-10, 10);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(-2, 4);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });

            it("should match an enter from the left", function() {
                makeDragEl(10, 80);
                makeDropEl(50, 50);
                setup();

                startPosDrag('end', 'middle');
                moveBy(19, 0);
                runsExpectCallCount(enterSpy, 0);
                moveBy(2, 0);
                runsExpectCallCount(enterSpy, 1);
                moveBy(10, 0);
                runsExpectCallCount(enterSpy, 1);
                moveBy(-10, 0);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(-2, 0);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });

            it("should match an enter from the top left", function() {
                makeDragEl(10, 10);
                makeDropEl(50, 50);
                setup();

                startPosDrag('end', 'end');
                moveBy(19, 19);
                runsExpectCallCount(enterSpy, 0);
                moveBy(2, 2);
                runsExpectCallCount(enterSpy, 1);
                moveBy(10, 10);
                runsExpectCallCount(enterSpy, 1);
                moveBy(-10, -10);
                runsExpectCallCount(enterSpy, 1);
                runsExpectCallCount(leaveSpy, 0);
                moveBy(-2, -2);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
            });
        });

        describe("z-index", function() {
            var drop1, drop2, drop3;

            function makeZIndexDrop(zIndex, color) {
                var el = makeEl({
                    style: {
                        position: 'absolute',
                        width: '100px',
                        height: '100px',
                        left: '50px',
                        top: '50px',
                        zIndex: zIndex,
                        backgroundColor: color
                    }
                });

                return new Ext.drag.Target({
                    element: el
                });
            }

            afterEach(function() {
                Ext.destroy(drop1, drop2, drop3);
            });

            it("should only match the topmost z-index", function() {
                drop1 = makeZIndexDrop(300, 'red');
                drop2 = makeZIndexDrop(200, 'blue');
                drop3 = makeZIndexDrop(100, 'green');

                makeDragEl();
                makeSource();
                drop1.on('dragenter', enterSpy.andCallFake(function(source, info) {
                    enterSpy.mostRecentCall.dragInfo = info.clone();
                }));
                drop1.on('dragleave', leaveSpy.andCallFake(function(source, info) {
                    leaveSpy.mostRecentCall.dragInfo = info.clone();
                }));

                startDrag();
                moveBy(50, 50);
                runs(function() {
                    expect(enterSpy.callCount).toBe(1);
                    expect(enterSpy.mostRecentCall.dragInfo.target).toBe(drop1);
                });
                moveBy(200, 200);
                runs(function() {
                    expect(leaveSpy.callCount).toBe(1);
                    expect(leaveSpy.mostRecentCall.dragInfo.target).toBe(drop1);
                });
                endDrag();
            });

            it("should not move to a lower z-index if the topmost doesn't accept the drop", function() {
                drop1 = makeZIndexDrop(300, 'red');
                drop2 = makeZIndexDrop(200, 'blue');
                drop3 = makeZIndexDrop(100, 'green');

                makeDragEl();
                makeSource();
                drop1.disable();
                drop2.on('dragenter', enterSpy);
                drop3.on('dragenter', enterSpy);

                startDrag();
                moveBy(50, 50);
                runsExpectCallCount(enterSpy, 0);
                endDrag();
            });
        });

        describe("nested targets", function() {
            it("should transition to the inner target", function() {
                makeDragEl();
                makeDropEl();

                var inner = new Ext.drag.Target({
                    element: Ext.getBody().createChild({
                        style: {
                            position: 'absolute',
                            width: '50px',
                            height: '50px',
                            left: '70px',
                            top: '70px',
                            border: '1px solid green'
                        }
                    })
                });

                makeSource();
                makeTarget();

                target.on('dragenter', enterSpy.andCallFake(function(target, info) {
                    enterSpy.mostRecentCall.dragInfo = info.clone();
                }));
                target.on('dragleave', leaveSpy.andCallFake(function(target, info) {
                    leaveSpy.mostRecentCall.dragInfo = info.clone();
                }));

                inner.on('dragenter', enterSpy.andCallFake(function(target, info) {
                    enterSpy.mostRecentCall.dragInfo = info.clone();
                }));
                inner.on('dragleave', leaveSpy.andCallFake(function(target, info) {
                    leaveSpy.mostRecentCall.dragInfo = info.clone();
                }));

                startDrag();
                moveBy(50, 50);
                runs(function() {
                    expect(enterSpy.callCount).toBe(1);
                    expect(enterSpy.mostRecentCall.dragInfo.target).toBe(target);
                });
                moveBy(30, 30);
                runs(function() {
                    expect(leaveSpy.callCount).toBe(1);
                    expect(leaveSpy.mostRecentCall.dragInfo.target).toBe(target);
                    expect(enterSpy.callCount).toBe(2);
                    expect(enterSpy.mostRecentCall.dragInfo.target).toBe(inner);

                });
                moveBy(40, 40);
                runs(function() {
                    expect(leaveSpy.callCount).toBe(2);
                    expect(leaveSpy.mostRecentCall.dragInfo.target).toBe(inner);
                    expect(enterSpy.callCount).toBe(3);
                    expect(enterSpy.mostRecentCall.dragInfo.target).toBe(target);
                });
                moveBy(100, 100);
                runs(function() {
                    expect(leaveSpy.callCount).toBe(3);
                    expect(leaveSpy.mostRecentCall.dragInfo.target).toBe(target);
                    expect(enterSpy.callCount).toBe(3);
                });

                runs(function() {
                    endDrag();
                    inner.destroy();
                });
            });
        });

        describe("scroll", function() {
            it("should match targets when the document is scrolled", function() {
                var stretcher, s;

                makeDragEl(405, 405);
                makeDropEl(450, 450);

                setup();
                s = Ext.getViewportScroller();

                stretcher = Ext.getBody().insertFirst({
                    style: {
                        width: '5000px',
                        height: '5000px',
                        border: '1px solid red'
                    }
                });

                s.scrollTo(0, 400);

                waitsForEvent(s, 'scrollend');

                startDrag();
                moveBy(50, 50);
                runsExpectCallCount(enterSpy, 1);
                moveBy(-20, -20);
                runsExpectCallCount(leaveSpy, 1);
                endDrag();
                runs(function() {
                    stretcher.remove();
                });
            });
        });
    });

    describe("restrictions", function() {
        var enterSpy, leaveSpy;

        beforeEach(function() {
            makeDragEl();
            makeDropEl();

            enterSpy = jasmine.createSpy();
            leaveSpy = jasmine.createSpy();
        });

        afterEach(function() {
            enterSpy = leaveSpy = null;
        });

        function dragInOutTarget() {
            target.on('dragenter', enterSpy.andCallFake(function(target, info) {
                enterSpy.mostRecentCall.valid = info.valid;
            }));
            target.on('dragleave', leaveSpy.andCallFake(function(target, info) {
                leaveSpy.mostRecentCall.valid = info.valid;
            }));
            startDrag();
            moveBy(50, 50);
            moveBy(200, 200);
            endDrag();
        }

        function runsExpectValid(spies, valid) {
            runs(function() {
                if (!Ext.isArray(spies)) {
                    spies = [spies];
                }

                Ext.Array.forEach(spies, function(spy) {
                    expect(spy.mostRecentCall.valid).toBe(valid);
                });
            });
        }

        describe("disabled", function() {
            it("should not interact if the target is disabled", function() {
                makeSource();
                makeTarget();
                target.disable();

                dragInOutTarget();
                runsExpectValid([enterSpy, leaveSpy], false);
            });
        });

        describe("accepts", function() {
            it("should accept by default", function() {
                makeSource();
                makeTarget();

                dragInOutTarget();
                runsExpectValid([enterSpy, leaveSpy], true);
            });

            it("should not call accept if target is disabled", function() {
                var accepts = jasmine.createSpy().andReturn(true);

                makeSource();
                makeTarget({
                    accepts: accepts
                });

                target.disable();

                dragInOutTarget();
                runsExpectCallCount(accepts, 0);
            });

            it("should not call accept if groups don't match", function() {
                var accepts = jasmine.createSpy().andReturn(true);

                makeSource({
                    groups: 'foo'
                });
                makeTarget({
                    groups: 'bar',
                    accepts: accepts
                });

                dragInOutTarget();
                runsExpectCallCount(accepts, 0);
            });

            it("should not enter the target if it doesn't accept the data", function() {
                var accepts = jasmine.createSpy().andReturn(false);

                makeSource();
                makeTarget({
                    accepts: accepts
                });

                dragInOutTarget();
                runsExpectValid([enterSpy, leaveSpy], false);
            });

            it("should pass the info object", function() {
                var accepts = jasmine.createSpy().andReturn(true);

                makeSource();
                makeTarget({
                    accepts: accepts
                });

                startDrag();
                moveBy(50, 50);
                runs(function() {
                    expect(accepts.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                });
                endDrag();
            });

            it("should only call accepts once it enters the target", function() {
                var accepts = jasmine.createSpy().andReturn(true);

                makeSource();
                makeTarget({
                    accepts: accepts
                });

                startDrag();
                moveBy(10, 10);
                runsExpectCallCount(accepts, 0);
                moveBy(10, 10);
                runsExpectCallCount(accepts, 0);
                moveBy(30, 30);
                runsExpectCallCount(accepts, 1);
                endDrag();
            });

            it("should only call accepts once when it enters the target and the source is accepted", function() {
                var accepts = jasmine.createSpy().andReturn(true);

                makeSource();
                makeTarget({
                    accepts: accepts
                });

                startDrag();
                moveBy(50, 50);
                runsExpectCallCount(accepts, 1);
                // Still inside the target
                moveBy(5, 5);
                runsExpectCallCount(accepts, 1);
                moveBy(5, 5);
                runsExpectCallCount(accepts, 1);
                moveBy(5, 5);
                runsExpectCallCount(accepts, 1);
                // Outside the target
                moveBy(200, 200);
                runsExpectCallCount(accepts, 1);
                endDrag();
            });

            it("should only call accepts once when it enters the target and the source is not accepted", function() {
                var accepts = jasmine.createSpy().andReturn(false);

                makeSource();
                makeTarget({
                    accepts: accepts
                });

                startDrag();
                moveBy(50, 50);
                runsExpectCallCount(accepts, 1);
                // Still inside the target
                moveBy(5, 5);
                runsExpectCallCount(accepts, 1);
                moveBy(5, 5);
                runsExpectCallCount(accepts, 1);
                moveBy(5, 5);
                runsExpectCallCount(accepts, 1);
                // Outside the target
                moveBy(200, 200);
                runsExpectCallCount(accepts, 1);
                endDrag();
            });

            it("should call accepts each time it enters the target", function() {
                var accepts = jasmine.createSpy().andReturn(true);

                makeSource();
                makeTarget({
                    accepts: accepts
                });
                startDrag();
                moveBy(50, 50);
                runsExpectCallCount(accepts, 1);
                moveBy(-50, -50);
                runsExpectCallCount(accepts, 1);
                moveBy(50, 50);
                runsExpectCallCount(accepts, 2);
                moveBy(-50, -50);
                runsExpectCallCount(accepts, 2);
                moveBy(50, 50);
                runsExpectCallCount(accepts, 3);
                endDrag();
            });
        });

        describe("groups", function() {
            describe("source: no group, target: no group", function() {
                it("should interact", function() {
                    makeSource();
                    makeTarget();

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], true);
                });
            });

            describe("source: no group, target: one group", function() {
                it("should not interact", function() {
                    makeSource();
                    makeTarget({
                        groups: 'a'
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });

            describe("source: no group, target: multiple groups", function() {
                it("should not interact", function() {
                    makeSource();
                    makeTarget({
                        groups: ['a', 'b', 'c']
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });

            describe("source: one group, target: no group", function() {
                it("should not interact", function() {
                    makeSource({
                        groups: 'a'
                    });
                    makeTarget();

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });

            describe("source: one group, target: one group", function() {
                it("should interact if the groups are the same", function() {
                    makeSource({
                        groups: 'a'
                    });
                    makeTarget({
                        groups: 'a'
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], true);
                });

                it("should not interact if the groups are different", function() {
                    makeSource({
                        groups: 'a'
                    });
                    makeTarget({
                        groups: 'b'
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });

            describe("source: one group, target: multiple groups", function() {
                it("should interact if the source group exists in the target groups", function() {
                    makeSource({
                        groups: 'b'
                    });
                    makeTarget({
                        groups: ['a', 'b', 'c']
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], true);
                });

                it("should not interact if the source group doesn't exist in the target groups", function() {
                    makeSource({
                        groups: 'bleh'
                    });
                    makeTarget({
                        groups: ['a', 'b', 'c']
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });

            describe("source: multiple groups, target: no group", function() {
                it("should not interact", function() {
                    makeSource({
                        groups: ['a', 'b', 'c']
                    });
                    makeTarget();

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });

            describe("source: multiple groups, target: one group", function() {
                it("should interact if the target group exists in the source groups", function() {
                    makeSource({
                        groups: ['a', 'b', 'c']
                    });
                    makeTarget({
                        groups: 'b'
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], true);
                });

                it("should not interact if the target group doesn't exist in the source groups", function() {
                    makeSource({
                        groups: ['a', 'b', 'c']
                    });
                    makeTarget({
                        groups: 'bleh'
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });

            describe("source: multiple groups, target: multiple groups", function() {
                it("should interact if the groups intersect", function() {
                    makeSource({
                        groups: ['a', 'b', 'c']
                    });
                    makeTarget({
                        groups: ['a', 'c']
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], true);
                });

                it("should not interact if the groups don't intersect", function() {
                    makeSource({
                        groups: ['a', 'b', 'c']
                    });
                    makeTarget({
                        groups: ['x', 'y', 'z']
                    });

                    dragInOutTarget();
                    runsExpectValid([enterSpy, leaveSpy], false);
                });
            });
        });
    });
});
