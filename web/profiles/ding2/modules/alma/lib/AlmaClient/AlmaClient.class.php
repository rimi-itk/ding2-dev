<?php
/**
 * @file
 * Provides a client for the Axiell Alma library information webservice.
 */

define('ALMA_SERVICE_TYPE_DUE_DATE_ALERT', 'dueDateAlert');
define('ALMA_SERVICE_TYPE_LIBRARY_MESSAGE', 'libraryMessage');
define('ALMA_SERVICE_TYPE_OVERDUE_NOTICE', 'overdueNotice');
define('ALMA_SERVICE_TYPE_PICK_UP_NOTICE', 'pickUpNotice');
define('ALMA_SERVICE_METHOD_SMS', 'sms');

class AlmaClient {
  /**
   * @var $base_url
   * The base server URL to run the requests against.
   */
  private $base_url;

  /**
   * The salt which will be used to scramble sensitive information across
   * all requests for the page load.
   */
  private static $salt;

  /**
   * @var $ssl_version
   * The SSL/TLS version to use when communicating with the service.
   */
  private $ssl_version;

  /**
   * Constructor, checking if we have a sensible value for $base_url.
    *
   * @param string $base_url
   *   The base url for the Alma end-point.
   * @param string $ssl_version
   *   The TLS/SSL version to use ('ssl', 'sslv2', 'sslv3' or 'tls').
   *
   * @throws \Exception
   */
  public function __construct($base_url, $ssl_version) {
    if (stripos($base_url, 'http') === 0 && filter_var($base_url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
      $this->base_url = $base_url;
    }
    else {
      // TODO: Use a specialised exception for this.
      throw new Exception('Invalid base URL: ' . $base_url);
    }

    $this->ssl_version = $ssl_version;

    self::$salt = mt_rand();
  }

  /**
   * Perform request to the Alma server.
   *
   * @param string $method
   *    The REST method to call e.g. 'patron/status'. borrCard and pinCode
   *    are required for all request related to library patrons.
   * @param array $params
   *    Query string parameters in the form of key => value.
   * @param bool $check_status
   *    Check the status element, and throw an exception if it is not ok.
   *
   * @return DOMDocument
   *    A DOMDocument object with the response.
   */
  public function request($method, $params = array(), $check_status = TRUE) {
    $start_time = explode(' ', microtime());
    // For use with a non-Drupal-system, we should have a way to swap
    // the HTTP client out.
    $request = drupal_http_request(url($this->base_url . $method, array('query' => $params)), array('secure_socket_transport' => $this->ssl_version));
    $stop_time = explode(' ', microtime());
    // For use with a non-Drupal-system, we should have a way to swap
    // logging and logging preferences out.
    if (variable_get('alma_enable_logging', FALSE)) {
      $seconds = floatval(($stop_time[1] + $stop_time[0]) - ($start_time[1] + $start_time[0]));

      // Filter params to avoid logging sensitive data.
      // This can be disabled by setting alma_logging_filter_params = 0. There
      // is no UI for setting this variable
      // It is intended for settings.php in development environments only.
      $params = (variable_get('alma_logging_filter_params', 1)) ? self::filter_request_params($params) : $params;

      // Log the request.
      watchdog('alma', 'Sent request: @url (@seconds s)', array('@url' => url($this->base_url . $method, array('query' => $params)), '@seconds' => $seconds), WATCHDOG_DEBUG);
    }

    if ($request->code == 200) {
      // Since we currently have no need for the more advanced stuff
      // SimpleXML provides, we'll just use DOM, since that is a lot
      // faster in most cases.
      $doc = new DOMDocument();
      $doc->loadXML($request->data);
      if (!$check_status || $doc->getElementsByTagName('status')->item(0)->getAttribute('value') == 'ok') {
        return $doc;
      }
      else {
        $message = $doc->getElementsByTagName('status')->item(0)->getAttribute('key');
        switch ($message) {
          case '':
          case 'borrCardNotFound':
            throw new AlmaClientBorrCardNotFound('Invalid borrower credentials');

          case 'reservationNotFound':
            throw new AlmaClientReservationNotFound('Reservation not found');

          case 'invalidPatron':
            if ($method == 'patron/selfReg') {
              throw new AlmaClientUserAlreadyExistsError();
            }
            else {
              throw new AlmaClientInvalidPatronError();
            }

          default:
            throw new AlmaClientCommunicationError('Status is not okay: ' . $message);
        }
      }
    }
    else {
      throw new AlmaClientHTTPError('Request error: ' . $request->code . $request->error);
    }
  }

  /**
   * Filters sensitive information in request parameters allowing the values to
   * be logged.
   *
   * @param array $params
   *   An array of request information
   *
   * @return array
   *   An array of filtered request information
   */
  private static function filter_request_params($params) {
    // Scramble sensitive information
    $sensitive = array(
      'borrCard',
      'pinCode',
      'pinCodeChange',
      'address',
      'emailAddress',
      'securityNumber',
      'pin',
      'email',
    );

    $log_params = array();
    foreach ($params as $key => $value) {
      if (in_array($key, $sensitive)) {
        // Replace the value with a scrambled version generated using md5() and
        // the static salt. This way all requests generated by the same page
        // load can be grouped.
        $value = substr(md5($value . self::$salt), 0, strlen($value));
      }
      $log_params[$key] = $value;
    }

    return $log_params;
  }

  /**
   * Get branch names from Alma.
   *
   * Formats the list of branches in an array usable for form API selects.
   *
   * @return array
   *   List of branches, keyed by branch_id.
   */
  public function get_branches() {
    $branches = array();
    $doc = $this->request('organisation/branches');

    foreach ($doc->getElementsByTagName('branch') as $branch) {
      $branches[$branch->getAttribute('id')] = $branch->getElementsByTagName('name')->item(0)->nodeValue;
    }

    return $branches;
  }

  /**
   * Get reservation branch names from Alma.
   *
   * Formats the list of branches in an array usable for form API selects.
   *
   * @return array
   *   List of branches, keyed by branch_id
   */
  public function get_reservation_branches() {
    $branches = array();
    $doc = $this->request('reservation/branches');
    foreach ($doc->getElementsByTagName('branch') as $branch) {
      $branches[$branch->getAttribute('id')] = $branch->getElementsByTagName('name')->item(0)->nodeValue;
    }

    return $branches;
  }

  /**
   * Get department names from Alma.
   *
   * Formats the list of branches in an array usable for form API selects.
   *
   * @return array
   *   List of departments, keyed by department id.
   */
  public function get_departments() {
    $departments = array();
    $doc = $this->request('organisation/departments');

    foreach ($doc->getElementsByTagName('department') as $department) {
      $departments[$department->getAttribute('id')] = $department->getElementsByTagName('name')->item(0)->nodeValue;
    }

    return $departments;
  }

  /**
   * Get location names from Alma.
   *
   * Formats the list of branches in an array usable for form API selects.
   *
   * @return array
   *   List of locations, keyed by location id.
   */
  public function get_locations() {
    $locations = array();
    $doc = $this->request('organisation/locations');

    foreach ($doc->getElementsByTagName('location') as $location) {
      $locations[$location->getAttribute('id')] = $location->getElementsByTagName('name')->item(0)->nodeValue;
    }

    return $locations;
  }

  /**
   * Get sub location names from Alma.
   *
   * Formats the list of branches in an array usable for form API selects.
   *
   * @return array
   *   List of sub locations, keyed by sub location id.
   */
  public function get_sublocations() {
    $sublocations = array();
    $doc = $this->request('organisation/subLocations');

    foreach ($doc->getElementsByTagName('subLocation') as $sublocation) {
      $sublocations[$sublocation->getAttribute('id')] = $sublocation->getElementsByTagName('name')->item(0)->nodeValue;
    }

    return $sublocations;
  }

  /**
   * Get collection names from Alma.
   *
   * Formats the list of branches in an array usable for form API selects.
   *
   * @return array
   *   List of collections, keyed by collection id.
   */
  public function get_collections() {
    $collections = array();
    $doc = $this->request('organisation/collections');

    foreach ($doc->getElementsByTagName('collection') as $collection) {
      $collections[$collection->getAttribute('id')] = $collection->getElementsByTagName('name')->item(0)->nodeValue;
    }

    return $collections;
  }

  /**
   * Get patron information from Alma.
   */
  public function get_patron_info($borr_card, $pin_code, $extended = FALSE) {
    $path = ($extended) ? 'patron/informationExtended' : 'patron/information';
    $info_node = ($extended) ? 'patronInformationExtended' : 'patronInformation';

    $doc = $this->request($path, array('borrCard' => $borr_card, 'pinCode' => $pin_code));

    $info = $doc->getElementsByTagName($info_node)->item(0);

    $data = array(
      'user_id' => $info->getAttribute('patronId'),
      'user_name' => $info->getAttribute('patronName'),
      'addresses' => array(),
      'mails' => array(),
      'phones' => array(),
      'category' => $info->getAttribute('patronCategory'),
    );

    foreach ($info->getElementsByTagName('address') as $address) {
      $data['addresses'][] = array(
        'id' => $address->getAttribute('id'),
        'type' => $address->getAttribute('type'),
        'active' => (bool) ($address->getAttribute('isActive') == 'yes'),
        'care_of' => $address->getAttribute('careOf'),
        'street' => $address->getAttribute('streetAddress'),
        'postal_code' => $address->getAttribute('zipCode'),
        'city' => $address->getAttribute('city'),
        'country' => $address->getAttribute('country'),
      );
    }

    foreach ($info->getElementsByTagName('emailAddress') as $mail) {
      $data['mails'][] = array(
        'id' => $mail->getAttribute('id'),
        'mail' => $mail->getAttribute('address'),
      );
    }

    foreach ($info->getElementsByTagName('phoneNumber') as $phone) {
      $data['phones'][] = array(
        'id' => $phone->getAttribute('id'),
        'phone' => $phone->getAttribute('localCode'),
        'sms' => (bool) ($phone->getElementsByTagName('sms')->item(0)->getAttribute('useForSms') == 'yes'),
      );
    }

    if ($prefs = $info->getElementsByTagName('patronPreferences')->item(0)) {
      $data['preferences'] = array(
        'patron_branch' => $prefs->getAttribute('patronBranch'),
      );
    }

    foreach ($info->getElementsByTagName('patronBlock') as $block) {
      $data['blocks'][] = array(
        'code' => $block->getAttribute('code'),
        'is_system' => (bool) ($block->getElementsByTagName('isSystemBlock') == 'yes'),
      );
    }

    foreach ($info->getElementsByTagName('absentPeriod') as $period) {
      $data['absent_periods'][] = array(
        'id' => $period->getAttribute('absentId'),
        'from_date' => $period->getAttribute('absentFromDate'),
        'to_date' => $period->getAttribute('absentToDate'),
      );
    }

    foreach ($info->getElementsByTagName('patronAllow') as $allow) {
      $data['allows'][$allow->getAttribute('allowType')] = array(
        'date' => $allow->getAttribute('allowDate'),
      );
    }

    return $data;
  }

  /**
   * Get reservation info.
   */
  public function get_reservations($borr_card, $pin_code) {
    $doc = $this->request('patron/reservations', array('borrCard' => $borr_card, 'pinCode' => $pin_code));

    $reservations = array();
    foreach ($doc->getElementsByTagName('reservation') as $item) {
      $reservation = array(
        'id' => $item->getAttribute('id'),
        'status' => $item->getAttribute('status'),
        'pickup_branch' => $item->getAttribute('reservationPickUpBranch'),
        'create_date' => $item->getAttribute('createDate'),
        'valid_from' => $item->getAttribute('validFromDate'),
        'valid_to' => $item->getAttribute('validToDate'),
        'queue_number' => $item->getAttribute('queueNo'),
        'organisation_id' => $item->getAttribute('organisationId'),
        'record_id' => $item->getElementsByTagName('catalogueRecord')->item(0)->getAttribute('id'),
        'record_available' => $item->getElementsByTagName('catalogueRecord')->item(0)->getAttribute('isAvailable'),
      );

      if ($note = $item->getElementsByTagName('note')->item(0)) {
        $reservation['notes'] = $note->getAttribute('value');
      }

      if ($reservation['status'] == 'fetchable') {
        $reservation['pickup_number'] = $item->getAttribute('pickUpNo');
        $reservation['pickup_expire_date'] = $item->getAttribute('pickUpExpireDate');
      }

      $reservations[$reservation['id']] = $reservation;
    }
    uasort($reservations, 'AlmaClient::reservation_sort');
    return $reservations;
  }

  /**
   * Helper function for sorting reservations.
   */
  private static function reservation_sort($a, $b) {
    return strcmp($a['create_date'], $b['create_date']);
  }

  /**
   * Get a list of historical loans.
   */
  public function get_historical_loans($borr_card, $from = 0) {
    $doc = $this->request('patron/loans/historical', array('borrCard' => $borr_card, 'fromDate' => date('Y-m-d', $from)));

    $loans = array();
    foreach ($doc->getElementsByTagName('catalogueRecord') as $item) {
      $loans[] = array(
        'id' => $item->getAttribute('id'),
        'loan_date' => strtotime($item->parentNode->getAttribute('loanDate'))
      );
    }

    return $loans;
  }

  /**
   * Get patron's current loans.
   */
  public function get_loans($borr_card, $pin_code) {
    $doc = $this->request('patron/loans', array('borrCard' => $borr_card, 'pinCode' => $pin_code));

    $loans = array();
    foreach ($doc->getElementsByTagName('loan') as $item) {
      $id = $item->getAttribute('id');
      $loan = array(
        'id' => $id,
        'branch' => $item->getAttribute('loanBranch'),
        'loan_date' => $item->getAttribute('loanDate'),
        'due_date' => $item->getAttribute('loanDueDate'),
        'is_renewable' => ($item->getElementsByTagName('loanIsRenewable')->item(0)->getAttribute('value') == 'yes') ? TRUE : FALSE,
        'record_id' => $item->getElementsByTagName('catalogueRecord')->item(0)->getAttribute('id'),
        'record_available' => $item->getElementsByTagName('catalogueRecord')->item(0)->getAttribute('isAvailable'),
      );
      if ($item->getElementsByTagName('note')->length > 0) {
        $loan['notes'] = $item->getElementsByTagName('note')->item(0)->getAttribute('value');
      }
      $loans[$id] = $loan;
    }
    uasort($loans, 'AlmaClient::loan_sort');
    return $loans;
  }

  /**
   * Helper function for sorting loans.
   */
  private static function loan_sort($a, $b) {
    return strcmp($a['due_date'], $b['due_date']);
  }

  /**
   * Add user consent.
   */
  public function add_user_consent($borr_card, $pin_code, $type) {
    // Initialise the query parameters with the current value from the
    // reservation array.
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'allowType' => $type,
    );

    try {
      $doc = $this->request('patron/allow/add', $params);
      $res_status = $doc->getElementsByTagName('status')->item(0)->getAttribute('value');
      // Return error code when patron is blocked.
      if ($res_status != 'ok') {
        return FALSE;
      }

      // General catchall if status is not okay is to report failure.
      if ($res_status == 'consentNotOk') {
        return FALSE;
      }
    }
    catch (AlmaClientConsentNotFound $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Remove user consent.
   */
  public function remove_user_consent($borr_card, $pin_code, $type) {
    // Initialise the query parameters with the current value from the
    // reservation array.
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'allowType' => $type,
    );

    try {
      $doc = $this->request('patron/allow/remove', $params);
      $res_status = $doc->getElementsByTagName('status')->item(0)->getAttribute('value');
      // Return error code when patron is blocked.
      if ($res_status != 'ok') {
        return FALSE;
      }

      // General catch all if status is not okay is to report failure.
      if ($res_status == 'consentNotOk') {
        return FALSE;
      }
    }
    catch (AlmaClientConsentNotFound $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get patron's debts.
   */
  public function get_debts($borr_card, $pin_code) {

    $doc = $this->request('patron/debts', array('borrCard' => $borr_card, 'pinCode' => $pin_code));

    $data = array(
      'total_formatted' => 0,
      'debts' => array(),
    );

    if ($debts_attr = $doc->getElementsByTagName('debts')->item(0)) {
      $data['total_formatted'] = $debts_attr->getAttribute('totalDebtAmountFormatted');
    }

    foreach ($doc->getElementsByTagName('debt') as $item) {
      $id = $item->getAttribute('debtId');
      $data['debts'][$id] = array(
        'id' => $id,
        'date' => $item->getAttribute('debtDate'),
        'type' => $item->getAttribute('debtType'),
        'amount' => $item->getAttribute('debtAmount'),
        'amount_formatted' => $item->getAttribute('debtAmountFormatted'),
        'note' => $item->getAttribute('debtNote'),
        'display_name' => $item->getAttribute('debtNote'),
      );
    }
    return $data;
  }

  /**
   * Add a reservation.
   */
  public function add_reservation($borr_card, $pin_code, $reservation) {
    // Initialise the query parameters with the current value from the
    // reservation array.
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'reservable' => rawurlencode($reservation['id']),
      'reservationPickUpBranch' => $reservation['pickup_branch'],
      'reservationValidFrom' => $reservation['valid_from'],
      'reservationValidTo' => $reservation['valid_to'],
    );

    // If there's not a validFrom date, set it as today.
    if (empty($params['reservationValidFrom'])) {
      $params['reservationValidFrom'] = date(ALMA_DATE, $_SERVER['REQUEST_TIME']);
    }

    // If there's not a validTo date, set it a year in the future.
    if (empty($params['reservationValidTo'])) {
      $params['reservationValidTo'] = intval(date('Y', $_SERVER['REQUEST_TIME'])) + 1 . date('-m-d', $_SERVER['REQUEST_TIME']);
    }

    try {
      $doc = $this->request('patron/reservations/add', $params);
      $res_status = $doc->getElementsByTagName('reservationStatus')->item(0)->getAttribute('value');
      $res_message = $doc->getElementsByTagName('reservationStatus')->item(0)->getAttribute('key');
      $queue_number = (int) $doc->getElementsByTagName('reservation')->item(0)->getAttribute('queueNo');

      // Return error code when patron is blocked.
      if ($res_message == 'reservationPatronBlocked') {
        return array(
          'alma_status' => ALMA_AUTH_BLOCKED,
          'res_status' => $res_status,
          'message' => $res_message
        );
      }

      // General catchall if status is not okay is to report failure.
      if ($res_status == 'reservationNotOk') {
        return array(
          'alma_status' => ALMA_AUTH_BLOCKED,
          'res_status' => $res_status,
          'message' => $res_message
        );
      }
    }
    catch (AlmaClientReservationNotFound $e) {
        return array(
          'alma_status' => ALMA_AUTH_BLOCKED,
          'res_status' => $res_status,
          'message' => $res_message
        );
    }

    return $queue_number;
  }

  /**
   * Change a reservation.
   */
  public function change_reservation($borr_card, $pin_code, $reservation, $changes) {
    // Initialise the query parameters with the current value from the
    // reservation array.
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'reservation' => $reservation['id'],
      'reservationPickUpBranch' => $reservation['pickup_branch'],
      'reservationValidFrom' => $reservation['valid_from'],
      'reservationValidTo' => $reservation['valid_to'],
    );

    // Then overwrite the values with those from the changes array.
    if (!empty($changes['pickup_branch'])) {
      $params['reservationPickUpBranch'] = $changes['pickup_branch'];
    }

    if (!empty($changes['valid_to'])) {
      $params['reservationValidTo'] = $changes['valid_to'];
    }

    $doc = $this->request('patron/reservations/change', $params);
    return TRUE;
  }

  /**
   * Remove a reservation.
   */
  public function remove_reservation($borr_card, $pin_code, $reservation) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'reservation' => $reservation['id'],
    );

    $doc = $this->request('patron/reservations/remove', $params);
    return TRUE;
  }

  /**
   * Renew a loan.
   */
  public function renew_loan($borr_card, $pin_code, $loan_ids) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'loans' => (is_array($loan_ids)) ? join(',', $loan_ids) : $loan_ids,
    );

    $doc = $this->request('patron/loans/renew', $params);

    // Built return array as specified by Ding loan provider.
    // See ding_provider_example_loan_renew_loans().
    $reservations = array();
    foreach ($doc->getElementsByTagName('loan') as $loan) {
      $id = $loan->getAttribute('id');
      if (in_array($id, $loan_ids)) {
        if ($renewable = $loan->getElementsByTagName('loanIsRenewable')->item(0)) {
          $message = $renewable->getAttribute('message');
          $renewable = $renewable->getAttribute('value');
          // If message is "isRenewedToday" we assume that the renewal is
          // successful. Even if this is not the case any error in the current
          // renewal is irrelevant as the loan has previously been renewed so
          // don't report it as such.
          if ($message == 'isRenewedToday' || $renewable == 'yes') {
            $reservations[$id] = TRUE;
          }
          // When renewalIsDenied marked as 'no' it is probably a ILL loan
          // which has been successfully requested to be renewed.
          elseif ($message == 'renewalIsDenied' && $renewable == 'no') {
            $reservations[$id] = 'requested';
          }
          elseif ($message == 'maxNofRenewals') {
            $reservations[$id] = 'maxnum';
          }
          elseif ($message == 'copyIsReserved') {
            $reservations[$id] = 'reserved';
          }
          else {
            $reservations[$id] = FALSE;
          }
        }
      }
    }

    return $reservations;
  }

  /**
   * Add phone number.
   */
  public function add_phone_number($borr_card, $pin_code, $new_number, $sms = TRUE) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'localCode' => $new_number,
      'useForSms' => ($sms) ? 'yes' : 'no',
    );
    $doc = $this->request('patron/phoneNumbers/add', $params);
    return TRUE;
  }

  /**
   * Change phone number.
   */
  public function change_phone_number($borr_card, $pin_code, $number_id, $new_number, $sms = TRUE) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'phoneNumber' => $number_id,
      'localCode' => $new_number,
      'useForSms' => ($sms) ? 'yes' : 'no',
    );
    $doc = $this->request('patron/phoneNumbers/change', $params);
    return TRUE;
  }

  /**
   * Delete phone number.
   */
  public function remove_phone_number($borr_card, $pin_code, $number_id) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'phoneNumber' => $number_id,
    );
    $doc = $this->request('patron/phoneNumbers/remove', $params);
    return TRUE;
  }

  /**
   * Add e-mail address.
   */
  public function add_email_address($borr_card, $pin_code, $new_email) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'address' => $new_email,
    );
    $doc = $this->request('patron/email/add', $params);
    return TRUE;
  }

  /**
   * Change e-mail address.
   */
  public function change_email_address($borr_card, $pin_code, $email_id, $new_email = FALSE) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'emailAddress' => $email_id,
      'address' => $new_email,
    );

    $doc = $this->request('patron/email/change', $params);
    return TRUE;
  }

  /**
   * Delete e-mail address.
   */
  public function remove_email_address($borr_card, $pin_code, $email_id) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'emailAddress' => $email_id,
    );
    $doc = $this->request('patron/email/remove', $params);
    return TRUE;
  }

  /**
   * Change PIN code.
   */
  public function change_pin($borr_card, $pin_code, $new_pin) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'pinCodeChange' => $new_pin,
    );
    $doc = $this->request('patron/pinCode/change', $params);
    return TRUE;
  }

  /**
   * Get details about one or more catalogue record.
   */
  public function catalogue_record_detail($alma_ids) {
    $params = array(
      'catalogueRecordKey' => $alma_ids,
    );
    $doc = $this->request('catalogue/detail', $params, FALSE);
    $data = array(
      'request_status' => $doc->getElementsByTagName('status')->item(0)->getAttribute('value'),
      'records' => array(),
    );

    foreach ($doc->getElementsByTagName('detailCatalogueRecord') as $elem) {
      $record = AlmaClient::process_catalogue_record_details($elem);
      $data['records'][$record['alma_id']] = $record;
    }

    return $data;
  }

  /**
   * Helper function for processing the catalogue records.
   */
  private static function process_catalogue_record_details($elem) {
    $record = array(
      'alma_id' => $elem->getAttribute('id'),
      'target_audience' => $elem->getAttribute('targetAudience'),
      'show_reservation_button' => ($elem->getAttribute('showReservationButton') == 'yes') ? TRUE : FALSE,
      'reservation_count' => $elem->getAttribute('nofReservations'),
      'loan_count_year' => $elem->getAttribute('nofLoansYear'),
      'loan_count_total' => $elem->getAttribute('nofLoansTotal'),
      'available_count' => $elem->getAttribute('nofAvailableForLoan'),
      'title_series' => $elem->getAttribute('titleSeries'),
      'title_original' => $elem->getAttribute('titleOriginal'),
      'resource_type' => $elem->getAttribute('resourceType'),
      'publication_year' => $elem->getAttribute('publicationYear'),
      'media_class' => $elem->getAttribute('mediaClass'),
      'extent' => $elem->getAttribute('extent'),
      'edition' => $elem->getAttribute('edition'),
      'category' => $elem->getAttribute('category'),
    );

    foreach ($elem->getElementsByTagName('author') as $item) {
      $record['authors'][] = $item->getAttribute('value');
    }

    foreach ($elem->getElementsByTagName('description') as $item) {
      $record['descriptions'][] = $item->getAttribute('value');
    }

    foreach ($elem->getElementsByTagName('isbn') as $item) {
      $record['isbns'][] = $item->getAttribute('value');
    }

    foreach ($elem->getElementsByTagName('language') as $item) {
      $record['languages'][] = $item->getAttribute('value');
    }

    foreach ($elem->getElementsByTagName('note') as $item) {
      $record['notes'][] = $item->getAttribute('value');
    }

    foreach ($elem->getElementsByTagName('title') as $item) {
      $record['titles'][] = $item->getAttribute('value');
    }

    if ($record['media_class'] != 'periodical') {
      $record['holdings'] = AlmaClient::process_catalogue_record_holdings($elem);
    }
    else {
      // Periodicals are nested holdings, which we want to keep that way.
      foreach ($elem->getElementsByTagName('compositeHoldings') as $holdings) {
        foreach ($holdings->childNodes as $year_holdings) {
          $year = $year_holdings->getAttribute('value');
          foreach ($year_holdings->childNodes as $issue_holdings) {
            $issue = $issue_holdings->getAttribute('value');

            // If this is a yearly publication it do not have issues.
            if (empty($issue)) {
              // So we set the issue to index as a single element.
              $issue = 0;
            }

            $holdings = AlmaClient::process_catalogue_record_holdings($issue_holdings);
            $record['holdings'][$year][$issue] = $holdings;
            $issue_list = array(
              'available_count' => 0,
              'branches' => array(),
              'reservable' => $holdings[0]['reservable'],
            );

            // Also create an array with the totals for each issue.
            foreach ($holdings as $holding) {
              if ($holding['available_count'] > 0) {
                $issue_list['available_count'] += (int) $holding['available_count'];
                if (isset($issue_list['branches'][$holding['branch_id']])) {
                  $issue_list['branches'][$holding['branch_id']] += (int) $holding['available_count'];
                }
                else {
                  $issue_list['branches'][$holding['branch_id']] = (int) $holding['available_count'];
                }
              }
            }
            $record['issues'][$year][$issue] = $issue_list;
          }
        }
      }
    }

    return $record;
  }

  /**
   * Helper function for processing the catalogue record holdings.
   */
  private static function process_catalogue_record_holdings($elem) {
    $holdings = array();

    foreach ($elem->getElementsByTagName('holding') as $item) {
      $holdings[] = array(
        'reservable' => $item->getAttribute('reservable'),
        'status' => $item->getAttribute('status'),
        'show_reservation_button' => $item->getAttribute('showReservationButton'),
        'ordered_count' => (int) $item->getAttribute('nofOrdered'),
        'checked_out_count' => (int) $item->getAttribute('nofCheckedOut'),
        'reference_count' => (int) $item->getAttribute('nofReference'),
        'total_count' => (int) $item->getAttribute('nofTotal'),
        'collection_id' => $item->getAttribute('collectionId'),
        'sublocation_id' => $item->getAttribute('subLocationId'),
        'location_id' => $item->getAttribute('locationId'),
        'department_id' => $item->getAttribute('departmentId'),
        'branch_id' => $item->getAttribute('branchId'),
        'organisation_id' => $item->getAttribute('organisationId'),
        'available_count' => (int) $item->getAttribute('nofAvailableForLoan'),
        'shelf_mark' => $item->getAttribute('shelfMark'),
        'available_from' => $item->getAttribute('firstLoanDueDate'),
      );
    }

    return $holdings;
  }

  /**
   * Get availability data for one or more records.
   */
  public function get_availability($alma_ids) {
    $data = array();
    $doc = $this->request('catalogue/availability', array('catalogueRecordKey' => $alma_ids));
    foreach ($doc->getElementsByTagName('catalogueRecord') as $record) {
      $data[$record->getAttribute('id')] = array(
        'reservable' => ($record->getAttribute('isReservable') == 'true') ? TRUE : FALSE,
        'available' => ($record->getAttribute('isAvailable') == 'yes') ? TRUE : FALSE,
      );
    }
    return $data;
  }

  /**
   * Pay debts.
   */
  public function add_payment($debt_ids, $order_id = NULL) {
    $params = array('debts' => $debt_ids);

    if (!empty($order_id)) {
      $params['orderId'] = $order_id;
    }

    $doc = $this->request('patron/payments/add', $params);
    return TRUE;
  }

  /**
   * Change user???s preferred branch.
   *
   * @param string $borr_card
   *   Library patron's borrowing card number. Either just an arbitrary
   *   number printed on their library card or their CPR-code.
   * @param string $pin_code
   *   Library patron's current four digit PIN code.
   * @param string $branch_code
   *   New preferred branch.
   */
  public function change_patron_preferences($borr_card, $pin_code, $branch_code) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'patronBranch' => $branch_code,
    );

    $doc = $this->request('patron/preferences/change', $params);
    return TRUE;
  }

  /**
   * Add an Alma absent period.
   *
   * @param string $borr_card
   *   Library patron's borrowing card number. Either just an arbitrary
   *   number printed on their library card or their CPR-code.
   * @param string $pin_code
   *   Library patron's current four digit PIN code.
   * @param string $from_date
   *   Absent period start date.
   * @param string $to_date
   *   Absent period start date.
   */
  public function add_absent_period($borr_card, $pin_code, $from_date, $to_date) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'absentFromDate' => $from_date,
      'absentToDate' => $to_date,
    );

    $doc = $this->request('patron/absentPeriod/add', $params);
    return TRUE;
  }

  /**
   * Change existing absent period.
   *
   * @param string $borr_card
   *   Library patron's borrowing card number. Either just an arbitrary
   *   number printed on their library card or their CPR-code.
   * @param string $pin_code
   *   Library patron's current four digit PIN code.
   * @param string $absent_id
   *   ID for existing period.
   * @param string $from_date
   *   Absent period start date.
   * @param string $to_date
   *   Absent period start date.
   */
  public function change_absent_period($borr_card, $pin_code, $absent_id, $from_date, $to_date) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'absentId' => $absent_id,
      'absentFrom' => date_format(date_create($from_date), ALMA_DATE),
      'absentTo' => date_format(date_create($to_date), ALMA_DATE),
    );

    $doc = $this->request('patron/absent/change', $params);
    return TRUE;
  }

  /**
   * Remove existing absent period.
   *
   * @param string $borr_card
   *   Library patron's borrowing card number. Either just an arbitrary
   *   number printed on their library card or their CPR-code.
   * @param string $pin_code
   *   Library patron's current four digit PIN code.
   * @param string $absent_id
   *   ID for existing period.
   */
  public function remove_absent_period($borr_card, $pin_code, $absent_id) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'absentId' => $absent_id,
    );

    $doc = $this->request('patron/absentPeriod/remove', $params);
    return TRUE;
  }

  /**
   * Add a messaging service.
   *
   * @param string $borr_card
   *   Library patron's borrowing card number. Either just an arbitrary
   *   number printed on their library card or their CPR-code.
   * @param string $pin_code
   *   Library patron's current four digit PIN code.
   * @param $method
   *   The method for sending messages e.g. SMS
   * @param $type
   *   The message type e.g. due date alerts
   */
  public function add_message_service($borr_card, $pin_code, $method, $type) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'sendMethod' => $method,
      'serviceType' => $type
    );

    $doc = $this->request('patron/messageServices/add', $params);
    return TRUE;
  }

  /**
   * Removes a messaging service.
   *
   * @param string $borr_card
   *   Library patron's borrowing card number. Either just an arbitrary
   *   number printed on their library card or their CPR-code.
   * @param string $pin_code
   *   Library patron's current four digit PIN code.
   * @param $method
   *   The method for sending messages e.g. SMS
   * @param $type
   *   The message type e.g. due date alerts
   */
  public function remove_message_service($borr_card, $pin_code, $method, $type) {
    $params = array(
      'borrCard' => $borr_card,
      'pinCode' => $pin_code,
      'sendMethod' => $method,
      'serviceType' => $type
    );

    $doc = $this->request('patron/messageServices/remove', $params);
    return TRUE;
  }

  /**
   * Create new user at alma.
   *
   * @param $cpr
   *   The users CPR number.
   * @param $pin_code
   *   The users pin-code.
   * @param $name
   *   The users full name.
   * @param $mail
   *   The users e-mail address.
   * @param $branch
   *   The users preferred pick-up branch.
   *
   * @return bool
   *   Always returns TRUE. If any errors happens exception is thrown in the
   *   request function.
   */
  public function self_register($cpr, $pin_code, $name, $mail, $branch) {
    $params = array(
      'securityNumber' => $cpr,
      'borrCard' => $cpr,
      'pin' => $pin_code,
      'name' => $name,
      'email' => $mail,
      'branch' => $branch,
      'addr1' => '+++',
      // Verified has to be set to the string value true
      // for this to work. Booleans are converted to integers
      // and they are no good.
      'verified' => 'true',
      'locale' => 'da_DK'
    );

    $this->request('patron/selfReg', $params);
    return TRUE;
  }

}

/**
 * Define exceptions for different error conditions inside the Alma client.
 */

class AlmaClientInvalidURLError extends Exception { }

class AlmaClientHTTPError extends Exception { }

class AlmaClientCommunicationError extends Exception { }

class AlmaClientInvalidPatronError extends Exception { }

class AlmaClientUserAlreadyExistsError extends Exception { }

class AlmaClientBorrCardNotFound extends Exception { }

class AlmaClientReservationNotFound extends Exception { }
