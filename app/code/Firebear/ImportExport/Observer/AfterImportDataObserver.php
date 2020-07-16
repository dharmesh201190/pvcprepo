<?php
/**
 * AfterImportDataObserver
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Observer;

use Magento\CatalogUrlRewrite\Observer\AfterImportDataObserver as MagentoAfterImportDataObserver;
use Magento\Framework\Event\Observer;

/**
 * Class AfterImportDataObserver
 * @package Firebear\ImportExport\Observer
 */
class AfterImportDataObserver extends MagentoAfterImportDataObserver
{
    /**
     * @var array
     */
    protected $vitalForGenerationFields = [
        'sku',
        'url_key',
        'url_path',
        'name',
        'visibility',
        'url_key_create_redirect',
        'save_rewrites_history',
    ];

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            parent::execute($observer);
        } catch (\Exception $e) {
            if (\method_exists($this->import, 'addLogWriteln')
                && \method_exists($this->import, 'getOutput')
            ) {
                $this->import->addLogWriteln(
                    $e->getMessage(),
                    $this->import->getOutput(),
                    'error'
                );
            }
        }
    }
}
