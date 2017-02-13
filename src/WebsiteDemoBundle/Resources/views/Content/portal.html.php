<?php $this->extend('WebsiteDemoBundle::layout.html.php') ?>

<div id="portalHeader" class="carousel header slide" data-ride="carousel" <?= ($this->editmode) ? 'data-interval="false"' : '' ?>>

    <!-- Indicators -->
    <ol class="carousel-indicators">
        <li data-target="#portalHeader" data-slide-to="0" class="active"></li>
        <li data-target="#portalHeader" data-slide-to="1"></li>
        <li data-target="#portalHeader" data-slide-to="2"></li>
    </ol>

    <?php
    /** @var \Pimcore\Model\Document\Tag\Select $carouselSlides */
    $carouselSlides = $this->select('carouselSlides', [
        'width'  => 70,
        'reload' => true,
        'store'  => [[1, 1], [2, 2], [3, 3], [4, 4]]
    ]);
    ?>

    <div class="carousel-inner">

        <?php
        $count = $carouselSlides->getData();
        if (!$count) {
            $count = 1;
        }

        for ($i = 0; $i < $count; $i++): ?>
            <?php
            $itemClass = [
                'portal-slide-' . ($i + 1)
            ];

            if ($i === 0) {
                $itemClass[] = 'active';
            }
            ?>

            <div class="item <?= implode(' ', $itemClass) ?>">

                <?php
                /** @var \Pimcore\Model\Document\Tag\Image $cImage */
                $cImage = $this->image('cImage_' . $i, [
                    'thumbnail' => 'portalCarousel',
                    'reload'    => true,
                    'hidetext'  => true,
                    'title'     => 'Drag Image Here',
                    'width'     => 150,
                    'height'    => 70,
                    'dropClass' => 'portal-slide-' . ($i + 1)
                ]);
                ?>

                <?= $cImage->frontend() ?>

                <div class="container">
                    <div class="carousel-caption">
                        <?php if ($this->editmode): ?>
                            <?= $cImage ?>
                            <br><br>
                        <?php endif; ?>

                        <h1><?= $this->input('cHeadline_' . $i); ?></h1>
                        <div class="caption"><?= $this->textarea('cText_' . $i); ?></div>
                        <div class="margin-bottom-10">
                            <?= $this->link('cLink_' . $i, [
                                'class' => 'btn btn-large btn-default'
                            ]); ?>
                        </div>
                    </div>
                </div>

            </div>

        <?php endfor; ?>
    </div>

    <a class="left carousel-control" href="#portalHeader" data-slide="prev"><span class="glyphicon glyphicon-chevron-left"></span></a>
    <a class="right carousel-control" href="#portalHeader" data-slide="next"><span class="glyphicon glyphicon-chevron-right"></span></a>
</div>

<?php if ($this->editmode): ?>

    <div class="container" style="padding-bottom: 40px">
        Number of Slides: <?= $carouselSlides ?>
    </div>

<?php endif; ?>

<div class="container">
    <?= $this->areablock('content'); ?>
</div>
