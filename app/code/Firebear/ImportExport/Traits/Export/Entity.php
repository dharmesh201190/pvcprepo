<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Export;

use Firebear\ImportExport\Traits\General as GeneralTrait;

trait Entity
{
    use GeneralTrait;

    /**
     * @var int
     */
    protected $lastEntityId;

    /**
     * @param $data
     *
     * @return array
     */
    public function changeData($data, $entityFieldID = null)
    {
        $listCodes = $this->_parameters['list'];
        if ($entityFieldID) {
            $listCodes[] = $entityFieldID;
        }
        $replaces = $this->_parameters['replace_code'];
        $replacesValues = $this->_parameters['replace_value'];
        $newData = [];
        $allFields = $this->_parameters['all_fields'];
        foreach ($data as $key => $record) {
            $newRecord = [];
            foreach ($record as $code => $value) {
                if (in_array($code, $listCodes)) {
                    $keyCode = $this->getKeyFromList($listCodes, $code);
                    $newCode = $code;
                    if (isset($replaces[$keyCode])) {
                        $newCode = $replaces[$keyCode];
                    }
                    $newRecord[$newCode] = $value;
                    if (isset($replacesValues[$keyCode]) && !empty($replacesValues[$keyCode])) {
                        $newRecord[$newCode] = $replacesValues[$keyCode];
                    }
                } else {
                    if (!$allFields) {
                        $newRecord[$code] = $value;
                    }
                }
            }

            $noFullList = array_diff($listCodes, array_keys($newRecord));
            if (!empty($noFullList)) {
                foreach ($noFullList as $code => $value) {
                    $newRecord[$code] = $value;
                }
            }
            if (!empty($newRecord)) {
                $newData[] = $newRecord;
            }
        }

        return $newData ? $newData : $data;
    }

    /**
     * @param $row
     *
     * @return array
     */
    public function changeRow($row)
    {
        $listCodes = $this->_parameters['list'];
        $replaces = $this->_parameters['replace_code'];
        $allFields = $this->_parameters['all_fields'];
        $replacesValues = $this->_parameters['replace_value'];
        $newRecord = [];
        foreach ($row as $code => $value) {
            if (in_array($code, $listCodes)) {
                $keyCode = $this->getKeyFromList($listCodes, $code);
                $newCode = $code;
                if (isset($replaces[$keyCode])) {
                    $newCode = $replaces[$keyCode];
                }
                $newRecord[$newCode] = $value;
                if (isset($replacesValues[$keyCode]) && !empty($replacesValues[$keyCode])) {
                    $newRecord[$newCode] = $replacesValues[$keyCode];
                }
            } else {
                if (!$allFields) {
                    $newRecord[$code] = $value;
                }
            }
        }

        $noFullList = array_diff($listCodes, array_keys($newRecord));
        if (!empty($noFullList)) {
            foreach ($noFullList as $code => $value) {
                $newRecord[$code] = $value;
            }
        }

        return $newRecord;
    }

    /**
     * @param $headers
     *
     * @return array
     */
    public function changeHeaders($headers)
    {
        $allFields = $this->_parameters['all_fields'];
        $listCodes = $this->_parameters['list'];
        $countCodes = count($listCodes);
        $replaces = $this->_parameters['replace_code'];
        $newHeaders = [];
        foreach ($headers as $code) {
            if (in_array($code, $listCodes)) {
                $newCode = $code;
                $keyCode = $this->getKeyFromList($listCodes, $code);
                if (isset($replaces[$keyCode])) {
                    $newCode = $replaces[$keyCode];
                    $newHeaders[array_search($code, $listCodes)] = $newCode;
                } else {
                    $newHeaders[$countCodes++] = $newCode;
                }
            } else {
                if (!$allFields) {
                    $newHeaders[$countCodes++] = $code;
                }
            }
        }
        ksort($newHeaders);

        return $newHeaders ? $newHeaders : $headers;
    }

    /**
     * @param $list
     * @param $search
     * @return false|int|string
     */
    protected function getKeyFromList($list, $search)
    {
        return array_search($search, $list);
    }

    public function getCount()
    {
        return $this->_getEntityCollection()->getSize();
    }
}
