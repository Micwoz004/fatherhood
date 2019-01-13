<?php
/**
 * Created by PhpStorm.
 * User: Michał
 * Date: 02.12.2017
 * Time: 16:28
 */

namespace AppBundle\Service;

use League\Csv\ResultSet;


class CsvFileValidator
{
    const COLUMN_NAME_OF_PERMANENT_START = 'UID';
    const COLUMN_NAME_OF_PERMANENT_LIMIT = 'Uwagi';

    const MAIN_COLUMN_NAMES = array(
        array (
            'inFile' => 'LAB',
            'inDB' => 'lab'
        ),
        array (
            'inFile' => 'Nr sprawy',
            'inDB' => 'case_number'
        ),
        array (
            'inFile' => 'Rola w sprawie',
            'inDB' => 'case_role'
        ),
        array (
            'inFile' => 'Nr próbki',
            'inDB' => 'sample_number'
        ),
        array (
            'inFile' => 'UID',
            'inDB' => 'UID'
        )
    );

    private $csvFileHeadersRow = array();
    private $emptyRowCounter = 0;

    /**
     * @param array $record
     * @return bool
     */
    public function rowIsHeader(Array $record) : bool
    {
        foreach( self::MAIN_COLUMN_NAMES as $key => $header ) {
            return in_array($header['inFile'], $record);
        }

        return false;
    }

    /**
     * @param ResultSet $records
     * @return array
     */
    public function getFileHeaders(ResultSet $records) : array
    {
        foreach ($records->getRecords() as $hkey => $headerRow) {

            if ($hkey == 4) { break; }

            if($this->rowIsHeader($headerRow)) {

                $this->csvFileHeadersRow = $headerRow;

                return $headerRow;
            }
        }

        return array();
    }

    /**
     * @return array
     */
    public function prepareColumnSchemaData() : array
    {
        $i = 0;
        $columnSchemaInsert = array();

        foreach ( $this->csvFileHeadersRow as $columnName ) {
            if ($this->shouldStopCollectData($columnName)) {
                break;
            }

            if ($i >= 1) {
                $columnSchemaInsert['data_'.$i] = $columnName;
                $i++;
            }

            if ($this->shouldStartCollectData($columnName)) {
                $i++;
            }
        }

        return $columnSchemaInsert;
    }

    /**
     * @param $columnName
     * @return bool
     */
    private function shouldStartCollectData($columnName) : bool
    {
        return $columnName == CsvFileValidator::COLUMN_NAME_OF_PERMANENT_START;
    }

    /**
     * @param $columnName
     * @return bool
     */
    private function shouldStopCollectData($columnName) : bool
    {
        return $columnName == CsvFileValidator::COLUMN_NAME_OF_PERMANENT_LIMIT;
    }

    /**
     * @return array
     */
    public function prepareImportedDataColumns() : array
    {
        $preparedImportData = array();

        $namesOfAllelColumnsToFill = $this->prepareColumnSchemaData();

        foreach(self::MAIN_COLUMN_NAMES as $key => $columName) {
            $preparedImportData[] = $columName['inDB'];
        }

        foreach($namesOfAllelColumnsToFill as $schemaColumnName => $allelColumnName) {
            $preparedImportData[] = $schemaColumnName ;
        }

        $preparedImportData[] = 'comments';

        return $preparedImportData;
    }

    /**
     * @param $csvRow
     * @return bool
     */
    public function isRowEmpty($csvRow) : bool
    {
        $localRecordEmptyCounter = 0;

        foreach ($csvRow as $key => $value) {
            if ($localRecordEmptyCounter >= 3) {
                $this->emptyRowCounter++;
                return true;
            }

            if (empty($value)) {
                $localRecordEmptyCounter++;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isTooManyEmptyRows() : bool
    {
        return ( $this->emptyRowCounter >= 3 ? true : false );
    }

    /**
     * @param $csvRow
     * @return array
     */
    public function cleanDataWithUtf8($csvRow) : array
    {
        return array_map(function($n){
                    return iconv('windows-1252', 'UTF-8', str_replace(',', '.', trim($n, '`')));
                }, $csvRow);
    }
}