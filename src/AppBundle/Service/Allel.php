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

    /**
     * Allel constructor.
     * @param array $allelValues
     * @param null $allelName
     */
    public function __construct(Array $allelValues, $allelName = null)
    {
        $this->allelFirstValue = $allelValues[0];
        $this->allelSecondValue = $allelValues[1];
        $this->allelArrayCollection = $allelValues;
        $this->allelName = $allelName;
    }

    /**
     * @return string
     */
    public function getAllelName() : string
    {
        return $this->allelName;
    }

    /**
     * @return string
     */
    public function getAllelFirstValue() : string
    {
        return $this->allelFirstValue;
    }

    /**
     * @return string
     */
    public function getAllelSecondValue() : string
    {
        return $this->allelSecondValue;
    }

    /**
     * @return array
     */
    public function getAllelArrayCollection() : array
    {
        return $this->allelArrayCollection;
    }

}