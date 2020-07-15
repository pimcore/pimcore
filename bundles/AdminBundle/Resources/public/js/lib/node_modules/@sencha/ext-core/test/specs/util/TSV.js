topSuite("Ext.util.TSV", function() {
    var TSV = Ext.util.TSV;

    // The "hostile" string is a single cell that has all of the special characters in
    // its value.
    var hostile = 'foo "bar"\t, \n\r\nbletch';

    // This is the encoded version of the above.
    var hostileEnc = '"foo ""bar""\t, \n\r\nbletch"';

    describe("encode", function() {
        it("should encode valid data types to TSV representation", function() {
            // Set the reference date to be an absolute time value so that tests will
            // run in any time zone.
            // This is Friday, January 1, 2010, 21:45:32.004 UTC
            // Around the world where tests may be run, the default toString
            // rendition of this may change, so testers beware.
            var date = new Date(1262382332004),
                result = TSV.encode([
                    [ hostile, 'Normal String', date ],
                    [ Math.PI, 1, false ]
                ]);

            // Test all valid types:
            //      String with quotes,    string,         Date,
            //      floating point number, integer number, boolean
            expect(result).toEqual(
                    hostileEnc + '\tNormal String\t2010-01-01T21:45:32.004Z' +
                    TSV.lineBreak +
                    '3.141592653589793\t1\tfalse');
        });

        it('should handle empty rows', function() {
            expect(TSV.encode([[]])).toBe('');
        });

        it('should handle null cell', function() {
            expect(TSV.encode([[null]])).toBe('');
        });

        it("should not encode arrays in cells", function() {
            expect(function() {
                TSV.encode([[[]]]);
            }).toThrow();
        });

        it("should not encode objects in cells", function() {
            expect(function() {
                TSV.encode([[{}]]);
            }).toThrow();
        });

        it("should not encode HTMLDocument in a cell", function() {
            expect(function() {
                TSV.encode([[document]]);
            }).toThrow();
        });

        it("should not encode HTMLBody in a cell", function() {
            expect(function() {
                TSV.encode([[document.body]]);
            }).toThrow();
        });

        it("should not encode NodeList in a cell", function() {
            expect(function() {
                TSV.encode([[document.body.childNodes]]);
            }).toThrow();
        });

        it("should not encode window in a cell", function() {
            expect(function() {
                TSV.encode([[Ext.global]]);
            }).toThrow();
        });

        it("should allow overriding quote char via arguments", function() {
            var result = TSV.encode(
                [['3', 'Deal with "High" priority items', '23 days', '10/31/2017 9:00']],
                undefined, null
            );

            expect(result).toBe('3\tDeal with "High" priority items\t' +
                                '23 days\t10/31/2017 9:00');
        });

        it("should allow overriding quote char via config", function() {
            var TSV = new Ext.util.TsvDecoder({
                quote: null
            });

            var result = TSV.encode(
                [['3', 'Deal with "High" priority items', '23 days', '10/31/2017 9:00']]
            );

            expect(result).toBe('3\tDeal with "High" priority items\t' +
                                '23 days\t10/31/2017 9:00');
        });
    });

    describe("decode", function() {
        it('should decode TSV with hostile string in the middle', function() {
            var result = TSV.decode('Normal String\t' +
                    hostileEnc + '\t2010-01-01T21:45:32.004Z' +
                    TSV.lineBreak +
                    '3.141592653589793\t1\tfalse');

            expect(result).toEqual([
                [ 'Normal String', hostile, '2010-01-01T21:45:32.004Z' ],
                [ '3.141592653589793', '1', 'false' ]
            ]);
        });

        it('should decode TSV back into an array of string arrays', function() {
            var result = TSV.decode(
                    hostileEnc + '\tNormal String\t2010-01-01T21:45:32.004Z' +
                    TSV.lineBreak +
                    '3.141592653589793\t1\tfalse');

            expect(result).toEqual([
                [ hostile, 'Normal String', '2010-01-01T21:45:32.004Z' ],
                [ '3.141592653589793', '1', 'false' ]
            ]);
        });

        it("should decote quotes by default", function() {
            var TSV = new Ext.util.TsvDecoder();

            var result = TSV.decode('3\tDeal with "High" priority items\t' +
                                    '23 days\t10/31/2017 9:00\n');

            expect(result[0]).toEqual(
                ['3', 'Deal with "High" priority items', '23 days', '10/31/2017 9:00']
            );
        });

        it("should should support custom quoted format via config", function() {
            var TSV = new Ext.util.TsvDecoder({
                quote: "'"
            });

            var result = TSV.decode("3\tDeal with 'High' priority items\t" +
                                    "23 days\t10/31/2017 9:00\n");

            expect(result[0]).toEqual(
                ['3', 'Deal with \'High\' priority items', '23 days', '10/31/2017 9:00']
            );
        });

        it("should should support custom quotes via argument", function() {
            var result = TSV.decode("3\tDeal with 'High' priority items\t" +
                                    "23 days\t10/31/2017 9:00\n", undefined, "'");

            expect(result[0]).toEqual(
                ["3", "Deal with 'High' priority items", "23 days", "10/31/2017 9:00"]
            );
        });

        it("should return an empty array for null, undefined and empty string", function() {
            expect(TSV.decode(undefined)).toEqual([]);
            expect(TSV.decode(null)).toEqual([]);
            expect(TSV.decode('')).toEqual([]);
        });

        it("should not create an empty row when a line feed is the last character in the input", function() {
            var test1 = 'John\tDoe\t42' + TSV.lineBreak + 'Jane\tHenry\t31' + TSV.lineBreak + '\t\t\r\n',
                test2 = 'John\tDoe\t42' + TSV.lineBreak + '\t\t' + TSV.lineBreak + 'Jane\tHenry\t31\n',
                test3 = 'John\tDoe\t42\r';

            // two rows of data, one empty row with \r\n end variant
            expect(TSV.decode(test1)).toEqual([
                ['John', 'Doe', '42'],
                ['Jane', 'Henry', '31'],
                ['', '', ''],
                ['']
            ]);

            // one row of data, one empty row, another row of data with \n end variant
            expect(TSV.decode(test2)).toEqual([
                ['John', 'Doe', '42'],
                ['', '', ''],
                ['Jane', 'Henry', '31'],
                ['']
            ]);

            // just one row of data with \r end variant
            expect(TSV.decode(test3)).toEqual([['John', 'Doe', '42'], ['']]);
        });
    });
});
