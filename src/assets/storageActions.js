var currentDirectoryId = null;
var currentIsPicker = $("#searchFileInput").data("is-picker") === 1;
var searchTimer;
var isSearching = false;
var originalUrl = window.location.href;

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
  console.log("Hiding modal:", modalId);
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
  let basePath = isManagePage() ? "/storage/default/manage" : "/storage/default/index";
  let url = basePath;

  if (currentDirectoryId) {
    url += "?id_directory=" + currentDirectoryId;
  }

  if (currentIsPicker) {
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

  return url;
}


function returnToMainPage() {
  isSearching = false;
  const baseUrl = getBaseUrl();

  $.pjax
    .reload({
      container: "#list-item-pjax",
      url: baseUrl,
      push: false,
      replace: false,
      timeout: 10000,
      complete: function () {
        console.log("Ana sayfaya döndü, pagination restore edildi");
      },
    })
    .done(function () {
      $.pjax.reload({ container: "#pjax-flash-message" });
    });
}

window.openFolder = function (id_directory, event) {
  if (
    event &&
    (event.target.classList.contains("folder-ellipsis") ||
      $(event.target).closest(".folder-dropdown-menu").length)
  ) {
    return;
  }
  currentDirectoryId =
    id_directory === null || id_directory === undefined
      ? null
      : parseInt(id_directory);

  let url = isManagePage() ? "/storage/default/manage" : "/storage/default/index";
  if (id_directory) {
    url += "?id_directory=" + id_directory;
  }

  if (currentIsPicker) {
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

  isSearching = false;
  $("#searchFileInput").val("");

  $.pjax.reload({
    container: "#list-item-pjax",
    url: url,
    push: false,
    replace: false,
    timeout: 10000,
    complete: function () {
      if (!url.includes("id_directory=")) currentDirectoryId = null;
    },
  });
};

function uploadFileMenu(event) {
  event.preventDefault();
  const newDropdownBtn = $("#newDropdownBtn");
  newDropdownBtn.addClass("btn-loading");

  let fileInput = document.getElementById("hiddenUploadInput");
  if (fileInput) {
    fileInput.remove();
  }

  fileInput = document.createElement("input");
  fileInput.type = "file";
  fileInput.id = "hiddenUploadInput";
  fileInput.style.display = "none";
  fileInput.multiple = true;
  document.body.appendChild(fileInput);

  fileInput.addEventListener("change", function () {
    if (fileInput.files.length > 0) {
      const files = Array.from(fileInput.files);

      let completed = 0;
      files.forEach(file => {
        const formData = new FormData();
        formData.append("Storage[file]", file);
        formData.append("Storage[title]", file.name);
        formData.append("id_directory", currentDirectoryId ? currentDirectoryId : "");

        if (currentIsPicker) {
          formData.append("isPicker", "1");
        }

        $.ajax({
          url: "/storage/default/upload-file",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          headers: {
            "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content")
          },
          success: function () {
            completed++;
            if (completed === files.length) {
              if (isSearching) {
                const searchValue = $("#searchFileInput").val().trim();
                if (searchValue) {
                  performSearch(searchValue);
                  return;
                }
              }

              const reloadUrl = getBaseUrl();
              $.pjax.reload({
                container: "#list-item-pjax",
                url: reloadUrl,
                replace: false,
                push: false,
              });
            }
          },
          error: function (xhr) {
            newDropdownBtn.removeClass("btn-loading");
            console.error("Yükleme hatası:", xhr);
          },
        });
      });
    }
  });

  fileInput.click();
  newDropdownBtn.removeClass("btn-loading");
}


function uploadFolderMenu(event) {
  event.preventDefault();
  const newDropdownBtn = $("#newDropdownBtn");
  newDropdownBtn.addClass("btn-loading");

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
      const formData = new FormData();

      Array.from(fileInput.files).forEach(file => {
        formData.append("Storage[file][]", file); 
      });


      formData.append("Storage[type]", "folder");
      formData.append("id_directory", currentDirectoryId ? currentDirectoryId : "");

      if (currentIsPicker) {
        formData.append("isPicker", "1");
      }

      $.ajax({
        url: "/storage/default/upload-file",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        headers: {
          "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content")
        },
        success: function () {
          if (isSearching) {
            const searchValue = $("#searchFileInput").val().trim();
            if (searchValue) {
              performSearch(searchValue);
              return;
            }
          }

          const reloadUrl = getBaseUrl();
          $.pjax.reload({
            container: "#list-item-pjax",
            url: reloadUrl,
            replace: false,
            push: false,
          });
        },
        error: function (xhr) {
          newDropdownBtn.removeClass("btn-loading");
          console.error("Yükleme hatası:", xhr);
        },
      });
    }
  });

  fileInput.click();
  newDropdownBtn.removeClass("btn-loading");
}

function openNewFolderModal(event) {
  event.preventDefault();

  const newDropdownBtn = $("#newDropdownBtn");
  newDropdownBtn.addClass("btn-loading");

  let url = "/storage/default/new-folder";

  if (currentDirectoryId) {
    url += "?id_directory=" + currentDirectoryId;
  } else {
    url += "?id_directory=null";
  }

  if (currentIsPicker) {
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
    },
    error: function (e) {
      console.error("Error loading new folder modal:", e);
      newDropdownBtn.removeClass("btn-loading");
    },
  });
}

// Yeni klasör oluşturma butonuna tıklanıldığında
$(document).off("click", "#createFolderButton").on("click", "#createFolderButton", function (e) {
  e.preventDefault();

  const form = document.getElementById("newFolderForm");
  if (!form) return;

  const formData = new FormData(form);

  if (currentDirectoryId) {
    formData.append("id_directory", currentDirectoryId);
  } else {
    formData.append("id_directory", null);
  }

  if (currentIsPicker) {
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

      if (isSearching) {
        const searchValue = $("#searchFileInput").val().trim();
        if (searchValue) {
          performSearch(searchValue);
          return;
        }
      }

      const reloadUrl = getBaseUrl();
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
  event.preventDefault();
  let url = "/storage/default/rename-folder?id=" + id;
  if (currentDirectoryId) {
    url += "&id_directory=" + currentDirectoryId;
  } else {
    url += "&id_directory=null";
  }

  if (currentIsPicker) {
    url += "&isPicker=1";
  }

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      $('.modal[id^="renameFolderModal"]').remove();
      $("#rename-folder-pjax").html(response);
      setTimeout(function () {
        if ($("#renameFolderModal").length) {
          showModal("renameFolderModal");
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

  if (currentDirectoryId) {
    formData.append("id_directory", currentDirectoryId);
  } else {
    formData.append("id_directory", "null");
  }

  if (currentIsPicker) {
    formData.append("isPicker", "1");
  }

  $.ajax({
    url:
      "/storage/default/rename-folder?id=" +
      $("#renameFolderButton").data("id") +
      "&id_directory=" +
      currentDirectoryId,
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
  event.preventDefault();

  $.ajax({
    url:
      "/storage/default/delete-folder?id_directory=" +
      (currentDirectoryId || "null") +
      "&id=" +
      id,
    type: "POST",
    data: currentIsPicker ? { isPicker: "1" } : {},
    headers: {
      "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
    },
    dataType: "json",
    complete: function () {
      refreshCurrentView();
    },
  });
}

function downloadFile(id) {
  $.post({
    url: "/storage/default/download-file",
    data: {
      id: id,
      isPicker: currentIsPicker ? "1" : "0",
    },
    xhrFields: { responseType: "blob" },
    headers: {
      "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
    },
    success: function (data, status, xhr) {
      const disposition = xhr.getResponseHeader("Content-Disposition");
      if (disposition && disposition.indexOf("attachment") !== -1) {
        const filename =
          disposition.split("filename=")[1]?.replace(/["']/g, "") ||
          "downloaded_file";
        const blobUrl = URL.createObjectURL(data);
        const a = document.createElement("a");
        a.href = blobUrl;
        a.download = decodeURIComponent(filename);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(blobUrl);
      }

      refreshCurrentView();
    },
    error: function () {
      refreshCurrentView();
    },
  });
}

function openRenameModal(id) {
  event.preventDefault();
  let url = "/storage/default/rename-file?id=" + id;
  if (currentDirectoryId) {
    url += "&id_directory=" + currentDirectoryId;
  } else {
    url += "&id_directory=null";
  }

  if (currentIsPicker) {
    url += "&isPicker=1";
  }

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
    },
    error: function (e) {
      console.log("Error Modal:", e);
      refreshCurrentView();
    },
  });
}

$(document).on("click", "#renameButton", function (e) {
  e.preventDefault();
  var form = document.getElementById("renameForm");
  var formData = new FormData(form);

  if (currentDirectoryId) {
    formData.append("id_directory", currentDirectoryId);
  } else {
    formData.append("id_directory", "null");
  }

  if (currentIsPicker) {
    formData.append("isPicker", "1");
  }

  $.ajax({
    url: form.action,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    complete: function () {
      hideModal("renameModal");
      refreshCurrentView();
    },
  });
});

function openUpdateModal(id) {
  event.preventDefault();
  let url = "/storage/default/update-file?id=" + id;
  if (currentDirectoryId) {
    url += "&id_directory=" + currentDirectoryId;
  } else {
    url += "&id_directory=null";
  }

  if (currentIsPicker) {
    url += "&isPicker=1";
  }

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
    },
    error: function (e) {
      console.log("Error Modal:", e);
      refreshCurrentView();
    },
  });
}

$(document).on("click", "#updateButton", function (e) {
  e.preventDefault();

  var form = document.getElementById("updateForm");
  var formData = new FormData(form);

  if (currentDirectoryId) {
    formData.append("id_directory", currentDirectoryId);
  } else {
    formData.append("id_directory", "null");
  }

  if (currentIsPicker) {
    formData.append("isPicker", "1");
  }

  $.ajax({
    url: form.action,
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    complete: function () {
      hideModal("updateModal");
      refreshCurrentView();
    },
  });
});

function openShareModal(id) {
  event.preventDefault();
  let url = "/storage/default/share-file?id=" + id;
  if (currentDirectoryId) {
    url += "&id_directory=" + currentDirectoryId;
  } else {
    url += "&id_directory=null";
  }

  if (currentIsPicker) {
    url += "&isPicker=1";
  }

  $.ajax({
    url: url,
    type: "GET",
    success: function (response) {
      $('.modal[id^="shareModal"]').remove();
      $("#share-file-pjax").html(response);
      setTimeout(function () {
        if ($("#shareModal").length) {
          showModal("shareModal");
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

$(document).on("click", "#shareButton", function (e) {
  e.preventDefault();

  var form = document.getElementById("shareForm");
  var formData = new FormData(form);

  if (currentDirectoryId) {
    formData.append("id_directory", currentDirectoryId);
  } else {
    formData.append("id_directory", "null");
  }

  if (currentIsPicker) {
    formData.append("isPicker", "1");
  }

  $.ajax({
    url:
      form.action +
      "?id_directory=" +
      (currentDirectoryId || "null") +
      "&id=" +
      $("#shareButton").data("id"),
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    complete: function () {
      hideModal("shareModal");
      refreshCurrentView();
    },
  });
});

function copyFile(id) {
  event.preventDefault();

  $.ajax({
    url: "/storage/default/copy-file",
    type: "POST",
    data: {
      id: id,
      id_directory: currentDirectoryId || null,
      isPicker: currentIsPicker ? "1" : "0",
    },
    headers: {
      "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
    },
    complete: function () {
      refreshCurrentView();
    },
  });
}

function deleteFile(id) {
  event.preventDefault();

  $.ajax({
    url: "/storage/default/delete-file",
    type: "POST",
    data: {
      id: id,
      id_directory: currentDirectoryId || null,
      isPicker: currentIsPicker ? "1" : "0",
    },
    headers: {
      "X-CSRF-Token": $('meta[name="csrf-token"]').attr("content"),
    },
    complete: function () {
      refreshCurrentView();
    },
  });
}

// Verileri burda gönderiyorum
function refreshCurrentView() {
  if (isSearching) {
    const searchValue = $("#searchFileInput").val().trim();
    if (searchValue) {
      performSearch(searchValue);
    } else {
      returnToMainPage();
    }
  } else {
    const reloadUrl = getBaseUrl();
    $.pjax
      .reload({
        container: "#list-item-pjax",
        url: reloadUrl,
        replace: false,
        push: false,
      })
      .done(function () {
        $.pjax.reload({ container: "#pjax-flash-message" });

        const mode = localStorage.getItem('viewMode') || 'grid';
        setViewMode(mode);
      });
  }
}

function performSearch(query) {
  if (!query || query.trim() === "") {
    returnToMainPage();
    return;
  }

  isSearching = true;
  const isPicker = $("#searchFileInput").data("is-picker") ? 1 : 0;

  // fileExtensions parametresini ekledim
  const fileExtensions = Array.isArray(window.fileExtensions)
    ? window.fileExtensions.join(",")
    : "";
  let finalUrl =
    "/storage/default/search?q=" +
    encodeURIComponent(query) +
    "&isPicker=" +
    isPicker;

  if (currentDirectoryId !== null) {
    finalUrl += "&id_directory=" + currentDirectoryId;
  }

  if (fileExtensions) {
    finalUrl += "&fileExtensions=" + encodeURIComponent(fileExtensions);
  }

  console.log("Search URL with extensions:", finalUrl);

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
      if (currentIsPicker) {
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

function bindSearchInput() {
  $(document)
    .off("keyup.search input.search")
    .on("keyup.search input.search", "#searchFileInput", function () {
      clearTimeout(searchTimer);
      const q = $(this).val().trim();

      searchTimer = setTimeout(function () {
        if (q === "") {
          console.log("Arama kutusu boş, ana sayfaya dönülüyor...");
          returnToMainPage();
        } else {
          console.log("Arama yapılıyor:", q);
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

window.openRenameModal = openRenameModal;
window.openUpdateModal = openUpdateModal;
window.openShareModal = openShareModal;
window.openRenameFolderModal = openRenameFolderModal;
window.downloadFile = downloadFile;
window.copyFile = copyFile;
window.deleteFile = deleteFile;
window.deleteFolder = deleteFolder;

$(document).ready(function () {
  bindSearchInput();
  console.log("Search binding initialized");
});

$(document).on("pjax:end", function () {
  bindSearchInput();
  console.log("Search binding refreshed after pjax");
  window.updateFileCard(window.selectedIdStorage);
});
