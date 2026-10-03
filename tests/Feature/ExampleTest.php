<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_racine_renvoie_vers_le_tableau_de_bord(): void
    {
        $this->get('/')->assertRedirect('/tableau');
    }

    public function test_un_visiteur_non_connecte_est_renvoye_vers_la_page_de_connexion(): void
    {
        $this->get('/tableau')->assertRedirect(route('login'));
    }

    public function test_la_page_de_connexion_s_affiche(): void
    {
        $this->get('/login')->assertOk();
    }
}
