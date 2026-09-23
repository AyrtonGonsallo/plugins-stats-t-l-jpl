<?php
/**
 * Plugin Name: Custom rencontres API
 * Description: Crée un endpoint api pour fournir des infos sur les infos rencontres aux mecs de la télé
 * Version: 1.0.0
 * Author: Gonsallo Ayrton
 */

function array_msort22($array, $cols)
	{
		$colarr = array();
		foreach ($cols as $col => $order) {
			$colarr[$col] = array();
			foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
		}
		$eval = 'array_multisort(';
		foreach ($cols as $col => $order) {
			$eval .= '$colarr[\''.$col.'\'],'.$order.',';
		}
		$eval = substr($eval,0,-1).');';
		eval($eval);
		$ret = array();
		foreach ($colarr as $col => $arr) {
			foreach ($arr as $k => $v) {
				$k = substr($k,1);
				if (!isset($ret[$k])) $ret[$k] = $array[$k];
				$ret[$k][$col] = $array[$k][$col];
			}
		}
		return $ret;
	
	}

$steps=array("FINAL FOUR","QUARTS DE FINALE C & D","QUARTS DE FINALE A & B","Poule A","Poule B","Poule C","Poule D");
$results=array();


    function get_rencontres_data( $saison_value,$journee){
        setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');

        $args = array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_key' => 'date_de_debut',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query'     => 
            array(  
                'relation' => 'AND', // Ajout de la relation pour combiner les conditions
                array(
                    'key'        => 'niveau',      
                    'compare'    => 'LIKE',
                    'value'      => 'Final four'
                ),
                array(
                    'key'        => 'saisons',
                    'compare'    => 'LIKE',
                    'value'      => $saison_value
                )
                
            ),	
        );
        $rencontres = new WP_Query( $args );
        
        //prettyPrint($rencontres->posts);exit(-1);
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            if($matchs_liste){
                //prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 
                
                //echo sizeof($matchs_liste).' combats<br>';
                foreach($matchs_liste as $matchs){
                    
                    //echo sizeof($matchs['combats']).' matchs<br>';
                    //prettyPrint($matchs['combats']);exit(-1);
                    foreach($matchs['combats'] as $match){
                        $results['total'][$rencontre->ID] = [
                            [
                                "combats" => [], // Initialize as an empty array
                            ]
                        ];
                        
                    }
                }
            }
        }
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            $equipe1 = get_field('equipe_1',$rencontre->ID)[0];
            $equipe2 = get_field('equipe_2',$rencontre->ID)[0];
            $video = get_field( "video_live",$rencontre->ID);
            $texte_descriptif = get_field( "texte_descriptif",$rencontre->ID);
            $lieu_rencontre = get_field( "lieu_rencontre",$rencontre->ID);
            $date_de_debut = get_field( "date_de_debut",$rencontre->ID);
            $journee = get_field( "journee",$rencontre->ID);
            if (strpos($date_de_debut, ' pm') !== false) {
                $date_string = str_replace(' pm', '', $date_de_debut); // Supprime 'pm'
            }
            
            if (strpos($date_de_debut, ' am') !== false) {
                $date_string = str_replace(' am', '', $date_de_debut); // Supprime 'am'
            }
            $date_string = str_replace('/', '-', $date_string); // Convertir les / en -
            $timestamp = strtotime($date_string);
            $full_date_de_debut = strftime('%A %d %B %Y',$timestamp);
            $heure_de_debut = strftime('%H:%M', $timestamp);
            $statut=get_field('statut',$rencontre->ID)['label'];
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            $titre=(get_field('intitule',$rencontre->ID)?get_field('intitule',$rencontre->ID):get_the_title($rencontre->ID));
            // Remplacer "&#8211;" ou le tiret long "–" dans le titre
            $titre = str_replace('&#8211;', '', $titre);
            $titre = str_replace('–', '', $titre); // Cas du tiret long directement dans le titre
            $results['total'][$rencontre->ID][0]["id"] = $rencontre->ID;
            $results['total'][$rencontre->ID][0]["title"] = $titre;
            $results['total'][$rencontre->ID][0]["lieu_rencontre"] = $lieu_rencontre;
            $results['total'][$rencontre->ID][0]["date_timestamp"] = $timestamp;
            $results['total'][$rencontre->ID][0]["date_de_debut"] = $date_de_debut;
            $results['total'][$rencontre->ID][0]["full_date_de_debut"] = $full_date_de_debut;
            $results['total'][$rencontre->ID][0]["heure_de_debut"] = $heure_de_debut;
            $results['total'][$rencontre->ID][0]["statut"] = $statut;
            $results['total'][$rencontre->ID][0]["phase"] = $phase;
            $results['total'][$rencontre->ID][0]["journee"] =$journee;
            $results['total'][$rencontre->ID][0]["equipe_1"] = get_the_title($equipe1->ID);
            $results['total'][$rencontre->ID][0]["equipe_2"] = get_the_title($equipe2->ID);
            $results["total"][$rencontre->ID][0]["abreviation_1"]= get_field('abreviation',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["abreviation_2"]= get_field('abreviation',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_principal_1"]= get_field('logo_principal',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_principal_2"]= get_field('logo_principal',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_circle_1"]= get_field('logo_circle',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_circle_2"]= get_field('logo_circle',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_miniature_1"]= get_field('logo_miniature',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_miniature_2"]= get_field('logo_miniature',$equipe2->ID);
            if($matchs_liste){
                
                foreach($matchs_liste as $matchs){//rencontres
                    $ncge1 = $matchs['nombre_de_combat_gagne_equipe_1'];
                    $pts_e1 = $matchs['points_equipe_1'] ;
                    $pts_e2 = $matchs['points_equipe_2'] ;
                    $equipe_gagnante = $matchs['equipe_gagnante'];
                    $ncge2 = $matchs['nombre_de_combat_gagne_equipe_2'];
                    $duree_combat= $matchs['temps_restant'];


                    $results['total'][$rencontre->ID][0]["pts_e1"] = $pts_e1;
                    $results['total'][$rencontre->ID][0]["pts_e2"] = $pts_e2;
                    $results['total'][$rencontre->ID][0]["duree_combat"] = $duree_combat;
                    $results['total'][$rencontre->ID][0]["equipe_gagnante"] = $equipe_gagnante;
                    $results['total'][$rencontre->ID][0]["ncge1"] = $ncge1;
                    $results['total'][$rencontre->ID][0]["ncge2"] = $ncge2;
                    foreach($matchs['combats'] as $match){//combats
                        //prettyPrint($match);exit(-1);
                        $judoka1=$match['judoka_equipe_1'][0];
                        $judoka_gagnant=$match['judoka_gagnant'];
                        $judoka2=$match['judoka_equipe_2'][0];
                        
                        $results['total'][$rencontre->ID][0]["combats"][] = [
                            "affiche" => $judoka1->post_title." vs ".$judoka2->post_title,
                        ];
                        
                    }
                   
                    
                  
                    
                }
            }
        }
        //prettyPrint($results);
        //exit(-1);
        
    
          
            return $results;
        
        
        
    }



    
    function get_ended_rencontres_data( $saison_value){
        setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');

        $args = array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_key' => 'date_de_debut',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query'     => 
            array(  
                'relation' => 'AND', // Ajout de la relation pour combiner les conditions
                array(
                    'key'     => 'statut', // remplace par ton champ ACF exact
                    'value'   => 'terminé',
                    'compare' => 'LIKE'
                ),
                array(
                    'key'        => 'saisons',
                    'compare'    => 'LIKE',
                    'value'      => $saison_value
                )
                
            ),	
        );
        $rencontres = new WP_Query( $args );
        
        //prettyPrint($rencontres->posts);exit(-1);
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            if($matchs_liste){
                //prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 
                
                //echo sizeof($matchs_liste).' combats<br>';
                foreach($matchs_liste as $matchs){
                    
                    //echo sizeof($matchs['combats']).' matchs<br>';
                    //prettyPrint($matchs['combats']);exit(-1);
                    foreach($matchs['combats'] as $match){
                        $results['total'][$rencontre->ID] = [
                            [
                                "combats" => [], // Initialize as an empty array
                            ]
                        ];
                        
                    }
                }
            }
        }
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            $equipe1 = get_field('equipe_1',$rencontre->ID)[0];
            $equipe2 = get_field('equipe_2',$rencontre->ID)[0];
            $video = get_field( "video_live",$rencontre->ID);
            $texte_descriptif = get_field( "texte_descriptif",$rencontre->ID);
            $lieu_rencontre = get_field( "lieu_rencontre",$rencontre->ID);
            $date_de_debut = get_field( "date_de_debut",$rencontre->ID);
            $journee = get_field( "journee",$rencontre->ID);
            if (strpos($date_de_debut, ' pm') !== false) {
                $date_string = str_replace(' pm', '', $date_de_debut); // Supprime 'pm'
            }
            
            if (strpos($date_de_debut, ' am') !== false) {
                $date_string = str_replace(' am', '', $date_de_debut); // Supprime 'am'
            }
            $date_string = str_replace('/', '-', $date_string); // Convertir les / en -
            $timestamp = strtotime($date_string);
            $full_date_de_debut = strftime('%A %d %B %Y',$timestamp);
            $heure_de_debut = strftime('%H:%M', $timestamp);
            $statut=get_field('statut',$rencontre->ID)['label'];
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            $titre=(get_field('intitule',$rencontre->ID)?get_field('intitule',$rencontre->ID):get_the_title($rencontre->ID));
            // Remplacer "&#8211;" ou le tiret long "–" dans le titre
            $titre = str_replace('&#8211;', '', $titre);
            $titre = str_replace('–', '', $titre); // Cas du tiret long directement dans le titre
            $results['total'][$rencontre->ID][0]["id"] = $rencontre->ID;
            $results['total'][$rencontre->ID][0]["title"] = $titre;
            $results['total'][$rencontre->ID][0]["lieu_rencontre"] = $lieu_rencontre;
            $results['total'][$rencontre->ID][0]["date_timestamp"] = $timestamp;
            $results['total'][$rencontre->ID][0]["date_de_debut"] = $date_de_debut;
            $results['total'][$rencontre->ID][0]["full_date_de_debut"] = $full_date_de_debut;
            $results['total'][$rencontre->ID][0]["heure_de_debut"] = $heure_de_debut;
            $results['total'][$rencontre->ID][0]["statut"] = $statut;
            $results['total'][$rencontre->ID][0]["phase"] = $phase;
            $results['total'][$rencontre->ID][0]["journee"] =$journee;
            $results['total'][$rencontre->ID][0]["equipe_1"] = get_the_title($equipe1->ID);
            $results['total'][$rencontre->ID][0]["equipe_2"] = get_the_title($equipe2->ID);
            $results["total"][$rencontre->ID][0]["abreviation_1"]= get_field('abreviation',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["abreviation_2"]= get_field('abreviation',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_principal_1"]= get_field('logo_principal',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_principal_2"]= get_field('logo_principal',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_circle_1"]= get_field('logo_circle',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_circle_2"]= get_field('logo_circle',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_miniature_1"]= get_field('logo_miniature',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_miniature_2"]= get_field('logo_miniature',$equipe2->ID);
            if($matchs_liste){
                
                foreach($matchs_liste as $matchs){//rencontres
                    $ncge1 = $matchs['nombre_de_combat_gagne_equipe_1'];
                    $pts_e1 = $matchs['points_equipe_1'] ;
                    $pts_e2 = $matchs['points_equipe_2'] ;
                    $equipe_gagnante = $matchs['equipe_gagnante'];
                    $ncge2 = $matchs['nombre_de_combat_gagne_equipe_2'];
                    $duree_combat= $matchs['temps_restant'];


                    $results['total'][$rencontre->ID][0]["pts_e1"] = $pts_e1;
                    $results['total'][$rencontre->ID][0]["pts_e2"] = $pts_e2;
                    $results['total'][$rencontre->ID][0]["duree_combat"] = $duree_combat;
                    $results['total'][$rencontre->ID][0]["equipe_gagnante"] = $equipe_gagnante;
                    $results['total'][$rencontre->ID][0]["ncge1"] = $ncge1;
                    $results['total'][$rencontre->ID][0]["ncge2"] = $ncge2;
                    foreach($matchs['combats'] as $match){//combats
                        //prettyPrint($match);exit(-1);
                        $judoka1=$match['judoka_equipe_1'][0];
                        $judoka_gagnant=$match['judoka_gagnant'];
                        $judoka2=$match['judoka_equipe_2'][0];
                        
                        $results['total'][$rencontre->ID][0]["combats"][] = [
                            "affiche" => $judoka1->post_title." vs ".$judoka2->post_title,
                        ];
                        
                    }
                   
                    
                  
                    
                }
            }
        }
        
        return $results;
        
        
        
    }



    function get_filtered_rencontres_data( $dd,$df){
        setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');

        $timezone = wp_timezone();

    $start = (new DateTime('@' . $dd))->setTimezone($timezone);
    $end   = (new DateTime('@' . $df))->setTimezone($timezone);

    $date_from = $start->format('Y-m-d H:i:s');
    $date_to   = $end->format('Y-m-d H:i:s');


        $args = array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_key' => 'date_de_debut',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query'     => [
            [
                'key'     => 'date_de_debut', // ACF date
                'value'   => [$date_from, $date_to],
                'compare' => 'BETWEEN',
                'type'    => 'DATETIME' // ou 'DATE' selon le format stocké
            ]
        ],
           
           
        );
        $rencontres = new WP_Query( $args );
        
        //prettyPrint($rencontres->posts);exit(-1);
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            if($matchs_liste){
                //prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 
                
                //echo sizeof($matchs_liste).' combats<br>';
                foreach($matchs_liste as $matchs){
                    
                    //echo sizeof($matchs['combats']).' matchs<br>';
                    //prettyPrint($matchs['combats']);exit(-1);
                    foreach($matchs['combats'] as $match){
                        $results['total'][$rencontre->ID] = [
                            [
                                "combats" => [], // Initialize as an empty array
                            ]
                        ];
                        
                    }
                }
            }
        }
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            $equipe1 = get_field('equipe_1',$rencontre->ID)[0];
            $equipe2 = get_field('equipe_2',$rencontre->ID)[0];
            $video = get_field( "video_live",$rencontre->ID);
            $texte_descriptif = get_field( "texte_descriptif",$rencontre->ID);
            $lieu_rencontre = get_field( "lieu_rencontre",$rencontre->ID);
            $date_de_debut = get_field( "date_de_debut",$rencontre->ID);
            $journee = get_field( "journee",$rencontre->ID);
            if (strpos($date_de_debut, ' pm') !== false) {
                $date_string = str_replace(' pm', '', $date_de_debut); // Supprime 'pm'
            }
            
            if (strpos($date_de_debut, ' am') !== false) {
                $date_string = str_replace(' am', '', $date_de_debut); // Supprime 'am'
            }
            $date_string = str_replace('/', '-', $date_string); // Convertir les / en -
            $timestamp = strtotime($date_string);
            $full_date_de_debut = strftime('%A %d %B %Y',$timestamp);
            $heure_de_debut = strftime('%H:%M', $timestamp);
            $statut=get_field('statut',$rencontre->ID)['label'];
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            $titre=(get_field('intitule',$rencontre->ID)?get_field('intitule',$rencontre->ID):get_the_title($rencontre->ID));
            // Remplacer "&#8211;" ou le tiret long "–" dans le titre
            $titre = str_replace('&#8211;', '', $titre);
            $titre = str_replace('–', '', $titre); // Cas du tiret long directement dans le titre
            $results['total'][$rencontre->ID][0]["id"] = $rencontre->ID;
            $results['total'][$rencontre->ID][0]["title"] = $titre;
            $results['total'][$rencontre->ID][0]["lieu_rencontre"] = $lieu_rencontre;
            $results['total'][$rencontre->ID][0]["date_timestamp"] = $timestamp;
            $results['total'][$rencontre->ID][0]["date_de_debut"] = $date_de_debut;
            $results['total'][$rencontre->ID][0]["full_date_de_debut"] = $full_date_de_debut;
            $results['total'][$rencontre->ID][0]["heure_de_debut"] = $heure_de_debut;
            $results['total'][$rencontre->ID][0]["statut"] = $statut;
            $results['total'][$rencontre->ID][0]["phase"] = $phase;
            $results['total'][$rencontre->ID][0]["journee"] =$journee;
            $results['total'][$rencontre->ID][0]["equipe_1"] = get_the_title($equipe1->ID);
            $results['total'][$rencontre->ID][0]["equipe_2"] = get_the_title($equipe2->ID);
            $results["total"][$rencontre->ID][0]["abreviation_1"]= get_field('abreviation',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["abreviation_2"]= get_field('abreviation',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_principal_1"]= get_field('logo_principal',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_principal_2"]= get_field('logo_principal',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_circle_1"]= get_field('logo_circle',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_circle_2"]= get_field('logo_circle',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_miniature_1"]= get_field('logo_miniature',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_miniature_2"]= get_field('logo_miniature',$equipe2->ID);
            if($matchs_liste){
                
                foreach($matchs_liste as $matchs){//rencontres
                    $ncge1 = $matchs['nombre_de_combat_gagne_equipe_1'];
                    $pts_e1 = $matchs['points_equipe_1'] ;
                    $pts_e2 = $matchs['points_equipe_2'] ;
                    $equipe_gagnante = $matchs['equipe_gagnante'];
                    $ncge2 = $matchs['nombre_de_combat_gagne_equipe_2'];
                    $duree_combat= $matchs['temps_restant'];


                    $results['total'][$rencontre->ID][0]["pts_e1"] = $pts_e1;
                    $results['total'][$rencontre->ID][0]["pts_e2"] = $pts_e2;
                    $results['total'][$rencontre->ID][0]["duree_combat"] = $duree_combat;
                    $results['total'][$rencontre->ID][0]["equipe_gagnante"] = $equipe_gagnante;
                    $results['total'][$rencontre->ID][0]["ncge1"] = $ncge1;
                    $results['total'][$rencontre->ID][0]["ncge2"] = $ncge2;
                    foreach($matchs['combats'] as $match){//combats
                        //prettyPrint($match);exit(-1);
                        $judoka1=$match['judoka_equipe_1'][0];
                        $judoka_gagnant=$match['judoka_gagnant'];
                        $judoka2=$match['judoka_equipe_2'][0];
                        
                        $results['total'][$rencontre->ID][0]["combats"][] = [
                            "affiche" => $judoka1->post_title." vs ".$judoka2->post_title,
                        ];
                        
                    }
                   
                    
                  
                    
                }
            }
        }
        
        return $results;
        
        
        
    }


    //avoir le classement 


    function get_classement22($rencontres,$saison_value,$limit){
        foreach($rencontres as $rencontre){
            //prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            //echo sizeof($matchs_liste).' combats<br>'; 
            if(!$matchs_liste[0]['combats']){
                $equipe1=get_field( 'equipe_1',$rencontre->ID)[0];
                $equipe2=get_field( 'equipe_2',$rencontre->ID)[0];
                
                //prettyPrint($equipe1);exit(-1); 
                $results[$equipe1->post_title]=[array("nom"=>$equipe1->post_title)];
                $results[$equipe2->post_title]=[array("nom"=>$equipe2->post_title)];
                $results[$equipe1->post_title][0]["image"]=get_field('logo_circle', $equipe1->ID);
                $results[$equipe2->post_title][0]["image"]=get_field('logo_circle', $equipe2->ID);
                $results[$equipe1->post_title][0]["id"]= $equipe1->ID;
                $results[$equipe2->post_title][0]["id"]= $equipe2->ID;
                $results[$equipe1->post_title][0]["points_marqués"]=0;
                $results[$equipe2->post_title][0]["points_marqués"]=0;
                $results[$equipe1->post_title][0]["ippons_marqués"]=0;
                $results[$equipe2->post_title][0]["ippons_marqués"]=0;
                        
            }else{
                foreach($matchs_liste as $matchs){
                
                    //echo sizeof($matchs['combats']).' matchs<br>';
                    //prettyPrint($matchs['combats']);exit(-1);
                    foreach($matchs['combats'] as $match){
                        $judoka1=$match['judoka_equipe_1'][0];
                        $judoka2=$match['judoka_equipe_2'][0];
                        $equipes_par_saisons_1 =get_field('equipes_par_saisons',$judoka1->ID);
                        $equipes_par_saisons_2 =get_field('equipes_par_saisons',$judoka2->ID);
                        $equipe1=null;
                        $equipe2=null;
                        //var_dump($equipes_par_saisons);
                        if ($equipes_par_saisons_1) {
                            foreach ($equipes_par_saisons_1 as $eq1) {
                                // Obtenir et afficher le titre de l'équipe
                                if (isset($eq1['equipe_judoka']) && isset($eq1['saisons']) ) {
                                    //var_dump($eq1['saisons'][0]);
                                        
                                        if( $eq1['saisons']== $saison_value){
                                            $equipe1 = $eq1['equipe_judoka'][0];
                                        }
                                        
                                    
                                }
                                

                            }
                        }

                        //var_dump($equipes_par_saisons);
                        if ($equipes_par_saisons_2) {
                            foreach ($equipes_par_saisons_2 as $eq2) {
                                // Obtenir et afficher le titre de l'équipe
                                if (isset($eq2['equipe_judoka']) && isset($eq2['saisons']) ) {
                                    //var_dump($equipe1['saisons'][0]);
                                        
                                        if( $eq2['saisons']== $saison_value){
                                            $equipe2 = $eq2['equipe_judoka'][0];
                                        }
                                        
                                    
                                }
                                

                            }
                        }
                        /**if(!$equipe1 || !$equipe2){
                            echo 'erreur';
                            prettyPrint($match);exit(-1); 
                        }**/
                        $results[$equipe1->post_title]=[array("nom"=>$equipe1->post_title)];
                        $results[$equipe2->post_title]=[array("nom"=>$equipe2->post_title)];
                        $results[$equipe1->post_title][0]["conference"]=get_field('conference',$equipe1->ID);
                        $results[$equipe2->post_title][0]["conference"]=get_field('conference',$equipe2->ID);
                        $results[$equipe1->post_title][0]["points"]=0;
                        $results[$equipe2->post_title][0]["points"]=0;
                    }
                }
            }
            
        }
        //prettyPrint($results);exit(-1); 
        foreach($rencontres as $rencontre){
            $mode_de_calcul_classement=get_field("mode_de_calcul_classement",$rencontre->ID);

            $matchs_liste=get_field('les_combat',$rencontre->ID);
            //prettyPrint($mode_de_calcul_classement);exit(1);
            
            if(!$matchs_liste[0]['combats']){
                $equipe1=get_field( 'equipe_1',$rencontre->ID)[0];
                $equipe2=get_field( 'equipe_2',$rencontre->ID)[0];
                $results[$equipe1->post_title][0]["nombre_de_rencontres"]+=0;
                $results[$equipe2->post_title][0]["nombre_de_rencontres"]+=0;
                
            
            }else{
                $equipe1=get_field( 'equipe_1',$rencontre->ID )[0];
                $equipe2=get_field( 'equipe_2',$rencontre->ID )[0];
                if($equipe1->ID){
                    $results[$equipe1->post_title][0]["id"]=$equipe1->ID;
                    $results[$equipe1->post_title][0]["image"]=get_field('logo_circle', $equipe1->ID);
                }
                if($equipe2->ID){
                    $results[$equipe2->post_title][0]["id"]=$equipe2->ID;
                    $results[$equipe2->post_title][0]["image"]=get_field('logo_circle', $equipe2->ID);	
                }
                        
                $results[$equipe1->post_title][0]["points_marqués"]+=intval($matchs_liste[0]['points_equipe_1']);
                $results[$equipe2->post_title][0]["points_marqués"]+=intval($matchs_liste[0]['points_equipe_2']);
                //$results[$equipe1->post_title][0]["bonus"]+=($matchs_liste[0]['bonus_equipe_1'])?intval($matchs_liste[0]['bonus_equipe_1']):0;
                //$results[$equipe2->post_title][0]["bonus"]+=($matchs_liste[0]['bonus_equipe_2'])?intval($matchs_liste[0]['bonus_equipe_2']):0;
                foreach($matchs_liste as $matchs){
                    
                    
                    $winner= $matchs['equipe_gagnante'];
                    foreach($matchs['combats'] as $match){
                        //prettyPrint($match);//exit(-1);
                        $judoka1=$match['judoka_equipe_1'][0];
                        $judoka2=$match['judoka_equipe_2'][0];
                        $equipes_par_saisons_1 =get_field('equipes_par_saisons',$judoka1->ID);
                        $equipes_par_saisons_2 =get_field('equipes_par_saisons',$judoka2->ID);
                        $equipe1=null;
                        $equipe2=null;
                        //var_dump($equipes_par_saisons);
                        if ($equipes_par_saisons_1) {
                            foreach ($equipes_par_saisons_1 as $eq1) {
                                // Obtenir et afficher le titre de l'équipe
                                if (isset($eq1['equipe_judoka']) && isset($eq1['saisons']) ) {
                                    //var_dump($eq1['saisons'][0]);
                                        
                                        if( $eq1['saisons']== $saison_value){
                                            $equipe1 = $eq1['equipe_judoka'][0];
                                        }
                                        
                                    
                                }
                                

                            }
                        }

                        //var_dump($equipes_par_saisons);
                        if ($equipes_par_saisons_2) {
                            foreach ($equipes_par_saisons_2 as $eq2) {
                                // Obtenir et afficher le titre de l'équipe
                                if (isset($eq2['equipe_judoka']) && isset($eq2['saisons']) ) {
                                    //var_dump($equipe1['saisons'][0]);
                                        
                                        if( $eq2['saisons']== $saison_value){
                                            $equipe2 = $eq2['equipe_judoka'][0];
                                        }
                                        
                                    
                                }
                                

                            }
                        }
                        $judoka_gagnant = $match['judoka_gagnant'];
                        if($judoka_gagnant!=null && $judoka_gagnant==1){
                            $results[$equipe1->post_title][0]["combats_gagnés"]+=1;
                            $results[$equipe2->post_title][0]["combats_perdus"]+=1;
                        }else if($judoka_gagnant!=null && $judoka_gagnant==2){
                            $results[$equipe2->post_title][0]["combats_gagnés"]+=1;
                            $results[$equipe1->post_title][0]["combats_perdus"]+=1;
                        }else{
                            $results[$equipe1->post_title][0]["combats_nuls"]+=1;
                            $results[$equipe2->post_title][0]["combats_nuls"]+=1;
                        }
                        $results[$equipe1->post_title][0]["combats_j"]+=1;
                            $results[$equipe2->post_title][0]["combats_j"]+=1;
                        $results[$equipe1->post_title][0]["error"]=$rencontre->ID.' : '.$judoka1->ID.' : '.$judoka1->post_title;
                        $results[$equipe2->post_title][0]["error"]=$rencontre->ID.' : '.$judoka2->ID.' : '.$judoka2->post_title;
                        //echo($mode_de_calcul_classement)exit(-1);
                        if($mode_de_calcul_classement=="auto"){
                            //lire les ippons(ippon1,ippon2) du fichier pour le classement
                            $results[$equipe1->post_title][0]["ippons_marqués"]+=$match['valeur_ippons_comptés_judoka_1'];
                            $results[$equipe2->post_title][0]["ippons_concédés"]+=$match['valeur_ippons_comptés_judoka_1'];
                            $results[$equipe2->post_title][0]["ippons_marqués"]+=$match['valeur_ippons_comptés_judoka_2'];
                            $results[$equipe1->post_title][0]["ippons_concédés"]+=$match['valeur_ippons_comptés_judoka_2'];
                        }else if($mode_de_calcul_classement=="manual"){	
                            
                            if (($match['valeur_ippon_judoka_1']>=1)){
                                if((is_numeric($match['valeurs_shidos_judoka_2']['value']))){
                                    //si il y 0,1,2 shidos en face
                                    $results[$equipe1->post_title][0]["ippons_marqués"]+=$match['valeur_ippon_judoka_1'];
                                    $results[$equipe2->post_title][0]["ippons_concédés"]+=$match['valeur_ippon_judoka_1'];
                                }else if(!(is_numeric($match['valeurs_shidos_judoka_2']['value']))){
                                    //si il y a penalité H,X,A,F,M en face
                                    $results[$equipe1->post_title][0]["ippons_marqués"]+=($match['valeur_ippon_judoka_1']-1);
                                    $results[$equipe2->post_title][0]["ippons_concédés"]+=($match['valeur_ippon_judoka_1']-1);
                                }
                                
                            }
                            if (($match['valeur_ippon_judoka_2']>=1)){
                                if((is_numeric($match['valeurs_shidos_judoka_1']['value']))){
                                    //si il y 0,1,2 shidos en face
                                    $results[$equipe2->post_title][0]["ippons_marqués"]+=$match['valeur_ippon_judoka_2'];
                                    $results[$equipe1->post_title][0]["ippons_concédés"]+=$match['valeur_ippon_judoka_2'];
                                }else if(!(is_numeric($match['valeurs_shidos_judoka_1']['value']))){
                                    //si il y a penalité H,X,A,F,M en face
                                    $results[$equipe2->post_title][0]["ippons_marqués"]+=($match['valeur_ippon_judoka_2']-1);
                                    $results[$equipe1->post_title][0]["ippons_concédés"]+=($match['valeur_ippon_judoka_2']-1);
                                }
                            }
                        }
                        if ($match['valeur_wazari__judoka_1']>=1){
                            $results[$equipe1->post_title][0]["wazaris_marqués"]+=$match['valeur_wazari__judoka_1'];
                            $results[$equipe2->post_title][0]["wazaris_concédés"]+=$match['valeur_wazari__judoka_1'];
                        }
                        if ($match['valeur_wazari__judoka_2']>=1){
                            $results[$equipe2->post_title][0]["wazaris_marqués"]+=$match['valeur_wazari__judoka_2'];
                            $results[$equipe1->post_title][0]["wazaris_concédés"]+=$match['valeur_wazari__judoka_2'];
                        }
                        
                    }
                    
                    $results[$equipe1->post_title][0]["nombre_de_rencontres"]+=1;
                    $results[$equipe2->post_title][0]["nombre_de_rencontres"]+=1;
                    if ($winner=='équipe 1'){
                        $results[$equipe1->post_title][0]["victoires"]+=1;
                        $results[$equipe2->post_title][0]["defaites"]+=1;
                        $results[$equipe1->post_title][0]["points"]+=3;
                        $results[$equipe2->post_title][0]["points"]+=0;
                    }
                    if ($winner=='inconnue'){
                        $results[$equipe1->post_title][0]["nuls"]+=1;
                        $results[$equipe2->post_title][0]["nuls"]+=1;
                        $results[$equipe1->post_title][0]["points"]+=1;
                        $results[$equipe2->post_title][0]["points"]+=1;
                    }
                    if ($winner=='équipe 2'){
                        $results[$equipe1->post_title][0]["defaites"]+=1;
                        $results[$equipe2->post_title][0]["victoires"]+=1;
                        $results[$equipe1->post_title][0]["points"]+=0;
                        $results[$equipe2->post_title][0]["points"]+=3;
                    }
                }
                
            }
        }	
            
        //prettyPrint($results);
        //exit(-1);
        $sorted_results=array();
        $sorted_result_ids=array();
        
        foreach($results as $result){
            array_push($sorted_result_ids,array("id"=>$result[0]["nom"],"combats_gagnés"=>$result[0]["combats_gagnés"],"points_totaux"=>$result[0]["points"],"points"=>$result[0]["points"],"points_marqués"=>$result[0]["points_marqués"],"ippons_marqués"=>$result[0]["ippons_marqués"]));
        }
        
        $sorted_result_ids2=array_slice(array_msort22($sorted_result_ids,array('points_totaux'=>SORT_DESC,'combats_gagnés'=>SORT_DESC,'points_marqués'=>SORT_DESC,'ippons_marqués'=>SORT_DESC,'id'=>SORT_ASC)),0,$limit);
        $i=0;
        foreach($sorted_result_ids2 as $s2){
            if($i<2){
                $results[$s2["id"]][0]["qualifié"]=1;
            }else{
                $results[$s2["id"]][0]["qualifié"]=0;
            }
            $i++;
            $sorted_results[$s2["id"]]=$results[$s2["id"]];
        }
        return $sorted_results;
    }


    function get_final_rencontres_data( $saison_value,$niveau,$fake=false){
        setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');


        $args = array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_key' => 'date_de_debut',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query'     => 
                array(  
                    'relation' => 'AND', // Ajout de la relation pour combiner les conditions
                    array(
                        'key'     => 'niveau',
                        'compare' => 'LIKE',
                        'value'   => $niveau,
                    ),
                    array(
                        'key'        => 'saisons',
                        'compare'    => 'LIKE',
                        'value'      => $saison_value
                    )
                ),	
            );
        $rencontres = new WP_Query( $args );

        if($fake==false){
            
            
            //prettyPrint($rencontres->posts);exit(-1);
            foreach($rencontres->posts as $rencontre){
                $phase=get_field("niveau",$rencontre->ID);
                
                $matchs_liste=get_field('les_combat',$rencontre->ID);
                if($matchs_liste){
                    //prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 
                    
                    //echo sizeof($matchs_liste).' combats<br>';
                    foreach($matchs_liste as $matchs){
                        
                        //echo sizeof($matchs['combats']).' matchs<br>';
                        //prettyPrint($matchs['combats']);exit(-1);
                        foreach($matchs['combats'] as $match){
                            $results['total'][$rencontre->ID] = [
                                [
                                    "combats" => [], // Initialize as an empty array
                                ]
                            ];
                            
                        }
                    }
                }
            }
            foreach($rencontres->posts as $rencontre){
                $phase=get_field("niveau",$rencontre->ID);
                $equipe1 = get_field('equipe_1',$rencontre->ID)[0];
                $equipe2 = get_field('equipe_2',$rencontre->ID)[0];
                $video = get_field( "video_live",$rencontre->ID);
                $texte_descriptif = get_field( "texte_descriptif",$rencontre->ID);
                $lieu_rencontre = get_field( "lieu_rencontre",$rencontre->ID);
                $date_de_debut = get_field( "date_de_debut",$rencontre->ID);
                $journee = get_field( "journee",$rencontre->ID);
                if (strpos($date_de_debut, ' pm') !== false) {
                    $date_string = str_replace(' pm', '', $date_de_debut); // Supprime 'pm'
                }
                
                if (strpos($date_de_debut, ' am') !== false) {
                    $date_string = str_replace(' am', '', $date_de_debut); // Supprime 'am'
                }
                $date_string = str_replace('/', '-', $date_string); // Convertir les / en -
                $timestamp = strtotime($date_string);
                $full_date_de_debut = strftime('%A %d %B %Y',$timestamp);
                $heure_de_debut = strftime('%H:%M', $timestamp);
                $statut=get_field('statut',$rencontre->ID)['label'];
                
                $matchs_liste=get_field('les_combat',$rencontre->ID);
                $titre=(get_field('intitule',$rencontre->ID)?get_field('intitule',$rencontre->ID):get_the_title($rencontre->ID));
                // Remplacer "&#8211;" ou le tiret long "–" dans le titre
                $titre = str_replace('&#8211;', '', $titre);
                $titre = str_replace('–', '', $titre); // Cas du tiret long directement dans le titre
                $results['total'][$rencontre->ID][0]["id"] = $rencontre->ID;
                $results['total'][$rencontre->ID][0]["title"] = $titre;
                $results['total'][$rencontre->ID][0]["lieu_rencontre"] = $lieu_rencontre;
                $results['total'][$rencontre->ID][0]["date_timestamp"] = $timestamp;
                $results['total'][$rencontre->ID][0]["date_de_debut"] = $date_de_debut;
                $results['total'][$rencontre->ID][0]["full_date_de_debut"] = $full_date_de_debut;
                $results['total'][$rencontre->ID][0]["heure_de_debut"] = $heure_de_debut;
                $results['total'][$rencontre->ID][0]["statut"] = $statut;
                $results['total'][$rencontre->ID][0]["phase"] = $phase;
                $results['total'][$rencontre->ID][0]["journee"] =$journee;
                $results['total'][$rencontre->ID][0]["equipe_1"] = get_the_title($equipe1->ID);
                $results['total'][$rencontre->ID][0]["equipe_2"] = get_the_title($equipe2->ID);
                $results["total"][$rencontre->ID][0]["abreviation_1"]= get_field('abreviation',$equipe1->ID);
                $results["total"][$rencontre->ID][0]["abreviation_2"]= get_field('abreviation',$equipe2->ID);
                $results["total"][$rencontre->ID][0]["logo_principal_1"]= get_field('logo_principal',$equipe1->ID);
                $results["total"][$rencontre->ID][0]["logo_principal_2"]= get_field('logo_principal',$equipe2->ID);
                $results["total"][$rencontre->ID][0]["logo_circle_1"]= get_field('logo_circle',$equipe1->ID);
                $results["total"][$rencontre->ID][0]["logo_circle_2"]= get_field('logo_circle',$equipe2->ID);
                $results["total"][$rencontre->ID][0]["logo_miniature_1"]= get_field('logo_miniature',$equipe1->ID);
                $results["total"][$rencontre->ID][0]["logo_miniature_2"]= get_field('logo_miniature',$equipe2->ID);
                if($matchs_liste){
                    
                    foreach($matchs_liste as $matchs){//rencontres
                        $ncge1 = $matchs['nombre_de_combat_gagne_equipe_1'];
                        $pts_e1 = $matchs['points_equipe_1'] ;
                        $pts_e2 = $matchs['points_equipe_2'] ;
                        $equipe_gagnante = $matchs['equipe_gagnante'];
                        $ncge2 = $matchs['nombre_de_combat_gagne_equipe_2'];
                        $duree_combat= $matchs['temps_restant'];


                        $results['total'][$rencontre->ID][0]["pts_e1"] = $pts_e1;
                        $results['total'][$rencontre->ID][0]["pts_e2"] = $pts_e2;
                        $results['total'][$rencontre->ID][0]["duree_combat"] = $duree_combat;
                        $results['total'][$rencontre->ID][0]["equipe_gagnante"] = $equipe_gagnante;
                        $results['total'][$rencontre->ID][0]["ncge1"] = $ncge1;
                        $results['total'][$rencontre->ID][0]["ncge2"] = $ncge2;
                        foreach($matchs['combats'] as $match){//combats
                            //prettyPrint($match);exit(-1);
                            $judoka1=$match['judoka_equipe_1'][0];
                            $judoka_gagnant=$match['judoka_gagnant'];
                            $judoka2=$match['judoka_equipe_2'][0];
                            
                            $results['total'][$rencontre->ID][0]["combats"][] = [
                                "affiche" => $judoka1->post_title." vs ".$judoka2->post_title,
                            ];

                            
                        }

                        
                        
                    
                        
                    
                        
                    }
                }
            }

        }else{
            
                $saison_value2="2025-2026";
                $args2=array(
                    'post_type'=> 'rencontre',
                    'posts_per_page' => -1,
                    'meta_query'     => 
                    array(  
                        array(
                            'key'        => 'saisons',
                            'compare'    => 'LIKE',
                            'value'      => $saison_value2
                        )
                    ),		
                    'meta_key' => 'date_de_debut',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',  
                );
                $rencontres2=get_posts($args2);
                $classement_equipes2=get_classement22($rencontres2,$saison_value2,18);
                $top8_raw = array_slice($classement_equipes2, 0, 8);

                $top8 = []; // tableau propre et réutilisable

                foreach ($top8_raw as $index => $d) {
                    $top8[] = [            // 1 à 8
                        'nom'   => $d[0]['nom'],
                        'image' => $d[0]['image'],
                        'id' => $d[0]['id'],
                        'data'  => $d[0],                   // optionnel : tout garder
                    ];
                }
                $quarts = [
                    [$top8[0], $top8[7]], // 1er vs 8e
                    [$top8[3], $top8[4]], // 4e vs 5e
                    [$top8[1], $top8[6]], // 2e vs 7e
                    [$top8[2], $top8[5]], // 3e vs 6e
                    
                ];

            
            
                for ($i = 0; $i < 4; $i++): 
                    $teamA = $quarts[$i][0];
                    $teamB = $quarts[$i][1];


                    $results['total'][$i][0]["id"] = ($i+1);
                    $results['total'][$i][0]["title"] = "Quart de finale #".($i+1);
                    $results['total'][$i][0]["lieu_rencontre"] = "";
                    $results['total'][$i][0]["date_timestamp"] = null;
                    $results['total'][$i][0]["date_de_debut"] = null;
                    $results['total'][$i][0]["full_date_de_debut"] = null;
                    $results['total'][$i][0]["heure_de_debut"] = null;
                    $results['total'][$i][0]["statut"] = "à venir";
                    $results['total'][$i][0]["phase"] = "Quarts de finale";
                    $results['total'][$i][0]["journee"] = "Quarts de finale";
                    $results['total'][$i][0]["equipe_1"] = $teamA['nom'];
                    $results['total'][$i][0]["equipe_2"] = $teamB['nom'];
                    $results["total"][$i][0]["abreviation_1"]= get_field('abreviation',$teamA['id']);
                    $results["total"][$i][0]["abreviation_2"]= get_field('abreviation',$teamB['id']);
                    $results["total"][$i][0]["logo_principal_1"]= get_field('logo_principal',$teamA['id']);
                    $results["total"][$i][0]["logo_principal_2"]= get_field('logo_principal',$teamB['id']);
                    $results["total"][$i][0]["logo_circle_1"]= get_field('logo_circle',$teamA['id']);
                    $results["total"][$i][0]["logo_circle_2"]= get_field('logo_circle',$teamB['id']);
                    $results["total"][$i][0]["logo_miniature_1"]= get_field('logo_miniature',$teamA['id']);
                    $results["total"][$i][0]["logo_miniature_2"]= get_field('logo_miniature',$teamB['id']);
                endfor; 
            
        }

        
        //prettyPrint($results);
        //exit(-1);
        
    
          
        return $results;
        
        
        
    }


    


    function get_rencontres_data_by_ids( $saison_value,$niveau,$ids){
        setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');

        $args = array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_key' => 'date_de_debut',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'post__in' => $ids,
        );
        $rencontres = new WP_Query( $args );
        
        //prettyPrint($rencontres->posts);exit(-1);
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            if($matchs_liste){
                //prettyPrint(get_field('les_combat')[0]['combats'][0]);exit(-1); 
                
                //echo sizeof($matchs_liste).' combats<br>';
                foreach($matchs_liste as $matchs){
                    
                    //echo sizeof($matchs['combats']).' matchs<br>';
                    //prettyPrint($matchs['combats']);exit(-1);
                    foreach($matchs['combats'] as $match){
                        $results['total'][$rencontre->ID] = [
                            [
                                "combats" => [], // Initialize as an empty array
                            ]
                        ];
                        
                    }
                }
            }
        }
        foreach($rencontres->posts as $rencontre){
            $phase=get_field("niveau",$rencontre->ID);
            $equipe1 = get_field('equipe_1',$rencontre->ID)[0];
            $equipe2 = get_field('equipe_2',$rencontre->ID)[0];
            $video = get_field( "video_live",$rencontre->ID);
            $texte_descriptif = get_field( "texte_descriptif",$rencontre->ID);
            $lieu_rencontre = get_field( "lieu_rencontre",$rencontre->ID);
            $date_de_debut = get_field( "date_de_debut",$rencontre->ID);
            $journee = get_field( "journee",$rencontre->ID);
            if (strpos($date_de_debut, ' pm') !== false) {
                $date_string = str_replace(' pm', '', $date_de_debut); // Supprime 'pm'
            }
            
            if (strpos($date_de_debut, ' am') !== false) {
                $date_string = str_replace(' am', '', $date_de_debut); // Supprime 'am'
            }
            $date_string = str_replace('/', '-', $date_string); // Convertir les / en -
            $timestamp = strtotime($date_string);
            $full_date_de_debut = strftime('%A %d %B %Y',$timestamp);
            $heure_de_debut = strftime('%H:%M', $timestamp);
            $statut=get_field('statut',$rencontre->ID)['label'];
            
            $matchs_liste=get_field('les_combat',$rencontre->ID);
            $titre=(get_field('intitule',$rencontre->ID)?get_field('intitule',$rencontre->ID):get_the_title($rencontre->ID));
            // Remplacer "&#8211;" ou le tiret long "–" dans le titre
            $titre = str_replace('&#8211;', '', $titre);
            $titre = str_replace('–', '', $titre); // Cas du tiret long directement dans le titre
            $results['total'][$rencontre->ID][0]["id"] = $rencontre->ID;
            $results['total'][$rencontre->ID][0]["title"] = $titre;
            $results['total'][$rencontre->ID][0]["lieu_rencontre"] = $lieu_rencontre;
            $results['total'][$rencontre->ID][0]["date_timestamp"] = $timestamp;
            $results['total'][$rencontre->ID][0]["date_de_debut"] = $date_de_debut;
            $results['total'][$rencontre->ID][0]["full_date_de_debut"] = $full_date_de_debut;
            $results['total'][$rencontre->ID][0]["heure_de_debut"] = $heure_de_debut;
            $results['total'][$rencontre->ID][0]["statut"] = $statut;
            $results['total'][$rencontre->ID][0]["phase"] = $phase;
            $results['total'][$rencontre->ID][0]["journee"] =$journee;
            $results['total'][$rencontre->ID][0]["equipe_1"] = get_the_title($equipe1->ID);
            $results['total'][$rencontre->ID][0]["equipe_2"] = get_the_title($equipe2->ID);
            $results["total"][$rencontre->ID][0]["abreviation_1"]= get_field('abreviation',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["abreviation_2"]= get_field('abreviation',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_principal_1"]= get_field('logo_principal',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_principal_2"]= get_field('logo_principal',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_circle_1"]= get_field('logo_circle',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_circle_2"]= get_field('logo_circle',$equipe2->ID);
            $results["total"][$rencontre->ID][0]["logo_miniature_1"]= get_field('logo_miniature',$equipe1->ID);
			$results["total"][$rencontre->ID][0]["logo_miniature_2"]= get_field('logo_miniature',$equipe2->ID);
            if($matchs_liste){
                
                foreach($matchs_liste as $matchs){//rencontres
                    $ncge1 = $matchs['nombre_de_combat_gagne_equipe_1'];
                    $pts_e1 = $matchs['points_equipe_1'] ;
                    $pts_e2 = $matchs['points_equipe_2'] ;
                    $equipe_gagnante = $matchs['equipe_gagnante'];
                    $ncge2 = $matchs['nombre_de_combat_gagne_equipe_2'];
                    $duree_combat= $matchs['temps_restant'];


                    $results['total'][$rencontre->ID][0]["pts_e1"] = $pts_e1;
                    $results['total'][$rencontre->ID][0]["pts_e2"] = $pts_e2;
                    $results['total'][$rencontre->ID][0]["duree_combat"] = $duree_combat;
                    $results['total'][$rencontre->ID][0]["equipe_gagnante"] = $equipe_gagnante;
                    $results['total'][$rencontre->ID][0]["ncge1"] = $ncge1;
                    $results['total'][$rencontre->ID][0]["ncge2"] = $ncge2;
                    foreach($matchs['combats'] as $match){//combats
                        //prettyPrint($match);exit(-1);
                        $judoka1=$match['judoka_equipe_1'][0];
                        $judoka_gagnant=$match['judoka_gagnant'];
                        $judoka2=$match['judoka_equipe_2'][0];
                        
                        $results['total'][$rencontre->ID][0]["combats"][] = [
                            "affiche" => $judoka1->post_title." vs ".$judoka2->post_title,
                        ];
                        
                    }
                   
                    
                  
                    
                }
            }
        }
        //prettyPrint($results);
        //exit(-1);
        
    
          
            return $results;
        
        
        
    }

    


    function get_current_rencontres_plugin( $data ) {
        $last_season_value = "2025-2026";
        $now=date('Y/m/d H:i:s', strtotime('+3 hours'));
        $class_rencontres = get_rencontres_data_by_ids( $last_season_value,"Quart de finale",array(4679) )['total'];
        $response = array();
    
        foreach ( $class_rencontres as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id' => $d[0]['id'] ?? null,
                'title' => $d[0]['title'] ?? '',
                'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                'statut' => $d[0]['statut'] ?? '',
                'phase' => $d[0]['phase'] ?? '',
                'journee' => $d[0]['phase'] ?? '',
                'duree_combat' => $d[0]['duree_combat'] ?? '',
                'equipe_1' => $d[0]['equipe_1'] ?? "",
                'equipe_2' => $d[0]['equipe_2'] ?? "",
                'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                'pts_e1' => $d[0]['pts_e1'] ?? 0,
                'pts_e2' => $d[0]['pts_e2'] ?? 0,
                'score_eq_1' => $d[0]['ncge1'] ?? 0,
                'score_eq_2' => $d[0]['ncge2'] ?? 0,
                'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                'combats' => $d[0]['combats'] ?? '',
                
            );
        }
        usort($response, function ($a, $b) {
            
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['date_timestamp'] - $b['date_timestamp'];
        });
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);

    }


    function get_next_rencontres_plugin( $data ) {
        $last_season_value = "2026-2027";
        $now=date('Y/m/d H:i:s', strtotime('+3 hours'));
       
                
        
        
        

        global $wpdb;
        $class_rencontres = array();
        $aujourdhui = current_time('Y-m-d');

        /*
        // Semaine actuelle
        $semaine_courante = date('o-W', strtotime($aujourdhui));

        // 1️⃣ Récupérer uniquement les rencontres de la semaine en cours
        $args_courante = array(
            'post_type'      => 'rencontre',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_key'       => 'date_de_debut',
            'meta_query'     => array(
                array(
                    'key'     => 'saisons',
                    'value'   => $last_season_value,
                    'compare' => '='
                ),
                array(
                    'key'     => 'date_de_debut',
                    'value'   => array(date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE'
                )
            )
        );

        $rencontres_courante = get_posts($args_courante);
        $ids_rencontres = wp_list_pluck($rencontres_courante, 'ID');

        */

       
        // 2️⃣ parcourir semaine par semaine pour trouver la semaine future la plus proche
        $monday_courant = date('Y-m-d', strtotime("monday this week", strtotime($aujourdhui)));
        $semaine_offset = 1;
        while ($semaine_offset <= 52) { // max 1 an
            $monday = date('Y-m-d', strtotime("+$semaine_offset week", strtotime($monday_courant)));
            $sunday = date('Y-m-d', strtotime("$monday +6 days"));

            $args_future = array(
                'post_type'      => 'rencontre',
                'posts_per_page' => -1,
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'meta_key'       => 'date_de_debut',
                'meta_query'     => array(
                    array(
                        'key'     => 'saisons',
                        'value'   => $last_season_value,
                        'compare' => '='
                    ),
                    array(
                        'key'     => 'date_de_debut',
                        'value'   => array($monday, $sunday),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE'
                    )
                )
            );

            $rencontres_future = get_posts($args_future);

            if (!empty($rencontres_future)) {
                // Ajouter ces rencontres aux IDs existants sans écraser
                // $ids_rencontres = array_merge($ids_rencontres, wp_list_pluck($rencontres_future, 'ID'));
                $ids_rencontres = wp_list_pluck($rencontres_future, 'ID');
                break; // arrêter après avoir trouvé la semaine future la plus proche
            }

            $semaine_offset++;
        }

        // 3️⃣ Appeler la fonction avec les IDs finaux
        $class_rencontres = get_rencontres_data_by_ids($last_season_value, "phase éliminatoire", $ids_rencontres)['total'];




        $response = array();
        if($class_rencontres){
            foreach ( $class_rencontres as $d ) {
                $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
            
                $response[] = array(
                    'id' => $d[0]['id'] ?? null,
                    'title' => $d[0]['title'] ?? '',
                    'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                    'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                    'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                    'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                    'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                    'statut' => $d[0]['statut'] ?? '',
                    'phase' => $d[0]['phase'] ?? '',
                    'journee' => $d[0]['phase'] ?? '',
                    'duree_combat' => $d[0]['duree_combat'] ?? '',
                    'equipe_1' => $d[0]['equipe_1'] ?? "",
                    'equipe_2' => $d[0]['equipe_2'] ?? "",
                    'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                    'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                    'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                    'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                    'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                    'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                    'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                    'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                    'pts_e1' => $d[0]['pts_e1'] ?? 0,
                    'pts_e2' => $d[0]['pts_e2'] ?? 0,
                    'score_eq_1' => $d[0]['ncge1'] ?? 0,
                    'score_eq_2' => $d[0]['ncge2'] ?? 0,
                    'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                    'combats' => $d[0]['combats'] ?? '',
                    
                );
            }
        }
       
        usort($response, function ($a, $b) {
            
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['date_timestamp'] - $b['date_timestamp'];
        });
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       /* usort($response, function ($a, $b) {
            // Compare by points_equipe (desc)
            if ($a['points_equipe'] != $b['points_equipe']) {
                return $b['points_equipe'] - $a['points_equipe'];
            }
            if ($a['ippons_marqués'] != $b['ippons_marqués']) {
                return $b['ippons_marqués'] - $a['ippons_marqués'];
            }
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['wazaris_marqués'] - $b['wazaris_marqués'];
        });*/
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }

    function get_rencontres_plugin( $data ) {
        
    
        $last_season_value = "2026-2027";
        $now=date('Y/m/d H:i:s', strtotime('+3 hours'));
       
                

        global $wpdb;
        $class_rencontres = array();
        $aujourdhui = current_time('Y-m-d');

        // Semaine actuelle
        $semaine_courante = date('o-W', strtotime($aujourdhui));

        // 1️⃣ Récupérer uniquement les rencontres de la semaine en cours
        $args_courante = array(
            'post_type'      => 'rencontre',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_key'       => 'date_de_debut',
            'meta_query'     => array(
                array(
                    'key'     => 'saisons',
                    'value'   => $last_season_value,
                    'compare' => '='
                ),
                array(
                    'key'     => 'date_de_debut',
                    'value'   => array(date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE'
                )
            )
        );

        $rencontres_courante = get_posts($args_courante);
        $ids_rencontres = wp_list_pluck($rencontres_courante, 'ID');
        if($ids_rencontres){
            $class_rencontres = get_rencontres_data_by_ids($last_season_value, "phase éliminatoire", $ids_rencontres)['total'];

        }else{
                    $class_rencontres = [];

        }
        
        $response = array();
    
        foreach ( $class_rencontres as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id' => $d[0]['id'] ?? null,
                'title' => ($d[0]['title']) ?? '',
                'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                'statut' => 'terminé',
                'phase' => $d[0]['phase'] ?? '',
                'journee' => $d[0]['phase'] ?? '',
                'duree_combat' => $d[0]['duree_combat'] ?? '',
                'equipe_1' => $d[0]['equipe_1'] ?? "",
                'equipe_2' => $d[0]['equipe_2'] ?? "",
                'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                'pts_e1' => $d[0]['pts_e1'] ?? 0,
                'pts_e2' => $d[0]['pts_e2'] ?? 0,
                'score_eq_1' => $d[0]['ncge1'] ?? 0,
                'score_eq_2' => $d[0]['ncge2'] ?? 0,
                'equipe_gagnante' =>   $d[0]['equipe_gagnante'] ?? '',
                'combats' => $d[0]['combats'] ?? '',
                
            );
        }
       
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       usort($response, function ($a, $b) {
            
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['date_timestamp'] - $b['date_timestamp'];
        });
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }



    function get_ended_rencontres_plugin( $data ) {
        $last_season_value = "2026-2027";
        $now=date('Y/m/d H:i:s',strtotime('-1 year'));
        $ended_rencontres = get_ended_rencontres_data( $last_season_value,)['total'];

        $response = array();
    
        foreach ( $ended_rencontres as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id' => $d[0]['id'] ?? null,
                'title' => ($d[0]['title']) ?? '',
                'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                'statut' => $d[0]['statut'] ?? '',
                'phase' => $d[0]['phase'] ?? '',
                'journee' => $d[0]['phase'] ?? '',
                'duree_combat' => $d[0]['duree_combat'] ?? '',
                'equipe_1' => $d[0]['equipe_1'] ?? "",
                'equipe_2' => $d[0]['equipe_2'] ?? "",
                'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                'pts_e1' => $d[0]['pts_e1'] ?? 0,
                'pts_e2' => $d[0]['pts_e2'] ?? 0,
                'score_eq_1' => $d[0]['ncge1'] ?? 0,
                'score_eq_2' => $d[0]['ncge2'] ?? 0,
                'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                'combats' => $d[0]['combats'] ?? '',
                
            );
        }

        

        
       
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       usort($response, function ($a, $b) {
            
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['date_timestamp'] - $b['date_timestamp'];
        });
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }




    function get_filtered_rencontres_plugin( $data ) {

    $dd = (int) $data->get_param('dd');
    $df = (int) $data->get_param('df');
        $last_season_value = "2025-2026";
        $now=date('Y/m/d H:i:s',strtotime('-1 year'));
        $filtered_rencontres = get_filtered_rencontres_data( $dd,$df)['total'];

        $response = array();
    
        foreach ( $filtered_rencontres as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id' => $d[0]['id'] ?? null,
                'title' => ($d[0]['title']) ?? '',
                'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                'statut' => $d[0]['statut'] ?? '',
                'phase' => $d[0]['phase'] ?? '',
                'journee' => $d[0]['phase'] ?? '',
                'duree_combat' => $d[0]['duree_combat'] ?? '',
                'equipe_1' => $d[0]['equipe_1'] ?? "",
                'equipe_2' => $d[0]['equipe_2'] ?? "",
                'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                'pts_e1' => $d[0]['pts_e1'] ?? 0,
                'pts_e2' => $d[0]['pts_e2'] ?? 0,
                'score_eq_1' => $d[0]['ncge1'] ?? 0,
                'score_eq_2' => $d[0]['ncge2'] ?? 0,
                'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                'combats' => $d[0]['combats'] ?? '',
                
            );
        }

        

        
       
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       usort($response, function ($a, $b) {
            
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['date_timestamp'] - $b['date_timestamp'];
        });
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }



     function get_quarts_de_final_rencontres_plugin( $data ) {
        $last_season_value = "2025-2026";
        $now=date('Y/m/d H:i:s',strtotime('-1 year'));
        $class_rencontres1 = get_final_rencontres_data( $last_season_value,"Quart de finale")['total'];

        $response = array();
    
        foreach ( $class_rencontres1 as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id' => $d[0]['id'] ?? null,
                'title' => ($d[0]['title']) ?? '',
                'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                'statut' => $d[0]['statut'] ?? '',
                'phase' => $d[0]['phase'] ?? '',
                'journee' => $d[0]['phase'] ?? '',
                'duree_combat' => $d[0]['duree_combat'] ?? '',
                'equipe_1' => $d[0]['equipe_1'] ?? "",
                'equipe_2' => $d[0]['equipe_2'] ?? "",
                'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                'pts_e1' => $d[0]['pts_e1'] ?? 0,
                'pts_e2' => $d[0]['pts_e2'] ?? 0,
                'score_eq_1' => $d[0]['ncge1'] ?? 0,
                'score_eq_2' => $d[0]['ncge2'] ?? 0,
                'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                'combats' => $d[0]['combats'] ?? '',
                
            );
        }

        

        
       
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       usort($response, function ($a, $b) {
            
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['date_timestamp'] - $b['date_timestamp'];
        });
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }


    




    function get_final_rencontres_plugin( $data ) {
        $last_season_value = "2025-2026";
        $now=date('Y/m/d H:i:s',strtotime('-1 year'));
        $class_rencontres11 = get_final_rencontres_data( $last_season_value,"Quart de finale")['total'];
     

        $class_rencontres2 = get_final_rencontres_data( $last_season_value,"Final four (Demi-finale)")['total'];
        $class_rencontres3 = get_final_rencontres_data( $last_season_value,"Final four (Finale)")['total'];

        $saison_value2="2025-2026";
        $args2=array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_query'     => 
            array(  
                'relation' => 'and',   
                array(      
                    'key'        => 'niveau',      
                    'compare'    => '=',      
                    'value'      => 'Quart de finale'
                    ),
                array(
                    'key'        => 'saisons',
                    'compare'    => 'LIKE',
                    'value'      => $saison_value2
                ),
            ),		
            'orderby' => 'post_title',
            'order' => 'ASC',  
        );
        $rencontres2=get_posts($args2);
        $winers = []; 
        $i=1;
        foreach ($rencontres2 as $rencontre):
            $combat=get_field('les_combat', $rencontre->ID)[0]; 
            $equipe1 =get_field('equipe_1', $rencontre->ID)[0];
            $equipe2 =get_field('equipe_2', $rencontre->ID)[0];
        
            $image_miniature1_url=(get_field('logo_miniature', $equipe1->ID))?get_field('logo_miniature', $equipe1->ID):get_the_post_thumbnail_url($equipe1->ID);
            $image_miniature2_url=(get_field('logo_miniature', $equipe2->ID))?get_field('logo_miniature', $equipe2->ID):get_the_post_thumbnail_url($equipe2->ID);
            $image_circle1_url=(get_field('logo_circle', $equipe1->ID))?get_field('logo_circle', $equipe1->ID):get_the_post_thumbnail_url($equipe1->ID);
            $image_circle2_url=(get_field('logo_circle', $equipe2->ID))?get_field('logo_circle', $equipe2->ID):get_the_post_thumbnail_url($equipe2->ID);
            $image_principal1_url=(get_field('logo_principal', $equipe1->ID))?get_field('logo_principal', $equipe1->ID):get_the_post_thumbnail_url($equipe1->ID);
            $image_principal2_url=(get_field('logo_principal', $equipe2->ID))?get_field('logo_principal', $equipe2->ID):get_the_post_thumbnail_url($equipe2->ID);
        
            $abreviation1=(get_field('abreviation', $equipe1->ID))?get_field('abreviation', $equipe1->ID):$equipe1->post_title;
            $abreviation2=(get_field('abreviation', $equipe2->ID))?get_field('abreviation', $equipe2->ID):$equipe2->post_title;
            $equipe_gagnante =  $combat['equipe_gagnante'];
            if($equipe_gagnante=='équipe 1'){
                $winers[] = [            
                    'nom'   => $equipe1->post_title,
                    'image_miniature' => $image_miniature1_url,    
                    'image_circle' => $image_circle1_url,  
                    'image_principal' => $image_principal1_url, 
                    'abreviation_equipe'  => $abreviation1,      
                ];
            }
            else if($equipe_gagnante=='équipe 2'){
                $winers[] = [            
                    'nom'   => $equipe2->post_title,
                    'image_miniature' => $image_miniature2_url,   
                    'image_circle' => $image_circle2_url, 
                    'image_principal' => $image_principal2_url,    
                    'abreviation_equipe'  => $abreviation2,        
                ]; 
            }
            else{
                $winers[] = [            
                    'nom'   => "Vainqueur QF $i",
                    'image_miniature' => "https://judoproleague.com/wp-content/uploads/2024/08/unknown.png", 
                    'image_circle' => "https://judoproleague.com/wp-content/uploads/2024/08/unknown.png", 
                    'image_principal' => "https://judoproleague.com/wp-content/uploads/2024/08/unknown.png",   
                    'abreviation_equipe'  => '',           
                ];
            }
            $i+=1;
        endforeach;

        $response = array();

        $ids = array(18852, 18855, 18854, 18853);

        $class_rencontres11_ordered = array();

        foreach ($ids as $wanted_id) {

            foreach ($class_rencontres11 as $d) {

                if (($d[0]['id'] ?? null) == $wanted_id) {

                    $class_rencontres11_ordered[] = $d;
                    break;
                }
            }
        }
        
        foreach ( $class_rencontres11_ordered as $d ) {
            $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
        
            $response[] = array(
                'id' => $d[0]['id'] ?? null,
                'title' => ($d[0]['title']) ?? '',
                'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                'statut' => $d[0]['statut'] ?? '',
                'phase' => 'Quarts de finale' ?? '',
                'journee' => 'Quarts de finale' ?? '',
                'duree_combat' => $d[0]['duree_combat'] ?? '',
                'equipe_1' => $d[0]['equipe_1'] ?? "",
                'equipe_2' => $d[0]['equipe_2'] ?? "",
                'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                'pts_e1' => $d[0]['pts_e1'] ?? 0,
                'pts_e2' => $d[0]['pts_e2'] ?? 0,
               
                'score_eq_1' => $d[0]['ncge1'] ?? 0,
                'score_eq_2' => $d[0]['ncge2'] ?? 0,
                'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                'combats' => $d[0]['combats'] ?? '',
                
            );
        }

        
        
        if($class_rencontres2){
            foreach ( $class_rencontres2 as $d ) {
                $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
            
                $response[] = array(
                    'id' => $d[0]['id'] ?? null,
                    'title' => ($d[0]['title']) ?? '',
                    'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                    'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                    'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                    'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                    'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                    'statut' => $d[0]['statut'] ?? '',
                    'phase' => 'Demi-finales' ?? '',
                    'journee' => 'Demi-finales' ?? '',
                    'duree_combat' => $d[0]['duree_combat'] ?? '',
                    'equipe_1' => $d[0]['equipe_1'] ?? "",
                    'equipe_2' => $d[0]['equipe_2'] ?? "",
                    'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                    'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                    'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                    'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                    'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                    'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                    'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                    'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                    'pts_e1' => $d[0]['pts_e1'] ?? 0,
                    'pts_e2' => $d[0]['pts_e2'] ?? 0,
                    'score_eq_1' => $d[0]['ncge1'] ?? 0,
                    'score_eq_2' => $d[0]['ncge2'] ?? 0,
                    'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                    'combats' => $d[0]['combats'] ?? '',
                    
                );
            }
        }else{
            
                $response[] = array(
                    'id' => 5521,
                    'title' => 'FINAL 4 (DEMI-FINALE 1)',
                    'lieu_rencontre' =>  'Dojo de Paris',
                    'date_de_debut' => "",
                    'date_timestamp' => 1736296400,
                    'full_date_de_debut' => "",
                    'heure_de_debut' => '',
                    'statut' => "à venir",
                    'phase' => "Final four (Demi-finale)",
                    'journee' => "Final four (Demi-finale)",
                    'duree_combat' =>  '',
                    'equipe_1' => $winers[0]['nom'],
                    'equipe_2' => $winers[3]['nom'],
                    'abreviation_equipe_1' =>  $winers[0]['abreviation_equipe'],
                    'abreviation_equipe_2' =>  $winers[3]['abreviation_equipe'],
                    'logo_principal_equipe_1' => $winers[0]['image_principal'],
                    'logo_principal_equipe_2' => $winers[3]['image_principal'],
                    'logo_circle_equipe_1' => $winers[0]['image_circle'],
                    'logo_circle_equipe_2' => $winers[3]['image_circle'],
                    'logo_miniature_equipe_1' => $winers[0]['image_miniature'],
                    'logo_miniature_equipe_2' => $winers[3]['image_miniature'],
                    'pts_e1' =>  0,
                    'pts_e2' =>  0,
                    'score_eq_1' =>  0,
                    'score_eq_2' =>  0,
                    'equipe_gagnante' => '',
                    'combats' =>  '',
                    
                );
                $response[] = array(
                    'id' => 5522,
                    'title' => 'FINAL 4 (DEMI-FINALE 2)',
                    'lieu_rencontre' =>  '',
                    'date_de_debut' => "",
                    'date_timestamp' => 1736296400,
                    'full_date_de_debut' => "",
                    'heure_de_debut' => '',
                    'statut' => "à venir",
                    'phase' => "Final four (Demi-finale)",
                    'journee' => "Final four (Demi-finale)",
                    'duree_combat' =>  '',
                    'equipe_1' => $winers[1]['nom'],
                    'equipe_2' => $winers[2]['nom'],
                    'abreviation_equipe_1' =>  $winers[1]['abreviation_equipe'],
                    'abreviation_equipe_2' =>  $winers[2]['abreviation_equipe'],
                   'logo_principal_equipe_1' => $winers[1]['image_principal'],
                    'logo_principal_equipe_2' => $winers[2]['image_principal'],
                    'logo_circle_equipe_1' => $winers[1]['image_circle'],
                    'logo_circle_equipe_2' => $winers[2]['image_circle'],
                    'logo_miniature_equipe_1' => $winers[1]['image_miniature'],
                    'logo_miniature_equipe_2' => $winers[2]['image_miniature'],
                    'pts_e1' =>  0,
                    'pts_e2' =>  0,
                    'score_eq_1' =>  0,
                    'score_eq_2' =>  0,
                    'equipe_gagnante' => '',
                    'combats' =>  '',
                    
                );
            
        }
        
        if($class_rencontres3){
            foreach ( $class_rencontres3 as $d ) {
                $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
            
                $response[] = array(
                    'id' => $d[0]['id'] ?? null,
                    'title' => ($d[0]['title']) ?? '',
                    'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                    'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                    'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                    'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                    'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                    'statut' => $d[0]['statut'] ?? '',
                    'phase' => 'Finale' ?? '',
                    'journee' => 'Finale' ?? '',
                    'duree_combat' => $d[0]['duree_combat'] ?? '',
                    'equipe_1' => $d[0]['equipe_1'] ?? "",
                    'equipe_2' => $d[0]['equipe_2'] ?? "",
                    'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                    'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                    'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                    'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                    'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                    'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                    'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                    'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                    'pts_e1' => $d[0]['pts_e1'] ?? 0,
                    'pts_e2' => $d[0]['pts_e2'] ?? 0,
                    'score_eq_1' => $d[0]['ncge1'] ?? 0,
                    'score_eq_2' => $d[0]['ncge2'] ?? 0,
                    'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                    'combats' => $d[0]['combats'] ?? '',
                    
                );
            }
        }else{
            $saison_value2="2025-2026";
            $args2=array(
                'post_type'=> 'rencontre',
                'posts_per_page' => -1,
                'meta_query'     => 
                array(  
                    'relation' => 'and',   
                    array(      
                        'key'        => 'niveau',      
                        'compare'    => 'LIKE',      
                        'value'      => 'Demi'
                        ),
                    array(
                        'key'        => 'saisons',
                        'compare'    => 'LIKE',
                        'value'      => $saison_value2
                    ),
                ),		
                'orderby' => 'post_title',
                'order' => 'ASC',  
            );
            $rencontres2=get_posts($args2);
            $semi_winers = []; 
            $i=1;
            foreach ($rencontres2 as $rencontre):
                $combat=get_field('les_combat', $rencontre->ID)[0]; 
                $equipe1 =get_field('equipe_1', $rencontre->ID)[0];
                $equipe2 =get_field('equipe_2', $rencontre->ID)[0];
            
                $image_miniature1_url=(get_field('logo_miniature', $equipe1->ID))?get_field('logo_miniature', $equipe1->ID):get_the_post_thumbnail_url($equipe1->ID);
                $image_miniature2_url=(get_field('logo_miniature', $equipe2->ID))?get_field('logo_miniature', $equipe2->ID):get_the_post_thumbnail_url($equipe2->ID);
                $image_circle1_url=(get_field('logo_circle', $equipe1->ID))?get_field('logo_circle', $equipe1->ID):get_the_post_thumbnail_url($equipe1->ID);
                $image_circle2_url=(get_field('logo_circle', $equipe2->ID))?get_field('logo_circle', $equipe2->ID):get_the_post_thumbnail_url($equipe2->ID);
                $image_principal1_url=(get_field('logo_principal', $equipe1->ID))?get_field('logo_principal', $equipe1->ID):get_the_post_thumbnail_url($equipe1->ID);
                $image_principal2_url=(get_field('logo_principal', $equipe2->ID))?get_field('logo_principal', $equipe2->ID):get_the_post_thumbnail_url($equipe2->ID);
            
                $abreviation1=(get_field('abreviation', $equipe1->ID))?get_field('abreviation', $equipe1->ID):$equipe1->post_title;
                $abreviation2=(get_field('abreviation', $equipe2->ID))?get_field('abreviation', $equipe2->ID):$equipe2->post_title;
                $equipe_gagnante =  $combat['equipe_gagnante'];
                if($equipe_gagnante=='équipe 1'){
                    $semi_winers[] = [            
                        'nom'   => $equipe1->post_title,
                        'image_miniature' => $image_miniature1_url,    
                        'image_circle' => $image_circle1_url,  
                        'image_principal' => $image_principal1_url, 
                        'abreviation_equipe'  => $abreviation1,      
                    ];
                }
                else if($equipe_gagnante=='équipe 2'){
                    $semi_winers[] = [            
                        'nom'   => $equipe2->post_title,
                        'image_miniature' => $image_miniature2_url,   
                        'image_circle' => $image_circle2_url, 
                        'image_principal' => $image_principal2_url,    
                        'abreviation_equipe'  => $abreviation2,        
                    ]; 
                }
                else{
                    $semi_winers[] = [            
                        'nom'   => "Vainqueur DF $i",
                        'image_miniature' => "https://judoproleague.com/wp-content/uploads/2024/08/unknown.png", 
                        'image_circle' => "https://judoproleague.com/wp-content/uploads/2024/08/unknown.png", 
                        'image_principal' => "https://judoproleague.com/wp-content/uploads/2024/08/unknown.png",   
                        'abreviation_equipe'  => '',           
                    ];
                }
                $i+=1;
            endforeach;


            $response[] = array(
                'id' => 5523,
                'title' => 'FINAL 4 (FINALE)',
                'lieu_rencontre' =>  'Dojo de Paris (75)',
                'date_de_debut' => "16/05/2026 19:00 pm",
                'date_timestamp' => 1736296400,
                'full_date_de_debut' => "samedi 16 mai 2026",
                'heure_de_debut' => '19:00',
                
                'statut' => "à venir",
                'phase' => "Finale",
                'journee' => "Finale",
                'duree_combat' =>  '',
                'equipe_1' => $semi_winers[0]['nom'],
                'equipe_2' => $semi_winers[1]['nom'],
                'abreviation_equipe_1' =>  $semi_winers[0]['abreviation_equipe'],
                'abreviation_equipe_2' =>  $semi_winers[1]['abreviation_equipe'],
                'logo_principal_equipe_1' => $semi_winers[0]['image_principal'],
                'logo_principal_equipe_2' => $semi_winers[1]['image_principal'],
                'logo_circle_equipe_1' => $semi_winers[0]['image_circle'],
                'logo_circle_equipe_2' => $semi_winers[1]['image_circle'],
                'logo_miniature_equipe_1' => $semi_winers[0]['image_miniature'],
                'logo_miniature_equipe_2' => $semi_winers[1]['image_miniature'],
                'pts_e1' =>  0,
                'pts_e2' =>  0,
                'score_eq_1' =>  0,
                'score_eq_2' =>  0,
                'equipe_gagnante' => '',
                'combats' =>  '',
                
            );
        }

        
       
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }



    function get_final4_rencontres_plugin( $data ) {
        $last_season_value = "2025-2026";
        $now=date('Y/m/d H:i:s',strtotime('-1 year'));
       
        $class_rencontres2 = get_final_rencontres_data( $last_season_value,"Final four (Demi-finale)")['total'];
        $class_rencontres3 = get_final_rencontres_data( $last_season_value,"Final four (Finale)")['total'];

        $response = array();
        
        
        
            foreach ( $class_rencontres2 as $d ) {
                $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
            
                $response[] = array(
                    'id' => $d[0]['id'] ?? null,
                    'title' => ($d[0]['title']) ?? '',
                    'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                    'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                    'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                    'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                    'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                    'statut' => $d[0]['statut'] ?? '',
                    'phase' => 'Demi-finales' ?? '',
                    'journee' => 'Demi-finales' ?? '',
                    'duree_combat' => $d[0]['duree_combat'] ?? '',
                    'equipe_1' => $d[0]['equipe_1'] ?? "",
                    'equipe_2' => $d[0]['equipe_2'] ?? "",
                    'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                    'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                    'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                    'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                    'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                    'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                    'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                    'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                    'pts_e1' => $d[0]['pts_e1'] ?? 0,
                    'pts_e2' => $d[0]['pts_e2'] ?? 0,
                    'score_eq_1' => $d[0]['ncge1'] ?? 0,
                    'score_eq_2' => $d[0]['ncge2'] ?? 0,
                    'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                    'combats' => $d[0]['combats'] ?? '',
                    
                );
            }
        
        
        if($class_rencontres3){
            foreach ( $class_rencontres3 as $d ) {
                $fields = get_fields( $d[0]['id'] ?? null); // Handle potential undefined 'rencontre_id'
            
                $response[] = array(
                    'id' => $d[0]['id'] ?? null,
                    'title' => ($d[0]['title']) ?? '',
                    'lieu_rencontre' => $d[0]['lieu_rencontre'] ?? '',
                    'date_de_debut' => $d[0]['date_de_debut'] ?? '',
                    'date_timestamp' => $d[0]['date_timestamp'] ?? '',
                    'full_date_de_debut' => $d[0]['full_date_de_debut'] ?? '',
                    'heure_de_debut' => $d[0]['heure_de_debut'] ?? '',
                    'statut' => $d[0]['statut'] ?? '',
                    'phase' => 'Finale' ?? '',
                    'journee' => 'Finale' ?? '',
                    'duree_combat' => $d[0]['duree_combat'] ?? '',
                    'equipe_1' => $d[0]['equipe_1'] ?? "",
                    'equipe_2' => $d[0]['equipe_2'] ?? "",
                    'abreviation_equipe_1' => $d[0]['abreviation_1'] ?? "",
                    'abreviation_equipe_2' => $d[0]['abreviation_2'] ?? "",
                    'logo_principal_equipe_1' => $d[0]['logo_principal_1'] ?? "",
                    'logo_principal_equipe_2' => $d[0]['logo_principal_2'] ?? "",
                    'logo_circle_equipe_1' => $d[0]['logo_circle_1'] ?? "",
                    'logo_circle_equipe_2' => $d[0]['logo_circle_2'] ?? "",
                    'logo_miniature_equipe_1' => $d[0]['logo_miniature_1'] ?? "",
                    'logo_miniature_equipe_2' => $d[0]['logo_miniature_2'] ?? "",
                    'pts_e1' => $d[0]['pts_e1'] ?? 0,
                    'pts_e2' => $d[0]['pts_e2'] ?? 0,
                    'score_eq_1' => $d[0]['ncge1'] ?? 0,
                    'score_eq_2' => $d[0]['ncge2'] ?? 0,
                    'equipe_gagnante' => $d[0]['equipe_gagnante'] ?? '',
                   // 'combats' => $d[0]['combats'] ?? '',
                    
                );
            }
        }else{
            $response[] = array(
                'id' => 5523,
                'title' => 'FINAL 4 (FINALE)',
                'lieu_rencontre' =>  'Dojo de Paris',
                'date_de_debut' => "18/01/2025 18:30 pm",
                'date_timestamp' => 1736296400,
                'full_date_de_debut' => "samedi 18 janvier 2025",
                'heure_de_debut' => '18:30',
                'statut' => "à venir",
                'phase' => "Finale",
                'journee' => "Finale",
                'duree_combat' =>  '',
                'equipe_1' => "Auxerre Judo",
                'equipe_2' => "US Orléans Judo Loiret",
                'abreviation_equipe_1' =>  "AUX",
                'abreviation_equipe_2' =>  "US0",
                'logo_principal_equipe_1' => "https://judoproleague.com/wp-content/uploads/2022/11/AUXERRE.png",
                'logo_principal_equipe_2' => "https://judoproleague.com/wp-content/uploads/2022/11/ORLEANS-1.png",
                'logo_circle_equipe_1' => "https://judoproleague.com/wp-content/uploads/2022/11/AUX_CIR.png",
                'logo_circle_equipe_2' => "https://judoproleague.com/wp-content/uploads/2022/11/USO_CIR.png",
                'logo_miniature_equipe_1' => "https://judoproleague.com/wp-content/uploads/2022/11/AUX_CIR.png",
                'logo_miniature_equipe_2' => "https://judoproleague.com/wp-content/uploads/2022/11/USO_CIR.png",
                'pts_e1' =>  0,
                'pts_e2' =>  0,
                'score_eq_1' =>  0,
                'score_eq_2' =>  0,
                'equipe_gagnante' => '',
                //'combats' =>  '',
                
            );
        }

        
       
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       
    
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
    }
    




add_action( 'rest_api_init', function () {
    register_rest_route(
        'custom/v2',
        '/rencontres_suivantes',
        array(
            'methods' => 'GET',
            'callback' => 'get_next_rencontres_plugin',
        )
    );
    register_rest_route(
        'custom/v2',
        '/rencontres_actuelles',
        array(
            'methods' => 'GET',
            'callback' => 'get_rencontres_plugin'
            //'callback' => 'get_current_rencontres_plugin',
        )
    );
    register_rest_route(
        'custom/v2',
        '/rencontres',
        array(
            'methods' => 'GET',
            'callback' => 'get_rencontres_plugin',
        )
    );
    register_rest_route(
        'custom/v2',
        '/final_rencontres',
        array(
            'methods' => 'GET',
            'callback' => 'get_final_rencontres_plugin',
        )
    );

    register_rest_route(
        'custom/v2',
        '/ended_meetings',
        array(
            'methods' => 'GET',
            'callback' => 'get_ended_rencontres_plugin',
        )
    );

    register_rest_route(
        'custom/v2',
        '/meetings_by_date',
        array(
            'methods'  => 'GET',
            'callback' => 'get_filtered_rencontres_plugin',
            'args'     => array(
                'dd' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
                'df' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
            ),
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