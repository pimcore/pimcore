/**
 * @private
 *
 * This class is used only by the grid's HeaderContainer docked child.
 *
 * It adds the ability to shrink the vertical size of the inner container element back if a grouped
 * column header has all its child columns dragged out, and the whole HeaderContainer needs
 * to shrink back down.
 *
 * Also, after every layout, after all headers have attained their 'stretchmax' height,
 * it goes through and calls `setPadding` on the columns so that they lay out correctly.
 */
Ext.define('Ext.grid.ColumnLayout', {
    extend: 'Ext.layout.container.HBox',
    alias: 'layout.gridcolumn',
    type: 'gridcolumn',

    requires: [
        'Ext.panel.Table'
    ],

    firstHeaderCls: Ext.baseCSSPrefix + 'column-header-first',
    lastHeaderCls: Ext.baseCSSPrefix + 'column-header-last',

    initLayout: function() {
        this.callParent();

        if (this.scrollbarWidth === undefined) {
            this.self.prototype.scrollbarWidth = Ext.scrollbar.width();
        }
    },

    beginLayout: function(ownerContext) {
        var me = this,
            owner = me.owner,
            firstCls = me.firstHeaderCls,
            lastCls = me.lastHeaderCls,
            bothCls = [firstCls, lastCls],
            items = me.getVisibleItems(),
            len = items.length,
            i, item;

        me.callParent([ ownerContext ]);

        // Sync the first/lastCls states for all the headers.
        for (i = 0; i < len; i++) {
            item = items[i];

            if (len === 1) {
                // item is the only item so it is both first and last
                item.addCls(bothCls);
            }
            else if (i === 0) {
                // item is the first of 2+ items
                item.addCls(firstCls);
                item.removeCls(lastCls);
            }
            else if (i === len - 1) {
                // item is the last of 2+ items
                item.removeCls(firstCls);
                item.addCls(lastCls);
            }
            else {
                item.removeCls(bothCls);
            }
        }

        // Start this at 0 and for the root headerCt call determineScrollbarWidth to get
        // it set properly. Typically that amounts to a "delete" to expose the system's
        // scrollbar width stored on our prototype.
        me.scrollbarWidth = 0;

        if (owner.isRootHeader && !owner.grid.isLocked) {
            // In a locking grid, the scrollbar is only managed on the normal side.
            me.determineScrollbarWidth(ownerContext);
        }

        if (!me.scrollbarWidth) {
            // By default Mac OS X has overlay scrollbars that do not take space, but also
            // the RTL override may have set this to 0... so make sure we don't try to
            // compensate for a scrollbar when there isn't one.
            ownerContext.manageScrollbar = false;
        }
    },

    moveItemBefore: function(item, before) {
        var prevOwner = item.ownerCt,
            nextSibling = before && before.nextSibling();

        // Due to the nature of grid headers, index calculation for
        // moving items is complicated, especially since removals can trigger
        // groups to be removed (and thus alter indexes). As such, the logic
        // is simplified by removing the item first, then calculating the index
        // and inserting it.
        // When removing from previous container ensure the header is not destroyed
        // or removed from the DOM (which would destroy focus).
        // The layout's moveItem method will preserve focus when it does the move.
        if (item !== before && prevOwner) {
            prevOwner.remove(item, {
                destroy: false,
                detach: false
            });

            // If the removal caused destruction of the before, this was
            // the last subheader, so move to beore its next sibling
            if (before && before.destroyed) {
                before = nextSibling;
            }
        }

        return this.callParent([item, before]);
    },

    determineScrollbarWidth: function(ownerContext) {
        var me = this,
            owner = me.owner,
            grid = owner.grid,
            // We read this value off of the immediate grid since the locked side of a
            // locking grid will not have this set. The ownerGrid in that case would have
            // it set but will pass along true only to the normal side.
            reserveScrollbar = grid.reserveScrollbar,
            scrollable = grid.view.getScrollable(),
            manageScrollbar = !reserveScrollbar && scrollable && scrollable.getY();

        // If we have reserveScrollbar then we will always have a vertical scrollbar so
        // manageScrollbar should be false. Otherwise it is based on overflow-y:
        ownerContext.manageScrollbar = manageScrollbar;

        // Determine if there is any need to deal with the width of the vertical scrollbar
        // and set "scrollbarWidth" to 0 if not or the system determined value (stored on
        // our prototype).
        //
        if (!grid.ownerGrid.collapsed && (reserveScrollbar || manageScrollbar)) {
            // Ensure the real scrollbarWidth value is exposed from the prototype. This
            // may be needed if the scrollFlags have changed since we may have a 0 set on
            // this instance from a previous layout run.
            delete me.scrollbarWidth;
        }

        // On return, the RTL override (Ext.rtl.grid.ColumnLayout) will deal with various
        // browser bugs and may set me.scrollbarWidth to 0 or a negative value.
    },

    calculate: function(ownerContext) {
        var me = this,
            owner = me.owner,
            grid = owner.grid,
            // Our TableLayout buddy sets this in its beginLayout so we can work this
            // out together:
            viewContext = ownerContext.viewContext,
            state = ownerContext.state,
            context = ownerContext.context,
            lockingPartnerContext,
            columnsChanged, columns, len, i, column, scrollbarAdjustment, viewOverflowY;

        me.callParent([ ownerContext ]);

        if (grid && owner.isRootHeader && state.parallelDone) {
            lockingPartnerContext = viewContext.lockingPartnerContext;

            // A force-fit needs to be "reflexed" so check that now. If we have to reflex
            // the items, we need to re-cacheFlexes and invalidate ourselves.
            if (grid.forceFit && !state.reflexed) {
                if (me.convertWidthsToFlexes(ownerContext)) {
                    me.cacheFlexes(ownerContext);
                    me.done = false;

                    ownerContext.invalidate({
                        state: {
                            reflexed: true,
                            scrollbarAdjustment: me.getScrollbarAdjustment(ownerContext)
                        }
                    });

                    return;
                }
            }

            // Once the parallelDone flag goes up, we need to pack up the changed column
            // widths for our TableLayout partner.
            if ((columnsChanged = state.columnsChanged) === undefined) {
                columns = ownerContext.target.getVisibleGridColumns();
                columnsChanged = false;

                for (i = 0, len = columns.length; i < len; i++) {
                    column = context.getCmp(columns[i]);
                    // Since we are parallelDone, all of the children should have width,
                    // so we can

                    if (!column.lastBox || column.props.width !== column.lastBox.width) {
                        (columnsChanged || (columnsChanged = []))[i] = column;
                    }
                }

                state.columnsChanged = columnsChanged;
                // This will trigger our TableLayout partner and allow it to proceed.
                ownerContext.setProp('columnsChanged', columnsChanged);
            }

            if (ownerContext.manageScrollbar) {
                // If we changed the column widths, we need to wait for the TableLayout to
                // return whether or not we have overflowY... well, that is, if we are
                // needing to tweak the scrollbarAdjustment...
                scrollbarAdjustment = me.getScrollbarAdjustment(ownerContext);

                if (scrollbarAdjustment) {
                    // Since we start with the assumption that we will need the scrollbar,
                    // we now need to wait to see if our guess was correct.
                    viewOverflowY = viewContext.getProp('viewOverflowY');

                    if (viewOverflowY === undefined) {
                        // The TableLayout has not determined this yet, so park it.
                        me.done = false;

                        return;
                    }

                    if (!viewOverflowY) {
                        // We have our answer, and it turns out the view did not overflow
                        // (even with the reduced width we gave it), so we need to remove
                        // the scrollbarAdjustment and go again.
                        if (lockingPartnerContext) {
                            // In a locking grid, only the normal side plays this game,
                            // so now that we know the resolution, we need to invalidate
                            // the locking view and its headerCt.
                            lockingPartnerContext.invalidate();
                            lockingPartnerContext.headerContext.invalidate();
                        }

                        viewContext.invalidate();

                        ownerContext.invalidate({
                            state: {
                                // Pass a 0 adjustment on into our next life. If this is
                                // the invalidate that resets ownerContext then this is
                                // put onto the new state. If not, it will reset back to
                                // undefined and we'll have to begin again (which is the
                                // correct thing to do in that case).
                                scrollbarAdjustment: 0
                            }
                        });
                    }
                }
                // else {
                // We originally assumed we would need the scrollbar and since we do
                // not now, we must be on the second pass, so we can move on...
                // }
            }
        }
    },

    finishedLayout: function(ownerContext) {
        this.callParent([ ownerContext ]);

        if (this.owner.ariaRole === 'rowgroup') {
            this.innerCt.dom.setAttribute('role', 'row');
        }

        // Wipe this array because it holds component references and gets cached on the object
        // Can cause a circular reference
        ownerContext.props.columnsChanged = null;
    },

    convertWidthsToFlexes: function(ownerContext) {
        var me = this,
            totalWidth = 0,
            calculated = me.sizeModels.calculated,
            childItems, len, i, childContext, item;

        childItems = ownerContext.childItems;
        len = childItems.length;

        for (i = 0; i < len; i++) {
            childContext = childItems[i];
            item = childContext.target;

            totalWidth += childContext.props.width;

            // Only allow to be flexed if it's a resizable column
            if (!(item.fixed || item.resizable === false)) {
                // For forceFit, just use allocated width as the flex value, and the proportions
                // will end up the same whatever HeaderContainer width they are being forced into.
                item.flex = ownerContext.childItems[i].flex = childContext.props.width;
                item.width = null;
                childContext.widthModel = calculated;
            }
        }

        // Only need to loop back if the total column width is not already an exact fit
        return totalWidth !== ownerContext.props.width;
    },

    getScrollbarAdjustment: function(ownerContext) {
        var me = this,
            state = ownerContext.state,
            grid = me.owner.grid,
            scrollbarAdjustment = state.scrollbarAdjustment;

        // If there is potential for a vertical scrollbar, then we start by assuming
        // we will need to reserve space for it. Unless, of course, there are no
        // records!
        if (scrollbarAdjustment === undefined) {
            scrollbarAdjustment = 0;

            if (grid.reserveScrollbar || (ownerContext.manageScrollbar &&
                    !grid.ownerGrid.getSizeModel().height.shrinkWrap)) {
                scrollbarAdjustment = me.scrollbarWidth;
            }

            state.scrollbarAdjustment = scrollbarAdjustment;
        }

        return scrollbarAdjustment;
    },

    /**
     * @private
     * Local getContainerSize implementation accounts for vertical scrollbar in the view.
     */
    getContainerSize: function(ownerContext) {
        var me = this,
            got, needed, padding, gotWidth, gotHeight, width, height, result;

        if (me.owner.isRootHeader) {
            result = me.callParent([ ownerContext ]);

            if (result.gotWidth) {
                result.width -= me.getScrollbarAdjustment(ownerContext);
            }
        }
        else {
            padding = ownerContext.paddingContext.getPaddingInfo();
            got = needed = 0;

            // The container size here has to be provided by the ColumnComponentLayout to
            // account for borders in its odd way.
            if (!ownerContext.widthModel.shrinkWrap) {
                ++needed;
                width = ownerContext.getProp('innerWidth');
                gotWidth = (typeof width === 'number');

                if (gotWidth) {
                    ++got;
                    width -= padding.width;

                    if (width < 0) {
                        width = 0;
                    }
                }
            }

            if (!ownerContext.heightModel.shrinkWrap) {
                ++needed;
                height = ownerContext.getProp('innerHeight');
                gotHeight = (typeof height === 'number');

                if (gotHeight) {
                    ++got;
                    height -= padding.height;

                    if (height < 0) {
                        height = 0;
                    }
                }
            }

            return {
                width: width,
                height: height,
                needed: needed,
                got: got,
                gotAll: got === needed,
                gotWidth: gotWidth,
                gotHeight: gotHeight
            };
        }

        return result;
    },

    publishInnerCtSize: function(ownerContext) {
        var me = this,
            owner = me.owner,
            cw = ownerContext.peek('contentWidth'),
            adjustment = 0;

        // Pass negative "reservedSpace", so that the innerCt gets *extra* size
        // to accommodate the view's vertical scrollbar
        if (cw != null && owner.isRootHeader) {
            adjustment = -ownerContext.state.scrollbarAdjustment;
        }

        return me.callParent([ownerContext, adjustment]);
    },

    roundFlex: function(width) {
        return Math.round(width);
    }
});
