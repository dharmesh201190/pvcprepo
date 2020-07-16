<?php
namespace LR\Serviceupgrade\Block\Adminhtml\Holidays;

/**
 * CMS block edit form container
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected function _construct()
    {
		$this->_objectId = 'id';
        $this->_blockGroup = 'LR_Serviceupgrade';
        $this->_controller = 'adminhtml_holidays';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->update('delete', 'label', __('Delete'));

        $this->buttonList->add(
            'saveandcontinue',
            array(
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => array(
                    'mage-init' => array('button' => array('event' => 'saveAndContinueEdit', 'target' => '#edit_form'))
                )
            ),
            -100
        );

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('block_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'hello_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'hello_content');
                }
            }
        ";
    }

    /**
     * Get edit form container header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('holiday')->getId()) {
            return __("Edit Item '%1'", $this->escapeHtml($this->_coreRegistry->registry('holiday')->getHolidayTitle()));
        } else {
            return __('New Item');
        }
    }
}
