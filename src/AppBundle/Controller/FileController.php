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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use League\Csv\CharsetConverter;
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

    private $dbConnection;
    private $uploadFileModel;
    private $csvFileValidator;

    private $csvFileHeaders = array();
    private $csvReader;

    public function __construct(
        Connection $connection,
        UploadFile $uploadFileModel,
        CsvFileValidator $csvFileValidator
    )
    {
        $this->dbConnection = $connection;
        $this->uploadFileModel = $uploadFileModel;
        $this->csvFileValidator = $csvFileValidator;
    }

    /**
     * @Route("/file/", name="_file_index")
     */
    public function indexController(Request $request = null, FileUploader $fileUploader)
    {

        $form = $this->createForm(UploadFileForm::class, $this->uploadFileModel);
        $form->handleRequest($request);

        //TODO: Wyrzucić do to serwisu

        if ($form->isSubmitted() && $form->isValid()) {
            $this->prepareUploadedFile($fileUploader);
        }

        return $this->render('file/uploadForm.html.twig',
            [
            'form' => $form->createView()
            ]
        );
    }

    private function prepareUploadedFile(FileUploader $fileUploader)
    {
        $filename = $fileUploader->upload($this->uploadFileModel->getCsv());

        $this->uploadFileModel->setCsv($filename);

        try {

            $this->csvReader = Reader::createFromPath('uploads/'.$filename, 'r');
            $this->csvReader->setDelimiter(';');

            $processedRecords = (new Statement())->process($this->csvReader);

            $this->csvFileHeaders = $this->csvFileValidator->getFileHeaders($processedRecords);

            if (!empty($this->csvFileHeaders)) {
                $this->createColumnNameSchema();
            } else {
                $this->addFlash('error', 'Błąd: Aplikacja nie była w stanie wskazać nagłówków kolumn przesłanego pliku. Sprawdź czy plik posiada prawidłowo opisane kolumny.');
            }

        } catch (\League\Csv\Exception $exception) {
            throw new \League\Csv\Exception('Error: ' . $exception->getMessage());
        }

        return $this->redirect($this->generateUrl('_file_index'));
    }

    private function createColumnNameSchema()
    {
        $this->dbConnection->beginTransaction();

        try {

            $this->dbConnection->exec('DELETE FROM column_name_schema');

            $columnSchemaInsert = $this->csvFileValidator->prepareColumnSchemaData();

            $this->dbConnection->insert('column_name_schema', $columnSchemaInsert );

            $this->dbConnection->commit();

        } catch(DBALException $exception) {
            $this->dbConnection->rollBack();
            throw new DBALException('Error: ' . $exception->getMessage());
        }

        $this->insertIntoDatabase();
    }

    private function insertIntoDatabase()
    {
        $followRecordKey = 0;
        $this->dbConnection->beginTransaction();

        try {

            $this->dbConnection->exec('DELETE FROM imported_data');
            $this->dbConnection->exec('DELETE FROM compare_all_to_all');
            $this->dbConnection->exec('DELETE FROM compare_m_p_d');


            foreach ($this->csvReader->getRecords() as $recordKey => $csvRow) {

                $followRecordKey = $recordKey;

                if ($this->csvFileValidator->isTooManyEmptyRows()) {
                    break;
                }

                if ($this->csvFileValidator->isRowEmpty($csvRow)) {
                    continue;
                }

                if (!$this->csvFileValidator->rowIsHeader($csvRow)) {

                    $importedDataColumnNames = $this->csvFileValidator->prepareImportedDataColumns();

                    $preparedCsvData = $this->csvFileValidator->modifyRowCharsetToUtf8($csvRow);

                    $this->dbConnection->insert('imported_data', array_combine($importedDataColumnNames, $preparedCsvData) );
                }
            }

            $this->dbConnection->commit();

            $this->addFlash('success', 'Dane zostały prawidłowo zaimportowane do bazy.');

        } catch (DBALException $exception) {
            $this->addFlash('error', 'Błąd: Błędne wartości w rekordzie ('.++$followRecordKey.').');
        }

        $this->redirect($this->generateUrl('_file_index'));
    }
}