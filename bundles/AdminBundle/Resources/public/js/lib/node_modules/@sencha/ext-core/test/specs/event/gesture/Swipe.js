// describe("Ext.event.gesture.DoubleTap", function() {});
// The above appeases Cmd's parser to associate spec run results with files.

(Ext.isIE10m ? xtopSuite : topSuite)("Ext.event.gesture.Swipe", function() {
    var helper = Ext.testHelper,
        recognizer = Ext.event.gesture.Swipe.instance,
        maxDuration = 130,
        halfDuration = 60,
        originalMaxDuration, targetEl, swipeHandler, e;

    function start(cfg) {
        helper.touchStart(targetEl, cfg);
    }

    function move(cfg) {
        helper.touchMove(targetEl, cfg);
    }

    function end(cfg) {
        helper.touchEnd(targetEl, cfg);
    }

    function cancel(cfg) {
        helper.touchCancel(targetEl, cfg);
    }

    beforeEach(function() {
        originalMaxDuration = recognizer.getMaxDuration();
        recognizer.setMaxDuration(maxDuration);

        targetEl = Ext.getBody().createChild({});
        swipeHandler = jasmine.createSpy();

        swipeHandler.andCallFake(function(event) {
            e = event;
        });

        targetEl.on('swipe', swipeHandler);
    });

    afterEach(function() {
        recognizer.setMaxDuration(originalMaxDuration);
        targetEl.destroy();
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
        });
    });
});
