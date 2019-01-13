<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2019-01-13
 * Time: 14:39
 */

namespace AppBundle\Controller;


use AppBundle\Entity\CompareAllToAll;
use AppBundle\Entity\CompareMDP;
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

class CompareMDPController extends AbstractController
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
     * DataProcessingController constructor.
     * @param EntityManagerInterface $entityManager
     * @param Connection $connection
     */
    public function __construct(EntityManagerInterface $entityManager, Connection $connection)
    {
        $this->em = $entityManager;
        $this->dbConnection = $connection;
    }

    /**
     * @Route("/compare-mdp", name="_compare_mdp")
     */
    public function compareMDPAction() : Response
    {
        $comparedData = $this->em->getRepository(CompareMDP::class)->findBy([], [ 'differentAllelCounter' => 'ASC' ]);

        return $this->render('/compareMDP/view.html.twig', [
                'comparedData' => $comparedData
        ]);
    }

    /**
     * @Route("/compare-mdp-again", name="_compare_mdp_again")
     */
    public function compareMDPAgain(Request $request) : JsonResponse
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
            $comparedData = $comparison->compareMotherChildFather($dbRecords);

            $this->dbConnection->beginTransaction();
            $this->dbConnection->exec('DELETE FROM compare_m_p_d');

            foreach($comparedData as $caseNumber => $data)
            {
                $compareMDP = new CompareMDP();
                $compareMDP->setCaseNumber($caseNumber);

                $compareMDP->setDifferentFatherAllelNames(json_encode($data['differentFatherAllelNames']));
                $compareMDP->setDifferentAllelCounter($data['differentAllelCounter']);
                $compareMDP->setIsMotherBiologicalParent($data['isMotherBiologicalParent']);
                $compareMDP->setComments($data['comments']);

                $this->em->persist($compareMDP);
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
     * @Route("/compare-mdp-excel", name="_compare_mdp_excel")
     */
    public function generateXls()
    {
        try {
            $compareMDP = $this->dbConnection->fetchAll('SELECT case_number, comments, is_mother_biological_parent, different_allel_counter, different_father_allel_names FROM compare_m_p_d ORDER BY different_allel_counter ASC');

            if (empty($compareMDP)) {
                throw new EmptyVariableException();
            }

            foreach($compareMDP as $k => $v) {
                $v['different_father_allel_names'] = json_decode($v['different_father_allel_names'], true);
                $compareMDP[$k]['is_mother_biological_parent'] = ( $v['is_mother_biological_parent'] == 1 ? 'Tak' : ( $v['is_mother_biological_parent'] == 2 ? 'Nie zweryfikowano' : 'Nie' ) );
                $compareMDP[$k]['different_father_allel_names'] = ( empty($v['different_father_allel_names']) ? 'Nie dotyczy' : implode(', ', $v['different_father_allel_names']) );
            }

            array_unshift($compareMDP, array(
                'Numer sprawy', 'Komentarz', 'Czy matka jest biologicznym rodzicem', 'Ilość odrzuceń', 'Allele odrzucone'
            ));

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->fromArray($compareMDP, NULL, 'A1');

            // redirect output to client browser
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.date('Y_m_d_H_i_s').'-compare_mdp'.'.xls"');
            header('Cache-Control: max-age=0');

            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');

        } catch (DBALException $exception) {
            $this->addFlash('error', 'Wystąpił błąd podczas pobierania danych z bazy. Kod: ' . $exception->getCode());
        } catch (EmptyVariableException $exception) {
            $this->addFlash('error', 'Porównanie "MDP" nie zostało jeszcze wykonane. Nie można utworzyć pliku XLS.');
        } catch (Exception $exception) {
            $this->addFlash('error', 'Wystąpił błąd podczas generowania pliku XLS.');
        }

        return $this->redirectToRoute('_compare_mdp');
    }

}