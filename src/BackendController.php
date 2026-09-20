<?php
declare(strict_types=1);

namespace Plugins\Eventregistration\src;

use App\Backend\Controller\BaseController;
use Plugins\Eventregistration\db\EventAdminDb;
use Magepattern\Component\HTTP\Request;
use Magepattern\Component\Tool\SmartyTool;

class BackendController extends BaseController
{
    public function run(): void
    {
        SmartyTool::addTemplateDir('Eventregistration', ROOT_DIR . 'plugins' . DS . 'Eventregistration' . DS . 'views' . DS . 'admin');

        $action = $_GET['action'] ?? null;

        if ($action && method_exists($this, $action)) {
            $this->$action();
        } else {
            $this->jsonResponse(false, 'Action invalide.');
        }
    }

    /**
     * Charge la liste des inscrits en AJAX
     */
    /**
     * Charge la liste AJAX des inscrits
     */
    private function loadList(): void
    {
        if (ob_get_length()) ob_clean();

        $idNews = (int)($_GET['id_module'] ?? $_GET['id_news'] ?? 0);

        if ($idNews <= 0) {
            // C'est ici que votre erreur s'affichait !
            echo '<div class="alert alert-warning">ID de l\'actualité manquant.</div>';
            return;
        }

        $db = new EventAdminDb(); // Ou EventDb selon le nom que vous lui avez donné
        $registrations = $db->getRegistrations($idNews);

        $columns = [
            'firstname'     => ['title' => 'Prénom', 'type' => 'text'],
            'lastname'      => ['title' => 'Nom', 'type' => 'text', 'class' => 'fw-bold text-dark'],
            'email'         => ['title' => 'Email', 'type' => 'text'],
            'phone'         => ['title' => 'Téléphone', 'type' => 'text'],
            'date_register' => ['title' => 'Date d\'inscription', 'type' => 'date']
        ];

        $this->view->assign([
            'registrations' => $registrations,
            'ajax_columns'  => $columns,
            'hashtoken'     => $this->session->getToken()
        ]);

        $this->view->display('ajax/registration_list.tpl');
    }

    /**
     * Sauvegarde la configuration de l'évènement
     */
    public function saveConfig(): void
    {
        if (ob_get_length()) ob_clean();

        $token = Request::isPost('hashtoken') ? $_POST['hashtoken'] : '';
        if (!$this->session->validateToken($token)) {
            $this->jsonResponse(false, 'Session expirée.');
        }

        $idNews = (int)($_POST['id_news'] ?? 0);
        $maxParticipants = (int)($_POST['max_participants'] ?? 0);

        $registrationEnabled = (int)($_POST['registration_enabled'] ?? 0);

        if ($idNews === 0) {
            $this->jsonResponse(false, 'Référence de l\'actualité introuvable.');
        }

        $db = new EventAdminDb();
        if ($db->saveEventConfig($idNews, $maxParticipants, $registrationEnabled)) {
            $this->jsonResponse(true, 'Configuration de l\'évènement sauvegardée.');
        } else {
            $this->jsonResponse(false, 'Erreur lors de la sauvegarde.');
        }
    }

    /**
     * Supprime un participant
     */
    /**
     * Supprime un participant
     */
    public function delete(): void
    {
        if (ob_get_length()) ob_clean();

        $token = Request::isPost('hashtoken') ? $_POST['hashtoken'] : '';
        if (!$this->session->validateToken($token)) {
            $this->jsonResponse(false, 'Session expirée. Veuillez rafraichir la page.');
        }

        $idRegistration = (int)($_POST['id_registration'] ?? 0);

        if ($idRegistration > 0) {
            $db = new EventAdminDb();
            if ($db->deleteRegistration($idRegistration)) {
                // SOLUTION : On renvoie un nouveau token valide pour le prochain clic de suppression !
                $this->jsonResponse(true, 'Inscription supprimée avec succès.', [
                    'hashtoken' => $this->session->getToken()
                ]);
            }
        }
        $this->jsonResponse(false, 'Impossible de supprimer cette inscription.');
    }
}