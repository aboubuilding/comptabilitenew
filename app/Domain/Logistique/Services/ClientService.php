<?php

namespace App\Domain\Logistique\Services;

use App\Domain\Logistique\DTO\ClientData;
use App\Domain\Logistique\Models\Client;
use App\Domain\Logistique\Repositories\ClientRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClientService
{
    public function __construct(
        private ClientRepository $clients,
    ) {}

    public function creer(ClientData $data): Client
    {
        if (empty($data->nomRaisonSociale)) {
            throw new RuntimeException("Le nom ou la raison sociale est obligatoire.");
        }

        return DB::transaction(fn () => $this->clients->create([
            'nom_raison_sociale' => $data->nomRaisonSociale,
            'type_client'        => $data->typeClient,
            'telephone'          => $data->telephone,
            'email'              => $data->email,
            'adresse'            => $data->adresse,
            'nif'                => $data->nif,
            'rccm'               => $data->rccm,
        ]));
    }

    public function modifier(int $id, ClientData $data): Client
    {
        $client = $this->clients->findOrFail($id);

        $this->clients->update($id, [
            'nom_raison_sociale' => $data->nomRaisonSociale,
            'type_client'        => $data->typeClient,
            'telephone'          => $data->telephone,
            'email'              => $data->email,
            'adresse'            => $data->adresse,
            'nif'                => $data->nif,
            'rccm'               => $data->rccm,
        ]);

        return $client->refresh();
    }

    public function supprimer(int $id): void
    {
        $client = $this->clients->findOrFail($id);

        if ($client->ventes()->where('etat', 1)->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce client : des ventes y sont rattachées."
            );
        }

        $this->clients->delete($id);
    }
}