topSuite("Ext.GlobalEvents",
    ['Ext.data.Store', 'Ext.Ajax', 'Ext.data.JsonP', 'Ext.data.proxy.JsonP', 'Ext.TaskManager',
     'Ext.Panel'],
function() {
    describe('idle event', function() {
        var delay = Ext.isIE ? 50 : 10,
            store, loadSpy, idleSpy,
            forumData = {
                "totalCount": "6679",
                "topics": [
                    {
                        "title": "XTemplate with in EditorGridPanel",
                        "threadid": "133690",
                        "username": "kpr@emco",
                        "userid": "272497",
                        "dateline": "1305604761",
                        "postid": "602876",
                        "forumtitle": "Ext 3.x: Help",
                        "forumid": "40",
                        "replycount": "2",
                        "lastpost": "1305857807",
                        "excerpt": ""
                    },
                    {
                        "title": "IFrame error  &quot;_flyweights is undefined&quot;",
                        "threadid": "133571",
                        "username": "Daz",
                        "userid": "52119",
                        "dateline": "1305533577",
                        "postid": "602456",
                        "forumtitle": "Ext 3.x: Help",
                        "forumid": "40",
                        "replycount": "1",
                        "lastpost": "1305857313",
                        "excerpt": ""
                    }
                ]
            };

        function completeRequest(url, data) {
            Ext.data.JsonP.mockComplete(url, data);
        }

        beforeEach(function() {
            MockAjaxManager.addMethods();
            loadSpy = jasmine.createSpy('store load');
            idleSpy = jasmine.createSpy('idle');

            Ext.on('idle', idleSpy);
        });

        afterEach(function() {
            Ext.un('idle', idleSpy);

            if (store) {
                store.destroy();
            }

            MockAjaxManager.removeMethods();
            loadSpy = idleSpy = store = null;
        });

        it("should fire after DOM event handler are invoked, but before control is returned to the browser", function() {
            var element = Ext.getBody().createChild(),
                mousedownSpy = jasmine.createSpy('mousedown');

            // attach a couple mousedown listeners, the idle event should fire after both
            // handlers have fired
            element.on('mousedown', mousedownSpy);
            element.on('mousedown', function() {
                mousedownSpy();
            });

            Ext.testHelper.touchStart(element);

            expect(mousedownSpy.callCount).toBe(2);
            expect(idleSpy).toHaveBeenCalled();

            Ext.testHelper.touchCancel(element);

            element.destroy();
        });

        it("should fire after a JsonPProxy processes a return packet", function() {
            store = Ext.create('Ext.data.Store', {
                asynchronousLoad: false,
                proxy: {
                    type: 'jsonp',
                    reader: {
                        rootProperty: 'topics',
                        totalProperty: 'totalCount'
                    },
                    url: 'fakeForumUrl'
                },
                fields: ['title'],
                listeners: {
                    load: loadSpy
                }
            });

            store.loadPage(1);
            completeRequest('fakeForumUrl', forumData);

            waitForSpy(loadSpy);

            waits(delay);

            runs(function() {
                expect(idleSpy).toHaveBeenCalled();
            });
        });

        it("should fire after a JsonP request is processed", function() {
            var request = Ext.data.JsonP.request({
                url: 'fakeRequest',
                callback: loadSpy
            });

            completeRequest(request, forumData);

            waitForSpy(loadSpy);

            waits(delay);

            runs(function() {
                expect(idleSpy).toHaveBeenCalled();
            });
        });

        it("should fire after an Ajax request is processed", function() {
            var request = Ext.Ajax.request({
                url: 'fakeUrl',
                callback: loadSpy
            });

            Ext.Ajax.mockCompleteWithData({}, request.id);

            waitForSpy(loadSpy);

            waits(delay);

            runs(function() {
                expect(idleSpy).toHaveBeenCalled();
            });
        });

        it("should fire after a scheduled Task is run", function() {
            Ext.TaskManager.newTask({
                run: loadSpy,
                repeat: 1,
                interval: 1
            }).start();

            waitForSpy(loadSpy);

            waits(delay);

            runs(function() {
                expect(idleSpy).toHaveBeenCalled();
            });
        });
    });

    (Ext.isClassic ? xdescribe : describe)("pressedComponent", function() {
        var button;

        beforeEach(function() {
            button = new Ext.Button({
                renderTo: Ext.getBody(),
                text: 'foo'
            });
        });

        afterEach(function() {
            button = Ext.destroy(button);
        });

        it("should capture the pressed component on mousedown", function() {
            jasmine.fireMouseEvent(button.el, 'mousedown');

            expect(Ext.GlobalEvents.pressedComponent).toBe(button);

            jasmine.fireMouseEvent(button.el, 'mouseup');
        });

        it("should call onRelease method when mouse is released", function() {
            var event,
                spy = spyOn(button, 'onRelease');

            spy.andCallFake(function(e) {
                event = e;
            });

            jasmine.fireMouseEvent(button.el, 'click');

            expect(spy).toHaveBeenCalled();
            expect(event.type).toBe('mouseup');
            expect(event.target).toBe(button.el.dom);
        });
    });

    describe('scroll event', function() {
        var stretcher,
            scrollingPanel,
            scrolledElements = [];

        function onGlobalScroll(scroller) {
            // Check for duplicates because on iOS a single call to scrollBy can trigger multiple scroll events
            var element = scroller.getElement();

            if (!Ext.Array.contains(scrolledElements, element)) {
                scrolledElements.push(element);
            }
        }

        afterEach(function() {
            Ext.un('scroll', onGlobalScroll);
            scrolledElements = [];

            stretcher.destroy();
            scrollingPanel.destroy();
        });

        it('should fire the global scroll event whenever anything scrolls', function() {
            var viewportScrollEndSpy = jasmine.createSpy('viewport scrollend spy'),
                panelScrollEndSpy = jasmine.createSpy('scrollingPanel scrollend spy');

            stretcher = Ext.getBody().createChild({
                style: 'height:10000px'
            });

            // Use Ext.Panel class - it will work in Classic and Modern
            scrollingPanel = new Ext.Panel({
                renderTo: document.body,
                floating: true,
                left: 0,
                top: 0,
                width: 300,
                height: 300,

                // Modern defaults to 'card', so explicitly use 'auto'
                layout: 'auto',
                scrollable: true,
                items: {
                    xtype: 'component',
                    style: 'height:1000px'
                }
            });

            // Record all scroll events
            Ext.on({
                scroll: onGlobalScroll
            });

            var viewportScroller = Ext.getViewportScroller();

            viewportScroller.on({
                scrollend: viewportScrollEndSpy
            });

            viewportScroller.scrollBy(null, 100);

            // Wait for scroll events to fire (may be async)
            waitsForSpy(viewportScrollEndSpy);

            runs(function() {
                expect(scrolledElements.length).toBe(1);
                expect(scrolledElements[0]).toBe(Ext.scroll.Scroller.viewport.getElement());
            });

            scrollingPanel.getScrollable().on({
                scrollend: panelScrollEndSpy
            });

            runs(function() {
                scrollingPanel.getScrollable().scrollBy(null, 100);
            });

            // Wait for scroll events to fire (may be async)
            waitsForSpy(panelScrollEndSpy);

            runs(function() {
                expect(scrolledElements.length).toBe(2);
                expect(scrolledElements[1]).toBe(scrollingPanel.getScrollable().getElement());
            });
        });
    });
});
