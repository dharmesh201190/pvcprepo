<?php
namespace LR\Matrixtable\Model\ResourceModel;

class Serviceupgrade extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
    
    protected function _construct()
    {        
        $this->_init('service_upgrade', "entity_id");
    }
}