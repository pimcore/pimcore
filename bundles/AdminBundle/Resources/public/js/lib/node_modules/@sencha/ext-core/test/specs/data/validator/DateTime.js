xdescribe("Ext.data.validator.DateTime", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.DateTime();

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

        it("should not validate if the value is not a valid date string with a time", function() {
            expect(validate('abc')).toBe(v.getMessage());
            expect(validate('23:00')).toBe(v.getMessage());
            expect(validate('1/1/1950 23:00 PM')).toBe(v.getMessage());
        });
    });

    describe("valid values", function() {
        it("should validate valid date and time strings", function() {
            expect(validate('1/1/1950 23:00')).toBe(true);
            expect(validate('1-1-1950 11:00')).toBe(true);
            expect(validate('1/1/1950 1:30 am')).toBe(true);
            expect(validate('1-1-1950 2:45 Pm')).toBe(true);
            expect(validate('1950-1-1 2:45 Pm')).toBe(true);
            expect(validate('1950/1/1 2:45 Pm')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.DateTime({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });

});
