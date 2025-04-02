document.addEventListener('DOMContentLoaded', function () {
    const accessSelect = document.getElementById('accessSelect');
    const accessIcon = document.getElementById('accessIcon');
    const accessText = document.getElementById('accessText');
    const accessDesc = document.getElementById('accessDesc');
    const copyLinkButton = document.getElementById('copyLink');
    const menuTrigger = document.getElementById('menu-trigger');
    const contextMenu = document.getElementById('context-menu');

    menuTrigger.addEventListener('click', function(e) {
        e.stopPropagation();
        contextMenu.classList.toggle('show');
    });

    document.addEventListener('click', function() {
        contextMenu.classList.remove('show');
    });

    contextMenu.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.remove('show');
    });

    accessSelect.addEventListener('change', function () {
        const isPublic = this.value === 'public';
        accessIcon.className = isPublic ? 'fa fa-globe bg-light rounded-circle p-2' : 'fa fa-lock bg-light rounded-circle p-2';
        accessText.innerText = isPublic ? accessText.dataset.public : accessText.dataset.private;
        accessDesc.innerText = isPublic ? accessDesc.dataset.public : accessDesc.dataset.private;
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
