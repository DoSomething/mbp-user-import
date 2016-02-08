<?php
/**
 * Service Class to provide properties and methods specific to co-marketing sources.
 */

namespace DoSomething\MBP_UserImport;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBP_UserImport_Source_Niche: Properties and methods related to the co-marketing partner niche.com.
 *
 * CSV files are gathered from machines@dosomething.org in a structure defined in this class.
 */
class MBP_UserImport_Source_Niche extends MBP_UserImport_BaseSource
{

  /**
   * Supported key / columns in CSV file from source.
   */
  protected function setKeys() {

    $keys = [
      'first_name',
      'last_name',
      'email',
      'address1',
      'address2',
      'city',
      'state',
      'zip',
      'phone',
      'hs_gradyear',
      'birthdate',
      'race',
      'religion',
      'hs_name',
      'college_name',
      'major_name',
      'degree_type',
      'sat_math',
      'sat_verbal',
      'sat_writing',
      'act_math',
      'act_english',
      'gpa',
      'role',
    ];

    return $keys;
  }

  /**
   * Assign columns specific to the Niche CSV file to common columns expected
   * by the consumer.
   *
   * @param array $data
   *   Values collected from the CSV file for assignment to expected indexes in
   *   the consumer.
   */
  public function canProcess($data) {

    if (empty($data['email'])) {
      return false;
    }

    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
      echo '- canProcess(), failed FILTER_VALIDATE_EMAIL: ' . $data['email'], PHP_EOL;
      return false;
    }

    return true;
  }

  /**
   * Assign columns specific to the Niche CSV file to common columns expected
   * by the consumer.
   *
   * @param array $data
   *   Values collected from the CSV file for assignment to expected indexes in the consumer.
   */
  public function setter(&$data) {

    // All niche users are assumed to be from the United States.
    $data['user_country'] = 'US';

    // Send all numbers to US mobile service
    // Mobile Commons opt-in path when user registers for site
    $data['mobile_opt_in_path_id'] = '164905';

    // General MailChimp list for US users.
    $data['mailchimp_list_id'] = 'f2fab1dfd4';
  }

  /**
   * Logic to process CSV file based on column / line endings.
   *
   * @param array $CSVRow
   *   A row of user data from the CSV file.
   *
   * @return array $data
   *   
   */
  public function process($CSVRow) {

    $CSVRow = str_replace('"', '',  $CSVRow);
    $CSVRow = str_replace("\r\n", '',  $CSVRow);
    $CSVData = explode(',', $CSVRow);

    $data = array();
    foreach ($this->keys as $signupIndex => $signupKey) {
      if (isset($CSVData[$signupIndex]) && $CSVData[$signupIndex] != '') {
        $data[$signupKey] = $CSVData[$signupIndex];
      }
    }

    return $data;
  }

}
