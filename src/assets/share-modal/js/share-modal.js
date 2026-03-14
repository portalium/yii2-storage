/**
 * Share Modal JavaScript
 * Handles file/directory/storage sharing functionality
 */

(function () {
    'use strict';

    let shareToastInstance = null;

    /**
     * Show toast notification
     */
    window.showShareToast = function (message, success = true) {
        const toastEl = document.getElementById('shareToast');
        const toastBody = document.getElementById('shareToastBody');

        if (!toastEl || !toastBody) return;

        toastBody.textContent = message;

        toastEl.classList.remove('text-bg-success', 'text-bg-danger');
        toastEl.classList.add(success ? 'text-bg-success' : 'text-bg-danger');

        if (shareToastInstance) {
            shareToastInstance.hide();
            shareToastInstance.dispose();
        }

        shareToastInstance = new bootstrap.Toast(toastEl, { delay: 2500 });
        shareToastInstance.show();
    };

    /**
     * Create a new share
     */
    window.createShare = function (type) {
        console.log('Creating share of type:', type);
        const config = window.shareConfig;
        if (!config) return;

        let data = {};

        if (config.idStorage) {
            data.id_storage = config.idStorage;
        } else if (config.idDirectory) {
            data.id_directory = config.idDirectory;
        } else if (config.idUserOwner) {
            data.id_user_owner = config.idUserOwner;
        }

        if (type === 'user') {
            const userId = document.getElementById('shareUserSelect').value;
            if (!userId) {
                showShareToast(config.messages.selectUser, false);
                return;
            }
            data.shared_with_type = 'user';
            data.id_shared_with = userId;
            data.permission_level = document.getElementById('shareUserPermission').value;
        } else if (type === 'workspace') {
            const workspaceId = document.getElementById('shareWorkspaceSelect').value;
            if (!workspaceId) {
                showShareToast(config.messages.selectWorkspace, false);
                return;
            }
            data.shared_with_type = 'workspace';
            data.id_shared_with = workspaceId;
            data.permission_level = document.getElementById('shareWorkspacePermission').value;
        } else if (type === 'link') {
            data.shared_with_type = 'link';
            data.permission_level = document.getElementById('shareLinkPermission').value;
            const expiry = document.getElementById('shareLinkExpiry').value;
            if (expiry) {
                data.expires_at = expiry;
            }
        }
        data.shareType = config.shareType;
        $.ajax({
            url: config.urls.createShare,
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    if (response.link) {
                        window.generatedShareLink = response.link;
                    }
                    showShareToast(config.messages.shareCreated);
                    refreshSharesList();
                    // Reset form
                    if (type === 'user') document.getElementById('shareUserSelect').value = '';
                    if (type === 'workspace') document.getElementById('shareWorkspaceSelect').value = '';
                } else {
                    showShareToast(response.message || config.messages.error, false);
                }
            },
            error: function () {
                showShareToast(config.messages.error, false);
            }
        });
    };

    /**
     * Revoke a share
     */
    window.revokeShare = function (shareId) {
        const config = window.shareConfig;
        if (!config) return;

        $.ajax({
            url: config.urls.revokeShare + '?id=' + shareId,
            type: 'POST',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    showShareToast(config.messages.shareRevoked);
                    const element = document.querySelector('[data-share-id="' + shareId + '"]');
                    if (element) element.remove();

                    // Check if no shares left
                    if (document.querySelectorAll('#sharesList [data-share-id]').length === 0) {
                        const noSharesMsg = config.messages.noSharesYet || 'No shares yet';
                        document.getElementById('sharesList').innerHTML =
                            '<div class="text-muted text-center py-3"><i class="fa fa-info-circle me-1"></i> ' + noSharesMsg + '</div>';
                    }
                } else {
                    showShareToast(response.message || config.messages.error, false);
                }
            },
            error: function () {
                showShareToast(config.messages.error, false);
            }
        });
    };

    /**
     * Update share permission level
     */
    window.updateSharePermission = function (shareId, permission) {
        const config = window.shareConfig;
        if (!config) return;

        $.ajax({
            url: config.urls.updatePermission + '?id=' + shareId,
            type: 'POST',
            data: {
                permission_level: permission
            },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    showShareToast(config.messages.permissionUpdated);
                } else {
                    showShareToast(response.message || config.messages.error, false);
                }
            },
            error: function () {
                showShareToast(config.messages.error, false);
            }
        });
    };

    /**
     * Refresh shares list
     */
    window.refreshSharesList = function () {
        const config = window.shareConfig;
        if (!config) return;

        let params = {};
        if (config.idStorage) params.id_storage = config.idStorage;
        if (config.idDirectory) params.id_directory = config.idDirectory;
        if (config.idUserOwner) params.id_user_owner = config.idUserOwner;

        $.ajax({
            url: config.urls.getShares,
            type: 'GET',
            data: params,
            success: function (response) {
                if (response.success && response.html) {
                    // Update only the shares list, not the entire page
                    const sharesList = document.getElementById('sharesList');
                    if (sharesList) {
                        sharesList.innerHTML = response.html;
                    }
                }
            },
            error: function () {
                console.error('Failed to refresh shares list');
            }
        });
    };

    /**
     * Update access UI (public/private)
     */
    window.updateAccessUI = function (level) {
        const accessText = document.getElementById('access-text');
        const accessDesc = document.getElementById('access-desc');
        const accessIcon = document.getElementById('access-icon');

        if (!accessText || !accessDesc || !accessIcon) return;

        accessText.textContent = accessText.dataset[level];
        accessDesc.textContent = accessDesc.dataset[level];

        if (level === 'public') {
            accessIcon.classList.remove('fa-lock');
            accessIcon.classList.add('fa-globe');
        } else {
            accessIcon.classList.remove('fa-globe');
            accessIcon.classList.add('fa-lock');
        }
    };

    /**
     * Set access level (public/private)
     */
    window.setAccessLevel = function (level) {
        const config = window.shareConfig;
        if (!config) return;

        updateAccessUI(level);

        $.ajax({
            url: config.urls.updateAccess,
            type: 'POST',
            data: {
                id: config.idStorage,
                access: level
            },
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    const statusText = level === 'public' ? config.messages.public : config.messages.restricted;
                    const msg = config.messages.accessChanged.replace('{status}', statusText);
                    showShareToast(msg);
                } else {
                    showShareToast(config.messages.error, false);
                }
            },
            error: function () {
                showShareToast(config.messages.error, false);
            }
        });
    };

    /**
     * Handle copy link button
     */
    window.handleCopyLink = function (btn) {
        var linkToCopy = window.generatedShareLink || '';
        if (!linkToCopy) {
            showShareToast('Please generate a link first', false);
            return;
        }

        //275-2280 yeni eklendi
        function onCopied() {
            var original = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-check me-2"></i>' + (btn.dataset.copied || 'Copied!');
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-success');


            setTimeout(function () {
                btn.innerHTML = original;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);

        }

        // Modern API (HTTPS / localhost)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(linkToCopy).then(onCopied).catch(function () {
                fallbackCopy(linkToCopy);
            });
        } else {
            fallbackCopy(linkToCopy);
        }

        function fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.top = '-9999px';
            btn.parentNode.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
                onCopied();
            } catch (e) {
                showShareToast('Failed to copy link', false);
            } finally {

                btn.parentNode.removeChild(ta);
            }
        }
    };

})();
