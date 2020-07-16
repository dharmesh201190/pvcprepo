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

class Downfiltres extends JobController
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
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
     * @var \Firebear\ImportExport\Model\Export\Product\Additional
     */
    protected $additional;

    /**
     * @var \Firebear\ImportExport\Model\Export\Customer\Additional
     */
    protected $additionalCust;

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collection
     * @param \Firebear\ImportExport\Model\Export\Dependencies\Config $config
     * @param \Firebear\ImportExport\Model\Source\Factory $createFactory
     * @param \Firebear\ImportExport\Model\Export\Product\Additional $additional
     * @param \Firebear\ImportExport\Model\Export\Customer\Additional $additionalCust
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collection,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $config,
        \Firebear\ImportExport\Model\Source\Factory $createFactory,
        \Firebear\ImportExport\Model\Export\Product\Additional $additional,
        \Firebear\ImportExport\Model\Export\Customer\Additional $additionalCust
    ) {
        parent::__construct($context, $jobFactory, $repository);
        $this->collection = $collection;
        $this->config = $config;
        $this->createFactory = $createFactory;
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
        $result = [];
        if ($this->getRequest()->isAjax()) {
            $entity = $this->getRequest()->getParam('entity');
            $type = $this->getRequest()->getParam('type');
            if ($entity && $type) {
                $options = array_merge_recursive(
                    $this->getFromAttributes(),
                    $this->getFromTables()
                );
            }
            if (!empty($options)) {
                foreach ($options[$type] as $field) {
                    if ($entity == $field['field']) {
                        $result = $field;
                    }
                }
            }
            return $resultJson->setData($result);
        }
    }

    /**
     * @return array
     */
    protected function getFromAttributes()
    {
        $options = [];
        $options['attr'] = [];
        $collection = $this->collection->addFieldToFilter('attribute_code', $this->getRequest()->getParam('entity'));
        foreach ($collection as $item) {
            $select = [];
            $type = $item->getFrontendInput();
            if (in_array($type, [\Magento\ImportExport\Model\Export::FILTER_TYPE_SELECT, 'multiselect'])) {
                if ($optionsAttr = $item->getSource()->getAllOptions()) {
                    foreach ($optionsAttr as $option) {
                        if (isset($option['value'])) {
                            $select[] = ['label' => $option['label'], 'value' => $option['value']];
                        }
                    }
                }
            }

            if ($item->getFrontendInput() != 'select'
                && in_array($item->getBackendType(), ['int', 'decimal'])) {
                $type = 'int';
            }
            if (in_array($item->getFrontendInput(), ['textarea', 'media_image', 'image', 'multiline', 'gallery'])) {
                $type = 'text';
            }
            if (in_array($item->getFrontendInput(), ['hidden'])) {
                $type = 'not';
            }
            if (in_array($item->getFrontendInput(), ['multiselect'])) {
                $type = 'select';
            }
            if ($item->getFrontendInput() == 'boolean') {
                $type = 'select';
                $select[] = ['label' => __('Yes'), 'value' => 1];
                $select[] = ['label' => __('No'), 'value' => 0];
            }
            if ($item->getAttributeCode() == 'category_ids') {
                $type = 'int';
            }

            $options['attr'][] =
                [
                    'field' => $item->getAttributeCode(),
                    'type' => $type,
                    'select' => $select
                ];
        }
        foreach ($this->additional->getAdditionalFields() as $field) {
            $options['attr'][] = $field;
        }
        foreach ($this->additionalCust->getAdditionalFields() as $field) {
            $options['attr'][] = $field;
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
            $model = $this->createFactory->create($type['model']);
            $columns = $model->getFieldColumns();
            if ('advanced_pricing' == $typeName) {
                if (empty($options['attr'])) {
                    $options['attr'] = [];
                }
                $options['attr'] += $columns['advanced_pricing'];
            } else {
                $options += $columns;
            }
        }
        return $options;
    }
}
