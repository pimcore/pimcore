<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<section class="area-blockquote" style="margin-top:20px;">

    <blockquote>
        <p><?= $this->input("quote"); ?></p>
        <small><?= $this->input("author"); ?></small>
    </blockquote>

</section>
