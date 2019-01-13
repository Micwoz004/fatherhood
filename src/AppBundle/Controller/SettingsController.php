<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2019-01-13
 * Time: 12:38
 */

namespace AppBundle\Controller;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /**
     * @var Connection
     */
    private $dbConnection;

    /**
     * SettingController constructor.
     * @param Connection $dbConnection
     */
    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * @Route("/settings/", name="_settings")
     */
    public function settings(Request $request)
    {
        if ($request->isMethod('POST') and !empty($request->get('discard_after'))) {
            try {
                $this->dbConnection->update('settings', array(
                    'value' => $request->get('discard_after')
                ), array(
                    'name' => 'discard_after'
                ));
            } catch (DBALException $exception) {
                $this->addFlash('error', 'Wystąpił bląd podczas zapisu ustawień. Jeżeli problem wystąpi ponownie, skontaktuj się z administratorem systemu.');
            }
        }

        try {
            $discardAfter = $this->dbConnection->fetchColumn('SELECT value FROM settings WHERE name = ?', array('discard_after'), 0);
        } catch (DBALException $exception) {
            $this->addFlash('error', 'Wystąpił błąd podczas pobierania ustawień. Jeżeli problem wystąpi ponownie, skontaktuj się z administratorem systemu.');
        }

        return $this->render('settings/index.html.twig', [
            'discardAfterValue' => $discardAfter
        ]);

    }
}