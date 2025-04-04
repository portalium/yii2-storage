
document.addEventListener('DOMContentLoaded', function () {
    const copyLinkButton = document.getElementById('copyLink');
    document.querySelectorAll('[id^="menu-trigger-"]').forEach(trigger => {
        const id = trigger.id.replace('menu-trigger-', '');
        const contextMenu = document.getElementById('context-menu-' + id);

        // Sayfa açılışında menüyü gizle
        contextMenu.classList.remove('show');

        // Tetikleyiciye tıklanınca menüyü aç/kapat
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();

            // Önce diğer açık menüleri kapat
            document.querySelectorAll('[id^="context-menu-"]').forEach(menu => {
                if (menu !== contextMenu) {
                    menu.classList.remove('show');
                }
            });

            // Bu menüyü toggle et
            contextMenu.classList.toggle('show');
        });

        // Menüye tıklanırsa kapanmasın
        contextMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

// Sayfa dışında bir yere tıklanınca tüm menüler kapanır
    document.addEventListener('click', function () {
        document.querySelectorAll('[id^="context-menu-"]').forEach(menu => {
            menu.classList.remove('show');
        });
    });

    document.querySelectorAll('.access-select').forEach(select => {
        select.addEventListener('change', function () {
            const isPublic = this.value === 'public';
            const container = this.closest('.file-access');
            const icon = container.querySelector('.access-icon');
            const text = container.querySelector('.access-text');
            const desc = container.querySelector('.access-desc');

            icon.className = isPublic
                ? 'access-icon fa fa-globe bg-light rounded-circle p-2'
                : 'access-icon fa fa-lock bg-light rounded-circle p-2';

            text.innerText = isPublic ? text.dataset.public : text.dataset.private;
            desc.innerText = isPublic ? desc.dataset.public : desc.dataset.private;
        });
    });



    copyLinkButton.addEventListener('click', function () {
        const originalText = this.innerHTML;
        const copiedMessage = this.dataset.copied;

        const copiedText = '<i class="fa fa-check me-2"></i>' + copiedMessage;

        this.innerHTML = copiedText;
        this.classList.add('btn-success');
        this.classList.remove('btn-outline-secondary');

        setTimeout(() => {
            this.innerHTML = originalText;
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-secondary');
        }, 2000);
    });
});
