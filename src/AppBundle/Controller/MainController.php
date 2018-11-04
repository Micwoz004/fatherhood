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
}
