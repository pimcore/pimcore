<?php

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use Pimcore\Model\Asset;

class Version20200324141723 extends AbstractPimcoreMigration
{
    public function doesSqlMigrations(): bool
    {
        return false;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $listing = new Asset\Image\Thumbnail\Config\Listing;
        $thumbnails = $listing->load();

        foreach ($thumbnails as $thumbnail) {
            if ($thumbnail->hasMedias()) {
                $medias = [];
                foreach ($thumbnail->getMedias() as $key => $config) {
                    if (preg_match('/^[\d]+w$/', $key)) {
                        // old style key (e.g. 500w)
                        $maxWidth = str_replace('w', '', $key);
                        $key = '(max-width: ' . $maxWidth . 'px)';
                    }

                    $medias[$key] = $config;
                }

                $thumbnail->setMedias($medias);
                $thumbnail->save();
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $listing = new Asset\Image\Thumbnail\Config\Listing;
        $thumbnails = $listing->load();

        foreach ($thumbnails as $thumbnail) {
            if ($thumbnail->hasMedias()) {
                $medias = [];
                foreach ($thumbnail->getMedias() as $key => $config) {
                    if (preg_match('/max-width:[ ]+([\d]+)px/', $key, $matches)) {
                        // old style key (e.g. 500w)
                        $key = $matches[1] . 'w';
                        $medias[$key] = $config;
                    } else {
                        $this->writeMessage(sprintf('Unable to fully downgrade thumbnail configuration for `%s` because media query `%s` is not supported in versions prior 6.6.0. Ignoring this media query.', $thumbnail->getName(), $key));
                    }
                }

                $thumbnail->setMedias($medias);
                $thumbnail->save();
            }
        }
    }
}
