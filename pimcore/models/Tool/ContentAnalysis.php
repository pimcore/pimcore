<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool;

use Pimcore\Model;
use Pimcore\Model\Tool;
use Pimcore\Helper\SocialMedia;

class ContentAnalysis extends Model\AbstractModel {

    /**
     *
     */
    public static function run () {

        $config = \Pimcore\Config::getReportConfig()->contentanalysis;
        if(!$config->enabled) {
            return;
        }

        include_once("simple_html_dom.php");

        $itemsPerCycle = 5;
        $instance = new self;
        $itemCount = $instance->getTotalIndexChangedItems();

        for($i=0; $i<ceil($itemCount/$itemsPerCycle); $i++) {
            $items = $instance->getIndexChangedItems($i*$itemsPerCycle, $itemsPerCycle);

            $data = array();
            $urls = array();

            foreach ($items as $item) {
                $record = array(
                    "id" => $item["id"],
                    "site" => $item["site"],
                    "type" => $item["type"],
                    "url" => $item["url"],
                    "typeReference" => $item["typeReference"],
                    "facebookShares" => 0,
                    "googlePlusOne" => 0,
                    "lastUpdate" => time(),
                    "h1Text" => "",
                    "title" => "",
                    "description" => "",
                    "imgWithoutAlt" => 0,
                    "imgWithAlt" => 0,
                    "robotsTxtBlocked" => 0,
                    "robotsMetaBlocked" => 0
                );

                $blockedRobots = false;

                $item["content"] = gzuncompress($item["content"]);

                $html = str_get_html($item["content"]);
                if($html) {
                    $record["links"] = count($html->find("a"));
                    $record["linksExternal"] = count($html->find("a[href^=http]"));
                    $record["h1"] = count($html->find("h1"));
                    $record["h2"] = count($html->find("h2"));
                    $record["h3"] = count($html->find("h3"));
                    $record["h4"] = count($html->find("h4"));
                    $record["h5"] = count($html->find("h5"));
                    $record["h6"] = count($html->find("h6"));

                    $h1 = $html->find("h1",0);
                    if($h1) {
                        $record["h1Text"] = html_entity_decode(strip_tags($h1->innertext));
                    }

                    $title = $html->find("title",0);
                    if($title) {
                        $record["title"] = html_entity_decode($title->innertext, null, "UTF-8");
                    }

                    $description = $html->find("meta[name=description]",0);
                    if($description) {
                        $record["description"] = html_entity_decode($description->content, null, "UTF-8");
                    }

                    $images = $html->find("img");
                    if($images) {
                        foreach ($images as $image) {
                            $alt = $image->alt;
                            if(empty($alt)) {
                                $record["imgWithoutAlt"]++;
                            } else {
                                $record["imgWithAlt"]++;
                            }
                        }
                    }

                    $record["microdata"] = count($html->find("[itemtype]"));
                    $record["opengraph"] = count($html->find("meta[property^=og:]"));
                    $record["twitter"] = count($html->find("meta[property^=twitter],meta[name^=twitter]"));

                    $record["robotsMetaBlocked"] = (int) ((bool) $html->find("meta[content*=noindex]"));

                    $html->clear();
                    unset($html);
                }

                $urlParts = parse_url($item["url"]);
                $record["host"] = $urlParts["host"];
                if(!array_key_exists("query", $urlParts)) {
                    $urlParts["query"] = "";
                }
                $record["urlLength"] = strlen($urlParts["path"] . $urlParts["query"]) + (empty($urlParts["query"]) ? 0 : 1);
                $record["urlParameters"] = substr_count($urlParts["query"], "=");

                try {
                    $robotsTester = new \Pimcore\Helper\RobotsTxt($urlParts["scheme"] . "://" . $urlParts["host"] . (array_key_exists("port", $urlParts) ? $urlParts["port"] : ""));
                    $record["robotsTxtBlocked"] = (int) $robotsTester->isUrlBlocked($item["url"], "Googlebot");
                } catch (\Exception $e) {

                }


                $data[$item["url"]] = $record;
                $urls[] = $item["url"];
            }

            try {
                $fbShares = SocialMedia::getFacebookShares($urls);
                if($fbShares && is_array($fbShares) && count($fbShares) > 0) {
                    foreach ($fbShares as $url => $shares) {
                        if(array_key_exists($url, $data)) {
                            $data[$url]["facebookShares"] = $shares;
                        }
                    }
                }
            } catch (\Exception $e) {

            }

            try {
                $googlePlus = SocialMedia::getGooglePlusShares($urls);
                if($googlePlus && is_array($googlePlus) && count($googlePlus) > 0) {
                    foreach ($googlePlus as $url => $shares) {
                        if(array_key_exists($url, $data)) {
                            $data[$url]["googlePlusOne"] = $shares;
                        }
                    }
                }
            } catch (\Exception $e) {

            }

            foreach ($data as $set) {
                $instance->update($set);
            }

            sleep(5);

            if($i % 20 === 0) {
                \Pimcore::collectGarbage();
            }
        }

        // statistics for sites
        $sites = new Model\Site\Listing();
        $sites = $sites->load();

        foreach ($sites as $site) {
            $service = new Tool\ContentAnalysis\Service();
            $overview = $service->getOverviewData($site->getId());
            self::saveAggregatedStatistics("pimcore_content_analysis_site_" . $site->getId(), $overview);
        }

        // statistics for default/main site
        $service = new Tool\ContentAnalysis\Service();
        $overview = $service->getOverviewData("default");
        self::saveAggregatedStatistics("pimcore_content_analysis_default", $overview);

        // save statistics data overall
        $service = new Tool\ContentAnalysis\Service();
        $overview = $service->getOverviewData();
        self::saveAggregatedStatistics("pimcore_content_analysis", $overview);
    }

    /**
     * @param $category
     * @param $overview
     */
    protected static function saveAggregatedStatistics($category, $overview) {
        foreach ($overview as $key => $value) {
            $event = Tool\Tracking\Event::getByDate($category, null, $key, date("d"), date("m"), date("Y"));
            $event->setData($value);
            $event->save();
        }
    }
}
