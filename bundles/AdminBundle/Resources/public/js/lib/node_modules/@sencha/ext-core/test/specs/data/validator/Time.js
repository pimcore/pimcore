xdescribe("Ext.data.validator.Time", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.Time();

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

        it("should not validate if the value is not a valid time string", function() {
            expect(validate('abc')).toBe(v.getMessage());
            expect(validate('33:00')).toBe(v.getMessage());
            expect(validate('23:00 PM')).toBe(v.getMessage());
        });
    });

    describe("valid values", function() {
        it("should validate valid time strings", function() {
            expect(validate('23:00')).toBe(true);
            expect(validate('11:00')).toBe(true);
            expect(validate('1:30 am')).toBe(true);
            expect(validate('2:45 Pm')).toBe(true);
            expect(validate('2:45 pM')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.Time({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });

});
