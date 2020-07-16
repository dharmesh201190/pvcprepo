<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Export;

trait Products
{
    use Entity;

    /**
     * @return mixed
     */
    public function getFieldsForExport()
    {
        $stockItemRows =  $this->fieldsCatalogInventory();
        $this->setHeaderColumns(1, $stockItemRows);
        $this->_headerColumns = $this->rowCustomizer->addHeaderColumns($this->_headerColumns);
        $subOptions = [];
        if (isset($this->_attributeColFactory)) {
            $attributeCollection = $this->_attributeColFactory->create()->addVisibleFilter()
                ->setOrder('attribute_code', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC);
            foreach ($attributeCollection as $attribute) {
                $subOptions[] = $attribute->getAttributeCode();
            }
            $this->_headerColumns = array_merge($this->_headerColumns, $subOptions);
        }
        return array_unique($this->_headerColumns);
    }
}
