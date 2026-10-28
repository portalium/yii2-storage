var currentDirectoryId = null;
var currentIsPicker = $("#searchFileInput").data("is-picker") === 1;
var searchTimer;
var isSearching = false;
var originalUrl = window.location.href;
window.lastListItemPjaxUrl = null;

function isInWidgetContext() {
  return (
    window.location.href.includes("picker-modal") ||
    window.top !== window.self ||
    window.frameElement !== null
  );
}

function showModal(modalId, timeout = 200) {
  setTimeout(function () {
    const modalEl = document.getElementById(modalId);
    if (modalEl) {
      const existingModal = bootstrap.Modal.getInstance(modalEl);
      if (existingModal) {
        existingModal.dispose();
      }

      const modalInstance = new bootstrap.Modal(modalEl, {
        backdrop: "static",
        keyboard: false,
      });
      modalInstance.show();
    } else {
      console.warn("Modal element not found:", modalId);
    }
  }, timeout);
}

function hideModal(modalId) {
  const modalEl = document.getElementById(modalId);
  if (modalEl) {
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (modalInstance) {
      modalInstance.hide();
    }

    setTimeout(() => {
      if (modalEl && modalEl.parentNode && modalEl.id !== "file-picker-modal") {
        modalEl.parentNode.removeChild(modalEl);
      }
    }, 300);
  }
}

function isManagePage() {
  return window.location.pathname.includes("/manage");
}

function getBaseUrl() {
  let basePath = isManagePage()
    ? "/storage/default/manage"
    : "/storage/default/index";
  let url = basePath;

  if (window.currentDirectoryId) {
    url += "?id_directory=" + window.currentDirectoryId;
  }

  if (window.currentIsPicker) {
    const separator = url.includes("?") ? "&" : "?";
    url += separator + "isPicker=1";
  }

  const fileExtensions = Array.isArray(window.fileExtensions)
    ? window.fileExtensions.join(",")
    : "";

  if (fileExtensions) {
    const separator = url.includes("?") ? "&" : "?";
    url += separator + "fileExtensions=" + encodeURIComponent(fileExtensions);
  }

  if (window.allowFolderSelection) {
    const separator = url.includes("?") ? "&" : "?";
    url += separator + "allowFolderSelection=1";
  }

  return url;
}

function returnToMainPage() {
  window.isSearching = false;
  const baseUrl = getBaseUrl();

  $.pjax
    .reload({
      container: "#list-item-pjax",
      url: baseUrl,
      push: false,
      replace: false,
      timeout: 10000,
      complete: function () {
      },
    })
    .done(function () {
      $.pjax.reload({ container: "#pjax-flash-message" });
    });
}

window.handleFileCardClick = function (event, id_storage) {
  if (event && $(event.target).closest('.file-more-options, [id^="context-menu-"], .custom-dropdown-menu').length) {
    return;
  }

  if (event && event.ctrlKey) {
    event.preventDefault();
    event.stopPropagation();

    if (id_storage && typeof window.toggleBulkSelection === 'function') {
      window.toggleBulkSelection(id_storage, event);
    }
    return false;
  }

  if (window.isPicker) {
    event.preventDefault();
    event.stopPropagation();

    if (window.allowFolderSelection) {
      $('.folder-item.active').removeClass('active');
      window.selectedDirectoryId = null;
    }

    const fileCard = $('.file-card[data-id="' + id_storage + '"]');
    const checkbox = fileCard.find('.file-select-checkbox');

    if (checkbox.length > 0) {
      const newCheckedState = !checkbox.prop('checked');
      checkbox.prop('checked', newCheckedState);

      if (typeof window.selectFile === 'function') {
        window.selectFile(checkbox[0], id_storage);
      }
    } else {
      $('.file-card.active').removeClass('active');
      fileCard.addClass('active');
      window.selectedIdStorage = id_storage;
    }
  }
};

window.selectFile = function (checkbox, id_storage) {
  const fileCard = $(checkbox).closest('.file-card');
  const isChecked = $(checkbox).prop('checked');

  if (window.multiple) {
    if (isChecked) {
      fileCard.addClass('active');
    } else {
      fileCard.removeClass('active');
    }
  } else {
    $('.file-card.active').removeClass('active');
    $('.file-card input[type="checkbox"]').not(checkbox).prop('checked', false);

    if (isChecked) {
      fileCard.addClass('active');
      window.selectedIdStorage = id_storage;
    } else {
      window.selectedIdStorage = null;
    }
  }
};

window.handleFolderCardClick = function (event, id_directory) {
  if (!window.allowFolderSelection || !window.isPicker) return;

  event.preventDefault();
  event.stopPropagation();

  $('.folder-item.active').removeClass('active');
  $('.file-card.active').removeClass('active');
  $('.file-card input[type="checkbox"]').prop('checked', false);
  window.selectedIdStorage = null;

  const folderEl = $('.folder-item[data-id="' + id_directory + '"]');
  folderEl.addClass('active');
  window.selectedDirectoryId = id_directory;
};

window.openFolder = function (id_directory, event) {
  if (
    event &&
    (event.target.classList.contains("folder-ellipsis") ||
      $(event.target).closest(".folder-dropdown-menu").length)
  ) {
    return;
  }
  window.currentDirectoryId =
    id_directory === null || id_directory === undefined
      ? null
      : parseInt(id_directory);

  let url = isManagePage()
    ? "/storage/default/manage"
    : "/storage/default/index";
  if (id_directory) {
    url += "?id_directory=" + id_directory;
  }

  if (window.currentIsPicker) {
    const separator = url.includes("?") ? "&" : "?";
    url += separator + "isPicker=1";
  }

  const fileExtensions = Array.isArray(window.fileExtensions)
    ? window.fileExtensions.join(",")
    : "";
  if (fileExtensions) {
    const separator = url.includes("?") ? "&" : "?";
    url += separator + "fileExtensions=" + encodeURIComponent(fileExtensions);
  }

  if (window.allowFolderSelection) {
    const separator = url.includes("?") ? "&" : "?";
    url += separator + "allowFolderSelection=1";
  }

  window.isSearching = false;
  $("#searchFileInput").val("");

  $.pjax.reload({
    container: "#list-item-pjax",
    url: url,
    push: false,
    replace: false,
    timeout: 10000,
    complete: function () {
      if (!url.includes("id_directory=")) window.currentDirectoryId = null;
    },
  });
};

var _uploadPanelItems = {};

function _getOrCreateUploadPanel() {
  var panel = document.getElementById("upload-progress-panel");
  if (!panel) {
    panel = document.createElement("div");
    panel.id = "upload-progress-panel";
    panel.innerHTML =
      '<div id="upload-progress-header">' +
        '<span class="upload-panel-title"><i class="fa fa-cloud-upload-alt"></i> Uploading</span>' +
        '<button id="upload-progress-toggle" title="Collapse/Expand"><i class="fa fa-chevron-down"></i></button>' +
      '</div>' +
      '<div id="upload-progress-body"></div>';
    document.body.appendChild(panel);

    document.getElementById("upload-progress-toggle").addEventListener("click", function () {
      var body = document.getElementById("upload-progress-body");
      var icon = this.querySelector("i");
      body.classList.toggle("collapsed");
      icon.classList.toggle("fa-chevron-down");
      icon.classList.toggle("fa-chevron-up");
    });
  }
  panel.classList.remove("hidden");
  return panel;
}

function _addUploadItem(id, filename) {
  _getOrCreateUploadPanel();
  var body = document.getElementById("upload-progress-body");
  var item = document.createElement("div");
  item.className = "upload-progress-item";
  item.id = "upi-" + id;
  item.innerHTML =
    '<div class="up-header">' +
      '<div class="up-filename" title="' + filename + '">' + filename + '</div>' +
      '<button class="up-cancel-btn" title="Cancel" data-item-id="' + id + '"><i class="fa fa-times"></i></button>' +
    '</div>' +
    '<div class="up-meta"><span class="up-speed">0 KB/s</span><span class="up-eta">Waiting...</span></div>' +
    '<div class="progress"><div class="progress-bar" style="width:0%"></div></div>';
  body.appendChild(item);
  _uploadPanelItems[id] = { startTime: Date.now(), loaded: 0, xhr: null };

  item.querySelector(".up-cancel-btn").addEventListener("click", function () {
    var state = _uploadPanelItems[id];
    if (state && state.xhr) {
      state.xhr.abort();
    }
  });
}

function _updateUploadItem(id, loaded, total) {
  var item = document.getElementById("upi-" + id);
  if (!item) return;
  var state = _uploadPanelItems[id];
  var pct = total > 0 ? Math.round((loaded / total) * 100) : 0;
  var bar = item.querySelector(".progress-bar");
  bar.style.width = pct + "%";

  var elapsed = (Date.now() - state.startTime) / 1000;
  var speedBps = elapsed > 0 ? loaded / elapsed : 0;
  var remaining = speedBps > 0 && total > loaded ? (total - loaded) / speedBps : 0;

  item.querySelector(".up-speed").textContent = _formatSpeed(speedBps);
  item.querySelector(".up-eta").textContent = pct + "% \u2014 " + (_formatEta(remaining) || "Almost done...");
}

function _formatSpeed(bytesPerSecond) {
  return bytesPerSecond > 1048576
    ? (bytesPerSecond / 1048576).toFixed(1) + " MB/s"
    : (bytesPerSecond / 1024).toFixed(0) + " KB/s";
}

function _formatEta(seconds) {
  if (seconds > 3600) {
    return Math.round(seconds / 3600) + " hr left";
  } else if (seconds > 60) {
    return Math.round(seconds / 60) + " min left";
  } else if (seconds > 0) {
    return Math.round(seconds) + " sec left";
  }
  return "";
}

function _finishUploadItem(id, success, cancelled) {
  var item = document.getElementById("upi-" + id);
  if (!item) return;
  var cancelBtn = item.querySelector(".up-cancel-btn");
  if (cancelBtn) cancelBtn.style.display = "none";
  var bar = item.querySelector(".progress-bar");
  var meta = item.querySelector(".up-meta");
  if (cancelled) {
    bar.style.width = "100%";
    bar.classList.add("bg-secondary");
    meta.innerHTML = '<span class="up-status-cancelled">Cancelled</span>';
  } else if (success) {
    bar.style.width = "100%";
    bar.classList.add("bg-success");
    meta.innerHTML = '<span class="up-status-done">Done</span>';
  } else {
    bar.style.width = "100%";
    bar.classList.add("bg-danger");
    meta.innerHTML = '<span class="up-status-error">Failed</span>';
  }
  delete _uploadPanelItems[id];

  setTimeout(function () {
    if (Object.keys(_uploadPanelItems).length === 0) {
      var header = document.getElementById("upload-progress-header");
      if (header) {
        header.querySelector(".upload-panel-title").innerHTML =
          '<i class="fa fa-check-circle" style="color:#198754"></i> Upload complete';
      }
      setTimeout(function () {
        var panel = document.getElementById("upload-progress-panel");
        if (panel) panel.classList.add("hidden");
        _uploadPanelItems = {};
        var body = document.getElementById("upload-progress-body");
        if (body) body.innerHTML = "";
        var hdr = document.getElementById("upload-progress-header");
        if (hdr) hdr.querySelector(".upload-panel-title").innerHTML =
          '<i class="fa fa-cloud-upload-alt"></i> Uploading';
      }, 2500);
    }
  }, 400);
}

function _uploadFileXhr(formData, itemId, onSuccess, onError) {
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "/storage/default/upload-file", true);
  xhr.setRequestHeader("X-CSRF-Token", $('meta[name="csrf-token"]').attr("content"));

  if (_uploadPanelItems[itemId]) {
    _uploadPanelItems[itemId].xhr = xhr;
  }

  xhr.upload.addEventListener("progress", function (e) {
    if (e.lengthComputable) {
      _updateUploadItem(itemId, e.loaded, e.total);
    }
  });

  xhr.addEventListener("load", function () {
    if (xhr.status >= 200 && xhr.status < 300) {
      _finishUploadItem(itemId, true, false);
      onSuccess();
    } else {
      _finishUploadItem(itemId, false, false);
      onError();
    }
  });

  xhr.addEventListener("error", function () {
    _finishUploadItem(itemId, false, false);
    onError();
  });

  xhr.addEventListener("abort", function () {
    _finishUploadItem(itemId, false, true);
    onError();
  });

  xhr.send(formData);
}

function uploadFileMenu(event) {
  event.preventDefault();
  const newDropdownBtn = $("#newDropdownBtn");

  let fileInput = document.getElementById("hiddenUploadInput");
  if (fileInput) {
    fileInput.remove();
  }

  fileInput = document.createElement("input");
  fileInput.type = "file";
  fileInput.id = "hiddenUploadInput";
  fileInput.style.display = "none";
  fileInput.multiple = true;

  var allowedExtensions = [];
  var pickerModal = document.getElementById('file-picker-modal');
  if (pickerModal) {
    var allowedExtStr = pickerModal.getAttribute('data-allowed-extensions');
    if (allowedExtStr) {
      try {
        allowedExtensions = JSON.parse(allowedExtStr);
      } catch (e) {
        console.error('Failed to parse allowedExtensions:', e);
      }
    }
  }

  if (allowedExtensions && allowedExtensions.length > 0) {
    var acceptValue = allowedExtensions.map(function (ext) {
      return '.' + ext.replace(/^\./, '');
    }).join(',');
    fileInput.setAttribute('accept', acceptValue);
  }

  document.body.appendChild(fileInput);

  fileInput.addEventListener("change", function () {
    if (fileInput.files.length > 0) {
      newDropdownBtn.addClass("btn-loading");
      const files = Array.from(fileInput.files);

      var header = document.getElementById("upload-progress-header");
      if (header) {
        header.querySelector(".upload-panel-title").innerHTML =
          '<i class="fa fa-cloud-upload-alt"></i> Uploading';
      }

      let completed = 0;
      files.forEach(function (file, idx) {
        const itemId = "f-" + Date.now() + "-" + idx;
        _addUploadItem(itemId, file.name);

        const formData = new FormData();
        formData.append("Storage[file]", file);
        formData.append("Storage[title]", file.name);
        formData.append("id_directory", window.currentDirectoryId ? window.currentDirectoryId : "");

        if (allowedExtensions && allowedExtensions.length > 0) {
          formData.append("Storage[allowedExtensions]", JSON.stringify(allowedExtensions));
        }

        if (window.currentIsPicker) {
          formData.append("isPicker", "1");
        }

        _uploadFileXhr(formData, itemId, function () {
          completed++;
          if (completed === files.length) {
            if (window.isSearching) {
              const searchValue = $("#searchFileInput").val().trim();
              if (searchValue) {
                performSearch(searchValue);
                newDropdownBtn.removeClass("btn-loading");
                return;
              }
            }
            const reloadUrl = window.lastListItemPjaxUrl || getBaseUrl();
            $.pjax.reload({
              container: "#list-item-pjax",
              url: reloadUrl,
              replace: false,
              push: false,
            }).done(function () {
              newDropdownBtn.removeClass("btn-loading");
            });
          }
        }, function () {
          completed++;
          newDropdownBtn.removeClass("btn-loading");
          console.error("Upload error for file:", file.name);
          if (completed === files.length) {
            const reloadUrl = window.lastListItemPjaxUrl || getBaseUrl();
            $.pjax.reload({ container: "#list-item-pjax", url: reloadUrl, replace: false, push: false });
          }
        });
      });
    }
  });

  fileInput.click();
}

// A folder is sent in small batches instead of one request: PHP rejects a
// body above post_max_size outright, and one huge request gives no progress
// while the server stores the files.
var FOLDER_BATCH_FILES = 10;
var FOLDER_TAIL_FILES = 3;
var FOLDER_BATCH_BYTES = 40 * 1024 * 1024;
var FOLDER_PARALLEL = 4;
var FOLDER_RETRIES = 2;
var FOLDER_RETRY_STATUSES = [0, 502, 503, 504];

function _relativePath(file) {
  return file.webkitRelativePath || file.name;
}

function _postStorageForm(formData, onProgress, onDone) {
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "/storage/default/upload-file", true);
  xhr.setRequestHeader("X-CSRF-Token", $('meta[name="csrf-token"]').attr("content"));
  xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

  if (onProgress) {
    xhr.upload.addEventListener("progress", function (e) {
      if (e.lengthComputable && e.total > 0) {
        onProgress(e.loaded / e.total);
      }
    });
  }

  xhr.addEventListener("load", function () {
    var json = null;
    try {
      json = JSON.parse(xhr.responseText);
    } catch (e) {
      json = null;
    }
    onDone(xhr.status, json);
  });
  xhr.addEventListener("error", function () {
    onDone(0, null);
  });
  xhr.addEventListener("abort", function () {
    onDone(-1, null);
  });

  xhr.send(formData);
  return xhr;
}

function _folderFormData(idRoot) {
  var formData = new FormData();
  formData.append("Storage[type]", "folder");
  formData.append("id_directory", window.currentDirectoryId ? window.currentDirectoryId : "");
  if (idRoot) {
    formData.append("id_root", idRoot);
  }
  if (window.currentIsPicker) {
    formData.append("isPicker", "1");
  }
  return formData;
}

function _chunkFiles(files, maxFiles) {
  var batches = [];
  var current = { files: [], bytes: 0 };

  files.forEach(function (file) {
    if (current.files.length > 0 &&
        (current.files.length >= maxFiles || current.bytes + file.size > FOLDER_BATCH_BYTES)) {
      batches.push(current);
      current = { files: [], bytes: 0 };
    }
    current.files.push(file);
    current.bytes += file.size;
  });

  if (current.files.length > 0) {
    batches.push(current);
  }
  return batches;
}

// The server stores a batch's files one after another (~0.5 s each on remote
// storage), so the last batches are kept small: otherwise the bar waits at
// the end while the final large batches are still being stored.
function _splitFolderBatches(files) {
  var batches = _chunkFiles(files, FOLDER_BATCH_FILES);
  var tail = batches.splice(Math.max(0, batches.length - FOLDER_PARALLEL));
  var tailFiles = [];
  tail.forEach(function (batch) {
    tailFiles = tailFiles.concat(batch.files);
  });
  return batches.concat(_chunkFiles(tailFiles, FOLDER_TAIL_FILES));
}

function _uploadFolderInBatches(files, itemId, onComplete) {
  var batches = _splitFolderBatches(files);
  var totalBytes = files.reduce(function (sum, file) { return sum + file.size; }, 0);
  var finishedBytes = 0;
  var doneFiles = 0;
  var failedFiles = [];
  var active = [];
  var retrying = 0;
  var next = 0;
  var idRoot = null;
  var cancelled = false;
  var finished = false;
  var startTime = Date.now();

  var state = _uploadPanelItems[itemId];
  if (state) {
    state.xhr = {
      abort: function () {
        cancelled = true;
        active.slice().forEach(function (entry) {
          entry.xhr.abort();
        });
        pump();
      },
    };
  }

  // The bar follows stored files, not sent bytes: storing takes longer than
  // sending, so a byte-based bar sat at 100% while the server was still busy.
  // Bytes in flight count half; the bar reaches 100% only when all is done.
  function render() {
    if (finished) return;
    var item = document.getElementById("upi-" + itemId);
    if (!item) return;

    var sent = finishedBytes;
    var inFlight = 0;
    active.forEach(function (entry) {
      sent += entry.fraction * entry.bytes;
      inFlight += entry.fraction * entry.files * 0.5;
    });
    sent = Math.min(sent, totalBytes);

    var progress = (doneFiles + failedFiles.length + inFlight) / files.length;
    var pct = Math.min(99, Math.floor(progress * 100));
    var elapsed = (Date.now() - startTime) / 1000;

    var eta;
    if (sent >= totalBytes) {
      eta = "Saving...";
    } else if (progress >= 0.02) {
      eta = _formatEta(elapsed * (1 - progress) / progress) || "Saving...";
    } else {
      eta = "Calculating...";
    }

    item.querySelector(".progress-bar").style.width = pct + "%";
    item.querySelector(".up-speed").textContent = _formatSpeed(elapsed > 0 ? sent / elapsed : 0);
    item.querySelector(".up-eta").textContent =
      doneFiles + "/" + files.length + " files \u2014 " + pct + "% \u2014 " + eta;
  }

  function finalize() {
    if (finished) return;
    finished = true;

    if (cancelled) {
      _finishUploadItem(itemId, false, true);
    } else if (failedFiles.length === 0) {
      _finishUploadItem(itemId, true, false);
    } else {
      _finishUploadItem(itemId, false, false);
      var status = document.querySelector("#upi-" + itemId + " .up-status-error");
      if (status) {
        status.textContent = "Failed: " + failedFiles.length + " of " + files.length + " files";
      }
      console.error("Folder upload: these files were not uploaded:", failedFiles);
      alert(failedFiles.length + " of " + files.length + " files could not be uploaded.\n\n" +
        failedFiles.slice(0, 10).join("\n") + (failedFiles.length > 10 ? "\n..." : ""));
    }
    onComplete(doneFiles > 0);
  }

  function pump() {
    if (finished) return;
    if (cancelled) {
      if (active.length === 0 && retrying === 0) finalize();
      return;
    }
    while (active.length + retrying < FOLDER_PARALLEL && next < batches.length) {
      send(batches[next++], 0);
    }
    if (active.length === 0 && retrying === 0 && next >= batches.length) {
      finalize();
    }
  }

  function send(batch, attempt) {
    var formData = _folderFormData(idRoot);
    batch.files.forEach(function (file) {
      formData.append("Storage[file][]", file, _relativePath(file));
    });

    var entry = { bytes: batch.bytes, files: batch.files.length, fraction: 0, xhr: null };
    active.push(entry);
    entry.xhr = _postStorageForm(formData, function (fraction) {
      entry.fraction = fraction;
      render();
    }, function (status, json) {
      active.splice(active.indexOf(entry), 1);

      if (status >= 200 && status < 300 && json && json.success) {
        finishedBytes += batch.bytes;
        doneFiles += batch.files.length;
      } else if (!cancelled && FOLDER_RETRY_STATUSES.indexOf(status) !== -1 && attempt < FOLDER_RETRIES) {
        retrying++;
        setTimeout(function () {
          retrying--;
          if (cancelled) {
            pump();
            return;
          }
          send(batch, attempt + 1);
        }, 2000 * (attempt + 1));
        render();
        return;
      } else if (status !== -1) {
        finishedBytes += batch.bytes;
        failedFiles = failedFiles.concat(batch.files.map(_relativePath));
        console.error("Folder upload batch failed:", status, json);
      }

      render();
      pump();
    });
  }

  var directories = {};
  files.forEach(function (file) {
    var path = _relativePath(file);
    var cut = path.lastIndexOf("/");
    if (cut > 0) {
      directories[path.substring(0, cut)] = true;
    }
  });

  if (Object.keys(directories).length === 0) {
    pump();
    return;
  }

  // The tree is created first so parallel batches never create the same
  // subdirectory twice.
  var treeForm = _folderFormData(null);
  treeForm.append("Storage[directories]", JSON.stringify(Object.keys(directories)));
  var treeEntry = { bytes: 0, files: 0, fraction: 0, xhr: null };
  active.push(treeEntry);
  treeEntry.xhr = _postStorageForm(treeForm, null, function (status, json) {
    active.splice(active.indexOf(treeEntry), 1);
    if (status >= 200 && status < 300 && json && json.success && json.id_root) {
      idRoot = json.id_root;
    } else if (!cancelled) {
      next = batches.length;
      finishedBytes = totalBytes;
      failedFiles = files.map(_relativePath);
      console.error("Folder upload: could not create the folder tree:", status, json);
    }
    pump();
  });
}

function uploadFolderMenu(event) {
  event.preventDefault();
  const newDropdownBtn = $("#newDropdownBtn");

  let fileInput = document.getElementById("hiddenUploadInput");
  if (fileInput) {
    fileInput.remove();
  }

  fileInput = document.createElement("input");
  fileInput.type = "file";
  fileInput.id = "hiddenUploadInput";
  fileInput.style.display = "none";
  fileInput.webkitdirectory = true;
  fileInput.multiple = false;
  document.body.appendChild(fileInput);

  fileInput.addEventListener("change", function () {
    if (fileInput.files.length > 0) {
      newDropdownBtn.addClass("btn-loading");
      const files = Array.from(fileInput.files).sort(function (a, b) {
        return _relativePath(a).localeCompare(_relativePath(b));
      });

      var folderName = _relativePath(files[0]).split("/")[0] || "Folder";
      var itemId = "folder-" + Date.now();

      var header = document.getElementById("upload-progress-header");
      if (header) {
        header.querySelector(".upload-panel-title").innerHTML =
          '<i class="fa fa-cloud-upload-alt"></i> Uploading';
      }

      _addUploadItem(itemId, folderName + " (" + files.length + " files)");

      _uploadFolderInBatches(files, itemId, function (anyUploaded) {
        if (!anyUploaded) {
          newDropdownBtn.removeClass("btn-loading");
          return;
        }
        if (window.isSearching) {
          const searchValue = $("#searchFileInput").val().trim();
          if (searchValue) {
            performSearch(searchValue);
            newDropdownBtn.removeClass("btn-loading");
            return;
          }
        }
        const reloadUrl = window.lastListItemPjaxUrl || getBaseUrl();
        $.pjax.reload({
          container: "#list-item-pjax",
          url: reloadUrl,
          replace: false,
          push: false,
        }).done(function () {
          newDropdownBtn.removeClass("btn-loading");
        });
      });
    }
  });

  fileInput.click();
}

function openNewFolderModal(event) {
  event.preventDefault();

  const newDropdownBtn = $("#newDropdownBtn");
  newDropdownBtn.addClass("btn-loading");

  let url = "/storage/default/new-folder";

  if (window.currentDirectoryId) {
    url += "?id_directory=" + window.currentDirectoryId;
  } else {
    url += "?id_directory=null";
  }

  if (window.currentIsPicker) {
    url += "&isPicker=1";
  }

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      newDropdownBtn.removeClass("btn-loading");
      $('.modal[id^="newFolderModal"]').remove();
      $("#new-folder-pjax").html(response);
      showModal("newFolderModal");

      const modal = $("#newFolderModal");
      modal.find("#storagedirectory-name").on("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          modal.find("#createFolderButton").click();
        }
      });
    },
    error: function (e) {
      console.error("Error loading new folder modal:", e);
      newDropdownBtn.removeClass("btn-loading");
    },
  });
}

// When the create new folder button is clicked
$(document)
  .off("click", "#createFolderButton")
  .on("click", "#createFolderButton", function (e) {
    e.preventDefault();

    const form = document.getElementById("newFolderForm");
    if (!form) return;

    const formData = new FormData(form);

    if (window.currentDirectoryId) {
      formData.append("id_directory", window.currentDirectoryId);
    } else {
      formData.append("id_directory", null);
    }

    if (window.currentIsPicker) {
      formData.append("isPicker", "1");
    }

    $.ajax({
      url: form.action,
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      complete: function () {
        hideModal("newFolderModal");

        if (window.isSearching) {
          const searchValue = $("#searchFileInput").val().trim();
          if (searchValue) {
            performSearch(searchValue);
            return;
          }
        }

        const reloadUrl = window.lastListItemPjaxUrl || getBaseUrl();
        $.pjax
          .reload({
            container: "#list-item-pjax",
            url: reloadUrl,
            replace: false,
            push: false,
          })
          .done(function () {
            $.pjax.reload({ container: "#pjax-flash-message" });
          });
      },
    });
  });

function openRenameFolderModal(id) {
  if (event) event.preventDefault();
  var shareId = $('#current-share-id').length ? $('#current-share-id').val() : null;
  let url = "/storage/default/rename-folder?id=" + id;
  if (window.currentDirectoryId) {
    url += "&id_directory=" + window.currentDirectoryId;
  } else {
    url += "&id_directory=null";
  }

  if (window.currentIsPicker) {
    url += "&isPicker=1";
  }
  
  if (shareId) {
    url += "&id_share=" + shareId;
  }

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      $('.modal[id^="renameFolderModal"]').remove();
      $("#rename-folder-pjax").html(response);
      setTimeout(function () {
        if ($("#renameFolderModal").length) {
          const modal = $("#renameFolderModal");
          showModal("renameFolderModal");

          modal.find("#storagedirectory-name").on("keydown", function (e) {
            if (e.key === "Enter") {
              e.preventDefault();
              modal.find("#renameFolderButton").click();
            }
          });
        } else {
          refreshCurrentView();
        }
      }, 100);
    },
    error: function (e) {
      console.log("Error Modal:", e);
      refreshCurrentView();
    },
  });
}

$(document).on("click", "#renameFolderButton", function (e) {
  e.preventDefault();

  var form = document.getElementById("renameFolderForm");
  var formData = new FormData(form);

  if (window.currentDirectoryId) {
    formData.append("id_directory", window.currentDirectoryId);
  } else {
    formData.append("id_directory", "null");
  }

  if (window.currentIsPicker) {
    formData.append("isPicker", "1");
  }

  $.ajax({
    url:
      "/storage/default/rename-folder?id=" +
      $("#renameFolderButton").data("id") +
      "&id_directory=" +
      window.currentDirectoryId,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    complete: function () {
      hideModal("renameFolderModal");
      refreshCurrentView();
    },
  });
});

function deleteFolder(id) {
  if (event) event.preventDefault();
  var shareId = $('#current-share-id').length ? $('#current-share-id').val() : null;

  var postData = window.currentIsPicker ? { isPicker: "1" } : {};
  if (shareId) {
    postData.id_share = shareId;
  }

  $.ajax({
    url:
      "/storage/default/delete-folder?id_directory=" +
      (window.currentDirectoryId || "null") +
      "&id=" +
      id,
    type: "POST",
    data: postData,
    headers: {
      "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
    },
    dataType: "json",
    complete: function () {
      if (shareId) {
        location.reload();
      } else {
        refreshCurrentView();
      }
    },
  });
}

function downloadFile(id) {
  var a = document.createElement("a");
  a.href = "/storage/default/download-file?id=" + encodeURIComponent(id);
  a.style.display = "none";
  document.body.appendChild(a);
  a.click();
  setTimeout(function () { document.body.removeChild(a); }, 200);
}

function refreshCurrentView() {
  if (window.isSearching) {
    const searchValue = $("#searchFileInput").val().trim();
    if (searchValue) {
      performSearch(searchValue);
    } else {
      returnToMainPage();
    }
  } else {
    const reloadUrl = window.lastListItemPjaxUrl || getBaseUrl();

    $.pjax
      .reload({
        container: "#list-item-pjax",
        url: reloadUrl,
        replace: false,
        push: false,
      })
      .done(function () {
        $.pjax.reload({ container: "#pjax-flash-message" });

        const mode = localStorage.getItem("viewMode") || "grid";
        if (typeof setViewMode === 'function') {
          setViewMode(mode);
        }
      });
  }
}

function performSearch(query) {
  if (!query || query.trim() === "") {
    returnToMainPage();
    return;
  }

  window.isSearching = true;
  const isPicker = $("#searchFileInput").data("is-picker") ? 1 : 0;

  const fileExtensions = Array.isArray(window.fileExtensions)
    ? window.fileExtensions.join(",")
    : "";
  let finalUrl =
    "/storage/default/search?q=" +
    encodeURIComponent(query) +
    "&isPicker=" +
    isPicker;

  if (window.currentDirectoryId !== null) {
    finalUrl += "&id_directory=" + window.currentDirectoryId;
  }

  if (fileExtensions) {
    finalUrl += "&fileExtensions=" + encodeURIComponent(fileExtensions);
  }

  if (window.allowFolderSelection) {
    finalUrl += "&allowFolderSelection=1";
  }


  const container = isInWidgetContext() ? "#list-file-pjax" : "#list-item-pjax";

  $.pjax.reload({
    container: container,
    url: finalUrl,
    timeout: 10000,
    push: false,
    replace: false,
  });
}

async function refreshFileList() {
  return await new Promise((resolve, reject) => {
    const container = isInWidgetContext()
      ? "#list-file-pjax"
      : "#list-item-pjax";

    if ($(container).length) {
      let refreshUrl = "/storage/default/file-list";
      if (window.currentIsPicker) {
        refreshUrl += "?isPicker=1";
      }

      const fileExtensions = Array.isArray(window.fileExtensions)
        ? window.fileExtensions.join(",")
        : "";
      if (fileExtensions) {
        const separator = refreshUrl.includes("?") ? "&" : "?";
        refreshUrl +=
          separator + "fileExtensions=" + encodeURIComponent(fileExtensions);
      }

      $.pjax.reload({
        container: container,
        timeout: false,
        url: refreshUrl,
        complete: function () {
          resolve();
        },
      });
    } else {
      reject("File list container not found");
    }
  });
}

function bindPageSizer() {
  const $select = $("#file-page-sizer select");

  $select.each(function () {
    this.onchange = null;
  });

  $select.off("change").on("change", function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    const container = "#list-item-pjax";
    const sizeUrl = new URL(
      $(this).find("option:selected").attr("redirect-url") || getBaseUrl(),
      window.location.origin
    );
    // search results reload themselves; every other list (picker included) reloads through index/manage
    const isSearch = sizeUrl.pathname.endsWith("/storage/default/search");
    const url = isSearch ? sizeUrl : new URL(getBaseUrl(), window.location.origin);
    if (!isSearch) {
      ["viewMode", "sortField", "sortDirection"].forEach(function (key) {
        if (sizeUrl.searchParams.has(key)) {
          url.searchParams.set(key, sizeUrl.searchParams.get(key));
        }
      });
    }
    url.searchParams.delete("_pjax");
    url.searchParams.delete("page");
    url.searchParams.set("per-page", $(this).val());

    if (window.selectedIdStorage) {
      url.searchParams.set("selectedFileId", window.selectedIdStorage);
    } else {
      url.searchParams.delete("selectedFileId");
    }
    const reloadUrl = url.pathname + url.search;

    $.pjax.reload({
      container: container,
      url: reloadUrl,
      push: false,
      replace: false,
      timeout: 10000,
    });
  });
}

function bindSearchInput() {
  $(document)
    .off("keyup.search input.search")
    .on("keyup.search input.search", "#searchFileInput", function () {
      clearTimeout(window.searchTimer);
      const q = $(this).val().trim();

      window.searchTimer = setTimeout(function () {
        if (q === "") {
          returnToMainPage();
        } else {
          performSearch(q);
        }
      }, 500);
    });
}

$(document)
  .off("click.fileActions")
  .on("click.fileActions", ".file-action", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const action = $(this).data("action");
    const id = $(this).closest("[data-id]").data("id");

    if (!id) return;

    switch (action) {
      case "copy":
        copyFile(id, e);
        break;
      case "delete":
        deleteFile(id, e);
        break;
      case "download":
        downloadFile(id, e);
        break;
      case "rename":
        openRenameModal(id, e);
        break;
      case "update":
        openUpdateModal(id, e);
        break;
      case "share":
        openShareModal(id, e);
        break;
    }
  });


$(document).ready(function () {
  bindSearchInput();
  bindPageSizer();

  $(document).on('click.fileItem', '.file-item', function (event) {
    console.log('File item clicked in storageactions.js!', {
    ctrlKey: event.ctrlKey,
      target: event.target,
      this: this,
      id: $(this).closest('.file-card').data('id'),
    });

    if (event.ctrlKey) {
      event.preventDefault();
      event.stopPropagation();

      const id_storage = $(this).closest('.file-card').data('id');

      if (id_storage && typeof window.toggleBulkSelection === 'function') {
        window.toggleBulkSelection(id_storage, event);
      } else {
        console.warn('toggleBulkSelection not available or id_storage missing');
      }
      return false;
    }
  });

  if (typeof window.restoreBulkSelection === 'function') {
    window.restoreBulkSelection();
  }
});

$(document).on("pjax:end", function () {
  bindSearchInput();
  bindPageSizer();

  if (typeof window.updateFileCard === "function") {
    window.updateFileCard(window.selectedIdStorage);
  }


  $(document).off('click.fileItem').on('click.fileItem', '.file-item', function (event) {
    //   ctrlKey: event.ctrlKey,
    //   target: event.target,
    //   id: $(this).closest('.file-card').data('id'),
    // });

    if (event.ctrlKey) {
      event.preventDefault();
      event.stopPropagation();

      const id_storage = $(this).closest('.file-card').data('id');

      if (id_storage && typeof window.toggleBulkSelection === 'function') {
        window.toggleBulkSelection(id_storage, event);
      }
      return false;
    }
  });

  if (typeof window.restoreBulkSelection === 'function') {
    window.restoreBulkSelection();
  }
});

// ==============================================================================
// SHARE FUNCTIONS - COMBINED FOR 4 BUTTONS
// ==============================================================================

function openRenameModal(id) {
  if (event) event.preventDefault();
  var shareId = $('#current-share-id').val();
  let url = "/storage/default/rename-file?id=" + id;
  if (window.currentDirectoryId) { url += "&id_directory=" + window.currentDirectoryId; } else { url += "&id_directory=null"; }
  if (window.currentIsPicker) { url += "&isPicker=1"; }
  if (shareId) { url += "&id_share=" + shareId; }

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      $('.modal[id^="renameModal"]').remove();
      $("#rename-file-pjax").html(response);
      setTimeout(function () {
        if ($("#renameModal").length) {
          showModal("renameModal");
        } else {
          refreshCurrentView();
        }
      }, 100);
    }
  });
}

$(document).off("click", "#renameButton").on("click", "#renameButton", function (e) {
  e.preventDefault();
  var form = document.getElementById("renameForm");
  var formData = new FormData(form);
  var shareId = $('#current-share-id').val();

  if (window.currentDirectoryId) { formData.append("id_directory", window.currentDirectoryId); }
  else { formData.append("id_directory", "null"); }

  if (window.currentIsPicker) { formData.append("isPicker", "1"); }
  if (shareId) { formData.append("id_share", shareId); }

  $.ajax({
    url: form.action,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    complete: function () {
      hideModal("renameModal");
      if (shareId) { location.reload(); } else { refreshCurrentView(); }
    },
  });
});

function openUpdateModal(id) {
  if (event) event.preventDefault();
  var shareId = $('#current-share-id').val();
  let url = "/storage/default/update-file?id=" + id;
  if (window.currentDirectoryId) { url += "&id_directory=" + window.currentDirectoryId; } else { url += "&id_directory=null"; }
  if (window.currentIsPicker) { url += "&isPicker=1"; }
  if (shareId) { url += "&id_share=" + shareId; }

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      $('.modal[id^="updateModal"]').remove();
      $("#update-file-pjax").html(response);
      setTimeout(function () {
        if ($("#updateModal").length) {
          showModal("updateModal");
        } else {
          refreshCurrentView();
        }
      }, 100);
    }
  });
}

$(document).off("click", "#updateButton").on("click", "#updateButton", function (e) {
  e.preventDefault();
  var form = document.getElementById("updateForm");
  var formData = new FormData(form);
  var shareId = $('#current-share-id').val();

  if (window.currentDirectoryId) { formData.append("id_directory", window.currentDirectoryId); }
  else { formData.append("id_directory", "null"); }

  if (window.currentIsPicker) { formData.append("isPicker", "1"); }
  if (shareId) { formData.append("id_share", shareId); }

  $.ajax({
    url: form.action,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    complete: function () {
      hideModal("updateModal");
      if (shareId) { location.reload(); } else { refreshCurrentView(); }
    },
  });
});

function openShareModal(id) {
  if (event) event.preventDefault();
  var shareId = $('#current-share-id').val();
  let url = "/storage/default/share-file?id=" + id;
  if (window.currentDirectoryId) { url += "&id_directory=" + window.currentDirectoryId; } else { url += "&id_directory=null"; }
  if (window.currentIsPicker) { url += "&isPicker=1"; }
  if (shareId) { url += "&id_share=" + shareId; }

  var pickerEl = document.getElementById('file-picker-modal');
  var reopenPicker = !!pickerEl;

  if (reopenPicker) {
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
    var pickerModal = bootstrap.Modal.getInstance(pickerEl);
    if (pickerModal) {
      pickerModal.hide();
    } else {
      // console.warn('[SHARE] No Bootstrap instance on picker, trying manual hide');
      pickerEl.style.display = 'none';
      pickerEl.classList.remove('show');
      document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
      document.body.classList.remove('modal-open');
    }
  }

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      $('.modal[id^="shareModal"]').remove();
      if (reopenPicker) {
        $('body').append(response);
      } else {
        $("#share-file-pjax").html(response);
      }
      setTimeout(function () {
        var shareModalEl = document.getElementById('shareModal');
        if (shareModalEl) {
          if (reopenPicker) {
            shareModalEl.addEventListener('hidden.bs.modal', function() {
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
              }
            }, { once: true });
          }
          showModal("shareModal");
        } else {
          if (!reopenPicker) refreshCurrentView();
        }
      }, 100);
    }
  });
}

function copyFile(id) {
  var shareId = $('#current-share-id').val();

  $.ajax({
    url: "/storage/default/copy-file",
    type: "POST",
    data: { id: id, id_share: shareId },
    dataType: "json",
    headers: { "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content") },
    success: function (response) {
      if (response.success) {
        // Redirect or refresh if successful
        alert("File successfully copied.");
        if (shareId) {
          location.reload();
        } else {
          $.pjax.reload({ container: '#list-item-pjax' });
        }
      } else {
        alert("Error: " + response.message);
      }
    }
  });
}
function deleteFile(id) {
  var shareId = $('#current-share-id').val();
  $.ajax({
    url: "/storage/default/delete-file",
    type: "POST",
    data: {
      id: id,
      id_directory: window.currentDirectoryId || null,
      isPicker: window.currentIsPicker ? "1" : "0",
      id_share: shareId ? shareId : null
    },
    headers: {
      "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
    },
    success: function () {
      if (shareId) {
        window.location.href = '/storage/default/index';
      } else {
        refreshCurrentView();
      }
    },
    error: function () {
      alert("File could not be deleted! You must have 'Manage' permissions to delete this file.");
    }
  });
}

function openShareFolderModal(id) {
  if (event) event.preventDefault();
  var shareId = $('#current-share-id').length ? $('#current-share-id').val() : null;
  setTimeout(function () {
    let url = "/storage/default/share-directory?id=" + id;
    if (window.currentDirectoryId) { url += "&id_directory=" + window.currentDirectoryId; } else { url += "&id_directory=null"; }
    if (window.currentIsPicker) { url += "&isPicker=1"; }
    if (shareId) { url += "&id_share=" + shareId; }

    $.ajax({
      url: url,
      type: "GET",
      success: function (response) {
        $('.modal[id^="shareModal"]').remove();
        $("#share-file-pjax").html(response);
        setTimeout(function () {
          if ($("#shareModal").length) { showModal("shareModal"); } else { refreshCurrentView(); }
        }, 100);
      }
    });
  }, 500);
}

function openShareStorageModal(event) {
  if (event) event.preventDefault();
  setTimeout(function () {
    let url = "/storage/default/share-full-storage";
    $.ajax({
      url: url,
      type: "GET",
      success: function (response) {
        $('.modal[id^="shareModal"]').remove();
        $("#share-file-pjax").html(response);
        setTimeout(function () {
          if ($("#shareModal").length) { showModal("shareModal"); }
        }, 100);
      }
    });
  }, 500);
}

// Security Filter (Updated only to prevent ShareModal's JSON data from being corrupted)
$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
  var shareId = $('#current-share-id').val();
  var storageId = $('#current-storage-id').val();

  if (shareId && options.type && options.type.toUpperCase() === 'POST') {
    if (options.data instanceof FormData) {
      if (!options.data.has('id_share')) options.data.append('id_share', shareId);
      if (storageId && !options.data.has('id_storage')) options.data.append('id_storage', storageId);
    } else if (typeof options.data === 'string') {
      if (options.contentType && options.contentType.indexOf('application/json') !== -1) {
        try {
          var jsonData = JSON.parse(options.data);
          jsonData.id_share = shareId;
          if (storageId) jsonData.id_storage = storageId;
          options.data = JSON.stringify(jsonData);
        } catch (e) { console.error('JSON Parse Error:', e); }
      } else {
        if (options.data.indexOf('id_share=') === -1) options.data += '&id_share=' + shareId;
        if (storageId && options.data.indexOf('id_storage=') === -1) options.data += '&id_storage=' + storageId;
      }
    }
  }
});

// Assignments to the Window object
window.openRenameModal = openRenameModal;
window.openUpdateModal = openUpdateModal;
window.openShareModal = openShareModal;
window.openShareFolderModal = openShareFolderModal;
window.openShareStorageModal = openShareStorageModal;
window.openRenameFolderModal = openRenameFolderModal;
window.downloadFile = downloadFile;
window.copyFile = copyFile;
window.deleteFile = deleteFile;
window.deleteFolder = deleteFolder;

// MOVE FUNCTIONS

function openMoveModal(ids) {
  if (event) event.preventDefault();
  if (!Array.isArray(ids)) ids = [ids];

  var url = "/storage/default/move-modal?" + ids.map(function (id) { return "ids[]=" + id; }).join("&");

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      $(".modal[id='moveItemsModal']").remove();
      $("#move-items-pjax").html(response);
      setTimeout(function () {
        if ($("#moveItemsModal").length) {
          showModal("moveItemsModal");
        }
      }, 100);
    },
    error: function (e) {
      console.error("Error loading move modal:", e);
    },
  });
}

function bulkMoveFiles() {
  if (typeof window.selectedFiles === "undefined" || window.selectedFiles.size === 0) return;
  var ids = Array.from(window.selectedFiles);
  openMoveModal(ids);
}

function selectMoveTarget(el, targetId) {
  $(".move-dir-item").removeClass("active");
  $(el).addClass("active");
  $("#moveTargetDirectory").val(targetId);
}

$(document)
  .off("click", "#confirmMoveBtn")
  .on("click", "#confirmMoveBtn", function (e) {
    e.preventDefault();

    var ids = $(this).data("ids");
    var targetDirectory = $("#moveTargetDirectory").val();

    $.ajax({
      url: "/storage/default/move-items",
      type: "POST",
      data: {
        ids: ids,
        target_directory: targetDirectory !== "" ? targetDirectory : "null",
      },
      headers: {
        "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
      },
      dataType: "json",
      complete: function () {
        hideModal("moveItemsModal");
        refreshCurrentView();
        if (typeof window.clearBulkSelection === "function") {
          window.clearBulkSelection();
        }
      },
    });
  });

window.openMoveModal = openMoveModal;
window.bulkMoveFiles = bulkMoveFiles;
window.selectMoveTarget = selectMoveTarget;