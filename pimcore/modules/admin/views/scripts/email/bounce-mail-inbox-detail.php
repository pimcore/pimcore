<!DOCTYPE html>
<html>
<head>

<style type="text/css">
    body {
        margin: 0;
        padding: 10px;
        font-family: Arial;
        font-size: 12px;
    }

    h2 {
        border-bottom: 1px solid #000;
    }

    table {
        border-left: 1px solid #000;
        border-top: 1px solid #000;
        border-collapse: collapse;
    }

    td, th {
        border-right: 1px solid #000;
        border-bottom: 1px solid #000;
        padding: 5px;
    }

    th {
        text-align: left;
    }

</style>


</head>

<body>

<h2><?php echo iconv(mb_detect_encoding($message->subject), "UTF-8", $this->message->subject); ?></h2>

<table>
    <tr>
        <th><?php echo $this->translate("from"); ?></th>
        <td><?php echo $this->message->from; ?></td>
    </tr>
    <tr>
        <th><?php echo $this->translate("to"); ?></th>
        <td><?php echo $this->message->to; ?></td>
    </tr>
</table>

<h3><?php echo $this->translate("message_parts"); ?></h3>

<?php if(!$this->message->isMultiPart()) { ?>
    <pre>
        <?php echo iconv(mb_detect_encoding($this->message->getContent()), "UTF-8", $this->message->getContent()); ?>
    </pre>
<?php } else { ?>
    <?php
        foreach (new RecursiveIteratorIterator($this->message) as $part) {
            try {
                echo "<pre>";
                echo "\n------------------------\n";
                echo iconv(mb_detect_encoding($part), "UTF-8", $part);
                echo "</pre>";
            } catch (\Zend_Mail_Exception $e) {
                // ignore
            }
        }
    ?>
<?php } ?>

</body>
</html>