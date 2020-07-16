<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;

abstract class Job extends Action
{
    const ADMIN_RESOURCE = 'Firebear_ImportExport::job';

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var JobRepositoryInterface
     */
    protected $repository;

    /**
     * @param Context $context
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     */
    public function __construct(
        Context $context,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository
    ) {
        $this->jobFactory = $jobFactory;
        $this->repository = $repository;
        parent::__construct($context);
    }
}
