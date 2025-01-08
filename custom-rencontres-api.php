<?php
/**
 * Plugin Name: Custom rencontres API
 * Description: Crée un endpoint api pour fournir des infos sur les infos rencontres aux mecs de la télé
 * Version: 1.0.0
 * Author: Gonsallo Ayrton
 */



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
                    'key'     => 'journee',
                    'compare' => 'LIKE',
                    'value'   => $journee,
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


    function get_final_rencontres_data( $saison_value,$niveau){
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


    function get_final_rencontres_data_by_date( $saison_value,$niveau){
        setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');

        $args = array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_key' => 'date_de_debut',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'post__in'       => array(4681,4679),
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


    function get_current_rencontres_data_by_date( $saison_value,$niveau){
        setlocale(LC_ALL, 'fr_FR.UTF8', 'fr_FR','fr','fr','fra','fr_FR@euro');

        $args = array(
            'post_type'=> 'rencontre',
            'posts_per_page' => -1,
            'meta_key' => 'date_de_debut',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'post__in'       => array(4678,4680),
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
        $last_season_value = "2024-2025";
        $now=date('Y/m/d H:i:s', strtotime('+3 hours'));
        $class_rencontres = get_current_rencontres_data_by_date( $last_season_value,"Quart de finale"  )['total'];
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
        $last_season_value = "2024-2025";
        $now=date('Y/m/d H:i:s', strtotime('+3 hours'));
        $class_rencontres = get_final_rencontres_data_by_date( $last_season_value,"Quart de finale"  )['total'];
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
        }else{
            $response[] = array(
                'id' => 5521,
                'title' => 'FINAL 4 (DEMI-FINALE 1)',
                'lieu_rencontre' =>  'Dojo de Paris',
                'date_de_debut' => "18/01/2025 15:30 pm",
                'date_timestamp' => 1736296400,
                'full_date_de_debut' => "samedi 18 janvier 2025",
                'heure_de_debut' => '15:30',
                'statut' => "à venir",
                'phase' => "Final four (Demi-finale)",
                'journee' => "Final four (Demi-finale)",
                'duree_combat' =>  '',
                'equipe_1' => "Vainqueur QF 1",
                'equipe_2' => "Vainqueur QF 4",
                'abreviation_equipe_1' =>  "VQF 1",
                'abreviation_equipe_2' =>  "VQF 4",
                'logo_principal_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_principal_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
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
                'lieu_rencontre' =>  'Dojo de Paris',
                'date_de_debut' => "18/01/2025 15:30 pm",
                'date_timestamp' => 1736296400,
                'full_date_de_debut' => "samedi 18 janvier 2025",
                'heure_de_debut' => '15:30',
                'statut' => "à venir",
                'phase' => "Final four (Demi-finale)",
                'journee' => "Final four (Demi-finale)",
                'duree_combat' =>  '',
                'equipe_1' => "Vainqueur QF 2",
                'equipe_2' => "Vainqueur QF 3",
                'abreviation_equipe_1' =>  "VQF 2",
                'abreviation_equipe_2' =>  "VQF 3",
                'logo_principal_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_principal_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'pts_e1' =>  0,
                'pts_e2' =>  0,
                'score_eq_1' =>  0,
                'score_eq_2' =>  0,
                'equipe_gagnante' => '',
                'combats' =>  '',
                
            );
            /*
            $response[] = array(
                'id' => 5523,
                'title' => 'FINAL 4 (FINALE)',
                'lieu_rencontre' =>  'Dojo de Paris',
                'date_de_debut' => "18/01/2025 15:30 pm",
                'date_timestamp' => 1736296400,
                'full_date_de_debut' => "samedi 18 janvier 2025",
                'heure_de_debut' => '15:30',
                'statut' => "à venir",
                'phase' => "Final four (Finale)",
                'journee' => "Final four (Finale)",
                'duree_combat' =>  '',
                'equipe_1' => "Vainqueur DF 1",
                'equipe_2' => "Vainqueur DF 2",
                'abreviation_equipe_1' =>  "VDF 1",
                'abreviation_equipe_2' =>  "VDF 2",
                'logo_principal_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_principal_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'pts_e1' =>  0,
                'pts_e2' =>  0,
                'score_eq_1' =>  0,
                'score_eq_2' =>  0,
                'equipe_gagnante' => '',
                'combats' =>  '',
                
            );
            */
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
        $last_season_value = "2024-2025";
        $now=date('Y/m/d H:i:s',strtotime('-1 year'));
        $class_rencontres = get_rencontres_data( $last_season_value,"")['total'];
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
        $last_season_value = "2024-2025";
        $now=date('Y/m/d H:i:s',strtotime('-1 year'));
        $class_rencontres1 = get_final_rencontres_data( $last_season_value,"Quart de finale")['total'];
        $class_rencontres2 = get_final_rencontres_data( $last_season_value,"Final four (Demi-finale)")['total'];
        $class_rencontres3 = get_final_rencontres_data( $last_season_value,"Final four (Finale)")['total'];

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
        }else{
            
                $response[] = array(
                    'id' => 5521,
                    'title' => 'FINAL 4 (DEMI-FINALE 1)',
                    'lieu_rencontre' =>  'Dojo de Paris',
                    'date_de_debut' => "18/01/2025 15:30 pm",
                    'date_timestamp' => 1736296400,
                    'full_date_de_debut' => "samedi 18 janvier 2025",
                    'heure_de_debut' => '15:30',
                    'statut' => "à venir",
                    'phase' => "Final four (Demi-finale)",
                    'journee' => "Final four (Demi-finale)",
                    'duree_combat' =>  '',
                    'equipe_1' => "Vainqueur QF 1",
                    'equipe_2' => "Vainqueur QF 4",
                    'abreviation_equipe_1' =>  "VQF 1",
                    'abreviation_equipe_2' =>  "VQF 4",
                    'logo_principal_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_principal_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_circle_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_circle_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_miniature_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_miniature_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
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
                    'lieu_rencontre' =>  'Dojo de Paris',
                    'date_de_debut' => "18/01/2025 15:30 pm",
                    'date_timestamp' => 1736296400,
                    'full_date_de_debut' => "samedi 18 janvier 2025",
                    'heure_de_debut' => '15:30',
                    'statut' => "à venir",
                    'phase' => "Final four (Demi-finale)",
                    'journee' => "Final four (Demi-finale)",
                    'duree_combat' =>  '',
                    'equipe_1' => "Vainqueur QF 2",
                    'equipe_2' => "Vainqueur QF 3",
                    'abreviation_equipe_1' =>  "VQF 2",
                    'abreviation_equipe_2' =>  "VQF 3",
                    'logo_principal_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_principal_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_circle_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_circle_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_miniature_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                    'logo_miniature_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
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
        }else{
            $response[] = array(
                'id' => 5523,
                'title' => 'FINAL 4 (FINALE)',
                'lieu_rencontre' =>  'Dojo de Paris',
                'date_de_debut' => "18/01/2025 15:30 pm",
                'date_timestamp' => 1736296400,
                'full_date_de_debut' => "samedi 18 janvier 2025",
                'heure_de_debut' => '15:30',
                'statut' => "à venir",
                'phase' => "Final four (Finale)",
                'journee' => "Final four (Finale)",
                'duree_combat' =>  '',
                'equipe_1' => "Vainqueur DF 1",
                'equipe_2' => "Vainqueur DF 2",
                'abreviation_equipe_1' =>  "VDF 1",
                'abreviation_equipe_2' =>  "VDF 2",
                'logo_principal_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_principal_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_circle_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_1' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'logo_miniature_equipe_2' => "http://rimo0631.odns.fr/wp-content/uploads/2024/08/unknown.png",
                'pts_e1' =>  0,
                'pts_e2' =>  0,
                'score_eq_1' =>  0,
                'score_eq_2' =>  0,
                'equipe_gagnante' => '',
                'combats' =>  '',
                
            );
        }

        
       
        // Sort by multiple fields: points_individuels_rencontre (desc), ippons_marqués 
       usort($response, function ($a, $b) {
            if ($a['id'] != $b['id']) {
                return $a['id'] - $b['id'];
            }
            // If ippons_marqués are equal, compare by wazaris_marqués (asc)
            return $a['date_timestamp'] - $b['date_timestamp'];
        });
    
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
            'callback' => 'get_current_rencontres_plugin',
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