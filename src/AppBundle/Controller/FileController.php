<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2018-01-15
 * Time: 13:25
 */

namespace AppBundle\Controller;

use AppBundle\Entity\UploadFile;
use AppBundle\Form\UploadFileForm;
use AppBundle\Service\FileUploader;
use Doctrine\DBAL\DBALException;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use AppBundle\Service\CsvFileValidator;

class FileController extends Controller
{
    /**
     * @Route("/file/", name="_file_index")
     */
    public function indexController(Request $request = null, FileUploader $fileUploader) {

        $upload = new UploadFile();
        $validator = new CsvFileValidator();

        $db = $this->get('database_connection');

        $form = $this->createForm(UploadFileForm::class, $upload);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $upload->getCsv();
            $filename = $fileUploader->upload($file);
            $upload->setCsv($filename);

            //Add to database rows from csv
            $csv = Reader::createFromPath('uploads/' . $filename, 'r');
            $csv->setDelimiter(';');

            $records = (new Statement())->process($csv);

            //Get headers
            $header = $validator->getFileHeaders($records);

            if (!empty($header)) {

                $db->beginTransaction();
                $upperlimit = array_search('UID', $header);
                $alldata = (count($header) - intval($upperlimit) - 1);
                $column_list = $validator->getQueryColumnList($alldata);

                try {
                    $i = 1;
                    $db->executeQuery('DELETE FROM imported_data');
                    $db->executeQuery('DELETE FROM column_name_schema');
                    $query = $db->prepare('INSERT INTO column_name_schema ('. ltrim($column_list["cnames"], ',') .') VALUES ('. ltrim($column_list["values"], ',') .')');

                    foreach ($header as $hKey => $hr) {
                        if ($hKey <= $upperlimit or trim($hr) == 'Uwagi' or empty($hr)) { continue; }
                        $query->bindValue($i++, $hr);
                    }

                    $query->execute();

                    foreach ($csv->getRecords() as $rKey => $csvRow) {

                        if ($rKey < 2) { continue; }

                        $query = $db->prepare('INSERT INTO imported_data (lab, case_number, case_role, sample_number, uid'. $column_list["cnames"] .', comments) VALUES (?, ?, ?, ?, ?'.$column_list["values"].', ?)');

                        foreach ($csvRow as $crKey => $cr) {
                            $query->bindValue(($crKey+1), str_replace(',', '.', $cr));
                        }
                        $query->execute();
                    }
                } catch(DBALException $e) {
                    $db->rollBack();
                    throw new DBALException($e->getMessage());
                }

                $this->addFlash('success', 'Data from the uploaded file has been correctly added to the database.');
                $db->commit();

            } else {
                $this->addFlash('error', 'Error: Headers failed to be specified.');
            }

            return $this->redirect($this->generateUrl('_file_index'));
        }

        return $this->render('file/uploadForm.html.twig', [
            'form' => $form->createView()
        ]);
    }
}