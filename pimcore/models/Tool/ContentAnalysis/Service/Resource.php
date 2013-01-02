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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_ContentAnalysis_Service_Resource extends Pimcore_Model_Resource_Abstract {

    public function getOverviewData () {

        $summary = array(
            "title" => array(),
            "description" => array(),
            "url" => array(),
            "blocked" => array(),
            "image" => array(),
            "social" => array(),
            "headline" => array(),
            "meta" => array()
        );

        $robotsCondition = "robotsTxtBlocked = 0 AND robotsMetaBlocked = 0";

        $summary["title"]["dublicate"] = $this->db->fetchOne("SELECT SUM(amount) FROM (SELECT COUNT(*) AS amount FROM content_analysis WHERE LENGTH(title) > 0 AND " . $robotsCondition . " GROUP BY title HAVING amount > 1) dummy_alias");
        $summary["title"]["empty"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE LENGTH(title) < 1 AND " . $robotsCondition . "");
        $summary["title"]["tooShort"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE LENGTH(title) < 8 AND LENGTH(title) > 0 AND " . $robotsCondition . "");
        $summary["title"]["tooLong"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE LENGTH(title) > 70 AND " . $robotsCondition . "");

        $summary["description"]["dublicate"] = $this->db->fetchOne("SELECT SUM(amount) FROM (SELECT COUNT(*) AS amount FROM content_analysis WHERE LENGTH(description) > 0 AND " . $robotsCondition . " GROUP BY description HAVING amount > 1) dummy_alias");
        $summary["description"]["empty"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE LENGTH(description) < 1 AND " . $robotsCondition . "");

        $summary["headline"]["h1Missing"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE LENGTH(h1Text) < 1 AND " . $robotsCondition);

        $summary["url"]["tooLong"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE urlLength > 115 AND " . $robotsCondition);
        $summary["url"]["tooMuchParameters"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE urlParameters > 2 AND " . $robotsCondition);

        $summary["blocked"]["meta"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE robotsMetaBlocked > 0");
        $summary["blocked"]["txt"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE robotsTxtBlocked > 0");

        $summary["image"]["withoutAlt"] = $this->db->fetchOne("SELECT SUM(imgWithoutAlt) FROM content_analysis WHERE imgWithoutAlt > 1 AND " . $robotsCondition);

        $summary["social"]["facebookShares"] = $this->db->fetchOne("SELECT SUM(facebookShares) FROM content_analysis");
        $summary["social"]["googlePlusOne"] = $this->db->fetchOne("SELECT SUM(googlePlusOne) FROM content_analysis");

        $summary["meta"]["microdata"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE microdata > 0 AND " . $robotsCondition);
        $summary["meta"]["opengraph"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE opengraph > 0 AND " . $robotsCondition);
        $summary["meta"]["twitter"] = $this->db->fetchOne("SELECT COUNT(*) FROM content_analysis WHERE twitter > 0 AND " . $robotsCondition);

        return $summary;
    }
}
