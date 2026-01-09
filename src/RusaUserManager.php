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
    $this->users = \Drupal::entityTypeManager()->getStorage('user');
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
    $user = $this->users->load($uid);
    $mid = $user->get('field_rusa_member_id')->getValue()[0]['value'];
    $email = $user->getEmail();
    $mdata = $this->members->getMember($mid);

    // Skip if we're using Plus addressing
    if (!strpos($email, '+')) {
      // Check to see if email is different
      if ($email !== $mdata->email) {
        // Update email address
        $user->setEmail($mdata->email);
        $user->set('field_date_of_birth', str_replace('/', '-', $mdata->birthdate));
        $user->save();
        $this->logger->notice('Updated email for %user', ['%user' => $uid]);
      }
    }
    // Set membership expiration date
    $this->logger->notice('Setting expiration date for %user to %edate', ['%user' => $uid, '%edate' => $mdata->expdate]);
    $user->set('field_member_expiration_date', str_replace('/', '-', $mdata->expdate));


    // Code added by Man-Fai to sync users from a file
    $syncMe = $this->getSyncList();
    if (in_array($mid, $syncMe, FALSE)) {
      $this->logger->notice('Performing detailed sync for Rusa#  %user', ['%user' => $mid]);
      $user->set('field_first_name', $mdata->fname);
      $user->set('field_last_name', $mdata->sname);
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

  protected function getSyncList() {
    $syncList = [];
    $sync_file = "/tmp/sync_members.csv";
    if (file_exists($sync_file)) {
      if (($fh = fopen($sync_file, "r")) != FALSE) {
        while (($data = fgetcsv($fh, 1000, ',')) !== FALSE) {
          $syncList = array_merge($syncList, $data);
        }
      }
    }
    return $syncList;
  }

}   // End of class 


