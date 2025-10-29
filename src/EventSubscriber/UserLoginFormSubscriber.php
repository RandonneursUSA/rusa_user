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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Drupal\rusa_user\RusaUserManager;

/**
 * Subscribes to the user login form event.
 */
class UserLoginFormSubscriber implements EventSubscriberInterface {


  /**
   * {@inheritdoc}
   */
  public function __construct(protected RusaUserManager $rusaUserService, protected LoggerInterface $logger){
  	$this->logger->notice("Instantiated event subscriber");  
  }
  
  /**
   * {@inheritdoc}
   * NOTE: This method is required by EventSubscriberInterface.
   */
  public static function getSubscribedEvents() {
    return [
      // The event name for all form builds.
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
    // Prepend our custom handlers to the existing ones.

   $this->logger->notice("Entered onFormBuild");  

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