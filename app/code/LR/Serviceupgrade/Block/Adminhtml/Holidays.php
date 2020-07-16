<?php
namespace LR\Serviceupgrade\Block\Adminhtml;
class Holidays extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
		
        $this->_controller = 'adminhtml_holidays';/*block grid.php directory*/
        $this->_blockGroup = 'LR_Serviceupgrade';
        $this->_headerText = __('Production Holidays');
        $this->_headerText = "Production Holidays";
        $this->_addButtonLabel = __('Add New Holiday'); 
        parent::_construct();
		
    }

    public function getHeaderText()
    {
        return __("Production Holidays");
    }
}
