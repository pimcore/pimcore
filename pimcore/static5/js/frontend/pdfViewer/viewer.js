
// polyfills, ...


(function () {

    if (typeof window.Element === "undefined" || "classList" in document.documentElement) return;

    var prototype = Array.prototype,
        push = prototype.push,
        splice = prototype.splice,
        join = prototype.join;

    function DOMTokenList(el) {
        this.el = el;
        // The className needs to be trimmed and split on whitespace
        // to retrieve a list of classes.
        var classes = el.className.replace(/^\s+|\s+$/g,'').split(/\s+/);
        for (var i = 0; i < classes.length; i++) {
            push.call(this, classes[i]);
        }
    };

    DOMTokenList.prototype = {
        add: function(token) {
            if(this.contains(token)) return;
            push.call(this, token);
            this.el.className = this.toString();
        },
        contains: function(token) {
            return this.el.className.indexOf(token) != -1;
        },
        item: function(index) {
            return this[index] || null;
        },
        remove: function(token) {
            if (!this.contains(token)) return;
            for (var i = 0; i < this.length; i++) {
                if (this[i] == token) break;
            }
            splice.call(this, i, 1);
            this.el.className = this.toString();
        },
        toString: function() {
            return join.call(this, ' ');
        },
        toggle: function(token) {
            if (!this.contains(token)) {
                this.add(token);
            } else {
                this.remove(token);
            }

            return this.contains(token);
        }
    };

    window.DOMTokenList = DOMTokenList;

    function defineElementGetter (obj, prop, getter) {
        if (Object.defineProperty) {
            Object.defineProperty(obj, prop,{
                get : getter
            });
        } else {
            obj.__defineGetter__(prop, getter);
        }
    }

    defineElementGetter(Element.prototype, 'classList', function () {
        return new DOMTokenList(this);
    });

})();

Function.prototype.bind = function (context) {
    var update = function (array, args) {
        var arrayLength = array.length, length = args.length;
        while (length--)
            array[arrayLength + length] = args[length];
        return array;
    };

    var merge = function (array, args) {
        array = slice.call(array, 0);
        return update(array, args);
    };

    var slice = Array.prototype.slice;

    if (arguments.length < 2 && typeof arguments[0] == "undefined") {
        return this;
    }
    var __method = this, args = slice.call(arguments, 1);
    return function() {
        var a = merge(args, arguments);
        return __method.apply(context, a);
    };
};

(function(win, doc){
	if(win.addEventListener) {
        return;
    }		//No need to polyfill

	function docHijack(p){var old = doc[p];doc[p] = function(v){return addListen(old(v))}}
	function addEvent(on, fn, self){
		return (self = this).attachEvent('on' + on, function(e){
			var e = e || win.event;
			e.preventDefault  = e.preventDefault  || function(){e.returnValue = false}
			e.stopPropagation = e.stopPropagation || function(){e.cancelBubble = true}
			fn.call(self, e);
		});
	}
	function addListen(obj, i){
		if(i = obj.length)while(i--)obj[i].addEventListener = addEvent;
		else obj.addEventListener = addEvent;
		return obj;
	}

	addListen([doc, win]);
	if('Element' in win)win.Element.prototype.addEventListener = addEvent;			//IE8
	else{		//IE < 8
		doc.attachEvent('onreadystatechange', function(){addListen(doc.all)});		//Make sure we also init at domReady
		docHijack('getElementsByTagName');
		docHijack('getElementById');
		docHijack('createElement');
		addListen(doc.all);
	}
})(window, document);


/**
 * PIMCORE PDF VIEWER
 */

if (typeof pimcore == "undefined") {
    pimcore = {};
}


pimcore.pdf = function (config) {
    this.id = config["id"];
    this.data = config["data"];

    this.init();
};

pimcore.pdf.prototype.init = function () {

    this.containerEl = document.getElementById(this.id);

    var elements = this.containerEl.getElementsByTagName("div");
    for(var i=0; i<elements.length; i++) {
        this[elements[i].className.replace(/pimcore-/,"").split(" ")[0]] = elements[i];
    }

    // add empty first-page
    this.data.pages.splice(0,0,{
        placeholder: true
    });

    // add pages
    var page, position;
    for(i=0; i<this.data.pages.length; i++) {

        position = "right";
        if(i % 2 == 0) {
            position = "left";
        }

        page = document.createElement("div");
        page.className = "pimcore-pdfPage pimcore-pdfPage-" + position;
        //page.setAttribute("data-page", i);

        this.data.pages[i]["node"] = page;
        this.pdfPages.appendChild(page);
    }

    this.pdfButtonLeft.addEventListener("click", this.prevPage.bind(this), true);
    this.pdfButtonRight.addEventListener("click", this.nextPage.bind(this), true);
    this.pdfFullscreenClose.addEventListener("click", this.closeFullScreen.bind(this), true);
    this.pdfDownload.addEventListener("click", this.download.bind(this), true);

    this.pdfButtonRight.addEventListener("mouseover", this.buttonHover, true);
    this.pdfButtonLeft.addEventListener("mouseover", this.buttonHover, true);
    this.pdfButtonRight.addEventListener("mouseout", this.buttonHoverOut, true);
    this.pdfButtonLeft.addEventListener("mouseout", this.buttonHoverOut, true);

    if(this.data["fullscreen"]) {
        this.pdfZoom.addEventListener("mouseover", this.buttonHover, true);
        this.pdfZoom.addEventListener("mouseout", this.buttonHoverOut, true);
        this.pdfZoom.addEventListener("click", this.zoom.bind(this), true);
    } else {
        this.pdfZoom.style.display = "none";
    }

    this.toPage(1);
    this.calculateDimensions();

    window.setInterval(this.calculateDimensions.bind(this), 1000);


    window.addEventListener("resize", this.calculateDimensions.bind(this), true);
};

pimcore.pdf.prototype.calculateDimensions = function () {

    // zoom button
    this.pdfZoom.style.fontSize = (this.pdfZoom.offsetHeight/1.5) + "px";
    this.pdfZoom.style.lineHeight = (this.pdfZoom.offsetHeight) + "px";

    // arrows
    var buttonWidth = this.pdfButtonRight.offsetWidth;
    if(!buttonWidth) {
        buttonWidth = this.pdfButtonLeft.offsetWidth;
    }

    this.pdfArrowRight.style.borderWidth = Math.round(buttonWidth/3) + "px";
    this.pdfArrowRight.style.marginRight = Math.round(buttonWidth/3) + "px";
    this.pdfArrowLeft.style.borderWidth = Math.round(buttonWidth/3) + "px";
    this.pdfArrowLeft.style.marginLeft = Math.round(buttonWidth/3) + "px";

    // check fullscreen
    if(document.mozFullScreenElement || document.webkitFullScreenElement || document.msFullScreenElement
        || document.fullScreenElement || document.webkitCurrentFullScreenElement || document.currentFullScreenElement
        || document.mozCurrentFullScreenElement || document.msCurrentFullScreenElement) {

    } else if(this.containerEl.classList && (this.containerEl.requestFullscreen
        || this.containerEl.mozRequestFullScreen || this.containerEl.webkitRequestFullscreen
        || this.containerEl.msRequestFullscreen)) {
        this.containerEl.classList.remove("pimcore-pdfFullscreen");
    }



    var im;
    var maxHeight = window.innerHeight;
    for(var i=0; i<this.data.pages.length; i++) {
        im = this.data.pages[i]["node"].getElementsByTagName("img")[0];
        if(im) {
            im.style.maxHeight = maxHeight + "px";
        }
    }

    // this is because of firefox, maybe there's a better solution for that
    // the problem is that the pdfPageContainer DIV is always 100% wide, although he is floating, ... Chrome & IE works
    /*if(navigator.userAgent.match(/(Firefox)/)) {
        var imgEl;
        for(var i=0; i<this.data.pages.length; i++) {
            if(!this.data.pages[i]["node"].classList.contains("hidden")) {
                imgEl = this.data.pages[i]["node"].getElementsByTagName("img")[0];
                if(imgEl && imgEl.offsetWidth > 50) {
                    imgEl.parentNode.style.width = imgEl.offsetWidth + "px";
                }
            }
        }
    }*/
};

pimcore.pdf.prototype.buttonHover = function () {
    this.style.opacity = "0.8";
};

pimcore.pdf.prototype.buttonHoverOut = function () {
    this.style.opacity = "0.6";
};

pimcore.pdf.prototype.addImageToPage = function (page) {
    var imgContainer, img, hotspot, hd, o, l;

    if(this.data.pages[page] && !this.data.pages[page]["detailLoaded"]) {
        if(this.data.pages[page]["detail"]) {
            imgContainer = document.createElement("div");
            imgContainer.className = "pimcore-pdfPageContainer";
            this.data.pages[page]["node"].appendChild(imgContainer);

            img = document.createElement("img");
            img.setAttribute("src", this.data.pages[page]["detail"]);
            imgContainer.appendChild(img);

            if(this.data.pages[page]["hotspots"] && this.data.pages[page]["hotspots"].length > 0) {
                for(o=0; o<this.data.pages[page]["hotspots"].length; o++) {
                    hd = this.data.pages[page]["hotspots"][o];
                    hotspot = document.createElement("div");
                    hotspot.className = "pimcore-pdfHotspot";
                    hotspot.style.width = hd["width"] + "%";
                    hotspot.style.height = hd["height"] + "%";
                    hotspot.style.top = hd["top"] + "%";
                    hotspot.style.left = hd["left"] + "%";

                    hotspot.addEventListener("mouseover", function () {
                        this.style.opacity = "0.5";
                    }, true);
                    hotspot.addEventListener("mouseout", function () {
                        this.style.opacity = "0.2";
                    }, true);

                    if(hd["data"] && hd["data"].length > 0) {
                        for(l=0; l<hd["data"].length; l++) {
                            if(hd["data"][l]["type"] == "link" && hd["data"][l]["value"]) {
                                hotspot.addEventListener("click", function (data) {
                                    window.open(data["value"]);
                                }.bind(hotspot, hd["data"][l]), true);
                                break;
                            }
                        }
                    }

                    imgContainer.appendChild(hotspot);
                }
            }
        } else {
            this.data.pages[page]["node"].innerHTML = "&nbsp;";
        }
        this.data.pages[page]["detailLoaded"] = true;
    }
};

pimcore.pdf.prototype.zoom = function () {

    // provide original pdf for mobile users
    if(navigator.userAgent.match(/(iPad|iPhone|Android)/)) {
        this.download();
    } else {
        var elem = this.containerEl;
        elem.classList.add("pimcore-pdfFullscreen");
        if (elem.requestFullscreen) {
          elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) {
          elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) {
          elem.webkitRequestFullscreen();
        }  else if (elem.msRequestFullscreen) {
          elem.msRequestFullscreen();
        } else {
            // fallback
            this.containerEl.originalParent = this.containerEl.parentNode;
            document.body.appendChild(this.containerEl);
        }
    }
};

pimcore.pdf.prototype.closeFullScreen = function (page) {
    if(this.containerEl.originalParent) {
        this.containerEl.originalParent.appendChild(this.containerEl);
        this.containerEl.className = this.containerEl.className.replace(/pimcore-pdfFullscreen/,"")
    }

    if (document.cancelFullScreen) {
        document.cancelFullScreen();
    } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen();
    } else if (document.webkitCancelFullScreen) {
        document.webkitCancelFullScreen();
    }  else if (document.msCancelFullScreen) {
        document.msCancelFullScreen();
    }
};

pimcore.pdf.prototype.download = function () {
    window.open(this.data.pdf);
};

pimcore.pdf.prototype.toPage = function (page) {

    // hide all pages
    for(var i=0; i<this.data.pages.length; i++) {
        if(this.data.pages[i]["node"].className.indexOf("hidden") < 0) {
            this.data.pages[i]["node"].className += " hidden";
        }
    }

    // get first page to show
    while(page % 2 !== 0) {
        page--;
    }

    for(i=page; i<=(page+1); i++) {
        if(this.data.pages[i]) {
            this.data.pages[i]["node"].className  = this.data.pages[i]["node"].className.replace("hidden", "");
        }
    }

    this.currentPage = page;

    if(page < 1) {
        this.pdfButtonLeft.style.display = "none";
    } else {
        this.pdfButtonLeft.style.display = "block";
    }

    if(page >= (this.data.pages.length-2)) {
        this.pdfButtonRight.style.display = "none";
    } else {
        this.pdfButtonRight.style.display = "block";
    }

    // pre-load next x pages
    for(i=page; i<=(page+4); i++) {
        this.addImageToPage(i);
    }

    this.calculateDimensions();
};

pimcore.pdf.prototype.nextPage = function () {
    this.toPage(this.currentPage+3);
};

pimcore.pdf.prototype.prevPage = function () {
    this.toPage(this.currentPage-2);
};

