<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Export\Adapter;

use Magento\Framework\ObjectManagerInterface;

/**
 * Export Adapter Factory
 */
class Factory
{
    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create New Export Adapter Instance
     *
     * @param string $className
     * @param array $data
     * @return \Magento\ImportExport\Model\Export\Adapter\AbstractAdapter
     * @throws \InvalidArgumentException
     */
    public function create($className, array $data = [])
    {
        if (!$className) {
            throw new \InvalidArgumentException('Incorrect class name');
        }
        return $this->_objectManager->create($className, $data);
    }
}
