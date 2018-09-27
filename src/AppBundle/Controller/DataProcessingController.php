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
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DataProcessingController extends Controller
{

    /**
     * @Route("/dataProcessing/compare_all_to_all", name="_compare_all_to_all")
     */
    public function compareAllToAllAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $comparedData = $entityManager->getRepository('AppBundle\Entity\CompareAllToAll')->findAll();

        return $this->render(
            '/table/compareAllToAllDetails.html.twig',
            [
                'comparedData' => $comparedData
            ]
        );
    }

    /**
     * @Route("/dataProcessing/compare_all_to_all_ajax", name="_compare_all_to_all_ajax")
     */
    public function compareAllToAllAjax() {

        $db = $this->get('database_connection');

        try {

            $columnSchema = $db->executeQuery('SELECT * FROM column_name_schema')->fetch();
            $dbRecords = $db->executeQuery('SELECT * FROM imported_data')->fetchAll();

            $comparison = new Comparison($columnSchema);
            $comparedRecords = $comparison->compareAllToAllDbRecords($dbRecords);

            $entityManager = $this->getDoctrine()->getManager();
            //$entityManager->getConnection()->beginTransaction();

            foreach($comparedRecords as $uid => $comparedRecord)
            {
                $compareAllToAll = new CompareAllToAll();
                $compareAllToAll->setUID($uid);
                $compareAllToAll->setNumberOfRelationship(count($comparedRecord['differenceAllelUids']));
                $compareAllToAll->setDifferentAllelUids(json_encode($comparedRecord['differenceAllelUids']));

                $entityManager->persist($compareAllToAll);
                $entityManager->flush();
                $entityManager->clear();
            }

            //$entityManager->getConnection()->commit();

            return $this->redirect(
                $this->generateUrl('_compare_all_to_all')
             );
    
        } catch (\Exception $exception) {
            //$entityManager->getConnection()->rollback();
            throw new \Exception('Error: ' . $exception->getMessage());
        }
    }

    /**
     * @Route("/dataProcessing/compare_m_p_d", name="_compare_m_p_d")
     */
    public function compareMPDAction() {

        $entityManager = $this->getDoctrine()->getManager();
        $comparedData = $entityManager->getRepository('AppBundle\Entity\CompareMPD')->findAll();

        return $this->render(
            '/table/compareMDPDetails.html.twig',
            [
                'comparedData' => $comparedData
            ]
        );
    }

    /**
     * @Route("/dataProcessing/compare_m_p_d_ajax", name="_compare_m_p_d_ajax")
     */
    public function compareMPDAjax() {
        
        $db = $this->get('database_connection');

        try {
            $columnSchema = $db->executeQuery('SELECT * FROM column_name_schema')->fetch();
            $dbRecords = $db->executeQuery('SELECT * FROM imported_data')->fetchAll();

            $comparison = new Comparison($columnSchema);
            $comparedData = $comparison->compareMotherChildFather($dbRecords);

            $entityManager = $this->getDoctrine()->getManager();
            //$entityManager->getConnection()->beginTransaction();

            foreach($comparedData as $caseNumber => $data)
            {
                $compareMPD = new CompareMPD();
                $compareMPD->setCaseNumber($caseNumber);
                $compareMPD->setDifferentFatherAllelNames(json_encode($data['differentFatherAllelNames']));
                $compareMPD->setIsMotherBiologicalParent(($data['isMotherBiologicalParent'] ? 1 : 0));

                $entityManager->persist($compareMPD);
                $entityManager->flush();
                $entityManager->clear();
            }

            //$entityManager->getConnection()->commit();

            return $this->redirect(
                $this->generateUrl('_compare_m_p_d')
            );

        } catch (\Exception $exception) {
            throw new \Exception('Error: ' . $exception->getMessage());
        }

    }



}