<?php
namespace LR\PriceCalculator\Block\Adminhtml\Items\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{
    protected $_wysiwygConfig;
 
    public function __construct(
        \Magento\Backend\Block\Template\Context $context, 
        \Magento\Framework\Registry $registry, 
        \Magento\Framework\Data\FormFactory $formFactory,  
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig, 
        array $data = []
    ) 
    {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Material Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Material Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_lr_pricecalculator_items');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Material Information')]);
        if ($model->getId()) {
            $fieldset->addField('pricecalculator_id', 'hidden', ['name' => 'pricecalculator_id']);
        }
        $fieldset->addField(
            'material',
            'text',
            ['name' => 'material', 'label' => __('Material'), 'title' => __('Material'), 'required' => true]
        );
        $fieldset->addField(
            'finish',
            'text',
            ['name' => 'finish', 'label' => __('Finish'), 'title' => __('Finish'), 'required' => true]
        );
        $fieldset->addField(
            'material_group',
            'select',
            ['name' => 'material_group', 'label' => __('Material Group'), 'title' => __('Material Group'),  'options'   => [0 => 'Banner-Grade', 1 => 'Point-of-Sale', 2 => 'Rigid', 3 => 'Fabric'], 'required' => true]
        );
        $fieldset->addField(
            'price',
            'text',
            ['name' => 'price', 'label' => __('Price'), 'title' => __('Price'), 'required' => true, 'note' => __('you have to add price for 1 Meter only')]
        );
        $fieldset->addField(
            'status',
            'select',
            ['name' => 'status', 'label' => __('Status'), 'title' => __('Status'),  'options'   => [0 => 'Disable', 1 => 'Enable'], 'required' => true]
        );
        
        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
