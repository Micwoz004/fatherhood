<?php
/**
 * Created by PhpStorm.
 * User: Michał
 * Date: 17.03.2018
 * Time: 20:33
 */

namespace AppBundle\Service;

use Doctrine\DBAL\Connection;

class Comparison
{
    private $maxDifferenceCounter;

    private $dbColumnSchema;
    private $dataMachine;
    private $dbConnection;

    /**
     * Comparison constructor.
     * @param $dbColumnSchema
     * @param Connection $dbConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct($dbColumnSchema, Connection $dbConnection)
    {
        $this->dbColumnSchema = $dbColumnSchema;
        $this->dataMachine = new DataMachine($dbColumnSchema);
        $this->dbConnection = $dbConnection;
        $this->maxDifferenceCounter = $dbConnection->fetchColumn('SELECT value FROM settings WHERE name = ?', array('discard_after'), 0);
    }

    /**
     * @param $firstRecord
     * @param $secondRecord
     * @return int
     */
    public function compareTwoRawDbRecords($firstRecord, $secondRecord) : int
    {
        $iteration = 1;
        $differenceCounter = 0;

        while(!empty($firstRecord[ 'data_' . $iteration ])) {

            $firstAllelPair = array($firstRecord['data_'.$iteration], $firstRecord['data_'.($iteration + 1)]);
            $firstAllelObject = new Allel($firstAllelPair);

            $secondAllelPair = array($secondRecord['data_'.$iteration], $secondRecord['data_'.($iteration + 1)]);
            $secondAllelObject = new Allel($secondAllelPair);

            if ($this->isDifferenceWithinTwoAllelPairs($firstAllelObject, $secondAllelObject))
            {
                $differenceCounter++;
            }

            $iteration += 2;
        }

        return $differenceCounter;
    }

    /**
     * @param $firstRecord
     * @param $secondRecord
     * @return int
     */
    public function compareTwoPreparedDbRecords($firstRecord, $secondRecord) : int
    {
        $differenceCounter = 0;

        foreach($firstRecord as $aKey => $allelValues) {
            if ($differenceCounter == $this->maxDifferenceCounter) {
                break;
            }

            $firstAllelPair = array($allelValues[0], $allelValues[1]);
            $firstAllelObject = new Allel($firstAllelPair);

            $secondAllelPair = array($secondRecord[$aKey][0], $secondRecord[$aKey][1]);
            $secondAllelObject = new Allel($secondAllelPair);

            if ($this->isDifferenceWithinTwoAllelPairs($firstAllelObject, $secondAllelObject))
            {
                $differenceCounter++;
            }
        }

        return $differenceCounter;
    }

    /**
     * @param Allel $fistAllelObject
     * @param Allel $secondAllelObject
     * @return bool
     */
    private function isDifferenceWithinTwoAllelPairs(Allel $fistAllelObject, Allel $secondAllelObject) : bool
    {
        if (
            !in_array($fistAllelObject->getAllelFirstValue(), $secondAllelObject->getAllelArrayCollection()) and
            !in_array($fistAllelObject->getAllelSecondValue(), $secondAllelObject->getAllelArrayCollection())
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $databaseRecords
     * @return array
     */
    public function compareMotherChildFather(Array $databaseRecords) : array
    {
        $outputArray = array();
        $caseNumberRoleArray = $this->dataMachine->createCaseNumberRoleArray($databaseRecords);

        foreach($caseNumberRoleArray as $caseNumber => $caseData)
        {
            $outputArray[$caseNumber]['differentFatherAllelNames'] = array();
            $outputArray[$caseNumber]['comments'] = '';
            $outputArray[$caseNumber]['isMotherBiologicalParent'] = 2;

            if ($this->doesRowHasThreeRoles($caseData))
            {
                if (!$this->isMotherBiologicalParent($caseData))
                {
                    $outputArray[$caseNumber]['isMotherBiologicalParent'] = 0;
                }
                else
                {
                    $outputArray[$caseNumber]['isMotherBiologicalParent'] = 1;

                    foreach($caseData['D'] as $allelName => $childAllelPair)
                    {
                        $childObject = new Allel($childAllelPair, $allelName);
                        $motherObject = new Allel($caseData['M'][$allelName], $allelName);
                        $fatherObject = new Allel($caseData['P'][$allelName], $allelName);

                        $differentFatherAlleName = $this->getDifferentFatherAllelName($childObject, $motherObject, $fatherObject);

                        if (!empty($differentFatherAlleName))
                        {
                            $outputArray[$caseNumber]['differentFatherAllelNames'][] = $differentFatherAlleName;
                        }
                    }
                }
            } else {
                $outputArray[$caseNumber]['comments'] = 'Brak trzeciej osoby';
            }

            $outputArray[$caseNumber]['differentAllelCounter'] = count($outputArray[$caseNumber]['differentFatherAllelNames']);
        }

        return $outputArray;
    }

    /**
     * @param array $caseNumberRole
     * @return bool
     */
    private function doesRowHasThreeRoles(Array $caseNumberRole) : bool
    {
        if ((!empty($caseNumberRole['M']) && !empty($caseNumberRole['D']) && !empty($caseNumberRole['P'])) && count($caseNumberRole) == 3)
        {
            return true;
        }

        return false;
    }

    /**
     * @param Allel $childObject
     * @param Allel $motherObject
     * @param Allel $fatherObject
     * @return string
     */
    private function getDifferentFatherAllelName(Allel $childObject, Allel $motherObject, Allel $fatherObject) : string
    {
        if ($this->isDifferenceWithinTwoAllelPairs($childObject, $motherObject))
        {
            if (!$this->isDifferenceWithinTwoAllelPairs($childObject, $fatherObject))
            {
                return $childObject->getAllelName();
            }
        }

        if ($this->isOneOfParentAllelMatch($childObject->getAllelFirstValue(), $motherObject))
        {
            if (!$this->isOneOfParentAllelMatch($childObject->getAllelSecondValue(), $fatherObject))
            {
                 return $childObject->getAllelName() . ' (A: ' . $childObject->getAllelSecondValue() . ')';
            }
        }

        if ($this->isOneOfParentAllelMatch($childObject->getAllelSecondValue(), $motherObject))
        {
            if (!$this->isOneOfParentAllelMatch($childObject->getAllelFirstValue(), $fatherObject) )
            {
                return $childObject->getAllelName() . ' (A: ' . $childObject->getAllelFirstValue() . ')';
            }
        }

        return '';
    }

    /**
     * @param $childAllelValue
     * @param Allel $parentObject
     * @return bool
     */
    private function isOneOfParentAllelMatch($childAllelValue, Allel $parentObject) : bool
    {
        if (in_array($childAllelValue, $parentObject->getAllelArrayCollection())) {
            return true;
        }

        return false;
    }

    /**
     * @param array $dbRecords
     * @return array
     */
    public function compareAllToAllDbRecords(Array $dbRecords) : array
    {
        $outputArray = array();

        foreach($dbRecords as $mainCaseNumber => $firstCaseDbRow) {
            $differenceAllelUids = array();

            foreach ($dbRecords as $secondaryCaseNumber => $secondCaseDbRow) {

                if ($mainCaseNumber == $secondaryCaseNumber) {
                    continue;
                }

                $differenceCounter = $this->compareTwoRawDbRecords($firstCaseDbRow, $secondCaseDbRow);

                if ($differenceCounter < $this->maxDifferenceCounter) {
                    $differenceAllelUids[] = $secondCaseDbRow['UID'] . ' ('.$differenceCounter.')';
                }
            }

            $outputArray[$firstCaseDbRow['UID']] = array(
                'differenceAllelUids' => $differenceAllelUids,
            );

            unset($dbRecords[$mainCaseNumber]);
        }

        return $outputArray;
    }

    /**
     * @param array $caseDbRow
     * @return bool
     */
    public function isMotherBiologicalParent(Array $caseDbRow) : bool
    {
        if (!empty($caseDbRow['M']) && !empty($caseDbRow['D'])) {
            $comparedBadCounter = $this->compareTwoPreparedDbRecords($caseDbRow['M'], $caseDbRow['D']);

            if ($comparedBadCounter >= 0 && $comparedBadCounter < $this->maxDifferenceCounter) {
                return true;
            }
        }

        return false;
    }
}