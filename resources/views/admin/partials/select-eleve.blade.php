{{--
    Sélecteur d'élève commun aux fenêtres de saisie (abonnement bus, cantine,
    activités, événements, prêts...). L'utilisateur choisit un élève par son
    nom ; le formulaire envoie l'identifiant attendu par le serveur.

    Paramètres :
      name      nom du champ (défaut : inscription_id)
      valeur    'inscription' (défaut) ou 'eleve' : identifiant envoyé
      required  champ obligatoire (défaut : true)
      id        attribut id (facultatif)

    La liste est celle des élèves inscrits sur l'année de travail (dossiers
    non rejetés, non abandonnés). La recherche est activée par select2
    (voir layouts/app.blade.php, classe .select-eleve).
--}}
@php
    $champ       = $name ?? 'inscription_id';
    $parEleve    = ($valeur ?? 'inscription') === 'eleve';
    $obligatoire = $required ?? true;
    $inscrits    = app(\App\Domain\Scolarite\Repositories\InscriptionRepository::class)
        ->listePourSelect((int) app(\App\Support\AnneeScolaireContext::class)->id());
@endphp
<select name="{{ $champ }}" @isset($id) id="{{ $id }}" @endisset
        class="form-select select-eleve" style="width:100%;" @if ($obligatoire) required @endif>
    <option value=""></option>
    @foreach ($inscrits as $inscrit)
        <option value="{{ $parEleve ? $inscrit->eleve_id : $inscrit->id }}">
            {{ $inscrit->eleve->nom }} {{ $inscrit->eleve->prenom }}
            @if ($inscrit->eleve->matricule) ({{ $inscrit->eleve->matricule }}) @endif
            — {{ $inscrit->classe?->libelle ?? $inscrit->niveau?->libelle ?? 'sans classe' }}
        </option>
    @endforeach
</select>
