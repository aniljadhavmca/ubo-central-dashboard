/* UBO Central Dashboard — Admin JS */
(function($) {
    'use strict';

    /* ── Generic live search builder ── */
    function buildLiveSearch( $input, $suggestions, opts ) {
        // opts: { minChars, debounce, getSite, onSelect }
        var timer   = null;
        var lastVal = '';

        function showLoading() {
            $suggestions.html('<div class="ubo-suggestion-loading"><span class="ubo-spinner"></span> Searching…</div>').addClass('active');
        }
        function hide() {
            $suggestions.removeClass('active').empty();
        }

        function doSearch( val ) {
            if ( val.length < ( opts.minChars || 1 ) ) { hide(); return; }
            showLoading();
            lastVal = val;

            var data = {
                action: 'ubo_search_skus',
                nonce:  uboAdmin.nonce,
                query:  val,
            };
            if ( opts.getSite ) {
                var site = opts.getSite();
                if ( site ) data.site = site;
            }

            $.ajax({
                url:    uboAdmin.ajaxUrl,
                method: 'POST',
                data:   data,
                success: function( res ) {
                    if ( ! res.success || ! res.data.length ) {
                        $suggestions.html('<div class="ubo-suggestion-loading">No results found.</div>').addClass('active');
                        return;
                    }
                    var html = '';
                    $.each( res.data, function( i, item ) {
                        html += '<div class="ubo-suggestion-item" data-sku="' + $('<div>').text( item.sku ).html() + '" data-name="' + $('<div>').text( item.name ).html() + '">' +
                            '<span class="ubo-sug-name">'  + $('<div>').text( item.name ).html() + '</span>' +
                            '<span class="ubo-sug-sku">'   + $('<div>').text( item.sku  ).html() + '</span>' +
                            '</div>';
                    });
                    $suggestions.html( html ).addClass('active');
                },
                error: function() { hide(); }
            });
        }

        $input.on('input', function() {
            var val = $(this).val().trim();
            clearTimeout( timer );
            if ( val.length < ( opts.minChars || 1 ) ) { hide(); lastVal = ''; return; }
            // Always fire — don't skip if same value (user may have cleared and retyped)
            timer = setTimeout( function() { doSearch( val ); }, opts.debounce || 280 );
        });

        // Scoped click: only fire for suggestions inside THIS suggestions container
        $suggestions.on('click', '.ubo-suggestion-item', function() {
            var sku  = $(this).data('sku');
            var name = $(this).data('name');
            $input.val( sku );
            lastVal = '';
            hide();
            if ( opts.onSelect ) opts.onSelect( sku, name );
        });

        $(document).on('click', function(e) {
            if ( ! $(e.target).closest( $suggestions.closest('.ubo-search-wrap') ).length ) hide();
        });

        $input.on('keydown', function(e) {
            if ( e.key === 'Enter' ) { hide(); }
        });

        return { hide: hide, resetLast: function() { lastVal = ''; } };
    }

    /* ── SKU Central live search ── */
    function initSkuCentralSearch() {
        var $input       = $('#ubo-live-search');
        var $suggestions = $('#ubo-search-suggestions');
        var $skuExact    = $('#ubo-sku-exact');
        var $form        = $input.closest('form');
        if ( ! $input.length ) return;

        buildLiveSearch( $input, $suggestions, {
            minChars: 1,
            debounce: 300,
            onSelect: function( sku ) {
                // Put the SKU in the hidden exact-match field, clear name search
                $skuExact.val( sku );
                $input.val( sku );   // keep visible so user sees what they picked
                $form.submit();
            }
        });

        // On manual Enter: treat typed value as name search, clear sku exact
        $input.on('keydown', function(e) {
            if ( e.key === 'Enter' ) {
                $skuExact.val('');
                $suggestions.removeClass('active').empty();
                $form.submit();
            }
        });

        // When user types, clear the hidden sku exact so it doesn't override
        $input.on('input', function() {
            $skuExact.val('');
        });
    }

    /* ── Adjustments SKU live search + validation ── */
    function initAdjustmentsSearch() {
        var $input       = $('#ubo-adj-sku');
        var $suggestions = $('#ubo-adj-suggestions');
        var $siteSelect  = $('#ubo-adj-site');
        var $validation  = $('#ubo-sku-validation');
        var $submitBtn   = $('#ubo-adj-submit');
        if ( ! $input.length ) return;

        var validationTimer = null;
        var skuValid        = false;  // tracks current validation state

        function setValid( msg ) {
            skuValid = true;
            $validation.html( '<span class="ubo-sku-valid">✔ ' + msg + '</span>' );
            $submitBtn.prop( 'disabled', false );
        }
        function setInvalid( msg ) {
            skuValid = false;
            $validation.html( '<span class="ubo-sku-invalid">✖ ' + msg + '</span>' );
            $submitBtn.prop( 'disabled', true );
        }
        function clearValidation() {
            skuValid = false;
            $validation.empty();
            $submitBtn.prop( 'disabled', false ); // allow attempt; server will catch
        }

        function validateSku( sku, site ) {
            if ( ! sku ) { clearValidation(); return; }
            $validation.html( '<span class="ubo-sku-checking"><span class="ubo-spinner"></span> Checking SKU on ' + site + ' store…</span>' );

            $.ajax({
                url:    uboAdmin.ajaxUrl,
                method: 'POST',
                data:   { action: 'ubo_validate_sku', nonce: uboAdmin.nonce, sku: sku, site: site },
                success: function( res ) {
                    res.success ? setValid( res.data.message ) : setInvalid( res.data.message );
                },
                error: function() { clearValidation(); }
            });
        }

        // Live search — scoped to selected store
        buildLiveSearch( $input, $suggestions, {
            minChars: 1,
            debounce: 280,
            getSite:  function() { return $siteSelect.val(); },
            onSelect: function( sku ) {
                validateSku( sku, $siteSelect.val() );
            }
        });

        // Validate on blur
        $input.on('blur', function() {
            clearTimeout( validationTimer );
            var sku = $(this).val().trim();
            if ( sku ) {
                validationTimer = setTimeout( function() {
                    validateSku( sku, $siteSelect.val() );
                }, 300 );
            } else {
                clearValidation();
            }
        });

        // Re-validate when store changes
        $siteSelect.on('change', function() {
            var sku = $input.val().trim();
            if ( sku ) validateSku( sku, $(this).val() );
        });

        // Block submit if explicitly invalid (server also validates, this is UX)
        $('#ubo-adj-form').on('submit', function() {
            var sku = $input.val().trim();
            if ( ! sku ) return true; // let HTML5 required handle it
            // If validation ran and failed, block
            if ( $validation.find('.ubo-sku-invalid').length ) {
                $input.focus();
                return false;
            }
            return true;
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
                url:    uboAdmin.ajaxUrl,
                method: 'POST',
                data:   $form.serialize() + '&action=ubo_save_reserved',
                success: function( res ) {
                    if ( res.success ) {
                        $btn.text('✓').css('color','#16a34a');
                        setTimeout(function() { $btn.text(orig).css('color','').prop('disabled', false); }, 1500);
                    } else {
                        $btn.text('!').css('color','#dc2626');
                        setTimeout(function() { $btn.text(orig).css('color','').prop('disabled', false); }, 1500);
                    }
                },
                error: function() { $btn.text(orig).prop('disabled', false); }
            });
        });
    }

    $(function() {
        initSkuCentralSearch();
        initAdjustmentsSearch();
        initAutoFilters();
        initReservedAjax();
    });

})(jQuery);
