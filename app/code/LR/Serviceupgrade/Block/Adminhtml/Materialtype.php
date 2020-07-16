<?php
namespace LR\Serviceupgrade\Block\Adminhtml;
class Materialtype extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_materialtype';/*block grid.php directory*/
        $this->_blockGroup = 'LR_Serviceupgrade';
        $this->_headerText = __('Material Type');
        $this->_addButtonLabel = __('Add New Entry'); 
        parent::_construct();
		
    }
}
