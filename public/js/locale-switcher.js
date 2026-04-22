(function () {
    function post(locale) {
        var token = document.querySelector('meta[name="csrf-token"]');
        if (!token) return;

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/locale';
        form.style.display = 'none';

        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = token.getAttribute('content');
        form.appendChild(csrf);

        var field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'locale';
        field.value = locale;
        form.appendChild(field);

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-lang]');
        if (!link) return;
        e.preventDefault();
        post(link.getAttribute('data-lang'));
    });
})();
