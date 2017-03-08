<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?><!DOCTYPE html>
<html lang="en">
<head>


    <style type="text/css" media="screen">
        body {
            background:#CCC;
        }

        #site {
            margin:0 auto;
            width: 21cm;
            padding:1cm 0 1cm 0;
        }

        .page {
            width: 21cm;
            padding:0.5cm 0 0.5cm 0;
            background:#FFF;
            -webkit-box-shadow: 0 0 4px 4px rgba(0, 0, 0, 0.2);
            -moz-box-shadow: 0 0 4px 4px rgba(0, 0, 0, 0.2);
            box-shadow: 0 0 4px 4px rgba(0, 0, 0, 0.2);

            position: relative;
        }


    </style>

    <style type="text/css">
        body {
            padding:0;
            margin: 0;
            font-family: "Lucida Sans Unicode", Arial;
            font-size: 14px;
        }

        #site {
            color:#65615E;
        }

        h1, h2, h3 {
            font-size: 18px;
            padding: 0 0 5px 0;
            border-bottom: 1px solid #001428;
            margin-bottom: 5px;
        }

        h3 {
            font-size: 14px;
            padding: 15px 0 5px 0;
            margin-bottom: 5px;
            border-color: #cccccc;
        }

        img {
            border: 0;
        }

        p {
            padding: 0 0 5px 0;
        }

        a {
            color: #000;
        }

        #logo {
            text-align: center;
            padding: 50px 0;
        }

        #logo hr {
            display: block;
            height: 1px;
            overflow: hidden;
            background: #BBB;
            border: 0;
            padding:0;
            margin:30px 0 20px 0;
        }

        .claim {
            text-transform: uppercase;
            color:#BBB;
        }

        #site ul {
            padding: 10px 0 10px 20px;
            list-style: circle;
        }

    </style>

    <?php if($this->printermarks) { ?>
        <link rel="stylesheet" type="text/css" href="/pimcore/static6/css/print/print-printermarks.css" media="print" />
    <?php } ?>


    <meta charset="UTF-8">
    <title>Example</title>
</head>

<body>



<div id="site">
    <div class="page">
        <div id="logo">
            <a href="http://www.pimcore.org/"><img style="width: 200px" src="/pimcore/static6/img/logo.png" /></a>
            <hr />
            <div class="claim">
                THE OPEN-SOURCE ENTERPRISE PLATFORM FOR PIM, CMS, DAM & COMMERCE based on Symfony
            </div>
        </div>
    </div>
</div>

</body>
</html>
