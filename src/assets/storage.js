function toggleContextMenu(e, id) {
    e.stopPropagation();

    document.querySelectorAll('[id^="context-menu-"]').forEach(menu => {
        if (menu.id !== 'context-menu-' + id) {
            menu.classList.remove('show');
        }
    });
    const menu = document.getElementById('context-menu-' + id);
    if (menu) {
        menu.classList.toggle('show');
    }
    const closeContextMenus = function(event) {
        document.querySelectorAll('[id^="context-menu-"]').forEach(menu => {
            menu.classList.remove('show');
        });
        document.removeEventListener('click', closeContextMenus);
    };

    if (menu && menu.classList.contains('show')) {
        setTimeout(() => {
            document.addEventListener('click', closeContextMenus);
        }, 0);
    }
}
function toggleFolderMenu(e, id) {
    e.stopPropagation();

    document.querySelectorAll('[id^="context-folder-menu-"]').forEach(menu => {
        if (menu.id !== 'context-folder-menu-' + id) {
            menu.classList.remove('show');
        }
    });

    const menu = document.getElementById('context-folder-menu-' + id);
    if (menu) {
        menu.classList.toggle('show');
    }

    const closeFolderMenus = function(event) {
        document.querySelectorAll('[id^="context-folder-menu-"]').forEach(menu => {
            menu.classList.remove('show');
        });
        document.removeEventListener('click', closeFolderMenus);
    };

    if (menu && menu.classList.contains('show')) {
        setTimeout(() => {
            document.addEventListener('click', closeFolderMenus);
        }, 0);
    }
}
function handleAccessChange(selectElement) {
    const isPublic = selectElement.value === 'public';
    const container = selectElement.closest('.file-access');
    const icon = container.querySelector('.access-icon');
    const text = container.querySelector('.access-text');
    const desc = container.querySelector('.access-desc');

    icon.className = isPublic
        ? 'access-icon fa fa-globe bg-light rounded-circle p-2'
        : 'access-icon fa fa-lock bg-light rounded-circle p-2';

    text.innerText = isPublic ? text.dataset.public : text.dataset.private;
    desc.innerText = isPublic ? desc.dataset.public : desc.dataset.private;
}
function handleCopyLink(button) {
    const originalText = button.innerHTML;
    const copiedMessage = button.dataset.copied;
    const copiedText = '<i class="fa fa-check me-2"></i>' + copiedMessage;

    button.innerHTML = copiedText;
    button.classList.add('btn-success');
    button.classList.remove('btn-outline-secondary');

    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}