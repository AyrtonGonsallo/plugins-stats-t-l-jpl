<?php
/*
Plugin Name: Check judokas teams
Description: Parcourir les rencontres et ajouter les saisons aux judokas
Version: 1.0
Author: Gonsallo Ayrton
*/


setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');

function start_scripts() {
    // Récupérer le paramètre de pagination dans l'URL
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
    $posts_per_page = 2; // Nombre de rencontres par page
    $offset = $page * $posts_per_page; // Calculer l'offset basé sur la page

    $args = array(
        'post_type' => 'rencontre',
        'posts_per_page' => $posts_per_page,
        'offset' => $offset,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query'     => array(
                'relation' => 'AND', // pour cette saison et les poules
                array(
                    'key'     => 'saisons',
                    'compare' => 'LIKE',
                    'value'   => "2024-2025",
                ),
                array(
                    'key'     => 'niveau',
                    'compare' => 'LIKE',
                    'value'   => 'Phase de poules',
                ),
            ),
    );
    $rencontres = get_posts($args);
    
    foreach ($rencontres as $rencontre) {
        $equipe1 = get_field('equipe_1', $rencontre->ID)[0];
        $equipe2 = get_field('equipe_2', $rencontre->ID)[0];
        $saison = get_field("saisons", $rencontre->ID); // Saison associée

        // Formatage de la date
        $date_de_debut = get_field("date_de_debut", $rencontre->ID);
        $date_string = preg_replace('/[ap]m/', '', $date_de_debut); // Supprime 'am' et 'pm'
        $date_string = str_replace('/', '-', $date_string); // Convertir les / en -
        $timestamp = strtotime($date_string);
        $full_date_de_debut = strftime('%A %d %B %Y', $timestamp);

        echo "Rencontre: " . get_the_title($rencontre->ID) . " - " . $saison . "<br>";
        echo "Date: " . $full_date_de_debut . "<br>";
        echo "Affiche: " . $equipe1->post_title . " vs " . $equipe2->post_title . "<br>";

        // Liste des combats
        $matchs_liste = get_field('les_combat', $rencontre->ID);
        if ($matchs_liste && $matchs_liste[0]['combats']) {
            foreach ($matchs_liste as $matchs) {
                foreach ($matchs['combats'] as $match) {
                    $judoka1 = $match['judoka_equipe_1'][0];
                    $judoka2 = $match['judoka_equipe_2'][0];

                    // Affichage des noms des judokas
                    $club1 = ($equipe1->post_title) ? ' (' . get_field('abreviation', $equipe1->ID) . ') ' : '';
                    $club2 = ($equipe2->post_title) ? ' (' . get_field('abreviation', $equipe2->ID) . ') ' : '';
                    $j1 = get_field('prenom_judoka', $judoka1->ID) . ' ' . get_field('nom_judoka', $judoka1->ID) . $club1;
                    $j2 = get_field('prenom_judoka', $judoka2->ID) . ' ' . get_field('nom_judoka', $judoka2->ID) . $club2;

                    echo "Combat: ".$judoka1->ID." - " . $j1 . " vs ".$judoka2->ID." - " . $j2 . "<br>";

                    // Mettre à jour les équipes et saisons pour les judokas
                    update_judoka_saison_equipe($judoka1->ID, $equipe1->ID, $saison);
                    update_judoka_saison_equipe($judoka2->ID, $equipe2->ID, $saison);
                }
            }
        }
        echo "<br>";
    }
     // Ajouter des liens de pagination
     echo '<br><a href="?page=' . max(0, $page - 1) . '">Page précédente</a> | ';
     echo '<a href="?page=' . ($page + 1) . '">Page suivante</a><br>';
}

/**
 * Mettre à jour le champ 'equipes_par_saisons' d'un judoka.
 *
 * @param int $judoka_id ID du judoka
 * @param int $equipe_id ID de l'équipe
 * @param string $saison Saison actuelle
 */
function update_judoka_saison_equipe($judoka_id, $equipe_id, $saison) {
    // Récupérer les données actuelles du répéteur 'equipes_par_saisons'
    $equipes_par_saisons = get_field('equipes_par_saisons', $judoka_id);
    
    // Vérifier si l'association équipe-saison existe déjà
    $exists = false;
    if ($equipes_par_saisons) {
        foreach ($equipes_par_saisons as $row) {
           // var_dump($row['equipe_judoka']);exit(-1)
            //echo "Verification courante : equipe ".$row['equipe_judoka'][0]->ID." et saison ".$row['saisons']." pour le judoka ID: $judoka_id.<br>";
            if ($row['equipe_judoka'][0]->ID == $equipe_id && $row['saisons'] == $saison) {
                $exists = true;
                break;
            }
        }
    }

    // Si l'entrée n'existe pas, l'ajouter
    if (!$exists) {
        $new_entry = array(
            'equipe_judoka' => $equipe_id,
            'saisons' => $saison
        );

        // Ajouter la nouvelle équipe et saison au répéteur
        if ($equipes_par_saisons) {
            $equipes_par_saisons[] = $new_entry;
        } else {
            $equipes_par_saisons = array($new_entry);
        }

        // Sauvegarder les nouvelles données dans le champ ACF
        $update_result = update_field('equipes_par_saisons', $equipes_par_saisons, $judoka_id);
        
        // Vérifier si la mise à jour a réussi
        if ($update_result) {
            echo "Mise à jour réussie pour le judoka ID: $judoka_id avec l'équipe ID: $equipe_id pour la saison $saison.<br>";
        } else {
            echo "Erreur lors de la mise à jour pour le judoka ID: $judoka_id.<br>";
        }
    } else {
        echo "L'équipe ID: $equipe_id et la saison $saison sont déjà attribuées au judoka ID: $judoka_id.<br>";
    }
        
}






function start_scripts_endpoint() {
    add_rewrite_rule('^start-scripts-judokas-teams/?', 'index.php?start_scripts=1', 'top');
}
add_action('init', 'start_scripts_endpoint');

function start_scripts_query_vars($query_vars) {
    $query_vars[] = 'start_scripts';
    return $query_vars;
}
add_filter('query_vars', 'start_scripts_query_vars');

function start_scripts_template_redirect() {
    if (get_query_var('start_scripts')) {
        start_scripts();  // Call the correct function
        exit;
    }
}
add_action('template_redirect', 'start_scripts_template_redirect');
