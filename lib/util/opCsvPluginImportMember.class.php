<?php

class opCsvPluginImportMember
{
  protected
    $fp = null,
    $fields = null,
    $requireFields = array(
      'nickname',
      'mail_address|pc_mail_address|mobile_mail_address',
      'password',
    ),
    $uniqueMemberConfigFields = array(
      'pc_address',
      'mobile_address',
    );

  static protected
    $GETLEN = 4096;

  public function __construct($file)
  {
    if (!($this->fp = fopen($file, 'r')))
    {
      throw new sfFileException();
    }
  }

  public function __destruct()
  {
    fclose($this->fp);
  }

  protected function save($data)
  {
    $event = new sfEvent(null, 'op_csv.import_filter_data');
    sfContext::getInstance()->getEventDispatcher()->filter($event, $data);
    $fields = $event->getReturnValue();

    $member = new Member();
    $memberConfigs  = array();
    $memberProfiles = array();

    foreach ($data as $key => $record)
    {
      $field = $this->fields[$key];

      if ($field['is_profile'])
      {
        //TODO: import profile

        continue;
      }

      switch ($field['name'])
      {
        case "nickname" :
          $validator = new opValidatorString(array('max_length' => 64, 'trim' => true, 'required' => true));
          $member->name = $validator->clean($record);

          break;
        case "mail_address" :
          $validator = new sfValidatorEmail(array('trim' => true, 'required' => true));
          $address = $validator->clean($record);
          if (opToolkit::isMobileEmailAddress($address))
          {
            $memberConfigs['mobile_address'] = $address;
          }

          $memberConfigs['pc_address'] = $address;

          break;
        case "pc_mail_address" :
          $validator = new sfValidatorEmail(array('trim' => true, 'required' => true));
          $address = $validator->clean($record);
          if (opToolkit::isMobileEmailAddress($address))
          {
            throw new RuntimeException();
          }

          $memberConfigs['pc_address'] = $address;

          break;
        case "mobile_mail_address" :
          $validator = new sfValidatorEmail(array('trim' => true, 'required' => true));
          $address = $validator->clean($record);
          if (!opToolkit::isMobileEmailAddress($address))
          {
            throw new RuntimeException();
          }
          $memberConfigs['mobile_address'] = $address;

          break;
        case "password" :
          $validator = new sfValidatorPassword(array('trim' => true, 'required' => true));
          $memberConfigs['password'] = $validator->clean($record);
          break;
      }
    }

    // check unique for member config
    foreach ($this->uniqueMemberConfigFields as $name)
    {
      if (
        isset($memberConfigs[$name]) &&
        Doctrine::getTable('MemberConfig')->retrieveByNameAndValue($name, $memberConfigs[$name])
      )
      {
        throw new RuntimeException();
      }
    }

    $member->save();
    foreach ($memberConfigs as $key => $value)
    {
      $member->setConfig($key, $value);
    }
    foreach ($memberProfiles as $key => $value)
    {
    }
  }

  protected function bindFields($data)
  {
    $event = new sfEvent(null, 'op_csv.import_filter_field');
    sfContext::getInstance()->getEventDispatcher()->filter($event, $data);
    $fields = $event->getReturnValue();

    foreach ($this->requireFields as $name)
    {
      $names = explode('|', $name);
      if (is_array($names))
      {
        if (1 < count($name)) //OR
        {
          $valid = false;
          foreach ($names as $name)
          {
            if (in_array($name, $fields))
            {
              $valid = true;
              break;
            }
          }

          if (!$valid)
          {
            throw new RuntimeException();
          }
        }
        elseif (1 === count($name))
        {
          if (!in_array($names[0], $fields))
          {
            throw new RuntimeException();
          }
        }
      }
    }

    $clean = array();
    foreach ($fields as $key => $field)
    {
      if (preg_match('/^profile\[(.*)\]$/', $field, $match))
      {
        $clean[$key] = array('is_profile' => true, 'name' => $match[1]);
        continue;
      }

      $clean[$key] = array('is_profile' => false, 'name' => $field);
    }

    $this->fields = $clean;
  }

 /**
  * fetchAndSave
  *
  * @params integer $start
  * @params integer $end
  * @return array
  */
  public function fetchAndSave($start, $end)
  {
    if (!($data = fgetcsv($this->fp, self::$GETLEN)))
    {
      return array('status' => 'ERROR' , 'msg' => 'File is empty.');
    }

    try
    {
      $this->bindFields($data);
    }
    catch (RuntimeException $e)
    {
      return array('status' => 'ERROR' , 'msg' => 'Fields is invalid.');
    }

    // Moving file pointer to start line.
    $i = 1;
    for (; $i < $start; $i++)
    {
      if (!fgets($this->fp, self::$GETLEN))
      {
        // End of file.
        return array('status' => 'COMPLETE');
      }
    }

    $errors = array();
    for (; $i < $end; $i++)
    {
      if ($data = fgetcsv($this->fp, self::$GETLEN))
      {
        try
        {
          $this->save($data);
        }
        catch (Exception $e)
        {
          $errors[] = 'Line'.($i+1).' '.$e->getMessage();
        }
      }
      else
      {
        return array('status' => 'COMPLETE', 'msgs' => $errors);
      }
    }

    return array('status' => 'CONTINUE', 'msgs' => $errors);
  }
}
