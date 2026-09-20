<?php

declare(strict_types=1);

namespace Plugins\Eventregistration\src;

use App\Frontend\Controller\BaseController;
use Plugins\Eventregistration\db\EventFrontDb;
use Magepattern\Component\Tool\FormTool;
use Magepattern\Component\Tool\MailTool;
use Magepattern\Component\Tool\SmartyTool;
use Magepattern\Component\Tool\StringTool;
use Magepattern\Component\HTTP\Request;
use App\Component\Routing\UrlTool;

class FrontendController extends BaseController
{
    public static function renderForm(array $params = []): string
    {
        $idNews = (int)($params['id_news'] ?? 0);
        if ($idNews <= 0) return '';

        try {
            $view = SmartyTool::getInstance('front');
            $db = new EventFrontDb();

            $config = $db->getEventConfig($idNews);

            if (empty($config) || (int)$config['registration_enabled'] !== 1) {
                return '';
            }

            $maxParticipants = (int)$config['max_participants'];
            $currentRegistrations = $db->countRegistrations($idNews);

            $isFull = ($maxParticipants > 0 && $currentRegistrations >= $maxParticipants);

            $template = ROOT_DIR . 'plugins' . DS . 'Eventregistration' . DS . 'views' . DS . 'front' . DS . 'form.tpl';

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

    public function run(): void
    {
        SmartyTool::addTemplateDir('front', ROOT_DIR . 'plugins' . DS . 'Eventregistration' . DS . 'views' . DS . 'front');

        $confFile = ROOT_DIR . 'plugins' . DS . 'Eventregistration' . DS . 'i18n' . DS . 'fr.conf';
        if (file_exists($confFile)) {
            $this->view->configLoad($confFile);
        }

        // On passe l'action en minuscules pour éviter les erreurs de frappe/casse
        $action = strtolower($_GET['action'] ?? '');

        // On compare avec la chaîne tout en minuscules
        if ($action === 'registerfrontend' && Request::isMethod('POST')) {
            $this->processRegistration();
            return;
        }

        // Si l'action est mauvaise, on renvoie une erreur JSON propre plutôt qu'une page 404 HTML
        $this->jsonResponse(false, 'Erreur de routage : Action non reconnue.');
    }

    /**
     * Méthode sécurisée pour récupérer une traduction avec un fallback obligatoire.
     */
    private function getTrans(string $key, string $default): string
    {
        $view = SmartyTool::getInstance('front');
        $confFile = ROOT_DIR . 'plugins' . DS . 'Eventregistration' . DS . 'i18n' . DS . 'fr.conf';

        if (file_exists($confFile)) {
            $view->configLoad($confFile);
        }

        $val = $view->getConfigVars($key);
        return !empty($val) ? (string)$val : $default;
    }

    private function processRegistration(): void
    {
        if (ob_get_length()) ob_clean();

        $firstname = FormTool::simpleClean($_POST['firstname'] ?? '');
        $lastname  = FormTool::simpleClean($_POST['lastname'] ?? '');
        $email     = FormTool::simpleClean($_POST['email'] ?? '');
        $phone     = FormTool::simpleClean($_POST['phone'] ?? '');
        $idNews    = (int)($_POST['id_news'] ?? 0);

        if (empty($firstname) || empty($lastname) || empty($email)) {
            $this->jsonResponse(false, $this->getTrans('event_error_empty_fields', 'Veuillez remplir tous les champs obligatoires.'));
        }

        if (!StringTool::isMail($email)) {
            $this->jsonResponse(false, $this->getTrans('event_error_invalid_email', 'Adresse e-mail invalide.'));
        }

        $isHuman = true;
        if (class_exists('\Plugins\GoogleRecaptcha\src\FrontendController')) {
            $recaptcha = new \Plugins\GoogleRecaptcha\src\FrontendController();
            $isHuman = $recaptcha->verify('event_registration');
        }

        if (!$isHuman) {
            $this->jsonResponse(false, $this->getTrans('event_error_recaptcha_failed', 'Erreur de sécurité : reCAPTCHA échoué.'));
        }

        if ($idNews <= 0) {
            $this->jsonResponse(false, $this->getTrans('event_error_not_found', 'Évènement introuvable.'));
        }

        $db = new EventFrontDb();
        $config = $db->getEventConfig($idNews);

        if (empty($config) || (int)$config['registration_enabled'] !== 1) {
            $this->jsonResponse(false, $this->getTrans('event_error_closed', 'Inscriptions fermées.'));
        }

        if ((int)$config['max_participants'] > 0) {
            $currentCount = $db->countRegistrations($idNews);
            if ($currentCount >= (int)$config['max_participants']) {
                $this->jsonResponse(false, $this->getTrans('event_error_full', 'Cet évènement est complet.'));
            }
        }

        if ($db->hasAlreadyRegistered($idNews, $email)) {
            $this->jsonResponse(false, $this->getTrans('event_error_already_registered', 'Cette adresse e-mail est déjà inscrite.'));
        }

        $insertData = [
            'id_news'   => $idNews,
            'firstname' => $firstname,
            'lastname'  => $lastname,
            'email'     => $email,
            'phone'     => $phone
        ];

        if (!$db->insertRegistration($insertData)) {
            $this->jsonResponse(false, $this->getTrans('event_error_technical', 'Erreur technique lors de l\'enregistrement.'));
        }

        $idLang  = (int)($this->currentLang['id_lang'] ?? 1);
        $isoLang = strtolower($this->currentLang['iso_lang'] ?? 'fr');
        $newsInfo = $db->getNewsInfo($idNews, $idLang);

        $urlTool = new UrlTool();
        $dateNews = !empty($newsInfo['date_publish']) ? $newsInfo['date_publish'] : ($newsInfo['date_event_start'] ?? '');

        $relativeUrl = $urlTool->buildUrl([
            'type' => 'news',
            'id'   => $idNews,
            'url'  => $newsInfo['url_news'] ?? '',
            'date' => $dateNews,
            'iso'  => $isoLang
        ]);

        $siteUrl = rtrim((string)$this->view->getTemplateVars('site_url'), '/');
        $absoluteUrl = $siteUrl . $relativeUrl;

        $emailData = array_merge($insertData, [
            'news_name' => $newsInfo['name_news'] ?? 'Évènement #' . $idNews,
            'news_url'  => $absoluteUrl
        ]);

        $this->sendNotificationEmails($emailData);

        $remaining = 'illimité';
        if ((int)$config['max_participants'] > 0) {
            $newCount = $db->countRegistrations($idNews);
            $remaining = max(0, (int)$config['max_participants'] - $newCount);
        }

        $this->jsonResponse(true, $this->getTrans('event_success_registered', 'Votre inscription a bien été confirmée !'), [
            'type'      => 'success',
            'remaining' => $remaining
        ]);
    }

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

        // Configuration pour ne pas être bloqué par le SMTP (DMARC/SPF)
        $senderEmail = $siteEmail;

        // --- MAIL 1 : Notification à l'Administrateur ---
        $fromHeaderAdmin = '"' . $data['firstname'] . ' ' . $data['lastname'] . '" <' . $senderEmail . '>';

        $msgAdmin = [
            'title'   => $this->getTrans('event_email_admin_title', 'Nouvelle inscription !'),
            'intro'   => $this->getTrans('event_email_admin_intro', 'Une nouvelle personne vient de s\'inscrire.'),
            'subject' => $this->getTrans('event_email_admin_subject', 'Nouvelle inscription') . ' : ' . $data['news_name'],
            'details' => [
                $this->getTrans('event_email_label_event', 'Évènement') => $data['news_name'],
                $this->getTrans('event_email_label_link', 'Lien')       => !empty($data['news_url']) ? $data['news_url'] : 'N/A',
                $this->getTrans('event_email_label_fname', 'Prénom')    => $data['firstname'],
                $this->getTrans('event_email_label_lname', 'Nom')       => $data['lastname'],
                $this->getTrans('event_email_label_email', 'Email')     => $data['email'],
                $this->getTrans('event_email_label_phone', 'Téléphone') => !empty($data['phone']) ? $data['phone'] : 'N/A'
            ]
        ];

        $mailer->sendTemplate(
            'front',
            'emails/message.tpl',
            $msgAdmin,
            $msgAdmin['subject'],
            $fromHeaderAdmin,
            [$siteEmail => 'Administration'],
            [],
            $data['email'] // On met l'email du visiteur en Reply-To pour lui répondre facilement
        );

        // --- MAIL 2 : Confirmation au Visiteur ---
        $contentTpl = $this->getTrans('event_email_user_content', 'Bonjour %s, <br><br>Votre inscription est confirmée.');
        // On évite un crash si le fichier de langue a oublié le %s
        $content = str_contains($contentTpl, '%s') ? sprintf($contentTpl, $data['firstname']) : $contentTpl;

        $msgUser = [
            'title'   => $this->getTrans('event_email_user_title', 'Confirmation d\'inscription'),
            'subject' => $this->getTrans('event_email_user_subject', 'Votre participation est confirmée'),
            'content' => nl2br($content)
        ];

        $fromHeaderUser = '"Service Web" <' . $senderEmail . '>';

        $mailer->sendTemplate(
            'front',
            'emails/message.tpl',
            $msgUser,
            $msgUser['subject'],
            $fromHeaderUser,
            [$data['email'] => $data['firstname'] . ' ' . $data['lastname']]
        );
    }
}