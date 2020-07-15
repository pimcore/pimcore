topSuite("Ext.data.validator.CIDRv4", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.CIDRv4();

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

        it("should not validate if the value is not a valid CIDR block", function() {
            expect(validate('192.168.0')).toBe(v.getMessage());
        });

    });

    describe("valid values", function() {
        it("should validate a CIDR block", function() {
            expect(validate('192.168.0.1/8')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.CIDRv4({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });

});
