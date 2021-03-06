<?php

class rex_var_property_test extends rex_var_base_test
{
  public function setUp()
  {
    rex::setProperty('myCoreProperty', 'myCorePropertyValue');
    rex_addon::get('tests')->setProperty('myPackageProperty', 'myPackagePropertyValue');
  }

  public function tearDown()
  {
    rex::removeProperty('myCoreProperty');
    rex_addon::get('tests')->removeProperty('tests', 'myPackageProperty');
  }

  public function propertyReplaceProvider()
  {
    return array(
      array('REX_PROPERTY[key=myCoreProperty]', 'myCorePropertyValue'),
      array('REX_PROPERTY[namespace=tests key=myPackageProperty]', 'myPackagePropertyValue')
    );
  }

  /**
   * @dataProvider propertyReplaceProvider
   */
  public function testPropertyReplace($content, $expectedOutput)
  {
    $this->assertParseOutputEquals($expectedOutput, $content);
  }
}
