<?php
/**
 * @file RusaUserManager.php
 *
 * @Created 
 *  2020-09-12 - Paul Lieberman
 *
 * Provide some services for managing member data
 *
 * ----------------------------------------------------------------------------------------
 */

namespace Drupal\rusa_user;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\rusa_api\RusaMembers;
use Psr\Log\LoggerInterface;


class RusaUserManager {
    protected $users;
    protected $members;
    protected $logger;
    
    public function __construct(LoggerInterface $logger) {
        $this->users   = \Drupal::entityTypeManager()->getStorage('user');
        $this->members = new RusaMembers(); // This will load all members. Maybe too slow.
        $this->logger = $logger;
    }
    
    
    /*
 	 * Provide a custom form validation for the login form
 	 * Check that the user's RUSA membership is not expired
 	 *
 	 */
	public static function userLoginFormValidate(&$form, FormStateInterface $form_state) {
		$name = $form_state->getValue('name');
		$account = user_load_by_name($name); 
 
		if (!empty($account)) {

			// Get member id from account
			$mid    = $account->get("field_rusa_member_id")->getString();		
			$memobj = new RusaMembers(['key' => 'mid', 'val' => $mid]);
			
			// If membersip is expired set an error on the login form
			if ($memobj->isExpired($mid)) {
				// Temporary disble membership requirement for the first week of the year
				// Maybe add a date check here next year, but for now just modify the code
				$mdata = $memobj->getMember($mid);
				$expdate = $mdata->expdate;
				\Drupal::Messenger()->addWarning(t('Your RUSA membership expired on %exp. Please renew your membership.', ['%exp' => $expdate]));			
			}
			// Check to see if membership will expire soon
			else {
				$mdata = $memobj->getMember($mid);
				$expdate = $mdata->expdate;
				if (strtotime($expdate) < strtotime("+2 month") ) {
					\Drupal::Messenger()->addWarning(t('Your RUSA membership will expire on %exp', ['%exp' => $expdate]));
				}
			}
	   
		}
	}

 
/**
 * Custom submit handler for login form.
 */
function userLoginFormSubmit($form, FormStateInterface $form_state) {
    
 	//Valid post-login redirect paths.
	$redirect_paths = [
		"configure_region" => "rusa_user.perl.configure_region",
		"assign_routes"    => "rusa_user.perl.assign_routes",
		"submit_calendar"  => "rusa_user.perl.submit_calendar",
		"submit_results"   => "rusa_user.perl.submit_results",
	];

    // Set redirect according to URL parameter.    
    $redirect_token = \Drupal::request()->query->get('r');
    if (array_key_exists($redirect_token, $redirect_paths)) {
        $form_state->setRedirect($redirect_paths[$redirect_token]);
    }
}

    
    
    /**
     * Sync data from the members database
     *
     * For now we'll just sync the email and expiration date
     *
     * @todo add or remove roles based on volunteer data
     */
    public function syncMemberData($uid = NULL) {
        if (empty($uid)) {
            $this->logger->notice('Entered syncMemberData');
            $query = $this->users->getQuery();
            $uids = $query
                ->condition('status', '1')
                ->condition('roles', 'rusa_member')
                ->accessCheck(TRUE)
                ->execute();
        
            // Step though each user
            foreach ($uids as $uid) {
                $this->syncData($uid);
            }
        }
        else {
            $this->syncData($uid);           
        }
    }
       
         
    /**
     * Do the actual sync here
     *
     */
    protected function syncData($uid) {
        $user   = $this->users->load($uid);
        $mid    = $user->get('field_rusa_member_id')->getValue()[0]['value'];
        $email  = $user->getEmail();
        $mdata  = $this->members->getMember($mid);
        
        // Skip if we're using Plus addressing
        $plus = "+";
        if (str_contains($email, $plus)) {
            // Check to see if email is different
            if ($email !== $mdata->email) {
                // Update email address
                $user->setEmail($mdata->email);
                $user->set('field_date_of_birth',  str_replace('/', '-', $mdata->birthdate));
                $user->save();
                $this->logger->notice('Updated email for %user', ['%user' => $uid]);
            }
        }
        // Set membership expiration date
        $this->logger->notice('Setting expiration date for %user to %edate', ['%user' => $uid, '%edate' => $mdata->expdate]);
        $user->set('field_member_expiration_date', str_replace('/', '-', $mdata->expdate));
       
       
        // Code added by Man-Fai to sync users from a file
        $syncMe = $this->getSyncList();
        if(in_array($mid, $syncMe, false)){
            $this->logger->notice('Performing detailed sync for Rusa#  %user', ['%user' => $mid]);
            $user->set('field_first_name',     $mdata->fname);
            $user->set('field_last_name',      $mdata->sname);
	        $fname = rtrim($mdata->fname);
	        $lname = rtrim($mdata->sname);
            $display_name = $fname . " " . $lname;
	        $user->set('field_display_name', $display_name);
	        $user->setUsername($fname . " " . $lname);
	        $tie = 1;
            //do {
                //    $violations = $user->validate();
                //    foreach ($violations as $violation) {
                //        switch ($violation->getPropertyPath()) {
                //            case 'name':
                //                // Use middle name if it exists
                //                $mname = empty($udata->mname) ? $tie++ : rtrim($mdata->mname);
                //                $user->setUsername($fname . " " . $mname . " " . $sname);
                //                break;
                //        }
                //}
                //} while ($violations->count() > 0);
            }

        $user->save();
    }
    
    protected function getSyncList(){
        $syncList = [];
	    $sync_file = "/tmp/sync_members.csv";
	    if(file_exists($sync_file)){
            if (($fh = fopen($sync_file, "r")) != FALSE){
                 while(($data = fgetcsv($fh, 1000, ',')) !== FALSE){
                     $syncList = array_merge($syncList, $data);
                 }
	        }
	    }
        return $syncList;
    }
    
}   // End of class 
            

