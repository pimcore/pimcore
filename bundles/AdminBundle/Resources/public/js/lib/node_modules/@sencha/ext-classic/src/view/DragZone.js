/**
 * @private
 */
Ext.define('Ext.view.DragZone', {
    extend: 'Ext.dd.DragZone',

    containerScroll: false,

    constructor: function(config) {
        var me = this,
            view, ownerCt, el;

        Ext.apply(me, config);

        // Create a ddGroup unless one has been configured.
        // User configuration of ddGroups allows users to specify which
        // DD instances can interact with each other. Using one
        // based on the id of the View would isolate it and mean it can only
        // interact with a DropZone on the same View also using a generated ID.
        if (!me.ddGroup) {
            me.ddGroup = 'view-dd-zone-' + me.view.id;
        }

        // Ext.dd.DragDrop instances are keyed by the ID of their encapsulating element.
        // So a View's DragZone cannot use the View's main element because the DropZone
        // must use that because the DropZone may need to scroll on hover at a scrolling boundary,
        // and it is the View's main element which handles scrolling.
        // We use the View's parent element to drag from. Ideally, we would use the internal
        // structure, but that is transient; DataViews recreate the internal structure dynamically
        // as data changes.
        // TODO: Ext 5.0 DragDrop must allow multiple DD objects to share the same element.
        view = me.view;

        // This is for https://www.w3.org/TR/pointerevents/ platforms.
        // On these platforms, the pointerdown event (single touchstart) is reserved for
        // initiating a scroll gesture. Setting the items draggable defeats that and
        // enables the touchstart event to trigger a drag.
        //
        // Two finger dragging will still scroll on these platforms.
        view.setItemsDraggable(true);

        ownerCt = view.ownerCt;

        // We don't just grab the parent el, since the parent el may be
        // some el injected by the layout
        if (ownerCt) {
            el = ownerCt.getTargetEl().dom;
        }
        else {
            el = view.el.dom.parentNode;
        }

        me.callParent([el]);

        me.ddel = document.createElement('div');
        me.ddel.className = Ext.baseCSSPrefix + 'grid-dd-wrap';
    },

    init: function(id, sGroup, config) {
        var me = this,
            eventSpec = {
                itemmousedown: me.onItemMouseDown,
                scope: me
            };

        // If there may be ambiguity with touch/swipe to scroll and a drag gesture
        // trigger drag start on longpress and a *real* mousedown.
        if (Ext.supports.Touch) {
            eventSpec.itemlongpress = me.onItemLongPress;

            // Longpress fires contextmenu in some touch platforms, so if we are using longpress
            // inhibit the contextmenu on this element
            eventSpec.contextmenu = {
                element: 'el',
                fn: me.onViewContextMenu
            };
        }

        me.initTarget(id, sGroup, config);
        me.view.mon(me.view, eventSpec);
    },

    onValidDrop: function(target, e, id) {
        this.callParent([target, e, id]);

        // focus the view that the node was dropped onto so that keynav will be enabled.
        if (!target.el.contains(Ext.Element.getActiveElement())) {
            target.el.focus();
        }
    },

    onViewContextMenu: function(e) {
        if (e.pointerType !== 'mouse') {
            e.preventDefault();
        }
    },

    onItemMouseDown: function(view, record, item, index, e) {
        // Ignore touchstart.
        // For touch events, we use longpress.
        if (e.pointerType === 'mouse') {
            this.onTriggerGesture(view, record, item, index, e);
        }
    },

    onItemLongPress: function(view, record, item, index, e) {
        // Ignore long mousedowns.
        // The initial mousedown started the drag.
        // For touch events, we use longpress.
        if (e.pointerType !== 'mouse') {
            this.onTriggerGesture(view, record, item, index, e);
        }
    },

    onTriggerGesture: function(view, record, item, index, e) {
        var navModel;

        // Only respond to longpress for touch dragging.
        // Reject drag start if mousedown is on the actionable cell of a grid view
        if ((e.pointerType === 'touch' && e.type !== 'longpress') ||
            (e.position && e.position.isEqual(e.view.actionPosition))) {
            return;
        }

        if (!this.isPreventDrag(e, record, item, index)) {
            navModel = view.getNavigationModel();

            // Since handleMouseDown prevents the default behavior of the event, which
            // is to focus the view, we focus the view now.  This ensures that the view
            // remains focused if the drag is cancelled, or if no drag occurs.
            //
            // A Table event will have a position property which is a CellContext
            if (e.position) {
                navModel.setPosition(e.position);
            }
            // Otherwise, just use the item index
            else {
                navModel.setPosition(index);
            }

            this.handleMouseDown(e);
        }
    },

    /**
     * @protected
     * Template method called upon mousedown. May be overridden in subclasses, or configured
     * into an instance.
     *
     * Return `true` to prevent drag start.
     * @param {Ext.event.Event} e The mousedown event.
     * @param {Ext.data.Model} record The record mousedowned upon.
     * @param {HTMLElement} item The grid row mousedowned upon.
     * @param {Number} index The row number mousedowned upon.
     */
    isPreventDrag: function(e, record, item, index) {
        return !!e.isInputFieldEvent;
    },

    getDragData: function(e) {
        var view = this.view,
            item = e.getTarget(view.getItemSelector());

        if (item) {
            return {
                copy: view.copy || (view.allowCopy && e.ctrlKey),
                event: e,
                view: view,
                ddel: this.ddel,
                item: item,
                records: view.getSelectionModel().getSelection(),
                fromPosition: Ext.fly(item).getXY()
            };
        }
    },

    onInitDrag: function(x, y) {
        var me = this,
            data = me.dragData,
            view = data.view,
            selectionModel = view.getSelectionModel(),
            record = view.getRecord(data.item);

        // Update the selection to match what would have been selected if the user had
        // done a full click on the target node rather than starting a drag from it
        if (!selectionModel.isSelected(record)) {
            selectionModel.selectWithEvent(record, me.DDMInstance.mousedownEvent);
        }

        data.records = selectionModel.getSelection();

        Ext.fly(me.ddel).setHtml(me.getDragText());
        me.proxy.update(me.ddel);
        me.onStartDrag(x, y);

        return true;
    },

    getDragText: function() {
        var count = this.dragData.records.length;

        return Ext.String.format(this.dragText, count, count === 1 ? '' : 's');
    },

    getRepairXY: function(e, data) {
        return data ? data.fromPosition : false;
    }
});
