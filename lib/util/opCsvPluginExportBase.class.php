<?php

/**
 * The exception when data export csv.
 *
 * @package    opCsvPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@bucyou.net>
 */
class opCsvPluginExportException extends opCsvPluginException
{
}

/**
 * The base class to export data.
 *
 * @package    opCsvPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@bucyou.net>
 */
abstract class opCsvPluginExportBase extends opCsvPluginFileBase
{
  /**
   * Constructor.
   *
   * @param string $file file name
   */
  public function __construct($file)
  {
    $this->fopen($file, 'a+');
  }

  /**
   * writeField
   *
   * @return array results
   */
  public function writeField();

  /**
   * Fetchs information from database and saves file as csv format.
   * You must specifiets line number of start-point and end-point
   * to import data.
   * This things is for peak load coverage.
   *
   * @param integer $start line number of start-point
   * @param integer $end   line number of end-point
   * @return array results
   */
  public function fetchAndWrite($start, $end);
}
