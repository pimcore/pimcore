topSuite("Ext.data.validator.CIDRv6", function() {
    var v;

    function validate(value) {
        v = new Ext.data.validator.CIDRv6();

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
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/')).toBe(v.getMessage());
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/129')).toBe(v.getMessage());
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/a')).toBe(v.getMessage());
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/√')).toBe(v.getMessage());
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/00')).toBe(v.getMessage());
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/03')).toBe(v.getMessage());
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/sdfsdfs')).toBe(v.getMessage());
        });

    });

    describe("valid values", function() {
        it("should validate ipv6 CIDR", function() {
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/0')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/1')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/2')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/3')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/5')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/6')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/7')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/8')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/9')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/11')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/12')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/13')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/14')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/15')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/16')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/17')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/18')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/19')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/20')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/21')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/22')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/23')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/24')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/25')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/26')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/27')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/28')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/29')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/30')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/31')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/32')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/33')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/34')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/35')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/36')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/37')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/38')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/39')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/40')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/41')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/42')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/43')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/44')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/45')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/46')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/47')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/48')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/49')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/50')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/51')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/52')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/53')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/54')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/55')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/56')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/57')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/58')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/59')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/60')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/61')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/62')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/63')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/64')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/65')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/66')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/67')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/68')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/69')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/70')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/71')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/72')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/73')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/74')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/75')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/76')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/77')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/78')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/79')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/80')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/81')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/82')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/83')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/84')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/85')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/86')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/87')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/88')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/89')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/90')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/91')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/92')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/93')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/94')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/95')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/96')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/97')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/98')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/99')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/100')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/101')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/102')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/103')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/104')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/105')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/106')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/107')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/108')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/109')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/110')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/111')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/112')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/113')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/114')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/115')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/116')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/117')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/118')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/119')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/120')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/121')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/122')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/123')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/124')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/125')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/126')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/127')).toBe(true);
            expect(validate('fe80:0000:0000:0000:0204:61ff:fe9d:f156/128')).toBe(true);
        });
    });

    describe("messages", function() {
        it("should accept a custom message", function() {
            v = new Ext.data.validator.CIDRv6({
                message: 'Foo'
            });
            expect(v.validate(undefined)).toBe('Foo');
        });
    });

});
