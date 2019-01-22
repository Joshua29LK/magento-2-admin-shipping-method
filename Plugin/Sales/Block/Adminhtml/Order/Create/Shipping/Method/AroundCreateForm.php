<?php /** @noinspection ALL */
/** @noinspection PhpUndefinedClassInspection */

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
namespace Bss\AdminShippingMethod\Plugin\Sales\Block\Adminhtml\Order\Create\Shipping\Method;

use Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form;
use Bss\AdminShippingMethod\Helper\Data;

class AroundCreateForm
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * AfterCreateForm constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Form $subject
     * @param $result
     * @return bool
     */
    public function aroundIsMethodActive(Form $subject, callable $proceed, $code)
    {
        $storeId = $subject->getAddress()->getQuote()->getStoreId();
        $selectStore = $this->helper->getPreSelect($storeId);
        $getActive = $subject->getActiveMethodRate();
        if (!$getActive) {
            if ($selectStore) {
                if ($code == "adminshippingmethod_adminshippingmethod") {
                    return true;
                }
            }
        }
        return $proceed($code);
    }
}
