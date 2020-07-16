<?php

namespace LR\Matrixtable\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddDescription implements ObserverInterface
{
    /**
     * Add Description field
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $form = $observer->getEvent()->getForm();
        $attributeObject = $observer->getEvent()->getAttribute();

        $fieldset = $form->getElement(
            'front_fieldset');

        $edit = $this->Data();

        $fieldset->addField(
            'description',
            'editor',
            [
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
                'value' => $attributeObject->getDescription(),
                'config' => $edit,
                'note' => __('It will visible in product page')
            ]
        );

        return $this;
    }

    public function Data()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $models = $objectManager->create('Magento\Cms\Model\Wysiwyg\Config');
        $wysiwygConfig = $models->getConfig();
        return $wysiwygConfig;
    }
}
