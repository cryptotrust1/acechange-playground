/**
 * Admin JavaScript pre AceChange SEO Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Počítadlo znakov pre meta description
         */
        const metaDescriptionField = $('#acechange_meta_description');

        if (metaDescriptionField.length) {
            // Vytvorenie počítadla
            const counter = $('<p class="description" id="acechange-char-counter"></p>');
            metaDescriptionField.after(counter);

            // Funkcia na aktualizáciu počítadla
            function updateCharCounter() {
                const length = metaDescriptionField.val().length;
                let color = '#666';
                let message = '';

                if (length === 0) {
                    message = 'Odporúčané: 150-160 znakov';
                } else if (length < 120) {
                    color = '#d97706';
                    message = `${length} znakov - príliš krátke (min. 120)`;
                } else if (length <= 160) {
                    color = '#059669';
                    message = `${length} znakov - optimálne ✓`;
                } else if (length <= 200) {
                    color = '#d97706';
                    message = `${length} znakov - dlhé (Google obreže)`;
                } else {
                    color = '#dc2626';
                    message = `${length} znakov - príliš dlhé!`;
                }

                counter.html(`<span style="color: ${color}; font-weight: bold;">${message}</span>`);
            }

            // Aktualizácia pri písaní
            metaDescriptionField.on('input', updateCharCounter);

            // Prvotná aktualizácia
            updateCharCounter();
        }

        /**
         * Tooltip pre nastavenia
         */
        $('.acechange-seo-admin .description').each(function() {
            $(this).css({
                'font-style': 'italic',
                'color': '#666'
            });
        });

        /**
         * Potvrdenie pred uložením nastavení
         */
        $('.acechange-seo-admin form').on('submit', function() {
            const autoSitemap = $('#auto_sitemap').is(':checked');

            if (autoSitemap) {
                console.log('XML Sitemap bude dostupná na: ' + window.location.origin + '/sitemap.xml');
            }

            return true;
        });

        /**
         * Interaktívne zvýraznenie dôležitých nastavení
         */
        const recommendedSettings = [
            'auto_meta_tags',
            'auto_open_graph',
            'auto_schema',
            'auto_sitemap',
            'canonical_urls',
            'twitter_card'
        ];

        recommendedSettings.forEach(function(settingId) {
            const checkbox = $('#' + settingId);
            if (checkbox.length && !checkbox.is(':checked')) {
                checkbox.closest('tr').css({
                    'background-color': '#fffbeb',
                    'border-left': '3px solid #f59e0b'
                });
            }
        });
    });

})(jQuery);
