<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */

$this->extend('layout.html.php');

?>
<?php
    // set page meta-data
    $this->headTitle()->set($this->article->getTitle());
    $this->headMeta()->setDescription($this->article->getText(), 160);
?>
<section class="area-wysiwyg">

    <div class="page-header">
        <h1><?= $this->article->getTitle(); ?></h1>
    </div>

    <?= $this->render("Blog/meta.html.php", ['article' => $article]); ?>

    <hr />

    <?php if($this->article->getPosterImage()) { ?>
        <div class="image-container" style="width:<?= $this->article->getPosterImage()->getThumbnail("content")->getWidth() ?>px">
            <?= $this->article->getPosterImage()->getThumbnail("content")->getHTML() ?>

            <?php if($this->article->getPosterImage()->getHotspots()) { ?>

                <?php foreach($this->article->getPosterImage()->getHotspots() as $hotspot) { ?>

                    <div class="image-hotspot"
                         style="top: <?= $hotspot['top'] ?>%; left: <?= $hotspot['left'] ?>%; width: <?= $hotspot['width'] ?>%; height: <?= $hotspot['height'] ?>%">
                    </div>

                <?php } ?>

            <?php } ?>

            <?php if($this->article->getPosterImage()->getMarker()) { ?>

                <?php foreach($this->article->getPosterImage()->getMarker() as $marker) { ?>

                    <div class="image-marker"
                         style="top: <?= $marker['top'] ?>%; left: <?= $marker['left'] ?>%">
                    </div>

                <?php } ?>

            <?php } ?>

        </div>

        <br /><br />
    <?php } ?>

    <?= $this->article->getText(); ?>


    <div class="disqus">
        <div id="disqus_thread"></div>
        <script type="text/javascript">
            /* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
            var disqus_shortname = 'pimcore'; // required: replace example with your forum shortname

            /* * * DON'T EDIT BELOW THIS LINE * * */
            (function() {
                var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
                dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
                (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
            })();
        </script>
        <noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
        <a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>
    </div>

</section>
