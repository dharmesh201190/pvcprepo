<?php
namespace LR\Matrixtable\Model\Import\ServiceUpgradeImport;
interface RowValidatorInterface extends \Magento\Framework\Validator\ValidatorInterface
{
       const ERROR_INVALID_TITLE= 'InvalidValueTITLE';
       const ERROR_SKU_IS_EMPTY = 'EmptySku';
       const ERROR_SHIPPINGDAYS_IS_EMPTY = 'EmptyShippingDays';
       const ERROR_SHIPPINGLABLE_IS_EMPTY = 'EmptyShippingLable';
       const ERROR_SHIPPINGPRICE_IS_EMPTY = 'EmptyShippingPrice';
       const ERROR_RECOMMENDED_IS_EMPTY = 'EmptyRecommended';
    /**
     * Initialize validator
     *
     * @return $this
     */
    public function init($context);
}