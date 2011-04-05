<?php

/**
 * This class implement method to export member's information.
 *
 * @package    opCsvPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@bucyou.net>
 */
class opCsvPluginExportMember extends opCsvPluginExportBase
{
  public function writeField()
  {
    $baseFields = array('nickname', 'pc_mail_address', 'mobile_mail_address');
  }

  public function fetchAndWrite($start, $end)
  {
  }
}
