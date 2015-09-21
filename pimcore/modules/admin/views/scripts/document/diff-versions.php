<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">

    <style type="text/css">
        html, body {
            padding: 0;
            margin: 0;
        }

        body {
            text-align: center;
            position: relative;
        }

        img {
            max-width: 100%;
        }

        #left, #right {
            position: absolute;
            top:0;
            width:50%;
        }

        #left {
            left: 0;
            z-index: 1;
        }

        #right {
            right: 0;
            z-index: 2;
            border-left: 1px dashed darkred;
        }
    </style>
</head>
<body>

    <?php if($this->image) { ?>
        <img src="data:image/png;base64,<?= $this->image ?>">
    <?php } else { ?>
        <div id="left">
            <img src="data:image/png;base64,<?= $this->image1 ?>">
        </div>
        <div id="right">
            <img src="data:image/png;base64,<?= $this->image2 ?>">
        </div>
    <?php } ?>

</body>
</html>