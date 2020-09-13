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

class RusaUserManager {
    protected $users;
    protected $members;
    
    public function __construct() {
        $this->users   = \Drupal::entityTypeManager()->getStorage('user');
        $this->members = new RusaMembers(); // This will load all members. Maybe too slow.
    }
    
    /**
     * Sync data from the members database
     *
     * For now we'll just sync the email
     *
     * @todo add or remove roles based on volunteer data
     */
    public function syncMemberData() {
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
            }
        }
    }
            

