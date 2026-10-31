/**
 * Comment Likes — sends one AJAX like per button, then locks the button.
 * Works without jQuery and without a page reload.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wcl-like-button').forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.classList.contains('wcl-liked') || button.disabled) {
                return;
            }
            button.disabled = true;

            var form = new FormData();
            form.append('action', 'wcl_like');
            form.append('nonce', wclData.nonce);
            form.append('comment_id', button.getAttribute('data-comment-id'));

            fetch(wclData.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.success) {
                        button.classList.add('wcl-liked');
                        button.setAttribute('aria-pressed', 'true');
                        var count = button.querySelector('.wcl-count');
                        if (count) {
                            count.textContent = data.data.count;
                        }
                        button.childNodes[0].textContent = '\u2714 Liked ';
                    } else {
                        button.disabled = false;
                    }
                })
                .catch(function () {
                    button.disabled = false;
                });
        });
    });
});
