topSuite("Ext.event.publisher.Focus", function() {
    var body = document.body,
        fired = false,
        a, b, c;

    function fireIt() {
        fired = true;
    }

    beforeEach(function() {
        var markup = [
            '<div id="a1" tabindex="-1" style="width:300px;height:100px;">',
                '<div id="b1" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c1" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c2" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
                '<div id="b2" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c3" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c4" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
                '<div id="b3" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c5" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c6" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
            '</div>',
            '<div id="a2" tabindex="-1" style="width:340px;height:100px;">',
                '<div id="b4" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c7" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c8" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
                '<div id="b5" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c9" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c10" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
                '<div id="b6" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c11" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c12" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
            '</div>',
            '<div id="a3" tabindex="-1" style="width:340px;height:100px;">',
                '<div id="b7" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c13" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c14" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
                '<div id="b8" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c15" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c16" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
                '<div id="b9" tabindex="-1" style="width:100px;height:100%;">',
                    '<div id="c16" tabindex="-1" style="width:50px;height:100%"></div>',
                    '<div id="c18" tabindex="-1" style="width:50px;height:100%"></div>',
                '</div>',
            '</div>'
        ],
        i, len;

        Ext.dom.Helper.insertFirst(body, markup.join(''));

        fired = false;

        a = [];
        b = [];
        c = [];

        for (i = 1, len = 4; i < len; i++) {
            a[i] = Ext.get('a' + i);
        }

        for (i = 1, len = 10; i < len; i++) {
            b[i] = Ext.get('b' + i);
        }

        for (i = 1, len = 19; i < len; i++) {
            c[i] = Ext.get('c' + i);
        }

        body.setAttribute('tabIndex', -1);
    });

    afterEach(function() {
        Ext.each([a, b, c], function(arr) {
            Ext.each(arr, function(el) {
                if (el) {
                    el.destroy();
                }
            });
        });

        body.removeAttribute('tabIndex');
    });

    describe("focusenter", function() {
        describe("fires", function() {
            function createSuite(name, beforeFn) {
                describe(name, function() {
                    if (beforeFn) {
                        beforeEach(beforeFn);
                    }

                    beforeEach(function() {
                        b[1].on('focusenter', fireIt);
                    });

                    it("fires when element itself is focused", function() {
                        b[1].focus();

                        expect(fired).toBe(true);
                    });

                    it("fires when child is focused", function() {
                        c[1].focus();

                        expect(fired).toBe(true);
                    });

                    it("doesn't fire when parent is focused", function() {
                        a[1].focus();

                        expect(fired).toBe(false);
                    });
                });
            }

            createSuite("from out of the universe");
            createSuite("from the document", function() { body.focus(); });
            createSuite("from its parent", function() { a[1].focus(); });
            createSuite("from a parent's sibling", function() { a[3].focus(); });
            createSuite("from a sibling el", function() { b[2].focus(); });
        });

        describe("doesn't fire", function() {
            describe("within the element", function() {
                it("when focus moves between children", function() {
                    b[1].focus();

                    a[1].on('focusenter', fireIt);

                    b[2].focus();

                    expect(fired).toBe(false);
                });

                it("when focus moves between grancdhildren", function() {
                    c[1].focus();

                    a[1].on('focusenter', fireIt);

                    c[2].focus();

                    expect(fired).toBe(false);
                });

                it("when focus moves between child and el itself", function() {
                    b[1].focus();

                    a[1].on('focusenter', fireIt);

                    a[1].focus();

                    expect(fired).toBe(false);
                });

                it("when focus moves between el and child", function() {
                    a[1].focus();

                    a[1].on('focusenter', fireIt);

                    b[1].focus();

                    expect(fired).toBe(false);
                });

                it("when focus moves between el and grandchild", function() {
                    a[1].focus();

                    a[1].on('focusenter', fireIt);

                    c[1].focus();

                    expect(fired).toBe(false);
                });
            });
        });
    });

    describe("focusleave", function() {
        describe("fires", function() {
            beforeEach(function() {
                b[1].on('focusleave', fireIt);
                b[1].focus();
            });

            it("when focus moves to its parent", function() {
                a[1].focus();

                expect(fired).toBe(true);
            });

            it("when focus moves to parent's sibling", function() {
                a[2].focus();

                expect(fired).toBe(true);
            });

            it("when focus moves to the document", function() {
                body.focus();

                expect(fired).toBe(true);
            });
        });

        describe("doesn't fire", function() {
            beforeEach(function() {
                a[1].on('focusleave', fireIt);
                b[1].focus();
            });

            it("when focus moves from one child to another", function() {
                b[2].focus();

                expect(fired).toBe(false);
            });

            it("when focus moves from child to grandchild", function() {
                c[1].focus();

                expect(fired).toBe(false);
            });

            it("when focus moves from one grandchild to another", function() {
                c[1].focus();
                c[2].focus();

                expect(fired).toBe(false);
            });

            it("when focus moves from child to el itself", function() {
                a[1].focus();

                expect(fired).toBe(false);
            });

            it("when focus moves from grandchild to el itself", function() {
                c[1].focus();
                a[1].focus();

                expect(fired).toBe(false);
            });
        });
    });

    describe('focusmove', function() {
        it('should fire focusmove whenever focus moves within an element', function() {
            var a1focusMove,
                b1focusMove,
                b2focusMove,
                c1Focused;

            a[1].on('focusmove', function(e) {
                a1focusMove = e;
            });
            b[1].on('focusmove', function(e) {
                b1focusMove = e;
            });
            b[2].on('focusmove', function(e) {
                b2focusMove = e;
            });
            c[1].on('focus', function() {
                c1Focused = true;
            });

            c[1].focus();

            // Wait for focus to have moved into a[1]
            waitsFor(function() {
                return c1Focused;
            }, 'Focus to move to a[1]>b[1]>c[1]');

            // No focus *moves* have ocurred. Only focusenters
            runs(function() {
                expect(a1focusMove).toBeUndefined();
                expect(b1focusMove).toBeUndefined();
                expect(b2focusMove).toBeUndefined();

                // This will move focus within a[1]
                c[2].focus();
            });

            // Focus should now *move* within a[1]
            waitsFor(function() {
                return !!a1focusMove;
            });

            runs(function() {

                // Move from c[1] to c[2] bubbled through b[1] to a[1]
                expect(b1focusMove).toBe(a1focusMove);

                // Focus is never going to move within b[2]
                expect(b2focusMove).toBeUndefined();

                // Move was from c[1] to c[2]
                expect(a1focusMove.target).toBe(c[2].dom);
                expect(a1focusMove.relatedTarget).toBe(c[1].dom);

                // Reset all events
                a1focusMove = b1focusMove = null;

                c[3].focus();
            });

            // Focus should have moved within a[1]
            waitsFor(function() {
                return !!a1focusMove;
            });

            runs(function() {

                // Focus has not moved within b1. Focus has left b1
                expect(b1focusMove).toBeNull();

                // Focus is never going to move within b[2]
                expect(b2focusMove).toBeUndefined();

                // Move was from c[2] to c[3]
                expect(a1focusMove.target).toBe(c[3].dom);
                expect(a1focusMove.relatedTarget).toBe(c[2].dom);

                // Reset a1's focusmove event. It should NOT be set if we suspend focus
                a1focusMove = null;
                c1Focused = false;

                // Suspend the focus publisher
                c[3].suspendFocusEvents();
                c[1].suspendFocusEvents();

                c[1].focus();
            });

            // Wait for focus to have moved to c[1]
            jasmine.waitForFocus(c[1], 'Focus to move from a[1]>b[2]>c[3] to a[1]>b[1]>c[1]');

            // a[1]'s focusmove event must NOT have been fired during suspension
            runs(function() {
                expect(a1focusMove).toBe(null);

                // Resume the focus publisher
                c[1].resumeFocusEvents();
                c[3].resumeFocusEvents();
            });

            // Give IE enough cycles to run resumeFocusEvents callback
            jasmine.waitAWhile();

            runs(function() {
                // This will move focus within a[1]
                c[2].focus();
            });

            // Focus should have moved within a[1]
            waitsFor(function() {
                return !!a1focusMove;
            });
        });
    });
});
