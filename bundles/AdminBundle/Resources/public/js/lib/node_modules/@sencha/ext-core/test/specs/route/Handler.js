topSuite('Ext.route.Handler', function() {
    describe('fromRouteConfig', function() {
        it('should create a handler instance', function() {
            var scope = {},
                config = {
                    action: 'onAction',
                    before: 'onBefore',
                    exit: 'onExit',
                    lazy: true
                },
                handler = Ext.route.Handler.fromRouteConfig(config, scope);

            expect(handler.isInstance).toBe(true);
            expect(handler instanceof Ext.route.Handler).toBe(true);

            expect(handler.action).toBe('onAction');
            expect(handler.before).toBe('onBefore');
            expect(handler.lazy).toBe(true);
            expect(handler.exit).toBe('onExit');
            expect(handler.scope).toBe(scope);
        });
    });
});
