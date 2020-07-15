xdescribe("Ext.data.validator.Date", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.Date();

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

        it("should not validate if the value is not a valid date string", function() {
            expect(validate('abc')).toBe(v.getMessage());
            expect(validate('1/2/3')).toBe(v.getMessage());
            expect(validate('1-2-3')).toBe(v.getMessage());
        });
    });

    describe("valid values", function() {
        it("should validate valid date strings", function() {
            expect(validate('1/1/1900')).toBe(true);
            expect(validate('1-1-1900')).toBe(true);
            expect(validate('1900/1/1')).toBe(true);
            expect(validate('1900-1-1')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.Date({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });

});
