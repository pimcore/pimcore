topSuite('Ext.Responsive',
    ['Ext.container.Viewport', 'Ext.Responsive', 'Ext.Panel', 'Ext.layout.container.Border'],
function() {
    var Responsive,
        oldGetOrientation, oldGetViewWidth, oldGetViewHeight, oldResponsiveContext,
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
            }
        },
        env;

    beforeEach(function() {
        Responsive = Ext.mixin.Responsive;

        oldGetOrientation = Ext.dom.Element.getOrientation;
        oldGetViewWidth = Ext.dom.Element.getViewportWidth;
        oldGetViewHeight = Ext.dom.Element.getViewportHeight;

        oldResponsiveContext = Responsive.context;

        Ext.dom.Element.getOrientation = function() {
            return env.orientation;
        };

        Ext.dom.Element.getViewportWidth = function() {
            return env.width;
        };

        Ext.dom.Element.getViewportHeight = function() {
            return env.height;
        };
    });

    afterEach(function() {
        Ext.dom.Element.getOrientation = oldGetOrientation;
        Ext.dom.Element.getViewportWidth = oldGetViewWidth;
        Ext.dom.Element.getViewportHeight = oldGetViewHeight;

        Responsive.context = oldResponsiveContext;

        expect(Responsive.active).toBe(false);
        expect(Responsive.count).toBe(0);
    });

    describe('responsive border region', function() {
        var child, panel;

        beforeEach(function() {
            env = environments.ipad.landscape;
            Responsive.context = {
                platform: {
                    tablet: true
                }
            };
        });
        afterEach(function() {
            child = panel = Ext.destroy(panel, child);
        });

        function createPanel() {
            panel = Ext.create({
                xtype: 'panel',
                layout: 'border',
                width: 600,
                height: 600,
                renderTo: Ext.getBody(),
                referenceHolder: true,

                items: [{
                    reference: 'child',
                    title: 'Some Title',

                    responsiveFormulas: {
                        narrow: function(state) {
                            return state.width < 800;
                        }
                    },
                    responsiveConfig: {
                        'width < 800': {
                            region: 'north'
                        },

                        'width >= 800': {
                            region: 'west'
                        },

                        narrow: {
                            title: 'Title - Narrow'
                        },
                        '!narrow': {
                            title: 'Title - Not Narrow'
                        }
                    }
                }, {
                    title: 'Center',
                    region: 'center'
                }]
            });

            child = panel.lookup('child');
        }

        it('respond to size change', function() {
            createPanel();

            expect(child.region).toBe('west');
            expect(child.title).toBe('Title - Not Narrow');

            env = environments.ipad.portrait;
            Responsive.notify();

            expect(child.region).toBe('north');
            expect(child.title).toBe('Title - Narrow');
        });

        describe('creation', function() {
            it('should be created using config object', function() {
                createPanel();

                expect(child.region).toBe('west');

                // tests to make sure plugin didn't set plugin configs
                // onto the component see EXTJS-25719
                expect(child.getId()).not.toBe('responsive');
            });
        });
    });
});
