/* global expect, Ext */

topSuite("Ext.mixin.Responsive", [
        'Ext.Responsive'
    ].concat(
        Ext.isModern
            ? [
                'Ext.viewport.Default',
                'Ext.layout.*',
                'Ext.Panel'
            ]
            : [
                'Ext.container.Viewport',
                'Ext.layout.container.*'
            ]),
function() {
    function stashProps(object, backup, props) {
        for (var i = props.length; i-- > 0; /* empty */) {
            var name = props[i];

            if (name in backup) {
                object[name] = backup[name];
            }
            else {
                delete object[name];
            }
        }
    }

    var Cls, instance, Responsive,
        oldGetOrientation, oldGetViewWidth, oldGetViewHeight,
        environments = {
            ipad: {
                landscape: {
                    width: 1024,
                    height: 768,
                    orientation: 'landscape'
                },
                portrait: {
                    width: 768,
                    height: 1024,
                    orientation: 'portrait'
                }
            },
            iphone: {
                landscape: {
                    width: 480,
                    height: 320,
                    orientation: 'landscape'
                },
                portrait: {
                    width: 320,
                    height: 480,
                    orientation: 'portrait'
                }
            }
        },
        env;

    beforeEach(function() {
        Responsive = Ext.mixin.Responsive;

        oldGetOrientation = Ext.dom.Element.getOrientation;
        oldGetViewWidth = Ext.dom.Element.getViewportWidth;
        oldGetViewHeight = Ext.dom.Element.getViewportHeight;

        Ext.dom.Element.getOrientation = function() {
            return env.orientation;
        };

        Ext.dom.Element.getViewportWidth = function() {
            return env.width;
        };

        Ext.dom.Element.getViewportHeight = function() {
            return env.height;
        };

        Cls = Ext.define(null, {
            mixins: [
                'Ext.mixin.Responsive'
            ],

            config: {
                title: 'Hello',
                bar: null,
                foo: null
            },

            responsiveFormulas: {
                small: 'width < 600',
                medium: 'width >= 600 && width < 800',
                large: 'width >= 800'
            },

            responsiveConfig: {
                small: {
                    bar: 'S'
                },
                medium: {
                    bar: 'M'
                },
                large: {
                    bar: 'L'
                },
                landscape: {
                    title: 'Landscape'
                },
                portrait: {
                    title: 'Portrait'
                }
            },

            constructor: function(config) {
                this.initConfig(config);
            }
        });
    });

    afterEach(function() {
        Ext.dom.Element.getOrientation = oldGetOrientation;
        Ext.dom.Element.getViewportWidth = oldGetViewWidth;
        Ext.dom.Element.getViewportHeight = oldGetViewHeight;

        Cls = null;
        instance = Ext.destroy(instance);

        expect(Responsive.active).toBe(false);
        expect(Responsive.count).toBe(0);
    });

    describe('initialization', function() {
        var backupProps = ['tablet', 'desktop'],
            backup;

        beforeEach(function() {
            env = environments.ipad.landscape;

            backup = {};
            stashProps(Ext.platformTags, backup, backupProps);

            Ext.platformTags.desktop = false;
            Ext.platformTags.tablet = true;
        });

        afterEach(function() {
            stashProps(backup, Ext.platformTags, backupProps);
        });

        it('should init with landscape from class', function() {
            instance = new Cls();

            var title = instance.getTitle();

            expect(title).toBe('Landscape');
        });

        it('should init with landscape from class over instanceConfig', function() {
            instance = new Cls({
                title: 'Foo' // the responsiveConfig will win
            });

            var title = instance.getTitle();

            expect(title).toBe('Landscape');
        });

        it('should init with portrait from class', function() {
            env = environments.ipad.portrait;
            instance = new Cls();

            var title = instance.getTitle();

            expect(title).toBe('Portrait');
        });

        it('should init with wide from instanceConfig', function() {
            instance = new Cls({
                responsiveConfig: {
                    wide: {
                        foo: 'Wide'
                    },
                    tall: {
                        foo: 'Tall'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Wide');
        });

        it('should init with tall from instanceConfig', function() {
            env = environments.ipad.portrait;
            instance = new Cls({
                responsiveConfig: {
                    wide: {
                        foo: 'Wide'
                    },
                    tall: {
                        foo: 'Tall'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Tall');
        });

        it('should init with landscape from instanceConfig', function() {
            instance = new Cls({
                responsiveConfig: {
                    landscape: {
                        title: 'Landscape 2'
                    }
                }
            });

            var title = instance.getTitle();

            expect(title).toBe('Landscape 2'); // instanceConfig wins
        });

        it('should init with portrait not hidden by instanceConfig', function() {
            env = environments.ipad.portrait;
            instance = new Cls({
                responsiveConfig: {
                    landscape: {
                        title: 'Landscape 2'
                    }
                }
            });

            var title = instance.getTitle();

            expect(title).toBe('Portrait'); // not replaced by instanceConfig
        });

        it('should init with platform.tablet from instanceConfig', function() {
            instance = new Cls({
                responsiveConfig: {
                    'platform.tablet': {
                        foo: 'Tablet'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Tablet');
        });

        it('should init with tablet from instanceConfig', function() {
            instance = new Cls({
                responsiveConfig: {
                    tablet: {
                        foo: 'Tablet'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Tablet');
        });

        it('should preserve instanceConfig if responsiveConfig has no match', function() {
            instance = new Cls({
                foo: 'Foo',
                responsiveConfig: {
                    'platform.desktop': { // env is tablet so this is false
                        foo: 'Desktop'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Foo');
        });

        it('should preserve instanceConfig if responsiveConfig has no match w/o prefix', function() {
            instance = new Cls({
                foo: 'Foo',
                responsiveConfig: {
                    desktop: { // env is tablet so this is false
                        foo: 'Desktop'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Foo');
        });

        it('should pick responsiveConfig over instanceConfig', function() {
            instance = new Cls({
                foo: 'Foo',
                responsiveConfig: {
                    'platform.tablet': {
                        foo: 'Tablet'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Tablet');
        });

        it('should pick responsiveConfig over instanceConfig w/o prefix', function() {
            instance = new Cls({
                foo: 'Foo',
                responsiveConfig: {
                    tablet: {
                        foo: 'Tablet'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Tablet');
        });
    }); // initializing

    describe('formulas', function() {
        var backupProps = ['tablet', 'desktop'],
            backup;

        beforeEach(function() {
            env = environments.ipad.landscape;

            backup = {};
            stashProps(Ext.platformTags, backup, backupProps);

            Ext.platformTags.desktop = false;
            Ext.platformTags.tablet = true;
        });

        afterEach(function() {
            stashProps(backup, Ext.platformTags, backupProps);
        });

        it('should init on iPad Landscape using formulas from class', function() {
            instance = new Cls();

            var bar = instance.getBar();

            expect(bar).toBe('L');
        });

        it('should init on iPad Portrait using formulas from class', function() {
            env = environments.ipad.portrait;
            instance = new Cls();

            var bar = instance.getBar();

            expect(bar).toBe('M');
        });

        it('should init on iPhone Portrait using formulas from class', function() {
            env = environments.iphone.portrait;
            instance = new Cls();

            var bar = instance.getBar();

            expect(bar).toBe('S');
        });

    }); // formulas

    describe('dynamic', function() {
        var backupProps = ['tablet', 'desktop'],
            backup;

        beforeEach(function() {
            env = environments.ipad.landscape;

            backup = {};
            stashProps(Ext.platformTags, backup, backupProps);

            Ext.platformTags.desktop = false;
            Ext.platformTags.tablet = true;
        });

        afterEach(function() {
            stashProps(backup, Ext.platformTags, backupProps);
        });

        it('should update when responsive state changes', function() {
            instance = new Cls({
                responsiveConfig: {
                    wide: {
                        foo: 'Wide'
                    },
                    tall: {
                        foo: 'Tall'
                    }
                }
            });

            var foo = instance.getFoo();

            expect(foo).toBe('Wide');

            env = environments.ipad.portrait;
            Responsive.notify();

            foo = instance.getFoo();
            expect(foo).toBe('Tall');
        });

        it('should update formulas when responsive state changes', function() {
            instance = new Cls();

            var bar = instance.getBar();

            expect(bar).toBe('L');

            env = environments.ipad.portrait;
            Responsive.notify();

            bar = instance.getBar();
            expect(bar).toBe('M');
        });
    });

    describe('use by responsive plugin', function() {
        var viewport,
            envWidth;

        beforeEach(function() {
            env = environments.ipad.landscape;
            envWidth = env.width;
        });
        afterEach(function() {
            env.width = envWidth;
            Ext.destroy(viewport);
        });

        /**
         * This test tests reconfiguring container layouts in response to environment
         * changes.
         *
         * There is a main layout (border in classic and dock in modern) which
         * has a button container and a main container.
         *
         * In narrow viewport, the button contains is docked:'top' or region:'north',
         * depending on the toolkit and uses layout: 'hbox'.
         *
         * When the viewport is made narrow, it moves to docked:'left'/region:'west', and switches
         * to layout: 'vbox'.
         */
        it('should update layout configs', function() {
            var viewportConfig = {
                    xtype: 'viewport',

                    responsiveConfig: {
                        modern: {
                            layout: 'auto'
                        },
                        classic: {
                            layout: 'border'
                        }
                    },

                    defaults: {
                        xtype: 'panel',
                        frame: true,
                        margin: 5,
                        bodyPadding: 5
                    },
                    items: [{
                        itemId: 'button-container',
                        region: 'west',
                        title: Ext.isModern ? 'Top Dock' : 'North Region',
                        width: 200,
                        layout: {
                            type: 'box',
                            align: 'stretch'
                        },
                        responsiveConfig: {
                            'width < 600 && modern': {
                                docked: 'top',
                                width: null,
                                title: 'Top Dock, HBox layout',
                                layout: {
                                    vertical: false
                                }
                            },
                            'width < 600 && classic': {
                                region: 'north',
                                width: null,
                                title: 'North Region, HBox layout',
                                layout: {
                                    vertical: false
                                }
                            },
                            'width >= 600 && modern': {
                                docked: 'left',
                                title: 'Left Dock, VBox layout',
                                width: 200,
                                layout: {
                                    vertical: true
                                }
                            },
                            'width >= 600 && classic': {
                                region: 'west',
                                title: 'West Region, VBox layout',
                                width: 200,
                                layout: {
                                    vertical: true
                                }
                            }
                        },
                        items: [{
                            xtype: 'button',
                            text: 'first item'
                        }, {
                            xtype: 'button',
                            text: 'second item'
                        }]
                    }, {
                        itemId: 'center',
                        region: 'center',
                        title: 'Center Region',
                        html: 'Center Body Content',
                        responsiveConfig: {
                            'width < 600': {
                                html: 'Should have a north region using hbox layout'
                            },
                            'width >= 600': {
                                html: 'Should have a west region using vbox layout'
                            }
                        }
                    }]
                },
                buttonContainer, center, haveText;

            viewport = Ext.create(viewportConfig);
            buttonContainer = viewport.down('#button-container');
            center = viewport.down('#center');

            // While we're wide, buttons are in a vbox layout docked left
            expect(buttonContainer.getLayout().getVertical()).toBe(true);

            if (Ext.isClassic) { // TODO
                expect(buttonContainer.region).toBe('west');
                haveText = Ext.String.trim(center.body.dom.innerText);
                expect(haveText).toBe('Should have a west region using vbox layout');
            }
            else {
                expect(buttonContainer.getDocked()).toBe('left');
                expect(center.getHtml()).toBe('Should have a west region using vbox layout');
            }

            // Switch to narrow width, should change the whole arrangement.
            env.width = 400;
            Responsive.notify();

            // Now we're narrow, buttons are in a hbox layout docked top
            expect(buttonContainer.getLayout().getVertical()).toBe(false);

            if (Ext.isClassic) {
                expect(buttonContainer.region).toBe('north');
                haveText = Ext.String.trim(center.body.dom.innerText);
                expect(haveText).toBe('Should have a north region using hbox layout');
            }
            else {
                expect(buttonContainer.getDocked()).toBe('top');
                expect(center.getHtml()).toBe('Should have a north region using hbox layout');
            }
        });
    });
});
