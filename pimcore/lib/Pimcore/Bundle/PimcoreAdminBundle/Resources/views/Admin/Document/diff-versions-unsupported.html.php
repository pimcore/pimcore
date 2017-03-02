<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <style type="text/css">
        
        html, body {
            height: 100%;
        }

        #message {
            position: absolute;
            width: 100%;
            left: 0;
            top: 45%;
            text-align: center;
            font-family: Arial , Tahoma, Verdana, sans-serif;
            font-size: 16px;
            color: darkred;
        }
    </style>
</head>
<body>

    <div id="message">
        <?= $this->translate("unsupported_feature"); ?>
        <br>
        <br>
        <b>wkhtmltoimage binary and PHP extension Imagick are required!</b>
    </div>

</body>
</html>