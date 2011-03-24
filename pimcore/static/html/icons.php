<?php


$icons = scandir("../img/icon/");

?>


<?php foreach ($icons as $icon) { ?>
    <img src="../img/icon/<?php echo $icon; ?>" title="<?php echo $icon; ?>" alt="<?php echo $icon; ?>" />
<?php } ?>


