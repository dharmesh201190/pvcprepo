<?php
namespace LR\Serviceupgrade\Block\Adminhtml;
class Serviceupgrade extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
		
        $this->_controller = 'adminhtml_serviceupgrade';/*block grid.php directory*/
        $this->_blockGroup = 'LR_Serviceupgrade';
        $this->_headerText = __('Production Turnaround');
        $this->_addButtonLabel = __('Add New Entry'); 
        parent::_construct();
		
    }
}
