<?php
/**
 * Created by PhpStorm.
 * User: Michał
 * Date: 17.03.2018
 * Time: 20:33
 */

namespace AppBundle\Service;

class Comparison
{
    const MAX_DIFFERENCE_COUNTER = 4;

    private $dbColumnSchema;
    private $dataMachine;

    public function __construct($dbColumnSchema)
    {
        $this->dbColumnSchema = $dbColumnSchema;
        $this->dataMachine = new DataMachine($dbColumnSchema);
    }

    public function compareTwoRawDbRecords($firstRecord, $secondRecord)
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

    public function compareTwoPreparedDbRecords($firstRecord, $secondRecord)
    {
        $differenceCounter = 0;

        foreach($firstRecord as $aKey => $allelValues) {
            if ($differenceCounter == self::MAX_DIFFERENCE_COUNTER) {
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

    private function isDifferenceWithinTwoAllelPairs(Allel $fistAllelObject, Allel $secondAllelObject)
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

    public function compareMotherChildFather(Array $databaseRecords)
    {
        $outputArray = array();
        $caseNumberRoleArray = $this->dataMachine->createCaseNumberRoleArray($databaseRecords);

        foreach($caseNumberRoleArray as $caseNumber => $caseData)
        {
            $outputArray[$caseNumber]['differentFatherAllelNames'] = array();
            $outputArray[$caseNumber]['isMotherBiologicalParent'] = true;

            if ($this->doesRowHasThreeRoles($caseData))
            {
                if (!$this->isMotherBiologicalParent($caseData))
                {
                    $outputArray[$caseNumber]['isMotherBiologicalParent'] = false;
                }
                else
                {
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
            }
        }

        return $outputArray;
    }

    private function doesRowHasThreeRoles(Array $caseNumberRole) : bool
    {
        if ((!empty($caseNumberRole['M']) && !empty($caseNumberRole['D']) && !empty($caseNumberRole['P'])) && count($caseNumberRole) == 3)
        {
            return true;
        }

        return false;
    }

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
                 return $childObject->getAllelName();
            }
        }

        if ($this->isOneOfParentAllelMatch($childObject->getAllelSecondValue(), $motherObject))
        {
            if (!$this->isOneOfParentAllelMatch($childObject->getAllelFirstValue(), $fatherObject) )
            {
                return $childObject->getAllelName();
            }
        }

        return null;
    }

    private function isOneOfParentAllelMatch($childAllelValue, Allel $parentObject) : bool
    {
        if (in_array($childAllelValue, $parentObject->getAllelArrayCollection())) {
            return true;
        }

        return false;
    }

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

                if ($differenceCounter < self::MAX_DIFFERENCE_COUNTER) {
                    $differenceAllelUids[] = $secondCaseDbRow['UID'] . '('.$differenceCounter.')';
                }
            }

            $outputArray[$firstCaseDbRow['UID']] = array(
                'differenceAllelUids' => $differenceAllelUids,
            );

            unset($dbRecords[$mainCaseNumber]);
        }

        return $outputArray;
    }

    public function isMotherBiologicalParent(Array $caseDbRow) : bool
    {
        if (!empty($caseDbRow['M']) && !empty($caseDbRow['D'])) {
            $comparedBadCounter = $this->compareTwoPreparedDbRecords($caseDbRow['M'], $caseDbRow['D']);

            if ($comparedBadCounter >= 0 && $comparedBadCounter < self::MAX_DIFFERENCE_COUNTER) {
                return true;
            }
        }

        return false;
    }
}