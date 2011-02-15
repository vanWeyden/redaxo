<?php

/**
 * Regelt die Rechte an den einzelnen Kategorien und gibt den Pfad aus
 * Kategorien = Startartikel und Bezüge
 * @package redaxo4
 * @version svn:$Id$
 */

$KATebene = 0; // aktuelle Ebene: default
$KATPATH = '|'; // Standard für path Eintragungen in DB
if (!isset($KATout)) $KATout = ''; // Variable definiert und vorbelegt wenn nicht existent

if (!isset($KAToutARR)) $KAToutARR = array(); // Variable definiert und vorbelegt wenn nicht existent
$KAToutARR[] = '<a href="index.php?page=structure&amp;category_id=0&amp;clang='. $clang .'"'. rex_tabindex() .'>Homepage</a>';


$KATPERM = false;
if ($REX['USER']->hasPerm('csw[0]') || $REX['USER']->isAdmin()) $KATPERM = true;

$KAT = rex_sql::factory();
// $KAT->debugsql = true;
$KAT->setQuery("SELECT * FROM ".$REX['TABLE_PREFIX']."article WHERE id=$category_id AND startpage=1 AND clang=$clang");

if ($KAT->getRows()!=1)
{
  // kategorie existiert nicht
  if($category_id != 0)
  {
    $category_id = 0;
    $article_id = 0;
  }
}
else
{
  // kategorie existiert

  $KPATH = explode('|',$KAT->getValue('path'));

  $KATebene = count($KPATH)-1;
  for ($ii=1;$ii<$KATebene;$ii++)
  {
    $SKAT = rex_sql::factory();
    $SKAT->setQuery('SELECT * FROM '. $REX['TABLE_PREFIX'] .'article WHERE id='. $KPATH[$ii] .' AND startpage=1 AND clang='. $clang);

    $catname = str_replace(' ', '&nbsp;', htmlspecialchars($SKAT->getValue('catname')));
    $catid = $SKAT->getValue('id');

    if ($SKAT->getRows()==1)
    {
      $KATPATH .= $KPATH[$ii]."|";
      if ($KATPERM || $REX['USER']->hasCategoryPerm($catid))
      {
        $KAToutARR[] = '<a href="index.php?page=structure&amp;category_id='. $catid .'&amp;clang='. $clang .'"'. rex_tabindex() .'>'. $catname .'</a>';
        if($REX['USER']->hasPerm('csw['.$catid.']'))
        {
          $KATPERM = true;
        }
      }
    }
  }

  if ($KATPERM || $REX['USER']->hasPerm('csw['. $category_id .']') /*|| $REX['USER']->hasPerm('csr['. $category_id .']')*/)
  {
    $catname = str_replace(' ', '&nbsp;', htmlspecialchars($KAT->getValue('catname')));

    $KAToutARR[] = '<a href="index.php?page=structure&amp;category_id='. $category_id .'&amp;clang='. $clang .'"'. rex_tabindex() .'>'. $catname .'</a>';
    $KATPATH .= $category_id .'|';

    if ($REX['USER']->hasPerm('csw['. $category_id .']'))
    {
      $KATPERM = true;
    }
  }
  else
  {
    $category_id = 0;
    $article_id = 0;
  }
}

$KATout = '
<!-- *** OUTPUT OF CATEGORY-TOOLBAR - START *** -->';

/*	ul-Liste erstellen */
$list = array();
$list[1]['attributes']['class'] = 'rex-navi';
$list[1]['entries'] = $KAToutARR;

$fragment = new rex_fragment();
$fragment->setVar('lists', $list, false);
$ul_list = $fragment->parse('list/ul_list');
unset($fragment);


/*	dl-Liste erstellen  */
$list = array();
$list[1]['attributes']['class'] = 'rex-navi-path';
$list[1]['entries'][$REX['I18N']->msg('path')] = $ul_list;

$fragment = new rex_fragment();
$fragment->setVar('lists', $list, false);
$dl_list = $fragment->parse('list/dl_list');
$dl_list = preg_replace('/(?:(?<=\>)|(?<=\/\>))(\s+)(?=\<\/?)/', '', $dl_list);
unset($fragment);

$KATout .= $dl_list;
$KATout .= '
<!-- *** OUTPUT OF CATEGORY-TOOLBAR - END *** -->';