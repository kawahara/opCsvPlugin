<?php

class opImportCsvFileForm extends BaseForm
{
  public function setup()
  {
    $this->setWidget('file', new sfWidgetFormInputFile());
    $this->setValidator('file', new sfValidatorFile(array('mime_types' => array('text/plain', 'text/comma-separated-values'))));
    $this->widgetSchema->setNameFormat('import[%s]');
  }
}
