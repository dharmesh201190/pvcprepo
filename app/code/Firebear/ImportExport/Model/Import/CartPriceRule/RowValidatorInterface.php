<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import\CartPriceRule;

interface RowValidatorInterface extends \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR_INVALID_TITLE= 'InvalidValueTITLE';

    const ERROR_TITLE_IS_EMPTY = 'EmptyTITLE';

    /**
     * Initialize validator
     *
     * @return $this
     */
    public function init($context);
}
