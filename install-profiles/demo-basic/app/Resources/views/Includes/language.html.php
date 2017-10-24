<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<?php
/** @var \AppBundle\Templating\Helper\LanguageSwitcher $languageSwitcher */
$languageSwitcher = $this->languageSwitcher();
?>

<li class="dropdown">
    <a href="#" class="dropdown-toggle" id="languageSelector" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true">
        <?= $this->translate('Language') ?>
        <span class="caret"></span>
    </a>
    <ul class="dropdown-menu" aria-labelledby="languageSelector">
        <?php foreach ($languageSwitcher->getLocalizedLinks($this->document) as $link => $text): ?>

            <li>
                <a href="<?= $link ?>"><?= $text ?></a>
            </li>

        <?php endforeach; ?>
    </ul>
</li>
