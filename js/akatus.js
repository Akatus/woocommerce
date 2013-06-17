jQuery(function(){

    jQuery('body').on('change', 'input[name=akatus]', function() {

        if (this.value === 'cartao') {
            jQuery('#cartao').show();
            jQuery('#tef').hide();

        } else if (this.value === 'tef') {
            jQuery('#tef').show();
            jQuery('#cartao').hide();

        } else {
            jQuery('#tef').hide();
            jQuery('#cartao').hide();
        }

    });

    jQuery('body').on('click', 'img.bandeira', function() {
        var id = this.dataset.input;
        jQuery('#' + id).trigger('click');
    });

});

