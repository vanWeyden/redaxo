<?php

/**
 *
 * @package redaxo4
 * @version svn:$Id$
 */

// basic request vars
$category_id = rex_request('category_id', 'rex-category-id');
$article_id  = rex_request('article_id',  'rex-article-id');
$clang       = rex_request('clang',       'rex-clang-id', $REX['START_CLANG_ID']);
$ctype       = rex_request('ctype',       'rex-ctype-id');

// additional request vars
$artstart    = rex_request('artstart',    'int');
$catstart    = rex_request('catstart',    'int');
$edit_id     = rex_request('edit_id',     'rex-category-id');
$function    = rex_request('function',    'string');

$info = '';
$warning = '';





// --------------------------------------------- Mountpoints

$mountpoints = $REX["USER"]->getMountpoints();
if(count($mountpoints)==1 && $category_id == 0)
{
  // Nur ein Mointpoint -> Sprung in die Kategory
  $category_id = current($mountpoints);
}

// --------------------------------------------- Rechte prŸfen
require dirname(__FILE__) .'/../functions/function_rex_structure.inc.php';
require dirname(__FILE__) .'/../functions/function_rex_category.inc.php';



// --------------------------------------------- TITLE

rex_title($REX['I18N']->msg('title_structure'), $KATout);

$sprachen_add = '&amp;category_id='. $category_id;
require dirname(__FILE__) .'/../functions/function_rex_languages.inc.php';

// -------------- STATUS_TYPE Map
$catStatusTypes = rex_categoryStatusTypes();
$artStatusTypes = rex_articleStatusTypes();

$context = new rex_context(array(
  'page' => 'structure',
  'category_id' => $category_id,
  'article_id' => $article_id,
  'clang' => $clang,
  'ctype' => $ctype,
));

// --------------------------------------------- KATEGORIE FUNKTIONEN
if (rex_post('catedit_function', 'boolean') && $edit_id != '' && $KATPERM)
{
  // --------------------- KATEGORIE EDIT
  $data = array();
  $data['catprior'] = rex_post('Position_Category', 'int');
  $data['catname']  = rex_post('kat_name', 'string');
  $data['path']     = $KATPATH;

  list($success, $message) = rex_editCategory($edit_id, $clang, $data);

  if($success)
    $info = $message;
  else
    $warning = $message;
}
elseif ($function == 'catdelete_function' && $edit_id != '' && $KATPERM && !$REX['USER']->hasPerm('editContentOnly[]'))
{
  // --------------------- KATEGORIE DELETE
  list($success, $message) = rex_deleteCategoryReorganized($edit_id);

  if($success)
  {
    $info = $message;
  }else
  {
    $warning = $message;
    $function = 'edit';
  }
}elseif ($function == 'status' && $edit_id != ''
        && ($REX['USER']->isAdmin() || $KATPERM && $REX['USER']->hasPerm('publishArticle[]')))
{
  // --------------------- KATEGORIE STATUS
  list($success, $message) = rex_categoryStatus($edit_id, $clang);

  if($success)
    $info = $message;
  else
    $warning = $message;
}
elseif (rex_post('catadd_function', 'boolean') && $KATPERM && !$REX['USER']->hasPerm('editContentOnly[]'))
{
  // --------------------- KATEGORIE ADD
  $data = array();
  $data['catprior'] = rex_post('Position_New_Category', 'int');
  $data['catname']  = rex_post('category_name', 'string');
  $data['path']     = $KATPATH;

  list($success, $message) = rex_addCategory($category_id, $data);

  if($success)
    $info = $message;
  else
    $warning = $message;
}

// --------------------------------------------- ARTIKEL FUNKTIONEN

if ($function == 'status_article' && $article_id != ''
    && ($REX['USER']->isAdmin() || $KATPERM && $REX['USER']->hasPerm('publishArticle[]')))
{
  // --------------------- ARTICLE STATUS
  list($success, $message) = rex_articleStatus($article_id, $clang);

  if($success)
    $info = $message;
  else
    $warning = $message;
}
// Hier mit !== vergleichen, da 0 auch einen gültige category_id ist (RootArtikel)
elseif (rex_post('artadd_function', 'boolean') && $category_id !== '' && $KATPERM &&  !$REX['USER']->hasPerm('editContentOnly[]'))
{
  // --------------------- ARTIKEL ADD
  $data = array();
  $data['prior']       = rex_post('Position_New_Article', 'int');
  $data['name']        = rex_post('article_name', 'string');
  $data['template_id'] = rex_post('template_id', 'rex-template-id');
  $data['category_id'] = $category_id;
  $data['path']        = $KATPATH;

  list($success, $message) = rex_addArticle($data);

  if($success)
    $info = $message;
  else
    $warning = $message;
}
elseif (rex_post('artedit_function', 'boolean') && $article_id != '' && $KATPERM)
{
  // --------------------- ARTIKEL EDIT
  $data = array();
  $data['prior']       = rex_post('Position_Article', 'int');
  $data['name']        = rex_post('article_name', 'string');
  $data['template_id'] = rex_post('template_id', 'rex-template-id');
  $data['category_id'] = $category_id;
  $data['path']        = $KATPATH;

  list($success, $message) = rex_editArticle($article_id, $clang, $data);

  if($success)
    $info = $message;
  else
    $warning = $message;
}
elseif ($function == 'artdelete_function' && $article_id != '' && $KATPERM && !$REX['USER']->hasPerm('editContentOnly[]'))
{
  // --------------------- ARTIKEL DELETE
  list($success, $message) = rex_deleteArticleReorganized($article_id);

  if($success)
    $info = $message;
  else
    $warning = $message;
}

// --------------------------------------------- KATEGORIE LISTE

if ($warning != "")
  echo rex_warning($warning);

if ($info != "")
  echo rex_info($info);

$cat_name = 'Homepage';
$category = rex_ooCategory::getCategoryById($category_id, $clang);
if($category)
  $cat_name = $category->getName();

$add_category = '';
if ($KATPERM && !$REX['USER']->hasPerm('editContentOnly[]'))
{
  $add_category = '<a class="rex-i-element rex-i-category-add" href="'. $context->getUrl(array('function' => 'add_cat')) .'"'. rex_accesskey($REX['I18N']->msg('add_category'), $REX['ACKEY']['ADD']) .'><span class="rex-i-element-text">'.$REX['I18N']->msg("add_category").'</span></a>';
}

$add_header = '';
$add_col = '';
$data_colspan = 4;
if ($REX['USER']->hasPerm('advancedMode[]'))
{
  $add_header = '<th class="rex-small">'.$REX['I18N']->msg('header_id').'</th>';
  $add_col = '<col width="40" />';
  $data_colspan = 5;
}

// --------------------- Extension Point
echo rex_register_extension_point('PAGE_STRUCTURE_HEADER', '',
  array(
    'category_id' => $category_id,
    'clang' => $clang
  )
);


// --------------------- COUNT CATEGORY ROWS

$KAT = rex_sql::factory();
// $KAT->debugsql = true;
if(count($mountpoints)>0 && $category_id == 0)
{
  $re_id = implode(',', $mountpoints);
  $KAT->setQuery('SELECT COUNT(*) as rowCount FROM '.$REX['TABLE_PREFIX'].'article WHERE id IN ('.$re_id.') AND startpage=1 AND clang='. $clang .' ORDER BY catname');
}else
{
  $KAT->setQuery('SELECT COUNT(*) as rowCount FROM '.$REX['TABLE_PREFIX'].'article WHERE re_id='. $category_id .' AND startpage=1 AND clang='. $clang .' ORDER BY catprior');
}

// --------------------- ADD PAGINATION

// FIXME add a realsitic rowsPerPage value
$catPager = new rex_pager($KAT->getValue('rowCount'), 3, 'catstart');
$catFragment = new rex_fragment();
$catFragment->setVar('urlprovider', $context);
$catFragment->setVar('pager', $catPager);
echo $catFragment->parse('rex_list/pagination');

// --------------------- GET THE DATA

if(count($mountpoints)>0 && $category_id == 0)
{
  $re_id = implode(',', $mountpoints);
  $KAT->setQuery('SELECT * FROM '.$REX['TABLE_PREFIX'].'article WHERE id IN ('.$re_id.') AND startpage=1 AND clang='. $clang .' ORDER BY catname LIMIT '. $catPager->getCursor(). ','. $catPager->getRowsPerPage());
}else
{
  $KAT->setQuery('SELECT * FROM '.$REX['TABLE_PREFIX'].'article WHERE re_id='. $category_id .' AND startpage=1 AND clang='. $clang .' ORDER BY catprior LIMIT '. $catPager->getCursor(). ','. $catPager->getRowsPerPage());
}


echo '
<!-- *** OUTPUT CATEGORIES - START *** -->';

echo '<div class="rex-block rex-structure-category">';

// ---------- INLINE THE EDIT/ADD FORM
if($function == 'add_cat' || $function == 'edit_cat')
{

  $legend = $REX['I18N']->msg('add_category');
  if ($function == 'edit_cat')
    $legend = $REX['I18N']->msg('edit_category');

  echo '
  <div class="rex-form" id="rex-form-structure-category">
  <form action="index.php" method="post">
    <fieldset>
      <legend><span>'.htmlspecialchars($legend) .'</span></legend>';

  $params = array();
  $params['catstart'] = $catstart;
  if ($function == 'edit_cat')
  {
    $params['edit_id'] = $edit_id;
  }

	echo $context->getHiddenInputFields($params);
}


// --------------------- PRINT CATS/SUBCATS

echo '
      <table class="rex-table" summary="'. htmlspecialchars($REX['I18N']->msg('structure_categories_summary', $cat_name)) .'">
        <caption>'. htmlspecialchars($REX['I18N']->msg('structure_categories_caption', $cat_name)) .'</caption>
        <colgroup>
          <col width="40" />
          '. $add_col .'
          <col width="*" />
          <col width="40" />
          <col width="51" />
          <col width="50" />
          <col width="50" />
        </colgroup>
        <thead>
          <tr>
            <th class="rex-icon">'. $add_category .'</th>
            '. $add_header .'
            <th>'.$REX['I18N']->msg('header_category').'</th>
            <th>'.$REX['I18N']->msg('header_priority').'</th>
            <th colspan="3">'.$REX['I18N']->msg('header_status').'</th>
          </tr>
        </thead>
        <tbody>';
if ($category_id != 0 && ($category = rex_ooCategory::getCategoryById($category_id)))
{
  echo '<tr>
          <td class="rex-icon">&nbsp;</td>';
  if ($REX['USER']->hasPerm('advancedMode[]'))
  {
    echo '<td class="rex-small">-</td>';
  }


  echo '<td><a href="'. $context->getUrl(array('category_id' => $category->getParentId())) .'">..</a></td>';
  echo '<td>&nbsp;</td>';
  echo '<td colspan="3">&nbsp;</td>';
  echo '</tr>';

}

// --------------------- KATEGORIE ADD FORM

if ($function == 'add_cat' && $KATPERM && !$REX['USER']->hasPerm('editContentOnly[]'))
{
  $add_td = '';
  if ($REX['USER']->hasPerm('advancedMode[]'))
  {
    $add_td = '<td class="rex-small">-</td>';
  }

  $meta_buttons = rex_register_extension_point('CAT_FORM_BUTTONS', "" );
  $add_buttons = '<input type="submit" class="rex-form-submit" name="catadd_function" value="'. $REX['I18N']->msg('add_category') .'"'. rex_accesskey($REX['I18N']->msg('add_category'), $REX['ACKEY']['SAVE']) .' />';

  $class = 'rex-table-row-active';
  if($meta_buttons != "")
    $class .= ' rex-has-metainfo';

  echo '
        <tr class="'. $class .'">
          <td class="rex-icon"><span class="rex-i-element rex-i-category"><span class="rex-i-element-text">'. $REX['I18N']->msg('add_category') .'</span></span></td>
          '. $add_td .'
          <td><input class="rex-form-text" type="text" id="rex-form-field-name" name="category_name" />'. $meta_buttons .'</td>
          <td><input class="rex-form-text" type="text" id="rex-form-field-prior" name="Position_New_Category" value="'.($KAT->getRows()+1).'" /></td>
          <td colspan="3">'. $add_buttons .'</td>
        </tr>';

  // ----- EXTENSION POINT
  echo rex_register_extension_point('CAT_FORM_ADD', '', array (
      'id' => $category_id,
      'clang' => $clang,
      'data_colspan' => ($data_colspan+1),
    ));
}





// --------------------- KATEGORIE LIST

for ($i = 0; $i < $KAT->getRows(); $i++)
{
  $i_category_id = $KAT->getValue('id');

  $kat_link = $context->getUrl(array('category_id' => $i_category_id));
  $kat_icon_td = '<td class="rex-icon"><a class="rex-i-element rex-i-category" href="'. $kat_link .'"><span class="rex-i-element-text">'. htmlspecialchars($KAT->getValue("catname")). '</span></a></td>';

  $kat_status = $catStatusTypes[$KAT->getValue('status')][0];
  $status_class = $catStatusTypes[$KAT->getValue('status')][1];

  if ($KATPERM)
  {
    if ($REX['USER']->isAdmin() || $KATPERM && $REX['USER']->hasPerm('publishCategory[]'))
    {
      $kat_status = '<a href="'. $context->getUrl(array('edit_id' => $i_category_id, 'function' => 'status', 'catstart' => $catstart)) .'" class="'. $status_class .'">'. $kat_status .'</a>';
    }
    else
    {
      $kat_status = '<span class="rex-strike '. $status_class .'">'. $kat_status .'</span>';
    }

    if (isset ($edit_id) && $edit_id == $i_category_id && $function == 'edit_cat')
    {
      // --------------------- KATEGORIE EDIT FORM
      $add_td = '';
      if ($REX['USER']->hasPerm('advancedMode[]'))
      {
        $add_td = '<td class="rex-small">'. $i_category_id .'</td>';
      }

      // ----- EXTENSION POINT
      $meta_buttons = rex_register_extension_point('CAT_FORM_BUTTONS', '', array(
        'id' => $edit_id,
        'clang' => $clang,
      ));
      $add_buttons = '<input type="submit" class="rex-form-submit" name="catedit_function" value="'. $REX['I18N']->msg('save_category'). '"'. rex_accesskey($REX['I18N']->msg('save_category'), $REX['ACKEY']['SAVE']) .' />';

      $class = 'rex-table-row-active';
      if($meta_buttons != "")
        $class .= ' rex-has-metainfo';

      echo '
        <tr class="'. $class .'">
          '. $kat_icon_td .'
          '. $add_td .'
          <td><input type="text" class="rex-form-text" id="rex-form-field-name" name="kat_name" value="'. htmlspecialchars($KAT->getValue("catname")). '" />'. $meta_buttons .'</td>
          <td><input type="text" class="rex-form-text" id="rex-form-field-prior" name="Position_Category" value="'. htmlspecialchars($KAT->getValue("catprior")) .'" /></td>
          <td colspan="3">'. $add_buttons .'</td>
        </tr>';

      // ----- EXTENSION POINT
      echo rex_register_extension_point('CAT_FORM_EDIT', '', array (
        'id' => $edit_id,
        'clang' => $clang,
        'category' => $KAT,
        'catname' => $KAT->getValue('catname'),
        'catprior' => $KAT->getValue('catprior'),
        'data_colspan' => ($data_colspan+1),
      ));

    }
    else
    {
      // --------------------- KATEGORIE WITH WRITE

      $add_td = '';
      if ($REX['USER']->hasPerm('advancedMode[]'))
      {
        $add_td = '<td class="rex-small">'. $i_category_id .'</td>';
      }

      if (!$REX['USER']->hasPerm('editContentOnly[]'))
      {
        $category_delete = '<a href="'. $context->getUrl(array('edit_id' => $i_category_id, 'function' => 'catdelete_function', 'catstart' => $catstart)) .'" onclick="return confirm(\''.$REX['I18N']->msg('delete').' ?\')">'.$REX['I18N']->msg('delete').'</a>';
      }
      else
      {
        $category_delete = '<span class="rex-strike">'. $REX['I18N']->msg('delete') .'</span>';
      }

      echo '
        <tr>
          '. $kat_icon_td .'
          '. $add_td .'
          <td><a href="'. $kat_link .'">'. htmlspecialchars($KAT->getValue("catname")) .'</a></td>
          <td>'. htmlspecialchars($KAT->getValue("catprior")) .'</td>
          <td><a href="'. $context->getUrl(array('edit_id' => $i_category_id, 'function' => 'edit_cat', 'catstart' => $catstart)) .'">'. $REX['I18N']->msg('change') .'</a></td>
          <td>'. $category_delete .'</td>
          <td>'. $kat_status .'</td>
        </tr>';
    }

  }
  elseif (/*$REX['USER']->hasPerm('csr['. $i_category_id .']') ||*/ $REX['USER']->hasPerm('csw['. $i_category_id .']'))
  {
      // --------------------- KATEGORIE WITH READ
      $add_td = '';
      if ($REX['USER']->hasPerm('advancedMode[]'))
      {
        $add_td = '<td class="rex-small">'. $i_category_id .'</td>';
      }

      echo '
        <tr>
          '. $kat_icon_td .'
          '. $add_td .'
          <td><a href="'. $kat_link .'">'.$KAT->getValue("catname").'</a></td>
          <td>'.htmlspecialchars($KAT->getValue("catprior")).'</td>
          <td><span class="rex-strike">'. $REX['I18N']->msg('change') .'</span></td>
          <td><span class="rex-strike">'. $REX['I18N']->msg('delete') .'</span></td>
          <td><span class="rex-strike '. $status_class .'">'. $kat_status .'</span></td>
        </tr>';
  }

  $KAT->next();
}

echo '
      </tbody>
    </table>';

if($function == 'add_cat' || $function == 'edit_cat')
{
  echo '
    <script type="text/javascript">
      <!--
      jQuery(function($){
        $("#rex-form-field-name").focus();
      });
      //-->
    </script>
  </fieldset>
</form>
</div>';
}

echo '</div>';

echo '
<!-- *** OUTPUT CATEGORIES - END *** -->
';

// --------------------------------------------- ARTIKEL LISTE

echo '
<!-- *** OUTPUT ARTICLES - START *** -->';

echo '<div class="rex-block rex-structure-article">';

// --------------------- READ TEMPLATES

if ($category_id > 0 || ($category_id == 0 && !$REX["USER"]->hasMountpoints()))
{

  $template_select = new rex_select;
  $template_select->setName('template_id');
  $template_select->setId('rex-form-template');
  $template_select->setSize(1);

  $templates = rex_ooCategory::getTemplates($category_id);
  if(count($templates)>0)
  {
    foreach($templates as $t_id => $t_name)
    {
      $template_select->addOption(rex_translate($t_name, null, false), $t_id);
      $TEMPLATE_NAME[$t_id] = rex_translate($t_name);
    }
  }else
  {
    $template_select->addOption($REX['I18N']->msg('option_no_template'), '0');
    $TEMPLATE_NAME[0] = $REX['I18N']->msg('template_default_name');
  }

  // --------------------- ARTIKEL LIST
  $art_add_link = '';
  if ($KATPERM && !$REX['USER']->hasPerm('editContentOnly[]'))
    $art_add_link = '<a class="rex-i-element rex-i-article-add" href="'. $context->getUrl(array('function' => 'add_art')) .'"'. rex_accesskey($REX['I18N']->msg('article_add'), $REX['ACKEY']['ADD_2']) .'><span class="rex-i-element-text">'. $REX['I18N']->msg('article_add') .'</span></a>';

  $add_head = '';
  $add_col  = '';
  if ($REX['USER']->hasPerm('advancedMode[]'))
  {
    $add_head = '<th class="rex-small">'. $REX['I18N']->msg('header_id') .'</th>';
    $add_col  = '<col width="40" />';
  }

  // ---------- COUNT DATA
  $sql = rex_sql::factory();
  // $sql->debugsql = true;
  $sql->setQuery('SELECT COUNT(*) as artCount
        FROM
          '.$REX['TABLE_PREFIX'].'article
        WHERE
          ((re_id='. $category_id .' AND startpage=0) OR (id='. $category_id .' AND startpage=1))
          AND clang='. $clang .'
        ORDER BY
          prior, name');

  // --------------------- ADD PAGINATION

  // FIXME add a realsitic rowsPerPage value
  $artPager = new rex_pager($sql->getValue('artCount'), 3, 'artstart');
  $artFragment = new rex_fragment();
  $artFragment->setVar('urlprovider', $context);
  $artFragment->setVar('pager', $artPager);
  echo $artFragment->parse('rex_list/pagination');

  // ---------- READ DATA
  $sql->setQuery('SELECT *
        FROM
          '.$REX['TABLE_PREFIX'].'article
        WHERE
          ((re_id='. $category_id .' AND startpage=0) OR (id='. $category_id .' AND startpage=1))
          AND clang='. $clang .'
        ORDER BY
          prior, name
        LIMIT '. $artPager->getCursor() .','. $artPager->getRowsPerPage());


  // ---------- INLINE THE EDIT/ADD FORM
  if($function == 'add_art' || $function == 'edit_art')
  {

    $legend = $REX['I18N']->msg('article_add');
    if ($function == 'edit_art')
      $legend = $REX['I18N']->msg('article_edit');

    echo '
    <div class="rex-form" id="rex-form-structure-article">
    <form action="index.php" method="post">
      <fieldset>
        <legend><span>'.htmlspecialchars($legend) .'</span></legend>';

    $params = array();
    $params['artstart'] = $artstart;
    echo $context->getHiddenInputFields($params);
  }

  // ----------- PRINT OUT THE ARTICLES

  echo '
      <table class="rex-table" summary="'. htmlspecialchars($REX['I18N']->msg('structure_articles_summary', $cat_name)) .'">
        <caption>'. htmlspecialchars($REX['I18N']->msg('structure_articles_caption', $cat_name)).'</caption>
        <colgroup>
          <col width="40" />
          '. $add_col .'
          <col width="*" />
          <col width="40" />
          <col width="200" />
          <col width="115" />
          <col width="51" />
          <col width="50" />
          <col width="50" />
        </colgroup>
        <thead>
          <tr>
            <th class="rex-icon">'. $art_add_link .'</th>
            '. $add_head .'
            <th>'.$REX['I18N']->msg('header_article_name').'</th>
            <th>'.$REX['I18N']->msg('header_priority').'</th>
            <th>'.$REX['I18N']->msg('header_template').'</th>
            <th>'.$REX['I18N']->msg('header_date').'</th>
            <th colspan="3">'.$REX['I18N']->msg('header_status').'</th>
          </tr>
        </thead>
        ';

  // tbody nur anzeigen, wenn später auch inhalt drinnen stehen wird
  if($sql->getRows() > 0 || $function == 'add_art')
  {
    echo '<tbody>
          ';
  }

  // --------------------- ARTIKEL ADD FORM
  if ($function == 'add_art' && $KATPERM && !$REX['USER']->hasPerm('editContentOnly[]'))
  {
    if($REX['DEFAULT_TEMPLATE_ID'] > 0 && isset($TEMPLATE_NAME[$REX['DEFAULT_TEMPLATE_ID']]))
    {
      $template_select->setSelected($REX['DEFAULT_TEMPLATE_ID']);

    }else
    {
      // template_id vom Startartikel erben
      $sql2 = rex_sql::factory();
      $sql2->setQuery('SELECT template_id FROM '.$REX['TABLE_PREFIX'].'article WHERE id='. $category_id .' AND clang='. $clang .' AND startpage=1');
      if ($sql2->getRows() == 1)
        $template_select->setSelected($sql2->getValue('template_id'));
    }

    $add_td = '';
    if ($REX['USER']->hasPerm('advancedMode[]'))
      $add_td = '<td class="rex-small">-</td>';

    echo '<tr class="rex-table-row-active">
            <td class="rex-icon"><span class="rex-i-element rex-i-article"><span class="rex-i-element-text">'.$REX['I18N']->msg('article_add') .'</span></span></td>
            '. $add_td .'
            <td><input type="text" class="rex-form-text" id="rex-form-field-name" name="article_name" /></td>
            <td><input type="text" class="rex-form-text" id="rex-form-field-prior" name="Position_New_Article" value="'.($sql->getRows()+1).'" /></td>
            <td>'. $template_select->get() .'</td>
            <td>'. rex_formatter :: format(time(), 'strftime', 'date') .'</td>
            <td colspan="3"><input type="submit" class="rex-form-submit" name="artadd_function" value="'.$REX['I18N']->msg('article_add') .'"'. rex_accesskey($REX['I18N']->msg('article_add'), $REX['ACKEY']['SAVE']) .' /></td>
          </tr>
          ';
  }

  // --------------------- ARTIKEL LIST

  for ($i = 0; $i < $sql->getRows(); $i++)
  {

    if ($sql->getValue('startpage') == 1)
      $class = 'rex-i-article-startpage';
    else
      $class = 'rex-i-article';

    // --------------------- ARTIKEL EDIT FORM

    if ($function == 'edit_art' && $sql->getValue('id') == $article_id && $KATPERM)
    {
      $add_td = '';
      if ($REX['USER']->hasPerm('advancedMode[]'))
        $add_td = '<td class="rex-small">'. $sql->getValue("id") .'</td>';

      $template_select->setSelected($sql->getValue('template_id'));

      echo '<tr class="rex-table-row-active">
              <td class="rex-icon"><a class="rex-i-element '.$class.'" href="'. $context->getUrl(array('page' => 'content', 'article_id' => $sql->getValue('id'))) .'"><span class="rex-i-element-text">' .htmlspecialchars($sql->getValue("name")).'</span></a></td>
              '. $add_td .'
              <td><input type="text" class="rex-form-text" id="rex-form-field-name" name="article_name" value="' .htmlspecialchars($sql->getValue('name')).'" /></td>
              <td><input type="text" class="rex-form-text" id="rex-form-field-prior" name="Position_Article" value="'. htmlspecialchars($sql->getValue('prior')).'" /></td>
              <td>'. $template_select->get() .'</td>
              <td>'. rex_formatter :: format($sql->getValue('createdate'), 'strftime', 'date') .'</td>
              <td colspan="3"><input type="submit" class="rex-form-submit" name="artedit_function" value="'. $REX['I18N']->msg('article_save') .'"'. rex_accesskey($REX['I18N']->msg('article_save'), $REX['ACKEY']['SAVE']) .' /></td>
            </tr>
            ';

    }elseif ($KATPERM)
    {
      // --------------------- ARTIKEL NORMAL VIEW | EDIT AND ENTER

      $add_td = '';
      if ($REX['USER']->hasPerm('advancedMode[]'))
        $add_td = '<td class="rex-small">'. $sql->getValue('id') .'</td>';

      $article_status = $artStatusTypes[$sql->getValue('status')][0];
      $article_class = $artStatusTypes[$sql->getValue('status')][1];

      $add_extra = '';
      if ($sql->getValue('startpage') == 1)
      {
        $add_extra = '<td><span class="rex-strike">'. $REX['I18N']->msg('delete') .'</span></td>
                      <td><span class="rex-strike '. $article_class .'">'. $article_status .'</span></td>';
      }else
      {
        if ($REX['USER']->isAdmin() || $KATPERM && $REX['USER']->hasPerm('publishArticle[]'))
          $article_status = '<a href="'. $context->getUrl(array('article_id' => $sql->getValue('id'), 'function' => 'status_article', 'artstart' => $artstart)) .'" class="rex-status-link '. $article_class .'">'. $article_status .'</a>';
        else
          $article_status = '<span class="rex-strike '. $article_class .'">'. $article_status .'</span>';

        if (!$REX['USER']->hasPerm('editContentOnly[]'))
          $article_delete = '<a href="'. $context->getUrl(array('article_id' => $sql->getValue('id'), 'function' => 'artdelete_function', 'artstart' => $artstart)) .'" onclick="return confirm(\''.$REX['I18N']->msg('delete').' ?\')">'.$REX['I18N']->msg('delete').'</a>';
        else
          $article_delete = '<span class="rex-strike">'. $REX['I18N']->msg('delete') .'</span>';

        $add_extra = '<td>'. $article_delete .'</td>
                      <td>'. $article_status .'</td>';
      }

      $editModeUrl = $context->getUrl(array('page' => 'content', 'article_id' => $sql->getValue('id'), 'mode' => 'edit'));
      $tmpl = isset($TEMPLATE_NAME[$sql->getValue('template_id')]) ? $TEMPLATE_NAME[$sql->getValue('template_id')] : '';

      echo '<tr>
              <td class="rex-icon"><a class="rex-i-element '.$class.'" href="'. $editModeUrl .'"><span class="rex-i-element-text">' .htmlspecialchars($sql->getValue('name')).'</span></a></td>
              '. $add_td .'
              <td><a href="'. $editModeUrl .'">'. htmlspecialchars($sql->getValue('name')) . '</a></td>
              <td>'. htmlspecialchars($sql->getValue('prior')) .'</td>
              <td>'. $tmpl .'</td>
              <td>'. rex_formatter :: format($sql->getValue('createdate'), 'strftime', 'date') .'</td>
              <td><a href="'. $context->getUrl(array('article_id' => $sql->getValue('id'), 'function' => 'edit_art', 'artstart' => $artstart)) .'">'. $REX['I18N']->msg('change') .'</a></td>
              '. $add_extra .'
            </tr>
            ';

    }else
    {
      // --------------------- ARTIKEL NORMAL VIEW | NO EDIT NO ENTER

      $add_td = '';
      if ($REX['USER']->hasPerm('advancedMode[]'))
        $add_td = '<td class="rex-small">'. $sql->getValue('id') .'</td>';

      $art_status = $artStatusTypes[$sql->getValue('status')][0];
      $art_status_class = $artStatusTypes[$sql->getValue('status')][1];
      $tmpl = isset($TEMPLATE_NAME[$sql->getValue('template_id')]) ? $TEMPLATE_NAME[$sql->getValue('template_id')] : '';

      echo '<tr>
              <td class="rex-icon"><span class="rex-i-element '.$class.'"><span class="rex-i-element-text">' .htmlspecialchars($sql->getValue('name')).'"</span></span></td>
              '. $add_td .'
              <td>'. htmlspecialchars($sql->getValue('name')).'</td>
              <td>'. htmlspecialchars($sql->getValue('prior')).'</td>
              <td>'. $tmpl .'</td>
              <td>'. rex_formatter :: format($sql->getValue('createdate'), 'strftime', 'date') .'</td>
              <td><span class="rex-strike">'.$REX['I18N']->msg('change').'</span></td>
              <td><span class="rex-strike">'.$REX['I18N']->msg('delete').'</span></td>
              <td><span class="rex-strike '. $art_status_class .'">'. $art_status .'</span></td>
            </tr>
            ';
    }

    $sql->next();
  }

  // tbody nur anzeigen, wenn später auch inhalt drinnen stehen wird
  if($sql->getRows() > 0 || $function == 'add_art')
  {
    echo '
        </tbody>';
  }

  echo '
      </table>';

  if($function == 'add_art' || $function == 'edit_art')
  {
    echo '
      <script type="text/javascript">
        <!--
        jQuery(function($){
          $("#rex-form-field-name").focus();
        });
        //-->
      </script>
    </fieldset>
  </form>
  </div>';
  }
}



echo '</div>';

echo '
<!-- *** OUTPUT ARTICLES - END *** -->
';