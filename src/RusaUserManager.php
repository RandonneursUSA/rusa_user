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

namespace drupal\rusa_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
        if (! strpos($email, '+')) {
            // Check to see if email is different
            if ($email !== $mdata->email) {
                // Update email address
                $user->setEmail($mdata->email);
                $user->save();
                $this->logger->notice('Updated email for %user', ['%user' => $uid]);
            }
        }
        // Set membership expiration date
        $this->logger->notice('Setting expiration date for %user to %edate', ['%user' => $uid, '%edate' => $mdata->expdate]);
        $user->set('field_member_expiration_date', str_replace('/', '-', $mdata->expdate));
        $user->save();
    }
    
}    
            

