<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Service;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository extends BaseRepository
{
    public function __construct(Service $model)
    {
        parent::__construct($model);
    }

    public function rechercher(string $terme, int $limite = 10): Collection
    {
        return $this->activeQuery()->where('libelle', 'like', "%{$terme}%")->limit($limite)->get();
    }
}