<?php
declare(strict_types=1);

namespace Plugins\EventRegistration\db;

use App\Frontend\Db\BaseDb;
use Magepattern\Component\Database\QueryBuilder;

class EventFrontDb extends BaseDb
{
    /**
     * Récupère la configuration de l'évènement pour le Frontend
     */
    public function getEventConfig(int $idNews): array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from('mc_news_event')
            ->where('id_news = :id', ['id' => $idNews]);

        $res = $this->executeRow($qb);

        return $res ?: [];
    }

    /**
     * Compte le nombre d'inscrits actuels pour vérifier le quota
     */
    public function countRegistrations(int $idNews): int
    {
        $qb = new QueryBuilder();
        $qb->select(['COUNT(id_registration) as total'])
            ->from('mc_news_registration')
            ->where('id_news = :id', ['id' => $idNews]);

        $res = $this->executeRow($qb);
        return $res ? (int)$res['total'] : 0;
    }

    /**
     * Insère une nouvelle inscription depuis le formulaire public
     * (Nous l'utiliserons dans la prochaine étape)
     */
    public function insertRegistration(array $data): bool
    {
        $qb = new QueryBuilder();
        $qb->insert('mc_news_registration', $data);
        return $this->executeInsert($qb);
    }
    /**
     * Récupère le nom et l'URL publique de l'actualité pour les e-mails
     */
    /**
     * Récupère le nom, le slug et la date de l'actualité pour générer l'URL
     */
    public function getNewsInfo(int $idNews, int $idLang): array
    {
        $qb = new QueryBuilder();
        $qb->select(['c.name_news', 'c.url_news', 'n.date_publish', 'n.date_event_start'])
            ->from('mc_news_content', 'c')
            ->join('mc_news', 'n', 'c.id_news = n.id_news')
            ->where('c.id_news = :id AND c.id_lang = :lang', ['id' => $idNews, 'lang' => $idLang]);

        $res = $this->executeRow($qb);
        return $res ?: [];
    }
}