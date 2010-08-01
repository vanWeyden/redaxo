<?php

class rex_be_page extends rex_be_page_container
{
  var $title;
  
  var $href;
  var $linkAttr;
  var $itemAttr;
  
  var $subPages;
  
  var $isCorePage;
  var $hasNavigation;
  var $activateCondition;
  var $requiredPermissions;
  var $path;
  
  function rex_be_page($title, $activateCondition = array(), $hidden = FALSE)
  {
    $this->title = $title;
    $this->subPages = array();
    $this->itemAttr = array();
    $this->linkAttr = array();
    
    $this->isCorePage = false;
    $this->hasNavigation = true;
    $this->activateCondition = $activateCondition;
    $this->requiredPermissions = array();
    $this->hidden = $hidden;
  }
  
  function &getPage()
  {
    return $this;
  }
  
  function getItemAttr($name, $default = '')
  {
    // return all attributes if null is passed as name
    if($name === null)
    {
      return $this->itemAttr;
    }
    
    return isset($this->itemAttr[$name]) ? $this->itemAttr[$name] : $default;
  }
  
  function setItemAttr($name, $value)
  {
    $this->itemAttr[$name] = $value;
  }
  
  function addItemClass($class)
  {
    $this->setItemAttr('class', ltrim($this->getItemAttr('class').' '. $class));
  }
  
  function getLinkAttr($name, $default = '')
  {
    // return all attributes if null is passed as name
    if($name === null)
    {
      return $this->linkAttr;
    }
    
    return isset($this->linkAttr[$name]) ? $this->linkAttr[$name] : $default;
  }
  
  function setLinkAttr($name, $value)
  {
    $this->linkAttr[$name] = $value;
  }
  
  function addLinkClass($class)
  {
    $this->setLinkAttr('class', ltrim($this->getLinkAttr('class').' '. $class));
  }
  
  function setHref($href)
  {
    $this->href = $href;
  }
  
  function getHref()
  {
    return $this->href;
  }

  function setHidden($hidden = TRUE)
  {
    $this->hidden = $hidden;
  }
  
  function getHidden()
  {
    return $this->hidden;
  }
  
  function setIsCorePage($isCorePage)
  {
    $this->isCorePage = $isCorePage;
  }
  
  function setHasNavigation($hasNavigation)
  {
    $this->hasNavigation = $hasNavigation;
  }
  
  function addSubPage(/*rex_be_page*/ $subpage)
  {
    $this->subPages[] = $subpage;
  }
  
  function &getSubPages()
  {
    return $this->subPages;
  }
  
  function getTitle()
  {
    return $this->title;
  }
  
  function setActivateCondition($activateCondition)
  {
    $this->activateCondition = $activateCondition;
  }
  
  function getActivateCondition()
  {
    return $this->activateCondition;
  }
  
  function isCorePage()
  {
    return $this->isCorePage;  
  }
  
  function hasNavigation()
  {
    return $this->hasNavigation;
  }
  
  function setRequiredPermissions($perm)
  {
    $this->requiredPermissions = (array) $perm;
  }
  
  function getRequiredPermissions()
  {
    return $this->requiredPermissions;
  }
  
  function checkPermission(/*rex_login_sql*/ $rexUser)
  {
    foreach($this->requiredPermissions as $perm)
    {
      if(!$rexUser->hasPerm($perm))
      {
        return false;
      }
    }
    return true;
  }
  
  function setPath($path)
  {
    $this->path = $path;
  }
  
  function hasPath()
  {
    return !empty($this->path);
  }
  
  function getPath()
  {
    return $this->path;
  }
  
  /*
   * Static Method: Returns True when the given be_page is valid
   */
  static public function isValid($be_page)
  {
    return is_object($be_page) && is_a($be_page, 'rex_be_page');
  }
}