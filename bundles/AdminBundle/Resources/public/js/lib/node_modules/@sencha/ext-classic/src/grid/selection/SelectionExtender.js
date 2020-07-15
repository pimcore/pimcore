Ext.define('Ext.grid.selection.SelectionExtender', {
    extend: 'Ext.dd.DragTracker',

    maskBox: {},

    constructor: function(config) {
        var me = this;

        // We can only initialize properly if there are elements to work with
        if (config.view.rendered) {
            me.initSelectionExtender(config);
        }
        else {
            me.view = config.view;
            config.view.on({
                render: me.initSelectionExtender,
                args: [config],
                scope: me
            });
        }
    },

    initSelectionExtender: function(config) {
        var me = this,
            displayMode = Ext.dom.Element.DISPLAY;

        me.el = config.view.el;

        me.handle = config.view.ownerGrid.body.createChild({
            cls: Ext.baseCSSPrefix + 'ssm-extender-drag-handle',
            style: 'display:none'
        }).setVisibilityMode(displayMode);

        me.handle.on({
            contextmenu: function(e) {
                e.stopEvent();
            }
        });

        me.mask = me.el.createChild({
            cls: Ext.baseCSSPrefix + 'ssm-extender-mask',
            style: 'display:none'
        }).setVisibilityMode(displayMode);

        me.superclass.constructor.call(me, config);

        // Mask and andle must survive being orphaned
        me.mask.skipGarbageCollection = me.handle.skipGarbageCollection = true;

        me.viewListeners = me.view.on({
            scroll: me.onViewScroll,
            scope: me,
            destroyable: true
        });

        me.gridListeners = me.view.ownerGrid.on({
            columnResize: me.alignHandle,
            scope: me,
            destroyable: true
        });

        me.extendX = !!(me.axes & 1);
        me.extendY = !!(me.axes & 2);
    },

    setHandle: function(firstPos, lastPos) {
        var me = this;

        if (!me.view.rendered) {
            me.view.on({
                render: me.initSelectionExtender,
                args: [firstPos, lastPos],
                scope: me
            });

            return;
        }

        me.firstPos = firstPos;
        me.lastPos = lastPos;

        // If we've done a "select all rows" and there is buffered rendering, then
        // the cells might not be rendered, so we can't activate the replicator.
        if (firstPos && lastPos && firstPos.getCell(true) && lastPos.getCell(true)) {
            if (me.curPos) {
                me.curPos.setPosition(lastPos);
            }
            else {
                me.curPos = lastPos.clone();
            }

            // Align centre of handle with bottom-right corner of last cell if possible.
            me.alignHandle();
        }
        else {
            me.disable();
        }
    },

    alignHandle: function() {
        var me = this,
            firstCell = me.firstPos && me.firstPos.getCell(true),
            lastCell = me.lastPos && me.lastPos.getCell(true),
            handle = me.handle,
            shouldDisplay;

        // Cell corresponding to the position might not be rendered.
        // This will be called upon scroll
        if (firstCell && lastCell) {
            me.enable();
            handle.alignTo(lastCell, 'c-br');

            shouldDisplay = me.isHandleWithinView(Ext.fly(lastCell).up('.x-grid-view'));
            handle.setVisible(shouldDisplay);
        }
        else {
            me.disable();
        }
    },

    isHandleWithinView: function(view) {
        var me = this,
            viewBox = view.getBox(),
            handleBox = me.handle.getBox(),
            withinX;

        withinX = viewBox.left <= handleBox.left &&
                  viewBox.right >= (handleBox.right - handleBox.width);

        return withinX;
    },

    enable: function() {
        this.handle.show();
        this.callParent();
    },

    disable: function() {
        this.handle.hide();
        this.mask.hide();
        this.callParent();
    },

    onDrag: function(e) {
        // pointer-events-none is not supported on IE10m.
        // So if shrinking the extension zone, the mousemove target may be the mask.
        // We have to retarget on the cell *below* that.
        if (e.target === this.mask.dom) {
            this.mask.hide();
            e.target = document.elementFromPoint.apply(document, e.getXY());
            this.mask.show();
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            view = me.view,
            viewTop = view.el.getY(),
            viewLeft = view.el.getX(),
            overCell = e.getTarget(me.view.getCellSelector()),
            scrollTask = me.scrollTask || (me.scrollTask = Ext.util.TaskManager.newTask({
                run: me.doAutoScroll,
                scope: me,
                interval: 10
            })),
            scrollBy = me.scrollBy || (me.scrollBy = []);

        // Dragged outside the view; stop scrolling.
        if (!me.el.contains(e.target)) {
            scrollBy[0] = scrollBy[1] = 0;

            return scrollTask.stop();
        }

        // Neart bottom of view
        if (me.lastXY[1] > viewTop + view.el.getHeight(true) - 15) {
            if (me.extendY) {
                scrollBy[1] = 3;
                scrollTask.start();
            }
        }

        // Near top of view
        else if (me.lastXY[1] < viewTop + 10) {
            if (me.extendY) {
                scrollBy[1] = -3;
                scrollTask.start();
            }
        }

        // Near right edge of view
        else if (me.lastXY[0] > viewLeft + view.el.getWidth(true) - 15) {
            if (me.extendX) {
                scrollBy[0] = 3;
                scrollTask.start();
            }
        }

        // Near left edge of view
        else if (me.lastXY[0] < viewLeft + 10) {
            if (me.extendX) {
                scrollBy[0] = -3;
                scrollTask.start();
            }
        }

        // Not near an edge, cancel autoscrolling
        else {
            scrollBy[0] = scrollBy[1] = 0;
            scrollTask.stop();
        }

        if (overCell && overCell !== me.lastOverCell) {
            me.lastOverCell = overCell;
            me.syncMaskOnCell(overCell);
        }
    },

    doAutoScroll: function() {
        var me = this,
            view = me.view,
            scrollOverCell;

        // Bump the view in whatever direction was decided in the onDrag method.
        view.scrollBy.apply(view, me.scrollBy);

        // Mouseover does not fire on autoscroll so see where the mouse is over on each scroll
        scrollOverCell = document.elementFromPoint.apply(document, me.lastXY);

        if (scrollOverCell) {
            scrollOverCell = Ext.fly(scrollOverCell).up(view.cellSelector);

            if (scrollOverCell && scrollOverCell !== me.lastOverCell) {
                me.lastOverCell = scrollOverCell;
                me.syncMaskOnCell(scrollOverCell);
            }
        }
    },

    onEnd: function(e) {
        var me = this;

        if (me.scrollTask) {
            me.scrollTask.stop();
        }

        if (me.extensionDescriptor) {
            me.disable();
            me.view.getSelectionModel().extendSelection(me.extensionDescriptor);
        }
    },

    onViewScroll: function() {
        var me = this;

        // If being dragged
        if (me.active && me.lastOverCell) {
            me.syncMaskOnCell(me.lastOverCell);
        }

        // We have been applied to a selection block
        if (me.firstPos) {

            // Align centre of handle with bottom-right corner of last cell if possible.
            me.alignHandle();
        }
    },

    syncMaskOnCell: function(overCell) {
        var me = this,
            view = me.view,
            rows = view.all,
            curPos = me.curPos,
            maskBox = me.maskBox,
            selRegion,
            firstPos = me.firstPos.clone(),
            lastPos = me.lastPos.clone(),
            extensionStart = me.firstPos.clone(),
            extensionEnd = me.lastPos.clone(),
            preventReduce = !me.allowReduceSelection;

        // Constrain cell positions to be within rendered range.
        firstPos.setRow(Math.min(Math.max(firstPos.rowIdx, rows.startIndex), rows.endIndex));
        lastPos.setRow(Math.min(Math.max(lastPos.rowIdx, rows.startIndex), rows.endIndex));

        me.selectionRegion = selRegion =
            firstPos.getCell().getRegion().union(lastPos.getCell().getRegion());

        curPos.setPosition(view.getRecord(overCell), view.getHeaderByCell(overCell));

        // The above calls require the cell to be a DOM reference
        overCell = Ext.fly(overCell);

        // Reset border to default, which is the overall border setting from SASS
        // We disable the border which is contiguous to the selection.
        me.mask.dom.style.borderTopWidth = me.mask.dom.style.borderRightWidth =
            me.mask.dom.style.borderBottomWidth = me.mask.dom.style.borderLeftWidth = '';

        // Dragged above the selection
        if (curPos.rowIdx < me.firstPos.rowIdx && me.extendY) {
            me.extensionDescriptor = {
                type: 'rows',
                start: extensionStart.setRow(curPos.rowIdx),
                end: extensionEnd.setRow(me.firstPos.rowIdx - 1),
                rows: curPos.rowIdx - me.firstPos.rowIdx,
                mousePosition: me.lastXY
            };
            me.mask.dom.style.borderBottomWidth = '0';
            maskBox.x = selRegion.x;
            maskBox.y = overCell.getY();
            maskBox.width = selRegion.right - selRegion.left;
            maskBox.height = selRegion.top - overCell.getY();
        }
        // Dragged below selection
        else if (curPos.rowIdx > me.lastPos.rowIdx && me.extendY) {
            me.extensionDescriptor = {
                type: 'rows',
                start: extensionStart.setRow(me.lastPos.rowIdx + 1),
                end: extensionEnd.setRow(curPos.rowIdx),
                rows: curPos.rowIdx - me.lastPos.rowIdx,
                mousePosition: me.lastXY
            };
            me.mask.dom.style.borderTopWidth = '0';
            maskBox.x = selRegion.x;
            maskBox.y = selRegion.bottom;
            maskBox.width = selRegion.right - selRegion.left;
            maskBox.height = overCell.getRegion().bottom - selRegion.bottom;
        }
        // reducing Y selection dragged from the bottom
        else if (!preventReduce && curPos.rowIdx < me.lastPos.rowIdx && me.extendY &&
                 curPos.colIdx === me.lastPos.colIdx) {
            me.extensionDescriptor = {
                type: 'rows',
                start: extensionStart.setRow(me.firstPos.rowIdx),
                end: extensionEnd.setRow(curPos.rowIdx),
                rows: -1,
                mousePosition: me.lastXY,
                reduce: true
            };

            me.mask.dom.style.borderTopWidth = '0';
            maskBox.x = selRegion.x;
            maskBox.y = selRegion.top;
            maskBox.width = selRegion.right - selRegion.left;
            maskBox.height = overCell.getRegion().bottom - selRegion.top;
        }

        // row position is within selected row range
        else {
            // Dragged to left of selection
            if (curPos.colIdx < me.firstPos.colIdx && me.extendX) {
                me.extensionDescriptor = {
                    type: 'columns',
                    start: extensionStart.setColumn(curPos.colIdx),
                    end: extensionEnd.setColumn(me.firstPos.colIdx - 1),
                    columns: curPos.colIdx - me.firstPos.colIdx,
                    mousePosition: me.lastXY
                };
                me.mask.dom.style.borderRightWidth = '0';
                maskBox.x = overCell.getX();
                maskBox.y = selRegion.top;
                maskBox.width = selRegion.left - overCell.getRegion().left;
                maskBox.height = selRegion.bottom - selRegion.top;
            }
            // Dragged to right of selection
            else if (curPos.colIdx > me.lastPos.colIdx && me.extendX) {
                me.extensionDescriptor = {
                    type: 'columns',
                    start: extensionStart.setColumn(me.lastPos.colIdx + 1),
                    end: extensionEnd.setColumn(curPos.colIdx),
                    columns: curPos.colIdx - me.lastPos.colIdx,
                    mousePosition: me.lastXY
                };
                me.mask.dom.style.borderLeftWidth = '0';
                maskBox.x = selRegion.right;
                maskBox.y = selRegion.top;
                maskBox.width = overCell.getRegion().right - selRegion.right;
                maskBox.height = selRegion.bottom - selRegion.top;
            }
            // reducing X selection dragged from the right
            else if (!preventReduce && curPos.colIdx < me.lastPos.colIdx && me.extendX) {
                me.extensionDescriptor = {
                    type: 'columns',
                    start: extensionStart.setColumn(me.firstPos.colIdx),
                    end: extensionEnd.setColumn(curPos.colIdx),
                    columns: -1,
                    mousePosition: me.lastXY,
                    reduce: true
                };
                me.mask.dom.style.borderLeftWidth = '0';
                maskBox.x = selRegion.left;
                maskBox.y = selRegion.top;
                maskBox.width = overCell.getRegion().right - selRegion.left;
                maskBox.height = selRegion.bottom - selRegion.top;
            }
            else {
                me.extensionDescriptor = null;
            }
        }

        if (view.ownerGrid.hasListeners.selectionextenderdrag) {
            view.ownerGrid.fireEvent(
                'selectionextenderdrag', view.ownerGrid, view.getSelectionModel().getSelected(),
                me.extensionDescriptor
            );
        }

        if (me.extensionDescriptor) {
            me.mask.show();
            me.mask.setBox(maskBox);
        }
        else {
            me.mask.hide();
        }
    },

    destroy: function() {
        var me = this;

        Ext.destroy(me.gridListeners, me.viewListeners, me.mask, me.handle);
        me.callParent();
    }
});
