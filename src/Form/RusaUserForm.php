<?php
/**
 * @file
 *
 * Provides a form for a RUSA member to generate a Drupal account
 *
 */

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
    protected $settings; // From our config form

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
    public function __construct() {
        // Just get our settings
        $this->settings = \Drupal::config('rusa_user.settings')->getRawData();
        
    }   


    /**
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state) {       
 
        // Build the form
        $form['info'] = [
            '#type'    => 'item',
            '#markup'  => $this->t($this->settings['instructions']),
        ];

        $form['mid'] = [
            '#type'   => 'textfield',
            '#title'  => $this->t('RUSA #'),
            '#size'   => '20',
        ];

         $form['actions'] = [
            '#type'   => 'actions',
                'cancel'  => [
                    '#type'  => 'submit',
                    '#value' => 'Cancel',
                    '#attributes' => ['onclick' => 'if(!confirm("Do you really want to cancel?")){return false;}'],
                ],
                'submit' => [
                    '#type'  => 'submit',
                    '#value' => 'Submit',
                ],
        ];
    
        $form['#attached']['library'][] = 'rusa_api/rusa_style';
    
    
    
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

            // Get volunteer titles from the RUSA database
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
            $email  = $member->email;
            $url    = $this->addUser($member);
            

            $to = 'rusa'; // $email Will be member's email
            
            $params = [ 
                'from'    => $this->settings['acctmgr'],  
                'message' => $this->settings['notify'],
                'url'     => $url,
                'subj'    => 'Login link for rusa.org',
            ];

            $mail = \Drupal::service('plugin.manager.mail');
            $result = $mail->mail('rusa_user', 'notify', $to, 'en', $params, $reply = NULL, $send = TRUE);

            // Status message
            $message = $this->settings['message'];
            
            // Eventually use the token service with a custom token handler but for now
            $message = str_replace('[rusa:mid]', $mid, $message);
            
            $this->messenger()->addStatus($message);            
            $form_state->setRedirect('rusa.home');
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
        $user->set('field_date_of_birth',  str_replace('/', '-', $udata->birthdate));

        // Set roles
        $user->addRole('rusa_member');
        if (in_array("Regional Brevet Administrator", $udata->titles)){
            $user->addRole('rusa_rba');
        }

        $user->activate();
        $user->save();

        // Get a one time password reset link
        return user_pass_reset_url($user);

    }

} //EoC
