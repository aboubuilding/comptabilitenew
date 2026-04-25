-- requetes pour gerer les liste de la cantine et du bus

select i.id,  e.nom , e.prenom , n.libelle as niveau_eleve, c.libelle as cycle, d.frais_ecole_id, SUM(d.montant)

from details d

join inscriptions i on i.id = d.inscription_id
join eleves e on e.id = i.eleve_id
join niveaux n on n.id = i.niveau_id
join cycles c on c.id = i.cycle_id

where d.etat = 1
and d.type_paiement = 7
and d.statut_paiement = 2

group by i.id,  e.nom , e.prenom,  n.libelle, c.libelle



;
----- requete pour supprimer la colonne photo de la table voiture
ALTER TABLE voitures
DROP COLUMN photo;

----- requete pour supprimer la colonne couleur  de la table voiture
ALTER TABLE voitures
DROP COLUMN couleur;


----- requete pour supprimer la colonne categorie_permis  de la table chauffeurs
ALTER TABLE chauffeurs
DROP COLUMN categorie_permis;


----- requete pour supprimer la colonne categorie_permis  de la table chauffeurs
ALTER TABLE chauffeurs
DROP COLUMN voiture_id;



----- requete pour supprimer la colonne voiture_id  de la table lignes
ALTER TABLE lignes
DROP COLUMN voiture_id;


----- requete pour supprimer la colonne chauffeur_id  de la table lignes
ALTER TABLE lignes
DROP COLUMN chauffeur_id;

----------- requete pour obtenir la liste des eleves n ayant pas encore payé
select e.nom, i.id,  e.nom , e.prenom , n.libelle as niveau_eleve, p.email

from inscriptions i

join eleves e on e.id = i.eleve_id
join niveaux n on n.id = i.niveau_id
left join parent_eleves p on p.id = i.parent_id


where i.etat = 1
and i.statut_validation = 2
and i.id not in (select distinct d.inscription_id

from details d  )

----------- requete pour ajouter la  colonne  frais examen 
ALTER TABLE `inscriptions` ADD `frais_examen` FLOAT NULL AFTER `frais_livre`;
