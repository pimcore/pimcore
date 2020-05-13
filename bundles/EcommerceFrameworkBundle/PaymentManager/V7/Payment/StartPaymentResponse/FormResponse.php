<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Symfony\Component\Form\FormBuilderInterface;

class FormResponse extends AbstractResponse
{
    /**
     * @var FormBuilderInterface
     */
    protected $form;

    /**
     * FormResponse constructor.
     *
     * @param AbstractOrder $order
     * @param FormBuilderInterface $form
     */
    public function __construct(AbstractOrder $order, FormBuilderInterface $form)
    {
        parent::__construct($order);
        $this->form = $form;
    }

    /**
     * @return FormBuilderInterface
     */
    public function getForm(): FormBuilderInterface
    {
        return $this->form;
    }
}
