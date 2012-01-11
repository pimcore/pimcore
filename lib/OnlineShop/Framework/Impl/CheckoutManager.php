<?php

class OnlineShop_Framework_Impl_CheckoutManager implements OnlineShop_Framework_ICheckoutManager {

    const CURRENT_STEP = "checkout_current_step";
    const FINISHED = "checkout_finished";
    const COMMITTED = "checkout_committed";

    private $checkoutSteps;
    private $checkoutStepOrder;
    private $currentStep;
    private $finished = false;
    private $committed = false;

    private $parentFolderId = 1;
    private $orderClassname;
    private $orderItemClassname;
    private $confirmationMail;

    /**
     * @var OnlineShop_Framework_ICommitOrderProcessor
     */
    private $commitOrderProcessor;
    private $commitOrderProcessorClassname; 

    /**
     * @var OnlineShop_Framework_ICart
     */
    private $cart;

    public function __construct(OnlineShop_Framework_ICart $cart, $config) {
        $this->cart = $cart;

        $this->parentFolderId = (string)$config->parentorderfolder;
        $this->commitOrderProcessorClassname = $config->commitorderprocessor->class;
        $this->orderClassname = (string)$config->orderstorage->orderClass;
        $this->orderItemClassname = (string)$config->orderstorage->orderItemClass;
        $this->confirmationMail = (string)$config->mails->confirmation;
        foreach($config->steps as $step) {
            $step = new $step->class($this->cart);
            $this->checkoutStepOrder[] = $step;
            $this->checkoutSteps[$step->getName()] = $step;
        }

        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
        $this->finished = $env->getCustomItem(self::FINISHED . "_" . $this->cart->getId());
        $this->committed = $env->getCustomItem(self::COMMITTED . "_" . $this->cart->getId());
        $this->currentStep = $this->checkoutSteps[$env->getCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId())];

        if(empty($this->currentStep) && !$this->isFinished()) {
            $this->currentStep = $this->checkoutStepOrder[0];  
        }

    }

    protected function getCommitOrderProcessor() {
        if(!$this->commitOrderProcessor) {
            $this->commitOrderProcessor = new $this->commitOrderProcessorClassname();
            $this->commitOrderProcessor->setParentOrderFolder($this->parentFolderId);
            $this->commitOrderProcessor->setOrderClass($this->orderClassname);
            $this->commitOrderProcessor->setOrderItemClass($this->orderItemClassname);
            $this->commitOrderProcessor->setConfirmationMail($this->confirmationMail);
        }
        return $this->commitOrderProcessor;
    }

    /**
     * @return bool
     */
    public function commitOrder() {
        if($this->committed) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Cart already committed.");
        }

        if(!$this->isFinished()) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Checkout not finished yet.");
        }

        $result = $this->getCommitOrderProcessor()->commitOrder($this->cart);
        $this->committed = true;

        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
        $env->removeCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId());
        $env->removeCustomItem(self::FINISHED . "_" . $this->cart->getId());
        $env->removeCustomItem(self::COMMITTED . "_" . $this->cart->getId());
        $env->save();

        return $result;
    }

    /**
     * @param OnlineShop_Framework_ICheckoutStep $step
     * @param  $data
     * @return bool
     */
    public function commitStep(OnlineShop_Framework_ICheckoutStep $step, $data) {

        $indexCurrentStep = array_search($this->currentStep, $this->checkoutStepOrder);
        $index = array_search($step, $this->checkoutStepOrder);

        if($indexCurrentStep < $index) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("There are uncommitted previous steps.");
        }
        $result = $step->commit($data);

        if($result) {
            $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
            $index = array_search($step, $this->checkoutStepOrder);
            $index++;
            if(count($this->checkoutStepOrder) > $index) {
                $this->currentStep = $this->checkoutStepOrder[$index];
                $this->finished = false;

                $env->setCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId(), $this->currentStep->getName());
            } else {
                $this->currentStep = null;
                $this->finished = true;

                $env->setCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId(), null);
            }
            $env->setCustomItem(self::FINISHED . "_" . $this->cart->getId(), $this->finished);
            $env->setCustomItem(self::COMMITTED . "_" . $this->cart->getId(), $this->committed);

            $this->cart->save();
            $env->save();
        }
        return $result;
    }

    /**
     * @return OnlineShop_Framework_ICart
     */
    public function getCart() {
        return $this->cart;
    }

    /**
     * @param  $stepname
     * @return OnlineShop_Framework_ICheckoutStep
     */
    public function getCheckoutStep($stepname) {
        return $this->checkoutSteps[$stepname];
    }

    /**
     * @return array(OnlineShop_Framework_ICheckoutStep)
     */
    public function getCheckoutSteps() {
        return $this->checkoutStepOrder;
    }

    /**
     * @return OnlineShop_Framework_ICheckoutStep
     */
    public function getCurrentStep() {
        return $this->currentStep;
    }

    /**
     * @return bool
     */
    public function isFinished() {
        return $this->finished;
    }

    public function isCommitted() {
        return $this->committed;
    }
}
