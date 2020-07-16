<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\CartPriceRule;

use Magento\Framework\Json\EncoderInterface;
use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\SalesRule\Model\Rule\Condition\Combine;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Found;
use Magento\SalesRule\Model\Rule\Condition\Product\Subselect;

class Condition
{

    protected $attributes;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Product
     */
    protected $product;

    protected $general;

    public $arrayAttr = [];

    /**
     * @var array
     */
    protected $list = [
        'combine' => [
            'type' => Combine::class,
            'attribute' => null,
            'operator' => null,
            'value' => '1',
            'is_value_processed' => null,
            'aggregator' => 'all',
            'conditions' => []
        ],
        'product' => [
            'type' => Found::class,
            'attribute' => null,
            'operator' => null,
            'value' => '1',
            'is_value_processed' => null,
            'aggregator' => 'all',
            'conditions' => []
        ],
        'subs' => [
            'type' => Subselect::class,
            'attribute' => null,
            'operator' => null,
            'value' => '1',
            'is_value_processed' => null,
            'aggregator' => 'all',
            'conditions' => []
        ]
    ];
    protected $emptyBlock = [
        "type" => "",
        "attribute" => "",
        "operator" => "",
        "value" => "",
        "is_value_processed" => false
    ];

    /**
     * @var array
     */
    protected $innerList = [
        'address' => Address::class,
        'product' => Product::class
    ];

    protected $jsonEncoder;

    /**
     * Condition constructor.
     * @param EncoderInterface $jsonEncoder
     * @param Address $address
     * @param Product $product
     */
    public function __construct(
        EncoderInterface $jsonEncoder,
        Address $address,
        Product $product
    ) {
        $this->jsonEncoder = $jsonEncoder;
        if ($this->attributes == null) {
            $this->attributes = $this->reverseArray($address->getAttributeOption());
            $this->attributes += $this->reverseArray($product->getAttributeOption());
        }

        $this->address = $address;
        $this->product = $product;
    }

    /**
     * @param $data
     * @return string
     */
    public function parseCondition($data)
    {
        $this->arrayAttr = [];
        $array = $this->list['combine'];
        $condition = [];
        foreach ($data as $key => $item) {
            $parentCondition = [];
            $inCondition = [];
            if ($key != 'main') {
                $parentCondition = $this->list[$key];
            }
            if ($key == 'subs') {
                $parentCondition = $this->correctData($parentCondition, $item);
            }
            foreach ($item['condition'] as $cond) {
                $scope = $this->emptyBlock;
                switch ($key) {
                    case "main":
                        $this->general = $this->address;
                        $scope['type'] = $this->innerList['address'];
                        break;
                    case "product":
                    case "subs":
                        $this->general = $this->product;
                        $scope['type'] = $this->innerList['product'];
                        break;
                }
                $inCondition[] = $this->correctData($scope, $cond);
            }
            if (empty($parentCondition)) {
                $condition[] = $inCondition;
            } else {
                $parentCondition['conditions'] = $inCondition;
                $condition[] = $parentCondition;
            }
        }
        $array['conditions'] = $condition;

        return $this->jsonEncoder->encode($array);
    }

    /**
     * @param $data
     * @return string
     */
    public function parseActionCondition($data)
    {
        $this->arrayAttr = [];
        $array = $this->list['combine'];
        $condition = [];
        foreach ($data as $key => $item) {
            $inCondition = [];
            foreach ($item['condition'] as $cond) {
                $scope = $this->emptyBlock;
                $scope['type'] = $this->innerList['product'];
                $this->general = $this->product;
                $inCondition[] = $this->correctData($scope, $cond);
            }
            $condition = $inCondition;
        }

        $array['conditions'] = $condition;

        return $this->jsonEncoder->encode($array);
    }

    /**
     * @param $data
     * @param $condition
     * @return mixed
     */
    protected function correctData($data, $condition)
    {

        if (isset($condition['attribute'])) {
            $data['attribute'] = $condition['attribute'];
            if (isset($this->attributes[$condition['attribute']])) {
                $data['attribute'] = $this->attributes[$condition['attribute']];
            } else {
                $this->arrayAttr[] = $data['attribute'];
                $data = [];
            }
        }
        if (count($data) > 0) {
            if (isset($condition['operator'])) {
                $data['operator'] = $condition['operator'];
            }

            if (isset($condition['value'])) {
                $data['value'] = $condition['value'];
            }
            if ($this->general != null) {
                $value = null;
                $this->general->setAttribute($data['attribute']);
                if (in_array($this->general->getInputType(), ['select', 'multiselect'])) {
                    $this->general->unsetData('value_select_options');
                    $options = $this->general->getValueSelectOptions();
                    if (is_array($data['value'])) {
                        foreach ($data['value'] as $itemValue) {
                            $value[] = $this->searchOption($itemValue, $options);
                        }
                    } else {
                        $value = $this->searchOption($data['value'], $options);
                    }
                    $data['value'] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * @param $array
     * @return array
     */
    public function reverseArray($array)
    {

        $newArray = [];
        foreach ($array as $key => $item) {
            if ($item instanceof \Magento\Framework\Phrase) {
                $item = $item->__toString();
            };
            $newArray[$item] = $key;
        }

        return $newArray;
    }

    public function searchOption($value, $options)
    {
        $fValue = "";
        foreach ($options as $item) {
            if (is_array($item['value'])) {
                $fValue = $this->searchOption($value, $item['value']);
                if (!empty($fValue)) {
                    break;
                }
            } else {
                if ($item['label'] == $value) {
                    $fValue = $item['value'];
                }
            }
        }

        return $fValue;
    }
}
