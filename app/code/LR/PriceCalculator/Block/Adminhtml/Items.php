<?php
namespace LR\PriceCalculator\Block\Adminhtml;

class Items extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'items';
        $this->_headerText = __('Materials');
        $this->_addButtonLabel = __('Add New Material');
        parent::_construct();
    }
}
