<?php
/**
 * Service Class to provide properties and methods specific to co-marketing sources.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBP_UserImport_Source_Niche: Properties and methods related to the co-marketing partner AfterSchool.
 *
 * CSV files are gathered from machines@dosomething.org in a structure defined in this class.
 */
class MBP_UserImport_Source_AfterSchool extends MBP_UserImport_BaseSource
{

  const USER_COUNTRY = 'US';
  const MOBILE_OPT_IN_PATH_ID = 200527;

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
      'Message'
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
    $mobile = str_replace("'", '', $data['SentToPhone']);
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
    $message['mobile'] = str_replace("'",'', $data['SentToPhone']);

    $data['SenderName'] = str_replace("'",'', $data['SenderName']);
    $nameBits = explode(' ',$data['SenderName']);
    if (count($nameBits) > 1) {
      $message['last_name'] = array_pop($nameBits);
      $message['first_name'] = implode(' ', $nameBits);
    }
    else {
      $message['first_name'] = $nameBits[0];
    }

    $message['hs_name'] = str_replace("'",'', $data['SchoolShort']);
    $message['hs_id'] = (int) str_replace("'",'', $data['SchoolID']);

    // All After School users are assumed to be from the United States.
    $message['user_country'] = self::USER_COUNTRY;

    // Send all numbers to US mobile service
    // Mobile Commons opt-in path when user registers for site
    $message['mobile_opt_in_path_id'] = self::MOBILE_OPT_IN_PATH_ID;

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

    return $data;
  }

}
