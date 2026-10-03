<?php

namespace Tests\Domain\Administration;

use App\Domain\Administration\DTO\MessageDTO;
use App\Domain\Administration\Models\Message;
use App\Domain\Administration\Models\MessageDestinataire;
use App\Domain\Administration\Services\DestinataireResolverService;
use App\Domain\Administration\Services\MessageService;
use App\Domain\Administration\Services\ModeleMessageService;
use App\Domain\Administration\Types\StatutEnvoi;
use App\Domain\Administration\Types\StatutMessage;
use App\Jobs\SendMessageJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 6, § 6.3 Communications
 * et règle transversale « Traçabilité des communications ».
 */
class CommunicationsTest extends DomainTestCase
{
    private MessageService $messages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messages = app(MessageService::class);
        Auth::setUser($this->creerUtilisateur());
    }

    private function dto(string $sujet = 'Réunion de rentrée'): MessageDTO
    {
        return new MessageDTO($sujet, 'Chers parents, la réunion aura lieu samedi.', 'information');
    }

    /** Crée une famille : un espace, un parent avec email, un élève inscrit dans la classe. */
    private function famille(string $email, int $classeId, int $niveauId, int $nbEleves = 1): int
    {
        $espaceId = DB::table('espaces')->insertGetId(['nom_famille' => 'Famille ' . $email, 'annee_id' => $this->annee->id, 'etat' => 1]);
        $parentId = DB::table('parent_eleves')->insertGetId([
            'nom_parent' => 'PARENT', 'prenom_parent' => 'Test', 'email' => $email,
            'espace_id' => $espaceId, 'is_principal' => 1, 'etat' => 1,
        ]);

        for ($i = 0; $i < $nbEleves; $i++) {
            $this->creerInscription($this->creerEleve(['espace_id' => $espaceId]), [
                'classe_id' => $classeId, 'niveau_id' => $niveauId,
            ]);
        }

        return $parentId;
    }

    public function test_un_message_est_cree_en_brouillon_avec_ses_destinataires(): void
    {
        $message = $this->messages->creer($this->dto(), [
            'type' => 'libre', 'emails_libres' => ['partenaire@exemple.test', 'mairie@exemple.test'],
        ]);

        $this->assertSame(StatutMessage::BROUILLON, $message->statut);
        $this->assertSame(2, (int) $message->nb_destinataires);
        $this->assertCount(2, $message->destinataires);
        $this->assertSame(Auth::id(), (int) $message->expediteur_id);
    }

    public function test_les_adresses_invalides_sont_ecartees(): void
    {
        $destinataires = app(DestinataireResolverService::class)->resoudre([
            'type' => 'libre', 'emails_libres' => ['valide@exemple.test', 'pas-une-adresse', ''],
        ]);

        $this->assertCount(1, $destinataires);
        $this->assertSame('valide@exemple.test', $destinataires[0]->email);
    }

    public function test_un_message_sans_destinataire_valide_n_est_pas_cree(): void
    {
        try {
            $this->messages->creer($this->dto(), ['type' => 'libre', 'emails_libres' => ['pas-une-adresse']]);
            $this->fail('Un message sans destinataire aurait dû être refusé.');
        } catch (RuntimeException) {
            $this->assertSame(0, Message::count());
        }
    }

    public function test_un_message_a_une_classe_atteint_les_parents_de_ses_eleves(): void
    {
        $cp = $this->creerStructure('CP');
        $ce1 = $this->creerStructure('CE1');
        $this->famille('famille.a@exemple.test', $cp['classe_id'], $cp['niveau_id']);
        $this->famille('famille.b@exemple.test', $cp['classe_id'], $cp['niveau_id']);
        $this->famille('famille.c@exemple.test', $ce1['classe_id'], $ce1['niveau_id']);

        $destinataires = app(DestinataireResolverService::class)->resoudre([
            'type' => 'classe', 'classe_id' => $cp['classe_id'], 'annee_id' => $this->annee->id,
        ]);

        $this->assertEqualsCanonicalizing(
            ['famille.a@exemple.test', 'famille.b@exemple.test'],
            array_map(fn ($d) => $d->email, $destinataires)
        );
    }

    public function test_un_parent_de_plusieurs_eleves_ne_recoit_le_message_qu_une_fois(): void
    {
        $cp = $this->creerStructure('CP');
        $this->famille('fratrie@exemple.test', $cp['classe_id'], $cp['niveau_id'], 2);

        $destinataires = app(DestinataireResolverService::class)->resoudre([
            'type' => 'niveau', 'niveau_id' => $cp['niveau_id'], 'annee_id' => $this->annee->id,
        ]);

        $this->assertCount(1, $destinataires);
    }

    public function test_un_message_peut_viser_des_parents_precis(): void
    {
        $cp = $this->creerStructure('CP');
        $parentA = $this->famille('famille.a@exemple.test', $cp['classe_id'], $cp['niveau_id']);
        $this->famille('famille.b@exemple.test', $cp['classe_id'], $cp['niveau_id']);

        $destinataires = app(DestinataireResolverService::class)->resoudre(['type' => 'parents', 'parent_ids' => [$parentA]]);

        $this->assertCount(1, $destinataires);
        $this->assertSame('famille.a@exemple.test', $destinataires[0]->email);
        $this->assertSame('parent', $destinataires[0]->type);
    }

    public function test_un_message_peut_viser_un_tiers_du_repertoire(): void
    {
        $tiersId = DB::table('contacts_tiers')->insertGetId(['nom' => 'Inspection', 'email' => 'inspection@exemple.test', 'etat' => 1]);

        $destinataires = app(DestinataireResolverService::class)->resoudre(['type' => 'tiers', 'tiers_ids' => [$tiersId]]);

        $this->assertCount(1, $destinataires);
        $this->assertSame('tiers', $destinataires[0]->type);
    }

    public function test_l_envoi_groupe_passe_par_la_file_d_attente(): void
    {
        Queue::fake();
        $message = $this->messages->creer($this->dto(), ['type' => 'libre', 'emails_libres' => ['a@exemple.test']]);

        $this->messages->envoyer($message);

        $this->assertSame(StatutMessage::EN_COURS, $message->fresh()->statut);
        $this->assertNotNull($message->fresh()->date_envoi);
        Queue::assertPushed(SendMessageJob::class, fn (SendMessageJob $job) => $job->messageId === $message->id);
    }

    public function test_chaque_destinataire_garde_son_statut_de_livraison_et_sa_date(): void
    {
        Mail::fake();

        $message = $this->messages->creer($this->dto(), [
            'type' => 'libre', 'emails_libres' => ['a@exemple.test', 'b@exemple.test'],
        ], true);

        $message->refresh();
        $this->assertSame(StatutMessage::ENVOYE, $message->statut);
        $this->assertSame(2, (int) $message->nb_envoyes);
        $this->assertSame(0, (int) $message->nb_echecs);
        foreach ($message->destinataires as $destinataire) {
            $this->assertSame(StatutEnvoi::ENVOYE, $destinataire->statut_envoi);
            $this->assertNotNull($destinataire->date_envoi);
        }
    }

    public function test_le_taux_de_livraison_distingue_envoi_partiel_et_echec_total(): void
    {
        $message = $this->messages->creer($this->dto(), [
            'type' => 'libre', 'emails_libres' => ['a@exemple.test', 'b@exemple.test'],
        ]);
        [$premier, $second] = $message->destinataires->all();

        $premier->update(['statut_envoi' => 'envoye']);
        $second->update(['statut_envoi' => 'echec', 'erreur' => 'Boîte pleine']);
        $this->messages->recalculerStatut($message);
        $this->assertSame(StatutMessage::PARTIEL, $message->fresh()->statut);
        $this->assertSame(1, (int) $message->fresh()->nb_echecs);

        $premier->update(['statut_envoi' => 'echec']);
        $this->messages->recalculerStatut($message);
        $this->assertSame(StatutMessage::ECHEC, $message->fresh()->statut);
    }

    public function test_un_message_deja_envoye_ne_part_pas_deux_fois(): void
    {
        Mail::fake();
        $message = $this->messages->creer($this->dto(), ['type' => 'libre', 'emails_libres' => ['a@exemple.test']], true);

        $this->expectException(RuntimeException::class);

        $this->messages->envoyer($message->fresh());
    }

    public function test_seul_un_brouillon_peut_etre_modifie(): void
    {
        Mail::fake();
        $message = $this->messages->creer($this->dto(), ['type' => 'libre', 'emails_libres' => ['a@exemple.test']], true);

        $this->expectException(RuntimeException::class);

        $this->messages->mettreAJour($message->fresh(), $this->dto('Nouveau sujet'));
    }

    public function test_l_historique_des_communications_d_une_famille_est_consultable(): void
    {
        $this->messages->creer($this->dto('Rentrée'), ['type' => 'libre', 'emails_libres' => ['famille@exemple.test']]);
        $this->messages->creer($this->dto('Relance'), ['type' => 'libre', 'emails_libres' => ['famille@exemple.test', 'autre@exemple.test']]);

        $historique = $this->messages->historiqueParEmail('famille@exemple.test');

        $this->assertCount(2, $historique);
        $this->assertEqualsCanonicalizing(['Rentrée', 'Relance'], $historique->pluck('message.sujet')->all());
        $this->assertSame(3, MessageDestinataire::count());
    }

    public function test_un_modele_de_message_est_reutilisable(): void
    {
        $modele = app(ModeleMessageService::class)->creer([
            'code' => 'RELANCE_IMPAYE', 'libelle' => 'Relance impayé',
            'sujet' => 'Rappel de paiement', 'corps' => 'Bonjour {{parent}}, il reste {{montant}} à régler.',
            'variables_disponibles' => ['parent', 'montant'], 'type' => 'relance',
        ]);

        $message = $this->messages->creer(
            new MessageDTO($modele->sujet, $modele->corps, 'relance', $modele->id),
            ['type' => 'libre', 'emails_libres' => ['famille@exemple.test']]
        );

        $this->assertSame(['parent', 'montant'], $modele->fresh()->variables_disponibles);
        $this->assertSame($modele->id, (int) $message->modele_id);
        $this->assertSame('RELANCE_IMPAYE', $message->modele->code);
    }
}
