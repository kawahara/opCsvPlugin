<?php

class opCsvPluginActions extends sfActions
{
  protected function renderJSON($data)
  {
    $this->getResponse()->setContentType('application/json');

    return $this->renderText(json_encode($data));
  }

  protected function getRange(sfWebRequest $request)
  {
    $range = explode('-', $request->getParameter('range'));
    $start = (int)$range[0];
    $end   = (int)$range[1];
    if (!($start <= $end && $start > 0))
    {
      throw new LogicException();
    }

    return array($start, $end);
  }

 /**
  * Executes index
  *
  * @param sfWebRequest $request
  */
  public function executeIndex(sfWebRequest $request)
  {
    $this->forward('opCsvPlugin', 'import');
  }

 /**
  * Executes import
  *
  * @param sfWebRequest $request
  */
  public function executeImport(sfWebRequest $request)
  {
    $this->form = new opImportCsvFileForm();
  }

 /**
  * Executes export
  *
  * @param sfWebRequest $request
  */
  public function executeExport(sfWebRequest $request)
  {
    // NOTE: This action is not implemented now.
    $this->token = opToolkit::getRandom(16);
  }


 /**
  * Executes generateExportData
  *
  * @param sfWebRequest $request
  */
  public function executeGenerateExportData(sfWebRequest $request)
  {
    $request->checkCSRFProtection();

    $token = $request->getParameter('token');
    list($start, $end) = $this->getRange($request);

    $export = new opCsvPluginExportDataMember($token);
    $export->save($start, $end);

    return sfView::NONE;
  }

 /**
  * Executes download
  *
  * @param sfWebRequest $request
  */
  public function executeDownload(sfWebRequest $request)
  {
    $token    = $request->getParameter('token');
    $filename = $request->getParameter('filename');

    return $this->renderText('hoge');
  }

 /**
  * Executes importFile
  *
  * @param sfWebRequest $request
  */
  public function executeImportFile(sfWebRequest $request)
  {
    $baseForm = new BaseForm();
    $this->csrfToken = $baseForm->getCSRFToken();
    $this->form = new opImportCsvFileForm();
    $this->form->bind($request->getParameter('import'), $request->getFiles('import'));
    if ($this->form->isValid())
    {
      $this->token = opToolkit::getRandom(16);
      $validatedFile = $this->form->getValue('file');
      $dir = sfConfig::get('sf_app_cache_dir').DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'opCsvPlugin';
      $validatedFile->save($dir.DIRECTORY_SEPARATOR.$this->token.'tmp.csv');
      Doctrine::getTable('SnsConfig')->set('op_csv_plugin_import', serialize(array(
        $this->token => $validatedFile->getSavedName(),
        'ts' => time()
      )));

      return sfView::SUCCESS;
    }

    $this->setTemplate('import');
  }

 /**
  * Executes importData
  *
  * @param sfWebRequest $request
  */
  public function executeImportData(sfWebRequest $request)
  {
    $request->checkCSRFProtection();

    $token = $request->getParameter('token');
    list($start, $end) = $this->getRange($request);

    $importInfo = unserialize(Doctrine::getTable('SnsConfig')->get('op_csv_plugin_import'));
    if (!(isset($importInfo[$token]) && isset($importInfo['ts']) && (time() - $importInfo['ts']) <= 3600))
    {
      return $this->renderJSON(array('status' => 'ERROR', 'msg' => 'Token is invalid'));
    }

    $import = new opCsvPluginImportMember($importInfo[$token]);
    return $this->renderJSON($import->fetchAndSave($start, $end));
  }
}
