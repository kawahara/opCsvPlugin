<?php

/**
 * This class implement method to import member's information.
 *
 * @package    opCsvPlugin
 * @subpackage util
 * @author     Shogo Kawahara <kawahara@bucyou.net>
 */
class opCsvPluginImportMember extends opCsvPluginImportBase
{
  protected
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

  protected function save($data)
  {
    $event = new sfEvent(null, 'op_csv.import_filter_data');
    sfContext::getInstance()->getEventDispatcher()->filter($event, $data);
    $fields = $event->getReturnValue();

    $member = new Member();
    $memberConfigs  = array();
    $memberProfiles = array();

    foreach ($data as $key => $col)
    {
      $field = $this->fields[$key];

      if ($field['is_profile'])
      {
        $validator = $field['validator'];
        $memberProfiles[$field['name']] = $validator->clean($col);

        continue;
      }
      else
      {
        switch ($field['name'])
        {
          case "nickname" :
            $validator = new opValidatorString(array('max_length' => 64, 'trim' => true, 'required' => true));
            $member->name = $validator->clean($col);

            break;
          case "mail_address" :
            $validator = new sfValidatorEmail(array('trim' => true, 'required' => true));
            $address = $validator->clean($col);
            if (opToolkit::isMobileEmailAddress($address))
            {
              $memberConfigs['mobile_address'] = $address;
            }

            $memberConfigs['pc_address'] = $address;

            break;
          case "pc_mail_address" :
            $validator = new sfValidatorEmail(array('trim' => true, 'required' => true));
            $address = $validator->clean($col);
            if (opToolkit::isMobileEmailAddress($address))
            {
              throw new opCsvPluginImportException("'pc_mail_address' is mobile address.");
            }

            $memberConfigs['pc_address'] = $address;

            break;
          case "mobile_mail_address" :
            $validator = new sfValidatorEmail(array('trim' => true, 'required' => true));
            $address = $validator->clean($col);
            if (!opToolkit::isMobileEmailAddress($address))
            {
              throw new opCsvPluginImportException("'mobile_mail_address' is not support mobile address.");
            }
            $memberConfigs['mobile_address'] = $address;

            break;
          case "password" :
            $validator = new sfValidatorPassword(array('trim' => true, 'required' => true));
            $memberConfigs['password'] = $validator->clean($col);
            break;
        }
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
        throw new opCsvPluginImportException("'".$name."' duplicated.");
      }
    }

    $member->setIsActive(true);
    $member->save();
    foreach ($memberConfigs as $key => $value)
    {
      $member->setConfig($key, $value);
    }
    foreach ($memberProfiles as $key => $value)
    {
      $profile = $this->field[$key]['profile'];
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
        $object = Doctrine::getTable('Profile')->findOneByName($match[1]);
        if (!$object)
        {
          throw new RuntimeException('Unknown profile field.');
        }
        if ($object->isMultipleSelect())
        {
          throw new RuntimeException('Unsported profile item of date and checkbox.');
        }
        $choices = array();
        if ($object->isSingleSelect())
        {
          $profileOptions = Doctrine::getTable('ProfileOption')->retrieveByProfileId($object->getId());
          foreach ($profileOptions as $option)
          {
            $choices[] = $object->getId();
          }
        }
        $validator = opFormItemGenerator::generateValidator($object->getValueType(), $choices);

        $clean[$key] = array('is_profile' => true, 'name' => $match[1], 'object' => $object, 'validator' => $validator);
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
    catch (opCsvPluginImportException $e)
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
