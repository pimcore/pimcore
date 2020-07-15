topSuite("Ext.drag.Target", ['Ext.drag.*', 'Ext.dom.Element'], function() {
    var helper = Ext.testHelper,
        touchId = 0,
        cursorTrack, source, target,
        dragEl, dropEl, defaultElCfg;

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

    function makeTarget(cfg, Type) {
        cfg = cfg || {};

        if (!cfg.element) {
            if (!dropEl) {
                makeDropEl();
            }

            cfg.element = dropEl;
        }

        Type = Type || Ext.drag.Target;
        target = new Type(cfg);
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

    function dragBy(x, y, target) {
        startDrag(null, null, target);
        moveBy(x, y, target);
        endDrag(null, null, target);
    }

    function getCenter(el) {
        var xy = el.getXY();

        return [xy[0] + (el.getWidth() / 2), xy[1] + (el.getHeight() / 2)];
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
        defaultElCfg = {
            style: {
                position: 'absolute',
                width: '20px',
                height: '20px',
                left: '50px',
                top: '50px',
                border: '1px solid red'
            }
        };
    });

    afterEach(function() {
        cursorTrack = source = target = dragEl = dropEl = Ext.destroy(dragEl, dropEl, source, target);
    });

    function expectXY(x, y) {
        var info = source.getInfo(),
            el = (info && info.proxy.element) || source.getElement();

        expect(el.getXY()).toEqual([x, y]);
    }

    function expectElXY(x, y) {
        expect(source.getElement().getXY()).toEqual([x, y]);
    }

    function expectProxyXY(x, y) {
        expect(source.getInfo().proxy.element.getXY()).toEqual([x, y]);
    }

    function runsExpectXY(x, y) {
        runs(function() {
            expectXY(x, y);
        });
    }

    function runsExpectElXY(x, y) {
        runs(function() {
            expectElXY(x, y);
        });
    }

    function runsExpectProxyXY(x, y) {
        runs(function() {
            expectProxyXY(x, y);
        });
    }

    describe("autoDestroy", function() {
        it("should destroy the element by default", function() {
            makeTarget();

            target.destroy();
            expect(Ext.get(dropEl.id)).toBeNull();
        });

        it("should destroy the element with autoDestroy: true", function() {
            makeTarget({
                autoDestroy: true
            });

            target.destroy();
            expect(Ext.get(dropEl.id)).toBeNull();
        });

        it("should not destroy the element with autoDestroy: false", function() {
            makeTarget({
                autoDestroy: false
            });

            target.destroy();
            expect(Ext.get(dropEl.id)).toBe(dropEl);
        });
    });

    describe("accepts", function() {
        describe("as a config", function() {
            it("should be able to be passed as a config", function() {
                var spy = jasmine.createSpy();

                makeSource();
                makeTarget({
                    accepts: spy
                });

                startDrag();
                moveBy(50, 50);
                runsExpectCallCount(spy, 1);
                endDrag();
            });

            it("should receive the info object", function() {
                var spy = jasmine.createSpy();

                makeSource();
                makeTarget({
                    accepts: spy
                });

                startDrag();
                moveBy(50, 50);
                runs(function() {
                    expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                });
                endDrag();
            });
        });

        describe("as a subclassed method", function() {
            it("should be able to be passed as a config", function() {
                var spy = jasmine.createSpy();

                var T = Ext.define(null, {
                    extend: 'Ext.drag.Target',
                    accepts: spy
                });

                makeSource();
                makeTarget({}, T);

                startDrag();
                moveBy(50, 50);
                runsExpectCallCount(spy, 1);
                endDrag();
            });

            it("should receive the info object", function() {
                var spy = jasmine.createSpy();

                var T = Ext.define(null, {
                    extend: 'Ext.drag.Target',
                    accepts: spy
                });

                makeSource();
                makeTarget({}, T);

                startDrag();
                moveBy(50, 50);
                runs(function() {
                    expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                });
                endDrag();
            });
        });
    });

    describe("template methods", function() {
        var spy;

        beforeEach(function() {
            makeSource();
            makeTarget();
        });

        afterEach(function() {
            spy = null;
        });

        describe("source enter", function() {
            beforeEach(function() {
                spy = spyOn(target, 'onDragEnter').andCallFake(function(info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                    spy.originalValue.apply(this, arguments);
                });
            });

            describe("when valid", function() {
                it("should call onDragEnter each time the target is entered", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    runsExpectCallCount(spy, 1);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 1);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 1);
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 1);
                    // Move out
                    moveBy(100, 100);
                    runsExpectCallCount(spy, 1);
                    // Back in
                    moveBy(-50, -50);
                    runsExpectCallCount(spy, 2);
                    moveBy(-5, -5);
                    runsExpectCallCount(spy, 2);
                    endDrag();
                });

                it("should pass the info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                        expect(info.target).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.valid).toBe(true);
                    });
                    endDrag();
                });

                it("should not be called if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should call onDragEnter each time the target is entered", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 1);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 2);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should call onDragEnter each time the target is entered", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 1);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 2);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should call onDragEnter each time the target is entered", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 1);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 2);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("source move", function() {
            beforeEach(function() {
                spy = spyOn(target, 'onDragMove').andCallFake(function(info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                    spy.originalValue.apply(this, arguments);
                });
            });

            describe("when valid", function() {
                it("should call onDragMove for each move inside the target", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    runsExpectCallCount(spy, 1);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 2);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 3);
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 4);
                    // Move out
                    moveBy(100, 100);
                    runsExpectCallCount(spy, 4);
                    // Back in
                    moveBy(-50, -50);
                    runsExpectCallCount(spy, 5);
                    moveBy(-5, -5);
                    runsExpectCallCount(spy, 6);
                    endDrag();
                });

                it("should pass the info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                        expect(info.target).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.valid).toBe(true);
                    });
                    endDrag();
                });

                it("should not be called if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should call onDragMove for each move inside the target", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 2);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 3);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 4);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 4);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 5);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 6);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should call onDragMove for each move inside the target", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 2);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 3);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 4);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 4);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 5);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 6);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should call onDragMove for each move inside the target", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 2);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 3);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 4);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 4);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 5);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 6);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("source leave", function() {
            beforeEach(function() {
                spy = spyOn(target, 'onDragLeave').andCallFake(function(info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                    spy.originalValue.apply(this, arguments);
                });
            });

            describe("when valid", function() {
                it("should call onDragLeave each time the target is left", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    runsExpectCallCount(spy, 0);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 0);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 0);
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    // Move out
                    moveBy(100, 100);
                    runsExpectCallCount(spy, 1);
                    // Back in
                    moveBy(-50, -50);
                    runsExpectCallCount(spy, 1);
                    moveBy(-5, -5);
                    runsExpectCallCount(spy, 1);
                    moveBy(-70, -70);
                    runsExpectCallCount(spy, 2);
                    endDrag();
                });

                it("should pass the info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    moveBy(100, 100);
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                        expect(info.target).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.valid).toBe(true);
                    });
                    endDrag();
                });

                it("should not be called if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should not be called if the target is never left", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should call onDragLeave each time the target is left", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 1);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 1);
                        moveBy(-70, -70);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        moveBy(100, 100);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });

                    it("should not be called if the target is never left", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should call onDragLeave each time the target is left", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 1);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 1);
                        moveBy(-70, -70);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        moveBy(100, 100);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });

                    it("should not be called if the target is never left", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should call onDragLeave each time the target is left", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 1);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 1);
                        moveBy(-70, -70);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        moveBy(100, 100);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                            expect(info.target).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });

                    it("should not be called if the target is never left", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("before drop", function() {
            beforeEach(function() {
                spy = spyOn(target, 'beforeDrop').andCallFake(function(info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                    spy.originalValue.apply(this, arguments);
                });
            });

            describe("when valid", function() {
                it("should be called before dropped on the target", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runsExpectCallCount(spy, 1);
                });

                it("should pass the info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                        expect(info.target).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.valid).toBe(true);
                    });
                });

                it("should not be called if the drop does not occur on the target", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should not be called", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should not be called", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should not be called", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("source drop", function() {
            beforeEach(function() {
                spy = spyOn(target, 'onDrop').andCallFake(function(info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                    spy.originalValue.apply(this, arguments);
                });
            });

            describe("when valid", function() {
                it("should be called when dropped on the target", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runsExpectCallCount(spy, 1);
                });

                it("should pass the info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0] instanceof Ext.drag.Info).toBe(true);
                        expect(info.target).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.valid).toBe(true);
                    });
                });

                it("should not be called if the drop does not occur on the target", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should not be called if the beforeDrop method returns false", function() {
                    target.beforeDrop = function() {
                        return false;
                    };

                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should not be called if the beforedrop listener returns false", function() {
                    target.on('beforedrop', function() {
                        return false;
                    });
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should not be called", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should not be called", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should not be called", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });
    });

    describe("events", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();

            makeSource();
            makeTarget();
        });

        afterEach(function() {
            spy = null;
        });

        describe("dragenter", function() {
            beforeEach(function() {
                target.on('dragenter', spy.andCallFake(function(target, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));
            });

            describe("when valid", function() {
                it("should fire each time the target is entered", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    runsExpectCallCount(spy, 1);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 1);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 1);
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 1);
                    // Move out
                    moveBy(100, 100);
                    runsExpectCallCount(spy, 1);
                    // Back in
                    moveBy(-50, -50);
                    runsExpectCallCount(spy, 2);
                    moveBy(-5, -5);
                    runsExpectCallCount(spy, 2);
                    endDrag();
                });

                it("should pass the target, info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0]).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.target).toBe(target);
                        expect(info.valid).toBe(true);
                    });
                    endDrag();
                });

                it("should not fire if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should fire each time the target is entered", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 1);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 2);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should fire each time the target is entered", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 1);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 2);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should fire each time the target is entered", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 1);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 1);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 2);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("dragmove", function() {
            beforeEach(function() {
                target.on('dragmove', spy.andCallFake(function(target, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));
            });

            describe("when valid", function() {
                it("should fire for each move inside the target", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    runsExpectCallCount(spy, 1);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 2);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 3);
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 4);
                    // Move out
                    moveBy(100, 100);
                    runsExpectCallCount(spy, 4);
                    // Back in
                    moveBy(-50, -50);
                    runsExpectCallCount(spy, 5);
                    moveBy(-5, -5);
                    runsExpectCallCount(spy, 6);
                    endDrag();
                });

                it("should pass the target, info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0]).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.target).toBe(target);
                        expect(info.valid).toBe(true);
                    });
                    endDrag();
                });

                it("should not be called if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should fire for each move inside the target", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 2);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 3);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 4);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 4);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 5);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 6);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should fire for each move inside the target", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 2);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 3);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 4);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 4);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 5);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 6);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should fire for each move inside the target", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 1);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 2);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 3);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 4);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 4);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 5);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 6);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not be called if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("dragleave", function() {
            beforeEach(function() {
                target.on('dragleave', spy.andCallFake(function(target, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));
            });

            describe("when valid", function() {
                it("should fire each time the target is left", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    runsExpectCallCount(spy, 0);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 0);
                    moveBy(1, 1);
                    runsExpectCallCount(spy, 0);
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    // Move out
                    moveBy(100, 100);
                    runsExpectCallCount(spy, 1);
                    // Back in
                    moveBy(-50, -50);
                    runsExpectCallCount(spy, 1);
                    moveBy(-5, -5);
                    runsExpectCallCount(spy, 1);
                    moveBy(-70, -70);
                    runsExpectCallCount(spy, 2);
                    endDrag();
                });

                it("should pass the target, info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    moveBy(100, 100);
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0]).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.target).toBe(target);
                        expect(info.valid).toBe(true);
                    });
                    endDrag();
                });

                it("should not fire if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should not fire if the target is never left", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should fire each time the target is left", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 1);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 1);
                        moveBy(-70, -70);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        moveBy(100, 100);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not fire if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });

                    it("should not fire if the target is never left", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should fire each time the target is left", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 1);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 1);
                        moveBy(-70, -70);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        moveBy(100, 100);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not fire if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });

                    it("should not fire if the target is never left", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should fire each time the target is left", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(1, 1);
                        runsExpectCallCount(spy, 0);
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        // Move out
                        moveBy(100, 100);
                        runsExpectCallCount(spy, 1);
                        // Back in
                        moveBy(-50, -50);
                        runsExpectCallCount(spy, 1);
                        moveBy(-5, -5);
                        runsExpectCallCount(spy, 1);
                        moveBy(-70, -70);
                        runsExpectCallCount(spy, 2);
                        endDrag();
                    });

                    it("should pass the target, info and have the valid state set correctly", function() {
                        startDrag();
                        moveBy(50, 50);
                        moveBy(100, 100);
                        runs(function() {
                            var info = spy.mostRecentCall.dragInfo;

                            expect(spy.mostRecentCall.args[0]).toBe(target);
                            expect(info.source).toBe(source);
                            expect(info.target).toBe(target);
                            expect(info.valid).toBe(false);
                        });
                        endDrag();
                    });

                    it("should not fire if the target is never entered", function() {
                        startDrag();
                        moveBy(0, 100);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });

                    it("should not fire if the target is never left", function() {
                        startDrag();
                        moveBy(50, 50);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("beforedrop", function() {
            beforeEach(function() {
                target.on('beforedrop', spy.andCallFake(function(target, info) {
                    spy.mostRecentCall.dragInfo = info.clone();
                }));
            });

            describe("when valid", function() {
                it("should fire when the source is dropped", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    endDrag();
                    runsExpectCallCount(spy, 1);
                });

                it("should pass the target, info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0]).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.target).toBe(target);
                        expect(info.valid).toBe(true);
                    });
                });

                it("should not fire if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should not fire if the target is not dropped on", function() {
                    startDrag();
                    moveBy(50, 50);
                    moveBy(100, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });
            });

            describe("when invalid", function() {
                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should not fire", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should not fire", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should not fire", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });

        describe("drop", function() {
            describe("when valid", function() {
                beforeEach(function() {
                    target.on('drop', spy.andCallFake(function(target, info) {
                        spy.mostRecentCall.dragInfo = info.clone();
                    }));
                });

                it("should fire when the source is dropped", function() {
                    startDrag();
                    moveBy(10, 10);
                    runsExpectCallCount(spy, 0);
                    moveBy(40, 40);
                    endDrag();
                    runsExpectCallCount(spy, 1);
                });

                it("should pass the target, info and have the valid state set correctly", function() {
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runs(function() {
                        var info = spy.mostRecentCall.dragInfo;

                        expect(spy.mostRecentCall.args[0]).toBe(target);
                        expect(info.source).toBe(source);
                        expect(info.target).toBe(target);
                        expect(info.valid).toBe(true);
                    });
                });

                it("should not fire if the target is never entered", function() {
                    startDrag();
                    moveBy(0, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should not fire if the target is not dropped on", function() {
                    startDrag();
                    moveBy(50, 50);
                    moveBy(100, 100);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should not fire if beforedrop returns false", function() {
                    target.on('beforedrop', function() {
                        return false;
                    });
                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runsExpectCallCount(spy, 0);
                });

                it("should fire after beforedrop", function() {
                    var order = [];

                    target.on('beforedrop', function() {
                        order.push('before');
                    });

                    target.on('drop', function() {
                        order.push('drop');
                    });

                    startDrag();
                    moveBy(50, 50);
                    endDrag();
                    runs(function() {
                        expect(order).toEqual(['before', 'drop']);
                    });
                });
            });

            describe("when invalid", function() {
                beforeEach(function() {
                    target.on('invaliddrop', spy);
                });

                describe("via disable", function() {
                    beforeEach(function() {
                        target.disable();
                    });

                    it("should not fire when the source is dropped", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via groups", function() {
                    beforeEach(function() {
                        target.setGroups('foo');
                    });

                    it("should not fire when the source is dropped", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });

                describe("via accepts", function() {
                    beforeEach(function() {
                        target.accepts = function() {
                            return false;
                        };
                    });

                    it("should not fire when the source is dropped", function() {
                        startDrag();
                        moveBy(10, 10);
                        runsExpectCallCount(spy, 0);
                        moveBy(40, 40);
                        endDrag();
                        runsExpectCallCount(spy, 0);
                    });
                });
            });
        });
    });

    describe("classes", function() {
        function runsExpectCls(cls) {
            runs(function() {
                expect(target.getElement()).toHaveCls(cls);
            });
        }

        function runsExpectNotCls(cls) {
            runs(function() {
                expect(target.getElement()).not.toHaveCls(cls);
            });
        }

        describe("invalidCls", function() {
            beforeEach(function() {
                makeSource({
                    groups: 'group1'
                });
            });

            it("should have the class when over the target and invalid", function() {
                makeTarget({
                    groups: 'group2',
                    invalidCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                runsExpectCls('foo');
                endDrag();
            });

            it("should not have the class by default", function() {
                makeTarget({
                    groups: 'group2',
                    invalidCls: 'foo'
                });
                expect(target.getElement()).not.toHaveCls('foo');
            });

            it("should not have the class if the source is not over the target", function() {
                makeTarget({
                    groups: 'group2',
                    invalidCls: 'foo'
                });
                startDrag();
                moveBy(10, 10);
                runsExpectNotCls('foo');
                endDrag();
            });

            it("should not have the class if the source is valid", function() {
                makeTarget({
                    groups: 'group1',
                    invalidCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                runsExpectNotCls('foo');
                endDrag();
            });

            it("should not have the class after dropping", function() {
                makeTarget({
                    groups: 'group2',
                    invalidCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                endDrag();
                runsExpectNotCls('foo');
            });

            it("should not have the class after the source leaves the target", function() {
                makeTarget({
                    groups: 'group2',
                    invalidCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                runsExpectCls('foo');
                moveBy(200, 200);
                runsExpectNotCls('foo');
                endDrag();
            });

            describe("not during drag", function() {
                describe("before first drag", function() {
                    it("should be able to set a cls", function() {
                        makeTarget({
                            groups: 'group2'
                        });
                        target.setInvalidCls('foo');
                        startDrag();
                        moveBy(50, 50);
                        runsExpectCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to clear a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            invalidCls: 'foo'
                        });
                        target.setInvalidCls('');
                        startDrag();
                        moveBy(50, 50);
                        runsExpectNotCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to change a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            invalidCls: 'foo'
                        });
                        target.setInvalidCls('bar');
                        startDrag();
                        moveBy(50, 50);
                        runsExpectNotCls('foo');
                        runsExpectCls('bar');
                        endDrag();
                        runsExpectNotCls('foo');
                        runsExpectNotCls('bar');
                    });
                });

                describe("after first drag", function() {
                    it("should be able to set a cls", function() {
                        makeTarget({
                            groups: 'group2'
                        });
                        dragBy(10, 10);
                        runs(function() {
                            target.setInvalidCls('foo');
                        });
                        startDrag();
                        moveBy(40, 40);
                        runsExpectCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to clear a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            invalidCls: 'foo'
                        });
                        dragBy(10, 10);
                        runs(function() {
                            target.setInvalidCls('');
                        });
                        startDrag();
                        moveBy(40, 40);
                        runsExpectNotCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to change a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            invalidCls: 'foo'
                        });
                        dragBy(10, 10);
                        runs(function() {
                            target.setInvalidCls('bar');
                        });
                        startDrag();
                        moveBy(40, 40);
                        runsExpectNotCls('foo');
                        runsExpectCls('bar');
                        endDrag();
                        runsExpectNotCls('foo');
                        runsExpectNotCls('bar');
                    });
                });
            });

            describe("during drag", function() {
                it("should be able to set a cls", function() {
                    makeTarget({
                        groups: 'group2'
                    });
                    startDrag();
                    moveBy(50, 50);
                    runsExpectNotCls('foo');
                    runs(function() {
                        target.setInvalidCls('foo');
                    });
                    runsExpectCls('foo');
                    endDrag();
                    runsExpectNotCls('foo');
                });

                it("should be able to clear a cls", function() {
                    makeTarget({
                        groups: 'group2',
                        invalidCls: 'foo'
                    });
                    startDrag();
                    moveBy(50, 50);
                    runsExpectCls('foo');
                    runs(function() {
                        target.setInvalidCls('');
                    });
                    runsExpectNotCls('foo');
                    endDrag();
                    runsExpectNotCls('foo');
                });

                it("should be able to change a cls", function() {
                    makeTarget({
                        groups: 'group2',
                        invalidCls: 'foo'
                    });
                    startDrag();
                    moveBy(50, 50);
                    runsExpectCls('foo');
                    runs(function() {
                        target.setInvalidCls('bar');
                    });
                    runsExpectNotCls('foo');
                    runsExpectCls('bar');
                    endDrag();
                    runsExpectNotCls('foo');
                    runsExpectNotCls('bar');
                });
            });
        });

        describe("validCls", function() {
            beforeEach(function() {
                makeSource({
                    groups: 'group2'
                });
            });

            it("should have the class when over the target and valid", function() {
                makeTarget({
                    groups: 'group2',
                    validCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                runsExpectCls('foo');
                endDrag();
            });

            it("should not have the class by default", function() {
                makeTarget({
                    groups: 'group2',
                    validCls: 'foo'
                });
                expect(target.getElement()).not.toHaveCls('foo');
            });

            it("should not have the class if the source is not over the target", function() {
                makeTarget({
                    groups: 'group2',
                    validCls: 'foo'
                });
                startDrag();
                moveBy(10, 10);
                runsExpectNotCls('foo');
                endDrag();
            });

            it("should not have the class if the source is invalid", function() {
                makeTarget({
                    groups: 'group1',
                    validCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                runsExpectNotCls('foo');
                endDrag();
            });

            it("should not have the class after dropping", function() {
                makeTarget({
                    groups: 'group2',
                    validCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                endDrag();
                runsExpectNotCls('foo');
            });

            it("should not have the class after the source leaves the target", function() {
                makeTarget({
                    groups: 'group2',
                    validCls: 'foo'
                });
                startDrag();
                moveBy(50, 50);
                runsExpectCls('foo');
                moveBy(200, 200);
                runsExpectNotCls('foo');
                endDrag();
            });

            describe("not during drag", function() {
                describe("before first drag", function() {
                    it("should be able to set a cls", function() {
                        makeTarget({
                            groups: 'group2'
                        });
                        target.setValidCls('foo');
                        startDrag();
                        moveBy(50, 50);
                        runsExpectCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to clear a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            validCls: 'foo'
                        });
                        target.setValidCls('');
                        startDrag();
                        moveBy(50, 50);
                        runsExpectNotCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to change a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            validCls: 'foo'
                        });
                        target.setValidCls('bar');
                        startDrag();
                        moveBy(50, 50);
                        runsExpectNotCls('foo');
                        runsExpectCls('bar');
                        endDrag();
                        runsExpectNotCls('foo');
                        runsExpectNotCls('bar');
                    });
                });

                describe("after first drag", function() {
                    it("should be able to set a cls", function() {
                        makeTarget({
                            groups: 'group2'
                        });
                        dragBy(10, 10);
                        runs(function() {
                            target.setValidCls('foo');
                        });
                        startDrag();
                        moveBy(40, 40);
                        runsExpectCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to clear a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            validCls: 'foo'
                        });
                        dragBy(10, 10);
                        runs(function() {
                            target.setValidCls('');
                        });
                        startDrag();
                        moveBy(40, 40);
                        runsExpectNotCls('foo');
                        endDrag();
                        runsExpectNotCls('foo');
                    });

                    it("should be able to change a cls", function() {
                        makeTarget({
                            groups: 'group2',
                            validCls: 'foo'
                        });
                        dragBy(10, 10);
                        runs(function() {
                            target.setValidCls('bar');
                        });
                        startDrag();
                        moveBy(40, 40);
                        runsExpectNotCls('foo');
                        runsExpectCls('bar');
                        endDrag();
                        runsExpectNotCls('foo');
                        runsExpectNotCls('bar');
                    });
                });
            });

            describe("during drag", function() {
                it("should be able to set a cls", function() {
                    makeTarget({
                        groups: 'group2'
                    });
                    startDrag();
                    moveBy(50, 50);
                    runsExpectNotCls('foo');
                    runs(function() {
                        target.setValidCls('foo');
                    });
                    runsExpectCls('foo');
                    endDrag();
                    runsExpectNotCls('foo');
                });

                it("should be able to clear a cls", function() {
                    makeTarget({
                        groups: 'group2',
                        validCls: 'foo'
                    });
                    startDrag();
                    moveBy(50, 50);
                    runsExpectCls('foo');
                    runs(function() {
                        target.setValidCls('');
                    });
                    runsExpectNotCls('foo');
                    endDrag();
                    runsExpectNotCls('foo');
                });

                it("should be able to change a cls", function() {
                    makeTarget({
                        groups: 'group2',
                        validCls: 'foo'
                    });
                    startDrag();
                    moveBy(50, 50);
                    runsExpectCls('foo');
                    runs(function() {
                        target.setValidCls('bar');
                    });
                    runsExpectNotCls('foo');
                    runsExpectCls('bar');
                    endDrag();
                    runsExpectNotCls('foo');
                    runsExpectNotCls('bar');
                });
            });
        });
    });

    describe("interaction", function() {
        it("should be reachable when the element is also a source with handles", function() {
            var spy = jasmine.createSpy();

            dragEl = dropEl = makeEl({
                style: {
                    width: '600px',
                    height: '600px',
                    border: '1px solid blue',
                    position: 'relative'
                },
                children: [{
                    cls: 'foo',
                    style: {
                        width: '100px',
                        height: '100px',
                        border: '1px solid red',
                        position: 'absolute',
                        left: '100px',
                        top: '100px'
                    }
                }]
            });

            makeSource({
                handle: '.foo',
                proxy: {
                    type: 'placeholder',
                    getElement: function() {
                        this.element = this.element || Ext.getBody().createChild({
                            style: {
                                width: '100px',
                                height: '100px',
                                border: '1px solid green'
                            }
                        });

                        return this.element;
                    }
                }
            });
            makeTarget({
                listeners: {
                    drop: spy
                }
            });

            var handle = Ext.fly(dragEl.down('.foo', true));

            startDrag(null, null, handle);
            moveBy(100, 100);
            endDrag(null, null, handle);
            runsExpectCallCount(spy, 1);
        });
    });
});
