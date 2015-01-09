<!DOCTYPE html>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script type="text/javascript" src="/pimcore/static/js/lib/jquery.min.js?_dc=<?php echo \Pimcore\Version::getVersion(); ?>"></script>

    <style type="text/css">

        body {
            padding: 20px;
            margin:0;
        }

        #frameContainer {
            margin:0 auto;
            overflow: hidden;
            position: relative;
            width: 1px;
            height: 1px;
        }

        #frame {
            border: none;
            position: absolute;
            z-index: 10;
            left:0;
            top:0;
            -moz-transform-origin: 0 0;
            -o-transform-origin: 0 0;
            -webkit-transform-origin: 0 0;
            -ms-transform-origin: 0 0;
            transform-origin: 0 0;
            visibility: hidden;
        }

        #background {
            position: absolute;
            z-index: 9;
        }



        /* iPhone specifiy */
        .iphone #background {
            width:245px;
            height: 480px;
            background: url(/pimcore/static/img/assets/page-preview-iphone-vertical.png);
        }

        .iphone #frame {
            top: 97px;
            left: 20px;
            width: 208px;
            height: 271px;
        }

        .horizontal.iphone #frame {
            height: 175px;
            width: 312px;
            top: 29px;
            left: 84px;
        }

        .horizontal.iphone #background {
            background: url(/pimcore/static/img/assets/page-preview-iphone-horizontal.png);
            height: 245px;
            width: 480px;
        }


    </style>

</head>

<body>

<div id="frameContainer" class="iphone">
    <iframe id="frame" name="frame" style="" onload="adjustZoom()" src="<?php echo $this->previewUrl; ?>"></iframe>
    <div id="background" onclick="flipToggle()"></div>
</div>


<script type="text/javascript">

    function flipToggle () {
        $("#frameContainer").toggleClass("horizontal");
        adjustZoom();
    }

    function roundNumber(num, dec) {
    	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
    	return result;
    }

    function adjustZoom () {

        // reset all element specific styles
        $("#frame").attr("style", "");

        var frameWidth = $("#frame").width();
        var frameHeight = $("#frame").height();
        var detectionReferenceFrameWidth = 400;


        // set the frame to 400x600 to determine if the containing page is mobile or not
        $("#frame").width(detectionReferenceFrameWidth);
        $("#frame").height(600);

        window["frame"].scrollTo(100000, 0);

        // this is necessary because webkit and IE have some timing issues when resizing an iframe
        window.setTimeout(function() {

            var xScroll = 0;
            if (window["frame"].pageXOffset) {
              xScroll = window["frame"].pageXOffset;
            } else if (window["frame"].document.documentElement && window["frame"].document.documentElement.scrollLeft) {
              xScroll = window["frame"].document.documentElement.scrollLeft;
            } else if (window["frame"].document.body) {
              xScroll = window["frame"].document.body.scrollLeft;
            }

            // set the frame dimensions to the device dimensions
            $("#frame").width(frameWidth);
            $("#frame").height(frameHeight);

            // if xScroll is 0 => mobile or flexible site
            var pagePadding = 0;
            if(xScroll > 0) {
                pagePadding = 50;
                xScroll += (detectionReferenceFrameWidth-frameWidth);
            } else {
                pagePadding = 160;
            }

            var pageWidth = xScroll + $(window["frame"].document.body).width();
            pageWidth += pagePadding;

            var scale = 0;
            scale = (frameWidth / pageWidth);


            $("#frame").css("-moz-transform","scale(" + scale + ")");
            $("#frame").css("-webkit-transform","scale(" + scale + ")");
            $("#frame").css("-ms-transform","scale(" + scale + ")");
            $("#frame").css("transform","scale(" + scale + ")");

            $("#frame").width(frameWidth / scale);
            $("#frame").height(frameHeight / scale);

            $("#frame").css("visibility","visible");

            $("#frameContainer").width($("#background").width());
            $("#frameContainer").height($("#background").height());
        }, 20);
    }

</script>

</body>
</html>
