<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2018-01-15
 * Time: 14:06
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class UploadFile
{
    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="Please, upload a CSV file containing PI test results")
     * @Assert\File(mimeTypes={ "text/plain" })
     */
    private $csv;

    /**
     * @return mixed
     */
    public function getCsv()
    {
        return $this->csv;
    }

    /**
     * @param mixed $csv
     */
    public function setCsv($csv)
    {
        $this->csv = $csv;
    }
}