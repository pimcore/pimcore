<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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

        .content-myTextarea {
            color: #0464BB;
            font-weight: bold;
            font-style: italic;
            text-shadow: 1px 1px #cccccc;
        }

        strong {
            font-weight: bold;
            color: #005c24;
        }

        #logo {
            text-align: center;
            padding: 0 0 10px 0;
        }

        #site ul {
            padding: 10px 0 10px 20px;
            list-style: circle;
        }

	</style>


    <div id="site">
        <div id="logo">
            <img src="/pimcore/static/img/logo-gray.png" />
        </div>

        <h1>Hello World!</h1>
        <p>
            This is just a simple example page.
            <br />
            To learn how to create templates with pimcore, please visit our <a href="http://www.pimcore.org/wiki/" target="_blank">documentation</a> or install the example data package.
            <br />
            <br />
        </p>

        <h2>What's next?</h2>
        <p>
            pimcore is in many ways different from other CMS/CMF.
            <br />
        </p>
        <ul>
            <li>There are no themes to create or adopt.<br />Just take your individual HTML/CSS and make it editable!</li>
            <li>Usually there's no need for modules or extensions, use the power of objects/classes!</li>
        </ul>
        <p>
            ...  this is the reason why we don't include a default site, because it isn't necessary ;-)
            <br />
        </p>

        <h2>Examples</h2>

        <h3>Simple WYSIWYG</h3>
        You can drag'n drop assets, documents, ...
        <?= $this->wysiwyg("myWysiwyg", ["height" => 130]); ?>

        <h3>Input &amp; Textarea</h3>
        <div class="input-textarea">
            Type something: <?= $this->input("myInput", ["width" => 400]); ?>
            ... styles are inherited ...
            <div class="content-myTextarea">
                <?= $this->textarea("myTextarea", ["width" => 400, "height" => 60]); ?>
            </div>
        </div>

        <h3>Images in a block element</h3>
        Press the button:
        <?php while($this->block("myImageBlock")->loop()) { ?>
            <?= $this->image("myImage", ["height" => 100]); ?>
        <?php } ?>

        <h3>Relations</h3>
        You can drop a single document, an asset or an object...
        <?= $this->href("myHref"); ?>
        ... and now multiple items ...
        <?= $this->multihref("myMultihref"); ?>

        <h3>Simple types</h3>
        Date: <?= $this->date("myDate"); ?>
        Checkbox: <br /><?= $this->checkbox("myCheckbox"); ?>
        <br style="clear: both;" />
        Link: <?= $this->link("myLink"); ?>
        <br />
        Number: <?= $this->numeric("myNumber"); ?>

        <h3>Selections</h3>
        <?= $this->select("mySelect", [
            "store" => [
                ["option1", "Option One"],
                ["option2", "Option Two"],
                ["option3", "Option Three"]
            ]
        ]); ?>
        ... and multiple seletions ...
        <?= $this->multiselect("multiselect", [
            "width" => 200,
            "height" => 100,
            "store" => [
                ["option1", "Option One"],
                ["option2", "Option Two"],
                ["option3", "Option Three"]
            ]
        ]) ?>

        <br />
        <strong>... and much more!
        <br />
        <strong>Check out our example data package for more advanced examples!</strong>



    </div>

</body>
</html>