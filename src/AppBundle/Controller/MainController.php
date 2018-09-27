<?php

namespace AppBundle\Controller;

use AppBundle\Service\WidgetBuilder;
use AppBundle\Service\DataMachine;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends Controller
{

    private $dbConnection;

    public function __construct(Connection $connection)
    {
        $this->dbConnection = $connection;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction() : Response
    {
        return $this->render('home.html.twig');
    }

    /**
     * @Route("/main-table", name="_main_table_view")
     */
    public function tableAction() : Response
    {
        return $this->render('table.html.twig');
    }
    
    /**
     * @Route("/main-table-loader", name="_main_table_loader")
     */
    public function tableActionLoader(Request $request) : JsonResponse
    {

        $widgetBuilder = new WidgetBuilder();

        try {

            if (!$request->isMethod('POST')) {
                throw new \Exception('This is not an AJAX request!', 403);
            }

            $columnNames = $this->dbConnection->fetchAll('SELECT * FROM column_name_schema');

            $dataMachine = new DataMachine($columnNames);

            $columnNames = $dataMachine->unsetEmptyColumns($columnNames);

            $theadTable = $widgetBuilder->dbTableThead($columnNames);

            $importedData = $this->dbConnection->fetchAll('SELECT * FROM imported_data');
            $importedData = $dataMachine->unsetEmptyColumns($importedData);
            $tbodyTable = $widgetBuilder->dbTableRows($importedData);

            return new JsonResponse(
                [
                'success' => true,
                'code' => 200,
                'message' => '',
                'data'    => [
                    'columns' => $theadTable,
                    'rows' => $tbodyTable
                ]
            ]);

        } catch (\Exception $exception) {

            return new JsonResponse([
                'success' => false,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
                'data' => []
            ]);

        }
    }

//    /**
//     * @Route("/test", name="_test")
//     */
//    public function actionTest()
//    {
//        try {
////
////            $databaseColumnSchema = $this->dbConnection->fetchAll('SELECT * FROM column_name_schema');
////
////            $dataMachine = new DataMachine($databaseColumnSchema);
////            $importedData = $this->dbConnection->fetchAll('SELECT * FROM imported_data');
////
////            //$array = $dataMachine->unsetEmptyColumns($data);
////
////
////            //$array = $comparison->createDataArray($databaseRow);
////
////            var_dump($importedData);
//        } catch (DBALException $e) {
//            throw new DBALException($e->getMessage());
//        }
//    }

//    /**
//     * @Route("/get_history_file_list", name="_get_history_file_list")
//     */
//    public function getFileHistoryAction(Request $request) {
//        if ($request->isXmlHttpRequest()) {
//            $fileList = [];
//            $finder = new Finder();
//            $finder->ignoreUnreadableDirs()->in('upload/');
//            $finder->files()->name('*.csv')->sortByModifiedTime();
//
//            foreach ($finder as $file) {
//                $fileList[] = array( 'displayUrl' => $file->getRelativePathname(), 'absoluteUrl' => 'upload/' . $file->getRelativePathname(), 'test' => $file->getContents() );
//            }
//
//            return new JsonResponse($fileList);
//
//        } else {
//            throw new BadRequestHttpException('Callback is not an Ajax Request!');
//        }
//    }
//
//    /**
//     * @Route("/load_new_file", name="_load_new_file")
//     */
//    public function loadNewFileData(Request $request, EntityManagerInterface $em) {
//        if($request->isMethod('post')) {
//            $filename = $request->request->get('filename');
//            $csv = Reader::createFromPath('upload/' . $filename, 'r');
//            $records = (new Statement())->process($csv);
//
//            //$excelColumnObject = new ExcelColumnValidation();
//
//            $em->createQuery('DELETE FROM AppBundle:ImportedData')->execute();
//            //$all = $em->getRepository('AppBundle:ImportedData')->;
//            //$em->remove($all);
//            //$em->flush();
//
//            //Ustaw numery kolumn
//            foreach ($records->getRecords() as $key => $record) {
//                $excelColumnObject->getColumnValidationKeys($record);
//                if ($key == 5) {
//                    //Wstawić obsługę błędów - dodać metodę, która sprawdza czy wszystkie kolumny zostały znalezione
//                    break;
//                }
//            }
//
//            try {
//                foreach ($records->getRecords() as $record) {
//
//                    }
//                }
//            } catch (PDOException $ex ){
//                $this->addFlash('error', 'Wystąpił błąd podczas dodawania danych do bazy.');
//            }
//
//            return $this->redirect('/');
//        } else {
//            throw new BadRequestHttpException('Requested method isn\'t POST type');
//        }
//    }
}
