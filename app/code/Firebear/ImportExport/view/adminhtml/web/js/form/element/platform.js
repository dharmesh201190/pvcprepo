/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
define(
    [
        'jquery',
        'underscore',
        'Firebear_ImportExport/js/form/element/additional-select',
        'uiRegistry',
        'mage/translate'
    ],
    function ($, _, Abstract, reg, $t) {
        'use strict';
        return Abstract.extend(
            {
                default: {
                    listens : {
                        'value': 'onChangeValue'
                    }
                },

                onUpdate: function () {
                    this._super();
                    var entity = reg.get(this.ns + '.' + this.ns + '.settings.entity');
                    var source = reg.get(this.ns + '.' + this.ns + '.source');
                    if (entity !== undefined && source !== undefined) {
                        /*var prefix = entity.value() + '_' + this.value();
                        _.each(source.elems(), function (elem) {
                            console.log(elem);
                            var inputName = elem.inputName,
                                index = elem.index;
                                //console.log(prefix);
                                console.log(index);
                                //console.log(inputName);
                                console.log(prefix + '_' + inputName);
                        });
                        console.log(source.elems());*/
                    }
                }
            }
        )
    }
);
