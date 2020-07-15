xtopSuite('Ext.util.History', function() {
    var fooLink, barLink, bletchLink;

    beforeEach(function() {
        location.hash = '';
    });

    afterEach(function() {
        fooLink.parentNode.removeChild(fooLink);
        barLink.parentNode.removeChild(barLink);
        bletchLink.parentNode.removeChild(bletchLink);
        location.hash = '';
    });

    describe("alternate class name", function() {
        it("should have Ext.History as the alternate class name", function() {
            expect(Ext.util.History.alternateClassName).toEqual("Ext.History");
        });

        it("should allow the use of Ext.History", function() {
            expect(Ext.History).toBeDefined();
        });
    });

    it('should track history', function() {
        fooLink = document.createElement('a');
        barLink = document.createElement('a');
        bletchLink = document.createElement('a');

        var hashHistory = [],
            useClickEvent = Ext.isWebKit || Ext.isGecko,
            useClickMethod = fooLink.click,
            navigate = useClickEvent
                ? function(link) {
                    if (Ext.isGecko) {
                        link.focus();
                    }

                    jasmine.fireMouseEvent(link, 'click');
                }
                : useClickMethod
                    ? function(link) {
                        link.click();
                    }
                    : function(link) {
                        Ext.util.History.setHash(link.hash.substr(1));
                    };

        fooLink.href = "#foo";
        barLink.href = "#bar";
        bletchLink.href = "#bletch";
        document.body.appendChild(fooLink);
        document.body.appendChild(barLink);
        document.body.appendChild(bletchLink);

        Ext.History.init();

        Ext.History.on('change', function(token) {
            hashHistory.push(token);
        });

        // Navigate to #foo
        navigate(fooLink);

        waitsFor(function() {
            return hashHistory.length === 1;
        }, 'Hash history change #foo', 200);

        runs(function() {
            expect(location.hash).toBe('#foo');

            // Navigate to #bar
            navigate(barLink);
        });

        waitsFor(function() {
            return hashHistory.length === 2;
        }, 'Hash history change #bar', 200);

        runs(function() {
            expect(location.hash).toBe('#bar');

            // Navigate to #bletch
            navigate(bletchLink);
        });

        waitsFor(function() {
            return hashHistory.length === 3;
        }, 'Hash history change #bletch', 200);

        runs(function() {
            expect(location.hash).toBe('#bletch');

            // Go back to #bar
            Ext.util.History.back();
        });

        waitsFor(function() {
            return hashHistory.length === 4;
        }, 'Hash history change #bar', 200);

        runs(function() {
            expect(location.hash).toBe('#bar');

            // Go back to #foo
            Ext.util.History.back();
        });

        waitsFor(function() {
            return hashHistory.length === 5;
        }, 'Hash history change #foo', 200);

        runs(function() {
            expect(location.hash).toBe('#foo');
            expect(hashHistory).toEqual(["foo", "bar", "bletch", "bar", "foo"]);
        });
    });
});
