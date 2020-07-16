<?php

/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Backend\App\Action\Context;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;

class Downfiltr extends JobController
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $collection;

    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $config;

    /**
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $createFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory
     */
    protected $typeCollection;

    /**
     * @var \Firebear\ImportExport\Model\Export\Product\Additional
     */
    protected $additional;

    /**
     * @var \Firebear\ImportExport\Model\Export\Customer\Additional
     */
    protected $additionalCust;

    /**
     * @var array
     */
    protected $customersArray = [
        'customer'
    ];

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $collectionFactory
     * @param \Firebear\ImportExport\Model\Export\Dependencies\Config $config
     * @param \Firebear\ImportExport\Model\Source\Factory $createFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $typeCollection
     * @param \Firebear\ImportExport\Model\Export\Product\Additional $additional
     * @param \Firebear\ImportExport\Model\Export\Customer\Additional $additionalCust
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $collectionFactory,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $config,
        \Firebear\ImportExport\Model\Source\Factory $createFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $typeCollection,
        \Firebear\ImportExport\Model\Export\Product\Additional $additional,
        \Firebear\ImportExport\Model\Export\Customer\Additional $additionalCust
    ) {
        parent::__construct($context, $jobFactory, $repository);
        $this->collection = $collectionFactory;
        $this->config = $config;
        $this->createFactory = $createFactory;
        $this->typeCollection = $typeCollection;
        $this->additional = $additional;
        $this->additionalCust = $additionalCust;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create($this->resultFactory::TYPE_JSON);
        $options = [];
        $list = [];
        $tables = $this->getFromTables();
        $extTypes = array_keys($tables);
        if ($this->getRequest()->isAjax()) {
            $entity = $this->getRequest()->getParam('entity');
            if ($entity) {
                if (!in_array($entity, $extTypes) || 'advanced_pricing' == $entity) {
                    $list = $this->getFromAttributes($entity);
                    if (in_array($entity, ['advanced_pricing', 'catalog_product'])) {
                        foreach ($this->uniqualFields() as $field) {
                            $list[] = $field;
                        }
                    }
                    if ('advanced_pricing' == $entity) {
                        $list = array_merge_recursive($list, $tables[$entity]);
                    }
                    if (in_array($entity, $this->customersArray)) {
                        foreach ($this->uniqualFieldsCust() as $field) {
                            $list[] = $field;
                        }
                    }
                    $options = $list;
                } else {
                    $options = $tables[$entity];
                }
            }
            return $resultJson->setData($options);
        }
    }

    /**
     * @param $type
     * @return array
     */
    protected function getFromAttributes($type)
    {
        $options = [];
        if ($type == 'advanced_pricing') {
            $type = 'catalog_product';
        }
        $types = $this->typeCollection->create()->addFieldToFilter('entity_type_code', $type);
        if ($types->getSize()) {
            $collection = $this->collection->create()->addFieldToFilter(
                'entity_type_id',
                $types->getFirstItem()->getId()
            );
            foreach ($collection as $item) {
                $options[] = [
                    'value' => $item->getAttributeCode(),
                    'label' => $item->getFrontendLabel() ? $item->getFrontendLabel() : $item->getAttributeCode()
                ];
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getFromTables()
    {
        $options = [];
        $data = $this->config->get();
        foreach ($data as $typeName => $type) {
            $typeModel = $type['model'];
            $model = $this->createFactory->create($typeModel);
            $options += $model->getFieldsForFilter();
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function uniqualFields()
    {
        return $this->additional->toOptionArray();
    }

    /**
     * @return array
     */
    protected function uniqualFieldsCust()
    {
        return $this->additionalCust->toOptionArray();
    }
}
