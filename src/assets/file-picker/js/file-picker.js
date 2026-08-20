var FP = window.FilePickerConfig || (window.FilePickerConfig = { urls: {}, t: {} });

// Modal registry - for modal level assignation
if (!window.modalRegistry) {
    window.modalRegistry = new Map();
}

function showPickerToast(message, success) {
    var modalId = 'picker-error-modal';
    var existing = document.getElementById(modalId);
    if (existing) {
        var existingModal = bootstrap.Modal.getInstance(existing);
        if (existingModal) { existingModal.hide(); }
        existing.remove();
    }

    var headerClass = success === false ? 'bg-danger' : 'bg-success';
    var el = document.createElement('div');
    el.id = modalId;
    el.className = 'modal fade';
    el.setAttribute('tabindex', '-1');
    el.innerHTML =
        '<div class="modal-dialog modal-dialog-centered modal-sm">' +
            '<div class="modal-content">' +
                '<div class="' + headerClass + ' text-white d-flex justify-content-end modal-header">' +
                    '<h5 class="modal-title">' + FP.t.error + '</h5>' +
                    '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>' +
                '</div>' +
                '<div class="modal-body">' +
                    '<div>' + message + '</div>' +
                '</div>' +
                '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-info" data-bs-dismiss="modal">' + FP.t.close + '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    document.body.appendChild(el);
    var modal = new bootstrap.Modal(el);
    modal.show();
    el.addEventListener('hidden.bs.modal', function () { el.remove(); });
}

function previewSelectedFile(widgetId) {

    const $input = $('#preview-file-' + widgetId);

    if ($input.length === 0) {
        console.warn('[PREVIEW] Hidden input not found: #preview-file-' + widgetId);
        return;
    }

    const rawValue = $input.val() || $('#' + widgetId).val();

    if (!rawValue || rawValue.trim() === '') {
        console.warn('[PREVIEW] Both preview-file input and main input are empty for widgetId:', widgetId);
        return;
    }

    let value;
    try {
        value = JSON.parse(rawValue);
    } catch (e) {
        console.error('[PREVIEW] JSON parse error:', e, '| raw:', rawValue);
        return;
    }

    var id_storage = value.id_storage || value;

    if (!id_storage) {
        console.warn('[PREVIEW] id_storage is empty after extraction.');
        return;
    }

    $.ajax({
        url: FP.urls.getFileAttributes,
        type: 'GET',
        data: { id: id_storage },
        dataType: 'json',
        success: function(data) {
            if (data.error === 'file_missing') {
                showPickerToast(FP.t.fileMissing, false);
                return;
            }
            if (data.error || !data.url) {
                showPickerToast(FP.t.fileLoadFailed, false);
                return;
            }

            const attributesRaw = JSON.stringify(data.attributes || {});

            if (typeof window.openFilePreview === 'function') {
                window.openFilePreview(data.url, attributesRaw);
            } else {
                console.warn('openFilePreview function not found.');
            }
        },
        error: function(err) {
            console.error('Failed to load file attributes:', err);
            showPickerToast(FP.t.fileLoadError, false);
        }
    });
}

if (!window.handleFilePickerClick) {
    window.handleFilePickerClick = function(btn, id, id_storage, multiple, isJson, callbackName, isPicker, attributes, allowedExtensions, allowFolderSelection) {
        var $btn = $(btn);

        if ($btn.hasClass("btn-loading")) return;

        $btn.addClass("btn-loading").css("pointer-events", "none");
        
        window.currentAllowedExtensions = allowedExtensions || [];

        window.openFilePickerModal(id, id_storage, multiple, isJson, callbackName, isPicker, attributes, allowedExtensions, allowFolderSelection);

        $(document).one('shown.bs.modal', '#file-picker-modal', function () {
            $btn.removeClass("btn-loading").css("pointer-events", "auto");
        });
    };
}

// Modal supporter functions
if (!window.updateFileCard) {
    window.updateFileCard = function(id_storage) {
        // Clear all active states first
        $('.file-card.active').removeClass('active');
        $('.file-card input[type="checkbox"]').prop('checked', false);
        $('.folder-item.active').removeClass('active');

        if (!id_storage) return;

        // Try to activate as a folder first, then fall back to file
        const activateOne = function(id) {
            let folderEl = $('#file-picker-modal .folder-item[data-id=' + id + ']');
            if (folderEl.length) {
                folderEl.addClass('active');
                return;
            }
            let fileEl = $('#file-picker-modal .file-card[data-id=' + id + ']');
            fileEl.addClass('active');
            fileEl.find('input[type="checkbox"]').prop('checked', true);
        };

        if (Array.isArray(id_storage)) {
            id_storage.forEach(id => activateOne(id));
        } else {
            activateOne(id_storage);
        }
    };
}

// Modal closing function
if (!window.closeModalById) {
    window.closeModalById = function(modalId) {
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            
            if (modalId !== 'file-picker-modal') {
                window.modalRegistry.delete(modalId);
                setTimeout(() => {
                    if (modalEl && modalEl.parentNode) {
                        modalEl.parentNode.removeChild(modalEl);
                    }
                    if (window.modalRegistry.size === 0) {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        setTimeout(() => {
                            window.restoreMainPageEvents && window.restoreMainPageEvents();
                        }, 100);
                    }
                }, 300);
            }
        }
    };
}

// Legacy cleanup function
if (!window.cleanupModal) {
    window.cleanupModal = function(modalId = null, onlySpecific = false) {
        if (modalId) {
            window.closeModalById(modalId);
        } else {
            window.modalRegistry.forEach((value, key) => {
                window.closeModalById(key);
            });
        }
    };
}

// Function that connects modal-specific close events
if (!window.bindModalCloseEvents) {
    window.bindModalCloseEvents = function(modalId, level = 0) {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) return;
        
        
        window.modalRegistry.set(modalId, { level: level });
        
        const directCloseButtons = modalEl.querySelectorAll(
            ':scope > .modal-dialog > .modal-content > .modal-header .btn-close, ' +
            ':scope > .modal-dialog > .modal-content > .modal-footer .btn-close, ' +
            ':scope > .modal-dialog > .modal-content > .modal-footer [data-bs-dismiss="modal"]'
        );
        
        directCloseButtons.forEach(button => {
            $(button).off('click.modal-close-' + modalId);
            $(button).on('click.modal-close-' + modalId, function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                window.closeModalById(modalId);
            });
        });
        
        modalEl.addEventListener('hidden.bs.modal', function(e) {
            if (modalId === 'file-picker-modal') {
                return;
            }
            
            window.modalRegistry.delete(modalId);
            
            setTimeout(() => {
                if (modalEl && modalEl.parentNode) {
                    modalEl.parentNode.removeChild(modalEl);
                }
            }, 100);
        });
    };
}

// Function to restore home page events
if (!window.restoreMainPageEvents) {
    window.restoreMainPageEvents = function() {
        if ((!window.isPicker || window.isPicker === false) && window.loadedRestoreMainPageEvents == false) {
            window.loadedRestoreMainPageEvents = true;
            
            $(document).off('click.main-dropdown').on('click.main-dropdown', '.dropdown-toggle:not(.modal .dropdown-toggle), .file-ellipsis:not(.modal .file-ellipsis), .folder-ellipsis:not(.modal .folder-ellipsis)', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropdown = $(this).closest('.dropdown');
                const menu = dropdown.find('.dropdown-menu');
                
                $('.dropdown-menu').not(menu).removeClass('show');
                $('.dropdown').not(dropdown).removeClass('show');
                
                dropdown.toggleClass('show');
                menu.toggleClass('show');
            });

            $(document).off('click.main-action').on('click.main-action', '.dropdown-item:not(.modal .dropdown-item), .dropdown-menu a:not(.modal .dropdown-menu a)', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const onclick = $(this).attr('onclick');
                const href = $(this).attr('href');
                
                $('.dropdown-menu').removeClass('show');
                $('.dropdown').removeClass('show');
                
                if (onclick) {
                    try { 
                        eval(onclick); 
                    } catch(error) { 
                        console.error('Onclick error:', error); 
                    }
                } else if (href) {
                    window.location.href = href;
                }
            });

            $(document).off('click.main-outside').on('click.main-outside', function(e) {
                if (!$(e.target).closest('.dropdown').length && !$(e.target).closest('.modal').length) {
                    $('.dropdown-menu').removeClass('show');
                    $('.dropdown').removeClass('show');
                }
            });
            
        }
    };
}

// Special event binding for file picker
if (!window.bindFilePickerEvents) {
    window.bindFilePickerEvents = function() {
        $('#file-picker-modal .btn-select').off('click.picker-select').on('click.picker-select', function(e) {
            e.stopPropagation();
            window.saveSelect();
        });

        $(document).off('click.picker-dropdown').on('click.picker-dropdown', '#file-picker-modal .dropdown-toggle, #file-picker-modal .file-ellipsis, #file-picker-modal .folder-ellipsis', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = $(this).closest('.dropdown');
            const menu = dropdown.find('.dropdown-menu');
            
            $('#file-picker-modal .dropdown-menu').not(menu).removeClass('show');
            $('#file-picker-modal .dropdown').not(dropdown).removeClass('show');
            
            dropdown.toggleClass('show');
            menu.toggleClass('show');
        });

        $(document).off('click.picker-outside').on('click.picker-outside', '#file-picker-modal', function(e) {
            if (!$(e.target).closest('#file-picker-modal .dropdown').length) {
                $('#file-picker-modal .dropdown-menu').removeClass('show');
                $('#file-picker-modal .dropdown').removeClass('show');
            }
        });
    };
}

// Action handler
if (!window.handlePickerAction) {
    window.handlePickerAction = function(action, id, href) {
        switch(action) {
            case 'download':
                var dlLink = document.createElement('a');
                dlLink.href = FP.urls.downloadFile + (FP.urls.downloadFile.indexOf('?') === -1 ? '?' : '&') + 'id=' + encodeURIComponent(id) + '&isPicker=1';
                dlLink.style.display = 'none';
                document.body.appendChild(dlLink);
                dlLink.click();
                setTimeout(function() { document.body.removeChild(dlLink); }, 200);
                break;
            case 'copy':
                $.post(FP.urls.copyFile, { id: id, isPicker: '1' })
                    .done(() => window.refreshFilePicker && window.refreshFilePicker());
                break;
            case 'delete':
                if (confirm(FP.t.confirmDeleteFile)) {
                    $.post(FP.urls.deleteFile, { id: id, isPicker: '1' })
                        .done(() => window.refreshFilePicker && window.refreshFilePicker());
                }
                break;
            case 'rename':
            case 'update':
            case 'share':
                if (href) window.openActionModal(action, href);
                break;
            case 'delete-folder':
                if (confirm(FP.t.confirmDeleteFolder)) {
                    $.post(FP.urls.deleteFolder, { id: id, isPicker: '1' })
                        .done(() => window.refreshFilePicker && window.refreshFilePicker());
                }
                break;
        }
    }
};

// Action open modal
if (!window.openActionModal) {
    window.openActionModal = function(action, href) {
        const modalId = 'action-modal-' + Date.now();

        if (action === 'share') {
            window.showLoading && window.showLoading(FP.t.openingShare);

            var reopenPicker = false;
            if (window.isPicker) {
                reopenPicker = true;
                window._pickerStateBeforeShare = {
                    inputId: window.inputId,
                    selectedIdStorage: window.selectedIdStorage,
                    multiple: window.multiple,
                    isJson: window.isJson,
                    callbackName: window.callbackName,
                    attributes: window.currentAttributes ? window.currentAttributes.slice() : ['id_storage'],
                    allowedExtensions: window.allowedExtensions ? window.allowedExtensions.slice() : [],
                    allowFolderSelection: window.allowFolderSelection || false,
                };

                var pickerEl = document.getElementById('file-picker-modal');
                if (pickerEl) {
                    var pickerModal = bootstrap.Modal.getInstance(pickerEl);
                    if (pickerModal) {
                        pickerModal.hide();
                    } else {
                        console.warn('[PICKER] No Bootstrap modal instance found for file-picker-modal');
                    }
                } else {
                    console.warn('[PICKER] file-picker-modal element not found in DOM');
                }
            } else {
            }

            setTimeout(function() {
                sendActionModalRequest(action, href, modalId, reopenPicker);
            }, 1000);
        } else {
            sendActionModalRequest(action, href, modalId, false);
        }
    };

    function sendActionModalRequest(action, href, modalId, reopenPicker) {
        $.get(href)
            .done(function(response) {
                $('.modal[id*="action-modal"], .modal[id*="Modal"]:not(#file-picker-modal), .modal[id*="modal-"]:not(#file-picker-modal)').each(function() {
                    window.closeModalById(this.id);
                });

                const idMap = {
                    'rename': 'renameModal',
                    'update': 'updateModal',
                    'share': 'modal-share'
                };
                const oldId = idMap[action] || 'modal';
                response = response.replace(new RegExp('id="' + oldId + '"', 'g'), 'id="' + modalId + '"');

                $('body').append(response);

                setTimeout(() => {
                    if (action === 'share') {
                        window.hideLoading && window.hideLoading();
                    }

                    const modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        if (reopenPicker) {
                            modalEl.addEventListener('hidden.bs.modal', function() {
                                var state = window._pickerStateBeforeShare;
                                if (state) {
                                    window._pickerStateBeforeShare = null;
                                    setTimeout(function() {
                                        window.openFilePickerModal(
                                            state.inputId,
                                            state.selectedIdStorage,
                                            state.multiple,
                                            state.isJson,
                                            state.callbackName,
                                            true,
                                            state.attributes,
                                            state.allowedExtensions,
                                            state.allowFolderSelection
                                        );
                                    }, 300);
                                } else {
                                    console.warn('[PICKER] No state to restore picker');
                                }
                            }, { once: true });
                        }

                        window.bindModalCloseEvents(modalId, 1);

                        const modal = new bootstrap.Modal(modalEl, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        modal.show();

                        $(modalEl).find('form').on('submit', function(e) {
                            e.preventDefault();
                            $.ajax({
                                url: this.action,
                                type: 'POST',
                                data: new FormData(this),
                                processData: false,
                                contentType: false,
                                complete: function() {
                                    window.closeModalById(modalId);
                                    window.refreshFilePicker && window.refreshFilePicker();
                                }
                            });
                        });
                    }
                }, 100);
            })
            .fail(function(e) {
                if (action === 'share') {
                    window.hideLoading && window.hideLoading();
                }
            });
    }
}

// Main file picker modal opening
if (!window.openFilePickerModal) {
    window.openFilePickerModal = function(id, id_storage, multiple, isJson, callbackName, isPicker = true, attributes = ['id_storage'], allowedExtensions = [], allowFolderSelection = false) {
        window.multiple = multiple;
        window.isJson = isJson;
        window.callbackName = callbackName;
        window.inputId = id;
        window.isPicker = isPicker;
        window.currentAttributes = Array.isArray(attributes) ? attributes : [attributes];
        window.allowedExtensions = allowedExtensions || [];
        window.allowFolderSelection = allowFolderSelection || false;

        let inputValue = $('#' + id).val();
        let parsedValue = {};

        try {
            parsedValue = JSON.parse(inputValue || '{}');
        } catch (e) {
            parsedValue = {};
        }

        let id_storage_2 = parsedValue.id_storage ?? null;

        window.selectedIdStorage =
            (id_storage_2 !== null && !isNaN(id_storage_2))
                ? id_storage_2
                : inputValue;

        if (document.getElementById('file-picker-modal')) {
            window.closeModalById('file-picker-modal');
        }

        const savedSortField = localStorage.getItem('sortField');
        const savedSortDirection = localStorage.getItem('sortDirection');
        
        const modalParams = {
            id: id,
            multiple: multiple,
            isJson: isJson,
            fileExtensions: window.fileExtensions,
            isPicker: isPicker,
            attributes: window.currentAttributes,
            selectedFileId: id_storage_2 || inputValue || null,
            allowedExtensions: allowedExtensions,
            allowFolderSelection: allowFolderSelection ? 1 : 0
        };
        
        if (savedSortField) {
            modalParams.sortField = savedSortField;
            modalParams.sortDirection = savedSortDirection || 'desc';
        }
        
        $.get(FP.urls.pickerModal, modalParams).done(function(response) {
            $('#file-picker-modal').remove();
            $('body').append(response);

            const modalEl = document.getElementById('file-picker-modal');
            if (modalEl) {
                window.pjaxBaseUrl = FP.urls.index + (FP.urls.index.indexOf('?') === -1 ? '?' : '&') + 'isPicker=1';
                if (id_storage_2 || inputValue) {
                    window.pjaxBaseUrl += '&selectedFileId=' + (id_storage_2 || inputValue);
                }
                if (window.fileExtensions && window.fileExtensions.length > 0) {
                    window.pjaxBaseUrl += '&fileExtensions=' + window.fileExtensions.join(',');
                }
                if (allowFolderSelection) {
                    window.pjaxBaseUrl += '&allowFolderSelection=1';
                }
                if (savedSortField) {
                    window.pjaxBaseUrl += '&sortField=' + savedSortField;
                    window.pjaxBaseUrl += '&sortDirection=' + (savedSortDirection || 'desc');
                }
                window.bindModalCloseEvents('file-picker-modal', 0);
                
                // Sync allowFolderSelection from DOM attribute in case it was set server-side
                const domAllowFolder = modalEl.getAttribute('data-allow-folder-selection');
                if (domAllowFolder !== null) {
                    window.allowFolderSelection = domAllowFolder === '1';
                }
                
                const modal = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: false
                });

                const savedMode = localStorage.getItem('viewMode') || 'grid';
                if (typeof applyViewModeClasses === 'function') {
                    applyViewModeClasses(savedMode);
                }
                
                modal.show();
                
                modalEl.addEventListener('shown.bs.modal', function() {
                    if (typeof updateSortDirectionLabels === 'function') {
                        updateSortDirectionLabels();
                    }
                    if (typeof highlightActiveSort === 'function') {
                        highlightActiveSort();
                    }
                    
                    if(id_storage_2 && !isNaN(id_storage_2)) {
                        window.updateFileCard(id_storage_2);
                    }else{
                        window.updateFileCard(inputValue);
                    }
                    window.bindFilePickerEvents();
                }, { once: true });
            }
        });
    };
}

$(document).ready(function() {
    window.restoreMainPageEvents();
});

// Supporting functions
if (!window.refreshFilePicker) {
    window.refreshFilePicker = function() {
        const container = $('#file-picker-modal .files-container');
        if (container.length) {
            $.get(FP.urls.pickerContent, {
                fileExtensions: window.fileExtensions,
                isPicker: window.isPicker,
                attributes: window.currentAttributes
            }).done(function(response) {
                container.html(response);
                window.bindFilePickerEvents();
                
                if (typeof updateSortDirectionLabels === 'function') {
                    updateSortDirectionLabels();
                }
                if (typeof highlightActiveSort === 'function') {
                    highlightActiveSort();
                }
                
                const id_storage = window.currentSelectedIdStorage || null;
                if (window.updateFileCard) {
                    window.updateFileCard(id_storage);
                }
            });
        }
    };
}

if (!window.getAttributesFromDOM) {
    window.getAttributesFromDOM = function(id) {
        let el = document.querySelector('[data-id="' + id + '"]');
        if (el) {
            let fileItem = el.querySelector('.file-item');
            if (fileItem) {
                el = fileItem;
            }
        }

        if (!el) return {};
        try {
            const attr = el.getAttribute('data-attributes') || el.getAttribute('attributes');
            return attr ? JSON.parse(attr) : {};
        } catch (e) {
            console.error('Error parsing attributes:', e);
            return {};
        }
    };
}

if (!window.saveSelect) {
    window.saveSelect = function() {
        const attributes = window.currentAttributes || ['id_storage'];
        
        // Check if a folder is selected (allowFolderSelection mode)
        const selectedFolderEl = $('.folder-item.active');
        if (window.allowFolderSelection && selectedFolderEl.length > 0) {
            const folderId = selectedFolderEl.data('id');
            const folderName = selectedFolderEl.find('.folder-name').text();
            let value;
            if (window.isJson) {
                value = JSON.stringify({ id_directory: folderId, name: folderName });
            } else {
                value = folderId;
            }
            $('#' + window.inputId).val(value);
            $('#preview-file-' + window.inputId).val(value);
            if (window.callbackName && typeof window[window.callbackName] === 'function') {
                window[window.callbackName]({ id_directory: folderId, name: folderName });
            }
            window.closeModalById('file-picker-modal');
            return;
        }

        const selectedFiles = window.multiple ?
            $('.file-card input[type="checkbox"]:checked').map(function() {
                return $(this).closest('.file-card').data('id');
            }).get() :
            $('.file-card.active').data('id');
            
        let value;
        if (window.isJson) {
            if (window.multiple) {
                value = JSON.stringify(selectedFiles.map(id => {
                    const fullData = getAttributesFromDOM(id);
                    const obj = {};
                    attributes.forEach(attr => {
                        obj[attr] = fullData[attr] || null;
                    });
                    return obj;
                }));
            } else {
                const fullData = getAttributesFromDOM(selectedFiles);
                if (attributes.length === 1) {
                    value = JSON.stringify(fullData[attributes[0]] || null);
                } else {
                    const obj = {};
                    attributes.forEach(attr => {
                        obj[attr] = fullData[attr] || null;
                    });
                    value = JSON.stringify(obj);
                }
            }
        } else {
            value = window.multiple ? selectedFiles.join(',') : selectedFiles;
        }

        $('#' + window.inputId).val(value);
        $('#preview-file-' + window.inputId).val(value);
        if (window.callbackName && typeof window[window.callbackName] === 'function') {
            window[window.callbackName](selectedFiles);
        }

        window.closeModalById('file-picker-modal');
    };
}
