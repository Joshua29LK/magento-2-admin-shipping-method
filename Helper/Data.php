<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category  BSS
 * @package   Bss_AdminShippingMethod
 * @author    Extension Team
 * @copyright Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AdminShippingMethod\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->context = $context;
    }

    /**
     * @param $scope
     * @return mixed
     */
    public function getPreSelect($scope)
    {
        return $this->scopeConfig->isSetFlag(
            'carriers/adminshippingmethod/pre_select',
            ScopeInterface::SCOPE_STORE,
            $scope
        );
    }

    /**
     * @param $scope
     * @return mixed
     */
    public function getCreatInvoice($scope)
    {
        return $this->scopeConfig->isSetFlag(
            'carriers/adminshippingmethod/createinvoice',
            ScopeInterface::SCOPE_STORE,
            $scope
        );
    }

    /**
     * @param $scope
     * @return mixed
     */
    public function getCreatShipment($scope)
    {
        return $this->scopeConfig->isSetFlag(
            'carriers/adminshippingmethod/createshipment',
            ScopeInterface::SCOPE_STORE,
            $scope
        );
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getName($storeId)
    {
        return $this->scopeConfig->getValue(
            'carriers/adminshippingmethod/name',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getTitle($storeId)
    {
        return $this->scopeConfig->getValue(
            'carriers/adminshippingmethod/title',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    
    /**
     * @param $storeId
     * @return mixed
     */
    public function getError($storeId)
    {
        return $this->scopeConfig->getValue(
            'carriers/adminshippingmethod/specificerrmsg',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
