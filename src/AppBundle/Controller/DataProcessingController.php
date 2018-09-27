<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2018-01-18
 * Time: 11:21
 */

namespace AppBundle\Controller;

use AppBundle\Entity\CompareAllToAll;
use AppBundle\Entity\CompareMPD;
use AppBundle\Service\Comparison;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DataProcessingController extends Controller
{

    private $em;
    private $dbConnection;

    public function __construct(EntityManagerInterface $entityManager, Connection $connection)
    {
        $this->em = $entityManager;
        $this->dbConnection = $connection;
    }

    /**
     * @Route("/data-processing/compare-all-to-all", name="_compare_all_to_all")
     */
    public function compareAllToAllView() : Response
    {
        $comparedData = $this->em->getRepository('AppBundle\Entity\CompareAllToAll')->findAll();

        return $this->render(
            '/table/compareAllToAllDetails.html.twig',
            [
                'comparedData' => $comparedData
            ]
        );
    }

    /**
     * @Route("/data-processing/compare-all-to-all-again", name="_compare_all_to_all_again")
     */
    public function compareAllToAllAgain(Request $request) : JsonResponse
    {
        try {

            if (!$request->isMethod('POST')) {
                throw new \Exception('This is not an AJAX request!', 403);
            }

            $columnSchema = $this->dbConnection->fetchAll('SELECT * FROM column_name_schema');
            $dbRecords = $this->dbConnection->fetchAll('SELECT * FROM imported_data');

            $comparison = new Comparison($columnSchema);
            $comparedRecords = $comparison->compareAllToAllDbRecords($dbRecords);

            $this->dbConnection->beginTransaction();
            $this->dbConnection->exec('DELETE FROM compare_all_to_all');

            foreach($comparedRecords as $uid => $comparedRecord)
            {
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
     * @Route("/data-processing/compare-m-p-d", name="_compare_m_p_d")
     */
    public function compareMPDAction() : Response
    {
        $comparedData = $this->em->getRepository('AppBundle\Entity\CompareMPD')->findAll();

        return $this->render(
            '/table/compareMDPDetails.html.twig',
            [
                'comparedData' => $comparedData
            ]
        );
    }

    /**
     * @Route("/data-processing/compare-m-p-d-again", name="_compare_m_p_d_again")
     */
    public function compareMPDAgain() : RedirectResponse
    {

        try {
            $columnSchema = $this->dbConnection->fetchAll('SELECT * FROM column_name_schema');
            $dbRecords = $this->dbConnection->fetchAll('SELECT * FROM imported_data');

            $comparison = new Comparison($columnSchema);
            $comparedData = $comparison->compareMotherChildFather($dbRecords);

            foreach($comparedData as $caseNumber => $data)
            {
                $compareMPD = new CompareMPD();
                $compareMPD->setCaseNumber($caseNumber);
                $compareMPD->setDifferentFatherAllelNames(json_encode($data['differentFatherAllelNames']));
                $compareMPD->setIsMotherBiologicalParent(($data['isMotherBiologicalParent'] ? 1 : 0));

                $this->em->persist($compareMPD);
                $this->em->flush();
                $this->em->clear();
            }

            return $this->redirect
            (
                $this->generateUrl('_compare_m_p_d')
            );

        } catch (\Exception $exception) {
            throw new \Exception('Error: ' . $exception->getMessage());
        }
    }
}