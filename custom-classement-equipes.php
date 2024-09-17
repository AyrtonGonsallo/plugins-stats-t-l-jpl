<?php
/**
 * Plugin Name: Custom classement equipes API
 * Description: Crée un endpoint api pour fournir des infos sur le classement des equipes en live aux mecs de la télé
 * Version: 1.0.0
 * Author: Gonsallo Ayrton
 */



$steps=array("FINAL FOUR","QUARTS DE FINALE C & D","QUARTS DE FINALE A & B","Poule A","Poule B","Poule C","Poule D");
$results=array();


    function get_classement_equipes_plugin( $saison_value){
        
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

		if($matchs_liste){

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

		}

	}

	foreach($rencontres->posts as $rencontre){

		$niveau=get_field("niveau",$rencontre->ID);
		$mode_de_calcul_classement=get_field("mode_de_calcul_classement",$rencontre->ID);
		$matchs_liste=get_field('les_combat',$rencontre->ID);
		$winner= get_field('les_combat')[0]['equipe_gagnante'];
        $ippons_equ1=0;
		$ippons_equ2=0;
        $equipe1 = get_field('equipe_1',$rencontre->ID)[0];
        $equipe2 = get_field('equipe_2',$rencontre->ID)[0];
		if($matchs_liste[0]['combats'][0]){

			//prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 

			//echo sizeof($matchs_liste).' combats<br>';

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
			
			foreach($matchs_liste as $matchs){

				

				$i=0;

				$winner= $matchs['equipe_gagnante'];
				
				foreach($matchs['combats'] as $match){

					//prettyPrint($match);exit(-1);

					$judoka1=$match['judoka_equipe_1'][0];

					$judoka2=$match['judoka_equipe_2'][0];

					$equipe1=get_field( 'equipe_judoka',$judoka1->ID )[0];

					$equipe2=get_field( 'equipe_judoka',$judoka2->ID )[0];
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
                
				if($ippons_equ1>=6){

					$results["total"][$equipe1->post_title][0]["bonus"]+=1;

				}

				if($ippons_equ2>=6){
					$results["total"][$equipe2->post_title][0]["bonus"]+=1;
				}

				
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



	}



$sorted_result_ids=array();
	foreach($results['total'] as $result_by_equipe){
		
		foreach($result_by_equipe as $result_by_equipe_data){
			//prettyPrint($result_by_equipe_data);
			
			array_push($sorted_result_ids,array("id"=>$result_by_equipe_data["nom"],"step"=>$result_by_equipe_data["step"],"points"=>$result_by_equipe_data["points"],"points_marqués"=>$result_by_equipe_data["points_marqués"],"ippons_marqués"=>$result_by_equipe_data["ippons_marqués"]));
		}
	}
	$sorted_result_ids2=array_msort2($sorted_result_ids,array('points'=>SORT_DESC,'points_marqués'=>SORT_DESC,'ippons_marqués'=>SORT_DESC));
	
	//prettyPrint($sorted_result_ids);
	//prettyPrint($sorted_result_ids2);
	//exit(-1);
	$i=1;
		foreach($sorted_result_ids2 as $s2){
			
				$results['total'][$s2["id"]][0]["rang"]=$i;
				$sorted_results['total'][$s2["id"]]=$results['total'][$s2["id"]];
				
					$i+=1;
				
				
				
		}
	//prettyPrint($sorted_results);
	//exit(-1);



	return $results;
        
    }

    


    function get_classement_equipes( $data ) {
        $last_season_value = "2023-2024";
        
        $class_classement_equipes = get_classement_equipes_plugin( $last_season_value )['total'];
        $response = array();
    
        foreach ( $class_classement_equipes as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id_ffjda' => $d[0]['id_ffjda'] ?? null,
                'titre' => $d[0]['titre'] ?? '',
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
        usort($response, function ($a, $b) {
            // Compare by points_equipe (desc)
            if ($a['rang'] != $b['rang']) {
                return $a['rang'] - $b['rang'];
            }
            if ($a['titre'] != $b['titre']) {
                return $b['titre'] - $a['titre'];
            }
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['points'] - $b['points'];
        });
    
        return new WP_REST_Response( $response, 200 );
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
});
