<?php
/**
 * @file 
 *
 * Provides a controller class for the rusa user module
 *
 */


namespace Drupal\rusa_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Rusa User Controller
 *
 */
class RusaUserController  extends ControllerBase {

    protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $currentUser) {
      $this->currentUser = $currentUser;
  } 

	/**
     * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
          $container->get('current_user'),
		);
	 }

	 /**
	  * Provide current user uid and mid via a JSON response
	  * This doesn't do what I had intended and should be repurposed or deleted
	  */
	 public function getCurrentUser() {
        $uid  = $this->currentUser->id();
        $user = User::load($uid);
        $mid  = $user->get('field_rusa_member_id')->getValue()[0]['value'];

        return new JsonResponse([
            'data' => ['uid' => $uid, 'mid' => $mid],
            'method' => 'GET',
        ]);
	}

} //EoC
