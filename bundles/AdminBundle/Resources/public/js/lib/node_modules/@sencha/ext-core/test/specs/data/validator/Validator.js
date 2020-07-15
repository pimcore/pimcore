topSuite("Ext.data.validator.Validator", ['Ext.data.validator.*', 'Ext.data.Model'], function() {
    var v;

    afterEach(function() {
        v = null;
    });

    describe("construction", function() {
        it("should accept a function to be the validate method", function() {
            var fn = function() {};

            v = new Ext.data.validator.Validator(fn);
            expect(v.validate).toBe(fn);
        });
    });

    describe("validate", function() {
        it("should return true", function() {
            v = new Ext.data.validator.Validator();
            expect(v.validate()).toBe(true);
        });
    });

    describe("factory", function() {
        var factory = function(type, cfg) {
            if (cfg) {
                return Ext.data.validator.Validator.create(Ext.apply({
                    type: type
                }, cfg));
            }
            else {
                return Ext.Factory.validator(type);
            }
        },
            validator;

        it("should create a length validator", function() {
            validator = factory('length');
            expect(validator instanceof Ext.data.validator.Length).toBe(true);
        });
        it("should cache default length validators", function() {
            expect(factory('length')).toBe(validator);
        });

        it("should create a presence validator", function() {
            expect((validator = factory('presence')) instanceof Ext.data.validator.Presence).toBe(true);
        });
        it("should cache default presence validators", function() {
            expect(factory('presence')).toBe(validator);
        });

        it("should create an email validator", function() {
            expect((validator = factory('email')) instanceof Ext.data.validator.Email).toBe(true);
        });
        it("should cache default email validators", function() {
            expect(factory('email')).toBe(validator);
        });

        it("should create a CIDRv4 validator", function() {
            expect((validator = factory('cidrv4')) instanceof Ext.data.validator.CIDRv4).toBe(true);
        });
        it("should cache default CIDRv4 validators", function() {
            expect(factory('cidrv4')).toBe(validator);
        });

        it("should create a CIDRv6 validator", function() {
            expect((validator = factory('cidrv6')) instanceof Ext.data.validator.CIDRv6).toBe(true);
        });
        it("should cache default CIDRV6 validators", function() {
            expect(factory('cidrv6')).toBe(validator);
        });

        it("should create a Currency validator", function() {
            expect((validator = factory('currency-us')) instanceof Ext.data.validator.CurrencyUS).toBe(true);
        });
        it("should cache default Currency validators", function() {
            expect(factory('currency-us')).toBe(validator);
        });

        it("should create a Date validator", function() {
            expect((validator = factory('date')) instanceof Ext.data.validator.Date).toBe(true);
        });
        it("should cache default date validators", function() {
            expect(factory('date')).toBe(validator);
        });

        it("should create a DateTime validator", function() {
            expect((validator = factory('datetime')) instanceof Ext.data.validator.DateTime).toBe(true);
        });
        it("should cache default datetime validators", function() {
            expect(factory('datetime')).toBe(validator);
        });

        it("should create a IPAddress validator", function() {
            expect((validator = factory('ipaddress')) instanceof Ext.data.validator.IPAddress).toBe(true);
        });
        it("should cache default IPAddress validators", function() {
            expect(factory('ipaddress')).toBe(validator);
        });

        it("should create a Number validator", function() {
            expect((validator = factory('number')) instanceof Ext.data.validator.Number).toBe(true);
        });
        it("should cache default number validators", function() {
            expect(factory('number')).toBe(validator);
        });

        it("should create a Phone validator", function() {
            expect((validator = factory('phone')) instanceof Ext.data.validator.Phone).toBe(true);
        });
        it("should cache default phone validators", function() {
            expect(factory('phone')).toBe(validator);
        });

        it("should create a Time validator", function() {
            expect((validator = factory('time')) instanceof Ext.data.validator.Time).toBe(true);
        });
        it("should cache default time validators", function() {
            expect(factory('time')).toBe(validator);
        });

        it("should create a Url validator", function() {
            expect((validator = factory('url')) instanceof Ext.data.validator.Url).toBe(true);
        });
        it("should cache default url validators", function() {
            expect(factory('url')).toBe(validator);
        });

        it("should create a range validator", function() {
            expect(factory('range') instanceof Ext.data.validator.Range).toBe(true);
        });

        it("should create a format validator", function() {
            expect(factory('format', {
                matcher: /foo/
            }) instanceof Ext.data.validator.Format).toBe(true);
        });

        it("should create an inclusion validator", function() {
            expect(factory('inclusion', {
                list: []
            }) instanceof Ext.data.validator.Inclusion).toBe(true);
        });

        it("should create an exclusion validator", function() {
            expect(factory('exclusion', {
                list: []
            }) instanceof Ext.data.validator.Exclusion).toBe(true);
        });

        it("should default to base", function() {
            expect(factory('') instanceof Ext.data.validator.Validator).toBe(true);
        });
    });

    describe("custom validator", function() {
        var validator;

        beforeEach(function() {
            Ext.define('Ext.data.validator.Custom', {
                extend: 'Ext.data.validator.Validator',
                alias: 'data.validator.custom'
            });

            validator = Ext.data.validator.Validator.create({
                type: 'custom'
            });
        });

        afterEach(function() {
            validator.destroy();
            Ext.undefine('Ext.data.validator.Custom');
            Ext.Factory.dataValidator.instance.clearCache();
        });

        it("should be able to create a custom validator", function() {
            expect(validator instanceof Ext.data.validator.Custom).toBe(true);
            expect(validator instanceof Ext.data.validator.Validator).toBe(true);
        });

        it("should pass value and record to Validator validate method", function() {
            spyOn(validator, 'validate').andCallThrough();

            var Model = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['test'],
                validators: {
                    test: validator
                }
            }),
            record = new Model({
                test: 'Foo'
            });

            record.isValid();

            expect(validator.validate).toHaveBeenCalled();
            expect(validator.validate).toHaveBeenCalledWith('Foo', record);
        });
    });
});
