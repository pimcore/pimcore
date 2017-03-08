<?php
/**
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $this
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine $view
 * @var \Pimcore\Bundle\PimcoreBundle\Templating\GlobalVariables\GlobalVariables $app
 */
?>

<?php
/** @var \WebsiteDemoBundle\Templating\LanguageSwitcher $languageSwitcher */
$languageSwitcher = $this->app->getContainer()->get('website_demo.language_switcher');
?>

<div class="languages">
    <div class="dropdown">
        <button class="btn btn-default btn-xs dropdown-toggle" type="button" id="languageSelector" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <?= $this->translate('Language') ?>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="languageSelector">
            <?php foreach ($languageSwitcher->getLocalizedLinks($this->document) as $link => $text): ?>

                <li>
                    <a href="<?= $link ?>"><?= $text ?></a>
                </li>

            <?php endforeach; ?>
        </ul>
    </div>
</div>
