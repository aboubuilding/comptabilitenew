<?php

namespace App\Domain\Administration\Repositories;

use App\Domain\Administration\Models\ModeleMessage;
use App\Repositories\BaseRepository;

class ModeleMessageRepository extends BaseRepository
{
    public function __construct(ModeleMessage $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filtres = [], int $perPage = 15)
    {
        $query = $this->activeQuery();

        if (!empty($filtres['recherche'])) {
            $r = $filtres['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('libelle', 'like', "%{$r}%")
                  ->orWhere('code', 'like', "%{$r}%")
                  ->orWhere('sujet', 'like', "%{$r}%");
            });
        }

        return $query->orderBy('libelle')->paginate($perPage);
    }
}