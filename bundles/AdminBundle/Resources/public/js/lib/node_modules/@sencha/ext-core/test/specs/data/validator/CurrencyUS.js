topSuite("Ext.data.validator.CurrencyUS", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.CurrencyUS();

        return v.validate(value);
    }

    afterEach(function() {
        v = null;
    });

    describe("invalid values", function() {
        it("should not validate if the value is undefined", function() {
            expect(validate(undefined)).toBe(v.getMessage());
        });

        it("should not validate if the value is null", function() {
            expect(validate(null)).toBe(v.getMessage());
        });

        it("should not validate if the value is an empty string", function() {
            expect(validate('')).toBe(v.getMessage());
        });

        it("should not validate if the value is not a valid currency value", function() {
            expect(validate('abc')).toBe(v.getMessage());
            expect(validate('$1,000,00.10')).toBe(v.getMessage());
            expect(validate('-$-1,000,000.1')).toBe(v.getMessage());
        });

    });

    describe("valid values", function() {
        it("should validate valid currency strings", function() {
            expect(validate('0')).toBe(true);
            expect(validate('$0')).toBe(true);
            expect(validate('-$1')).toBe(true);
            expect(validate('+$1')).toBe(true);
            expect(validate('$1,000')).toBe(true);
            expect(validate('$1,000,000')).toBe(true);
            expect(validate('$1,000,000.10')).toBe(true);
            expect(validate('$1,000,000.')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.CurrencyUS({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });
});
