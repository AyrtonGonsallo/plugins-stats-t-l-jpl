<?php
/**
 * Plugin Name: Custom images API
 * Description: Crée un endpoint api pour récupérer les images et tous les éléments liés (date, rencontre, judoka, club, photographe...)
 * Version: 1.0.0
 * Author: Gonsallo Ayrton
 */



$results=array();


function get_galeries( ){

	$args = array(
		'post_type'      => 'galerie',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		
	);

	$galeries = new WP_Query( $args );


	foreach ($galeries->posts as $galerie) {

		$galerie_id = $galerie->ID;

		$titre = get_field('titre', $galerie_id);
		$credit_images = get_field('credit_images', $galerie_id);
		$equipes = get_field('equipes', $galerie_id);
		$rencontre = get_field('rencontre', $galerie_id);
		$photos = get_field('photos', $galerie_id);

		// Normaliser les valeurs
		$equipes = is_array($equipes) ? $equipes : [];
		$rencontre = is_array($rencontre) ? $rencontre : [];
		$photos = is_array($photos) ? $photos : [];

		// Vérifier qu'on possède au minimum les deux équipes
		$equipe1 = $equipes[0] ?? null;
		$equipe2 = $equipes[1] ?? null;

		// Vérifier la rencontre
		$rencontre_obj = $rencontre[0] ?? null;

		if (!$photos) {
			continue;
		}

		foreach ($photos as $image) {

			// Sécurité sur l'image
			if (!is_array($image)) {
				continue;
			}

			$img_id = !empty($image['ID']) ? (int) $image['ID'] : 0;

			if (!$img_id) {
				continue;
			}

			// Sizes
			$sizes = isset($image['sizes']) && is_array($image['sizes'])
				? $image['sizes']
				: [];

			$url_large = !empty($sizes['large'])
				? esc_url($sizes['large'])
				: '';

			$url_thumbnail = !empty($sizes['thumbnail'])
				? esc_url($sizes['thumbnail'])
				: '';

			$url_2048x2048 = !empty($sizes['2048x2048'])
				? esc_url($sizes['2048x2048'])
				: '';

			// Métadonnées
			$related_saison = get_post_meta(
				$img_id,
				'related_saison',
				true
			);

			$related_judoka_1_id = get_post_meta(
				$img_id,
				'related_judoka_1',
				true
			);

			$related_judoka_2_id = get_post_meta(
				$img_id,
				'related_judoka_2',
				true
			);

			$related_rencontre_id = get_post_meta(
				$img_id,
				'related_rencontre',
				true
			);

			// Objets
			$related_judoka_1 = $related_judoka_1_id
				? get_post((int) $related_judoka_1_id)
				: null;

			$related_judoka_2 = $related_judoka_2_id
				? get_post((int) $related_judoka_2_id)
				: null;

			$related_rencontre = $related_rencontre_id
				? get_post((int) $related_rencontre_id)
				: null;

			// Valeurs sécurisées
			$results['total'][$img_id] = [
				[
					'id' => $img_id,

					'rencontre_id' => $related_rencontre
						? $related_rencontre->ID
						: ($rencontre_obj->ID ?? null),

					'judoka1_id' => $related_judoka_1
						? $related_judoka_1->ID
						: null,

					'judoka2_id' => $related_judoka_2
						? $related_judoka_2->ID
						: null,

					'equipe1_id' => $equipe1->ID ?? null,
					'equipe2_id' => $equipe2->ID ?? null,

					'date' => $image['date'] ?? null,

					'url_large' => $url_large,
					'url_thumbnail' => $url_thumbnail,
					'url_2048x2048' => $url_2048x2048,

					'rencontre' => $related_rencontre
						? $related_rencontre->post_title
						: ($rencontre_obj->post_title ?? ''),

					'judoka1' => $related_judoka_1
						? $related_judoka_1->post_title
						: '',

					'judoka2' => $related_judoka_2
						? $related_judoka_2->post_title
						: '',

					'equipe1' => $equipe1->post_title ?? '',
					'equipe2' => $equipe2->post_title ?? '',

					'photographe' => $credit_images ?: '',
				]
			];
		}
	}

	 return $results;
}

	
        



	


    


    function get_galeries_flux( ) {
       
        
        $datas = get_galeries()['total'];
        $response = array();
    
        foreach ( $datas as $d ) {
          
			$item = $d[0] ?? [];
        
            $response[] = [
				'id' => $item['id'] ?? null,

				'rencontre_id' => $item['rencontre_id'] ?? null,
				'judoka1_id' => $item['judoka1_id'] ?? null,
				'judoka2_id' => $item['judoka2_id'] ?? null,
				'equipe1_id' => $item['equipe1_id'] ?? null,
				'equipe2_id' => $item['equipe2_id'] ?? null,

				'date' => $item['date'] ?? null,

				'url_large' => $item['url_large'] ?? '',
				'url_thumbnail' => $item['url_thumbnail'] ?? '',
				'url_2048x2048' => $item['url_2048x2048'] ?? '',

				'rencontre' => $item['rencontre'] ?? '',

				'judoka1' => $item['judoka1'] ?? '',
				'judoka2' => $item['judoka2'] ?? '',

				'equipe1' => $item['equipe1'] ?? '',
				'equipe2' => $item['equipe2'] ?? '',

				'photographe' => $item['photographe'] ?? '',
			];
        }


        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
        usort($response, function ($a, $b) {

			// Date DESC
			$dateA = $a['date'] ?? '';
			$dateB = $b['date'] ?? '';

			if ($dateA !== $dateB) {
				return strcmp($dateB, $dateA);
			}

			// Rencontre ID DESC
			$rencontreA = (int) ($a['rencontre_id'] ?? 0);
			$rencontreB = (int) ($b['rencontre_id'] ?? 0);

			if ($rencontreA !== $rencontreB) {
				return $rencontreB <=> $rencontreA;
			}

			// Image ID ASC
			return (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0);
		});
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }



add_action( 'rest_api_init', function () {
    register_rest_route(
        'custom/v2',
        '/photos',
        array(
            'methods' => 'GET',
            'callback' => 'get_galeries_flux',
        )
    );


});

add_action('rest_api_init', function() {
    // Autoriser les requêtes CORS
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Authorization");

    // Gestion des requêtes OPTIONS pour les pré-vols CORS
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        status_header(200);
        exit();
    }
}, 15);