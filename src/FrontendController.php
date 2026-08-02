<?php

declare(strict_types=1);

namespace Plugins\EventRegistration\src;

use App\Frontend\Controller\BaseController;
use Plugins\EventRegistration\db\EventFrontDb;
use Magepattern\Component\Tool\FormTool;
use Magepattern\Component\Tool\MailTool;
use Magepattern\Component\Tool\SmartyTool;
use Magepattern\Component\Tool\StringTool;
use Magepattern\Component\HTTP\Request;
use App\Component\Routing\UrlTool;

class FrontendController extends BaseController
{
    // =================================================================
    // 1. LE MOTEUR DE HOOKS (Injection dans le template single.tpl)
    // =================================================================
    public static function renderForm(array $params = []): string
    {
        $idNews = (int)($params['id_news'] ?? 0);
        if ($idNews <= 0) return '';

        try {
            $view = SmartyTool::getInstance('front');
            $db = new EventFrontDb();

            $config = $db->getEventConfig($idNews);

            if (empty($config) || (int)$config['registration_enabled'] !== 1) {
                return ''; // Les inscriptions sont désactivées
            }

            $maxParticipants = (int)$config['max_participants'];
            $currentRegistrations = $db->countRegistrations($idNews);

            $isFull = ($maxParticipants > 0 && $currentRegistrations >= $maxParticipants);

            $template = ROOT_DIR . 'plugins' . DS . 'EventRegistration' . DS . 'views' . DS . 'front' . DS . 'form.tpl';

            if (!file_exists($template)) return '';

            return $view->fetch($template, [
                'event_id_news' => $idNews,
                'event_is_full' => $isFull,
                'event_places'  => ($maxParticipants > 0) ? ($maxParticipants - $currentRegistrations) : 'illimité',
                'event_token'   => $view->getTemplateVars('hashtoken')
            ]);
        } catch (\Throwable $e) {
            return '';
        }
    }

    // =================================================================
    // 2. LE CONTRÔLEUR CLASSIQUE (Traitement de l'AJAX MagixFrontForms)
    // =================================================================
    public function run(): void
    {
        // On déclare le dossier de vues pour que MailTool trouve le message.tpl du plugin
        SmartyTool::addTemplateDir('front', ROOT_DIR . 'plugins' . DS . 'EventRegistration' . DS . 'views' . DS . 'front');

        $action = $_GET['action'] ?? '';

        if ($action === 'registerFrontend' && Request::isMethod('POST')) {
            $this->processRegistration();
            return;
        }

        $this->render404();
    }

    private function processRegistration(): void
    {
        // Nettoyage de la mémoire tampon pour éviter que du HTML ne pollue le JSON
        if (ob_get_length()) ob_clean();

        // 1. Nettoyage et récupération des données POST
        $firstname = FormTool::simpleClean($_POST['firstname'] ?? '');
        $lastname  = FormTool::simpleClean($_POST['lastname'] ?? '');
        $email     = FormTool::simpleClean($_POST['email'] ?? '');
        $phone     = FormTool::simpleClean($_POST['phone'] ?? '');
        $idNews    = (int)($_POST['id_news'] ?? 0);

        // 2. Vérification des champs obligatoires
        if (empty($firstname) || empty($lastname) || empty($email)) {
            $this->jsonResponse(false, 'Veuillez remplir tous les champs obligatoires.');
        }

        if (!StringTool::isMail($email)) {
            $this->jsonResponse(false, 'L\'adresse e-mail fournie est invalide.');
        }

        // 3. Intégration stricte du Google reCAPTCHA
        $isHuman = true;
        if (class_exists('\Plugins\GoogleRecaptcha\src\FrontendController')) {
            $recaptcha = new \Plugins\GoogleRecaptcha\src\FrontendController();
            $isHuman = $recaptcha->verify('event_registration');
        }

        if (!$isHuman) {
            $this->jsonResponse(false, 'Erreur de sécurité : Validation reCAPTCHA échouée. Veuillez réessayer.');
        }

        if ($idNews <= 0) {
            $this->jsonResponse(false, 'Erreur technique : Évènement introuvable.');
        }

        // 4. Vérification de la disponibilité (Sécurité côté serveur)
        $db = new EventFrontDb();
        $config = $db->getEventConfig($idNews);

        if (empty($config) || (int)$config['registration_enabled'] !== 1) {
            $this->jsonResponse(false, 'Les inscriptions sont fermées pour cet évènement.');
        }

        if ((int)$config['max_participants'] > 0) {
            $currentCount = $db->countRegistrations($idNews);
            if ($currentCount >= (int)$config['max_participants']) {
                $this->jsonResponse(false, 'Désolé, cet évènement est désormais complet.');
            }
        }

        // 5. Enregistrement en Base de données
        $insertData = [
            'id_news'   => $idNews,
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'email'     => $email,
            'phone'     => $phone
        ];

        if (!$db->insertRegistration($insertData)) {
            $this->jsonResponse(false, 'Une erreur technique est survenue lors de l\'enregistrement.');
        }

        // CONSTRUCTION DE L'URL ABSOLUE (CORRIGÉE)
        $idLang  = (int)($this->currentLang['id_lang'] ?? 1);
        $isoLang = strtolower($this->currentLang['iso_lang'] ?? 'fr');
        $newsInfo = $db->getNewsInfo($idNews, $idLang);

        $urlTool = new UrlTool();

        // On récupère la date de publication (ou la date de l'évènement à défaut)
        $dateNews = !empty($newsInfo['date_publish']) ? $newsInfo['date_publish'] : ($newsInfo['date_event_start'] ?? '');

        // Utilisation native du UrlTool de Magix CMS pour les news
        $relativeUrl = $urlTool->buildUrl([
            'type' => 'news',
            'id'   => $idNews,
            'url'  => $newsInfo['url_news'] ?? '',
            'date' => $dateNews,
            'iso'  => $isoLang
        ]);

        // On récupère l'URL de base du site (ex: https://magixcms.test)
        $siteUrl = rtrim((string)$this->view->getTemplateVars('site_url'), '/');

        // Assemblage final : https://magixcms.test/fr/news/2026-04-20/9-test/
        $absoluteUrl = $siteUrl . $relativeUrl;

        $emailData = array_merge($insertData, [
            'news_name' => $newsInfo['name_news'] ?? 'Évènement #' . $idNews,
            'news_url'  => $absoluteUrl
        ]);

        $this->sendNotificationEmails($emailData);

        // 7. Calcul des places restantes après cette inscription
        $remaining = 'illimité';
        if ((int)$config['max_participants'] > 0) {
            $newCount = $db->countRegistrations($idNews);
            $remaining = max(0, (int)$config['max_participants'] - $newCount);
        }

        // 8. Succès ! Retour au Javascript avec le compteur à jour
        $this->jsonResponse(true, 'Votre inscription a bien été confirmée !', [
            'type'      => 'success',
            'remaining' => $remaining
        ]);
    }

    /**
     * Gère l'envoi des e-mails en utilisant le MailTool de Magix CMS
     */
    private function sendNotificationEmails(array $data): void
    {
        $isSmtp = isset($this->siteSettings['smtp_enabled']['value']) && $this->siteSettings['smtp_enabled']['value'] == '1';
        $type = $isSmtp ? 'smtp' : 'mail';

        $options = [
            'setHost'       => $this->siteSettings['set_host']['value'] ?? '',
            'setPort'       => (int)($this->siteSettings['set_port']['value'] ?? 25),
            'setEncryption' => $this->siteSettings['set_encryption']['value'] ?? '',
            'setUsername'   => $this->siteSettings['set_username']['value'] ?? '',
            'setPassword'   => $this->siteSettings['set_password']['value'] ?? '',
        ];

        $mailer = new MailTool($type, $options);
        $siteEmail = $this->siteSettings['mail_sender']['value'] ?? '';

        if (empty($siteEmail)) return;

        // --- MAIL 1 : Notification à l'Administrateur (Mode Tableau) ---
        $msgAdmin = [
            'title'   => 'Nouvelle inscription !',
            'intro'   => 'Une nouvelle personne vient de s\'inscrire à un évènement depuis le site web.',

            'subject' => 'Nouvelle inscription : ' . $data['news_name'],

            'details' => [
                'Évènement'          => $data['news_name'],

                'Lien vers la page'  => !empty($data['news_url']) ? $data['news_url'] : 'Non disponible',

                'Prénom'             => $data['firstname'],
                'Nom'                => $data['lastname'],
                'E-mail'             => $data['email'],
                'Téléphone'          => !empty($data['phone']) ? $data['phone'] : 'Non renseigné'
            ]
        ];

        $mailer->sendTemplate(
            'front',
            'emails/message.tpl',
            $msgAdmin,
            "Nouvelle inscription à l'évènement",
            $data['email'],
            [$siteEmail => 'Administration']
        );

        // --- MAIL 2 : Confirmation au Visiteur (Mode Texte) ---
        $msgUser = [
            'title'   => 'Confirmation d\'inscription',
            'subject' => 'Votre participation est confirmée',
            'content' => nl2br("Bonjour {$data['firstname']},\n\nNous vous confirmons que votre inscription à l'évènement a bien été prise en compte.\n\nMerci de votre confiance et à très vite !")
        ];

        $mailer->sendTemplate(
            'front',
            'emails/message.tpl',
            $msgUser,
            "Confirmation de votre inscription",
            $siteEmail,
            [$data['email'] => $data['firstname'] . ' ' . $data['lastname']]
        );
    }
}