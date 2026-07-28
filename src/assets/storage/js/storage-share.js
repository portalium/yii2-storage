function applyViewModeClasses(mode) {
    const el = document.getElementById('files-section');
    const el2 = document.getElementById('folders-section');

    if (el2) {
        el2.classList.remove('grid-view', 'list-view');
        el2.classList.add(mode + '-view');
    }
    if (el) {
        el.classList.remove('grid-view', 'list-view');
        el.classList.add(mode + '-view');

        const row = el.querySelector('.row');
        if (row) {
            row.classList.remove('g-3');
            if (mode === 'grid') row.classList.add('g-3');
        }
    }

    const gridBtn = document.getElementById('btn-grid');
    const listBtn = document.getElementById('btn-list');
    if (gridBtn && listBtn) {
        if (mode === 'grid') {
            gridBtn.classList.remove('btn-unselected'); gridBtn.classList.add('btn-selected');
            listBtn.classList.remove('btn-selected'); listBtn.classList.add('btn-unselected');
        } else {
            listBtn.classList.remove('btn-unselected'); listBtn.classList.add('btn-selected');
            gridBtn.classList.remove('btn-selected'); gridBtn.classList.add('btn-unselected');
        }
    }

    const fileList = document.getElementById('file-list');
    if (fileList) {
        if (mode === 'list') {
            fileList.classList.remove('file-grid', 'mb-3');
        } else {
            fileList.classList.add('file-grid', 'mb-3');
        }
    }
}

function setViewMode(mode) {
    document.cookie = "viewMode=" + mode + "; path=/; max-age=31536000";
    applyViewModeClasses(mode);
}

window.openFilePreview = function(url, attributesRaw) {
    if (!url) return console.warn('data-url not found');

    var attributes = {};
    if (attributesRaw) {
        try {
            if (typeof attributesRaw === 'string') {
                attributes = JSON.parse(attributesRaw.replace(/'/g, '"'));
            } else {
                attributes = attributesRaw;
            }
        }
        catch (err) { console.warn('data-attributes could not be parsed.', err); }
    }

    var title = attributes.title || 'No Title';
    var iconClass = attributes.icon_class_php || 'fa fa-file'; 
    var mime_type = attributes.mime_type;
    var fileId = attributes.id_storage;
    var shareToken = attributes.share_token;

    if (shareToken) {
        url = '/storage/default/view-share?token=' + shareToken + '&file_id=' + fileId;
    } else if (fileId && url.indexOf('get-file') === -1 && url.indexOf('view-share') === -1) {
        url = '/storage/default/get-file?id=' + fileId;
    }

    if (fileId && window.storageConfig && window.storageConfig.trackAccessUrl) {
        $.ajax({
            url: window.storageConfig.trackAccessUrl,
            type: 'GET',
            data: { id: fileId },
            dataType: 'json',
            success: function (response) {
                console.log('Access tracked:', response);
            },
            error: function (xhr, status, error) {
                console.error('Access tracking failed:', error);
            }
        });
    }

    var modalHeader = '<div class="d-flex align-items-center">';
    modalHeader += '<i class="' + iconClass + ' file-icon me-2"></i>';
    modalHeader += '<span class="file-title">' + title + '</span>';
    modalHeader += '</div>';
    $('#file-preview-modal .modal-title').html(modalHeader);

    var loadingContent = '<div class="loading-spinner show text-center">';
    loadingContent += '<div class="spinner-border" role="status">';
    loadingContent += '<span class="sr-only">Loading...</span>';
    loadingContent += '</div>';
    loadingContent += '<p class="mt-2">File loading...</p>';
    loadingContent += '</div>';
    $('#filePreviewContent').html(loadingContent);

    $('#file-preview-modal').modal('show');

    var content = '';
    if (mime_type == 2) {
        content = '<div class="file-preview-container">';
        content += '<div class="pdf-viewer-container">';
        content += '<embed src="' + url + '#toolbar=1&navpanes=1&scrollbar=1" ';
        content += 'type="application/pdf" class="pdf-container" ';
        content += 'onload="$(\'#filePreviewContent .loading-spinner\').removeClass(\'show\')" ';
        content += 'onerror="fallbackToPdfJs(\'' + url + '\', \'' + title + '\')">';
        content += '</embed>';
        content += '</div>';
        content += '</div>';

        setTimeout(function () {
            $('#filePreviewContent .loading-spinner').removeClass('show');
        }, 500);

    } else if ([0, 1, 17, 25].includes(parseInt(mime_type))) {
        content = '<div class="file-preview text-center">';
        content += '<img src="' + url + '" alt="' + title + '" ';
        content += 'class="file-icon img-fluid" ';
        content += 'style="max-width:100%;max-height:70vh;" ';
        content += 'onload="$(\'#filePreviewContent .loading-spinner\').removeClass(\'show\')" ';
        content += 'onerror="handlePreviewError(\'Failed to load image.\')"/>';
        content += '</div>';

    } else if ([9, 11, 12, 13].includes(parseInt(mime_type))) {
        content = '<div class="file-preview text-center">';
        content += '<video controls autoplay style="max-width:100%;max-height:70vh;" ';
        content += 'oncanplay="$(\'#filePreviewContent .loading-spinner\').removeClass(\'show\')" ';
        content += 'onerror="handlePreviewError(\'Failed to load video.\')">';
        content += '<source src="' + url + '" type="video/mp4">';
        content += 'Your browser does not support the video tag.';
        content += '</video>';
        content += '</div>';

    } else {
        content = '<div class="file-preview text-center">';
        content += '<div class="alert alert-info">';
        content += '<i class="fa fa-info-circle fa-3x mb-3"></i>';
        content += '<h5>Preview Not Supported</h5>';
        content += '<p>Preview is not available for this file type.</p>';
        content += '<a href="' + url + '" target="_blank" class="btn btn-primary">';
        content += '<i class="fa fa-download me-1"></i>Download File';
        content += '</a>';
        content += '</div>';
        content += '</div>';

        setTimeout(function () {
            $('#filePreviewContent .loading-spinner').removeClass('show');
        }, 100);
    }

    setTimeout(function () {
        $('#filePreviewContent').html(content);
    }, 200);
}

function handleMultipleFilePreview(files) {
    if (!files || files.length === 0) {
        return console.warn('No files were found to preview...');
    }

    var firstFile = files[0];
    openFilePreview(firstFile.url, firstFile.attributes);
}

function getCurrentViewMode() {
    const filesSection = document.getElementById('files-section');
    const fileList = document.getElementById('file-list');

    // If we have explicit view class flags, use them.
    if (filesSection) {
        if (filesSection.classList.contains('list-view')) return 'list';
        if (filesSection.classList.contains('grid-view')) return 'grid';
    }

    // Fallback: infer from the file list layout class.
    if (fileList && fileList.classList.contains('file-grid')) {
        return 'grid';
    }

    return 'list';
}

$(document).on('dblclick', '.file-preview, .file-header', function (e) {
    // Only open preview when double-clicking the right element based on current view mode.
    // - Grid view: only dblclick on the preview tile should open the modal.
    // - List view: dblclick on the row header should open the modal.
    const viewMode = getCurrentViewMode();
    if (viewMode === 'grid' && !$(this).hasClass('file-preview')) {
        return;
    }
    if (viewMode === 'list' && !$(this).hasClass('file-header')) {
        return;
    }

    // Ignore dblclicks on menu buttons / checkboxes so actions still work.
    if ($(e.target).closest('.file-more-options, .file-select-checkbox').length) {
        return;
    }

    e.preventDefault();
    var fileItem = $(this).closest('.file-item');
    var url = fileItem.data('url');
    var attributes = fileItem.attr('data-attributes');
    openFilePreview(url, attributes);
});

$(document).on('show.bs.modal', '#file-preview-modal', function () {
    var defaultBackdropZ = 1040;
    var defaultModalZ = 1050;

    var maxZ = 0;
    $('.modal:visible').each(function () {
        var z = parseInt($(this).css('z-index')) || 0;
        if (z > maxZ) maxZ = z;
    });
    $('.modal-backdrop').each(function () {
        var z = parseInt($(this).css('z-index')) || 0;
        if (z > maxZ) maxZ = z;
    });

    if (maxZ < defaultModalZ) {
        return;
    }

    var modalZ = maxZ + 10;
    var backdropZ = maxZ + 5;

    $('#file-preview-modal').css('z-index', modalZ);
    $('.modal-backdrop').last().css('z-index', backdropZ);
});

$(document).on('hidden.bs.modal', '#file-preview-modal', function () {
    $(this).css('z-index', '');
    $('#filePreviewContent').html('');

    $('.modal-backdrop').css('z-index', '');
});
