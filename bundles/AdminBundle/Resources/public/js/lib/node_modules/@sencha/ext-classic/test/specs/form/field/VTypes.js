topSuite("Ext.form.field.VTypes", function() {
    var VTYPES = Ext.form.field.VTypes;

    describe('Ext.form.field.VTypes.url()', function() {

        it("should return TRUE for properly formatted URLs", function() {

            // missing WWW
            expect(VTYPES.url('http://sencha.com')).toEqual(true);
            expect(VTYPES.url('https://sencha.com')).toEqual(true);

            // trailing slash
            expect(VTYPES.url('http://sencha.com/')).toEqual(true);
            expect(VTYPES.url('https://sencha.com/')).toEqual(true);

            // with WWW
            expect(VTYPES.url('http://www.sencha.com')).toEqual(true);
            expect(VTYPES.url('https://www.sencha.com')).toEqual(true);

            // trailing slash
            expect(VTYPES.url('http://www.sencha.com/')).toEqual(true);
            expect(VTYPES.url('https://www.sencha.com/')).toEqual(true);

            // missing WWW, multi-TLD
            expect(VTYPES.url('http://sencha.com.au')).toEqual(true);
            expect(VTYPES.url('https://sencha.com.au')).toEqual(true);

            // trailing slash, multi-TLD
            expect(VTYPES.url('http://sencha.com.au/')).toEqual(true);
            expect(VTYPES.url('https://sencha.com.au/')).toEqual(true);

            // with WWW, multi-TLD
            expect(VTYPES.url('http://www.sencha.com.au')).toEqual(true);
            expect(VTYPES.url('https://www.sencha.com.au')).toEqual(true);

            // trailing slash, multi-TLD
            expect(VTYPES.url('http://www.sencha.com.au/')).toEqual(true);
            expect(VTYPES.url('https://www.sencha.com.au/')).toEqual(true);

            // GET params
            expect(VTYPES.url('http://www.sencha.com?foo=bar')).toEqual(true);
            expect(VTYPES.url('https://www.sencha.com?foo=bar')).toEqual(true);

            // trailing slash, GET params
            expect(VTYPES.url('http://www.sencha.com/?foo=bar')).toEqual(true);
            expect(VTYPES.url('https://www.sencha.com/?foo=bar')).toEqual(true);

        });

        it("should return FALSE for improperly formatted URLs", function() {

            // domains should have at least a 2 letter TLD
            // missing WWW
            expect(VTYPES.url('http://a.a')).toEqual(false);
            expect(VTYPES.url('https://a.a')).toEqual(false);

            // domains should have at least a 2 letter TLD
            // with WWW
            expect(VTYPES.url('http://www.a.a')).toEqual(false);
            expect(VTYPES.url('https://www.a.a')).toEqual(false);

            // nonsense url
            expect(VTYPES.url('http://foobar')).toEqual(false);
            expect(VTYPES.url('https://foobar')).toEqual(false);

            // trailing slash
            expect(VTYPES.url('http://foobar/')).toEqual(false);
            expect(VTYPES.url('https://foobar/')).toEqual(false);

        });

        it("should return TRUE for localhost URLs", function() {

            // normal localhost
            expect(VTYPES.url('http://localhost')).toEqual(true);
            expect(VTYPES.url('https://localhost')).toEqual(true);

            // trailing slash
            expect(VTYPES.url('http://localhost/')).toEqual(true);
            expect(VTYPES.url('https://localhost/')).toEqual(true);

            // GET params
            expect(VTYPES.url('http://localhost?foo=bar')).toEqual(true);
            expect(VTYPES.url('https://localhost?foo=bar')).toEqual(true);

            // trailing slash, GET params
            expect(VTYPES.url('http://localhost/?foo=bar')).toEqual(true);
            expect(VTYPES.url('https://localhost/?foo=bar')).toEqual(true);

            // CAPITAL LOCALHOST
            // normal localhost
            expect(VTYPES.url('http://LOCALHOST')).toEqual(true);
            expect(VTYPES.url('https://LOCALHOST')).toEqual(true);

            // trailing slash
            expect(VTYPES.url('http://LOCALHOST/')).toEqual(true);
            expect(VTYPES.url('https://LOCALHOST/')).toEqual(true);

            // GET params
            expect(VTYPES.url('http://LOCALHOST?foo=bar')).toEqual(true);
            expect(VTYPES.url('https://LOCALHOST?foo=bar')).toEqual(true);

            // trailing slash, GET params
            expect(VTYPES.url('http://LOCALHOST/?foo=bar')).toEqual(true);
            expect(VTYPES.url('https://LOCALHOST/?foo=bar')).toEqual(true);

        });

    });

    describe('Ext.form.field.VTypes.email()', function() {
        describe('local-part of email address', function() {
            it("should allow for alpha characters", function() {
                expect(VTYPES.email('abcdefghijklmnopqrstuvwxyz@extjs.com')).toEqual(true);
            });

            it("should allow for numeric characters", function() {
                expect(VTYPES.email('0123456789@extjs.com')).toEqual(true);
            });

            it("should allow for alphanumeric characters", function() {
                expect(VTYPES.email('0a1b2c3d4e5f6g7h9i@extjs.com')).toEqual(true);
            });

            it("should allow for a mix of alphanumeric and special chars", function() {
                expect(VTYPES.email('"baba_o\'reilly.1.-who4?"@extjs.com')).toEqual(true);
            });

            it("should allow for special characters at the beginning", function() {
                expect(VTYPES.email('!dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('#dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('$dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('%dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('&dev@extjs.com')).toEqual(true);
                expect(VTYPES.email("'dev@extjs.com")).toEqual(true);
                expect(VTYPES.email('*dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('+dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('/dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('=dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('?dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('^dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('_dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('`dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('{dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('|dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('}dev@extjs.com')).toEqual(true);
                expect(VTYPES.email('~dev@extjs.com')).toEqual(true);
            });

            it("should allow for special characters at the end", function() {
                expect(VTYPES.email('dev!@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev#@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev$@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev%@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev&@extjs.com')).toEqual(true);
                expect(VTYPES.email("dev'@extjs.com")).toEqual(true);
                expect(VTYPES.email('dev*@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev+@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev/@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev=@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev?@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev^@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev_@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev`@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev{@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev|@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev}@extjs.com')).toEqual(true);
                expect(VTYPES.email('dev~@extjs.com')).toEqual(true);
            });

            it("should allow for special characters mixed within the body", function() {
                expect(VTYPES.email("!d#e$v%e&l'o*p+e/r=@extjs.com")).toEqual(true);
                expect(VTYPES.email("?d^e_v`e{l|o}p~er@extjs.com")).toEqual(true);
            });

            it("should allow for repeated special characters mixed within the body", function() {
                expect(VTYPES.email("!d####e$v%%e&l'''o*p+e////r@extjs.com")).toEqual(true);
                expect(VTYPES.email("?d^^^^e_v`e{l|||||||o}}}p~er@extjs.com")).toEqual(true);
            });

            it("should allow for periods anywhere within the body", function() {
                expect(VTYPES.email('d.e.v.e.l.o.p.e.r@extjs.com')).toEqual(true);
            });

            it("should not allow for a period at the beginning", function() {
                expect(VTYPES.email('.d.e.v.e.l.o.p.e.r@extjs.com')).toEqual(false);
                expect(VTYPES.email('.dev@extjs.com')).toEqual(false);
            });

            it("should not allow for a period at the end", function() {
                expect(VTYPES.email('d.e.v.e.l.o.p.e.r.@extjs.com')).toEqual(false);
                expect(VTYPES.email('dev.@extjs.com')).toEqual(false);
            });

            it("should not allow for more than one period in a row", function() {
                expect(VTYPES.email('de..v@extjs.com')).toEqual(false);
                expect(VTYPES.email('d...e....v@extjs.com')).toEqual(false);
            });

            it("should allow for it to be wrapped by double quotes", function() {
                expect(VTYPES.email('"dev"@extjs.com')).toEqual(true);
            });

            it("should not allow a single white space at the beginning", function() {
                expect(VTYPES.email(' dev@extjs.com')).toEqual(false);
            });

            it("should not allow multiple white spaces at the beginning", function() {
                expect(VTYPES.email('     dev@extjs.com')).toEqual(false);
            });

            it("should not allow for a single double quote at the beginning", function() {
                expect(VTYPES.email('"dev@extjs.com')).toEqual(false);
            });

            it("should not allow for a single double quote at the end", function() {
                expect(VTYPES.email('dev"@extjs.com')).toEqual(false);
            });

            it("should allow for it to contain special chars and to be wrapped by double quotes", function() {
                expect(VTYPES.email('"baba_o\'reilly-who?"@extjs.com')).toEqual(true);
            });

            it("should validate the examples in the docs", function() {
                expect(VTYPES.email('barney@example.de')).toEqual(true);
                expect(VTYPES.email('barney.rubble@example.com')).toEqual(true);
                expect(VTYPES.email('barney-rubble@example.coop')).toEqual(true);
                expect(VTYPES.email('barney+rubble@example.com')).toEqual(true);
                expect(VTYPES.email('barney\'rubble@example.com')).toEqual(true);
                expect(VTYPES.email('b.arne.y_r.ubbl.e@example.com')).toEqual(true);
                expect(VTYPES.email('barney4rubble@example.com')).toEqual(true);
                expect(VTYPES.email('barney4rubble!@example.com')).toEqual(true);
                expect(VTYPES.email('_barney+rubble@example.com')).toEqual(true);
                expect(VTYPES.email('"barney+rubble"@example.com')).toEqual(true);
            });
        });
    });

});
