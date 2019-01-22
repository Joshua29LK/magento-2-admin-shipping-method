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
namespace Bss\AdminShippingMethod\Model;
 
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Backend\App\Area\FrontNameResolver;
use Bss\AdminShippingMethod\Helper\Data;
use Magento\Framework\App\RequestInterface;

class ShippingMethod extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'adminshippingmethod';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var Data
     */
    protected $help;

    /**
     * @var RequestInterface
     */
    protected $requestInterface;
    /**
     * ShippingMethod constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param State $appState
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        State $appState,
        Data $help,
        RequestInterface $requestInterface,
        array $data = []
    ) {
        $this->help = $help;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->appState = $appState;
        $this->requestInterface = $requestInterface;
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $data
        );
    }

    /**
     * Checks if user is logged in as admin
     *
     * @return bool
     */
    protected function isAdmin()
    {
        if ($this->appState->getAreaCode() === FrontNameResolver::AREA_CODE) {
            return true;
        }
        return false;
    }

    /**
     * FreeShipping Rates Collector
     *
     * @param RateRequest $request //@SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active') || !$this->isAdmin()) {
            return false;
        }

        /** @var Result $result */
        $result = $this->rateResultFactory->create();
        /** @var Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier('adminshippingmethod');
        $method->setCarrierTitle($this->help->getTitle($this->requestInterface->getPostValue()['store_id']));

        $method->setMethod('adminshippingmethod');
        $method->setMethodTitle($this->help->getName($this->requestInterface->getPostValue()['store_id']));

        $method->setPrice('0.00');
        $method->setCost('0.00');
        $result->append($method);

        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['adminshippingmethod' => $this->help->getName($this->requestInterface->getPostValue()['store_id'])];
    }

    /**
     * @param \Magento\Framework\DataObject $request
     * @return $this|bool|ShippingMethod|false|\Magento\Framework\Model\AbstractModel|AbstractCarrier
     */
    public function checkAvailableShipCountries(\Magento\Framework\DataObject $request)
    {
        $speCountriesAllow = $this->getConfigData('sallowspecific');
        /*
         * for specific countries, the flag will be 1
         */
        if ($speCountriesAllow && $speCountriesAllow == 1) {
            return $this->checkValue($request);
        }
        return $this;
    }

    /**
     * @param $request
     * @return bool
     */
    public function checkValue($request)
    {
        $showMethod = $this->getConfigData('showmethod');
        $availableCountries = [];
        if ($this->getConfigData('specificcountry')) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
        }
        if ($availableCountries && in_array($request->getDestCountryId(), $availableCountries)) {
            return $this;
        } elseif ($showMethod && (!$availableCountries || $availableCountries && !in_array(
            $request->getDestCountryId(),
            $availableCountries
        ))
        ) {
            /** @var $error */
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $errorMsg = $this->help->getError($this->requestInterface->getPostValue()['store_id']);
            $error->setErrorMessage(
                $errorMsg ? $errorMsg : __(
                    'Sorry, but we can\'t deliver to the destination country with this shipping module.'
                )
            );

            return $error;
        } else {
            /*
             * The admin set not to show the shipping module if the delivery country
             * is not within specific countries
             */
            return false;
        }
    }
}
