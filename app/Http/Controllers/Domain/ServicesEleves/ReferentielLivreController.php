<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Models\Auteur;
use App\Domain\ServicesEleves\Models\AnneEdition;
use App\Domain\ServicesEleves\Models\CategorieLivre;
use App\Domain\ServicesEleves\Models\MaisonEdition;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReferentielLivreController extends Controller
{
    use HandlesControllerErrors;

    public function __construct()
    {
        $this->middleware('permission:bibliotheque.voir')->only(['index']);
        $this->middleware('permission:bibliotheque.gerer')->only(['store', 'update', 'destroy']);
    }

    /** Écran unique avec 4 onglets (auteurs, maisons, catégories, années). */
    public function index()
    {
        return view('domain.services-eleves.bibliotheque.referentiels.index', [
            'auteurs'    => Auteur::orderBy('nom_prenom')->get(),
            'maisons'    => MaisonEdition::orderBy('libelle')->get(),
            'categories' => CategorieLivre::orderBy('libelle')->get(),
            'annees'     => AnneEdition::orderBy('libelle')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'    => ['required', 'in:auteur,maison,categorie,annee'],
            'libelle' => ['required', 'string', 'max:200'],
        ]);

        $model = match ($data['type']) {
            'auteur'    => Auteur::class,
            'maison'    => MaisonEdition::class,
            'categorie' => CategorieLivre::class,
            'annee'     => AnneEdition::class,
        };

        $field = $data['type'] === 'auteur' ? 'nom_prenom' : 'libelle';

        $item = $model::create([$field => $data['libelle']]);

        return $this->runAjax(
            fn () => ['item' => $item, 'type' => $data['type']],
            'Élément ajouté.'
        );
    }

    public function update(Request $request, string $type, int $id)
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:200'],
        ]);

        $model = match ($type) {
            'auteur'    => Auteur::class,
            'maison'    => MaisonEdition::class,
            'categorie' => CategorieLivre::class,
            'annee'     => AnneEdition::class,
            default     => abort(404),
        };

        $field = $type === 'auteur' ? 'nom_prenom' : 'libelle';

        $item = $model::findOrFail($id);
        $item->update([$field => $data['libelle']]);

        return $this->runAjax(fn () => null, 'Élément mis à jour.');
    }

    public function destroy(string $type, int $id)
    {
        $model = match ($type) {
            'auteur'    => Auteur::class,
            'maison'    => MaisonEdition::class,
            'categorie' => CategorieLivre::class,
            'annee'     => AnneEdition::class,
            default     => abort(404),
        };

        $model::findOrFail($id)->update(['etat' => \App\Repositories\BaseRepository::INACTIF]);

        return $this->runAjax(fn () => null, 'Élément désactivé.');
    }
}