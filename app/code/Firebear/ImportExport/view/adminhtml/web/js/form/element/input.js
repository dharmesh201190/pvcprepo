/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'underscore',
        'Magento_Ui/js/form/element/abstract',
        'Firebear_ImportExport/js/form/element/general'
    ],
    function (_, Acstract, general) {
        'use strict';

        return Acstract.extend(general).extend(
            {
                defaults: {
                    base: false
                },
                changeText: function (value) {
                    if (this.base && !this.value()) {
                        this.value(value);
                    }
                    if (!this.base) {
                        this.base = true;
                    }
                }
            }
        );
    }
);
