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
     * For now we'll just sync the email
     *
     * @todo add or remove roles based on volunteer data
     */
    public function syncMemberData() {
        $this->logger->notice('Entered syncMemberData');
        $query = $this->users->getQuery();
        $uids = $query
            ->condition('status', '1')
            ->condition('roles', 'rusa_member')
            ->execute();
        
        // Step though each user
        foreach ($uids as $uid) {
            $user   = $this->users->load($uid);
            $mid    = $user->get('field_rusa_member_id')->value();
            $email  = $user->getEmail();
            $mdata  = $this->members->getMember($mid);
            
            // Check to see if email is different
            if ($email !== $mdata->email) {
                // Update email address
                $user->setEmail($mdata->email);
                $user->save();
                $this->logger->notice('Updated email for %user', [%user => $uid]);
            }
        }
    }
            

