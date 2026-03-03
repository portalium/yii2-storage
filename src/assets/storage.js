function toggleContextMenu(e, id) {
    e.stopPropagation();

    // 1. Close other open menus and reset the z-index of the parent rows
    document.querySelectorAll('[id^="context-menu-"]').forEach(menu => {
        if (menu.id !== 'context-menu-' + id) {
            menu.classList.remove('show');

            // Find the parent row and clear the z-index
            // According to your HTML structure, you can add 'tr' or the main container class
            const parentRow = menu.closest('tr, .file-card, [data-key], .list-view-item');
            if (parentRow) {
                parentRow.style.zIndex = '';
            }
        }
    });

    // 2. Toggle the clicked menu and bring the parent row to the front
    const menu = document.getElementById('context-menu-' + id);
    if (menu) {
        menu.classList.toggle('show');

        const parentRow = menu.closest('tr, .file-card, [data-key], .list-view-item');

        if (menu.classList.contains('show') && parentRow) {
            // If the menu is opened, bring the parent row to the front
            // (For z-index to work, the position value must be relative or absolute)
            if (getComputedStyle(parentRow).position === 'static') {
                parentRow.style.position = 'relative';
            }
            parentRow.style.zIndex = '9999';
        } else if (parentRow) {
            // If the menu is closed, revert it back to its original state
            parentRow.style.zIndex = '';
        }
    }

    // 3. When clicking on empty space, close all menus and reset z-indexes
    const closeContextMenus = function () {
        document.querySelectorAll('[id^="context-menu-"]').forEach(menu => {
            menu.classList.remove('show');

            const parentRow = menu.closest('tr, .file-card, [data-key], .list-view-item');
            if (parentRow) {
                parentRow.style.zIndex = '';
            }
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

    const closeMenus = function () {
        document.querySelectorAll('[id^="context-folder-menu-"]').forEach(menu => {
            menu.classList.remove('show');
        });
        document.removeEventListener('click', closeMenus);
    };

    if (menu && menu.classList.contains('show')) {
        setTimeout(() => {
            document.addEventListener('click', closeMenus);
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