<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Example</title>
</head>

<body>

	<style type="text/css">
		body {
			padding:0;
            margin: 0;
			font-family: "Lucida Sans Unicode", Arial;
			font-size: 14px;
		}

       #site {
           margin: 0 auto;
           width: 600px;
           padding: 30px 0 0 0;
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

		p {
			padding: 0 0 5px 0;
		}
		
		a {
			color: #000;
		}

        strong {
            font-weight: bold;
            color: #005c24;
        }

        #logo {
            text-align: center;
            padding: 0 0 10px 0;
        }

	</style>


    <div id="site">
        <div id="logo">
            <img src="http://demo.pimcore.org/website/static/img/logo-standard.png" width="300" />
        </div>

        <h1><?= $this->input("headline"); ?></h1>
        <p>
            <?= $this->wysiwyg("content"); ?>
        </p>

    </div>
</body>
</html>