jQuery(document).ready(function($) {
    // Logic to handle image selection and multiple selections
    $('.image-checkbox').change(function() {
        $(this).closest('.image-container').toggleClass('selected', this.checked);
    });

 

    
    $('#submit-selected-images').on('click', function(e) {
        e.preventDefault();

        // Récupérer les images sélectionnées
        var selectedImages = [];
        $('.image-checkbox:checked').each(function() {
            selectedImages.push($(this).val());
        });

        // Récupérer les valeurs des champs Judoka 1, Judoka 2 et Saison
        var judoka1 = $('#judoka1').val();
        var judoka2 = $('#judoka2').val();
        var saison = $('#saison').val();

        // Vérifier qu'il y a des images sélectionnées
        if (selectedImages.length === 0) {
            alert('Veuillez sélectionner au moins une image.');
            return;
        }
        console.log({
            action: 'save_related_judokas',
            images: selectedImages,
            judoka1: judoka1,
            judoka2: judoka2,
            saison: saison
        });
        // Envoyer les données via AJAX
        $.ajax({
            url: ajaxurl, // URL d'admin AJAX dans WordPress
            method: 'POST',
            data: {
                action: 'save_related_judokas', // Action pour le hook AJAX
                images: selectedImages,
                judoka1: judoka1,
                judoka2: judoka2,
                saison: saison
            },
            success: function(response) {
                if (response.success) {
                    //alert('Les judokas et la saison ont été associés avec succès aux images.');
                    location.reload();
                } else {
                    alert('Une erreur est survenue lors de l\'association.');
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de l\'association.');
            }
        });
    });

    $(document).ready(function() {
        // Ouvre la lightbox lors du clic sur l'image
        $('.lightbox-trigger').on('click', function() {
            $('#lightbox').css('display', 'flex'); // Affiche la lightbox
            $('#lightbox-img').attr('src', $(this).attr('src')); // Remplace l'image de la lightbox
        });
    
        // Ferme la lightbox
        $('.close').on('click', function() {
            $('#lightbox').css('display', 'none');
        });
    
        // Ferme la lightbox lorsqu'on clique en dehors de l'image
        $('#lightbox').on('click', function(e) {
            if ($(e.target).is('#lightbox')) {
                $(this).css('display', 'none');
            }
        });
    });
    // Sélectionner toutes les images
$('#select-all').on('change', function() {
    if ($(this).is(':checked')) {
        $('.image-checkbox').prop('checked', true);
        $('#deselect-all').prop('checked', false);
    }
});

// Désélectionner toutes les images
$('#deselect-all').on('change', function() {
    if ($(this).is(':checked')) {
        $('.image-checkbox').prop('checked', false);
        $('#select-all').prop('checked', false);
    }
});

$('.select2').select2({
    placeholder: "Sélectionner un judoka",
    allowClear: true
});
    
});