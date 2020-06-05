<?php

namespace Drupal\rusa_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\rusa_api\RusaMembers;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RusaUserForm extends FormBase {

  protected $memobj; // API Member object


  /**
   * Required
   *
   */
  public function getFormId() {
    return 'rusa_user_form';
  }

  /**
   * {@inheritdoc}
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build the form
    $form['info'] = [
      '#type'    => 'item',
      '#markup'  => $this->t("Use this form to create your rusa.org user account.<br /> "  .
                             "Your user account will be used to authenticate you form many RUSA forms, <br />" .
                             "as well as letting you maintain your RUSA profile. <br />" .
                             "Enter your RUSA # and click submit to create your user account."),
    ];

    $form['mid'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('RUSA #'),
    ];

    $form['actions'] = [
      'cancel'  => [
      '#type'  => 'submit',
      '#value' => 'Cancel',
      ],
      'submit' => [
        '#type'  => 'submit',
      '#value' => 'Submit',
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // See if we have a value
    $mid = $form_state->getValue('mid');
    if (!empty($mid)) {
      // Check to see if user with this ID already exists
      if ($this->checkExisting($mid)) {
        $form_state->setErrorByName('mid', "A user with RUSA # " . $mid . " already exists.");
      }

      $this->memobj  = new RusaMembers(['key' => 'mid', 'val' => $mid]); 
      $this->memobj->addTitles();

      // Check for valid ID
      if (! $this->memobj->isValid($mid)) {
        $form_state->setErrorByName('mid', $mid . " does not appear to be a valid RUSA #.");
      }
      // Check for expired user
      if ($this->memobj->isExpired($mid)) {
        $form_state->setErrorByName('mid', "Membership for RUSA # " . $mid . " is expired.");
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getTriggeringElement();
    if ($action['#value'] == "Cancel") {
      $form_state->setRedirect('rusa.home');
    }
    else {
      // Add the user 
      $mid    = $form_state->getValue('mid');
      $member = $this->memobj->getMember($mid);
      $url    = $this->addUser($member);

      $messenger = \Drupal::messenger();
      $messenger->addMessage(t("An email was sent to " . $member->email . " with a one time link to set your new password. " .   
      "<br />" . $url), $messenger::TYPE_STATUS);

    }
  }

  private function checkExisting($mid) {

    // Check to see if use with this RUSA ID already exists
    $uids = \Drupal::entityQuery('user')
      ->condition("field_rusa_member_id", $mid, "=")
      ->execute();
    return $uids;
  }

  private function addUser($udata) {
    // Create the user
    $user = User::create();

    //Required settings
    $user->setPassword('ReallyB0gusPa$$W0rd');
    $user->enforceIsNew();
    $user->setEmail($udata->email);
    $user->setUsername($udata->fname . " " . $udata->sname );

    // Custom fields
    $user->set('field_first_name',     $udata->fname);
    $user->set('field_last_name',      $udata->sname);
    $user->set('field_rusa_member_id', $udata->mid);

    // Set roles
    $user->addRole('rusa_member');
    if (in_array("Regional Brevet Administrator", $udata->titles)){
      $user->addRole('rusa_rba');
    }
    /*
    if (in_array("Permanent Route Owner", $udata->titles)){
      $user->addRole('rusa_perm_owner');
    }
    */

    $user->activate();
    $user->save();

    // Get a one time password reset link
    return user_pass_reset_url($user);

  }

} //EoC
