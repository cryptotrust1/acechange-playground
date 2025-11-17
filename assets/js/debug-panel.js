/**
 * AI SEO Manager - Debug Panel JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Already implemented inline in the view file
        // This file is for future enhancements

        // Auto-refresh functionality (optional)
        var autoRefresh = false;
        var refreshInterval;

        // Add refresh button if needed
        function initAutoRefresh() {
            if (!autoRefresh) return;

            refreshInterval = setInterval(function() {
                location.reload();
            }, 30000); // Refresh every 30 seconds
        }

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });

        // Highlight search terms
        function highlightSearchTerms() {
            var searchTerm = $('#log-search').val();
            if (!searchTerm) return;

            $('.ai-seo-debug-logs td').each(function() {
                var text = $(this).text();
                if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
                    var regex = new RegExp('(' + searchTerm + ')', 'gi');
                    var highlighted = text.replace(regex, '<mark>$1</mark>');
                    $(this).html(highlighted);
                }
            });
        }

        // Initialize
        // initAutoRefresh(); // Uncomment to enable auto-refresh
    });

})(jQuery);
