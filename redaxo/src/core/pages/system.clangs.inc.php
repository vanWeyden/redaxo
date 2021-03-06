<?php

/**
 * Verwaltung der Content Sprachen
 * @package redaxo5
 */

$content = '';

// -------------- Defaults
$clang_id   = rex_request('clang_id', 'int');
$clang_code = rex_request('clang_code', 'string');
$clang_name = rex_request('clang_name', 'string');
$func       = rex_request('func', 'string');

// -------------- Form Submits
$add_clang_save  = rex_post('add_clang_save', 'boolean');
$edit_clang_save = rex_post('edit_clang_save', 'boolean');

$warning = '';
$info = '';

// ----- delete clang
if ($func == 'deleteclang' && $clang_id != '') {
  if (rex_clang::exists($clang_id)) {
    rex_clang_service::deleteCLang($clang_id);
    $info = rex_i18n::msg('clang_deleted');
    $func = '';
    unset ($clang_id);
  }
}

// ----- add clang
if ($add_clang_save || $edit_clang_save) {
  if ($clang_code == '') {
    $warning = rex_i18n::msg('enter_code');
    $func = $add_clang_save ? 'addclang' : 'editclang';
  } elseif ($clang_name == '') {
    $warning = rex_i18n::msg('enter_name');
    $func = $add_clang_save ? 'addclang' : 'editclang';
  } elseif ($add_clang_save) {
    if (!rex_clang::exists($clang_id)) {
      $info = rex_i18n::msg('clang_created');
      rex_clang_service::addCLang($clang_id, $clang_code, $clang_name);
      unset ($clang_id);
      $func = '';
    } else {
      $warning = rex_i18n::msg('id_exists');
      $func = 'addclang';
    }
  } else {
    if (rex_clang::exists($clang_id)) {
      rex_clang_service::editCLang($clang_id, $clang_code, $clang_name);
      $info = rex_i18n::msg('clang_edited');
      $func = '';
      unset ($clang_id);
    }
  }
}

// seltype
$sel = new rex_select;
$sel->setName('clang_id');
$sel->setStyle('class="rex-form-select"');
$sel->setId('rex-form-clang-id');
$sel->setSize(1);
$remaingClangs = array_diff(range(0, rex::getProperty('maxclangs') - 1), rex_clang::getAllIds());
foreach ($remaingClangs as $clang) {
  $sel->addOption($clang, $clang);
}

// no remaing clang-ids
if (empty($remaingClangs)) {
  $warning = rex_i18n::msg('clang_no_left');
}

if ($info != '')
  $content .= rex_view::info($info);

if ($warning != '')
  $content .= rex_view::warning($warning);


$content .= '
      <div class="rex-form" id="rex-form-system-language">
      <form action="index.php#clang" method="post">
    ';

if ($func == 'addclang' || $func == 'editclang') {
  $legend = $func == 'addclang' ? rex_i18n::msg('clang_add') : rex_i18n::msg('clang_edit');
  $content .= '
        <fieldset>
          <legend>' . $legend . '</legend>
          <input type="hidden" name="page" value="system" />
          <input type="hidden" name="subpage" value="lang" />
          <input type="hidden" name="clang_id" value="' . $clang_id . '" />
      ';
}


$content .= '
    <table class="rex-table" summary="' . rex_i18n::msg('clang_summary') . '">
      <caption>' . rex_i18n::msg('clang_caption') . '</caption>
      <thead>
        <tr>
          <th class="rex-icon"><a class="rex-ic-clang rex-ic-add" href="' . rex_url::currentBackendPage(array('func' => 'addclang')) . '#clang"' . rex::getAccesskey(rex_i18n::msg('clang_add'), 'add') . '>' . rex_i18n::msg('clang_add') . '</a></th>
          <th class="rex-id">ID</th>
          <th class="rex-code">' . rex_i18n::msg('clang_code') . '</th>
          <th class="rex-name">' . rex_i18n::msg('clang_name') . '</th>
          <th class="rex-function">' . rex_i18n::msg('clang_function') . '</th>
        </tr>
      </thead>
      <tbody>
  ';

// Add form
if ($func == 'addclang') {
  //ggf wiederanzeige des add forms, falls ungueltige id uebermittelt
  $content .= '
        <tr class="rex-active">
          <td class="rex-icon"><span class="rex-i-element rex-i-clang"><span class="rex-i-element-text">' . htmlspecialchars($clang_name) . '</span></span></td>
          <td class="rex-id">' . $sel->get() . '</td>
          <td class="rex-code"><input class="rex-form-text" type="text" id="rex-form-clang-code" name="clang_code" value="' . htmlspecialchars($clang_code) . '" /></td>
          <td class="rex-name"><input class="rex-form-text" type="text" id="rex-form-clang-name" name="clang_name" value="' . htmlspecialchars($clang_name) . '" /></td>
          <td class="rex-save"><input class="rex-form-submit" type="submit" name="add_clang_save" value="' . rex_i18n::msg('clang_add') . '"' . rex::getAccesskey(rex_i18n::msg('clang_add'), 'save') . ' /></td>
        </tr>
      ';
}
foreach (rex_clang::getAll() as $lang_id => $lang) {

  $add_td = '';
  $add_td = '<td class="rex-id">' . $lang_id . '</td>';

  $delLink = rex_i18n::msg('clang_delete');
  if ($lang_id == 0)
   $delLink = '<span class="rex-strike">' . $delLink . '</span>';
  else
    $delLink = '<a href="' . rex_url::currentBackendPage(array('func' => 'deleteclang', 'clang_id' => $clang_id)) . '" data-confirm="' . rex_i18n::msg('delete') . ' ?">' . $delLink . '</a>';

  // Edit form
  if ($func == 'editclang' && $clang_id == $lang_id) {
    $content .= '
          <tr class="rex-active">
            <td class="rex-icon"><span class="rex-ic-clang">' . htmlspecialchars($clang_name) . '</span></td>
            ' . $add_td . '
            <td class="rex-code"><input class="rex-form-text" type="text" id="rex-form-clang-code" name="clang_code" value="' . htmlspecialchars($lang->getCode()) . '" /></td>
            <td class="rex-name"><input class="rex-form-text" type="text" id="rex-form-clang-name" name="clang_name" value="' . htmlspecialchars($lang->getName()) . '" /></td>
            <td class="rex-save"><input class="rex-form-submit" type="submit" name="edit_clang_save" value="' . rex_i18n::msg('clang_update') . '"' . rex::getAccesskey(rex_i18n::msg('clang_update'), 'save') . ' /></td>
          </tr>';

  } else {
    $editLink = rex_url::currentBackendPage(array('func' => 'editclang', 'clang_id' => $lang_id)) . '#clang';

    $content .= '
          <tr>
            <td class="rex-icon"><a class="rex-ic-clang" href="' . $editLink . '">' . htmlspecialchars($clang_name) . '</a></td>
            ' . $add_td . '
            <td class="rex-code">' . htmlspecialchars($lang->getCode()) . '</td>
            <td class="rex-name"><a href="' . $editLink . '">' . htmlspecialchars($lang->getName()) . '</a></td>
            <td class="rex-delete">' . $delLink . '</td>
          </tr>';
  }
}

$content .= '
    </tbody>
  </table>';

if ($func == 'addclang' || $func == 'editclang') {
  $content .= '
          <script type="text/javascript">
            <!--
            jQuery(function($){
              $("#rex-form-clang-name").focus();
            });
            //-->
          </script>
        </fieldset>';
}

$content .= '
      </form>
      </div>';

echo rex_view::contentBlock($content, '', 'block');
