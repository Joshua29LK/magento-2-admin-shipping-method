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
 * @copyright Copyright (c) 2018-2019 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\AdminShippingMethod\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AutoCreateInvoice
 *
 * @package Bss\AdminPaymentMethod\Observer
 */
class AutoCreateInvoiceAndShipment implements ObserverInterface
{
    protected $helper;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transaction;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $convertOrder;

    /**
     * @var \Magento\Shipping\Model\ShipmentNotifier
     */
    protected $shipmentNotifier;

    /**
     * AutoCreateInvoice constructor.
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\DB\TransactionFactory $transaction
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\DB\TransactionFactory $transaction,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Bss\AdminShippingMethod\Helper\Data $helper
    ) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->messageManager = $messageManager;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     * @return null|void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $shipment = $order->getShippingMethod();
        $storeId = $order->getStoreId();
        // Check code payment method
        if ($shipment == 'adminshippingmethod_adminshippingmethod') {
     // Check option createshipment
            if ($this->helper->getCreatShipment($storeId)) {
              // to check order can ship or not
                if (!$order->canShip()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('You cant create the Shipment of this order.')
                    );
                }
                $orderShipment = $this->convertOrder->toShipment($order);

                foreach ($order->getAllItems() as $orderItem) {
                    // Check virtual item and item Quantity
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    $qty = $orderItem->getQtyToShip();
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
                    $orderShipment->addItem($shipmentItem);
                }
                $orderShipment->register();
                $orderShipment->getOrder()->setIsInProcess(true);
                try {
                    // Save created Order Shipment
                    $orderShipment->save();
                    $orderShipment->getOrder()->save();

                    // Send Shipment Email
                    $this->shipmentNotifier->notify($orderShipment);
                    $orderShipment->save();

                    //Show message create shipment
                    $this->messageManager->addSuccessMessage(__("Automatically generated Shipment."));
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        
            // Check option createinvoice
            $this->checkOptions($storeId, $order);
        }
    }

    /**
     * @param $storeId
     * @param $order
     */
    protected function checkOptions($storeId, $order)
    {
        if ($this->helper->getCreatInvoice($storeId) && $order->canInvoice()) {
            try {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->getOrder()->setIsInProcess(true);
                $transaction = $this->transaction->create()->addObject($invoice)->addObject($invoice->getOrder());
                $transaction->save();

                //Show message create invoice
                $this->messageManager->addSuccessMessage(__("Automatically generated Invoice."));
            } catch (\Exception $e) {
                $order->addStatusHistoryComment('Exception message: ' . $e->getMessage(), false);
                $order->save();
            }
        }
    }
}
