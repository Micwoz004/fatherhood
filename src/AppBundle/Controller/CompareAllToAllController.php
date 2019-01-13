<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2019-01-13
 * Time: 13:11
 */

namespace AppBundle\Controller;


use AppBundle\Entity\CompareAllToAll;
use AppBundle\Exceptions\EmptyVariableException;
use AppBundle\Service\Comparison;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CompareAllToAllController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Connection
     */
    private $dbConnection;

    /**
     * CompareAllToAllController constructor.
     * @param EntityManagerInterface $entityManager
     * @param Connection $connection
     */
    public function __construct(EntityManagerInterface $entityManager, Connection $connection)
    {
        $this->em = $entityManager;
        $this->dbConnection = $connection;
    }

    /**
     * @Route("/compare-all-to-all", name="_compare_all_to_all")
     */
    public function compareAllToAllView(): Response
    {
        $comparedData = $this->em->getRepository('AppBundle\Entity\CompareAllToAll')->findAll();

        return $this->render('compareAllToAll/view.html.twig', [
            'comparedData' => $comparedData
        ]);
    }

    /**
     * @Route("/compare-all-to-all-again", name="_compare_all_to_all_again")
     */
    public function compareAllToAllAgain(Request $request): JsonResponse
    {
        try {

            if (!$request->isMethod('POST')) {
                throw new \Exception('This is not an AJAX request!', 403);
            }

            $columnSchema = $this->dbConnection->fetchAssoc('SELECT * FROM column_name_schema');
            $dbRecords = $this->dbConnection->fetchAll('SELECT * FROM imported_data');

            if (count($columnSchema) === 1) {
                throw new \Exception('W bazie danych znajduje się błędny schemat Alleli. Załaduj nowy schemat, aby kontynuować.');
            }

            $comparison = new Comparison($columnSchema, $this->dbConnection);
            $comparedRecords = $comparison->compareAllToAllDbRecords($dbRecords);

            $this->dbConnection->beginTransaction();
            $this->dbConnection->exec('DELETE FROM compare_all_to_all');

            foreach ($comparedRecords as $uid => $comparedRecord) {
                $compareAllToAll = new CompareAllToAll();
                $compareAllToAll->setUID($uid);
                $compareAllToAll->setNumberOfRelationship(count($comparedRecord['differenceAllelUids']));
                $compareAllToAll->setDifferentAllelUids(json_encode($comparedRecord['differenceAllelUids']));

                $this->em->persist($compareAllToAll);
                $this->em->flush();
            }

            $this->dbConnection->commit();

            return new JsonResponse(
                [
                    'success' => true,
                    'code' => 200,
                    'message' => ''
                ]);

        } catch (\Exception $exception) {

            if ($this->dbConnection->isTransactionActive()) {
                $this->dbConnection->rollBack();
            }

            return new JsonResponse(
                [
                    'success' => false,
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage()
                ]);
        }
    }

    /**
     * @Route("/compare-all-to-all-excel", name="_compare_all_to_all_excel")
     */
    public function generateXls()
    {
        try {
            $compareAlltoAll = $this->dbConnection->fetchAll('SELECT UID, number_of_relationship, different_allel_uids FROM compare_all_to_all');

            if (empty($compareAlltoAll)) {
                throw new EmptyVariableException();
            }

            foreach($compareAlltoAll as $k => $v) {
                $compareAlltoAll[$k]['different_allel_uids'] = implode(', ', json_decode($v['different_allel_uids'], true));
            }

            array_unshift($compareAlltoAll, array(
                'UID', 'Potencjalne pokrewieństwo', 'Lista spokrewnionych UID'
            ));

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->fromArray($compareAlltoAll, NULL, 'A1');

            // redirect output to client browser
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.date('Y_m_d_H_i_s').'-compare_ata'.'.xls"');
            header('Cache-Control: max-age=0');

            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');

        } catch (DBALException $exception) {
            $this->addFlash('error', 'Wystąpił błąd podczas pobierania danych z bazy. Kod: ' . $exception->getCode());
        } catch (EmptyVariableException $exception) {
            $this->addFlash('error', 'Porównanie "każdy do każdego" nie zostało jeszcze wykonane. Nie można utworzyć pliku XLS.');
        } catch (Exception $exception) {
            $this->addFlash('error', 'Wystąpił błąd podczas generowania pliku XLS.');
        }

        return $this->redirectToRoute('_compare_all_to_all');
    }
}