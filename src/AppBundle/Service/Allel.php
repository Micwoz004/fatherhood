<?php
/**
 * Created by PhpStorm.
 * User: Michał
 * Date: 24.03.2018
 * Time: 17:52
 */

namespace AppBundle\Service;

class Allel
{
    private $allelName;
    private $allelFirstValue;
    private $allelSecondValue;
    private $allelArrayCollection = array();

    public function __construct(Array $allelValues, $allelName = null)
    {
        $this->allelFirstValue = $allelValues[0];
        $this->allelSecondValue = $allelValues[1];
        $this->allelArrayCollection = $allelValues;
        $this->allelName = $allelName;
    }

    public function getAllelName() : string
    {
        return $this->allelName;
    }

    public function getAllelFirstValue() : string
    {
        return $this->allelFirstValue;
    }

    public function getAllelSecondValue() : string
    {
        return $this->allelSecondValue;
    }

    public function getAllelArrayCollection() : array
    {
        return $this->allelArrayCollection;
    }

}