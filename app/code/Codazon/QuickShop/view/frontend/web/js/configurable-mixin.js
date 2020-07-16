/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/utils/wrapper',
], function ($, wrapper) {
    'use strict';
    return function (configurable) {
        return wrapper.wrap(configurable, function (originalAction, options) {
            if($(".quickshop-wrapper .super-attribute-select").length){
                options.superSelector = '.super-attribute-select, .quickshop-wrapper .super-attribute-select';
            }
            return originalAction();
        });
    };
});
