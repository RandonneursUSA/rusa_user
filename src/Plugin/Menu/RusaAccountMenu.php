<?php
/**
 * @file 
 *  RusaAccountMenu.php
 *
 * Author: Paul Lieberman
 * Created: 2020-07-16
 *
 * Changes the Log in link to Member Log in 
 *
 */
 

namespace Drupal\rusa_user\Plugin\Menu;

use Drupal\user\Plugin\Menu\LoginLogoutMenuLink;

class RusaAccountMenu extends LoginLogoutMenuLink {

  public function getTitle() {
    if ($this->currentUser->isAuthenticated()) {
      return $this->t('Log out');
    }
    else {
      return $this->t('Member Log in');
    }
  }

}