<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2018-05-15
 * Time: 10:33
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="compare_m_p_d")
 */
class CompareMDP
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, unique=true, nullable=false)
     */
    private $caseNumber;

    /**
     * @ORM\Column(type="text")
     */
    private $differentFatherAllelNames;

    /**
     * @ORM\Column(type="integer")
     */
    private $differentAllelCounter;

    /**
     * @ORM\Column(type="integer")
     */
    private $isMotherBiologicalParent;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $comments;

    /**
     * @return mixed
     */
    public function getCaseNumber()
    {
        return $this->caseNumber;
    }

    /**
     * @param mixed $caseNumber
     */
    public function setCaseNumber($caseNumber)
    {
        $this->caseNumber = $caseNumber;
    }

    /**
     * @return mixed
     */
    public function getDifferentFatherAllelNames()
    {
        return json_decode($this->differentFatherAllelNames);
    }

    /**
     * @param mixed $differentFatherAllelNames
     */
    public function setDifferentFatherAllelNames($differentFatherAllelNames)
    {
        $this->differentFatherAllelNames = $differentFatherAllelNames;
    }

    /**
     * @return mixed
     */
    public function getDifferentAllelCounter()
    {
        return $this->differentAllelCounter;
    }

    /**
     * @param mixed $differentAllelCounter
     */
    public function setDifferentAllelCounter($differentAllelCounter)
    {
        $this->differentAllelCounter = $differentAllelCounter;
    }

    /**
     * @return mixed
     */
    public function getisMotherBiologicalParent()
    {
        return $this->isMotherBiologicalParent;
    }

    /**
     * @param mixed $isMotherBiologicalParent
     */
    public function setIsMotherBiologicalParent($isMotherBiologicalParent)
    {
        $this->isMotherBiologicalParent = $isMotherBiologicalParent;
    }

    /**
     * @param mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

}