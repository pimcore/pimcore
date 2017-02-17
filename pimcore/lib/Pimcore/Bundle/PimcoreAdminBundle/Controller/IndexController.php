<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Pimcore\Bundle\PimcoreBundle\Configuration\TemplatePhp;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\Element\Service;
use Pimcore\Tool;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller
{
    /**
     * @Route("/", name="admin_index")
     * @TemplatePhp()
     */
    public function indexAction()
    {
        $view = new ViewModel([
            'config' => $this->getParameter('pimcore.config')
        ]);

        $this
            ->addMaintenanceConfig($view)
            ->addMailConfig($view)
            ->addReportConfig($view)
            ->addCustomViewConfig($view)
            ->addSystemVars($view)
            ->addCsrfToken($view);

        return $view;
    }

    protected function addMaintenanceConfig(ViewModel $view)
    {
        // check maintenance
        $maintenance_enabled = false;

        $manager = Model\Schedule\Manager\Factory::getManager("maintenance.pid");

        $lastExecution = $manager->getLastExecution();
        if ($lastExecution) {
            if ((time() - $lastExecution) < 3660) { // maintenance script should run at least every hour + a little tolerance
                $maintenance_enabled = true;
            }
        }

        $view->maintenance_enabled = json_encode($maintenance_enabled);

        return $this;
    }

    /**
     * @param ViewModel $view
     * @return $this
     */
    protected function addMailConfig(ViewModel $view)
    {
        //mail settings
        $mailIncomplete = false;
        if ($view->config->email) {
            if (!$view->config->email->debug->emailaddresses) {
                $mailIncomplete = true;
            }
            if (!$view->config->email->sender->email) {
                $mailIncomplete = true;
            }
            if ($view->config->email->method == "smtp" && !$view->config->email->smtp->host) {
                $mailIncomplete = true;
            }
        }

        $view->mail_settings_complete = json_encode(!$mailIncomplete);

        return $this;
    }

    /**
     * @param ViewModel $view
     * @return $this
     */
    protected function addReportConfig(ViewModel $view)
    {
        $view->report_config = Config::getReportConfig();

        return $this;
    }

    /**
     * @param ViewModel $view
     * @return $this
     */
    protected function addCustomViewConfig(ViewModel $view)
    {
        $cvData = [];

        // still needed when publishing objects
        $cvConfig = Tool::getCustomViewConfig();

        if ($cvConfig) {
            foreach ($cvConfig as $node) {
                $tmpData = $node;
                // backwards compatibility
                $treeType = $tmpData["treetype"] ? $tmpData["treetype"] : "object";
                $rootNode = Service::getElementByPath($treeType, $tmpData["rootfolder"]);

                if ($rootNode) {
                    $tmpData["rootId"]         = $rootNode->getId();
                    $tmpData["allowedClasses"] = $tmpData["classes"] ? explode(",", $tmpData["classes"]) : null;
                    $tmpData["showroot"]       = (bool)$tmpData["showroot"];

                    // Check if a user has privileges to that node
                    if ($rootNode->isAllowed("list")) {
                        $cvData[] = $tmpData;
                    }
                }
            }
        }

        $view->customview_config = $cvData;

        return $this;
    }

    /**
     * @param ViewModel $view
     * @return $this
     */
    protected function addSystemVars(ViewModel $view)
    {
        // upload limit
        $max_upload = filesize2bytes(ini_get("upload_max_filesize") . "B");
        $max_post   = filesize2bytes(ini_get("post_max_size") . "B");
        $upload_mb  = min($max_upload, $max_post);

        $view->upload_max_filesize = $upload_mb;

        // session lifetime (gc)
        $session_gc_maxlifetime = ini_get("session.gc_maxlifetime");
        if (empty($session_gc_maxlifetime)) {
            $session_gc_maxlifetime = 120;
        }

        $view->session_gc_maxlifetime = $session_gc_maxlifetime;

        return $this;
    }

    /**
     * @param ViewModel $view
     * @return $this
     */
    protected function addCsrfToken(ViewModel $view)
    {
        // TODO add CSRF token
        $view->csrfToken = 'foo';

        return $this;
    }
}
