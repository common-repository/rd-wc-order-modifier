jQuery(document).ready(function() {
    RDWCOMManager.load();
});

function RDWCOMManager() {}

RDWCOMManager.load = function() {
    RDWCOMManager.addToRefundShowAction();
    RDWCOMManager.addToRefundCancelAction();
    RDWCOMManager.addToBlurActions();
    RDWCOMManager.adjustRefundTotal();
    RDWCOMManager.adjustTotalBasedOnQuantity();
    RDWCOMManager.hideReviewUpgradeNotice();
    RDWCOMManager.hideTaxItemRequiredNotice();
}

RDWCOMManager.addToRefundShowAction = function() {
    //Add to refund show action
    jQuery('#woocommerce-order-items').on('click', 'button.refund-items', function() {
        jQuery('#woocommerce-order-items').find('div.rdwcom-refund').show();
    });
}

RDWCOMManager.addToRefundCancelAction = function() {
    //Add to refund cancel action
    jQuery('#woocommerce-order-items').on('click', 'button.cancel-action', function() {
        jQuery('#woocommerce-order-items' ).find('div.rdwcom-refund').hide();
        jQuery('.woocommerce_order_items .rdwcom-refund .rdwcom_refund_line_total').each(function() {
            jQuery(this).val('');
        });
    });
}

RDWCOMManager.addToBlurActions = function() {
    //Add to blur actions
    jQuery('#woocommerce-order-items').on('change', '.refund_order_item_qty,.refund_line_total,.refund_line_tax', function() {
        let tr = jQuery(this).parentsUntil('tr').parent();
        jQuery(tr).find('.rdwcom-refund .rdwcom_refund_line_total').val('');
    });
}

RDWCOMManager.adjustRefundTotal = function() {
    //Adjust refund total
    jQuery('#woocommerce-order-items').on('blur', 'input.rdwcom_refund_line_total', function() {
        let tr = jQuery(this).parentsUntil('tr').parent();
        jQuery(tr).find('.refund_order_item_qty,.refund_line_total,.refund_line_tax').val('');

        var refund_amount = 0;
        var $items        = jQuery('.woocommerce_order_items' ).find( 'tr.item, tr.fee, tr.shipping');

        $items.each(function() {
            var $row               = jQuery( this );
            var refund_cost_fields = $row.find('.rdwcom-refund .rdwcom_refund_line_total');

            refund_cost_fields.each(function( index, el ) {
                refund_amount += parseFloat( accounting.unformat( jQuery( el ).val() || 0, woocommerce_admin.mon_decimal_point ) );
            });
        });

        jQuery( '#refund_amount' )
            .val( accounting.formatNumber(
                refund_amount,
                woocommerce_admin_meta_boxes.currency_format_num_decimals,
                '',
                woocommerce_admin.mon_decimal_point
            ) )
            .trigger( 'change' );
    });
}

RDWCOMManager.adjustTotalBasedOnQuantity = function() {
    //Adjust total based on quantity
    jQuery('#woocommerce-order-items').on('change', '#order_line_items .quantity', function() {
        let quantity = parseInt(jQuery(this).val());
        quantity = (isNaN(quantity)) ? 0 : quantity;
        let subtotal_input = jQuery(this).parentsUntil('tr.item').parent().find('.rdwcom_line_incl_subtotal').first();
        let total_input = jQuery(this).parentsUntil('tr.item').parent().find('.rdwcom_line_incl_total').first();
        if (subtotal_input && total_input) {
            let subtotal = parseFloat(jQuery(subtotal_input).attr('data-subtotal'));
            subtotal = (isNaN(subtotal)) ? 0 : quantity * subtotal;
            let total = parseFloat(jQuery(total_input).attr('data-total'));
            total = (isNaN(total)) ? 0 : quantity * total;
            jQuery(subtotal_input).val(subtotal);
            jQuery(total_input).val(total);
        }
    });
}

RDWCOMManager.hideReviewUpgradeNotice = function() {
    jQuery('#wpbody-content').on('click', '#rdwcom-review-upgrade-notice .rdwcom-hide-notice', function(event) {
        event.preventDefault();
        jQuery('#rdwcom-review-upgrade-notice .notice-dismiss').click();
        let data = {
            action: 'rdwcom_hide_review_upgrade_notice',
            _ajax_nonce: RDWCOMSettings.ajax_nonce,
        }
            
        jQuery.post(ajaxurl, data, function(response) {});
    });
}

RDWCOMManager.hideTaxItemRequiredNotice = function() {
    jQuery('#wpbody-content').on('click', '#rdwcom-tax-item-required-notice .rdwcom-hide-notice', function(event) {
        event.preventDefault();
        jQuery('#rdwcom-tax-item-required-notice .notice-dismiss').click();
        let data = {
            action: 'rdwcom_hide_tax_item_required_notice',
            _ajax_nonce: RDWCOMSettings.ajax_nonce,
        }
            
        jQuery.post(ajaxurl, data, function(response) {});
    });
}