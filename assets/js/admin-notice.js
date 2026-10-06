jQuery(function ($) {
  $(document).on('click', '.happyvr-admin-notice.is-dismissible .notice-dismiss', function () {
    $.post(yalogica_happyvr_global_admin_notice.ajaxurl, {
      action: 'happyvr_dismiss_admin_notice',
      nonce: yalogica_happyvr_global_admin_notice.nonce,
    });
  });
});