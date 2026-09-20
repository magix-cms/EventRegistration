<?php

declare(strict_types=1);

namespace Plugins\Eventregistration\db;

use App\Backend\Db\BaseDb;
use Magepattern\Component\Database\QueryBuilder;

class EventAdminDb extends BaseDb
{
    /**
     * Récupère la configuration (max participants) d'un évènement
     */
    public function getEventConfig(int $idNews): array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from('mc_news_event')
            ->where('id_news = :id', ['id' => $idNews]);

        $res = $this->executeRow($qb);
        // Si aucune config n'existe, on renvoie une structure par défaut
        return $res ?: ['id_news' => $idNews, 'max_participants' => 0];
    }

    /**
     * Sauvegarde la configuration (INSERT ou UPDATE)
     */
    /**
     * Sauvegarde la configuration (INSERT ou UPDATE)
     */
    public function saveEventConfig(int $idNews, int $maxParticipants, int $registrationEnabled): bool
    {
        $qbCheck = new QueryBuilder();
        $qbCheck->select(['id_news'])->from('mc_news_event')->where('id_news = :id', ['id' => $idNews]);

        $exists = $this->executeRow($qbCheck);
        $qb = new QueryBuilder();

        if ($exists) {
            $qb->update('mc_news_event', [
                'max_participants'     => $maxParticipants,
                'registration_enabled' => $registrationEnabled //  Ajout
            ])
                ->where('id_news = :id', ['id' => $idNews]);
            return $this->executeUpdate($qb);
        } else {
            $qb->insert('mc_news_event', [
                'id_news'              => $idNews,
                'max_participants'     => $maxParticipants,
                'registration_enabled' => $registrationEnabled //  Ajout
            ]);
            return $this->executeInsert($qb);
        }
    }

    /**
     * Récupère la liste des inscrits pour une actualité
     */
    public function getRegistrations(int $idNews): array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from('mc_news_registration')
            ->where('id_news = :id', ['id' => $idNews])
            ->orderBy('date_register', 'DESC');

        return $this->executeAll($qb) ?: [];
    }

    /**
     * Supprime une inscription (côté admin)
     */
    public function deleteRegistration(int $idRegistration): bool
    {
        $qb = new QueryBuilder();
        $qb->delete('mc_news_registration')->where('id_registration = :id', ['id' => $idRegistration]);
        return $this->executeDelete($qb);
    }
}