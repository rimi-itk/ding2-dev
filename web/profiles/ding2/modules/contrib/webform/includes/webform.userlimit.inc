<?php

/**
 * @file
 * Classes for flood protection.
 */

/**
 * Register and check flood events for webform submissions.
 */
class WebformFloodEvent {

  const PREFIX = 'webform_submission:';

  protected $event;
  protected $threshold;
  protected $window;

  /**
   * Create new instance based on a webform node.
   *
   * Extracts all necessary config variables form the node object.
   *
   * @param object $node
   *   The webform node.
   */
  public static function fromNode($node) {
    $event = static::PREFIX . $node->nid;
    $window = $node->webform['submit_interval'];
    if ($window == -1) {
      // 10 years should be close enough to infinity.
      $window = 10 * 365 * 24 * 3600;
    }
    return new static($event, $node->webform['submit_limit'], $window);
  }

  /**
   * Create a new instance.
   */
  public function __construct($event, $threshold, $window) {
    $this->event = $event;
    $this->threshold = $threshold;
    $this->window = $window;
  }

  /**
   * Get the user identifier used for flood protection.
   *
   * @param object $user
   *   The currently active Drupal user.
   *
   * @return string
   *   The user identifier used for flood protection.
   */
  protected function identifier($user) {
    return $user->uid ? 'uid:' . $user->uid : NULL;
  }

  /**
   * Register a flood event.
   *
   * @param object $user
   *   The currently active Drupal user.
   */
  public function register($user) {
    flood_register_event($this->event, $this->window, $this->identifier($user));
  }

  /**
   * Check whether submitting a new submission should be allowed.
   *
   * @param object $user
   *   The currently active Drupal user.
   *
   * @return bool
   *   FALSE when this operation exceeds the threshold.
   */
  public function isAllowed($user) {
    return flood_is_allowed($this->event, $this->threshold, $this->window, $this->identifier($user));
  }

}
