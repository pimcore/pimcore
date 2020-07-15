topSuite("Ext.data.validator.Url", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.Url();

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
            expect(validate('http://')).toBe(v.getMessage());
        });
    });

    describe("valid values", function() {
        it("should validate valid URL strings", function() {
            expect(validate('http://foo.com')).toBe(true);
            expect(validate('https://foo.com')).toBe(true);
            expect(validate('http://foo.com/path')).toBe(true);
            expect(validate('http://foo.com/path?query=string')).toBe(true);
            expect(validate('http://foo.com/path?query=string#anchor')).toBe(true);
            expect(validate('http://user:password@foo.com/path')).toBe(true);
            expect(validate('ftp://foo.com')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.Url({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });

});
