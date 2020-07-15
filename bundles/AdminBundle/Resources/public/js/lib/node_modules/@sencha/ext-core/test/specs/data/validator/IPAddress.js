topSuite("Ext.data.validator.IPAddress", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.IPAddress();

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

        it("should not validate if the value is not a valid IP address", function() {
            expect(validate('300.168.0.10')).toBe(v.getMessage());
            expect(validate('192.168.300')).toBe(v.getMessage());
        });

    });

    describe("valid values", function() {
        it("should validate valid IP addresses", function() {
            expect(validate('192.168.0.1')).toBe(true);
            expect(validate('2001:558:1418:d::1')).toBe(true);
            expect(validate('2001:558:3da:9::1')).toBe(true);
            expect(validate('2001:0000:3238:DFE1:0063:0000:0000:FEFB')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.IPAddress({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });

});
