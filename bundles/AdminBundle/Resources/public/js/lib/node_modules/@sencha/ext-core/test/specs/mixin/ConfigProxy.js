topSuite("Ext.mixin.ConfigProxy", function() {
    var bars, blerps, inners, inner2s, dohs, zips,
        Outer, Outer2, Derived, Derived2, Inner, GrandDerived, GrandDerived2;

    beforeAll(function() {
        // console.clear();

        Outer = Ext.define(null, {
            mixins: [
                'Ext.mixin.ConfigProxy'
            ],

            config: {
                inner: {
                    fluff: 42
                },

                inner2: {
                    doh: 427
                }
            },

            proxyConfig: {
                inner: [
                    'bar',
                    'zip'
                ],

                inner2: {
                    configs: [
                        'foo'
                    ],
                    methods: [
                        'zif'
                    ]
                }
            },

            constructor: function(config) {
                this.initConfig(config);
            },

            applyInner: function(config) {
                var c = this.mergeProxiedConfigs('inner', config);

                inners.push(c);

                return new Inner(c);
            },

            applyInner2: function(config) {
                inner2s.push(Ext.apply({}, config));

                return new Inner(config);
            }
        });

        Outer2 = Ext.define(null, {
            extend: Outer,

            proxyConfig: {
                inner2: [
                    'blerp'
                ]
            }
        });

        Derived = Ext.define(null, {
            extend: Outer,

            proxyConfig: {
                inner2: [
                    'doh'
                ]
            },

            inner: {
                // Ensure that we can override a child's configs even if they
                // are proxied configs (so long as they are not specified on
                // the outer level).
                bar: 'world'

                // Also, "fluff" should be preserved from Outer
            },

            inner2: {
                // This should replace the "doh" from Outer's version (and also
                // not lose to unspecified proxied config)
                doh: 'jkm'
            }
        });

        GrandDerived = Ext.define(null, {
            extend: Derived,

            inner: {
                derp: 'abc'
            },

            // This should replace the "abc" from Derived's inner2 config
            doh: 'xyz'
        });

        Derived2 = Ext.define(null, {
            extend: Outer,

            bar: 'woot',

            doh: '123'
        });

        GrandDerived2 = Ext.define(null, {
            extend: Derived2,

            zip: 'DERP',

            inner2: {
                doh: 'ray'
            }
        });

        Inner = Ext.define(null, {
            config: {
                bar: null,
                blerp: null,
                doh: null,
                zip: null
            },

            constructor: function(config) {
                this.initConfig(config);
            },

            applyBar: function(v) {
                bars.push(v);

                return v && v.toUpperCase();
            },

            applyBlerp: function(v) {
                blerps.push(v);

                return v && (v + '?');
            },

            applyDoh: function(v) {
                dohs.push(v);

                return '>' + v + '<';
            },

            applyZip: function(v) {
                zips.push(v);

                return v && v.toLowerCase();
            },

            zif: function(x) {
                return x + this.getZip();
            }
        });
    });

    beforeEach(function() {
        bars = [];
        blerps = [];
        dohs = [];
        inners = [];
        inner2s = [];
        zips = [];
    });

    describe('creation', function() {
        it('should not obstruct normal class configs', function() {
            var c = new Outer();

            // make sure the proxyConfig does not force in a "bar" config:
            expect(bars.length).toBe(0);

            expect(dohs).toEqual([427]);

            expect(inners).toEqual([
                { fluff: 42 }
            ]);

            expect(inner2s).toEqual([
                { doh: 427 }
            ]);
        });

        it('should not obstruct derived class configs', function() {
            var c = new Derived();

            expect(bars).toEqual(['world']);

            expect(inners).toEqual([
                { fluff: 42, bar: 'world' }
            ]);

            expect(dohs).toEqual(['jkm']);

            expect(c.getBar()).toBe('WORLD');
        });

        it('should not obstruct grand derived class configs', function() {
            var c = new GrandDerived();

            expect(bars.length).toBe(1);

            expect(inners).toEqual([
                { fluff: 42, bar: 'world', derp: 'abc' }
            ]);
            expect(inner2s).toEqual([
                { doh: 'jkm' }
            ]);

            expect(c.getBar()).toBe('WORLD');
            expect(c.getDoh()).toBe('>xyz<');
        });

        it('should proxy derived class configs', function() {
            var c = new Derived2();

            expect(bars.length).toBe(1);

            expect(inners).toEqual([
                { fluff: 42, bar: 'woot' }
            ]);

            expect(c.getBar()).toBe('WOOT');
        });

        it('should proxy grand derived class configs', function() {
            var c = new GrandDerived2();

            expect(bars).toEqual(['woot']);
            expect(zips).toEqual(['DERP']);

            expect(inners).toEqual([
                { fluff: 42, bar: 'woot', zip: 'DERP' }
            ]);

            expect(c.getBar()).toBe('WOOT');
            expect(c.getZip()).toBe('derp');
        });

        it("should push instanceConfig to child during creation", function() {
            var outer = new Outer({
                bar: 'hello'
            });

            var s = outer.getBar();

            expect(s).toBe('HELLO');
        });

        it("should call child setter once during creation", function() {
            var outer = new Outer({
                bar: 'hello'
            });

            expect(inners.length).toBe(1);
            expect(bars).toEqual(['hello']);
        });

        it('should allow proxyConfig on derived classes', function() {
            var o2 = new Outer2({
                blerp: 'what'
            });

            expect(blerps).toEqual([
                'what'
            ]);

            expect('proxyConfig' in o2).toBe(false);
        });
    }); // creation

    describe('methods', function() {
        it('should proxy methods', function() {
            var o = new Outer({
                inner2: {
                    zip: 'ABC'
                }
            });

            var z = o.zif('x');

            expect(z).toBe('xabc');
        });
    });
});
