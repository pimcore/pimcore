Ext.namespace('Ext.ux.form');
/**
 * @class Ext.ux.form.SuperField 
 * @constructor
 * @author <a href="mailto:danh2000@gmail.com">Dan Humphrey</a>
 * @version 0.7
 */
Ext.ux.form.SuperField = {
	items : [],
	allowEdit: true,
	minItems : 0,
	minItemsText : 'This field expects a minimum of {0} items',
	maxItems : 0,
	maxItemsText : 'This field accepts a maximum of {0} items',
	arrayAppendChar : '[]', //need to think about this
	stripeRows: true, 
	addLabel : 'Add',
	editLabel : 'Edit Item',
	deleteLabel : 'Delete Item',
	defaultItemText: 'Item',
	renderSummaryHeader: true,
	centerSummaryData: false,
	values : [], 
	windowConfig : {
		resizable : false,
		autoHeight : true
	},
	hideLabel : true,
	msgTarget : 'qtip',
    	disableMaskedElements: true,
	initComponent : function() {
		this.validationEvent = 'blur';
		this.ti = this.tabIndex || "-1";
		this.tabIndex = "-1";
		this.focusClass = 'over';
		this.emptyText = '';
		this.defaultAutoCreate = {
			tag: 'div',
			cls: 'x-superfield'	
		};
		this.allowAdd = true;
		this.getRawValue = this.getValue;
		this.arrayAppend = (this.maxItems === 0) ? this.arrayAppendChar : (this.maxItems > 1) ? this.arrayAppendChar : '';
		//saved items collection
		this.savedItems = new Ext.util.MixedCollection(false);
		this.saveId = Ext.id();
		//format minItemsText and maxItemsText messages
		this.minItemsText = String.format(this.minItemsText,this.minItems);
		this.maxItemsText = String.format(this.maxItemsText,this.maxItems);
		
		this.summaryHeaderFields = [];
		this.itemClassArray = [];
		//store summaryCls and summaryHeader for items
		for(var i = 0, j = this.items.length, head, item; i < j; i++){
			item = this.items[i];
			if (item.summaryCls) {
				this.itemClassArray[item.name] = item.summaryCls;
			}
			if(item.summaryDisplay){
				head = item.summaryHeader ? item.summaryHeader : item.fieldLabel;
				this.summaryHeaderFields.push({
					name : item.name,
					header: head
				}); 
			}		
		}
		var ti = this.items[this.items.length-1].tabIndex + 1 || -1;
		//ITEMS FORM AND ITEMS WINDOW
		this.itemsForm = new  Ext.FormPanel({
			bodyStyle : 'padding:10px;',
			items : this.items,
			autoScroll : true,
			monitorValid : true,
			autoHeight:true,
			labelWidth: 180,
			buttons : [{
				text : 'Save',
				tabIndex: ti,
				handler : function(){
					var f = this.itemsForm.form;
					var v = f.getFieldValues();
					var e = this.editing;
					this.itemsWindow.hide();
					if(e){
						this.updateItem.call(this,e,v);
					}else{
						this.saveItem.call(this,v);
					}
					f.reset();
					this.addButton.focus();
				},
				scope : this,
				formBind : true
			}]
		});
		// create items window
		// required config that cannot be overridden
		var winConfig = {
			title : this.addLabel,
			closeAction : 'hide',
			items : [this.itemsForm],
			modal:true,
			listeners : {
				'beforeshow' : {
					fn : function() {
						try{
							var first = Ext.getCmp(this.items[0].id);
							this.itemsForm.form.clearInvalid();
							first.focus(true,150);
						}catch(e){}
					},
					delay : 50,
					scope : this
				},
				'beforehide' : {
                    fn : function() {
                        this.addButton.focus();
                        this.editing = null;
                    },
                    delay : 50,
                    scope : this
                }
			}
		};
		//apply user window config
		Ext.applyIf(winConfig, this.windowConfig);
		// window config defaults that will be applied if not provided
		var windowConfigDefaults = {
			autoScroll : true,
			title : this.addLabel,
			layout : 'fit',
			modal : true,
			width : 450,
			height : 300
		};
		Ext.applyIf(winConfig, windowConfigDefaults);
		this.itemsWindow = new Ext.Window(winConfig);
		
		//prevent tabbing behind 
        
        // removed for pimcore by BR
        
		/*this.itemsWindow.on('beforeshow',function(){
			if (this.disableMaskedElements === true) {
				this.disabledMaskedControls = [];
				var db = Ext.fly(document.body);
				var els = db.select("input, select, a, button");
				els.each(function(el){
					if (!el.dom.disabled && el.isVisible() && el.findParent('div[id='+this.itemsWindow.getId()+']') === null) {
						el.dom.disabled = true;
						this.disabledMaskedControls.push(el.dom);
					}
				}, this);
			}
		},this);
		this.itemsWindow.on('beforehide',function(){
			Ext.each(this.disabledMaskedControls, function(el) {
				el.disabled = false;
			});
		},this);
        */
		//end prevent tabbing behind 

		//CUSTOM EVENTS
		this.addEvents(
			'beforeadd',
			'beforeadditem',
			'additem',
			'beforeupdateitem',
			'updateitem',
			'beforeremoveitem',
			'removeitem'
		);
		//super	
		Ext.ux.form.SuperField.superclass.initComponent.call(this);
	},
	onRender : function(ct, position){
		//super
		Ext.ux.form.SuperField.superclass.onRender.call(this, ct, position);
		//create wrap
		this.wrapEl = this.el.wrap({
			tag : 'div',
			cls : 'x-superfield-wrap'
		});
		//create header
		this.header = this.el.createChild({
			tag : 'div',
			cls : 'x-superfield-header' 
		});
		var t = this.header.createChild({
		    tag : 'table',
		    width: '100%',
		    border: "0",
		    html: '<tbody><tr><td></td><td width="20"></td></tr></tbody>'
		});
		
		//add button
		this.addButton = t.down('tbody tr td').createChild({
            tag : 'button',
            cls : 'x-superfield-button add',
            tabIndex : this.ti,
            html: '<img src="'+Ext.BLANK_IMAGE_URL+'" width="15" height="15"/><span>'+ this.addLabel + '</span>'
        });
        
        this.addButtonImg = this.addButton.down('img');
        this.addButton.on({
            'mouseover' : function(){
            	if (this.disabled || this.allowAdd === false) {
            	   return;
            	}
                this.addButtonImg.addClass('over');
            },
            'mouseout' : function(){
                this.addButtonImg.removeClass('over');
            },
            'focus' : function(){
                if (this.disabled || this.allowAdd === false) {
                   return;
                }
                this.addButtonImg.addClass('over');
            },
            'blur' : function() {
                this.addButtonImg.removeClass('over');
                this.validateValue();
            },
            'click' : function() {
                if (this.disabled || this.allowAdd === false) {
                    return;
                }
                if (this.fireEvent('beforeadd') !== false) {
                    this.itemsForm.form.reset();
                    this.itemsWindow.show();
                }
            },
            scope: this 
        }); 
		//create error container for msgTarget of 'side'
		this.errCt = t.down('tbody tr td[width=20]').createChild({
			tag:'div',
			style: 'width:20px;height:20px;',
			html: '&nbsp;'	
		}); 
		//create items container
		this.createItemsContainer();
		// set values passed into config
		this.setValue.call(this, this.values);
		delete this.values;
	},
	afterRender : function() {
		Ext.ux.form.SuperField.superclass.afterRender.call(this);
		this.originalValue = Ext.encode(this.getValue());
	},
	onDestroy : function() {
		try {
			this.clearValues();
			this.addButton.removeAllListeners();
			this.addButton.remove();
			this.errCt.remove();
			this.header.remove();
			this.itemsContainer.remove();
			this.wrapEl.remove();
			this.itemsForm.destroy();
			this.itemsWindow.destroy();
		}
		catch (e) {} 
		Ext.ux.form.SuperField.superclass.onDestroy.call(this);
	},
	createItemsContainer : function(){
		//item summary header
		var summaryHeader = '';
		if (this.renderSummaryHeader) {
			if (this.summaryHeaderFields.length) {
				summaryHeader = '<thead><tr>';
				for (var i = 0, j = this.summaryHeaderFields.length; i < j; i++) {
					summaryHeader += '<th class="x-superfield-summary-header">' + this.summaryHeaderFields[i].header + '</th>';
				}
				if(this.allowEdit){
				    summaryHeader += '<th width="15" class="x-superfield-summary-header">&nbsp;</th>';//extra column for edit button
				}
				summaryHeader += '<th width="15" class="x-superfield-summary-header">&nbsp;</th>';//extra column for delete 
				summaryHeader += '</tr></thead>';
			}
		}
		var itemsCls = 'x-superfield-items';
		if(this.centerSummaryData === true){
			itemsCls += ' center';	
		}
		//items table and container
		var itemsTable = this.el.createChild({
			tag : 'table',
			width : '100%',
			cls : itemsCls ,
			cellpadding : '0',
			cellspacing : '0',
			html : summaryHeader + '<tbody><tr></tr></tbody>'
		});
		this.itemsContainer = itemsTable.down('tbody');
	
	},
	manageAddButton : function() {
		if (!this.rendered) { // not rendered
			return;
		}
		// disable add button
		if (this.maxItems > 0 && (this.savedItems.getCount() == this.maxItems)) {
			this.allowAdd = false;
			this.header.setOpacity(0.5,true);
			this.header.addClass('disabled');
			this.addButton.dom.disabled = true;
			Ext.QuickTips.register({ 
				target: this.header.id,
    			text: this.maxItemsText
			});
		// enable add button
		} else {
			Ext.QuickTips.unregister(this.header.id);
			this.allowAdd = true;
			this.header.clearOpacity();
			this.header.removeClass('disabled');
			this.addButton.dom.disabled = false;
		}
	},
	fixItemNumbers : function(){
		if (this.summaryHeaderFields.length && this.renderSummaryHeader){
			return;
		}
		var ic = 1;
		this.itemsContainer.select('tr.x-superfield-item').each(function(i){
			i.down('td').update(this.defaultItemText+' '+ ic++);
		},this);
	},
	fixStripes : function(){
		if (this.stripeRows !== true){
			return;
		}
		var odd = this.itemsContainer.select('tr.x-superfield-item:odd');
		odd.removeClass('even');
		odd.addClass('odd');
		var even = this.itemsContainer.select('tr.x-superfield-item:even');
		even.removeClass('odd');
		even.addClass('even');
	},
	saveItem : function(itemVals) {
		// prevent saving item if subscriber returns false to beforeadditem
		if (this.fireEvent('beforeadditem', itemVals) === false) {
			return;
		}
		// create hidden elements and add to the owner container
		// and hidden elements array
		var hiddenElements = [];
		for (var p in itemVals) {
			var h = new Ext.form.Hidden({
				id : p + this.saveId,
				name : p + this.arrayAppend,
				value : itemVals[p]
			});
			hiddenElements.push(h);
			this.ownerCt.add(h);
		}
		
		// store new item
		var newItem = {
			hiddenElements : hiddenElements,
			values : itemVals,
			id: this.saveId
		};
		var startVal = this.getValue();
		this.savedItems.add(this.saveId, newItem);
		this.fireEvent('change', this, startVal, this.getValue());
		
		this.renderItem(newItem);
		
		this.saveId = Ext.id(); 
		
		this.manageAddButton.call(this);
		this.ownerCt.doLayout();
		
		this.validate();
		this.fireEvent('additem', itemVals);
		return itemVals;
	},
	createItemRow: function(itemValues){
		var rowData = '';
		for(var i = 0,j = this.summaryHeaderFields.length;i <j;i++){
		    //item class
		    var cls = this.itemClassArray[this.summaryHeaderFields[i].name] || '';
		    rowData += '<td nowrap="nowrap" class="'+cls+'">' + itemValues[this.summaryHeaderFields[i].name] + '</td>';
		}
		if(rowData === ''){ //no summary data
		    rowData = '<td>'+this.defaultItemText+' ' + this.savedItems.getCount() +'</td>';
		}
		var editBtn = this.allowEdit ? '<td style="padding:0px !important;" width="15"><button class="x-superfield-button edit"><img src="'+Ext.BLANK_IMAGE_URL+'" width="15" height="15" /></button></td>' : '';
		//render the item row
		return rowData + editBtn + '<td style="padding:0px !important;" width="15"><button class="x-superfield-button delete"><img src="'+Ext.BLANK_IMAGE_URL+'" width="15" height="15" /></button></td>'
        
	},
	updateItem : function(item,newVals) {
		var old = item.values;
		item.editButton.removeAllListeners();
		item.deleteButton.removeAllListeners();
		for(var p in newVals){
		    var n = p+item.id;
		    Ext.get(n).dom.value = newVals[p];
		}
		item.values = newVals;
		this.renderItem(item,this.createItemRow(newVals));
		this.fireEvent('updateitem', old, item.values);
	},
	updateItemRow : function(rowId,html){
		if(!Ext.isIE){
			Ext.get(rowId).update(html);
			return;
		}
		var temp = Ext.get('dom-update-tag') || Ext.getBody().createChild({
			tag: 'span',
			id: 'dom-update-tag',
			style: 'visibility: hidden;'
		});
		
		temp.update('<table><tbody><tr>'+html.replace(/\n/g,'')+'</tr></tbody></table>');
		var tempRow = temp.child('tr').dom;
		var rowToUpdate = Ext.getDom(rowId);
		
		Ext.each(rowToUpdate.childNodes,function(item,idx,all){
			if(item.nodeType == 1){
				rowToUpdate.replaceChild(tempRow.firstChild,rowToUpdate.childNodes[idx]);		
			}
		});
	},
	renderItem: function(item,replaceRow){
		
		if(replaceRow){
			 item.editButton.removeAllListeners();
			 item.deleteButton.removeAllListeners();
			 this.updateItemRow(item.id,replaceRow);
		}else{
			item.row = this.itemsContainer.createChild({
			tag : 'tr',
			id: item.id,
			cls : 'x-superfield-item',
			html : this.createItemRow(item.values)
		    });
		}
		item.row.hide();
		//edit button
		if(this.allowEdit){
		    item.editButton = item.row.down('td button.x-superfield-button.edit');
		    var editButtonImg = item.editButton.down('img');
		    if(this.editLabel){
			
				Ext.QuickTips.register({
				    target : editButtonImg,
				    text : this.editLabel
				});
		    }
		    item.editButton.on({
			'mouseover' : function(){
			    if (this.disabled) {
			       return;
			    }
			    editButtonImg.addClass('over');
			},
			'mouseout' : function(){
			    editButtonImg.removeClass('over');
			},
			'focus' : function(){
			    if (this.disabled) {
			       return;
			    }
			    editButtonImg.addClass('over');
			},
			'blur' : function() {
			    editButtonImg.removeClass('over');
			},
			'click' : function() {
			    if(this.disabled) {return;}
			    editButtonImg.removeClass('over');
			    // prevent remove if event returns false
			    if (this.fireEvent('beforeupdateitem', item.values) === false) {
				return;
			    }
			    this.editItem(item);
			},
			scope : this
		    }); 
		}
		// create the item remove button and apply listeners
		item.deleteButton = item.row.down('td button.x-superfield-button.delete');
		var deleteButtonImg = item.deleteButton.down('img'); 
		if(this.deleteLabel){
		    Ext.QuickTips.register({
				target : deleteButtonImg,
				text : this.deleteLabel,
				minWidth : 20
		    });
		}
		item.deleteButton.on({
		    'mouseover' : function(){
				if (this.disabled) {
				   return;
				}
				deleteButtonImg.addClass('over');
		    },
		    'mouseout' : function(){
				deleteButtonImg.removeClass('over');
		    },
		    'focus' : function(){
				if (this.disabled) {
				   return;
				}
				deleteButtonImg.addClass('over');
		    },
		    'blur' : function() {
				deleteButtonImg.removeClass('over');
		    },	
		    'click' : function() {
				if(this.disabled) {return;}
				deleteButtonImg.removeClass('over');
				// prevent remove if event returns false
				if (this.fireEvent('beforeremoveitem', item.values) === false) {
				    return;
				}
				this.removeItem(item);
		    },
		    scope : this
		});
		item.row.show(true);
		this.fixStripes();
	},
	editItem : function(item) {
		this.editing = item;
		this.itemsWindow.show();
		this.itemsForm.form.setValues(item.values);
	},
	removeItem : function(item) {
		// remove and destroy hidden elements
		//var item = this.savedItems.get(itemId)
		Ext.each(item.hiddenElements, function(h) {
		    this.ownerCt.remove(h, true);
		    h = null;
		}, this);
		delete item.hiddenElements;
		this.fireEvent('removeitem', item.values);
		var startVal = this.getValue();
		this.savedItems.remove(item);
		this.fireEvent('change', this, startVal, this.getValue());
		item.deleteButton.removeAllListeners();
		item.deleteButton.remove;
		delete item.deleteButton;
		item.row.remove();
		delete item.row;
		item = null;
		this.manageAddButton.call(this);
		this.validate();
		this.ownerCt.doLayout();
		this.fixStripes();
		this.fixItemNumbers();
	},
	initValue : Ext.emptyFn,
	clearInvalid : function() {
		if (!this.rendered || this.preventMark) { // not rendered
			return;
		}
		this.el.removeClass(this.invalidClass);
		switch (this.msgTarget) {
			case 'qtip' :
				this.header.dom.qtip = '';
				this.header.dom.qclass = '';
				break;
			case 'title' :
				this.el.dom.title = '';
				break;
			case 'under' :
				if (this.errorEl) {
					Ext.form.Field.msgFx[this.msgFx].hide(this.errorEl, this);
				}
				break;
			case 'side' :
				if (this.errorIcon) {
					this.errorIcon.dom.qtip = '';
					this.errorIcon.hide();
					this.un('resize', this.alignErrorIcon, this);
				}
				break;
			default :
				if (this.msgTarget) {
					var t = Ext.getDom(this.msgTarget);
					t.innerHTML = '';
					t.style.display = 'none';
				}
				break;
		}
		this.fireEvent('valid', this);
	},
	markInvalid : function(msg) {
		if (!this.rendered || this.preventMark) { // not rendered
			return;
		}
		this.el.addClass(this.invalidClass);
		msg = msg || this.invalidText;
		var elp;
		switch (this.msgTarget) {
			case 'qtip' :
				this.header.dom.qtip = msg;
				this.header.dom.qclass = 'x-form-invalid-tip';
				if (Ext.QuickTips) { 
					Ext.QuickTips.enable();
				}
				break;
			case 'title' :
				this.el.dom.title = msg;
				break;
			case 'under' :
				if (!this.errorEl) {
					elp = this.getErrorCt();
					this.errorEl = elp.createChild({
						cls : 'x-form-invalid-msg'
					});
					this.errorEl.setWidth(elp.getWidth(true) - 20);
				}
				this.errorEl.update(msg);
				Ext.form.Field.msgFx[this.msgFx].show(this.errorEl, this);
				break;
			case 'side' :
				if (!this.errorIcon) {
					elp = this.getErrorCt();
					this.errorIcon = elp.createChild({
						cls : 'x-form-invalid-icon'
					});
				}
				this.alignErrorIcon();
				this.errorIcon.dom.qtip = msg;
				this.errorIcon.dom.qclass = 'x-form-invalid-tip';
				this.errorIcon.show();
				this.on('resize', this.alignErrorIcon, this);
				break;
			default :
				if (this.msgTarget) {
					var t = Ext.getDom(this.msgTarget);
					t.innerHTML = msg;
					t.style.display = this.msgDisplay;
					break;
				}
			}
			this.fireEvent('invalid', this, msg);
		},
		alignErrorIcon : function(){
    		this.errorIcon.alignTo(this.errCt, 'tr?', [-18, 0]);
    	},
    	disable : function() {
		this.clearInvalid();
		Ext.ux.form.SuperField.superclass.disable.call(this);
	},
	validateValue : function(value) {
		if (this.disabled) {
			return;
		}
		if ((this.minItems > 0) && this.savedItems.getCount() < this.minItems) {
			this.markInvalid(this.minItemsText);
			return false;
		}
		return true;
	},
	reset : function() {
		if (this.disabled) {
			return;
		}
		this.setValue(Ext.decode(this.originalValue));
		this.clearInvalid();
		this.manageAddButton.call(this);
	},
	isDirty : function() {
		if (this.disabled) {
			return false;
		}
		return Ext.encode(this.getValue()) !== this.originalValue;
	},
	getValue : function() {
		var ret = [];
		this.savedItems.each(function(item, idx, len) {
			ret.push(item.values);
		});
		return ret;
	},
	setValue : function(values) {
		if (this.disabled || !values) {
			return;
		}
		this.clearValues.call(this);
		if (Ext.isArray(values)) {
			var vc = 0;
			Ext.each(values, function(v) {
				this.saveItem.call(this, v);
				++vc;
				if (vc == this.maxItems) { // prevent adding too many
					return false;
				}
			}, this);
		} else {
			this.saveItem.call(this, values);
		}
	},
	clearValues : function() {
		this.savedItems.each(function(item, idx, len) {
			this.removeItem(item);
		}, this);
		this.ownerCt.doLayout();
	},
	getErrorCt : function() {
		if(this.msgTarget == 'under'){
			return this.wrapEl;
		}
		return this.errCt;
	}
};
Ext.ux.form.SuperField = Ext.extend(Ext.form.Field,Ext.ux.form.SuperField);
