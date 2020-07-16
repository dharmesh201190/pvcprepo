<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\RowCustomizer;

use Firebear\ImportExport\Model\Export\Product\Bundle\RowCustomizer as BundleRowCustomizer;

class Composite extends \Magento\CatalogImportExport\Model\Export\RowCustomizer\Composite
{

    /**
     * @param array $dataRow
     * @param int $productId
     * @return array
     */
    public function addData($dataRow, $productId)
    {
        foreach ($this->customizers as $key => $className) {
            if ($key == 'bundleProduct') {
                $className = BundleRowCustomizer::class;
            }
            $dataRow = $this->objectManager->get($className)->addData($dataRow, $productId);
        }
        return $dataRow;
    }

    /**
     * @param mixed $collection
     * @param int[] $productIds
     * @return mixed
     */
    public function prepareData($collection, $productIds)
    {
        foreach ($this->customizers as $key => $className) {
            if ($key == 'bundleProduct') {
                $className = BundleRowCustomizer::class;
            }
            $this->objectManager->get($className)->prepareData($collection, $productIds);
        }
    }
}
