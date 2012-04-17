<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
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