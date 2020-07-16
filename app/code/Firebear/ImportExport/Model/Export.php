<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Model\Source\Type\File\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Export\Adapter\AbstractAdapter;
use Magento\Store\Model\ScopeInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class Export extends \Magento\ImportExport\Model\Export
{
    use \Firebear\ImportExport\Traits\General;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $configExDi;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var Config
     */
    protected $fireExportConfig;
    /** @var bool */
    protected $_debugMode;
    /** @var ExportJobRepositoryInterface */
    protected $exportJobRepository;
    /** @var \Magento\Framework\Json\EncoderInterface  */
    protected $encoder;
    /** @var \Magento\Framework\Json\DecoderInterface  */
    protected $decoder;

    /**
     * Export constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\ImportExport\Model\Export\Entity\Factory $entityFactory
     * @param Export\Adapter\Factory $exportAdapterFac
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param ConsoleOutput $output
     * @param ScopeConfigInterface $scopeConfig
     * @param Export\Dependencies\Config $configExDi
     * @param Config $fireExportConfig
     * @param \Magento\Framework\Json\DecoderInterface $decoder
     * @param \Magento\Framework\Json\EncoderInterface $encoder
     * @param ExportJobRepositoryInterface $exportJobRepository
     * @param array $data
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Magento\ImportExport\Model\Export\Entity\Factory $entityFactory,
        \Firebear\ImportExport\Model\Export\Adapter\Factory $exportAdapterFac,
        \Firebear\ImportExport\Helper\Data $helper,
        ConsoleOutput $output,
        ScopeConfigInterface $scopeConfig,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi,
        \Firebear\ImportExport\Model\Source\Type\File\Config $fireExportConfig,
        \Magento\Framework\Json\DecoderInterface $decoder,
        \Magento\Framework\Json\EncoderInterface $encoder,
        ExportJobRepositoryInterface $exportJobRepository,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configExDi = $configExDi;
        $this->output = $output;
        $this->fireExportConfig = $fireExportConfig;
        $this->_exportConfig = $exportConfig;
        $this->_entityFactory = $entityFactory;
        $this->_exportAdapterFac = $exportAdapterFac;
        $this->_logger = $logger;
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_data = $data;
        $this->_debugMode = $helper->getDebugMode();
        $this->exportJobRepository = $exportJobRepository;
        $this->decoder = $decoder;
        $this->encoder = $encoder;
    }

    /**
     * @param mixed $debugData
     * @return $this
     */
    public function addLogComment($debugData)
    {

        if (is_array($debugData)) {
            $this->_logTrace = array_merge($this->_logTrace, $debugData);
        } else {
            $this->_logTrace[] = $debugData;
        }

        if (is_scalar($debugData)) {
            $this->addLogWriteln($debugData, null, 'debug');
        } else {
            foreach ($debugData as $message) {
                if ($message instanceof \Magento\Framework\Phrase) {
                    $this->addLogWriteln($message->__toString(), null, 'debug');
                } else {
                    $this->addLogWriteln($message, null, 'debug');
                }
            }
        }

        return $this;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getEntityAdapter()
    {
        $types = $this->configExDi->get();
        foreach ($types as $typeName => $type) {
            if ($typeName == $this->getEntity()) {
                $this->setModel($type['model']);

                return $this->_entityAdapter;
            }
        }

        parent::_getEntityAdapter();

        if ($entity = $this->scopeConfig->getValue(
            'firebear_importexport/entities/' . $this->getEntity(),
            ScopeInterface::SCOPE_STORE
        )
        ) {
            $this->setModel($entity);
        }

        return $this->_entityAdapter;
    }

    /**
     * Export data.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function export()
    {
        if (isset($this->_data[self::FILTER_ELEMENT_GROUP])) {
            $this->addLogComment(__('Begin export of %1', $this->getEntity()));

            $countRows = 0;
            $lastEntityId = 0;
            $exportData = $this->_getEntityAdapter()
                ->setLogger($this->_logger)
                ->setWriter($this->_getWriter())
                ->export();
            $result = $exportData[0];
            if (isset($exportData[1])) {
                $countRows = (int)$exportData[1];
            }
            if (isset($exportData[2])) {
                $lastEntityId = (int)$exportData[2];
            }
            $exportJob = $this->exportJobRepository
                ->getById($this->getData('job_id'));
            $sourceData = $this->decoder->decode($exportJob->getExportSource());
            if ($lastEntityId > 0) {
                $sourceData = array_merge(
                    $sourceData,
                    [
                        'last_entity_id' => $lastEntityId
                    ]
                );
                $sourceData = $this->encoder->encode($sourceData);
                $exportJob->setExportSource($sourceData);
                $this->exportJobRepository->save($exportJob);
            }
            if (!$countRows) {
                $this->addLogComment([__('There is no data for the export.')]);

                return false;
            }
            if ($result) {
                $this->addLogComment([__('Exported %1 items.', $countRows), __('The export is finished.')]);
            }
            return $result;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please provide filter data.'));
        }
    }

    /**
     * @param $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function setModel($entity)
    {
        try {
            $this->_entityAdapter = $this->_entityFactory->create($entity);
            $this->_entityAdapter->setParameters($this->getData());
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please enter a correct entity model.')
            );
        }
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getWriter()
    {
        if (!$this->_writer) {
            $data = $this->fireExportConfig->get();
            $fileFormats = $data['export'];
            if (isset($fileFormats[$this->getFileFormat()])) {
                try {
                    $this->_writer = $this->_exportAdapterFac->create(
                        $fileFormats[$this->getFileFormat()]['model'],
                        ['data' => $this->_data]
                    );
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a correct entity model.')
                    );
                }
                if (!$this->_writer instanceof AbstractAdapter) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            'The adapter object must be an instance of %1.',
                            AbstractAdapter::class
                        )
                    );
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please correct the file format.'));
            }
        }
        return $this->_writer;
    }

    /**
     * Retrieve entity field for export
     *
     * @return array
     */
    public function getFields()
    {
        $adapter = $this->_getEntityAdapter();
        // @todo replace for interface check Firebear\ImportExport\Model\Export\EntityInterface
        return method_exists($adapter, 'getFieldsForExport')
            ? $adapter->getFieldsForExport()
            : []; // [] from custom adapters
    }

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }
}
