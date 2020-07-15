topSuite("Ext.event.Event", function() {
    var E = Ext.event.Event,
        e;

    function makeKeyEvent(config) {
        e = new E(Ext.apply({
            type: 'keydown'
        }, config));

        return e;
    }

    // The following key specs have their behaviour taken from the listed browsers.
    // IE/WebKit share the same behaviour, however Gecko behaves differently in various
    // cases.
    // 
    // Each item listed is
    // [expected, name, keyCode, charCode, options]

    describe("isSpecialKey", function() {
        function makeKeySuite(options) {
            Ext.Object.each(options, function(type, values) {
                describe(type, function() {
                    Ext.Array.forEach(values, function(value) {
                        var result = value[0],
                            key = value[1],
                            keyCode = value[2],
                            charCode = value[3],
                            keyOptions = value[4] || {};

                        it("should have " + key + " " + result, function() {
                            makeKeyEvent(Ext.apply({
                                type: type,
                                keyCode: keyCode,
                                charCode: charCode
                            }, keyOptions));
                            expect(e.isSpecialKey()).toBe(result);
                        });
                    });
                });
            });
        }

        // IE8-11 + Chrome follow the same behaviour
        if (Ext.isGecko) {
            makeKeySuite((function() {
                var downupData = [
                    [true, 'ctrl', 17, 0, { ctrlKey: true }],
                    [true, 'shift', 16, 18, { shiftKey: true }],
                    [true, 'alt', 18, 0, { altKey: true }],
                    [false, 'a', 65, 0],
                    [false, 'c', 67, 0],
                    [false, 'x', 88, 0],
                    [false, 'ctrl+a', 65, 0, { ctrlKey: true }],
                    [false, 'ctrl+c', 67, 0, { ctrlKey: true }],
                    [false, 'ctrl+x', 88, 0, { ctrlKey: true }],
                    [false, 'shift+a', 65, 0, { shiftKey: true }],
                    [false, 'shift+c', 67, 0, { shiftKey: true }],
                    [false, 'shift+x', 88, 0, { shiftKey: true }],
                    [false, '1', 49, 0],
                    [false, '5', 53, 0],
                    [false, '8', 56, 0],
                    [false, '!', 49, 0, { shiftKey: true }],
                    [false, '%', 53, 0, { shiftKey: true }],
                    [false, '*', 56, 0, { shiftKey: true }],
                    [true, 'backspace', 8, 0],
                    [true, 'enter', 13, 0],
                    [true, 'home', 36, 0],
                    [true, 'end', 35, 0],
                    [true, 'insert', 45, 0],
                    [true, 'delete', 46, 0],
                    [true, 'pgup', 33, 0],
                    [true, 'pgdown', 34, 0],
                    [true, 'tab', 9, 0],
                    [true, 'esc', 27, 0],
                    [true, 'pause', 19, 0],
                    [true, 'capslock', 20, 0],
                    [true, 'printscr', 44, 0],
                    [true, 'arrowup', 38, 0],
                    [true, 'arrowdown', 40, 0],
                    [true, 'arrowleft', 37, 0],
                    [true, 'arrowright', 39, 0],
                    [false, ',', 188, 0],
                    [false, '.', 190, 0],
                    [false, '/', 191, 0],
                    [false, ';', 59, 0],
                    [false, '\'', 222, 0],
                    [false, '[', 219, 0],
                    [false, ']', 220, 0],
                    [false, '-', 173, 0],
                    [false, '<', 188, 0, { shiftKey: true }],
                    [false, '>', 190, 0, { shiftKey: true }],
                    [false, '?', 191, 0, { shiftKey: true }],
                    [false, ':', 59, 0, { shiftKey: true }],
                    [false, '"', 222, 0, { shiftKey: true }],
                    [false, '{', 219, 0, { shiftKey: true }],
                    [false, '}', 220, 0, { shiftKey: true }],
                    [false, '_', 173, 0, { shiftKey: true }]
                ];

                return {
                    keydown: downupData,
                    keyup: downupData,
                    // Commented out keys don't fire
                    keypress: [
                        // ctrl
                        // shift
                        // alt
                        [false, 'a', 0, 97],
                        [false, 'c', 0, 99],
                        [false, 'x', 0, 120],
                        [false, 'ctrl+a', 0, 97, { ctrlKey: true }],
                        [false, 'ctrl+c', 0, 99, { ctrlKey: true }],
                        [false, 'ctrl+x', 0, 120, { ctrlKey: true }],
                        [false, 'shift+a', 0, 65, { shiftKey: true }],
                        [false, 'shift+c', 0, 67, { shiftKey: true }],
                        [false, 'shift+x', 0, 88, { shiftKey: true }],
                        [false, '1', 0, 49],
                        [false, '5', 0, 53],
                        [false, '8', 0, 56],
                        [false, '!', 0, 33, { shiftKey: true }],
                        [false, '%', 0, 37, { shiftKey: true }],
                        [false, '*', 0, 42, { shiftKey: true }],
                        [true, 'backspace', 8, 0],
                        [true, 'enter', 13, 0],
                        [true, 'home', 36, 0],
                        [true, 'end', 35, 0],
                        [true, 'insert', 45, 0],
                        [true, 'delete', 46, 0],
                        [true, 'pgup', 33, 0],
                        [true, 'pgdown', 34, 0],
                        [true, 'tab', 9, 0],
                        [true, 'esc', 27, 0],
                        [true, 'pause', 19, 0],
                        // capslock
                        // printscr
                        [true, 'arrowup', 38, 0],
                        [true, 'arrowdown', 40, 0],
                        [true, 'arrowleft', 37, 0],
                        [true, 'arrowright', 39, 0],
                        [false, ',', 0, 44],
                        [false, '.', 0, 46],
                        [false, '/', 0, 47],
                        [false, ';', 0, 59],
                        [false, '\'', 0, 39],
                        [false, '[', 0, 91],
                        [false, ']', 0, 93],
                        [false, '-', 0, 45],
                        [false, '<', 0, 60, { shiftKey: true }],
                        [false, '>', 0, 62, { shiftKey: true }],
                        [false, '?', 0, 63, { shiftKey: true }],
                        [false, ':', 0, 58, { shiftKey: true }],
                        [false, '"', 0, 34, { shiftKey: true }],
                        [false, '{', 0, 123, { shiftKey: true }],
                        [false, '}', 0, 125, { shiftKey: true }],
                        [false, '_', 0, 95, { shiftKey: true }]
                    ]
                };
            })());
        }
        else {
            makeKeySuite((function() {
                var downupData = [
                    [true, 'ctrl', 17, 0, { ctrlKey: true }],
                    [true, 'shift', 16, 18, { shiftKey: true }],
                    [true, 'alt', 18, 0, { altKey: true }],
                    [false, 'a', 65, 0],
                    [false, 'c', 67, 0],
                    [false, 'x', 88, 0],
                    [false, 'ctrl+a', 65, 0, { ctrlKey: true }],
                    [false, 'ctrl+c', 67, 0, { ctrlKey: true }],
                    [false, 'ctrl+x', 88, 0, { ctrlKey: true }],
                    [false, 'shift+a', 65, 0, { shiftKey: true }],
                    [false, 'shift+c', 67, 0, { shiftKey: true }],
                    [false, 'shift+x', 88, 0, { shiftKey: true }],
                    [false, '1', 49, 0],
                    [false, '5', 53, 0],
                    [false, '8', 56, 0],
                    [false, '!', 49, 0, { shiftKey: true }],
                    [false, '%', 53, 0, { shiftKey: true }],
                    [false, '*', 56, 0, { shiftKey: true }],
                    [true, 'backspace', 8, 0],
                    [true, 'enter', 13, 0],
                    [true, 'home', 36, 0],
                    [true, 'end', 35, 0],
                    [true, 'insert', 45, 0],
                    [true, 'delete', 46, 0],
                    [true, 'pgup', 33, 0],
                    [true, 'pgdown', 34, 0],
                    [true, 'tab', 9, 0],
                    [true, 'esc', 27, 0],
                    [true, 'pause', 19, 0],
                    [true, 'capslock', 20, 0],
                    [true, 'printscr', 44, 0],
                    [true, 'arrowup', 38, 0],
                    [true, 'arrowdown', 40, 0],
                    [true, 'arrowleft', 37, 0],
                    [true, 'arrowright', 39, 0],
                    [false, ',', 188, 0],
                    [false, '.', 190, 0],
                    [false, '/', 191, 0],
                    [false, ';', 186, 0],
                    [false, '\'', 222, 0],
                    [false, '[', 219, 0],
                    [false, ']', 220, 0],
                    [false, '-', 189, 0],
                    [false, '<', 188, 0, { shiftKey: true }],
                    [false, '>', 190, 0, { shiftKey: true }],
                    [false, '?', 191, 0, { shiftKey: true }],
                    [false, ':', 186, 0, { shiftKey: true }],
                    [false, '"', 222, 0, { shiftKey: true }],
                    [false, '{', 219, 0, { shiftKey: true }],
                    [false, '}', 220, 0, { shiftKey: true }],
                    [false, '_', 189, 0, { shiftKey: true }]
                ];

                return {
                    keydown: downupData,
                    keyup: downupData,
                    // Commented out keys don't fire
                    keypress: [
                        // ctrl
                        // shift
                        // alt
                        [false, 'a', 97, 97],
                        [false, 'c', 99, 99],
                        [false, 'x', 120, 120],
                        // ctrl+a
                        // ctrl+c
                        // ctrl+x 
                        [false, 'shift+a', 65, 65],
                        [false, 'shift+c', 67, 67],
                        [false, 'shift+x', 88, 88],
                        [false, '1', 49, 49],
                        [false, '5', 53, 53],
                        [false, '8', 56, 56],
                        [false, '!', 33, 33],
                        [false, '%', 37, 37],
                        [false, '*', 42, 42],
                        // backspace
                        [true, 'enter', 13, 13],
                        // home
                        // end
                        // insert
                        // delete
                        // pgup
                        // pgdown
                        // tab
                        // esc
                        // pause
                        // capslock
                        // printscr
                        // arrowup
                        // arrowdown
                        // arrowleft
                        // arrowright
                        [false, ',', 44, 44],
                        [false, '.', 46, 46],
                        [false, '/', 47, 47],
                        [false, ';', 59, 59],
                        [false, '\'', 39, 39],
                        [false, '[', 91, 91],
                        [false, ']', 93, 93],
                        [false, '-', 45, 45],
                        [false, '<', 60, 60, { shiftKey: true }],
                        [false, '>', 62, 62, { shiftKey: true }],
                        [false, '?', 63, 63, { shiftKey: true }],
                        [false, ':', 58, 58, { shiftKey: true }],
                        [false, '"', 34, 34, { shiftKey: true }],
                        [false, '{', 123, 123, { shiftKey: true }],
                        [false, '}', 125, 125, { shiftKey: true }],
                        [false, '_', 95, 95, { shiftKey: true }]
                    ]
                };
            })());
        }
    });

    describe("isNavKeyPress", function() {
        function makeSuite(scrollableOnly) {
            describe("scrollableOnly: " + scrollableOnly, function() {
                function makeKeySuite(options) {
                    Ext.Object.each(options, function(type, values) {
                        describe(type, function() {
                            Ext.Array.forEach(values, function(value) {
                                var result = value[0],
                                    key = value[1],
                                    keyCode = value[2],
                                    charCode = value[3],
                                    keyOptions = value[4] || {};

                                it("should have " + key + " " + result, function() {
                                    makeKeyEvent(Ext.apply({
                                        type: type,
                                        keyCode: keyCode,
                                        charCode: charCode
                                    }, keyOptions));
                                    expect(e.isNavKeyPress(scrollableOnly)).toBe(result);
                                });
                            });
                        });
                    });
                }

                // IE8-11 + Chrome follow the same behaviour
                if (Ext.isGecko) {
                    makeKeySuite((function() {
                        var downupData = [
                            [false, 'ctrl', 17, 0, { ctrlKey: true }],
                            [false, 'shift', 16, 18, { shiftKey: true }],
                            [false, 'alt', 18, 0, { altKey: true }],
                            [false, 'a', 65, 0],
                            [false, 'c', 67, 0],
                            [false, 'x', 88, 0],
                            [false, 'ctrl+a', 65, 0, { ctrlKey: true }],
                            [false, 'ctrl+c', 67, 0, { ctrlKey: true }],
                            [false, 'ctrl+x', 88, 0, { ctrlKey: true }],
                            [false, 'shift+a', 65, 0, { shiftKey: true }],
                            [false, 'shift+c', 67, 0, { shiftKey: true }],
                            [false, 'shift+x', 88, 0, { shiftKey: true }],
                            [false, '1', 49, 0],
                            [false, '5', 53, 0],
                            [false, '8', 56, 0],
                            [false, '!', 49, 0, { shiftKey: true }],
                            [false, '%', 53, 0, { shiftKey: true }],
                            [false, '*', 56, 0, { shiftKey: true }],
                            [false, 'backspace', 8, 0],
                            [!scrollableOnly, 'enter', 13, 0],
                            [true, 'home', 36, 0],
                            [true, 'end', 35, 0],
                            [false, 'insert', 45, 0],
                            [false, 'delete', 46, 0],
                            [true, 'pgup', 33, 0],
                            [true, 'pgdown', 34, 0],
                            [!scrollableOnly, 'tab', 9, 0],
                            [!scrollableOnly, 'esc', 27, 0],
                            [false, 'pause', 19, 0],
                            [false, 'capslock', 20, 0],
                            [false, 'printscr', 44, 0],
                            [true, 'arrowup', 38, 0],
                            [true, 'arrowdown', 40, 0],
                            [true, 'arrowleft', 37, 0],
                            [true, 'arrowright', 39, 0],
                            [false, ',', 188, 0],
                            [false, '.', 190, 0],
                            [false, '/', 191, 0],
                            [false, ';', 59, 0],
                            [false, '\'', 222, 0],
                            [false, '[', 219, 0],
                            [false, ']', 220, 0],
                            [false, '-', 173, 0],
                            [false, '<', 188, 0, { shiftKey: true }],
                            [false, '>', 190, 0, { shiftKey: true }],
                            [false, '?', 191, 0, { shiftKey: true }],
                            [false, ':', 59, 0, { shiftKey: true }],
                            [false, '"', 222, 0, { shiftKey: true }],
                            [false, '{', 219, 0, { shiftKey: true }],
                            [false, '}', 220, 0, { shiftKey: true }],
                            [false, '_', 173, 0, { shiftKey: true }]
                        ];

                        return {
                            keydown: downupData,
                            keyup: downupData,
                            // Commented out keys don't fire
                            keypress: [
                                // ctrl
                                // shift
                                // alt
                                [false, 'a', 0, 97],
                                [false, 'c', 0, 99],
                                [false, 'x', 0, 120],
                                [false, 'ctrl+a', 0, 97, { ctrlKey: true }],
                                [false, 'ctrl+c', 0, 99, { ctrlKey: true }],
                                [false, 'ctrl+x', 0, 120, { ctrlKey: true }],
                                [false, 'shift+a', 0, 65, { shiftKey: true }],
                                [false, 'shift+c', 0, 67, { shiftKey: true }],
                                [false, 'shift+x', 0, 88, { shiftKey: true }],
                                [false, '1', 0, 49],
                                [false, '5', 0, 53],
                                [false, '8', 0, 56],
                                [false, '!', 0, 33, { shiftKey: true }],
                                [false, '%', 0, 37, { shiftKey: true }],
                                [false, '*', 0, 42, { shiftKey: true }],
                                [false, 'backspace', 8, 0],
                                [!scrollableOnly, 'enter', 13, 0],
                                [true, 'home', 36, 0],
                                [true, 'end', 35, 0],
                                [false, 'insert', 45, 0],
                                [false, 'delete', 46, 0],
                                [true, 'pgup', 33, 0],
                                [true, 'pgdown', 34, 0],
                                [!scrollableOnly, 'tab', 9, 0],
                                [!scrollableOnly, 'esc', 27, 0],
                                [false, 'pause', 19, 0],
                                // capslock
                                // printscr
                                [true, 'arrowup', 38, 0],
                                [true, 'arrowdown', 40, 0],
                                [true, 'arrowleft', 37, 0],
                                [true, 'arrowright', 39, 0],
                                [false, ',', 0, 44],
                                [false, '.', 0, 46],
                                [false, '/', 0, 47],
                                [false, ';', 0, 59],
                                [false, '\'', 0, 39],
                                [false, '[', 0, 91],
                                [false, ']', 0, 93],
                                [false, '-', 0, 45],
                                [false, '<', 0, 60, { shiftKey: true }],
                                [false, '>', 0, 62, { shiftKey: true }],
                                [false, '?', 0, 63, { shiftKey: true }],
                                [false, ':', 0, 58, { shiftKey: true }],
                                [false, '"', 0, 34, { shiftKey: true }],
                                [false, '{', 0, 123, { shiftKey: true }],
                                [false, '}', 0, 125, { shiftKey: true }],
                                [false, '_', 0, 95, { shiftKey: true }]
                            ]
                        };
                    })());
                }
                else {
                    makeKeySuite((function() {
                        var downupData = [
                            [false, 'ctrl', 17, 0, { ctrlKey: true }],
                            [false, 'shift', 16, 18, { shiftKey: true }],
                            [false, 'alt', 18, 0, { altKey: true }],
                            [false, 'a', 65, 0],
                            [false, 'c', 67, 0],
                            [false, 'x', 88, 0],
                            [false, 'ctrl+a', 65, 0, { ctrlKey: true }],
                            [false, 'ctrl+c', 67, 0, { ctrlKey: true }],
                            [false, 'ctrl+x', 88, 0, { ctrlKey: true }],
                            [false, 'shift+a', 65, 0, { shiftKey: true }],
                            [false, 'shift+c', 67, 0, { shiftKey: true }],
                            [false, 'shift+x', 88, 0, { shiftKey: true }],
                            [false, '1', 49, 0],
                            [false, '5', 53, 0],
                            [false, '8', 56, 0],
                            [false, '!', 49, 0, { shiftKey: true }],
                            [false, '%', 53, 0, { shiftKey: true }],
                            [false, '*', 56, 0, { shiftKey: true }],
                            [false, 'backspace', 8, 0],
                            [!scrollableOnly, 'enter', 13, 0],
                            [true, 'home', 36, 0],
                            [true, 'end', 35, 0],
                            [false, 'insert', 45, 0],
                            [false, 'delete', 46, 0],
                            [true, 'pgup', 33, 0],
                            [true, 'pgdown', 34, 0],
                            [!scrollableOnly, 'tab', 9, 0],
                            [!scrollableOnly, 'esc', 27, 0],
                            [false, 'pause', 19, 0],
                            [false, 'capslock', 20, 0],
                            [false, 'printscr', 44, 0],
                            [true, 'arrowup', 38, 0],
                            [true, 'arrowdown', 40, 0],
                            [true, 'arrowleft', 37, 0],
                            [true, 'arrowright', 39, 0],
                            [false, ',', 188, 0],
                            [false, '.', 190, 0],
                            [false, '/', 191, 0],
                            [false, ';', 186, 0],
                            [false, '\'', 222, 0],
                            [false, '[', 219, 0],
                            [false, ']', 220, 0],
                            [false, '-', 189, 0],
                            [false, '<', 188, 0, { shiftKey: true }],
                            [false, '>', 190, 0, { shiftKey: true }],
                            [false, '?', 191, 0, { shiftKey: true }],
                            [false, ':', 186, 0, { shiftKey: true }],
                            [false, '"', 222, 0, { shiftKey: true }],
                            [false, '{', 219, 0, { shiftKey: true }],
                            [false, '}', 220, 0, { shiftKey: true }],
                            [false, '_', 189, 0, { shiftKey: true }]
                        ];

                        return {
                            keydown: downupData,
                            keyup: downupData,
                            // Commented out keys don't fire
                            keypress: [
                                // ctrl
                                // shift
                                // alt
                                [false, 'a', 97, 97],
                                [false, 'c', 99, 99],
                                [false, 'x', 120, 120],
                                // ctrl+a
                                // ctrl+c
                                // ctrl+x 
                                [false, 'shift+a', 65, 65],
                                [false, 'shift+c', 67, 67],
                                [false, 'shift+x', 88, 88],
                                [false, '1', 49, 49],
                                [false, '5', 53, 53],
                                [false, '8', 56, 56],
                                [false, '!', 33, 33],
                                [false, '%', 37, 37],
                                [false, '*', 42, 42],
                                // backspace
                                [!scrollableOnly, 'enter', 13, 13],
                                // home
                                // end
                                // insert
                                // delete
                                // pgup
                                // pgdown
                                // tab
                                // esc
                                // pause
                                // capslock
                                // printscr
                                // arrowup
                                // arrowdown
                                // arrowleft
                                // arrowright
                                [false, ',', 44, 44],
                                [false, '.', 46, 46],
                                [false, '/', 47, 47],
                                [false, ';', 59, 59],
                                [false, '\'', 39, 39],
                                [false, '[', 91, 91],
                                [false, ']', 93, 93],
                                [false, '-', 45, 45],
                                [false, '<', 60, 60, { shiftKey: true }],
                                [false, '>', 62, 62, { shiftKey: true }],
                                [false, '?', 63, 63, { shiftKey: true }],
                                [false, ':', 58, 58, { shiftKey: true }],
                                [false, '"', 34, 34, { shiftKey: true }],
                                [false, '{', 123, 123, { shiftKey: true }],
                                [false, '}', 125, 125, { shiftKey: true }],
                                [false, '_', 95, 95, { shiftKey: true }]
                            ]
                        };
                    })());
                }
            });
        }

        makeSuite(false);
        makeSuite(true);
    });

    describe("within", function() {
        var target;

        beforeEach(function() {
            target = Ext.getBody().createChild();
        });

        afterEach(function() {
            target.destroy();
            target = null;
        });

        it("should return true by default if e.target === el.dom", function() {
            target.on({
                mousedown: function(e) {
                    expect(e.within(target)).toBe(true);
                }
            });

            jasmine.fireMouseEvent(target, 'mousedown');
            jasmine.fireMouseEvent(target, 'mouseup');
        });

        it("should return false if allowEl is false and e.target === el.dom", function() {
            target.on({
                mousedown: function(e) {
                    expect(e.within(target, null, false)).toBe(false);
                }
            });

            jasmine.fireMouseEvent(target, 'mousedown');
            jasmine.fireMouseEvent(target, 'mouseup');
        });
    });

    describe("time stamp", function() {
        describe("delegated", function() {
            var target, event, t0, t1;

            beforeEach(function() {
                target = Ext.getBody().createChild();

                target.on('mousedown', function(e) {
                    event = e;
                    t0 = e.timeStamp;
                });

                target.on('mouseup', function(e) {
                    t1 = e.timeStamp;
                });
            });

            afterEach(function() {
                target.destroy();
                target = null;
            });

            it("should be the current date in milliseconds", function() {
                jasmine.fireMouseEvent(target, 'mousedown');

                jasmine.fireMouseEvent(target, 'mouseup');

                // MDN (https://developer.mozilla.org/en-US/docs/Web/API/Event/timeStamp):
                // This value is the number of milliseconds elapsed from the beginning of
                // the current document's lifetime till the event was created.
                expect(t1 >= t0).toBe(true);
            });

            it("should set both time and timeStamp", function() {
                jasmine.fireMouseEvent(target, 'mousedown');
                expect(event.time).toBe(event.timeStamp);
                jasmine.fireMouseEvent(target, 'mouseup');
            });
        });

        describe("non-delegated", function() {
            var target, event, t0, t1;

            beforeEach(function() {
                target = Ext.getBody().createChild();

                target.on({
                    mousedown: function(e) {
                        event = e;
                        t0 = e.timeStamp;
                    },
                    mouseup: function(e) {
                        event = e;
                        t1 = e.timeStamp;
                    },
                    delegated: false
                });
            });

            afterEach(function() {
                target.destroy();
                target = null;
            });

            it("should be the current date in milliseconds", function() {
                jasmine.fireMouseEvent(target, 'mousedown');

                jasmine.fireMouseEvent(target, 'mouseup');

                // MDN (https://developer.mozilla.org/en-US/docs/Web/API/Event/timeStamp):
                // This value is the number of milliseconds elapsed from the beginning of
                // the current document's lifetime till the event was created.
                expect(t1 >= t0).toBe(true);
            });

            it("should set both time and timeStamp", function() {
                jasmine.fireMouseEvent(target, 'mousedown');
                expect(event.time).toBe(event.timeStamp);
                jasmine.fireMouseEvent(target, 'mouseup');
            });
        });
    });

    describe('which', function() {
        var target, event;

        beforeEach(function() {
            target = Ext.getBody().createChild();
        });

        afterEach(function() {
            target.destroy();
            target = null;
        });

        if (!Ext.isIE8) {
            /**
             * IE8 will not set keyCode param on the event.
             * e.keyCode will always be 0
             */
            describe('key event', function() {
                beforeEach(function() {
                    target.on({
                        delegated: false,
                        keydown: function(e) {
                            event = e;
                        }
                    });
                });

                it('should recognize key code', function() {
                    jasmine.fireKeyEvent(target, 'keydown', 'a');
                    jasmine.fireKeyEvent(target, 'keyup', 'a');

                    var which = event.which();

                    expect(which).toBe('a');
                });

                it('should recognize key code with modifier', function() {
                    jasmine.fireKeyEvent(target, 'keydown', 'B', true);
                    jasmine.fireKeyEvent(target, 'keyup', 'B', true);

                    var which = event.which();

                    expect(which).toBe('B');
                });
            });
        }
    });
});
