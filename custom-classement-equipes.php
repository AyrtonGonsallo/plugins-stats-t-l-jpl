<?php
/**
 * Plugin Name: Custom classement equipes API
 * Description: Crée un endpoint api pour fournir des infos sur le classement des equipes en live aux mecs de la télé
 * Version: 1.0.0
 * Author: Gonsallo Ayrton
 */



$results=array();


    function get_classement_equipes_plugin( $saison_value,$tri){
        
        $args = array(
            'post_type'      => 'rencontre',
            'posts_per_page' => -1,
            'meta_key'       => 'date_de_debut',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'AND', // Ajout de la relation pour combiner les conditions
                array(
                    'key'     => 'saisons',
                    'compare' => 'LIKE',
                    'value'   => $saison_value,
                ),
                array(
                    'key'     => 'niveau',
                    'compare' => 'LIKE',
                    'value'   => 'Phase de poules',
                ),
            ),
        );

	$rencontres = new WP_Query( $args );

	//prettyPrint($rencontres->posts);exit(-1);

	foreach($rencontres->posts as $rencontre){

		$niveau=get_field("niveau",$rencontre->ID);
		

		$matchs_liste=get_field('les_combat',$rencontre->ID);

		if($matchs_liste[0]['combats']){

			//prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 
			//echo sizeof($matchs_liste).' combats<br>';

			foreach($matchs_liste as $matchs){

				//echo sizeof($matchs['combats']).' matchs<br>';

				//prettyPrint($matchs['combats']);exit(-1);

				foreach($matchs['combats'] as $match){

					$judoka1=$match['judoka_equipe_1'][0];

					$judoka2=$match['judoka_equipe_2'][0];

					$equipe1=get_field( 'equipe_judoka',$judoka1->ID )[0];

					$equipe2=get_field( 'equipe_judoka',$judoka2->ID )[0];
					$equipes_par_saisons1 =get_field('equipes_par_saisons',$judoka1->ID);
					$equipes_par_saisons2 =get_field('equipes_par_saisons',$judoka2->ID);
					//var_dump($equipes_par_saisons);
					if ($equipes_par_saisons1) {
						foreach ($equipes_par_saisons1 as $equipe11) {
							// Obtenir et afficher le titre de l'équipe
							if (isset($equipe11['equipe_judoka']) && isset($equipe11['saisons']) ) {
								//var_dump($equipe1['saisons']);
								
								if( $equipe11['saisons']== $saison_value){
									$equipe1 = $equipe11['equipe_judoka'][0];
								}
							}
						}
					}
					if ($equipes_par_saisons2) {
						foreach ($equipes_par_saisons2 as $equipe21) {
							// Obtenir et afficher le titre de l'équipe
							if (isset($equipe21['equipe_judoka']) && isset($equipe21['saisons']) ) {
								//var_dump($equipe1['saisons']);
								
								if( $equipe21['saisons']== $saison_value){
									$equipe2 = $equipe21['equipe_judoka'][0];
								}
							}
						}
					}
										
					$results['total'][$equipe1->post_title] = [
                        [
                            "nom" => $equipe1->post_title,
                            "combats_individuels" => [], // Initialize as an empty array
                        ]
                    ];
                    $results['total'][$equipe2->post_title] = [
                        [
                            "nom" => $equipe2->post_title,
                            "combats_individuels" => [], // Initialize as an empty array
                        ]
                    ];
				}

			}

		}else{
			$equipe1 = get_field('equipe_1',$rencontre->ID)[0];
        	$equipe2 = get_field('equipe_2',$rencontre->ID)[0];
			$results['total'][$equipe1->post_title] = [
				[
					"nom" => $equipe1->post_title,
					"titre" => $equipe1->post_title,
					"id_ffjda" => get_field('id_ffjda', $equipe1->ID),
					"abreviation" => get_field('abreviation', $equipe1->ID),
					"logo_principal" => get_field('logo_principal', $equipe1->ID),
					"logo_circle" => get_field('logo_circle', $equipe1->ID),
					"logo_miniature" => get_field('logo_miniature', $equipe1->ID),
					"niveau" =>get_field("niveau",$rencontre->ID),
					"combats_individuels" => [], // Initialize as an empty array

				]
			];
			$results['total'][$equipe2->post_title] = [
				[
					"nom" => $equipe2->post_title,
					"titre" => $equipe2->post_title,
					"id_ffjda" => get_field('id_ffjda', $equipe2->ID),
					"abreviation" => get_field('abreviation', $equipe2->ID),
					"logo_principal" => get_field('logo_principal', $equipe2->ID),
					"logo_circle" => get_field('logo_circle', $equipe2->ID),
					"logo_miniature" => get_field('logo_miniature', $equipe2->ID),
					"niveau" =>get_field("niveau",$rencontre->ID),
					"combats_individuels" => [], // Initialize as an empty array
				]
			];
		}

	}

	foreach($rencontres->posts as $rencontre){

		$niveau=get_field("niveau",$rencontre->ID);
		$mode_de_calcul_classement=get_field("mode_de_calcul_classement",$rencontre->ID);
		$matchs_liste=get_field('les_combat',$rencontre->ID);
		$winner= get_field('les_combat',$rencontre->ID)[0]['equipe_gagnante'];
        $ippons_equ1=0;
		$ippons_equ2=0;
        $equipe1 = get_field('equipe_1',$rencontre->ID)[0];
        $equipe2 = get_field('equipe_2',$rencontre->ID)[0];
		
		if($matchs_liste[0]['combats']){
			$results["total"][$equipe1->post_title][0]["points_marqués"]+=intval($matchs_liste[0]['points_equipe_1']);
			$results["total"][$equipe2->post_title][0]["points_marqués"]+=intval($matchs_liste[0]['points_equipe_2']);
			$results["total"][$equipe1->post_title][0]["titre"]=$equipe1->post_title;
			$results["total"][$equipe2->post_title][0]["titre"]=$equipe2->post_title;
			$results["total"][$equipe1->post_title][0]["id_ffjda"]= get_field('id_ffjda',$equipe1->ID);
			$results["total"][$equipe2->post_title][0]["id_ffjda"]= get_field('id_ffjda',$equipe2->ID);
			$results["total"][$equipe1->post_title][0]["abreviation"]= get_field('abreviation',$equipe1->ID);
			$results["total"][$equipe2->post_title][0]["abreviation"]= get_field('abreviation',$equipe2->ID);
			$results["total"][$equipe1->post_title][0]["logo_principal"]= get_field('logo_principal',$equipe1->ID);
			$results["total"][$equipe2->post_title][0]["logo_principal"]= get_field('logo_principal',$equipe2->ID);
			$results["total"][$equipe1->post_title][0]["logo_circle"]= get_field('logo_circle',$equipe1->ID);
			$results["total"][$equipe2->post_title][0]["logo_circle"]= get_field('logo_circle',$equipe2->ID);
			$results["total"][$equipe1->post_title][0]["logo_miniature"]= get_field('logo_miniature',$equipe1->ID);
			$results["total"][$equipe2->post_title][0]["logo_miniature"]= get_field('logo_miniature',$equipe2->ID);
				
			//prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 

			//echo sizeof($matchs_liste).' combats<br>';

			
			foreach($matchs_liste as $matchs){

				

				$i=0;

				$winner= $matchs['equipe_gagnante'];
				
				foreach($matchs['combats'] as $match){

					//prettyPrint($match);exit(-1);

					$judoka1=$match['judoka_equipe_1'][0];

					$judoka2=$match['judoka_equipe_2'][0];

					$equipe1=get_field( 'equipe_judoka',$judoka1->ID )[0];

					$equipe2=get_field( 'equipe_judoka',$judoka2->ID )[0];
					$equipes_par_saisons1 =get_field('equipes_par_saisons',$judoka1->ID);
					$equipes_par_saisons2 =get_field('equipes_par_saisons',$judoka2->ID);
					
					//var_dump($equipes_par_saisons);
					if ($equipes_par_saisons1) {
						foreach ($equipes_par_saisons1 as $equipe11) {
							// Obtenir et afficher le titre de l'équipe
							if (isset($equipe11['equipe_judoka']) && isset($equipe11['saisons']) ) {
								//var_dump($equipe1['saisons']);
								
								if( $equipe11['saisons']== $saison_value){
									$equipe1 = $equipe11['equipe_judoka'][0];
								}
							}
						}
					}
					if ($equipes_par_saisons2) {
						foreach ($equipes_par_saisons2 as $equipe21) {
							// Obtenir et afficher le titre de l'équipe
							if (isset($equipe21['equipe_judoka']) && isset($equipe21['saisons']) ) {
								//var_dump($equipe1['saisons']);
								
								if( $equipe21['saisons']== $saison_value){
									$equipe2 = $equipe21['equipe_judoka'][0];
								}
							}
						}
					}
					$judoka_gagnant=$match['judoka_gagnant'];
					if($judoka_gagnant!=null && $judoka_gagnant==1){
						$results['total'][$equipe1->post_title][0]["matchs_v"]+=1;
						$results['total'][$equipe2->post_title][0]["matchs_d"]+=1;
						
					}else if($judoka_gagnant!=null && $judoka_gagnant==2){
						$results['total'][$equipe1->post_title][0]["matchs_d"]+=1;
						$results['total'][$equipe2->post_title][0]["matchs_v"]+=1;
					}else{
						$results['total'][$equipe1->post_title][0]["matchs_nuls"]+=1;
						$results['total'][$equipe2->post_title][0]["matchs_nuls"]+=1;
					}
                    $results['total'][$equipe1->post_title][0]["combats_individuels"][] = [
                        "RencontreID" => $rencontre->ID,
                        "Combat" => $judoka1->post_title." (".$equipe1->post_title.") vs ".$judoka2->post_title." (".$equipe2->post_title.")",
                        "Gagnant"=>$winner,
                        //"i"=>$i
                    ];
                    $results['total'][$equipe2->post_title][0]["combats_individuels"][] = [
                        "RencontreID" => $rencontre->ID,
                        "Combat" => $judoka1->post_title." (".$equipe1->post_title.") vs ".$judoka2->post_title." (".$equipe2->post_title.")",
                        "Gagnant"=>$winner,
                        //"i"=>$i
                    ];
					if($i==0){

						$results["total"][$equipe1->post_title][0]["nombre_de_rencontres"]+=1;

						$results["total"][$equipe2->post_title][0]["nombre_de_rencontres"]+=1;

					}
					
					if($mode_de_calcul_classement=="auto"){
							//lire les ippons(ippon1,ippon2) du fichier pour le classement
                            $ippons_equ1+=$match['valeur_ippons_comptés_judoka_1'];
                            $ippons_equ2+=$match['valeur_ippons_comptés_judoka_2'];
						$results["total"][$equipe1->post_title][0]["ippons_marqués"]+=$match['valeur_ippons_comptés_judoka_1'];
						$results["total"][$equipe2->post_title][0]["ippons_concédés"]+=$match['valeur_ippons_comptés_judoka_1'];
						$results["total"][$equipe2->post_title][0]["ippons_marqués"]+=$match['valeur_ippons_comptés_judoka_2'];
						$results["total"][$equipe1->post_title][0]["ippons_concédés"]+=$match['valeur_ippons_comptés_judoka_2'];
					
					}else if($mode_de_calcul_classement=="manual"){//manual : par analyse dans le code
						if (($match['valeur_ippon_judoka_1']>=1)){
                            $ippons_equ1+=$match['valeur_ippon_judoka_1'];
							if((is_numeric($match['valeurs_shidos_judoka_2']['value']))){
								//si il y 0,1,2 shidos en face
								$results["total"][$equipe1->post_title][0]["ippons_marqués"]+=$match['valeur_ippon_judoka_1'];
								$results["total"][$equipe2->post_title][0]["ippons_concédés"]+=$match['valeur_ippon_judoka_1'];
							}else if(!(is_numeric($match['valeurs_shidos_judoka_2']['value']))){
								//si il y a penalité H,X,A,F,M en face
								$results["total"][$equipe1->post_title][0]["ippons_marqués"]+=($match['valeur_ippon_judoka_1']-1);
								$results["total"][$equipe2->post_title][0]["ippons_concédés"]+=($match['valeur_ippon_judoka_1']-1);
							}
							
						}
						if (($match['valeur_ippon_judoka_2']>=1)){
                            $ippons_equ2+=$match['valeur_ippon_judoka_2'];
							if((is_numeric($match['valeurs_shidos_judoka_1']['value']))){
								//si il y 0,1,2 shidos en face
								$results["total"][$equipe2->post_title][0]["ippons_marqués"]+=$match['valeur_ippon_judoka_2'];
								$results["total"][$equipe1->post_title][0]["ippons_concédés"]+=$match['valeur_ippon_judoka_2'];
							}else if(!(is_numeric($match['valeurs_shidos_judoka_1']['value']))){
								//si il y a penalité H,X,A,F,M en face
								$results["total"][$equipe2->post_title][0]["ippons_marqués"]+=($match['valeur_ippon_judoka_2']-1);
								$results["total"][$equipe1->post_title][0]["ippons_concédés"]+=($match['valeur_ippon_judoka_2']-1);
							}
						}
					}
					
					/*
					*/
					if ($match['valeur_wazari__judoka_1']>=1){

						$results["total"][$equipe1->post_title][0]["wazaris_marqués"]+=$match['valeur_wazari__judoka_1'];


						$results["total"][$equipe2->post_title][0]["wazaris_concédés"]+=$match['valeur_wazari__judoka_1'];

					}

					if ($match['valeur_wazari__judoka_2']>=1){

						$results["total"][$equipe2->post_title][0]["wazaris_marqués"]+=$match['valeur_wazari__judoka_2'];

						$results["total"][$equipe1->post_title][0]["wazaris_concédés"]+=$match['valeur_wazari__judoka_2'];

					}

					$i++;

					$results["total"][$equipe1->post_title][0]["niveau"]=$niveau;

					$results["total"][$equipe2->post_title][0]["niveau"]=$niveau;

					$results["total"][$equipe1->post_title][0]["equipe_id"]=$equipe1->ID;

					$results["total"][$equipe2->post_title][0]["equipe_id"]=$equipe2->ID;

				}
                
				if($ippons_equ1>=5){

					$results["total"][$equipe1->post_title][0]["bonus"]+=1;

				}

				if($ippons_equ2>=5){
					$results["total"][$equipe2->post_title][0]["bonus"]+=1;
				}

				
			}


			$results["total"][$equipe1->post_title][0]["matchs_joues"]+=1;
			$results["total"][$equipe2->post_title][0]["matchs_joues"]+=1;
			if ($winner=='équipe 1'){
	
				$results["total"][$equipe1->post_title][0]["victoires"]+=1;
				$results["total"][$equipe2->post_title][0]["defaites"]+=1;
				$results["total"][$equipe1->post_title][0]["points"]+=3;
				$results["total"][$equipe2->post_title][0]["points"]+=0;
			}
			if ($winner=='inconnue'){
				$results["total"][$equipe1->post_title][0]["nuls"]+=1;
				$results["total"][$equipe2->post_title][0]["nuls"]+=1;
				$results["total"][$equipe1->post_title][0]["points"]+=1;
				$results["total"][$equipe2->post_title][0]["points"]+=1;
			}
			if ($winner=='équipe 2'){
				$results["total"][$equipe1->post_title][0]["defaites"]+=1;
				$results["total"][$equipe2->post_title][0]["victoires"]+=1;
				$results["total"][$equipe1->post_title][0]["points"]+=0;
				$results["total"][$equipe2->post_title][0]["points"]+=3;
			}
			$results["total"][$equipe1->post_title][0]["points"]+=intval($matchs_liste[0]['bonus_equipe_1']);
			$results["total"][$equipe2->post_title][0]["points"]+=intval($matchs_liste[0]['bonus_equipe_2']);
		}
        



	}



$sorted_result_ids=array();
	foreach($results['total'] as $result_by_equipe){
		
		foreach($result_by_equipe as $result_by_equipe_data){
			//prettyPrint($result_by_equipe_data);
			
			array_push($sorted_result_ids,array("id"=>$result_by_equipe_data["nom"],"matchs_v"=>$result_by_equipe_data["matchs_v"],"points"=>$result_by_equipe_data["points"],"points_marqués"=>$result_by_equipe_data["points_marqués"],"ippons_marqués"=>$result_by_equipe_data["ippons_marqués"]));
		}
	}
	if($tri=="classement"){
		$sorted_result_ids2=array_msort2($sorted_result_ids,array('points'=>SORT_DESC,'matchs_v'=>SORT_DESC,'points_marqués'=>SORT_DESC,'ippons_marqués'=>SORT_DESC,'id'=>SORT_ASC));

	}else if($tri=="offensive"){
		$sorted_result_ids2=array_msort2($sorted_result_ids,array('points_marqués'=>SORT_DESC,'ippons_marqués'=>SORT_DESC,'id'=>SORT_ASC));

	}
	
	//prettyPrint($sorted_result_ids);
	//var_dump($sorted_result_ids2);
	//exit(-1);
	if($tri=="classement"){
		$count=0;
		$i=1;
		$pts_prec=0;
		foreach($sorted_result_ids2 as $s2){
			$results['total'][$s2["id"]][0]["rang"]=$i;
			$sorted_results['total'][$s2["id"]]=$results['total'][$s2["id"]];
			if($count==0){
				$i+=1;
			}
			if( ($s2["points"]<=$pts_prec)){
				$i+=1;
			}
			$count+=1;
			$pts_prec=$s2["points"];
		}
	}else if($tri=="offensive"){
		$count2=0;
		$i2=1;
		$pts_prec2=0;
		$ipps_prec2=0;
		foreach($sorted_result_ids2 as $s2){
			$results['total'][$s2["id"]][0]["rang"]=$i2;
			$sorted_results['total'][$s2["id"]]=$results['total'][$s2["id"]];
			if($count2==0){
				$i2+=1;
			}
			if( ($s2["points_marqués"]<$pts_prec2)){
				$i2+=1;
			}else if( ($s2["ippons_marqués"]<$ipps_prec2)){
				$i2+=1;
			}
			
			$count2+=1;
			$pts_prec2=$s2["points_marqués"];
			$ipps_prec2=$s2["ippons_marqués"];
		}
	}
	
	//var_dump($sorted_results);
	//exit(-1);



	return $sorted_results;
        
    }

    


    function get_classement_equipes( $data ) {
        $last_season_value = "2024-2025";
        
        $class_classement_equipes = get_classement_equipes_plugin( $last_season_value,"classement" )['total'];
        $response = array();
    
        foreach ( $class_classement_equipes as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id_ffjda' => $d[0]['id_ffjda'] ?? null,
                'titre' => $d[0]['titre'] ?? '',
				'phase'=>'journée 1',
                'abreviation' => $d[0]['abreviation'] ?? '',
                'logo_miniature' => $d[0]['logo_miniature'] ?? '',
                'logo_circle' => $d[0]['logo_circle'] ?? '',
                'logo_principal' => $d[0]['logo_principal'] ?? '',
                'niveau' => $d[0]['niveau'] ?? '',
                'rang' => $d[0]['rang'] ?? '',
                'points' => $d[0]['points'] ?? 0,
                'bonus' => $d[0]['bonus'] ?? 0,
                'matchs_joues' => $d[0]['matchs_joues'] ?? 0,
                'victoires' => $d[0]['victoires'] ?? 0,
                'nuls' => $d[0]['nuls'] ?? 0,
                'defaites' => $d[0]['defaites'] ?? 0,
                'ippons_marqués' => $d[0]['ippons_marqués'] ?? 0,
                'ippons_concédés' => $d[0]['ippons_concédés'] ?? 0,
                'wazaris_marqués' => $d[0]['wazaris_marqués'] ?? 0,
                'wazaris_concédés' => $d[0]['wazaris_concédés'] ?? 0,
                'points_marqués' => $d[0]['points_marqués'] ?? 0,
				'matchs_v' => $d[0]['matchs_v'] ?? 0,
                'combats_individuels' => $d[0]['combats_individuels'] ?? '',

                
            );
        }
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
        usort($response, function ($a, $b) {
            // Compare by points_equipe (desc)
            if ($a['rang'] != $b['rang']) {
                return $a['rang'] - $b['rang'];
            }
            if ($a['titre'] != $b['titre']) {
                return strcmp($a['titre'], $b['titre']);
            }
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['points'] - $b['points'];
        });
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }


	function get_classement_equipes_offensives( $data ) {
        $last_season_value = "2024-2025";
        
        $class_classement_equipes = get_classement_equipes_plugin( $last_season_value,"offensive" )['total'];
		//var_dump($class_classement_equipes);exit(-1);
        $response = array();
    
        foreach ( $class_classement_equipes as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id_ffjda' => $d[0]['id_ffjda'] ?? null,
                'titre' => $d[0]['titre'] ?? '',
				'phase'=>'journée 1',
                'abreviation' => $d[0]['abreviation'] ?? '',
                'logo_miniature' => $d[0]['logo_miniature'] ?? '',
                'logo_circle' => $d[0]['logo_circle'] ?? '',
                'logo_principal' => $d[0]['logo_principal'] ?? '',
                'niveau' => $d[0]['niveau'] ?? '',
                'rang' => $d[0]['rang'] ?? '',
                'points' => $d[0]['points'] ?? 0,
                'bonus' => $d[0]['bonus'] ?? 0,
                'matchs_joues' => $d[0]['matchs_joues'] ?? 0,
                'victoires' => $d[0]['victoires'] ?? 0,
                'nuls' => $d[0]['nuls'] ?? 0,
                'defaites' => $d[0]['defaites'] ?? 0,
                'ippons_marqués' => $d[0]['ippons_marqués'] ?? 0,
                'ippons_concédés' => $d[0]['ippons_concédés'] ?? 0,
                'wazaris_marqués' => $d[0]['wazaris_marqués'] ?? 0,
                'wazaris_concédés' => $d[0]['wazaris_concédés'] ?? 0,
                'points_marqués' => $d[0]['points_marqués'] ?? 0,
                'combats_individuels' => $d[0]['combats_individuels'] ?? '',
                
            );
        }
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
        // Sort by multiple fields: points (desc), ippons_marqués (desc)
		usort($response, function ($a, $b) {
			// Compare by points (desc)
			if ($a['points_marqués'] != $b['points_marqués']) {
				return $b['points_marqués'] - $a['points_marqués']; // Descending order
			}
			// Compare by ippons_marqués (desc)
			if ($a['ippons_marqués'] != $b['ippons_marqués']) {
				return $b['ippons_marqués'] - $a['ippons_marqués']; // Descending order
			}
			if ($a['titre'] != $b['titre']) {
                return strcmp($a['titre'], $b['titre']);//ce sont des strings
            }
			return 0; // If points and ippons_marqués are equal
		});
    
        wp_send_json(array_slice($response,0,5), 200, JSON_UNESCAPED_UNICODE);
    }
    


add_action( 'rest_api_init', function () {
    register_rest_route(
        'custom/v2',
        '/classement_equipes',
        array(
            'methods' => 'GET',
            'callback' => 'get_classement_equipes',
        )
    );

	register_rest_route(
        'custom/v2',
        '/equipes_offensives',
        array(
            'methods' => 'GET',
            'callback' => 'get_classement_equipes_offensives',
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