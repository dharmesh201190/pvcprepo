<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait General
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @param $logger
     *
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * @param $errorAggregator
     *
     * @return mixed
     */
    public function setErrorAggregator($errorAggregator)
    {
        return $this->errorAggregator = $errorAggregator;
    }

    /**
     * @param $debugData
     * @param OutputInterface|null $output
     * @param null $type
     *
     * @return $this
     */
    public function addLogWriteln($debugData, OutputInterface $output = null, $type = null)
    {
        $text = $debugData;
        if ($debugData instanceof \Magento\Framework\Phrase) {
            $text = $debugData->__toString();
        }

        switch ($type) {
            case 'error':
                $this->_logger->error($text);
                break;
            case 'warning':
                $this->_logger->warning($text);
                break;
            case 'debug':
                $this->_logger->debug($text);
                break;
            default:
                $this->_logger->info($text);
        }

        if ($output) {
            switch ($type) {
                case 'error':
                    $text = '<error>' . $text . '</error>';
                    break;
                case 'info':
                    $text = '<info>' . $text . '</info>';
                    break;
                default:
                    $text = '<comment>' . $text . '</comment>';
                    break;
            }
            $output->writeln($text, $output->getVerbosity());
        }

        return $this;
    }

    public function setErrorMessages()
    {
        return true;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return ConsoleOutput
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function joinIdenticalyData($data)
    {
        $reverts = [];
        foreach ($this->_parameters['map'] as $item) {
            if ($item['import']) {
                $reverts[$item['import']][] = $item['system'];
            }
        }
        if (!empty($this->_parameters['identicaly'])) {
            foreach ($this->_parameters['identicaly'] as $elem) {
                if (!empty($data[$reverts[$elem['import']][0]])) {
                    $data[$elem['system']] = $data[$reverts[$elem['import']][0]];
                }
            }
        }

        return $data;
    }

    public function customChangeData($data)
    {
        return $data;
    }

    public function customBunchesData($data)
    {
        return $data;
    }

    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Apply filter to collection and add not skipped attributes to select.
     *
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     *
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareEntityCollection(\Magento\Eav\Model\Entity\Collection\AbstractCollection $collection)
    {
        if (!isset(
            $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP]
        ) || !is_array(
            $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP]
        )
        ) {
            $filter = [];
        } else {
            $filter = $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP];
        }

        $exportCodes = $this->_getExportAttrCodes();

        foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
            $attrCode = $attribute->getAttributeCode();

            // filter applying
            if (isset($filter[$attrCode])) {
                $attrFilterType = \Magento\ImportExport\Model\Export::getAttributeFilterType($attribute);

                if (\Magento\ImportExport\Model\Export::FILTER_TYPE_SELECT == $attrFilterType) {
                    if (is_scalar($filter[$attrCode])) {
                        if ($filter[$attrCode] == 0) {
                            $collection->addAttributeToFilter([
                                ['attribute' => $attrCode, 'eq' => $filter[$attrCode]],
                                ['attribute' => $attrCode, 'null' => 1],
                            ]);
                        } else {
                            $collection->addAttributeToFilter($attrCode, ['eq' => $filter[$attrCode]]);
                        }
                    }
                } elseif (\Magento\ImportExport\Model\Export::FILTER_TYPE_INPUT == $attrFilterType) {
                    if (is_scalar($filter[$attrCode]) && trim($filter[$attrCode])) {
                        $collection->addAttributeToFilter($attrCode, ['like' => "%{$filter[$attrCode]}%"]);
                    }
                } elseif (\Magento\ImportExport\Model\Export::FILTER_TYPE_DATE == $attrFilterType) {
                    if (is_array($filter[$attrCode]) && count($filter[$attrCode]) == 2) {
                        $from = array_shift($filter[$attrCode]);
                        $to = array_shift($filter[$attrCode]);

                        if (is_scalar($from) && !empty($from)) {
                            $date = (new \DateTime($from))->format('m/d/Y');
                            $collection->addAttributeToFilter($attrCode, ['from' => $date, 'date' => true]);
                        }
                        if (is_scalar($to) && !empty($to)) {
                            $date = (new \DateTime($to))->format('m/d/Y');
                            $collection->addAttributeToFilter($attrCode, ['to' => $date, 'date' => true]);
                        }
                    }
                } elseif (\Magento\ImportExport\Model\Export::FILTER_TYPE_NUMBER == $attrFilterType) {
                    if (is_array($filter[$attrCode]) && count($filter[$attrCode]) == 2) {
                        $from = array_shift($filter[$attrCode]);
                        $to = array_shift($filter[$attrCode]);

                        if (is_numeric($from)) {
                            $collection->addAttributeToFilter(
                                $attrCode,
                                ['from' => $from]
                            );
                        }
                        if (is_numeric($to)) {
                            $collection->addAttributeToFilter(
                                $attrCode,
                                ['to' => $to]
                            );
                        }
                    }
                }
            }
            if (in_array($attrCode, $exportCodes)) {
                $collection->addAttributeToSelect($attrCode);
            }
        }
        return $collection;
    }
}
