// Highlight the item in the page when a result is clicked

jQuery(document).ready(function() {

    jQuery(document).on('click','.dcs-elementor-render-time-result',function(e){

        /* Reset all the current highlighted items */
        jQuery('.elementor-element').removeClass('dcs-speed-container-selected');

        /* Find the element in the page, based on the item that was clicked in the render data (see data-target) */
        let widget = jQuery( '.elementor-element-' + jQuery(this).data('target') );
        widget.addClass('dcs-speed-container-selected');

        /* Scroll to the widget */
        jQuery([document.documentElement, document.body]).animate({
            scrollTop: widget.offset().top
        }, 250);

    });

    jQuery(document).on('click','#wp-admin-bar-elementor_widget_inspector',function(e){

        if( jQuery('#dcs-elementor-speed-results').hasClass( 'widget-inspector-active-show-sidebar' )) {
            jQuery('.elementor').removeClass('widget-inspector-active-resize-elementor');
            jQuery('#dcs-elementor-speed-results').removeClass('widget-inspector-active-show-sidebar');
        } else {
            jQuery('.elementor').addClass('widget-inspector-active-resize-elementor');
            jQuery('#dcs-elementor-speed-results').addClass('widget-inspector-active-show-sidebar');
        }

    });

    jQuery(document).on('click','.show-active-settings',function(e){

        e.preventDefault();

        let settings = jQuery( '.active-settings-' + jQuery(this).data('target') );

        if(settings.hasClass( 'show-active-settings' )) {
            settings.removeClass('show-active-settings');
        } else {
            settings.addClass('show-active-settings');
        }
        
    });

});
