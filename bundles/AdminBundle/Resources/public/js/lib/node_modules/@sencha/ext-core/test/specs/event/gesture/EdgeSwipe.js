// describe("Ext.event.gesture.DoubleTap", function() {});
// The above appeases Cmd's parser to associate spec run results with files.

(Ext.isIE10m ? xtopSuite : topSuite)("Ext.event.gesture.EdgeSwipe", function() {
    var helper = Ext.testHelper,
        recognizer = Ext.event.gesture.EdgeSwipe.instance,
        maxDuration = 130,
        halfDuration = 60,
        originalMaxDuration, targetEl, swipeHandler,
        edgeSwipeStartHandler, edgeSwipeEndHandler,  e;

    function start(cfg) {
        helper.touchStart(targetEl, cfg);
    }

    function move(cfg) {
        helper.touchMove(targetEl, cfg);
    }

    function end(cfg) {
        helper.touchEnd(targetEl, cfg);
    }

    beforeEach(function() {
        originalMaxDuration = recognizer.getMaxDuration();
        recognizer.setMaxDuration(maxDuration);

        targetEl = Ext.getBody().createChild({});
        swipeHandler = jasmine.createSpy();
        edgeSwipeStartHandler = jasmine.createSpy();
        edgeSwipeEndHandler = jasmine.createSpy();

        swipeHandler.andCallFake(function(event) {
            e = event;
        });

        targetEl.on('edgeSwipe', swipeHandler);
        targetEl.on('edgeswipestart', edgeSwipeStartHandler);
        targetEl.on('edgeswipeend', edgeSwipeEndHandler);
    });

    afterEach(function() {
        recognizer.setMaxDuration(originalMaxDuration);
        targetEl.destroy();
    });

    it("should fire edge swipe listeners", function() {
        runs(function() {
            start({ id: 1, x: 2, y: 10 });
        });

        wait(halfDuration);

        runs(function() {
            move({ id: 1, x: 100, y: 10 });
        });

        wait(maxDuration);

        runs(function() {
            end({ id: 1, x: 200, y: 10 });
        });

        waitsForAnimation();

        runs(function() {
            expect(swipeHandler).toHaveBeenCalled();
            expect(edgeSwipeStartHandler).toHaveBeenCalled();
            expect(edgeSwipeEndHandler).toHaveBeenCalled();
        });
    });

    it("should not fire when deltaX and deltaY are 0", function() {
        runs(function() {
            start({ id: 1, x: 10, y: 10 });
        });

        wait(halfDuration);

        runs(function() {
            end({ id: 1, x: 10, y: 10 });
        });

        waitsForAnimation();

        runs(function() {
            expect(swipeHandler).not.toHaveBeenCalled();
            expect(edgeSwipeStartHandler).not.toHaveBeenCalled();
            expect(edgeSwipeEndHandler).not.toHaveBeenCalled();
        });
    });
});
