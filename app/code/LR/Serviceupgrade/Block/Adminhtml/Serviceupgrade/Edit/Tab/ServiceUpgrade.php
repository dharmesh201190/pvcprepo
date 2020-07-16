<?php
namespace LR\Serviceupgrade\Block\Adminhtml\Serviceupgrade\Edit\Tab;
class ServiceUpgrade extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = array()
    ) {
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
		/* @var $model \Magento\Cms\Model\Page */
        $model = $this->_coreRegistry->registry('serviceupgrade_serviceupgrade');
		$isElementDisabled = false;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => __('Production Turnaround')));

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', array('name' => 'id'));
        }

		$fieldset->addField(
            'range_min_price',
            'text',
            ['name' => 'range_min_price', 'label' => __('Min Price'), 'title' => __('Min Price'), 'required' => false]
        );
        $fieldset->addField(
            'range_max_price',
            'text',
            ['name' => 'range_max_price', 'label' => __('Max Price'), 'title' => __('Max Price'), 'required' => false]
        );
        $fieldset->addField(
            'sku',
            'text',
            ['name' => 'sku', 'label' => __('SKU'), 'title' => __('SKU'), 'required' => false]
        );
        $fieldset->addField(
            'shipping_days',
            'text',
            ['name' => 'shipping_days', 'label' => __('Shipping Days'), 'title' => __('Shipping Days'), 'required' => true]
        );
        $fieldset->addField(
            'shipping_lable',
            'text',
            ['name' => 'shipping_lable', 'label' => __('Shipping Lable'), 'title' => __('Shipping Lable'), 'required' => true]
        );
        $fieldset->addField(
            'shipping_price_percent',
            'text',
            ['name' => 'shipping_price_percent', 'label' => __('Shipping Price Percent'), 'title' => __('Shipping Price Percent'), 'required' => true]
        );
        $fieldset->addField(
            'recommended',
            'select',
            [
                'label' => __('Recommended'),
                'title' => __('Recommended'),                
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );		
        
        if (!$model->getId()) {
            $model->setData('status', $isElementDisabled ? '2' : '1');
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();   
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Production Turnaround');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Production Turnaround');
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
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
