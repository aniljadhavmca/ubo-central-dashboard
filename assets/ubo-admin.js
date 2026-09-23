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

    /* ── Inventory: expand/collapse variations ── */
    function initInventoryExpand() {
        $(document).on('click', '.ubo-expand-btn', function() {
            var pid  = $(this).data('pid');
            var $row = $('#ubo-vars-' + pid);
            var open = $row.is(':visible');
            $row.toggle( ! open );
            $(this).text( open ? '▶' : '▼' );
        });
    }

    /* ── Inventory: Edit modal ── */
    function initInventoryEdit() {
        var $overlay   = $('#ubo-edit-overlay');
        var $saveBtn   = $('#ubo-modal-save');
        var origQty    = null;

        function openModal( data ) {
            origQty = data.qty !== '' ? parseInt( data.qty, 10 ) : null;

            // Store all state in hidden inputs — never read from display elements
            $('#ubo-edit-id').val( data.id );
            $('#ubo-edit-type').val( data.type );
            $('#ubo-edit-parent').val( data.parent );
            $('#ubo-edit-site').val( data.site );
            $('#ubo-edit-orig-qty').val( data.qty );
            $('#ubo-edit-sku').val( data.sku );
            $('#ubo-edit-orig-price').val( data.price );
            $('#ubo-edit-orig-sale').val( data.sale );

            $('#ubo-modal-subtitle').text( data.name );
            $('#ubo-modal-sku').text( data.sku || 'No SKU' );
            $('#ubo-modal-store').text( data.site === 'US' ? '🇺🇸 US' : '🇮🇳 India' )
                .removeClass('ubo-store-us ubo-store-india')
                .addClass( data.site === 'US' ? 'ubo-store-us' : 'ubo-store-india' );

            $('#ubo-edit-price').val( data.price );
            $('#ubo-edit-sale').val( data.sale );
            $('#ubo-edit-qty').val( data.qty );
            $('#ubo-edit-reason').val('');
            $('#ubo-edit-note').val('');
            $('#ubo-modal-notice').hide().empty();

            var hint = origQty !== null ? 'Current: ' + origQty + ' units' : 'No stock tracking set';
            $('#ubo-qty-hint').text( hint );

            if ( data.noSku ) {
                // Show red banner, blur + disable all fields, hide Save
                $('#ubo-modal-notice')
                    .attr('class', 'ubo-notice ubo-notice-error')
                    .html('⚠️ No SKU assigned to this product. Please add a SKU in WooCommerce before editing.')
                    .show();
                $('.ubo-modal-fields input, .ubo-modal-fields select, .ubo-modal-fields textarea')
                    .prop('disabled', true);
                $('.ubo-modal').addClass('ubo-modal-locked');
                $saveBtn.hide();
            } else {
                $('.ubo-modal-fields input, .ubo-modal-fields select, .ubo-modal-fields textarea')
                    .prop('disabled', false);
                $('.ubo-modal').removeClass('ubo-modal-locked');
                $saveBtn.show();
                toggleReasonField();
            }

            $overlay.fadeIn( 150 );
        }

        function closeModal() {
            $overlay.fadeOut( 120 );
        }

        function toggleReasonField() {
            var newQty = $('#ubo-edit-qty').val();
            var changed = newQty !== '' && origQty !== null && parseInt( newQty, 10 ) !== origQty;
            $('#ubo-reason-field').toggle( changed );
        }

        function showNotice( msg, type ) {
            var cls = type === 'success' ? 'ubo-notice-success' : 'ubo-notice-error';
            $('#ubo-modal-notice').attr('class', 'ubo-notice ' + cls).html( msg ).show();
        }

        // Open on Edit button click
        $(document).on('click', '.ubo-edit-btn', function() {
            var sku = $(this).data('sku');
            openModal({
                id:     $(this).data('id'),
                type:   $(this).data('type'),
                parent: $(this).data('parent'),
                site:   $(this).data('site'),
                name:   $(this).data('name'),
                sku:    sku,
                price:  $(this).data('price'),
                sale:   $(this).data('sale'),
                qty:    $(this).data('qty'),
                noSku:  ! sku || sku === '',
            });
        });

        // Toggle reason field as qty changes
        $(document).on('input', '#ubo-edit-qty', toggleReasonField );

        // Close
        $(document).on('click', '#ubo-modal-close, #ubo-modal-cancel', closeModal );
        $(document).on('click', '#ubo-edit-overlay', function(e) {
            if ( $(e.target).is('#ubo-edit-overlay') ) closeModal();
        });
        $(document).on('keydown', function(e) {
            if ( e.key === 'Escape' ) closeModal();
        });

        // Save
        $(document).on('click', '#ubo-modal-save', function() {
            var newQty    = $('#ubo-edit-qty').val().trim();
            var qtyInt    = newQty !== '' ? parseInt( newQty, 10 ) : null;
            var qtyChanged = qtyInt !== null && origQty !== null && qtyInt !== origQty;

            if ( qtyChanged && ! $('#ubo-edit-reason').val() ) {
                showNotice( '⚠️ Please select a reason for the quantity change.', 'error' );
                $('#ubo-edit-reason').focus();
                return;
            }

            $saveBtn.text('Saving…').prop('disabled', true);
            $('#ubo-modal-notice').hide();

            // Only send fields that actually changed
            var postData = {
                action:    'ubo_update_product',
                nonce:     uboAdmin.nonce,
                id:        $('#ubo-edit-id').val(),
                type:      $('#ubo-edit-type').val(),
                parent_id: $('#ubo-edit-parent').val(),
                site:      $('#ubo-edit-site').val(),
                sku:       $('#ubo-edit-sku').val(),
                orig_qty:  $('#ubo-edit-orig-qty').val(),
                reason:    $('#ubo-edit-reason').val(),
                note:      $('#ubo-edit-note').val(),
            };
            var newPrice = $('#ubo-edit-price').val().trim();
            var newSale  = $('#ubo-edit-sale').val().trim();
            if ( newPrice !== $('#ubo-edit-orig-price').val() ) postData.price      = newPrice;
            if ( newSale  !== $('#ubo-edit-orig-sale').val()  ) postData.sale_price = newSale;
            if ( newQty   !== '' )                              postData.qty        = newQty;

            $.ajax({
                url:    uboAdmin.ajaxUrl,
                method: 'POST',
                data:   postData,
                success: function( res ) {
                    if ( res.success ) {
                        showNotice( '✅ ' + res.data.message, 'success' );
                        // Update the row in the table without page reload
                        var id   = $('#ubo-edit-id').val();
                        var type = $('#ubo-edit-type').val();
                        var $btn = type === 'variation'
                            ? $('[data-id="' + id + '"][data-type="variation"]')
                            : $('[data-id="' + id + '"][data-type="product"]');
                        if ( newQty !== '' ) {
                            $btn.data('qty', newQty).data('orig-qty', newQty);
                            origQty = qtyInt;
                            $('#ubo-edit-orig-qty').val( newQty );
                            $('#ubo-qty-hint').text('Current: ' + newQty + ' units');
                        }
                        if ( $('#ubo-edit-price').val() ) $btn.data('price', $('#ubo-edit-price').val());
                        if ( $('#ubo-edit-sale').val() !== undefined ) $btn.data('sale', $('#ubo-edit-sale').val());
                        setTimeout( closeModal, 1200 );
                    } else {
                        showNotice( '❌ ' + ( res.data.message || 'Update failed.' ), 'error' );
                    }
                },
                error: function() {
                    showNotice( '❌ Request failed. Please try again.', 'error' );
                },
                complete: function() {
                    $saveBtn.text('💾 Save Changes').prop('disabled', false);
                }
            });
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
                data:   $form.serialize() + '&action=ubo_save_reserved&nonce=' + encodeURIComponent( uboAdmin.nonce ),
                success: function( res ) {
                    if ( res.success ) {
                        $btn.text('✓').css('color','#16a34a');

                        // Recalculate Available cell in this row
                        var site      = $form.data('site');
                        var newRes    = parseInt( $form.find('[name="reserved_qty"]').val(), 10 ) || 0;
                        var $row      = $form.closest('tr');
                        var usStock   = parseInt( $row.data('us-stock'), 10 ) || 0;
                        var inStock   = parseInt( $row.data('in-stock'), 10 ) || 0;

                        // Get the OTHER store's current reserved from its input
                        var otherRes  = 0;
                        var $otherForm;
                        if ( site === 'US' ) {
                            $otherForm = $row.find('.ubo-reserved-form[data-site="India"]');
                        } else {
                            $otherForm = $row.find('.ubo-reserved-form[data-site="US"]');
                        }
                        if ( $otherForm.length ) {
                            otherRes = parseInt( $otherForm.find('[name="reserved_qty"]').val(), 10 ) || 0;
                        }

                        var usRes    = site === 'US'    ? newRes : otherRes;
                        var inRes    = site === 'India' ? newRes : otherRes;
                        var usAvail  = Math.max( 0, usStock - usRes );
                        var inAvail  = Math.max( 0, inStock - inRes );
                        var total    = usAvail + inAvail;

                        $row.find('.qty-avail').text( total );

                        // Update status badge
                        var threshold = parseInt( $row.closest('.ubo-table-wrap').data('ubo-threshold') || 10, 10 );
                        var $badge    = $row.find('.ubo-badge');
                        if ( total === 0 ) {
                            $badge.attr('class','ubo-badge ubo-stock-out').text('Out of Stock');
                        } else if ( total <= threshold ) {
                            $badge.attr('class','ubo-badge ubo-stock-low').text('Low Stock');
                        } else {
                            $badge.attr('class','ubo-badge ubo-stock-ok').text('In Stock');
                        }

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
        initInventoryExpand();
        initInventoryEdit();
        initReservedAjax();
    });

})(jQuery);
