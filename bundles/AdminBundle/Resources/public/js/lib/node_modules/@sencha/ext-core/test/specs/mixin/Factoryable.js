topSuite("Ext.mixin.Factoryable", function() {
    beforeEach(function() {
        Ext.define('spec.factoryable.Base', {
            mixins: [
                'Ext.mixin.Factoryable'
            ],
            factoryConfig: {
                type: 'factoryable'
            },
            isTestBase: true
        });

        Ext.define('spec.factoryable.Class', {
            extend: 'spec.factoryable.Base',
            alias: 'factoryable.class'
        });

        Ext.define('spec.factoryable.Singleton', {
            extend: 'spec.factoryable.Base',
            alias: 'factoryable.singleton',
            singleton: true,

            constructor: function() {
                this.foo = 42;
            }
        });
    });

    afterEach(function() {
        Ext.undefine('spec.factoryable.Singleton');
        Ext.undefine('spec.factoryable.Class');
        Ext.undefine('spec.factoryable.Base');
    });

    describe("class creation", function() {
        it("should create the right class", function() {
            var obj = Ext.Factory.factoryable('class');

            expect(obj.isTestBase).toBe(true);
        });
    });

    describe("singletons", function() {
        it("should return the same singleton", function() {
            var obj = Ext.Factory.factoryable('singleton');

            expect(obj.isTestBase).toBe(true);
            expect(obj.foo).toBe(42);
            expect(obj).toBe(spec.factoryable.Singleton);
        });
    });
});
