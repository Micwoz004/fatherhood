<?php
/**
 * Created by PhpStorm.
 * User: Michał
 * Date: 02.12.2017
 * Time: 16:28
 */

namespace AppBundle\Service;

use League\Csv\ResultSet;
use League\Csv\Statement;

class CsvFileValidator
{

    public function rowIsHeader(Array $record) : bool
    {
        $headers = array( 'LAB', 'Nr sprawy', 'Rola w sprawie', 'Nr próbki', 'UID' );

        foreach($headers as $key => $header) {
            if (in_array($header, $record)) {
                return true;
            }
        }

        return false;
    }

    public function getFileHeaders(ResultSet $records)
    {
        foreach ($records->getRecords() as $hkey => $headerRow) {

            if ($hkey == 4) { break; }

            if($this->rowIsHeader($headerRow)) {
                return $headerRow;
            }
        }

        return null;
    }

    public function getQueryColumnList($alldata) : array
    {
        $arr = array(
            'cnames' => '',
            'values' => ''
        );

        for ($i = 1; $i < $alldata; $i++) {
            $arr['cnames'] .= ', data_'.$i;
            $arr['values'] .= ', ?';
        }

        return $arr;
    }
}