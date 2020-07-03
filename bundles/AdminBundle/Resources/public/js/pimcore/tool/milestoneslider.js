Ext.define('Ext.pimcore.slider.Milestone', {
    extend: 'Ext.slider.Multi',

    requires: [
        'Ext.slider.Multi'
    ],

    startDate: null,
    thumbsToRender: [],
    activeThumb: null,

    initComponent: function() {
        this.useTips = true;
        this.tipText = function(thumb){
            var date = new Date(thumb.value * 1000);
            return Ext.Date.format(date, 'H:i');
        };

        this.callParent();

        if(this.startDate) {
            this.initDate(this.startDate);
        }

        this.increment = 20;
        this.thumbs = [];
        this.constrainThumbs = false;
        this.clickToChange = false;

        this.initDefaultListeners();
    },

    initDefaultListeners: function() {

        this.addListener('change', function(slider, newValue, thumb, eOpts) {
            if(typeof thumb.moveCallback === "function") {
                thumb.moveCallback(newValue);
            }

        });

        this.addListener('render', function() {
            for(var i = 0; i < this.thumbsToRender.length; i++) {
                this.attachListenersToThumb(this.thumbsToRender[i]);
            }

            this.thumbsToRender = [];
            if(this.activeThumb) {
                this.activateThumb(this.activeThumb);
            }
        }.bind(this));

    },

    initDate: function(date) {
        this.removeThumbs();

        this.startDate = date;
        var startDate = date.getTime() / 1000;
        this.setMinValue(startDate);
        this.setMaxValue(startDate + 86399);


    },

    addTimestamp: function(timestamp, key, moveCallback, clickCallback, deleteCallback) {
        var thumb = this.addThumb(timestamp);
        thumb.key = key;
        thumb.moveCallback = moveCallback;
        thumb.deleteCallback = deleteCallback;
        thumb.clickCallback = clickCallback;

        if(this.rendered) {
            this.attachListenersToThumb(thumb);
        } else {
            this.thumbsToRender.push(thumb);
        }

        return thumb;
    },

    attachListenersToThumb: function(thumb) {

        var domElement = thumb.el;

        if(typeof thumb.clickCallback === "function") {
            domElement.on('click', function(thumb) {
                this.activateThumb(thumb);
            }.bind(this, thumb));
        }

        domElement.on("contextmenu", function (e) {
            var menu = new Ext.menu.Menu();
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (thumb, item) {
                    thumb.deleteCallback(thumb.key);
                    this.removeThumb(thumb);
                }.bind(this, thumb)
            }));
            menu.showAt(e.getXY());
            menu.setZIndex(20000);

            e.stopEvent();
        }.bind(this));

    },

    activateThumb: function(thumb) {
        this.activeThumb = thumb;
        if(this.rendered) {
            for(var i = 0; i < this.thumbs.length; i++) {
                if(this.thumbs[i].el) {
                    this.thumbs[i].el.removeCls('selected');
                }
            }
            thumb.el.addCls('selected');
            thumb.clickCallback(thumb.key);
        }
    },

    activateThumbByValue: function(value) {
        for(var i = 0; i < this.thumbs.length; i++) {
            if(this.thumbs[i].value == value) {
                this.activateThumb(this.thumbs[i]);
                return;
            }
        }
    },

    removeThumbs: function() {
        this.thumbsToRender = [];
        for(var i = 0; i < this.thumbs.length; i++) {
            this.thumbs[i].destroy();
        }
        this.thumbs = [];
        this.thumbStack = null;
    },

    removeThumb: function(thumb) {
        thumb.destroy();

        var index = this.thumbs.indexOf(thumb);
        if (index !== -1) {
            this.thumbs.splice(index, 1);
        }
    },

    //needs to be overwritten in order to handle no thumbs available
    onClickChange : function(trackPoint) {
        var me = this,
            thumb, index;

        // How far along the track *from the origin* was the click.
        // If vertical, the origin is the bottom of the slider track.

        //find the nearest thumb to the click event
        thumb = me.getNearest(trackPoint);
        if (thumb && !thumb.disabled) {
            index = thumb.index;
            me.setValue(index, Ext.util.Format.round(me.reversePixelValue(trackPoint), me.decimalPrecision), undefined, true);
        }
    }
});