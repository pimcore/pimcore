topSuite('Ext.mixin.Mashup', function() {
    var oldLoadScripts = Ext.Loader.loadScripts,
        mashup;

    beforeEach(function() {
        mashup = {};

        Ext.manifest = {
            mashup: mashup
        };
    });

    afterEach(function() {
        Ext.Loader.loadScripts = oldLoadScripts;

        Ext.undefine('MyTest');

        mashup = Ext.manifest = null;
    });

    it('should load script', function() {
        var spy = spyOn({
            test: Ext.emptyFn
        }, 'test');

        Ext.Loader.loadScripts = function(options) {
            var url = options.url;

            spy.call(this, options);

            expect(url.length).toBe(1);

            expect(url).toEqual([
                '//example.com/foo'
            ]);

            options.onLoad();
        };

        Ext.define('MyTest', {
            mixins: [
                'Ext.mixin.Mashup'
            ],

            requiredScripts: [
                '//example.com/foo'
            ]
        });

        expect(spy).toHaveBeenCalled();
    });

    it('should replace options', function() {
        var spy = spyOn({
            test: Ext.emptyFn
        }, 'test');

        Ext.Loader.loadScripts = function(options) {
            var url = options.url;

            spy.call(this, options);

            expect(url.length).toBe(1);

            expect(url).toEqual([
                '//example.com/foo?some_options'
            ]);

            options.onLoad();
        };

        mashup.test = {
            options: '?some_options'
        };

        Ext.define('MyTest', {
            xtypes: [
                'test'
            ],

            mixins: [
                'Ext.mixin.Mashup'
            ],

            requiredScripts: [
                '//example.com/foo{options}'
            ]
        });

        expect(spy).toHaveBeenCalled();
    });

    describe('redirect', function() {
        it('should replace script', function() {
            var spy = spyOn({
                test: Ext.emptyFn
            }, 'test');

            Ext.Loader.loadScripts = function(options) {
                var url = options.url;

                spy.call(this, options);

                expect(url.length).toBe(2);

                expect(url).toEqual([
                    'https://example.com/foo',
                    '//example.com/bar'
                ]);

                options.onLoad();
            };

            mashup.redirect = {
                '//example.com/foo': 'https://example.com/foo'
            };

            Ext.define('MyTest', {
                mixins: [
                    'Ext.mixin.Mashup'
                ],

                requiredScripts: [
                    '//example.com/foo',
                    '//example.com/bar'
                ]
            });

            expect(spy).toHaveBeenCalled();
        });

        it('should skip loading script', function() {
            var spy = spyOn(Ext.Loader, 'loadScripts');

            mashup.redirect = {
                '//example.com/foo': false
            };

            Ext.define('MyTest', {
                mixins: [
                    'Ext.mixin.Mashup'
                ],

                requiredScripts: [
                    '//example.com/foo'
                ]
            });

            expect(spy).not.toHaveBeenCalled();
        });

        it('should skip loading script but load other', function() {
            var spy = spyOn({
                test: Ext.emptyFn
            }, 'test');

            Ext.Loader.loadScripts = function(options) {
                var url = options.url;

                spy.call(this, options);

                expect(url.length).toBe(1);

                expect(url).toEqual([
                    '//example.com/bar'
                ]);

                options.onLoad();
            };

            mashup.redirect = {
                '//example.com/foo': false
            };

            Ext.define('MyTest', {
                mixins: [
                    'Ext.mixin.Mashup'
                ],

                requiredScripts: [
                    '//example.com/foo',
                    '//example.com/bar'
                ]
            });

            expect(spy).toHaveBeenCalled();
        });

        it('should replace options still', function() {
            var spy = spyOn({
                test: Ext.emptyFn
            }, 'test');

            Ext.Loader.loadScripts = function(options) {
                var url = options.url;

                spy.call(this, options);

                expect(url.length).toBe(1);

                expect(url).toEqual([
                    'https://example.com/bar?foobar'
                ]);

                options.onLoad();
            };

            mashup.redirect = {
                '//example.com/foo': false,
                '//example.com/bar{options}': 'https://example.com/bar{options}'
            };
            mashup.test = {
                options: '?foobar'
            };

            Ext.define('MyTest', {
                xtypes: [
                    'test'
                ],

                mixins: [
                    'Ext.mixin.Mashup'
                ],

                requiredScripts: [
                    '//example.com/foo',
                    '//example.com/bar{options}'
                ]
            });

            expect(spy).toHaveBeenCalled();
        });
    });
});
