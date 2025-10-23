<?php
/*
Plugin Name: Check and add judokas teams
Description: Parcourir un excel et verifier les equipes des judokas
Version: 1.0
Author: Gonsallo Ayrton
*/
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>
            Le plugin <b>Check and add judokas teams</b> nécessite PhpSpreadsheet.<br>
            Exécutez <code>composer require phpoffice/phpspreadsheet</code> dans son dossier.
        </p></div>';
    });
    return;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

// 1️⃣ Définir l’endpoint
function start_check_and_update_scripts_endpoint() {
    add_rewrite_rule(
        '^start-check-and-update-scripts-judokas-teams/?',
        'index.php?start_check_and_update_scripts_teams=1',
        'top'
    );
}
add_action('init', 'start_check_and_update_scripts_endpoint');

function start_check_and_update_scripts_query_vars($query_vars) {
    $query_vars[] = 'start_check_and_update_scripts_teams';
    return $query_vars;
}
add_filter('query_vars', 'start_check_and_update_scripts_query_vars');

function start_check_and_update_scripts_template_redirect() {
    if (get_query_var('start_check_and_update_scripts_teams')) {
        start_check_and_update_scripts();
        exit;
    }
}
add_action('template_redirect', 'start_check_and_update_scripts_template_redirect');


// 2️⃣ Fonction principale
function start_check_and_update_scripts() {
    if ( ! function_exists('download_url') ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    // URL du fichier Excel
    $file_url  = "https://judoproleague.com/import_judokas.xlsx";
    $tmp_file  = download_url($file_url);

    if (is_wp_error($tmp_file)) {
        echo "Erreur de téléchargement du fichier Excel.";
        return;
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp_file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    unlink($tmp_file); // Nettoyer le fichier temporaire

    $saison = "2025-2026";

    // Pagination
    $per_page = 200;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $per_page;

    // Nombre total de lignes (moins l’entête)
    $total_rows = count($rows) - 1;
    $total_pages = ceil($total_rows / $per_page);

    echo "<h2>Vérification des judokas (page $page / $total_pages)</h2>";
    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse;'>
        <tr>
            <th>#</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Catégorie</th>
            <th>Nouvelle équipe</th>
            <th>Statut</th>
        </tr>";

    $i = 0;
    foreach ($rows as $index => $row) {
        if ($index == 1) continue; // Sauter entête
        if ($i < $offset) { $i++; continue; }
        if ($i >= $offset + $per_page) break;
        $i++;

        $id              = trim($row['A']);
        $equipes_saison  = trim($row['B']);
        $nom             = trim($row['C']);
        $prenom          = trim($row['D']);
        $id_ffjda        = trim($row['E']);
        $categorie       = trim($row['F']);
        $nom_equipe_excel = trim($row['G']);
        $nom_trouve             = "";
        $prenom_trouve         = "";

        if (empty($nom) && empty($prenom)) continue;

        $statut = "";

        // Recherche par id_ffjda
        $judoka_id = null;
        if (!empty($id_ffjda)) {
            $args = [
                'post_type' => 'judoka',
                'meta_query' => [[
                    'key' => 'id_ffjda',
                    'value' => $id_ffjda,
                    'compare' => '='
                ]],
                'posts_per_page' => 1
            ];
            $posts = get_posts($args);
            if ($posts) $judoka_id = $posts[0]->ID;
            
            $nom_trouve =get_field('nom_judoka',$judoka_id);
            $prenom_trouve =get_field('prenom_judoka',$judoka_id);
           
        }

        // Sinon recherche par nom + prénom
        if (!$judoka_id) {
            $args = [
                'post_type' => 'judoka',
                'meta_query' => [
                    ['key' => 'nom_judoka', 'value' => $nom, 'compare' => '='],
                    ['key' => 'prenom_judoka', 'value' => $prenom, 'compare' => '=']
                ],
                'posts_per_page' => 1
            ];
            $posts = get_posts($args);
            if ($posts) $judoka_id = $posts[0]->ID;

            $nom_trouve =get_field('nom_judoka',$judoka_id);
            $prenom_trouve =get_field('prenom_judoka',$judoka_id);
        }

        if (!$judoka_id) {
            $statut .= "<br>❌ Judoka non trouvé";
            //le creer
            $judoka_id = wp_insert_post([
                'post_type'   => 'judoka',
                'post_title'  => $nom . ' ' . $prenom,
                'post_status' => 'publish',
            ]);

            if ($judoka_id && !is_wp_error($judoka_id)) {
                // Remplir les champs ACF
                update_field('nom_judoka', $nom, $judoka_id);
                update_field('prenom_judoka', $prenom, $judoka_id);
                if (!empty($id_ffjda)) {
                    update_field('id_ffjda', $id_ffjda, $judoka_id);
                }
                if (!empty($categorie)) {
                    $categorie_clean = str_ireplace('kg', '', $categorie);
                    $categorie_clean = trim($categorie_clean); // nettoyer les espaces
                    update_field('categorie_de_poids', $categorie_clean, $judoka_id);
                    update_field('field_6388b854c20eb', $categorie_clean, $judoka_id);
                    $statut .= "<br>✅ catégorie mise à jour";
                }
                update_field('categorie_dage', 'Senior', $judoka_id);
                update_field('field_64c121fc191a5', 'Senior', $judoka_id);
                $statut .= "<br>✅ Catégorie d'âge mise à jour";
                $statut .= "<br>✅ Judoka créé";

                // Mettre à jour les variables pour affichage
                $nom_trouve = $nom;
                $prenom_trouve = $prenom;
            } else {
                $statut .= "<br>❌ Erreur lors de la création du judoka";
            }

        } else {
            $statut .= "<br>✅ Judoka  trouvé";
            // Vérifier la catégorie
            $cat_judoka = get_field('categorie_de_poids', $judoka_id);
            update_field('categorie_dage', 'Senior', $judoka_id);
            update_field('field_64c121fc191a5', 'Senior', $judoka_id);
            $statut .= "<br>✅ Catégorie d'âge mise à jour";
            if (! $cat_judoka) {
                $statut .= "<br>⚠️ Catégorie absente sur le site";
                 $categorie_clean = str_ireplace('kg', '', $categorie);
                        $categorie_clean = trim($categorie_clean); // nettoyer les espaces
                        update_field('categorie_de_poids', $categorie_clean, $judoka_id);
                        update_field('field_6388b854c20eb', $categorie_clean, $judoka_id);
                         $statut .= "<br>✅ catégorie mise à jour";

            }else{
                $statut .= "<br>✅ Catégorie présente sur le site";
                if (! $categorie) {
                    $statut .= "<br>⚠️ Catégorie absente dans le fichier";
                }
                else {
                    $statut .= "<br>✅ Catégorie présente dans le fichier";
                    if (($cat_judoka."kg") != $categorie) {
                        $statut .= "<br>⚠️ Catégorie différente ($cat_judoka sur le site vs $categorie dans le fichier)";
                        $categorie_clean = str_ireplace('kg', '', $categorie);
                        $categorie_clean = trim($categorie_clean); // nettoyer les espaces
                        update_field('categorie_de_poids', $categorie_clean, $judoka_id);
                        update_field('field_6388b854c20eb', $categorie_clean, $judoka_id);
                         $statut .= "<br>✅ catégorie mise à jour";
                    } else{
                        $statut .= "<br>✅ Catégorie égale ($cat_judoka sur le site vs $categorie dans le fichier)";
                    }
                } 
            }
            
            
        }
        if($nom_equipe_excel) {
                // Vérifier équipe
                $args = [
                    'post_type' => 'equipes',
                    'meta_query' => [
                        'relation' => 'OR',
                        [
                            'key' => 'ffj_nom',
                            'value' => $nom_equipe_excel,
                            'compare' => 'like'
                        ],
                        [
                            'key' => 'titre_de_la_page',
                            'value' => $nom_equipe_excel,
                            'compare' => 'like'
                        ],
                    ],
                    'posts_per_page' => 1
                ];
                $posts = get_posts($args);
                if (!$posts) {
                    $statut .= "<br>❌ Équipe non trouvée";
                } else {
                    $statut .= "<br>✅ Équipe trouvée";
                    $equipe_id = $posts[0]->ID;
                    $equipes_par_saisons = get_field('equipes_par_saisons', $judoka_id);
                    $exists = false;
                    if ($equipes_par_saisons && $judoka_id) {
                        foreach ($equipes_par_saisons as $row_eq) {
                            if (
                                isset($row_eq['equipe_judoka'][0]) &&
                                $row_eq['equipe_judoka'][0]->ID == $equipe_id &&
                                $row_eq['saisons'] == $saison
                            ) {
                                $exists = true;
                                break;
                            }
                        }
                    }
                    if ($exists && $judoka_id) {
                        $statut .= "<br> ℹ️ Déjà présent";
                        
                            
                        
                    } else if(!$exists && $judoka_id) {
                       if ( have_rows('field_664f3da10cda8', $judoka_id) ) {

                            $found = false;

                            while( have_rows('field_664f3da10cda8', $judoka_id) ) {
                                the_row();
                                $saison_ligne = get_sub_field('saisons');
                                if ($saison_ligne == $saison) {
                                    $found = true;
                                    break; // on a trouvé la saison, inutile de continuer
                                }
                            }

                            if (!$found) {
                                // Ajouter une nouvelle ligne si la saison n'existe pas
                                $new_row = [
                                    'field_6371060b58975'      => $equipe_id,
                                    'field_65e06fb7bc7d0'            => $saison,
                                ];
                                add_row('field_664f3da10cda8', $new_row, $judoka_id);
                                $statut .= "✅ Nouvelle ligne ajoutée pour cette saison<br>";
                            }

                        } else {
                            // Répéteur vide, créer la première ligne
                            $new_row = [
                                'field_6371060b58975'      => $equipe_id,
                                'field_65e06fb7bc7d0'            => $saison,
                            ];
                            add_row('field_664f3da10cda8', $new_row, $judoka_id);
                            $statut .= "✅ Première ligne ajoutée pour cette saison<br>";
                        }


                    }
                }
            }else{
                $statut .= "<br>⚠️ Équipe absente";
            }

        echo "<tr>
            <td>".($index-1)."</td>
            <td>$nom</td>
            <td>$prenom</td>
            <td>$categorie</td>
            <td>$nom_equipe_excel</td>
            <td>$statut</td>
        </tr>";
    }

    echo "</table>";

    // Pagination liens
    if ($total_pages > 1) {
        echo "<div style='margin-top:15px;'>";
        for ($p = 1; $p <= $total_pages; $p++) {
            $link = add_query_arg('page', $p);
            if ($p == $page) {
                echo "<strong>[$p]</strong> ";
            } else {
                echo "<a href='$link'>$p</a> ";
            }
        }
        echo "</div>";
    }
}



// -----------------------------
// 1) Définir l’endpoint
// -----------------------------
function get_unique_categories_from_excel_endpoint() {
    add_rewrite_rule(
        '^get-unique-categories-from-excel/?$',
        'index.php?get_unique_categories_from_excel=1',
        'top'
    );
}
add_action('init', 'get_unique_categories_from_excel_endpoint');

// Ajouter la query var
function get_unique_categories_from_excel_query_vars($query_vars) {
    $query_vars[] = 'get_unique_categories_from_excel';
    return $query_vars;
}
add_filter('query_vars', 'get_unique_categories_from_excel_query_vars');

// Redirection quand la query var est appelée
function get_unique_categories_from_excel_template_redirect() {
    if (get_query_var('get_unique_categories_from_excel')) {
        get_unique_categories_from_excel();
        exit;
    }
}
add_action('template_redirect', 'get_unique_categories_from_excel_template_redirect');


// -----------------------------
// 2) Fonction principale
// -----------------------------
function get_unique_categories_from_excel() {
    header('Content-Type: text/html; charset=UTF-8');

    if ( ! function_exists('download_url') ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $file_url = 'https://judoproleague.com/import_judokas.xlsx';
    $tmp_file = download_url( $file_url );

    if ( is_wp_error( $tmp_file ) ) {
        status_header(500);
        echo 'Erreur de téléchargement du fichier Excel : ' . esc_html( $tmp_file->get_error_message() );
        return;
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $tmp_file );
    } catch ( Exception $e ) {
        @unlink( $tmp_file );
        status_header(500);
        echo 'Erreur lors du chargement du fichier Excel : ' . esc_html( $e->getMessage() );
        return;
    }

    $sheet = $spreadsheet->getActiveSheet();
    $rows  = $sheet->toArray( null, true, true, true );

    @unlink( $tmp_file );

    $categories = [];

    foreach ( $rows as $index => $row ) {
        if ( $index == 1 ) continue; // entête
        $cat = isset( $row['F'] ) ? trim( $row['F'] ) : '';
        if ( $cat !== '' ) $categories[] = $cat;
    }

    $categories = array_unique( $categories );
    sort( $categories, SORT_NATURAL );

    echo '<!doctype html><html><head><meta charset="utf-8"><title>Catégories de poids</title></head><body>';
    echo '<h2>Catégories de poids (' . esc_html( count($categories) ) . ')</h2>';
    foreach ( $categories as $c ) {
        echo esc_html( $c ) . '<br>' . "\n";
    }
    echo '</body></html>';
}


// -----------------------------
// 3) Flush rewrite rules (optionnel si dans un plugin)
// -----------------------------
/*
register_activation_hook( __FILE__, function() {
    get_unique_categories_from_excel_endpoint();
    flush_rewrite_rules();
});
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
*/


// 1️⃣ Définir l'endpoint
function judokas_saisons_endpoint() {
    add_rewrite_rule(
        '^judokas-saisons/?',
        'index.php?judokas_saisons=1',
        'top'
    );
}
add_action('init', 'judokas_saisons_endpoint');

function judokas_saisons_query_vars($vars) {
    $vars[] = 'judokas_saisons';
    $vars[] = 'page';
    return $vars;
}
add_filter('query_vars', 'judokas_saisons_query_vars');

function judokas_saisons_template_redirect() {
    if (get_query_var('judokas_saisons')) {
        global $wpdb;

        // Pagination
        $per_page = 50;
        $page = max(1, intval(get_query_var('page', 1)));
        $offset = ($page - 1) * $per_page;

        // Vider et remplir la table
        $wpdb->query("TRUNCATE TABLE wp_judokas_saisons");

        $judokas = get_posts([
            'post_type' => 'judoka',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        foreach ($judokas as $j) {
            $equipes_par_saisons = get_field('equipes_par_saisons', $j->ID);
           $sexe = get_field('sexe', $j->ID) ? get_field('sexe', $j->ID)[0] : 'M';
            $sexe = strtoupper($sexe);
            if ($equipes_par_saisons) {
                foreach ($equipes_par_saisons as $row) {
                    $equipe_obj = $row['equipe_judoka'][0] ?? null; // récupérer le premier élément
                    $equipe_id = $equipe_obj ? $equipe_obj->ID : 0;
                    $equipe_nom = $equipe_obj ? $equipe_obj->post_title : '';
                    $photo = get_the_post_thumbnail_url($j->ID) ?: '/wp-content/uploads/2023/09/profil.jpg';
                    $saison = $row['saisons'] ?? '';
                    

                    // Vérifier si l'entrée existe déjà
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM prol_judokas_saisons WHERE judoka_id=%d AND saison=%s AND equipe_id=%d",
                        $j->ID,
                        $saison,
                        $equipe_id
                    ));

                    if (!$exists) {
                        $wpdb->insert('prol_judokas_saisons', [
                            'judoka_id' => $j->ID,
                            'saison' => $saison,
                            'equipe_id' => $equipe_id,
                            'categorie_de_poids' => get_field('categorie_de_poids', $j->ID) ?? '',
                            'nom' => get_field('nom_judoka', $j->ID) ?? '',
                            'prenom' => get_field('prenom_judoka', $j->ID) ?? '',
                            'permalien' => get_permalink($j->ID),
                            'photo' => $photo,
                            'sexe' => $sexe
                        ]);
                    }
                }
            }
        }


        // Récupérer les données paginées
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wp_judokas_saisons ORDER BY judoka_id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        echo '<h2>Liste des judokas – page ' . $page . '</h2>';
        echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse;">';
        echo '<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Saison</th><th>Équipe</th><th>Catégorie</th><th>Lien</th><th>Photo</th></tr>';

        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->judoka_id) . '</td>';
            echo '<td>' . esc_html($row->nom) . '</td>';
            echo '<td>' . esc_html($row->prenom) . '</td>';
            echo '<td>' . esc_html($row->saison) . '</td>';
            echo '<td>' . esc_html($row->equipe_id) . '</td>';
            echo '<td>' . esc_html($row->categorie_de_poids) . '</td>';
            echo '<td><a href="' . esc_url($row->permalien) . '" target="_blank">Voir</a></td>';
            echo '<td><img src="' . esc_url($row->photo) . '" width="50" /></td>';
            echo '</tr>';
        }

        echo '</table>';

        // Pagination simple
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM wp_judokas_saisons");
        $total_pages = ceil($total_rows / $per_page);

        if ($page > 1) {
            echo '<a href="?judokas_saisons=1&page=' . ($page-1) . '">&laquo; Précédent</a> ';
        }
        if ($page < $total_pages) {
            echo '<a href="?judokas_saisons=1&page=' . ($page+1) . '">Suivant &raquo;</a>';
        }

        exit;
    }
}
add_action('template_redirect', 'judokas_saisons_template_redirect');
