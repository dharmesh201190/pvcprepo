<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Grid\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class BlockActions
 */
class ExportJobActions extends JobActions
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'import/export_job/edit';

    const URL_PATH_DELETE = 'import/export_job/delete';

    const URL_PATH_ENABLE = 'import/export_job/enable';

    const URL_PATH_DISABLE = 'import/export_job/disable';
}
