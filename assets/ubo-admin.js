/* UBO Central Dashboard — Admin JS */
(function($) {
    'use strict';

    /* ── AJAX Live Search ── */
    function initLiveSearch() {
        var $input = $('#ubo-live-search');
        if ( ! $input.length ) return;

        var $suggestions = $('#ubo-search-suggestions');
        var $form        = $input.closest('form');
        var timer        = null;
        var lastVal      = '';

        function showLoading() {
            $suggestions.html('<div class="ubo-suggestion-loading"><span class="ubo-spinner"></span> Searching…</div>').addClass('active');
        }

        function hideSuggestions() {
            $suggestions.removeClass('active').empty();
        }

        function doSearch(val) {
            if ( val.length < 2 ) { hideSuggestions(); return; }
            if ( val === lastVal ) return;
            lastVal = val;
            showLoading();

            $.ajax({
                url: uboAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action:   'ubo_search_skus',
                    nonce:    uboAdmin.nonce,
                    query:    val,
                    page:     uboAdmin.currentPage,
                },
                success: function(res) {
                    if ( ! res.success || ! res.data.length ) {
                        $suggestions.html('<div class="ubo-suggestion-loading">No results found.</div>').addClass('active');
                        return;
                    }
                    var html = '';
                    $.each(res.data, function(i, item) {
                        html += '<div class="ubo-suggestion-item" data-value="' + $('<div>').text(item.sku).html() + '">' +
                            '<span class="ubo-sug-name">' + $('<div>').text(item.name).html() + '</span>' +
                            '<span class="ubo-sug-sku">' + $('<div>').text(item.sku).html() + '</span>' +
                            '</div>';
                    });
                    $suggestions.html(html).addClass('active');
                },
                error: function() {
                    hideSuggestions();
                }
            });
        }

        // Trigger on input with debounce
        $input.on('input', function() {
            var val = $(this).val().trim();
            clearTimeout(timer);
            if ( val.length < 2 ) { hideSuggestions(); lastVal = ''; return; }
            timer = setTimeout(function() { doSearch(val); }, 280);
        });

        // Click suggestion — fill input and submit
        $(document).on('click', '.ubo-suggestion-item', function() {
            $input.val( $(this).data('value') );
            hideSuggestions();
            $form.submit();
        });

        // Hide on outside click
        $(document).on('click', function(e) {
            if ( ! $(e.target).closest('.ubo-search-wrap').length ) hideSuggestions();
        });

        // Submit on Enter
        $input.on('keydown', function(e) {
            if ( e.key === 'Enter' ) { hideSuggestions(); $form.submit(); }
        });
    }

    /* ── Auto-submit dropdowns ── */
    function initAutoFilters() {
        $(document).on('change', '.ubo-auto-filter', function() {
            $(this).closest('form').submit();
        });
    }

    /* ── Reserved stock AJAX save ── */
    function initReservedAjax() {
        $(document).on('submit', '.ubo-reserved-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn  = $form.find('.ubo-save-btn');
            var orig  = $btn.text();
            $btn.text('…').prop('disabled', true);

            $.ajax({
                url: uboAdmin.ajaxUrl,
                method: 'POST',
                data: $form.serialize() + '&action=ubo_save_reserved',
                success: function(res) {
                    if ( res.success ) {
                        $btn.text('✓').css('color','#16a34a');
                        setTimeout(function() { $btn.text(orig).css('color','').prop('disabled', false); }, 1500);
                    } else {
                        $btn.text('!').css('color','#dc2626');
                        setTimeout(function() { $btn.text(orig).css('color','').prop('disabled', false); }, 1500);
                    }
                },
                error: function() {
                    $btn.text(orig).prop('disabled', false);
                }
            });
        });
    }

    $(function() {
        initLiveSearch();
        initAutoFilters();
        initReservedAjax();
    });

})(jQuery);
