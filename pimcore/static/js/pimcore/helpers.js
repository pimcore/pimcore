/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */


pimcore.registerNS("pimcore.helpers.x");


pimcore.helpers.openAsset = function (id, type) {

    if (pimcore.globalmanager.exists("asset_" + id) == false) {

        pimcore.helpers.addTreeNodeLoadingIndicator("asset", id);

        if (!pimcore.asset[type]) {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset.unknown(id));
        }
        else {
            pimcore.globalmanager.add("asset_" + id, new pimcore.asset[type](id));
        }

    }
    else {
        pimcore.globalmanager.get("asset_" + id).activate();
    }
};

pimcore.helpers.closeAsset = function (id) {

    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
    var tabId = "asset_" + id;
    tabPanel.remove(tabId);

    pimcore.helpers.removeTreeNodeLoadingIndicator("asset", id);
    pimcore.globalmanager.remove("asset_" + id);
};

pimcore.helpers.openDocument = function (id, type) {
    if (pimcore.globalmanager.exists("document_" + id) == false) {
        if (pimcore.document[type]) {
            pimcore.helpers.addTreeNodeLoadingIndicator("document", id);
            pimcore.globalmanager.add("document_" + id, new pimcore.document[type](id));
        }
    }
    else {
        pimcore.globalmanager.get("document_" + id).activate();
    }
};


pimcore.helpers.closeDocument = function (id) {

    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
    var tabId = "document_" + id;
    tabPanel.remove(tabId);

    pimcore.helpers.removeTreeNodeLoadingIndicator("document", id);
    pimcore.globalmanager.remove("document_" + id);
};

pimcore.helpers.openObject = function (id, type) {
    if (pimcore.globalmanager.exists("object_" + id) == false) {
        pimcore.helpers.addTreeNodeLoadingIndicator("object", id);

        if(type != "folder" && type != "variant" && type != "object") {
            type = "object";
        }

        pimcore.globalmanager.add("object_" + id, new pimcore.object[type](id));
    }
    else {
        var tab = pimcore.globalmanager.get("object_" + id);
        tab.activate();
    }
};

pimcore.helpers.closeObject = function (id) {

    var tabPanel = Ext.getCmp("pimcore_panel_tabs");
    var tabId = "object_" + id;
    tabPanel.remove(tabId);

    pimcore.helpers.removeTreeNodeLoadingIndicator("object", id);
    pimcore.globalmanager.remove("object_" + id);
}


pimcore.helpers.openElement = function (id, type, subtype) {
    if (type == "document") {
        pimcore.helpers.openDocument(id, subtype);
    }
    else if (type == "asset") {
        pimcore.helpers.openAsset(id, subtype);
    }
    else if (type == "object") {
        pimcore.helpers.openObject(id, subtype);
    }
};


pimcore.helpers.addTreeNodeLoadingIndicator = function (type, id) {
    // display loading indicator on treenode
    try {
        var tree = pimcore.globalmanager.get("layout_" + type + "_tree");
        var node = tree.tree.getNodeById(id);
        if (node) {
            
            node.originalIconSrc = Ext.get(node.getUI().getIconEl()).getAttribute("src");
            Ext.get(node.getUI().getIconEl()).dom.setAttribute("src", "/pimcore/static/img/panel-loader.gif");
            
            /*node.originalIconClass = Ext.get(node.getUI().getIconEl()).getAttribute("class");
            Ext.get(node.getUI().getIconEl()).dom.setAttribute("class", "x-tree-node-icon pimcore_icon_loading");*/
            
            Ext.get(node.getUI().getIconEl()).repaint();
        }
    }
    catch (e) {
        console.log(e);
    }
}

pimcore.helpers.removeTreeNodeLoadingIndicator = function (type, id) {
    // remove loading indicator on treenode
    try {
        var tree = pimcore.globalmanager.get("layout_" + type + "_tree");
        var node = tree.tree.getNodeById(id);
        
        if (node.originalIconSrc) {
            Ext.get(node.getUI().getIconEl()).dom.setAttribute("src", node.originalIconSrc);
        }
        
        /*if (node.originalIconClass) {
            Ext.get(node.getUI().getIconEl()).dom.setAttribute("class", node.originalIconClass);
        }*/
        
        Ext.get(node.getUI().getIconEl()).repaint();
    }
    catch (e) {
    }
}

pimcore.helpers.openSeemode = function () {
    if (pimcore.globalmanager.exists("pimcore_seemode")) {
        pimcore.globalmanager.get("pimcore_seemode").start();
    }
    else {
        pimcore.globalmanager.add("pimcore_seemode", new pimcore.document.seemode());
    }
}

pimcore.helpers.dndMaskFrames = function () {
    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // check for opened document
        if (activeTab.initialConfig.document) {
            if (typeof activeTab.initialConfig.document.maskFrames == "function") {
                activeTab.initialConfig.document.maskFrames();
            }
        }
        // check for opened object
        if (activeTab.initialConfig.object) {
            if (typeof activeTab.initialConfig.object.maskFrames == "function") {
                activeTab.initialConfig.object.maskFrames();
            }
        }
    }
};

pimcore.helpers.dndUnmaskFrames = function () {
    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // check for opened document
        if (activeTab.initialConfig.document) {
            if (typeof activeTab.initialConfig.document.unmaskFrames == "function") {
                activeTab.initialConfig.document.unmaskFrames();
            }
        }
        // check for opened object
        if (activeTab.initialConfig.object) {
            if (typeof activeTab.initialConfig.object.unmaskFrames == "function") {
                activeTab.initialConfig.object.unmaskFrames();
            }
        }
    }

};

pimcore.helpers.isValidFilename = function (value) {
    var result = value.match(/[a-zA-Z0-9_.\-]+/);
    if (result == value) {
        // key must be at least one character, an maximum 30 characters
        if (value.length < 1 && value.length > 30) {
            return false;
        }
        return true;
    }
    return false;
};


pimcore.helpers.getValidFilename = function (value) {
    var validChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_.~";
    var filename = trim(str_replace(pimcore.transliteration.search, pimcore.transliteration.replace, value)).toLowerCase();
    var filenameParts = [];
    var tmpChar = "";

    for (var i = 0; i < filename.length; i++) {
        tmpChar = filename.charAt(i);
        if (validChars.indexOf(tmpChar) != -1) {
            filenameParts.push(tmpChar);
        }
        else {
            if (i > 0 && i < (filename.length - 1)) {
                filenameParts.push("-");
            }
        }
    }

    filename = filenameParts.join("");
    filename = filename.replace(/\-+/g, '-');

    return filename;
};


pimcore.helpers.showNotification = function (title, text, type, errorText) {
    // icon types: info,error,success
    if(type == "error"){

        if(errorText != null && errorText != undefined){
            text = text + " - " + errorText;
        }
        Ext.MessageBox.show({
            title:title,
            msg: text,
            buttons: Ext.Msg.OK ,
            icon: Ext.MessageBox.ERROR
        });
    } else {
        var notification = new Ext.ux.Notification({
            iconCls: 'icon_notification_' + type,
            title: title,
            html: text,
            autoDestroy: true,
            hideDelay:  1000
        });
         notification.show(document);
    }

};


pimcore.helpers.handleCtrlS = function () {

    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        if (activeTab.initialConfig.document) {
            activeTab.initialConfig.document.publish();
        }
        else if (activeTab.initialConfig.object) {
            activeTab.initialConfig.object.publish();
        }
        else if (activeTab.initialConfig.asset) {
            activeTab.initialConfig.asset.save();
        }
    }
};


pimcore.helpers.handleF5 = function () {

    var tabpanel = Ext.getCmp("pimcore_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        if (activeTab.initialConfig.document) {
            activeTab.initialConfig.document.reload();
            return;
        }
        else if (activeTab.initialConfig.object) {
            activeTab.initialConfig.object.reload();
            return;
        }
    }

    var date = new Date();
    location.href = "/admin/?_dc=" + date.getTime();

    mapF5.stopEvent = false;
};

pimcore.helpers.lockManager = function (cid, ctype, csubtype, data) {
    
    var lockDate = new Date(data.editlock.date * 1000);
    var lockDetails = "<br /><br />";
    lockDetails += "<b>" + t("user") + ":</b> " + data.editlock.user.username + "<br />";
    lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate);
    lockDetails += "<br /><br />" + t("element_lock_question");

    Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails, function (lock, buttonValue) {
        if (buttonValue == "yes") {
            Ext.Ajax.request({
                url: "/admin/misc/unlock-element",
                params: {
                    id: lock[0],
                    type:  lock[1]
                },
                success: function () {
                    pimcore.helpers.openElement(lock[0], lock[1], lock[2]);
                }
            });
        }
    }.bind(this, arguments));
};


pimcore.helpers.closeAllElements = function () {
    var tabs = Ext.getCmp("pimcore_panel_tabs").items;
    if (tabs.getCount() > 0) {
        if (tabs.getCount() > 1) {
            window.setTimeout(pimcore.helpers.closeAllElements, 200);
        }
        Ext.getCmp("pimcore_panel_tabs").remove(tabs.first());
    }
};


pimcore.helpers.loadingShow = function () {
    pimcore.globalmanager.get("loadingmask").show();
}

pimcore.helpers.loadingHide = function () {
    pimcore.globalmanager.get("loadingmask").hide();
}

pimcore.helpers.itemselector = function (muliselect, callback, restrictions, config) {
    var itemselector = new pimcore.element.selector.selector(muliselect, callback, restrictions, config);
}


pimcore.helpers.activateMaintenance = function () {

    Ext.Ajax.request({
        url: "/admin/misc/maintenance/activate/true"
    });

    if(!Ext.getCmp("pimcore_maintenance_disable_button")) {
        pimcore.helpers.showMaintenanceDisableButton();
    }
}

pimcore.helpers.deactivateMaintenance = function () {

    Ext.Ajax.request({
        url: "/admin/misc/maintenance/deactivate/true"
    });

    var toolbar = pimcore.globalmanager.get("layout_toolbar").toolbar;
    toolbar.remove(Ext.getCmp("pimcore_maintenance_disable_button"));
    toolbar.doLayout();
}

pimcore.helpers.showMaintenanceDisableButton = function () {
    var toolbar = pimcore.globalmanager.get("layout_toolbar").toolbar;

    var deactivateButton = new Ext.Button({
        id: "pimcore_maintenance_disable_button",
        text: "DEACTIVATE MAINTENANCE",
        iconCls: "pimcore_icon_maintenance",
        cls: "pimcore_main_menu",
        handler: pimcore.helpers.deactivateMaintenance
    });

    toolbar.insertButton(5, [deactivateButton]);
    toolbar.doLayout();
}

pimcore.helpers.download = function (url) {
    pimcore.settings.showCloseConfirmation = false;
    window.setTimeout(function () {
        pimcore.settings.showCloseConfirmation = true;
    },1000);

    location.href = url;
}

pimcore.helpers.getFileExtension = function (filename) {
    var extensionP = filename.split("\.");
    return extensionP[extensionP.length - 1];
}


pimcore.helpers.startPong = function () {

    var width = Ext.get("pimcore_body").getWidth();
    var height = Ext.get("pimcore_body").getHeight();

    Ext.get("pimcore_body").slideOut();

    // here is the game code (http://blog.benogle.com/2009/04/20/jquery-pong/)
    (function(o){o.fn.pong=function(s,q){function m(b,c,f,g,e,h){b?c.playerScore++:c.compScore++;h.html("Browser: "+c.compScore+" | You: "+c.playerScore);c.playerScore==a.playTo||c.compScore==a.playTo?(e.css("visibility","hidden"),c.gameOver=true,c.playerScore==a.playTo?h.append("; you win!"):h.append("; you lose :(")):(c.x=b?a.width-a.paddleWidth-a.paddleBuffer-a.ballWidth-10:a.paddleWidth+a.paddleBuffer+10,c.y=Math.round(Math.random()*(a.height-e.height())),e.css("left",c.x),e.css("top",c.y),b!=0>Math.cos(a.ballAngle*Math.PI/180)>0&&(a.ballAngle+=180),e.css("visibility","visible"))}function p(b,c,f,g,e,h){if(b.gameOver)h.html("click to start!");else{h.html("press ESC to stop");var j=new Date,d=j.valueOf()-b.delay.valueOf()-a.target;b.speed+=d>5?-1:0;b.speed+=d<-5?1:0;b.speed=Math.abs(b.speed);b.delay=j;setTimeout(function(){p(b,c,f,g,e,h)},b.speed);var k=a.ballAngle*Math.PI/180;b.y+=Math.round(a.ballSpeed*Math.sin(k));b.x+=Math.round(a.ballSpeed*Math.cos(k));var j=180-a.ballAngle,l=0-a.ballAngle,d=parseInt(c.css("top")),i=a.paddleHeight/2+d,k=Math.cos(k)>0||b.x>a.width/(2-b.compAdj/(a.difficulty*10))?a.height/2:a.ballHeight/2+b.y,n=Math.abs(k-i);if(n>a.compSpeed)n=a.compSpeed;k>i?d+=n:d-=n;d<1&&(d=1);d+a.paddleHeight+1>a.height&&(d=a.height-a.paddleHeight-1);c.css("top",d+"px");i=parseInt(f.css("top"));b.up&&(i-=a.playerSpeed);b.down&&(i+=a.playerSpeed);i<1&&(i=1);i+a.paddleHeight+1>a.height&&(i=a.height-a.paddleHeight-1);f.css("top",i+"px");if(b.y<1)b.y=1,a.ballAngle=l;if(b.y>a.height-a.ballHeight)b.y=a.height-a.ballHeight,a.ballAngle=l;if(b.x<1)b.x=1,a.ballAngle=j,b.compAdj-=a.difficulty,m(true,b,c,f,g,e,h);if(b.x>a.width-a.ballWidth)b.x=a.width-a.ballWidth,a.ballAngle=j,m(false,b,c,f,g,e,h);l=a.paddleWidth+a.paddleBuffer;if(b.x<l&&b.y<a.paddleHeight+d&&b.y+a.ballHeight>d)b.x=l,a.ballAngle=j,b.compAdj++;d=a.width-a.ballWidth-a.paddleWidth-a.paddleBuffer;if(b.x>d&&b.y<a.paddleHeight+i&&b.y+a.ballHeight>i)b.x=d,a.ballAngle=j;g.css("top",b.y);g.css("left",b.x);if(b.compAdj<0)b.compAdj=0}}function r(a,c,f,g,e,h){if(a.gameOver)a.gameOver=false,a.playerScore=-1,a.compScore=-1,setTimeout(function(){p(a,c,f,g,e,h)},a.speed),m(false,a,c,f,g,e,h),m(true,a,c,f,g,e,h)}var a=o.extend({targetSpeed:30,ballAngle:45,ballSpeed:8,compSpeed:5,playerSpeed:5,difficulty:5,width:400,height:300,paddleWidth:10,paddleHeight:40,paddleBuffer:1,ballWidth:14,ballHeight:14,playTo:10},q);return this.each(function(){var b={up:false,down:false,x:0,y:0,compAdj:0,compScore:0,playerScore:0,speed:30,gameOver:true,delay:new Date},c=o(this);c.css("background","#000");c.css("position","relative");c.append('<textarea class="field" style="position:absolute;background:#000;border:0;top:-9999; left:-9999; width:0;height0;"></textarea>');c.append('<div class="score" style="position:relative;color:#ffffff; font-family: sans-serif; text-align: center; font-weight: bold;">Browser: 0 | You: 0</div>');c.append('<div class="leftPaddle" style="position:absolute;background-color:#ffffff;"></div>');c.append('<div class="rightPaddle" style="position:absolute;background-color:#ffffff;"></div>');c.append('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAMAAAAoyzS7AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYxIDY0LjE0MDk0OSwgMjAxMC8xMi8wNy0xMDo1NzowMSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNS4xIFdpbmRvd3MiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RkE5OUI3MzAwODA0MTFFMUEwMkZENkVERDI1RTdDRjUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RkE5OUI3MzEwODA0MTFFMUEwMkZENkVERDI1RTdDRjUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpGQTk5QjcyRTA4MDQxMUUxQTAyRkQ2RUREMjVFN0NGNSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpGQTk5QjcyRjA4MDQxMUUxQTAyRkQ2RUREMjVFN0NGNSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PtVy0SIAAAAGUExURf///wAAAFXC034AAAAMSURBVHjaYmAACDAAAAIAAU9tWeEAAAAASUVORK5CYII=" class="ball" style="position:absolute;visibility:hidden;">');c.append('<div class="msg" style="position:absolute; font-size: 16px; color:#fff; bottom: 10px; right: 10px;"></div>');var f=c.children(".leftPaddle"),g=c.children(".rightPaddle"),e=c.children(".ball"),h=c.children(".score"),j=c.children(".msg"),d=c.children(".field");d.keydown(function(a){switch(a.keyCode){case 38:b.up=true;break;case 40:b.down=true;break;case 27:c.children(".ball").css("visibility","hidden"),b.gameOver=true,pimcore.helpers.stopPong()}return false});d.keyup(function(a){switch(a.keyCode){case 38:b.up=false;break;case 40:b.down=false}return false});c.css("width",a.width);c.css("height",a.height);f.css("width",a.paddleWidth);f.css("height",a.paddleHeight);f.css("left",a.paddleBuffer);f.css("top",Math.round(1+Math.random()*(a.height-a.paddleHeight-2)));g.css("width",a.paddleWidth);g.css("height",a.paddleHeight);g.css("left",a.width-a.paddleWidth-a.paddleBuffer);g.css("top",Math.round(1+Math.random()*(a.height-a.paddleHeight-2)));e.css("width",a.ballWidth);e.css("height",a.ballHeight);b.speed=a.targetSpeed;p(b,f,g,e,h,j);c.click(function(){d.focus();r(b,f,g,e,h,j)})})}})(jQuery);

    window.setTimeout(function () {
        Ext.getBody().insertHtml('afterBegin', '<div id="play_pong" style="width:' + width + 'px; height:' + height + 'px; top:0; left:0; position:absolute;background:#f60;z-index:1000; "></div>');

        $('#play_pong').pong('image is in the source as base64 data uri',{
            ballAngle: 45,    //degrees
            ballSpeed: Math.round(width/70),     //pixels per update
            compSpeed: Math.round(height/50),     //speed of your opponent!!
            playerSpeed: Math.round(height/50),  //pixels per update
            difficulty: 10,
            width: width,       //px
            height: height,      //px
            paddleWidth: 20,  //px
            paddleHeight: Math.round(height/5), //px
            paddleBuffer: 1,  //px from the edge of the play area
            ballWidth: 25,    //px
            ballHeight: 25,   //px
            playTo: 10,        //points
            targetSpeed: 30
        });
    }, 1000);

}

pimcore.helpers.stopPong = function () {

    Ext.get("play_pong").remove();
    Ext.get("pimcore_body").slideIn();
}

