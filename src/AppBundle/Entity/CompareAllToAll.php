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
 * @ORM\Table(name="compare_all_to_all")
 */
class CompareAllToAll
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, unique=true, nullable=false)
     */
    private $UID;

    /**
     * @ORM\Column(type="integer", name="number_of_relationship", nullable=false)
     */
    private $numberOfRelationship;

    /**
     * @ORM\Column(type="text")
     */
    private $differentAllelUids;

    /**
     * @return mixed
     */
    public function getUID()
    {
        return $this->UID;
    }

    /**
     * @param mixed $UID
     */
    public function setUID($UID)
    {
        $this->UID = $UID;
    }

    /**
     * @return mixed
     */
    public function getNumberOfRelationship()
    {
        return $this->numberOfRelationship;
    }

    /**
     * @param mixed $numberOfRelationship
     */
    public function setNumberOfRelationship($numberOfRelationship)
    {
        $this->numberOfRelationship = $numberOfRelationship;
    }

    /**
     * @return mixed
     */
    public function getDifferentAllelUids()
    {
        return $this->differentAllelUids;
    }

    /**
     * @param mixed $differentAllelUids
     */
    public function setDifferentAllelUids($differentAllelUids)
    {
        $this->differentAllelUids = $differentAllelUids;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

}