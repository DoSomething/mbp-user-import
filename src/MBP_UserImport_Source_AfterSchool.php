<?php
/**
 * Service Class to provide properties and methods specific to co-marketing sources.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;
use \Exception;

/*
 * MBP_UserImport_Source_Niche: Properties and methods related to the co-marketing partner AfterSchool.
 *
 * CSV files are gathered from machines@dosomething.org in a structure defined in this class.
 */
class MBP_UserImport_Source_AfterSchool extends MBP_UserImport_BaseSource
{

  const USER_COUNTRY = 'US';
  const AFTERSCHOOL_OPTIN_SINGLE = 'SOLO';
  const AFTERSCHOOL_OPTIN_DOUBLE = 'PAIR';

  /**
   * Supported key / columns in CSV file from source.
   */
  protected function setKeys() {

    $keys = [
      'SentToPhone',
      'SenderName',
      'ReceiverName',
      'SchoolID',
      'SchoolName',
      'SchoolShort',
      'SchoolAbbreviation',
      'Message',
      'Optin',
      'CampaignID'
    ];

    return $keys;
  }
  
  /**
   * Logic to determine if data from row in After School CSV file can be processed.
   *
   * @param array $data
   *   Values being processed from row of data in CSV file.
   */
  public function canProcess($data) {

    if (empty($data['SentToPhone'])) {
      return false;
    }

    // Validate phone number based on the North American Numbering Plan
    // https://en.wikipedia.org/wiki/North_American_Numbering_Plan
    $regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
    $mobile = str_replace('"', '', $data['SentToPhone']);
    if (!(preg_match( $regex, $mobile))) {
      echo '** canProcess(): Invalid phone number based on North American Numbering Plan standard: ' .  $mobile, PHP_EOL;
      return false;
    }

    return true;
  }

  /**
   * Assign columns specific to the After School CSV file to common columns expected
   * by the consumer.
   *
   * @param array $data
   *   Values collected from the CSV file for assignment to expected indexes in the consumer.
   */
  public function setter(&$data) {

    $message = [];
    $message['subscribed'] = $data['subscribed'];
    $message['activity_timestamp'] = $data['activity_timestamp'];
    $message['application_id'] = $data['application_id'];
    $message['source'] = $data['source'];
    $message['source_file'] = $data['source_file'];

    $message['mobile'] = str_replace('"','', $data['SentToPhone']);

    $data['SenderName'] = str_replace('"','', $data['SenderName']);
    $data['ReceiverName'] = str_replace('"','', $data['ReceiverName']);
    $nameBits = explode(' ',$data['ReceiverName']);
    if (count($nameBits) > 1) {
      $message['last_name'] = ucfirst(array_pop($nameBits));
      $message['first_name'] = ucwords(implode(' ', $nameBits));
    }
    else {
      $message['first_name'] = ucfirst($nameBits[0]);
    }

    $campaignID = str_replace('"','', $data['CampaignID']);
    $campaignID = str_replace("\n",'', $campaignID);
    $message['as_campaign_id'] = $campaignID;

    // User profile custom field values
    $message['school_name'] = str_replace('"','', $data['SchoolShort']);
    $message['hs_name'] = str_replace('"','', $data['SchoolShort']);
    $optin = str_replace('"','', $data['Optin']);
    if ($optin == 'SINGLE_OPT_IN') {
      $message['optin'] = self::AFTERSCHOOL_OPTIN_SINGLE;
    }
    elseif ($optin == 'DOUBLE_OPT_IN') {
      $message['optin'] = self::AFTERSCHOOL_OPTIN_DOUBLE;
    }
    else {
      echo '=> WARNING: optin not set, unsupported value: ' . $optin, PHP_EOL;
    }

    // All After School users are assumed to be from the United States.
    $message['user_country'] = self::USER_COUNTRY;

    // Send all numbers to US mobile service
    $message['mobile_opt_in_path_id'] = $this->campaignIDtoOptInID($campaignID);

    // Wipe data values with formatted $message values
    $data = $message;
  }

  /**
   * Logic to process CSV file based on column / line endings.
   *
   * @param array $csvRow
   *   A row of user data from the CSV file.
   *
   * @return array $data
   *   CSV values formatted into an array.
   */
  public function process($csvRow) {

    $csvData = explode(',', $csvRow);
    $data = array();
    foreach ($this->keys as $signupIndex => $signupKey) {
      if (isset($csvData[$signupIndex]) && $csvData[$signupIndex] != '') {
        $data[$signupKey] = $csvData[$signupIndex];
      }
    }

    $this->statHat->ezCount('mbp-user-import:  MBP_UserImport_Source_AfterSchool: process', 1);
    return $data;
  }

  /**
   * campaignIDtoOptInID(): Determine Mobile Commons optin ID based on the After School
   * campaign ID sent with the user transactional data via CSV file. Throw Exception if lookup
   * does not have an optin assignment. This will result in the message staying in the queue
   * and triggering alerts of a new / unknown ID.
   *
   * @param integer $afterSchoolCampaignID
   *   The After School ID sent with the user transactional info.
   *
   * @return integer $mobileCommonsOptinID
   *   The Mobile Commons optin ID to send the user (phone number) to.
   */
  private function campaignIDtoOptInID($afterSchoolCampaignID) {

    switch ($afterSchoolCampaignID) {

      // Less Stress Text
      case 113728:
        $mobileCommonsOptinID = 203783;
        break;

      // Planet Zombie - To Be Determined
      case '120470':
        $mobileCommonsOptinID = 205829;
        break;

      default:
        throw new Exception('Undefined After School Campaign ID: ' . $afterSchoolCampaignID);
        break;

    }

    return $mobileCommonsOptinID;
  }

}
