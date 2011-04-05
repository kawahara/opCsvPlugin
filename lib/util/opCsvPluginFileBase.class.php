<?php

/**
 * The base class to import/export file.
 *
 * @package    opCsvPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@bucyou.net>
 */
abstract class opCsvPluginFileBase
{
  protected
    $fp = null;

  static protected
    $GETLEN = 4096;

  protected function fopen($filename, $mode)
  {
    if (!($this->fp = fopen($filename, $mode)))
    {
      throw new sfFileException();
    }

    return $this->fp;
  }

  public function __destruct()
  {
    fclose($this->fp);
  }
}
