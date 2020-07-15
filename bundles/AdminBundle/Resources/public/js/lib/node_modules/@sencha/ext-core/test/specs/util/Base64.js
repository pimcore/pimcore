topSuite("Ext.util.Base64", function() {
    // https://www.base64encode.org was used as the reference for encoding
    var tests = [{
        name: 'lowercase Latin characters',
        plain: 'abcdefghijklmnopqrstuvwxyz',
        encoded: 'YWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXo='
    }, {
        name: 'uppercase Latin characters',
        plain: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        encoded: 'QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVo='
    }, {
        name: 'numbers',
        plain: '0123456789',
        encoded: 'MDEyMzQ1Njc4OQ=='
    }, {
        name: 'punctuation characters',
        plain: '~-=+?.,<>[]{}!@#$%^&*()\'"',
        encoded: 'fi09Kz8uLDw+W117fSFAIyQlXiYqKCknIg=='
    }, {
        name: 'Western Latin characters',
        plain: 'ēūīļķģšāžčņĒŪĪĻĶĢŠĀŽČŅ',
        encoded: 'xJPFq8SrxLzEt8SjxaHEgcW+xI3FhsSSxarEqsS7xLbEosWgxIDFvcSMxYU='
    }, {
        name: 'Cyrillic characters',
        plain: 'яшертыуиопюжэасдфгчйклзхцвбнмщьъ',
        encoded: '0Y/RiNC10YDRgtGL0YPQuNC+0L/RjtC20Y3QsNGB0LTRhNCz0YfQudC60LvQt9GF0YbQstCx0L3QvNGJ0YzRig=='
    }, {
        name: 'Traditional Chinese characters',
        plain: '每一個獵人想知道哪裡坐野雞',
        encoded: '5q+P5LiA5YCL54215Lq65oOz55+l6YGT5ZOq6KOh5Z2Q6YeO6Zue'
    }, {
        name: 'Simplified Chinese characters',
        plain: '每一个猎人想知道哪里坐野鸡',
        encoded: '5q+P5LiA5Liq54yO5Lq65oOz55+l6YGT5ZOq6YeM5Z2Q6YeO6bih'
    }, {
        name: 'Japanese characters',
        plain: 'すべてのハンターはキジが座る場所を知りたいです',
        encoded: '44GZ44G544Gm44Gu44OP44Oz44K/44O844Gv44Kt44K444GM5bqn44KL5aC05omA44KS55+l44KK44Gf44GE44Gn44GZ'
    }];

    describe("encode", function() {
        for (var i = 0; i < tests.length; i++) {
            var test = tests[i];

            (function(name, input, want) {
                it('should encode ' + name, function() {
                    var have = Ext.util.Base64.encode(input);

                    expect(have).toBe(want);
                });
            })(test.name, test.plain, test.encoded);
        }
    });

    describe("decode", function() {
        for (var i = 0; i < tests.length; i++) {
            var test = tests[i];

            (function(name, input, want) {
                it('should decode ' + name, function() {
                    var have = Ext.util.Base64.decode(input);

                    expect(have).toBe(want);
                });
            })(test.name, test.encoded, test.plain);
        }
    });
});
