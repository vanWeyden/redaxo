<?php

$content = '';

$versions = array();

$apiFunc = rex_api_function::factory();
if ($apiFunc && ($result = $apiFunc->getResult()) && $result->isSuccessfull()) {
  header('Location: ' . rex_url::currentBackendPage(array('info' => $result->getMessage())));
  exit;
}

if ($info = rex_get('info', 'string'))
  $content .= rex_view::info($info);
else
  $content .= rex_api_function::getMessage();

try {
  $versions = rex_api_install_core_update::getVersions();
} catch (rex_functional_exception $e) {
  $content .= rex_view::warning($e->getMessage());
}

$content .= '
  <div class="rex-area">
    <h2 class="rex-hl2">' . $this->i18n('available_updates', count($versions)) . '</h2>';

if (count($versions) > 0) {
  $content .= '
    <table class="rex-table">
      <tr>
        <th class="rex-icon"></th>
        <th>' . $this->i18n('version') . '</th>
        <th>' . $this->i18n('description') . '</th>
        <th></th>
      </tr>';

  foreach ($versions as $id => $version) {
    $content .= '
        <tr>
          <td class="rex-icon"><span class="rex-i-element rex-i-addon"><span class="rex-i-element-text">' . $version['version'] . '</span></span></td>
          <td>' . $version['version'] . '</td>
          <td>' . nl2br($version['description']) . '</td>
          <td><a href="' . rex_url::currentBackendPage(array('rex-api-call' => 'install_core_update', 'version_id' => $id)) . '">' . $this->i18n('update') . '</a></td>
        </tr>';
  }

  $content .= '
    </table>';
}
$content .= '
  </div>
  ';

echo rex_view::contentBlock($content, '', 'block');
