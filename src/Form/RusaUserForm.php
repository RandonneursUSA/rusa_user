<?php
/**
 * @file
 *
 * Provides a form for a RUSA member to generate a Drupal account
 *
 */

namespace Drupal\rusa_user\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\rusa_api\RusaMembers;

class RusaUserForm extends ConfirmFormBase {

    protected $member;   // API Member object
    protected $settings; // From our config form
    protected $step = 1; // Keep track of multistep form
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
    */
    public function getCancelUrl() {
        return new Url('user.register');
    }

    /**
    * {@inheritdoc}
    */
    public function getQuestion() {
        $name = $this->member->fname . ' ' . $this->member->sname;
        $mid  = $this->member->mid;

        return $this->t("Are you %name with RUSA # %mid?", [
                '%name' => $name,
                '%mid'  => $mid,
            ]);
    }
    
    /**
    * {@inheritdoc}
    */
    public function getDescription() {
        return $this->t("Please confirm your identity.");
    }
    
    
    /**
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state) {       
    
        // If we're at the confirmation step just pass it on
        if ($this->step === 2) {
            // but first attach our CSS to hide the local task tabs
            $form['#attached']['library'][] = 'rusa_user/rusa_user_style';
            return parent::buildForm($form, $form_state);
        }
        
        // Build the form
        $form['info'] = [
            '#type'    => 'item',
            '#markup'  => $this->t($this->settings['instructions']),
        ];

        $form['mid'] = [
            '#type'     => 'textfield',
            '#title'    => $this->t('RUSA #'),
            '#size'     => '20',
            '#required' => TRUE,
        ];

         $form['actions'] = [
            '#type'   => 'actions',                
                'submit' => [
                    '#type'  => 'submit',
                    '#value' => 'Submit',
                ],
        ];
    
        $form['#attached']['library'][] = 'rusa_api/rusa_style';
    
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

        // The confirmation step needs no additional validation.
        if ($this->step === 2) {
            return;
        }


        // See if we have a value
        $mid = $form_state->getValue('mid');
        if (!empty($mid)) {
        
            // Get member and titles from the RUSA database
            $memobj  = new RusaMembers(['key' => 'mid', 'val' => $mid]); 
            $memobj->addTitles();
        
            // Check to see if user with this ID already exists
            if ($this->checkExisting($mid)) {
                $form_state->setErrorByName('mid', "A user with RUSA # " . $mid . " already exists.");
            }

            // Check for valid ID
            if (! $memobj->isValid($mid)) {
                $form_state->setErrorByName('mid', $mid . " does not appear to be a valid RUSA #.");
            }
            
            // Check for expired user
            if ($memobj->isExpired($mid)) {
                $form_state->setErrorByName('mid', "Membership for RUSA # " . $mid . " is expired.");
            }
            
            // Make sure email exists
            if (! $memobj->hasEmail($mid)) {
                  $form_state->setErrorByName('mid', "Member RUSA # " . $mid . " does not have an email address. Please update your member data with a valid email address.");
            }
          
            // Member data is good so save it
            $this->member = $memobj->getMember($mid);
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
   
        // Don't submit the form until after confirmation
        if ($this->step === 1) {
            $form_state->setRebuild();
            $this->step = 2;
            return;
        }
              
        // Add the user 
        $email = $this->member->email;
        $url   = $this->addUser();
        

        // Send email notification
        $to = $email;
        
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
        $message = str_replace('[rusa:mid]', $this->member->mid, $message);
        
        $this->messenger()->addStatus($message);            
        $form_state->setRedirect('rusa.home');
    }

    private function checkExisting($mid) {

        // Check to see if use with this RUSA ID already exists
        $uids = \Drupal::entityQuery('user')
            ->condition("field_rusa_member_id", $mid, "=")
            ->accessCheck(TRUE)
            ->execute();
        return $uids;
    }

    private function addUser() {
        
        $udata = $this->member;
        
        // Drupal will croak if the username has 2 spaces
        // So we have to trim trialing spaces from the names
        $udata->fname = rtrim($udata->fname);
        $udata->sname = rtrim($udata->sname);
        
        // Concatenate first and last for username
        $uname = $udata->fname . " " . $udata->sname;
        
        // Create the user
        $user = User::create();

        // Required settings
        $user->setPassword('ReallyB0gusPa$$W0rd');
        $user->enforceIsNew();
        $user->setEmail($udata->email);
        $user->setUsername($uname);
        
        // Validate username and email
        $tie = 1;
        do {
            $violations = $user->validate();
            foreach ($violations as $violation) {
                switch ($violation->getPropertyPath()) {
                    case 'name':
                        // Use middle name if it exists
                        $mname = empty($udata->mname) ? $tie++ : $udata->mname;
                        $user->setUsername($udata->fname . " " . $mname . " " . $udata->sname);
                        break;

                    case 'mail':
                        // Use Plus addressing
                        $mailparts = explode('@', $udata->email);
                        $user->setEmail($mailparts[0] . '+' . $udata->mid . '@' . $mailparts[1] );
                        break;
                }
            }
        } while ($violations->count() > 0);

        // Custom fields
        $user->set('field_first_name',     $udata->fname);
        $user->set('field_last_name',      $udata->sname);
        $user->set('field_rusa_member_id', $udata->mid);
        $user->set('field_date_of_birth',  str_replace('/', '-', $udata->birthdate));

        // Set club affiliation
        $club = $this->getClub($udata->clubacp);
        $user->set('field_club_affiliation', $club);

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
    
    /**
     *
     * Get a reference to a club
     *
     */
     protected function getClub($acpcode) {
     
        $storage = \Drupal::service('entity_type.manager')->getStorage('node');
        $query   = $storage->getQuery()
        	->accessCheck(TRUE)
            ->condition('type', 'club')
            ->condition('field_club_acpcode', $acpcode);
        $result  = $query->execute();
     
        if ($result) {
            return $result;
        }
     }

} //EoC
