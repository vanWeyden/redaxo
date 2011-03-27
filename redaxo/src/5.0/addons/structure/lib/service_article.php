<?php

class rex_article_service
{
  /**
   * Erstellt einen neuen Artikel
   *
   * @param array $data Array mit den Daten des Artikels
   *
   * @return array Ein Array welches den status sowie eine Fehlermeldung beinhaltet
   */
  static public function addArticle($data)
  {
    global $REX;

    $success = true;
    $message = '';

    if(!is_array($data))
    trigger_error('Expecting $data to be an array!', E_USER_ERROR);

    if(isset($data['prior']))
    {
      if($data['prior'] <= 0)
      $data['prior'] = 1;
    }

    $templates = rex_ooCategory::getTemplates($data['category_id']);

    // Wenn Template nicht vorhanden, dann entweder erlaubtes nehmen
    // oder leer setzen.
    if(!isset($templates[$data['template_id']]))
    {
      $data['template_id'] = 0;
      if(count($templates)>0)
      $data['template_id'] = key($templates);
    }

    $message = rex_i18n::msg('article_added');

    $AART = rex_sql::factory();
    foreach($REX['CLANG'] as $key => $val)
    {
      // ------- Kategorienamen holen
      $category = rex_ooCategory::getCategoryById($data['category_id'], $key);
      
      $categoryName = '';
      if($category)
      {
        $categoryName = $category->getName();
      }

      $AART->setTable($REX['TABLE_PREFIX'].'article');
      if (!isset ($id) or !$id)
      $id = $AART->setNewId('id');
      else
      $AART->setValue('id', $id);
      $AART->setValue('name', $data['name']);
      $AART->setValue('catname', $categoryName);
      $AART->setValue('attributes', '');
      $AART->setValue('clang', $key);
      $AART->setValue('re_id', $data['category_id']);
      $AART->setValue('prior', $data['prior']);
      $AART->setValue('path', $data['path']);
      $AART->setValue('startpage', 0);
      $AART->setValue('status', 0);
      $AART->setValue('template_id', $data['template_id']);
      $AART->addGlobalCreateFields();
      $AART->addGlobalUpdateFields();

      if($AART->insert())
      {
        // ----- PRIOR
        self::newArtPrio($data['category_id'], $key, 0, $data['prior']);
      }
      else
      {
        $success = false;
        $message = $AART->getError();
      }

      // ----- EXTENSION POINT
      $message = rex_register_extension_point('ART_ADDED', $message,
      array (
        'id' => $id,
        'clang' => $key,
        'status' => 0,
        'name' => $data['name'],
        're_id' => $data['category_id'],
        'prior' => $data['prior'],
        'path' => $data['path'],
        'template_id' => $data['template_id'],
        'data' => $data,
      )
      );
    }

    return array($success, $message);
  }

  /**
   * Bearbeitet einen Artikel
   *
   * @param int   $article_id  Id des Artikels der verändert werden soll
   * @param int   $clang       Id der Sprache
   * @param array $data        Array mit den Daten des Artikels
   *
   * @return array Ein Array welches den status sowie eine Fehlermeldung beinhaltet
   */
  static public function editArticle($article_id, $clang, $data)
  {
    global $REX;

    $success = false;
    $message = '';

    if(!is_array($data))
    trigger_error('Expecting $data to be an array!', E_USER_ERROR);

    $templates = rex_ooCategory::getTemplates($data['category_id']);

    // Wenn Template nicht vorhanden, dann entweder erlaubtes nehmen
    // oder leer setzen.
    if(!isset($templates[$data['template_id']]))
    {
      $data['template_id'] = 0;
      if(count($templates)>0)
      $data['template_id'] = key($templates);
    }

    // Artikel mit alten Daten selektieren
    $thisArt = rex_sql::factory();
    $thisArt->setQuery('select * from '.$REX['TABLE_PREFIX'].'article where id='.$article_id.' and clang='. $clang);

    if(isset($data['prior']))
    {
      if($data['prior'] <= 0)
      $data['prior'] = 1;
    }

    $EA = rex_sql::factory();
    $EA->setTable($REX['TABLE_PREFIX']."article");
    $EA->setWhere("id='$article_id' and clang=$clang");
    $EA->setValue('name', $data['name']);
    $EA->setValue('template_id', $data['template_id']);
    $EA->setValue('prior', $data['prior']);
    $EA->addGlobalUpdateFields();

    if($EA->update())
    {
      $message = rex_i18n::msg('article_updated');

      // ----- PRIOR
      self::newArtPrio($data['category_id'], $clang, $data['prior'], $thisArt->getValue('prior'));
      rex_article_cache::delete($article_id, $clang);

      // ----- EXTENSION POINT
      $message = rex_register_extension_point('ART_UPDATED', $message,
      array (
        'id' => $article_id,
				'article' => clone($EA),
				'article_old' => clone($thisArt),
        'status' => $thisArt->getValue('status'),
        'name' => $data['name'],
        'clang' => $clang,
        're_id' => $data['category_id'],
        'prior' => $data['prior'],
        'path' => $data['path'],
        'template_id' => $data['template_id'],

        'data' => $data,
      )
      );

      $success = true;
    }
    else
    {
      $message = $EA->getError();
    }

    return array($success, $message);
  }

  /**
   * Löscht einen Artikel und reorganisiert die Prioritäten verbleibender Geschwister-Artikel
   *
   * @param int $article_id Id des Artikels die gelöscht werden soll
   *
   * @return array Ein Array welches den status sowie eine Fehlermeldung beinhaltet
   */
  static public function deleteArticle($article_id)
  {
    global $REX;

    $return = array();
    $return['state'] = FALSE;
    $return['message'] = '';

    $Art = rex_sql::factory();
    $Art->setQuery('select * from '.$REX['TABLE_PREFIX'].'article where id='.$article_id.' and startpage=0');

    if ($Art->getRows() == count($REX['CLANG']))
    {
      $return = self::_deleteArticle($article_id);
      $re_id = $Art->getValue("re_id");

      foreach($REX['CLANG'] as $clang => $clang_name)
      {
        // ----- PRIOR
        self::newArtPrio($Art->getValue("re_id"), $clang, 0, 1);

        // ----- EXTENSION POINT
        $return = rex_register_extension_point('ART_DELETED', $return,
        array (
          "id"          => $article_id,
          "clang"       => $clang,
          "re_id"       => $re_id,
          'name'        => $Art->getValue('name'),
          'status'      => $Art->getValue('status'),
          'prior'       => $Art->getValue('prior'),
          'path'        => $Art->getValue('path'),
          'template_id' => $Art->getValue('template_id'),
        )
        );

        $Art->next();
      }
    }
    return array($return['state'],$return['message']);
  }

  /**
   * Löscht einen Artikel
   *
   * @param $id ArtikelId des Artikels, der gelöscht werden soll
   *
   * @return Erfolgsmeldung bzw. Fehlermeldung bei Fehlern.
   */
  static private function _deleteArticle($id)
  {
    global $REX;

    // artikel loeschen
    //
    // kontrolle ob erlaubnis nicht hier.. muss vorher geschehen
    //
    // -> startpage = 0
    // --> artikelfiles löschen
    // ---> article
    // ---> content
    // ---> clist
    // ---> alist
    // -> startpage = 1
    // --> rekursiv aufrufen

    $return = array();
    $return['state'] = FALSE;

    if ($id == $REX['START_ARTICLE_ID'])
    {
      $return['message'] = rex_i18n::msg('cant_delete_sitestartarticle');
      return $return;
    }
    if ($id == $REX['NOTFOUND_ARTICLE_ID'])
    {
      $return['message'] = rex_i18n::msg('cant_delete_notfoundarticle');
      return $return;
    }

    $ART = rex_sql::factory();
    $ART->setQuery('select * from '.$REX['TABLE_PREFIX'].'article where id='.$id.' and clang=0');

    if ($ART->getRows() > 0)
    {
      $re_id = $ART->getValue('re_id');
      $return['state'] = true;

      $return = rex_register_extension_point('ART_PRE_DELETED', $return, array (
                    "id"          => $id,
                    "re_id"       => $re_id,
                    'name'        => $ART->getValue('name'),
                    'status'      => $ART->getValue('status'),
                    'prior'       => $ART->getValue('prior'),
                    'path'        => $ART->getValue('path'),
                    'template_id' => $ART->getValue('template_id')
      )
      );

      if(!$return["state"])
      {
        return $return;
      }

      if ($ART->getValue('startpage') == 1)
      {
        $return['message'] = rex_i18n::msg('category_deleted');
        $SART = rex_sql::factory();
        $SART->setQuery('select * from '.$REX['TABLE_PREFIX'].'article where re_id='.$id.' and clang=0');
        for ($i = 0; $i < $SART->getRows(); $i ++)
        {
          $return['state'] = self::_deleteArticle($id);
          $SART->next();
        }
      }else
      {
        $return['message'] = rex_i18n::msg('article_deleted');
      }

      // Rekursion über alle Kindkategorien ergab keine Fehler
      // => löschen erlaubt
      if($return['state'] === true)
      {
        rex_article_cache::delete($id);
        $ART->setQuery('delete from '.$REX['TABLE_PREFIX'].'article where id='.$id);
        $ART->setQuery('delete from '.$REX['TABLE_PREFIX'].'article_slice where article_id='.$id);

        // --------------------------------------------------- Listen generieren
        rex_article_cache::generateLists($re_id);
      }

      return $return;
    }
    else
    {
      $return['message'] = rex_i18n::msg('category_doesnt_exist');
      return $return;
    }
  }


  /**
   * Ändert den Status des Artikels
   *
   * @param int       $article_id Id des Artikels die gelöscht werden soll
   * @param int       $clang      Id der Sprache
   * @param int|null  $status     Status auf den der Artikel gesetzt werden soll, oder NULL wenn zum nächsten Status weitergeschaltet werden soll
   *
   * @return array Ein Array welches den status sowie eine Fehlermeldung beinhaltet
   */
  static public function articleStatus($article_id, $clang, $status = null)
  {
    global $REX;

    $success = false;
    $message = '';
    $artStatusTypes = self::statusTypes();

    $GA = rex_sql::factory();
    $GA->setQuery("select * from ".$REX['TABLE_PREFIX']."article where id='$article_id' and clang=$clang");
    if ($GA->getRows() == 1)
    {
      // Status wurde nicht von außen vorgegeben,
      // => zyklisch auf den nächsten Weiterschalten
      if(!$status)
      $newstatus = ($GA->getValue('status') + 1) % count($artStatusTypes);
      else
      $newstatus = $status;

      $EA = rex_sql::factory();
      $EA->setTable($REX['TABLE_PREFIX']."article");
      $EA->setWhere("id='$article_id' and clang=$clang");
      $EA->setValue('status', $newstatus);
      $EA->addGlobalUpdateFields($REX['REDAXO'] ? null : 'frontend');

      if($EA->update())
      {
        $message = rex_i18n::msg('article_status_updated');
        rex_article_cache::delete($article_id, $clang);

        // ----- EXTENSION POINT
        $message = rex_register_extension_point('ART_STATUS', $message, array (
        'id' => $article_id,
        'clang' => $clang,
        'status' => $newstatus
        ));

        $success = true;
      }
      else
      {
        $message = $EA->getError();
      }
    }
    else
    {
      $message = rex_i18n::msg("no_such_category");
    }

    return array($success, $message);
  }

  /**
   * Gibt alle Stati zurück, die für einen Artikel gültig sind
   *
   * @return array Array von Stati
   */
  static public function statusTypes()
  {
    global $REX;

    static $artStatusTypes;

    if(!$artStatusTypes)
    {
      $artStatusTypes = array(
      // Name, CSS-Class
      array(rex_i18n::msg('status_offline'), 'rex-offline'),
      array(rex_i18n::msg('status_online'), 'rex-online')
      );

      // ----- EXTENSION POINT
      $artStatusTypes = rex_register_extension_point('ART_STATUS_TYPES', $artStatusTypes);
    }

    return $artStatusTypes;
  }

  /**
   * Berechnet die Prios der Artikel in einer Kategorie neu
   *
   * @param $re_id    KategorieId der Kategorie, die erneuert werden soll
   * @param $clang    ClangId der Kategorie, die erneuert werden soll
   * @param $new_prio Neue PrioNr der Kategorie
   * @param $old_prio Alte PrioNr der Kategorie
   *
   * @deprecated 4.1 - 26.03.2008
   * Besser die rex_organize_priorities() Funktion verwenden!
   *
   * @return void
   */
  static public function newArtPrio($re_id, $clang, $new_prio, $old_prio)
  {
    global $REX;
    if ($new_prio != $old_prio)
    {
      if ($new_prio < $old_prio)
      $addsql = "desc";
      else
      $addsql = "asc";

      rex_organize_priorities(
      $REX['TABLE_PREFIX'].'article',
      'prior',
      'clang='. $clang .' AND ((startpage<>1 AND re_id='. $re_id .') OR (startpage=1 AND id='. $re_id .'))',
      'prior,updatedate '. $addsql,
      'pid'
      );

      rex_article_cache::deleteLists($re_id, $clang);
    }
  }
}