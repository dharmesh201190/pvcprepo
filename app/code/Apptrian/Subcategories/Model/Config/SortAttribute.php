<?php
/**
 * @category  Apptrian
 * @package   Apptrian_Subcategories
 * @author    Apptrian
 * @copyright Copyright (c) 2017 Apptrian (http://www.apptrian.com)
 * @license   http://www.apptrian.com/license Proprietary Software License EULA
 */
 
namespace Apptrian\Subcategories\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class SortAttribute implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'name', 'label' => __('Name')],
            ['value' => 'meta_title', 'label' => __('Page Title')],
            ['value' => 'position', 'label' => __('Position')],
            ['value' => 'created_at', 'label' => __('Created Date')]
        ];
    }
}
