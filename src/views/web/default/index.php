<?php

use portalium\storage\bundles\StorageAsset;
use portalium\storage\Module;
use portalium\theme\widgets\Button;
use portalium\theme\widgets\Html;
use portalium\theme\widgets\Loading;
use portalium\widgets\Pjax;
use yii\helpers\Url;

/** @var $this yii\web\View */
/** @var $form portalium\theme\widgets\ActiveForm */
/** @var yii\data\ActiveDataProvider $directoryDataProvider */
/** @var yii\data\ActiveDataProvider $fileDataProvider */
/** @var bool $isPicker */
/** @var string $actionId */

$actionId = $actionId ?? 'index';

$currentUrl = Yii::$app->request->url;
$this->registerJsVar('pjaxBaseUrl', \yii\helpers\Url::to($currentUrl));

StorageAsset::register($this);

$this->title = Module::t('Storage');
$this->params['breadcrumbs'][] = $this->title;

// Register Loading widget
echo Loading::widget([
    'defaultMessage' => Module::t('Loading...'),
]);

?>
<div class="file-manager">
  <div class="file-controls">
    <?php
    echo Html::beginTag('div', [
      'class' => 'd-flex align-items-center gap-1 flex-wrap'
    ]);

    echo Html::tag(
      'div',
      Html::textInput('file', '', [
        'class' => 'form-control',
        'id' => 'searchFileInput',
        'placeholder' => Module::t('Search file..'),
        'data-is-picker' => $isPicker ? '1' : '0',
      ]) .
      Html::tag('span', Html::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']), [
        'class' => 'input-group-text'
      ]),
      [
        'class' => 'input-group',
        'style' => 'flex-grow: 1;',
      ]
    );


    echo Html::beginTag('div', ['class' => 'dropdown d-inline']);

    echo Html::button(
      Html::tag('i', '', ['class' => 'fa fa-plus me-2']) .
      Html::tag('span', Module::t('New'), ['class' => 'btn-text']),
      [
        'class' => 'newDropdownClass',
        'type' => 'button',
        'id' => 'newDropdownBtn',
        'data-bs-toggle' => 'dropdown',
        'aria-expanded' => 'false',
      ]
    );


    echo Html::beginTag('ul', ['class' => 'dropdown-menu custom-dropdown-align', 'aria-labelledby' => 'newDropdownBtn']);

    echo Html::tag(
      'li',
      Html::a(
        Html::tag('i', '', ['class' => 'fa fa-folder me-2']) . Module::t('New Folder'),
        '#',
        ['class' => 'dropdown-item', 'onclick' => 'openNewFolderModal(event)', 'id' => 'newFolderBtn']
      )
    );

    echo Html::tag(
      'li',
      Html::a(
        Html::tag('i', '', ['class' => 'fa fa-upload me-2']) . Module::t('Upload File'),
        '#',
        ['class' => 'dropdown-item', 'onclick' => 'uploadFileMenu(event)', 'id' => 'uploadFileBtn']
      )
    );

    echo Html::tag(
      'li',
      Html::a(
        Html::tag('i', '', ['class' => 'fa fa-upload me-2']) . Module::t('Upload Folder'),
        '#',
        ['class' => 'dropdown-item', 'onclick' => 'uploadFolderMenu(event)', 'id' => 'uploadFolderBtn']
      )
    );
    
echo Html::endTag('ul');
echo Html::endTag('div');

// Share My Storage Button
if (!$isPicker) {
    echo Html::button(
        Html::tag('i', '', ['class' => 'fa fa-share-alt me-2', 'style' => 'color: #000;']) . 
        Html::tag('span', Module::t('Share'), ['class' => 'btn-text']),
        [
            'class' => 'newDropdownClass',
            'type' => 'button',
            'onclick' => 'openShareStorageModal(event)',
            'title' => Module::t('Share My Storage'),
            'data-bs-toggle' => 'tooltip',
        ]
    );
}

echo Html::beginTag('div', ['class' => 'dropdown d-inline']);

echo Html::button(
  Html::tag('i', '', ['class' => 'fa fa-sort me-2']) .
  Html::tag('span', Module::t('Sort'), ['class' => 'btn-text']),
  [
    'class' => 'btn btn-primary btn-sm d-flex align-items-center',
    'type' => 'button',
    'id' => 'sortDropdownBtn',
    'data-bs-toggle' => 'dropdown',
    'aria-expanded' => 'false',
  ]
);

echo Html::beginTag('ul', [
  'class' => 'dropdown-menu custom-dropdown-align p-2',
  'aria-labelledby' => 'sortDropdownBtn',
  'style' => 'min-width: 220px;'
]);

echo Html::tag('li', Html::tag('strong', Module::t('Sort By')));
echo Html::tag('li', Html::tag('hr', '', ['class' => 'dropdown-divider']));

echo Html::tag('li', Html::a(Module::t('Title'), '#', [
  'class' => 'dropdown-item sort-option',
  'data-sort-field' => 'name',
]));

echo Html::tag('li', Html::a(Module::t('Date Added (Default)'), '#', [
  'class' => 'dropdown-item sort-option',
  'data-sort-field' => 'default',
]));

echo Html::tag('li', Html::a(Module::t('Last Accessed'), '#', [
  'class' => 'dropdown-item sort-option',
  'data-sort-field' => 'last_accessed',
]));

echo Html::tag('li', Html::a(Module::t('Most Accessed'), '#', [
  'class' => 'dropdown-item sort-option',
  'data-sort-field' => 'most_accessed',
]));

echo Html::tag('li', Html::tag('hr', '', ['class' => 'dropdown-divider']));

echo Html::tag('li', Html::tag('strong', Module::t('Sort Direction')));
echo Html::tag('li', Html::tag('hr', '', ['class' => 'dropdown-divider']));

echo Html::tag('li', Html::a(Module::t("New to Old"), '#', [
  'class' => 'dropdown-item sort-direction',
  'data-sort-direction' => 'desc',
  'data-for-fields' => 'default,name',
  'id' => 'sort-first-option'
]));

echo Html::tag('li', Html::a(Module::t("Old to New"), '#', [
  'class' => 'dropdown-item sort-direction',
  'data-sort-direction' => 'asc',
  'data-for-fields' => 'default,name',
  'id' => 'sort-second-option'
]));

echo Html::tag('li', Html::a(Module::t("Last to First"), '#', [
  'class' => 'dropdown-item sort-direction d-none',
  'data-sort-direction' => 'desc',
  'data-for-fields' => 'last_accessed',
  'id' => 'sort-last-to-first'
]));

echo Html::tag('li', Html::a(Module::t("First to Last"), '#', [
  'class' => 'dropdown-item sort-direction d-none',
  'data-sort-direction' => 'asc',
  'data-for-fields' => 'last_accessed',
  'id' => 'sort-first-to-last'
]));

echo Html::tag('li', Html::a(Module::t("Most to Least"), '#', [
  'class' => 'dropdown-item sort-direction d-none',
  'data-sort-direction' => 'desc',
  'data-for-fields' => 'most_accessed',
  'id' => 'sort-most-to-least'
]));

echo Html::tag('li', Html::a(Module::t("Least to Most"), '#', [
  'class' => 'dropdown-item sort-direction d-none',
  'data-sort-direction' => 'asc',
  'data-for-fields' => 'most_accessed',
  'id' => 'sort-least-to-most'
]));

echo Html::endTag('ul');
echo Html::endTag('div');

echo Html::beginTag('div', [
 'class' => 'view-toggle d-flex align-items-center ms-auto align-self-center mb-0'
]);

    echo Html::button(
      Html::tag('i', '', ['class' => 'fa fa-th me-2']) .
      Html::tag('span', Module::t('Grid View'), ['class' => 'btn-text']),
      [
        'id' => 'btn-grid',
        'class' => 'btn btn-selected btn-sm me-2 d-flex align-items-center',
        'type' => 'button',
        'onclick' => 'setViewMode("grid")',
      ]
    );

    echo Html::button(
      Html::tag('i', '', ['class' => 'fa fa-list me-2']) .
      Html::tag('span', Module::t('List View'), ['class' => 'btn-text']),
      [
        'id' => 'btn-list',
        'class' => 'btn btn-unselected btn-sm d-flex align-items-center',
        'type' => 'button',
        'onclick' => 'setViewMode("list")',
      ]
    );

    echo Html::endTag('div');

    echo Html::endTag('div'); // d-flex align-items-center gap-2 flex-wrap

    echo Html::endTag('div'); // file-controls

    echo Html::beginTag('div', [
      'class' => 'file-list'
    ]);
    Pjax::begin([
      'id' => 'upload-file-pjax',
      'history' => false,
      'timeout' => false,
      'enablePushState' => false,
    ]);
    Pjax::end();

    Pjax::begin([
      'id' => 'new-folder-pjax',
      'history' => false,
      'timeout' => false,
      'enablePushState' => false,
    ]);
    Pjax::end();

    Pjax::begin([
      'id' => 'rename-folder-pjax',
      'history' => false,
      'timeout' => false,
      'enablePushState' => false,
    ]);
    Pjax::end();

    Pjax::begin([
      'id' => 'list-item-pjax',
      'timeout' => false,
      'enablePushState' => false,
      'enableReplaceState' => false,

      'clientOptions' => ['push' => true, 'replace' => false, 'history' => true],
    ]);

    echo $this->render('_item-list', [
      'directoryDataProvider' => $directoryDataProvider,
      'fileDataProvider' => $fileDataProvider,
      'isPicker' => $isPicker ?? false,
      'actionId' => $actionId,
      'allowFolderSelection' => $allowFolderSelection ?? false,
    ]);

    Pjax::end();

    Pjax::begin([
      'id' => 'rename-file-pjax',
      'history' => false,
      'timeout' => false,
      'enablePushState' => false
    ]);
    Pjax::end();

    Pjax::begin([
      'id' => 'update-file-pjax',
      'history' => false,
      'timeout' => false,
      'enablePushState' => false
    ]);
    Pjax::end();

    Pjax::begin([
      'id' => 'share-file-pjax',
      'history' => false,
      'timeout' => false,
      'enablePushState' => false
    ]);
    Pjax::end();

    echo Html::endTag('div'); // file-list
    echo Html::endTag('div'); // file-manager

    echo $this->render('_filePreviewModal');
    ?>
<script>
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
      listBtn.classList.remove('btn-selected');  listBtn.classList.add('btn-unselected');
    } else {
      listBtn.classList.remove('btn-unselected'); listBtn.classList.add('btn-selected');
      gridBtn.classList.remove('btn-selected');  gridBtn.classList.add('btn-unselected');
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
  localStorage.setItem('viewMode', mode);
  document.cookie = "viewMode=" + mode + "; path=/; max-age=31536000";

  applyViewModeClasses(mode);
}

document.addEventListener('DOMContentLoaded', function() {

  const baseUrlToUse = (document.getElementById('file-picker-modal') && window.pjaxBaseUrl) ? window.pjaxBaseUrl : pjaxBaseUrl;
  const savedMode = localStorage.getItem('viewMode') || 'grid';
  applyViewModeClasses(savedMode);

  const savedSortField = localStorage.getItem('sortField');
  const savedSortDirection = localStorage.getItem('sortDirection') || 'desc';

  if (savedSortField) {
    const url = new URL(baseUrlToUse, window.location.origin);
    url.searchParams.set('sortField', savedSortField);
    url.searchParams.set('sortDirection', savedSortDirection || 'asc');

    const baseUrlParams = new URL(baseUrlToUse, window.location.origin).searchParams;
    const currentField = baseUrlParams.get('sortField');
    const currentDir = baseUrlParams.get('sortDirection');

    if (currentField !== savedSortField || currentDir !== savedSortDirection) {
      $.pjax.reload({
        container: '#list-item-pjax',
        url: url.toString(),
        push: false,
        replace: false,
        timeout: 10000,
      });
    }
  }
});

function getSortScope() {
  const modal = document.getElementById('file-picker-modal');
  if (modal) return $(modal);
  return $('.file-manager').length ? $('.file-manager') : $(document);
}

$(document).on('pjax:end', function () {
  const mode = localStorage.getItem('viewMode') || 'grid';
  applyViewModeClasses(mode);
  
  updateSortDirectionLabels();
  highlightActiveSort();
});

$(document).on('click', '.sort-option', function(e) {
  e.preventDefault();

  const sortField = $(this).data('sort-field');
  
  $('.sort-direction').addClass('d-none');
  $(`.sort-direction[data-for-fields*="${sortField}"]`).removeClass('d-none');
  
  const currentUrl = lastListItemPjaxUrl || ((document.getElementById('file-picker-modal') && window.pjaxBaseUrl) ? window.pjaxBaseUrl : pjaxBaseUrl);
  
  const url = new URL(currentUrl, window.location.origin);

  const currentPage = url.searchParams.get('page');
  if (currentPage) {
    url.searchParams.set('page', currentPage);
  }

  let newDirection = 'desc';
  const currentField = url.searchParams.get('sortField');
  const currentDirection = url.searchParams.get('sortDirection') || 'desc';

  if (currentField === sortField) {
      newDirection = (currentDirection === 'asc') ? 'desc' : 'asc';
  } else if (sortField === 'default' || sortField === 'most_accessed' || sortField === 'last_accessed') {
      newDirection = 'desc';
  } else {
      newDirection = 'asc';
  }

  url.searchParams.set('sortField', sortField);
  url.searchParams.set('sortDirection', newDirection);

  localStorage.setItem('sortField', sortField);
  localStorage.setItem('sortDirection', newDirection);

  $.pjax.reload({
    container: '#list-item-pjax',
    url: url.toString(),
    push: false,
    replace: false,
    timeout: 10000,
  }).done(function () {
    $.pjax.reload({ container: "#pjax-flash-message" });

    const mode = localStorage.getItem('viewMode') || 'grid';
    setViewMode(mode);

    lastListItemPjaxUrl = url.toString();
  });

  updateSortDirectionLabels();
});

$(document).on('click', '.sort-direction', function(e) {
  e.preventDefault();
  const sortDirection = $(this).attr('data-sort-direction');
  const savedField = localStorage.getItem('sortField') || 'default';
  
  const currentUrl = lastListItemPjaxUrl || ((document.getElementById('file-picker-modal') && window.pjaxBaseUrl) ? window.pjaxBaseUrl : pjaxBaseUrl);
  
  const url = new URL(currentUrl, window.location.origin);

  const currentPage = url.searchParams.get('page');
  if (currentPage) {
    url.searchParams.set('page', currentPage);
  }

  url.searchParams.set('sortField', savedField);
  url.searchParams.set('sortDirection', sortDirection);

  localStorage.setItem('sortField', savedField);
  localStorage.setItem('sortDirection', sortDirection);

  $.pjax.reload({
    container: '#list-item-pjax',
    url: url.toString(),
    push: false,
    replace: false,
    timeout: 10000,
  }).done(function () {
    $.pjax.reload({ container: "#pjax-flash-message" });
    const mode = localStorage.getItem('viewMode') || 'grid';
    setViewMode(mode);
    lastListItemPjaxUrl = url.toString();
  });
});

function highlightActiveSort() {
  const savedField = localStorage.getItem('sortField') || 'default';
  const savedDir = localStorage.getItem('sortDirection') || 'desc';

  $('.sort-option, .sort-direction').removeClass('active-sort');

  const scope = getSortScope();
  scope.find(`.sort-option[data-sort-field="${savedField}"]`).addClass('active-sort');
  
  scope.find('.sort-direction').addClass('d-none');
  scope.find(`.sort-direction[data-for-fields*="${savedField}"]`).removeClass('d-none');
  
  scope.find(`.sort-direction[data-sort-direction="${savedDir}"][data-for-fields*="${savedField}"]`).addClass('active-sort');
}

function updateSortDirectionLabels() {
  const savedField = localStorage.getItem('sortField') || 'default';
  const scope = getSortScope();

  const firstOption = scope.find('#sort-first-option');
  const secondOption = scope.find('#sort-second-option');

  if (firstOption.length === 0 || secondOption.length === 0) {
    if (savedField === 'name') {
      $('#sort-first-option').text("<?= Module::t("A to Z") ?>").attr('data-sort-direction', 'asc').data('sort-direction', 'asc');
      $('#sort-second-option').text("<?= Module::t("Z to A") ?>").attr('data-sort-direction', 'desc').data('sort-direction', 'desc');
    } else if (savedField === 'default') {
      $('#sort-first-option').text("<?= Module::t("New to Old") ?>").attr('data-sort-direction', 'desc').data('sort-direction', 'desc');
      $('#sort-second-option').text("<?= Module::t("Old to New") ?>").attr('data-sort-direction', 'asc').data('sort-direction', 'asc');
    } else {
      $('#sort-first-option').text("<?= Module::t("Descending") ?>").attr('data-sort-direction', 'desc');
      $('#sort-second-option').text("<?= Module::t("Ascending") ?>").attr('data-sort-direction', 'asc');
    }
    return;
  }

  if (savedField === 'name') {
    firstOption.text("<?= Module::t("A to Z") ?>").attr('data-sort-direction', 'asc').data('sort-direction', 'asc');
    secondOption.text("<?= Module::t("Z to A") ?>").attr('data-sort-direction', 'desc').data('sort-direction', 'desc');
  } 
  else if (savedField === 'default') {
    firstOption.text("<?= Module::t("New to Old") ?>").attr('data-sort-direction', 'desc').data('sort-direction', 'desc');
    secondOption.text("<?= Module::t("Old to New") ?>").attr('data-sort-direction', 'asc').data('sort-direction', 'asc');
  } 
  else {
    firstOption.text("<?= Module::t("Descending") ?>").attr('data-sort-direction', 'desc');
    secondOption.text("<?= Module::t("Ascending") ?>").attr('data-sort-direction', 'asc');
  }
}

$(document).ready(function() {
  updateSortDirectionLabels(); 
  highlightActiveSort();    
});

</script>
<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- Content will be loaded via AJAX -->
    </div>
  </div>
</div>
