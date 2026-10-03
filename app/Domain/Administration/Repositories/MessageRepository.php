<?php

namespace App\Domain\Administration\Repositories;

use App\Domain\Administration\Models\Message;
use App\Repositories\BaseRepository;

class MessageRepository extends BaseRepository
{
    public function __construct(Message $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filtres = [], int $perPage = 15)
    {
        $query = $this->activeQuery()
            ->with(['modele', 'expediteur'])
            ->withCount('destinataires');

        if (!empty($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }
        if (!empty($filtres['type'])) {
            $query->where('type', $filtres['type']);
        }
        if (!empty($filtres['recherche'])) {
            $r = $filtres['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('sujet', 'like', "%{$r}%")
                  ->orWhere('corps', 'like', "%{$r}%");
            });
        }
        if (!empty($filtres['date_debut'])) {
            $query->whereDate('created_at', '>=', $filtres['date_debut']);
        }
        if (!empty($filtres['date_fin'])) {
            $query->whereDate('created_at', '<=', $filtres['date_fin']);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function listePourExport(array $filtres = [])
    {
        $query = $this->activeQuery()
            ->with(['modele', 'expediteur'])
            ->withCount('destinataires');

        if (!empty($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }
        return $query->latest('created_at')->get();
    }
}