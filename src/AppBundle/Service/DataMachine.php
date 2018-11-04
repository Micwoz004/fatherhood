<?php
/**
 * Created by PhpStorm.
 * User: Michał
 * Date: 17.03.2018
 * Time: 20:33
 */

namespace AppBundle\Service;

class DataMachine
{
    const PRIMARY_KEY_COLUMN_NAME = 'id';
    const COMMENTS_COLUMN_NAME = 'comments';

    private $dbColumnSchema;

    public function __construct(Array $dbColumnSchema)
    {
        $this->dbColumnSchema = $dbColumnSchema;
    }

    public function createAllelValueArray(Array $databaseRow) : array
    {
        $createdArray = array();

        foreach($this->dbColumnSchema as $rowKey => $allelName)
        {

            if ($rowKey == self::PRIMARY_KEY_COLUMN_NAME or empty($allelName))
            {
                continue;
            }
            if (empty($createdArray[$allelName]))
            {
                $createdArray[$allelName] = array();
            }
            array_push($createdArray[$allelName], $databaseRow[$rowKey]);
        }

        return $createdArray;
    }

    public function createCaseNumberRoleArray(Array $databaseRecords) : array
    {
        $CaseNumberRole = array();

        foreach($databaseRecords as $dbKey => $dbRow)
        {
            $CaseNumberRole[$dbRow['case_number']][$dbRow['case_role']] = $this->createAllelValueArray($dbRow);
        }

        return $CaseNumberRole;
    }

    public function unsetEmptyColumns(Array $databaseRow) : array
    {
        if ($this->isTwoDimensionalArray($databaseRow))
        {
            return $this->iterateTwoDimensionalArrayAndUnset($databaseRow);
        }
        else
        {
            return $this->iterateOneDimensionalArrayAndUnset($databaseRow);
        }
    }

    private function isTwoDimensionalArray(Array $databaseRow) : bool
    {
        if (!empty($databaseRow[0]) and is_array($databaseRow[0]))
        {
            return true;
        }

        return false;
    }

    private function iterateTwoDimensionalArrayAndUnset(Array $databaseRow) : array
    {
        foreach($databaseRow as $fKey => $firstDimensionRow)
        {
            foreach($firstDimensionRow as $sKey => $secondDimensionRow)
            {
                if ((empty($secondDimensionRow) and $sKey != self::COMMENTS_COLUMN_NAME ) or $sKey == self::PRIMARY_KEY_COLUMN_NAME)
                {
                    unset($databaseRow[$fKey][$sKey]);
                }
            }
        }

        return $databaseRow;
    }

    private function iterateOneDimensionalArrayAndUnset(Array $databaseRow) : array
    {
        foreach ($databaseRow as $key => $record)
        {
            if ((empty($record) and $key != self::COMMENTS_COLUMN_NAME ) or $key == self::PRIMARY_KEY_COLUMN_NAME)
            {
                unset($databaseRow[$key]);
            }
        }

        return $databaseRow;
    }

}