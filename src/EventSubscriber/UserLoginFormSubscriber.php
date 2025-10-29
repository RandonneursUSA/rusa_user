<?php
/**
 * @file UserLoginFormSubscriber.php
 *
 * @Created 
 *  2025-10-28 - Paul Lieberman
 *
 * Subscribe to login events
 *
 * ----------------------------------------------------------------------------------------
 */
  
// src/EventSubscriber/
namespace Drupal\rusa_user\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Form\FormEventManagerInterface;
use Drupal\Core\EventSubscriber\FormEventSubscriberBase;
use Drupal\rusa_user\RusaUserManager;

/**
 * Subscribes to the user login form event.
 */
class UserLoginFormSubscriber implements EventSubscriberInterface {


  /**
   * {@inheritdoc}
   */
  public function __construct(protected RusaUserManager $rusaUserService){}
  
  /**
   * {@inheritdoc}
   * NOTE: This method is required by EventSubscriberInterface.
   */
  public static function getSubscribedEvents() {
    return [
      // The event name for all form builds.
      'kernel.request' => ['onKernelRequest', 0], // Example to show the structure
      'user_login_form' => 'onFormBuild', // Use the Form ID as the event name
    ];
  }
  
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableFormEvents() {
    // Subscribe to the form build event for the user login form.
    return [
      'user_login_form' => FormEventManagerInterface::ON_BUILD,
    ];
  }

  /**
   * Adds the custom submit handler to the form.
   *
   * @param array $form
   * An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * The current state of the form.
   */
  public function onFormBuild(array &$form, FormStateInterface $form_state) {
    // Prepend your custom submit handler to the existing ones.
    // NOTE: This uses the service instance's method as a callable array.
    
    // Custom Validate
    array_unshift($form['actions']['submit']['#submit'], [
      $this->rusaUserService, 'userLoginFormValidate'
    ]);
    
    // Custom submit handler
    array_unshift($form['actions']['submit']['#submit'], [
      $this->rusaUserService, 'userLoginFormSubmit'
    ]);
  }
}