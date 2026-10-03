<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\DTO\ClientData;
use App\Domain\Logistique\Repositories\ClientRepository;
use App\Domain\Logistique\Services\ClientService;
use App\Domain\Logistique\Types\TypeClient;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\StoreClientRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ClientRepository $clients,
        private ClientService    $service,
    ) {
        $this->middleware('permission:ventes.voir')->only(['index', 'export']);
        $this->middleware('permission:ventes.gerer')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:ventes.voir')->only(['rechercher']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        return view('domain.logistique.clients.index', [
            'clients' => $this->clients->listePaginee($filters),
            'filters' => $filters,
            'types'   => TypeClient::options(),
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        return $this->runAjax(
            fn () => ['client' => $this->service->creer(ClientData::fromRequest($request->validated()))],
            'Client créé.'
        );
    }

    public function update(StoreClientRequest $request, int $client)
    {
        return $this->runAjax(
            fn () => ['client' => $this->service->modifier($client, ClientData::fromRequest($request->validated()))],
            'Client mis à jour.'
        );
    }

    public function destroy(int $client)
    {
        return $this->runAjax(function () use ($client) {
            $this->service->supprimer($client);
            return null;
        }, 'Client désactivé.');
    }

    /** Recherche AJAX pour le point de vente. */
    public function rechercher(Request $request)
    {
        $request->validate(['q' => ['required', 'string', 'min:2']]);

        return response()->json([
            'clients' => $this->clients->rechercher($request->input('q')),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search']);
        $clients = $this->clients->listePaginee($filters, perPage: 10000);

        $filename = 'clients_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($clients) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Raison sociale', 'Type', 'Téléphone', 'Email', 'Adresse'], ';');

            foreach ($clients as $c) {
                fputcsv($out, [
                    $c->nom_raison_sociale,
                    $c->type_client?->label(),
                    $c->telephone,
                    $c->email,
                    $c->adresse,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}