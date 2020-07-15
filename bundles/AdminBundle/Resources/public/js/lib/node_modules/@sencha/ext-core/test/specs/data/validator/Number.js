topSuite("Ext.data.validator.Number", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.Number();

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

        it("should not validate if the value is has alpha characters", function() {
            expect(validate('abc')).toBe(v.getMessage());
        });

        it("should not validate if the thousand separator isn't used correctly on the lower end of the number", function() {
            expect(validate('1,000,00.10')).toBe(v.getMessage());
        });

        it("should not validate if there are multiple negative symbols", function() {
            expect(validate('--100')).toBe(v.getMessage());
        });

        it("should not validate with both + and - symbols", function() {
            expect(validate('-+100')).toBe(v.getMessage());
            expect(validate('+-100')).toBe(v.getMessage());
        });

        it("should not validate with multiple decimal points", function() {
            expect(validate('1.2.3')).toBe(v.getMessage());
        });

    });

    describe("valid values", function() {
        it("should validate valid number strings", function() {
            expect(validate('0')).toBe(true);
            expect(validate('-1')).toBe(true);
            expect(validate('+1')).toBe(true);
            expect(validate('1,000')).toBe(true);
            expect(validate('1,000,000')).toBe(true);
            expect(validate('1,000,000.10')).toBe(true);
            expect(validate('1,000,000.1001010101')).toBe(true);
            expect(validate('.1001010101')).toBe(true);
            expect(validate('-.1001010101')).toBe(true);
            expect(validate('+.1001010101')).toBe(true);
            expect(validate('0.1001010101')).toBe(true);
            expect(validate('-0.1001010101')).toBe(true);
            expect(validate('+0.1001010101')).toBe(true);
            expect(validate('1.')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.Number({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });
});
