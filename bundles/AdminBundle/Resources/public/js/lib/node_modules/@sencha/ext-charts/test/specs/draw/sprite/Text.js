topSuite("Ext.draw.sprite.Text", function() {
    var proto = Ext.draw.sprite.Text.prototype;

    describe('makeFontShorthand', function() {
        var attr = {
            fontVariant: 'small-caps',
            fontStyle: 'italic',
            fontWeight: 'bold',
            fontSize: '34px/100px',
            fontFamily: '"Times New Roman", serif'
        };

        it('should not have a leading or trailing space', function() {
            var font = '';

            var fakeSprite = {
                setAttributes: function(attr) {
                    font = attr.font;
                }
            };

            proto.makeFontShorthand.call(fakeSprite, attr);
            expect(font.length).toEqual(Ext.String.trim(font).length);
        });

        it('should list all available values in the preferred order', function() {
            var font;

            var fakeSprite = {
                setAttributes: function(attr) {
                    font = attr.font;
                }
            };

            proto.makeFontShorthand.call(fakeSprite, attr);
            expect(font).toEqual('italic small-caps bold 34px/100px "Times New Roman", serif');
        });

        it('needs to contain at least font-size and font-family', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                fontWeight: 'bold',
                fontStyle: 'italic'
            });
            expect(sprite.attr.font).toEqual('italic bold 10px sans-serif');
        });
    });

    describe('parseFontShorthand', function() {
        it('needs to handle "normal" values properly', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'normal 24px Verdana'
            });
            expect(sprite.attr.fontStyle).toEqual('');
            expect(sprite.attr.fontVariant).toEqual('');
            expect(sprite.attr.fontWeight).toEqual('');
            expect(sprite.attr.fontSize).toEqual('24px');
            expect(sprite.attr.fontFamily).toEqual('Verdana');
        });

        it('should ignore the "inherit" values', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'inherit 24px Verdana'
            });
            expect(sprite.attr.fontStyle).toEqual('');
            expect(sprite.attr.fontVariant).toEqual('');
            expect(sprite.attr.fontWeight).toEqual('');
            expect(sprite.attr.fontSize).toEqual('24px');
            expect(sprite.attr.fontFamily).toEqual('Verdana');
        });

        it('should support font names with spaces in them', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'x-large/110% "New Century Schoolbook", serif'
            });
            expect(sprite.attr.fontFamily).toEqual('"New Century Schoolbook", serif');
        });

        it('should support font families with more than one font name', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'italic small-caps normal 13px/150% Arial, Helvetica, sans-serif'
            });
            expect(sprite.attr.fontFamily).toEqual('Arial, Helvetica, sans-serif');
        });

        it('should be able to handle fontSize/lineHeight values ' +
           'by extracting fontSize and discarding lineHeigh', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'x-large/110% "New Century Schoolbook", serif'
            });
            expect(sprite.attr.fontSize).toEqual('x-large');
        });

        it('should recognize percentage font sizes', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: '80% sans-serif'
            });
            expect(sprite.attr.fontSize).toEqual('80%');
        });

        it('should recognize absolute font sizes', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'small serif'
            });
            expect(sprite.attr.fontSize).toEqual('small');
        });

        it('should recognize font weight values', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'italic 600 large Palatino, serif'
            });
            expect(sprite.attr.fontWeight).toEqual('600');
        });

        it('should recognize font variant values', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'normal small-caps 120%/120% fantasy'
            });
            expect(sprite.attr.fontVariant).toEqual('small-caps');
        });

        it('should recognize font style values', function() {
            var sprite = new Ext.draw.sprite.Text();

            sprite.setAttributes({
                font: 'bold large oblique Palatino, serif'
            });
            expect(sprite.attr.fontStyle).toEqual('oblique');
        });
    });

    describe('fontWeight processor', function() {
        // See: http://www.w3.org/TR/css3-fonts/#propdef-font-weight

        var def = Ext.draw.sprite.Text.def,
            fontWeight = def.getProcessors().fontWeight;

        fontWeight = fontWeight.bind(def);

        it('should return an empty string for unrecognized values', function() {
            var a = fontWeight(Infinity),
                b = fontWeight(-Infinity),
                c = fontWeight(101),
                d = fontWeight('hello'),
                e = fontWeight('505'),
                f = fontWeight(NaN),
                g = fontWeight(null),
                h = fontWeight(undefined),
                i = fontWeight(true),
                j = fontWeight(false);

            expect(a).toEqual('');
            expect(b).toEqual('');
            expect(c).toEqual('');
            expect(d).toEqual('');
            expect(e).toEqual('');
            expect(f).toEqual('');
            expect(g).toEqual('');
            expect(h).toEqual('');
            expect(i).toEqual('');
            expect(j).toEqual('');
        });
        it('should accept strings that can be parsed to a valid number', function() {
            var a = fontWeight('700');

            expect(a).toEqual('700');
        });
        it('should always return a string', function() {
            var a = fontWeight(400);

            expect(a).toEqual('400');
        });
        it('should only accept numbers that are multiples of 100 in the [100,900] interval', function() {
            var a = fontWeight(300),
                b = fontWeight(350),
                c = fontWeight(0),
                d = fontWeight(1000);

            expect(a).toEqual('300');
            expect(b).toEqual('');
            expect(c).toEqual('');
            expect(d).toEqual('');
        });
    });
});
