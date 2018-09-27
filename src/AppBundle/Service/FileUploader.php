<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2018-01-15
 * Time: 14:48
 */

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDir;

    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
    }

    public function upload(UploadedFile $file) : string
    {
        $fileName = md5(uniqid()).'.csv';

        $file->move($this->getTargetDir(), $fileName);

        return $fileName;
    }

    public function getTargetDir() : string
    {
        return $this->targetDir;
    }
}

