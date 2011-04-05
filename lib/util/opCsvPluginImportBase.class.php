<?php

/**
 * The exception when data import from csv
 *
 * @package    opCsvPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@bucyou.net>
 */
class opCsvPluginImportException extends opCsvPluginException
{
}

/**
 * The base class to import data.
 *
 * If you read csv and save information to database,
 * use fetchAndSave() method.
 *
 * @package    opCsvPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@bucyou.net>
 */
abstract class opCsvPluginImportBase extends opCsvPluginFileBase
{
  /**
   * Constructor.
   *
   * @param string $file file name
   */
  public function __construct($file)
  {
    $this->fopen($file, 'r');
  }

  /**
   * Fetchs csv file and saves information to database.
   * You must specifiets line number of start-point and end-point
   * to import data.
   * This things is for peak load coverage.
   *
   * @param integer $start line number of start-point
   * @param integer $end   line number of end-point
   * @return array results
   */
  public function fetchAndSave($start, $end);
}
