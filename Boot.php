<?php
declare(strict_types=1);

namespace Plugins\EventRegistration;

use App\Component\Hook\HookManager;
use Magepattern\Component\Tool\SmartyTool;
use Plugins\EventRegistration\db\EventAdminDb;
use Plugins\EventRegistration\db\EventFrontDb;

class Boot
{
    public function register(): void
    {
        $module = 'news';
        $idKey = 'id_news';

        // --- BACKEND : Injection de l'onglet dans l'administration des News ---
        HookManager::register("{$module}_edit_tab", 'EventRegistration', function(array $params) {
            $smarty = SmartyTool::getInstance('admin');
            $file = ROOT_DIR . 'plugins' . DS . 'EventRegistration' . DS . 'views' . DS . 'admin' . DS . 'hooks' . DS . 'tab_button.tpl';
            return $smarty->templateExists($file) ? $smarty->fetch($file) : '';
        });

        HookManager::register("{$module}_edit_content", 'EventRegistration', function(array $params) use ($idKey) {
            $smarty = SmartyTool::getInstance('admin');
            $idNews = (int)($params[$idKey] ?? 0);

            if ($idNews <= 0) return '';

            $db = new EventAdminDb();
            $smarty->assign([
                'id_news'      => $idNews,
                'event_config' => $db->getEventConfig($idNews),
                'hashtoken'    => $smarty->getTemplateVars('hashtoken')
            ]);

            $file = ROOT_DIR . 'plugins' . DS . 'EventRegistration' . DS . 'views' . DS . 'admin' . DS . 'hooks' . DS . 'tab_content.tpl';
            return $smarty->templateExists($file) ? $smarty->fetch($file) : '';
        });

        // --- FRONTEND : Injection du formulaire sur le site public ---
        HookManager::register('displayNewsBottom', 'EventRegistration', ['\Plugins\EventRegistration\src\FrontendController', 'renderForm']);

        // --- OPTIMISATION : Contrôle conditionnel du Google reCAPTCHA ---
        if (class_exists('\Plugins\GoogleRecaptcha\src\FrontendController')) {

            \Plugins\GoogleRecaptcha\src\FrontendController::addInjectCondition(function(string $currentModule) use ($module) {
                // On ne gère le veto que si reCAPTCHA s'apprête à charger sur notre module concerné
                if ($currentModule !== $module) {
                    return true;
                }

                // Récupération de l'ID courant passé dans l'URL par le routeur
                $idItem = (int)($_GET['id'] ?? 0);

                // CORRECTION : Si pas d'ID, on est sur la liste des news (index).
                // Il n'y a pas de formulaire d'inscription ici, donc on BLOQUE reCAPTCHA.
                if ($idItem <= 0) {
                    return false;
                }

                // On vérifie en base si cet événement précis nécessite le formulaire
                $db = new EventFrontDb();
                $config = $db->getEventConfig($idItem);

                // VETO : Si l'inscription est désactivée (ou inexistante), on bloque l'injection du script
                if (empty($config) || (int)$config['registration_enabled'] !== 1) {
                    return false;
                }

                // Tout est ok, l'ID existe, le formulaire va s'afficher, on autorise reCAPTCHA
                return true;
            });
        }
    }
}