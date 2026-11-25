<link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
<style>
.select2-container{font:inherit}
.select2-container--default .select2-selection--single{height:42px;border:1px solid var(--line);border-radius:10px;background:#fff;display:flex;align-items:center}
.select2-container--default .select2-selection--single .select2-selection__rendered{color:var(--ink);line-height:1.2;padding-left:12px;padding-right:32px;font-weight:500}
.select2-container--default .select2-selection--single .select2-selection__placeholder{color:var(--muted)}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:40px;right:6px}
.select2-container--default .select2-selection--single .select2-selection__clear{margin-right:8px;color:var(--muted)}
.select2-container--default.select2-container--focus .select2-selection--single,.select2-container--default.select2-container--open .select2-selection--single{border-color:var(--green)}
.select2-dropdown{border:1px solid var(--line);border-radius:12px;overflow:hidden;box-shadow:var(--shadow);z-index:1100}
.select2-search--dropdown .select2-search__field{border:1px solid var(--line);border-radius:8px;padding:8px 10px;font:inherit}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{background:var(--green);color:#fff}
.select2-container--default .select2-results__option--selected{background:var(--mint);color:var(--ink)}
.select2-container--default.select2-container--disabled .select2-selection--single{background:var(--paper);opacity:.8}
.hn-logfilter .select2-container{min-width:180px}
</style>
<script src="{{ asset('vendor/select2/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
<script src="{{ asset('vendor/select2/id.js') }}"></script>
<script>
jQuery(function ($) {
    // Di dalam <dialog> modal, dropdown harus menempel ke dialog agar tampil di atasnya.
    $('select.js-select2').each(function () {
        var $el = $(this), $dialog = $el.closest('dialog');
        $el.select2({
            width: '100%',
            language: 'id',
            placeholder: $el.data('placeholder') || '',
            allowClear: !!$el.data('allow-clear'),
            dropdownParent: $dialog.length ? $dialog : $(document.body),
        });
    });
});
</script>
