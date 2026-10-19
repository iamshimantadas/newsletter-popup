jQuery(function ($) {

    // Auto-dismiss admin notices
    setTimeout(function () {
        $('.notice.is-dismissible').fadeOut();
    }, 6000);

    // Copy shortcode
    $(document).on('click', '.np-copy-btn', function () {
        const text = $(this).data('target');
        if (!text) return;
        const $tmp = $('<textarea>').val(text).appendTo('body').select();
        document.execCommand('copy');
        $tmp.remove();
        const $btn = $(this);
        const old = $btn.text();
        $btn.text('Copied!').prop('disabled', true);
        setTimeout(function () { $btn.text(old).prop('disabled', false); }, 1200);
    });

    /* =========================================================
       TEMPLATE PICKER FOR CAMPAIGN EDITOR
    ========================================================= */
    if (typeof NP_ADMIN !== 'undefined' && NP_ADMIN.templates) {

        const templates = NP_ADMIN.templates;
        const $select = $('.np-template-select');

        if ($select.length) {
            Object.keys(templates).forEach(function (key) {
                const t = templates[key];
                $select.append($('<option>').val(key).text(t.label));
            });
        }

        $(document).on('click', '.np-template-apply', function () {

            const key = $select.val();
            if (!key) { alert('Please choose a template first.'); return; }
            const t = templates[key];
            if (!t) return;

            if (!confirm('Replace the current subject and body with the "' + t.label + '" template?')) return;

            $('#subject').val(t.subject);

            if (typeof tinymce !== 'undefined' && tinymce.get('body')) {
                tinymce.get('body').setContent(t.body);
            } else {
                $('#body').val(t.body);
            }
        });
    }
});