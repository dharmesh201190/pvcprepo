<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\ExportJobFactory;

abstract class Job extends Action
{
    const ADMIN_RESOURCE = 'Firebear_ImportExport::export_job';

    /**
     * @var ExportJobFactory
     */
    protected $exportJobFactory;

    /**
     * @var ExportJobRepositoryInterface
     */
    protected $exportRepository;

    /**
     * @param Context $context
     * @param ExportJobFactory $exportJobFactory
     * @param ExportJobRepositoryInterface $exportRepository
     */
    public function __construct(
        Context $context,
        ExportJobFactory $exportJobFactory,
        ExportJobRepositoryInterface $exportRepository
    ) {
        $this->exportJobFactory = $exportJobFactory;
        $this->exportRepository = $exportRepository;
        parent::__construct($context);
    }
}
