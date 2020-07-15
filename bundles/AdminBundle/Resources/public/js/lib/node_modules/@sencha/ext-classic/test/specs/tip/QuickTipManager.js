topSuite("Ext.tip.QuickTipManager", function() {
    // QuickTips are very special and persistent; when unit testing we override
    // the Manager to init QuickTips as soon as it's loaded, and add it to the list
    // of ever-allowed components. For this bunch of tests we need to reverse that.
    beforeAll(function() {
        jasmine.removeAllowedComponent(Ext.QuickTips.tip, true);
        Ext.tip.QuickTipManager.destroy();
    });

    // And when these tests are finished we need to make sure QuickTips are back
    // so that other tests depending on them wouldn't fail.
    afterAll(function() {
        Ext.tip.QuickTipManager.destroy();
        Ext.tip.QuickTipManager.init();
        jasmine.addAllowedComponent(Ext.QuickTips.tip, true, true);
    });

    afterEach(function() {
        Ext.tip.QuickTipManager.destroy();
    });

    describe("init", function() {
        it("should create a new Ext.QuickTip instance", function() {
            expect(Ext.tip.QuickTipManager.getQuickTip()).toBe(null);
            Ext.tip.QuickTipManager.init();
            expect(Ext.tip.QuickTipManager.getQuickTip()).toBeDefined();
            expect(Ext.tip.QuickTipManager.getQuickTip() instanceof Ext.tip.QuickTip).toBeTruthy();
        });

        it("should automatically render by default", function() {
            Ext.tip.QuickTipManager.init();
            expect(Ext.tip.QuickTipManager.getQuickTip().rendered).toBeTruthy();
        });

        it("should automatically render if the 'autorender' param is true", function() {
            Ext.tip.QuickTipManager.init(true);
            expect(Ext.tip.QuickTipManager.getQuickTip().rendered).toBeTruthy();
        });

        it("should not automatically render if the 'autorender' param is false", function() {
            Ext.tip.QuickTipManager.init(false);
            expect(Ext.tip.QuickTipManager.getQuickTip().rendered).toBeFalsy();
        });
    });

    describe("destroy", function() {
        it("should destroy the QuickTip instance", function() {
            Ext.tip.QuickTipManager.init();
            Ext.tip.QuickTipManager.destroy();
            expect(Ext.tip.QuickTipManager.getQuickTip()).toBe(null);
        });
    });

    describe("disable", function() {
        it("should disable the QuickTip instance", function() {
            Ext.tip.QuickTipManager.init();
            Ext.tip.QuickTipManager.disable();
            expect(Ext.tip.QuickTipManager.getQuickTip().disabled).toBeTruthy();
        });
    });

    describe("ddDisable", function() {
        it("should disable the QuickTip instance", function() {
            Ext.tip.QuickTipManager.init();
            Ext.tip.QuickTipManager.ddDisable();
            expect(Ext.tip.QuickTipManager.getQuickTip().disabled).toBeTruthy();
        });
    });

    describe("enable", function() {
        it("should enable the QuickTip instance", function() {
            Ext.tip.QuickTipManager.init();
            Ext.tip.QuickTipManager.enable();
            expect(Ext.tip.QuickTipManager.getQuickTip().disabled).toBeFalsy();
        });
    });

    describe("ddEnable", function() {
        it("should enable the QuickTip instance", function() {
            Ext.tip.QuickTipManager.init();
            Ext.tip.QuickTipManager.ddEnable();
            expect(Ext.tip.QuickTipManager.getQuickTip().disabled).toBeFalsy();
        });
    });

    describe("isEnabled", function() {
        it("should enable the QuickTip instance", function() {
            Ext.tip.QuickTipManager.init();
            Ext.tip.QuickTipManager.enable();
            expect(Ext.tip.QuickTipManager.isEnabled()).toBeTruthy();
            Ext.tip.QuickTipManager.disable();
            expect(Ext.tip.QuickTipManager.isEnabled()).toBeFalsy();
        });
    });

    describe("register", function() {
        // this gets tested more thoroughly in QuickTip.js, here just ensure it calls through
        it("should call the QuickTip's register method", function() {
            Ext.tip.QuickTipManager.init();
            var spy = spyOn(Ext.tip.QuickTipManager.getQuickTip(), 'register'),
                arg = {};

            Ext.tip.QuickTipManager.register(arg);
            expect(spy).toHaveBeenCalledWith(arg);
        });
    });

    describe("unregister", function() {
        // this gets tested more thoroughly in QuickTip.js, here just ensure it calls through
        it("should call the QuickTip's unregister method", function() {
            Ext.tip.QuickTipManager.init();
            var spy = spyOn(Ext.tip.QuickTipManager.getQuickTip(), 'unregister'),
                arg = Ext.getBody();

            Ext.tip.QuickTipManager.unregister(arg);
            expect(spy).toHaveBeenCalledWith(arg);
        });
    });
});
