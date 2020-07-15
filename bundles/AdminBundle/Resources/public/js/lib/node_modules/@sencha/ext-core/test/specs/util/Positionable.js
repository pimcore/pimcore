topSuite("Ext.util.Positionable", 'Ext.Component', function() {
    var wrap, positionable;

    function createElement(cfg) {
        positionable = wrap.createChild(Ext.apply({
            cls: 'x-border-box',
            style: {
                width: '40px',
                height: '40px',
                left: '6px',
                top: '7px',
                'z-index': 10,
                position: 'absolute',
                backgroundColor: 'green'
            }
        }, cfg));
    }

    function createComponent(cfg) {
        positionable = Ext.widget(Ext.apply({
            xtype: 'component',
            renderTo: wrap,
            style: {
                width: '40px',
                height: '40px',
                left: '6px',
                top: '7px',
                'z-index': 10,
                position: 'absolute',
                backgroundColor: 'green'
            }
        }, cfg));
    }

    beforeEach(function() {
        // creates an absolute positioned wrapper element so that specs can
        // append Elements/Components to it to test positioning to page-coordinates
        wrap = Ext.getBody().createChild({
            style: {
                width: '100px',
                height: '100px',
                left: '15px',
                top: '20px',
                position: 'absolute'
            }
        });
    });

    afterEach(function() {
        wrap.destroy();
        positionable.destroy();
    });

    function createSuite(isComponent) {
        var suiteType = isComponent ? "Components" : "Elements";

        // iOS viewport body scrolls in a different way (body grows and panning is used)
        // so scrolling viewport tests fail.
        (Ext.isiOS ? xdescribe : describe)("aligning " + suiteType, function() {
            var positions = [ 'tl', 't', 'tr', 'l', 'c', 'r', 'bl', 'b', 'br' ],
                alignToPositions = {
                    tl: [60, 60],
                    t: [90, 60],
                    tr: [120, 60],
                    l: [60, 90],
                    c: [90, 90],
                    r: [120, 90],
                    bl: [60, 120],
                    b: [90, 120],
                    br: [120, 120]
                },
                alignPositions = {
                    tl: [0, 0],
                    t: [-20, 0],
                    tr: [-40, 0],
                    l: [0, -20],
                    c: [-20, -20],
                    r: [-40, -20],
                    bl: [0, -40],
                    b: [-20, -40],
                    br: [-40, -40]
                },
                alignToEl;

            beforeEach(function() {
                if (isComponent) {
                    createComponent();
                }
                else {
                    createElement();
                }

                alignToEl = Ext.getBody().createChild({
                    style: {
                        width: '60px',
                        height: '60px',
                        left: '60px',
                        top: '60px',
                        position: 'absolute',
                        backgroundColor: 'red'
                    }
                });
            });

            afterEach(function() {
                alignToEl.destroy();
            });

            Ext.each(positions, function(pos) {
                Ext.each(positions, function(alignToPos) {
                    var posString = pos + '-' + alignToPos;

                    it("should align " + posString, function() {
                        var xy;

                        positionable.alignTo(alignToEl, posString);
                        xy = positionable.getXY();
                        expect(xy[0]).toBe(alignToPositions[alignToPos][0] + alignPositions[pos][0]);
                        expect(xy[1]).toBe(alignToPositions[alignToPos][1] + alignPositions[pos][1]);
                    });
                });
            });

            it("should respect scrolling when align to body", function() {
                var positions = positionable.el.getAlignToRegion(Ext.getBody()),
                    positionsAfter;

                alignToEl.setHeight(2000);
                Ext.getViewportScroller().scrollBy(0, 100);

                positionsAfter = positionable.el.getAlignToRegion(Ext.getBody());
                expect(positions.top).toBe(positionsAfter.top - 100);
            });
        });

        describe("aligning " + suiteType + ' with "?" constraining', function() {
            // Test tesult with constraining.
            // alignToEl is positioned offset so that the positioned element sometimes swaps sides
            // and sometimes is just constrained.
            var resultPositions = {
                "tl-tl?": [35, 40],
                "tl-t?": [65, 40],
                "tl-tr?": [95, 40],
                "tl-l?": [35, 70],
                "tl-c?": [65, 70],
                "tl-r?": [95, 70],
                "tl-bl?": [35, 100],
                "tl-b?": [65, 100],
                "tl-br?": [95, 100],
                "t-tl?": [15, 40],
                "t-t?": [45, 40],
                "t-tr?": [75, 40],
                "t-l?": [15, 70],
                "t-c?": [45, 70],
                "t-r?": [75, 70],
                "t-bl?": [15, 100],
                "t-b?": [45, 100],
                "t-br?": [75, 100],
                "tr-tl?": [95, 40],
                "tr-t?": [25, 40],
                "tr-tr?": [55, 40],
                "tr-l?": [95, 70],
                "tr-c?": [25, 70],
                "tr-r?": [55, 70],
                "tr-bl?": [15, 100],
                "tr-b?": [25, 100],
                "tr-br?": [55, 100],
                "l-tl?": [35, 20],
                "l-t?": [65, 20],
                "l-tr?": [95, 20],
                "l-l?": [35, 50],
                "l-c?": [65, 50],
                "l-r?": [95, 50],
                "l-bl?": [35, 80],
                "l-b?": [65, 80],
                "l-br?": [95, 80],
                "c-tl?": [15, 20],
                "c-t?": [45, 20],
                "c-tr?": [75, 20],
                "c-l?": [15, 50],
                "c-c?": [45, 50],
                "c-r?": [75, 50],
                "c-bl?": [15, 80],
                "c-b?": [45, 80],
                "c-br?": [75, 80],
                "r-tl?": [95, 20],
                "r-t?": [25, 20],
                "r-tr?": [55, 20],
                "r-l?": [95, 50],
                "r-c?": [25, 50],
                "r-r?": [55, 50],
                "r-bl?": [95, 80],
                "r-b?": [25, 80],
                "r-br?": [55, 80],
                "bl-tl?": [35, 100],
                "bl-t?": [65, 100],
                "bl-tr?": [95, 20],
                "bl-l?": [35, 30],
                "bl-c?": [65, 30],
                "bl-r?": [95, 30],
                "bl-bl?": [35, 60],
                "bl-b?": [65, 60],
                "bl-br?": [95, 60],
                "b-tl?": [15, 100],
                "b-t?": [45, 100],
                "b-tr?": [75, 100],
                "b-l?": [15, 30],
                "b-c?": [45, 30],
                "b-r?": [75, 30],
                "b-bl?": [15, 60],
                "b-b?": [45, 60],
                "b-br?": [75, 60],
                "br-tl?": [15, 100],
                "br-t?": [25, 100],
                "br-tr?": [55, 100],
                "br-l?": [95, 30],
                "br-c?": [25, 30],
                "br-r?": [55, 30],
                "br-bl?": [95, 60],
                "br-b?": [25, 60],
                "br-br?": [55, 60]
            },
            positions = [ 'tl', 't', 'tr', 'l', 'c', 'r', 'bl', 'b', 'br' ],
            alignToEl;

            beforeEach(function() {
                if (isComponent) {
                    createComponent();
                }
                else {
                    createElement();
                }

                wrap.setSize(120, 120);
                wrap.dom.style.backgroundColor = 'white';
                wrap.dom.appendChild(positionable.el.dom);
                positionable.el.dom.style.backgroundColor = 'red';

                alignToEl = Ext.getBody().createChild({
                    style: {
                        backgroundColor: 'black',
                        width: '60px',
                        height: '60px',
                        left: '35px',
                        top: '40px',
                        position: 'absolute'
                    }
                });
            });

            afterEach(function() {
                alignToEl.destroy();
            });

            Ext.each(positions, function(pos) {
                Ext.each(positions, function(alignToPos) {
                    var posString = pos + '-' + alignToPos + '?';

                    it('should align "' + posString + '"', function() {
                        var xy;

                        positionable.alignTo(alignToEl, posString);
                        xy = positionable.getXY();
                        expect(xy[0]).toBe(resultPositions[posString][0]);
                        expect(xy[1]).toBe(resultPositions[posString][1]);
                    });
                });
            });
        });

        describe("aligning " + suiteType + ' with "!" constraining', function() {
            // Test tesult with constraining.
            // The only one test is enabled which we need to fix EXTJS-18971
            // The others should be re-enabled in https://sencha.jira.com/browse/EXTJS-19018
            var resultPositions = {
//                 "tl-tl!": [35, 40],
//                 "tl-t!": [55, 40],
//                 "tl-tr!": [35, 40],
//                 "tl-l!": [35, 60],
//                 "tl-c!": [55, 60],
//                 "tl-r!": [35, 60],
//                 "tl-bl!": [35, 100],
//                 "tl-b!": [65, 100],
//                 "tl-br!": [95, 100],
//                 "t-tl!": [15, 40],
//                 "t-t!": [45, 40],
//                 "t-tr!": [75, 40],
//                 "t-l!": [15, 70],
//                 "t-c!": [45, 70],
//                 "t-r!": [75, 70],
//                 "t-bl!": [15, 100],
//                 "t-b!": [45, 100],
//                 "t-br!": [75, 100],
//                 "tr-tl!": [95, 40],
//                 "tr-t!": [25, 40],
//                 "tr-tr!": [55, 40],
//                 "tr-l!": [95, 70],
//                 "tr-c!": [25, 70],
//                 "tr-r!": [55, 70],
//                 "tr-bl!": [15, 100],
//                 "tr-b!": [25, 100],
//                 "tr-br!": [55, 100],
//                 "l-tl!": [35, 20],
//                 "l-t!": [65, 20],
//                 "l-tr!": [95, 20],
                "l-l!": [35, 50]
//                 "l-c!": [65, 50],
//                 "l-r!": [95, 50],
//                 "l-bl!": [35, 80],
//                 "l-b!": [65, 80],
//                 "l-br!": [95, 80],
//                 "c-tl!": [15, 20],
//                 "c-t!": [45, 20],
//                 "c-tr!": [75, 20],
//                 "c-l!": [15, 50],
//                 "c-c!": [45, 50],
//                 "c-r!": [75, 50],
//                 "c-bl!": [15, 80],
//                 "c-b!": [45, 80],
//                 "c-br!": [75, 80],
//                 "r-tl!": [95, 20],
//                 "r-t!": [25, 20],
//                 "r-tr!": [55, 20],
//                 "r-l!": [95, 50],
//                 "r-c!": [25, 50],
//                 "r-r!": [55, 50],
//                 "r-bl!": [95, 80],
//                 "r-b!": [25, 80],
//                 "r-br!": [55, 80],
//                 "bl-tl!": [35, 100],
//                 "bl-t!": [65, 100],
//                 "bl-tr!": [95, 20],
//                 "bl-l!": [35, 30],
//                 "bl-c!": [65, 30],
//                 "bl-r!": [95, 30],
//                 "bl-bl!": [35, 60],
//                 "bl-b!": [65, 60],
//                 "bl-br!": [95, 60],
//                 "b-tl!": [15, 100],
//                 "b-t!": [45, 100],
//                 "b-tr!": [75, 100],
//                 "b-l!": [15, 30],
//                 "b-c!": [45, 30],
//                 "b-r!": [75, 30],
//                 "b-bl!": [15, 60],
//                 "b-b!": [45, 60],
//                 "b-br!": [75, 60],
//                 "br-tl!": [95, 100],
//                 "br-t!": [25, 100],
//                 "br-tr!": [55, 100],
//                 "br-l!": [95, 30],
//                 "br-c!": [25, 30],
//                 "br-r!": [55, 30],
//                 "br-bl!": [95, 60],
//                 "br-b!": [25, 60],
//                 "br-br!": [55, 60]
            },
            positions = [ 'tl', 't', 'tr', 'l', 'c', 'r', 'bl', 'b', 'br' ],
            alignToEl;

            beforeEach(function() {
                if (isComponent) {
                    createComponent();
                }
                else {
                    createElement();
                }

                wrap.setSize(120, 120);
                wrap.dom.style.backgroundColor = 'white';
                wrap.dom.appendChild(positionable.el.dom);
                positionable.el.dom.style.backgroundColor = 'red';

                alignToEl = Ext.getBody().createChild({
                    style: {
                        backgroundColor: 'black',
                        width: '60px',
                        height: '60px',
                        left: '35px',
                        top: '40px',
                        position: 'absolute'
                    }
                });
            });

            afterEach(function() {
                alignToEl.destroy();
            });

            Ext.each(positions, function(pos) {
                Ext.each(positions, function(alignToPos) {
                    var posString = pos + '-' + alignToPos + '!',
                        todoIt = resultPositions[posString] ? it : xit;

                    todoIt('should align "' + posString + '"', function() {
                        var xy;

                        positionable.alignTo(alignToEl, posString);
                        xy = positionable.getXY();
                        expect(xy[0]).toBe(resultPositions[posString][0]);
                        expect(xy[1]).toBe(resultPositions[posString][1]);
                    });
                });
            });
        });

        describe("positioning " + suiteType, function() {
            beforeEach(function() {
                if (isComponent) {
                    createComponent();
                }
                else {
                    createElement();
                }
            });

            describe("getBox", function() {
                it("should get the box", function() {
                    expect(positionable.getBox()).toEqual({
                        0: 21,
                        1: 27,
                        x: 21,
                        y: 27,
                        left: 21,
                        top: 27,
                        width: 40,
                        height: 40,
                        right: 61,
                        bottom: 67
                    });
                });

                it("should get the content box", function() {
                    positionable.el.setStyle('border', '5px solid #000');
                    expect(positionable.getBox(true)).toEqual({
                        0: 26,
                        1: 32,
                        x: 26,
                        y: 32,
                        left: 26,
                        top: 32,
                        width: 30,
                        height: 30,
                        right: 56,
                        bottom: 62
                    });
                });

                it("should get the local box", function() {
                    expect(positionable.getBox(false, true)).toEqual({
                        0: 6,
                        1: 7,
                        x: 6,
                        y: 7,
                        left: 6,
                        top: 7,
                        width: 40,
                        height: 40,
                        right: 46,
                        bottom: 47
                    });
                });

                it("should get the local content box", function() {
                    positionable.el.setStyle('border', '5px solid #000');
                    expect(positionable.getBox(true, true)).toEqual({
                        0: 11,
                        1: 12,
                        x: 11,
                        y: 12,
                        left: 11,
                        top: 12,
                        width: 30,
                        height: 30,
                        right: 41,
                        bottom: 42
                    });
                });
            });

            describe("getConstrainVector", function() {
                // TODO
            });

            describe("getLocalX", function() {
                it("should return the local x position", function() {
                    expect(positionable.getLocalX()).toBe(6);
                });
            });

            describe("getLocalXY", function() {
                it("should return the local xy position", function() {
                    expect(positionable.getLocalXY()).toEqual([6, 7]);
                });
            });

            describe("getLocalY", function() {
                it("should return the local y position", function() {
                    expect(positionable.getLocalY()).toBe(7);
                });
            });

            describe("getOffsetsTo", function() {
                it("should get the offsets to an element", function() {
                    var offsetEl = Ext.getBody().createChild({
                        style: {
                            height: '100px',
                            width: '100px',
                            top: '77px',
                            left: '121px',
                            position: 'absolute'
                        }
                    });

                    expect(positionable.getOffsetsTo(offsetEl)).toEqual([-100, -50]);
                    offsetEl.destroy();
                });
            });

            describe("getRegion", function() {
                it("should get the Region", function() {
                    var region = positionable.getRegion();

                    expect(region.top).toBe(27);
                    expect(region.right).toBe(61);
                    expect(region.bottom).toBe(67);
                    expect(region.left).toBe(21);
                });
            });

            // IE9 disabled because https://sencha.jira.com/browse/EXTJS-19483
            (Ext.isIE9 ? xdescribe : describe)("getClientRegion", function() {
                var scrollbarSize = Ext.getScrollbarSize(),
                    el, region;

                function addScrollStyle(axis) {
                    el.setStyle('overflow-' + axis, 'scroll');
                }

                beforeEach(function() {
                    el = isComponent ? positionable.el : positionable;

                    // Default width and height of 40px is not enough
                    // to display scrollbars in some browsers
                    el.dom.style.width = el.dom.style.height = '100px';
                    el.dom.style.backgroundColor = 'red';

                    region = positionable.getRegion();
                });

                it("should be the same as Region with no scrollbars", function() {
                    var clientRegion = positionable.getClientRegion();

                    expect(clientRegion.equals(region)).toBe(true);
                });

                it("should account for vertical scrollbar", function() {
                    addScrollStyle('y');

                    var clientRegion = positionable.getClientRegion();

                    expect(clientRegion.top).toBe(region.top);
                    expect(clientRegion.right).toBe(region.right - scrollbarSize.width);
                    expect(clientRegion.bottom).toBe(region.bottom);
                    expect(clientRegion.left).toBe(region.left);
                });

                it("should account for horizontal scrollbar", function() {
                    addScrollStyle('x');

                    var clientRegion = positionable.getClientRegion();

                    expect(clientRegion.top).toBe(region.top);
                    expect(clientRegion.right).toBe(region.right);
                    expect(clientRegion.bottom).toBe(region.bottom - scrollbarSize.height);
                    expect(clientRegion.left).toBe(region.left);
                });

                it("should account for both scrollbars", function() {
                    addScrollStyle('x');
                    addScrollStyle('y');

                    var clientRegion = positionable.getClientRegion();

                    expect(clientRegion.top).toBe(region.top);
                    expect(clientRegion.right).toBe(region.right - scrollbarSize.width);
                    expect(clientRegion.bottom).toBe(region.bottom - scrollbarSize.height);
                    expect(clientRegion.left).toBe(region.left);
                });
            });

            describe("getX", function() {
                it("should return the x position", function() {
                    expect(positionable.getX()).toBe(21);
                });
            });

            describe("getXY", function() {
                var xy;

                beforeEach(function() {
                    xy = positionable.getXY();
                });

                it("should return the x position", function() {
                    expect(xy[0]).toBe(21);
                });

                it("should return the y position", function() {
                    expect(xy[1]).toBe(27);
                });
            });

            describe("getY", function() {
                it("should return the y position", function() {
                    expect(positionable.getY()).toBe(27);
                });
            });

            describe("move", function() {
                it("should move left", function() {
                    positionable.move('l', 10);
                    expect(positionable.getXY()).toEqual([11, 27]);
                });

                it("should move right", function() {
                    positionable.move('r', 10);
                    expect(positionable.getXY()).toEqual([31, 27]);
                });

                it("should move top", function() {
                    positionable.move('t', 10);
                    expect(positionable.getXY()).toEqual([21, 17]);
                });

                it("should move bottom", function() {
                    positionable.move('b', 10);
                    expect(positionable.getXY()).toEqual([21, 37]);
                });
            });

            describe("setBox", function() {
                it("should set the box", function() {
                    positionable.setBox({
                        x: 50,
                        y: 60,
                        width: 100,
                        height: 200
                    });
                    expect(positionable.getXY()).toEqual([50, 60]);
                    expect(positionable.el.getWidth()).toBe(100);
                    expect(positionable.el.getHeight()).toBe(200);
                });

                it("should set the region", function() {
                    positionable.setBox(new Ext.util.Region(60, 150, 260, 50));
                    expect(positionable.getXY()).toEqual([50, 60]);
                    expect(positionable.el.getWidth()).toBe(100);
                    expect(positionable.el.getHeight()).toBe(200);

                });
            });

            describe("setLocalX", function() {
                it("should set the local x coordinate to a pixel value", function() {
                    positionable.setLocalX(100);
                    expect(positionable.el.dom.style.left).toBe('100px');
                });

                it("should set the local x coordinate to an auto value", function() {
                    positionable.setLocalX(null);
                    expect(positionable.el.dom.style.left).toBe('auto');
                });
            });

            describe("setLocalXY", function() {
                describe("x and y as separate parameters", function() {
                    it("should set only the local x coordinate to a pixel value", function() {
                        positionable.setLocalXY(100);
                        expect(positionable.el.dom.style.left).toBe('100px');
                        expect(positionable.el.dom.style.top).toBe('7px');
                    });

                    it("should set only the local x coordinate to an auto value", function() {
                        positionable.setLocalXY(null);
                        expect(positionable.el.dom.style.left).toBe('auto');
                        expect(positionable.el.dom.style.top).toBe('7px');
                    });

                    it("should set only the local y coordinate to a pixel value", function() {
                        positionable.setLocalXY(undefined, 100);
                        expect(positionable.el.dom.style.left).toBe('6px');
                        expect(positionable.el.dom.style.top).toBe('100px');
                    });

                    it("should set only the local y coordinate to an auto value", function() {
                        positionable.setLocalXY(undefined, null);
                        expect(positionable.el.dom.style.left).toBe('6px');
                        expect(positionable.el.dom.style.top).toBe('auto');
                    });

                    it("should set pixel x and pixel y", function() {
                        positionable.setLocalXY(100, 200);
                        expect(positionable.el.dom.style.left).toBe('100px');
                        expect(positionable.el.dom.style.top).toBe('200px');
                    });

                    it("should set pixel x and auto y", function() {
                        positionable.setLocalXY(100, null);
                        expect(positionable.el.dom.style.left).toBe('100px');
                        expect(positionable.el.dom.style.top).toBe('auto');
                    });

                    it("should set auto x and pixel y", function() {
                        positionable.setLocalXY(null, 100);
                        expect(positionable.el.dom.style.left).toBe('auto');
                        expect(positionable.el.dom.style.top).toBe('100px');
                    });

                    it("should set auto x and auto y", function() {
                        positionable.setLocalXY(null, null);
                        expect(positionable.el.dom.style.left).toBe('auto');
                        expect(positionable.el.dom.style.top).toBe('auto');
                    });
                });

                describe("x and y as array parameter", function() {
                    it("should set only the local x coordinate to a pixel value", function() {
                        positionable.setLocalXY([100]);
                        expect(positionable.el.dom.style.left).toBe('100px');
                        expect(positionable.el.dom.style.top).toBe('7px');
                    });

                    it("should set only the local x coordinate to an auto value", function() {
                        positionable.setLocalXY([null]);
                        expect(positionable.el.dom.style.left).toBe('auto');
                        expect(positionable.el.dom.style.top).toBe('7px');
                    });

                    it("should set only the local y coordinate to a pixel value", function() {
                        positionable.setLocalXY([undefined, 100]);
                        expect(positionable.el.dom.style.left).toBe('6px');
                        expect(positionable.el.dom.style.top).toBe('100px');
                    });

                    it("should set only the local y coordinate to an auto value", function() {
                        positionable.setLocalXY([undefined, null]);
                        expect(positionable.el.dom.style.left).toBe('6px');
                        expect(positionable.el.dom.style.top).toBe('auto');
                    });

                    it("should set pixel x and pixel y", function() {
                        positionable.setLocalXY([100, 200]);
                        expect(positionable.el.dom.style.left).toBe('100px');
                        expect(positionable.el.dom.style.top).toBe('200px');
                    });

                    it("should set pixel x and auto y", function() {
                        positionable.setLocalXY([100, null]);
                        expect(positionable.el.dom.style.left).toBe('100px');
                        expect(positionable.el.dom.style.top).toBe('auto');
                    });

                    it("should set auto x and pixel y", function() {
                        positionable.setLocalXY([null, 100]);
                        expect(positionable.el.dom.style.left).toBe('auto');
                        expect(positionable.el.dom.style.top).toBe('100px');
                    });

                    it("should set auto x and auto y", function() {
                        positionable.setLocalXY([null, null]);
                        expect(positionable.el.dom.style.left).toBe('auto');
                        expect(positionable.el.dom.style.top).toBe('auto');
                    });
                });
            });

            describe("setLocalY", function() {
                it("should set the local y coordinate to a pixel value", function() {
                    positionable.setLocalY(100);
                    expect(positionable.el.dom.style.top).toBe('100px');
                });

                it("should set the local y coordinate to an auto value", function() {
                    positionable.setLocalY(null);
                    expect(positionable.el.dom.style.top).toBe('auto');
                });
            });

            describe("setX", function() {
                it("should set the x position", function() {
                    positionable.setX(50);
                    expect(positionable.getX()).toBe(50);
                });
            });

            describe("setXY", function() {
                var xy;

                beforeEach(function() {
                    positionable.setXY([50, 60]);
                    xy = positionable.getXY();
                });

                it("should set the x position", function() {
                    expect(xy[0]).toBe(50);
                });

                it("should set the y position", function() {
                    expect(xy[1]).toBe(60);
                });
            });

            describe("setY", function() {
                it("should set the y position", function() {
                    positionable.setY(60);
                    expect(positionable.getY()).toBe(60);
                });
            });
        });
    }

    createSuite(false);

    if (Ext.toolkit === 'classic') {
        createSuite(true);
    }
});
