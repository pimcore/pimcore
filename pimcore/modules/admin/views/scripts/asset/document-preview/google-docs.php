<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style type="text/css">

        html {
            height: 100%;
            overflow: hidden;
        }

        #flashcontent {
            height: 100%;
        }

        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

    </style>

</head>

<body>

    <iframe src="https://docs.google.com/viewer?embedded=true&url=<?php echo urlencode($this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() . $this->asset->getFullPath() . "?dc_=" . time()); ?>" frameborder="0" width="100%" height="100%"></iframe>

</body>
</html>