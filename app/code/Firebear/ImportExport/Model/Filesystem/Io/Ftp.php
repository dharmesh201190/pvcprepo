<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Filesystem\Io;

/**
 * Extended FTP client
 */
class Ftp extends \Magento\Framework\Filesystem\Io\Ftp
{
    /**
     * Returns the last modified time of the given file
     * Note: Not all servers support this feature! Does not work with directories.
     *
     * @param string $filename
     *
     * @see http://php.net/manual/en/function.ftp-mdtm.php
     *
     * @return int
     */
    public function mdtm($filename)
    {
        return @ftp_mdtm($this->_conn, $filename);
    }

    public function checkIsPath($filename, $dest)
    {
        try {
            $result = ftp_get($this->_conn, $dest, $filename, $this->_config['file_mode']);
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }
}
