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
use Psr\Log\LoggerInterface;

/**
 * Rusa User Controller
 *
 */
class RusaUserController  extends ControllerBase {

    protected $currentUser;
    protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $currentUser, 
                              LoggerInterface $logger) {
      $this->currentUser = $currentUser;
      $this->logger = $logger;
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
	
	/**
	 * Sync member data from GDBM for 1 user
	 * Designed to be run from Perl when member data is updated
	 **/
	public function syncMember($mid){
		if (! empty($mid)) { 
			/* first we must get the user id given the member id */
			$uid = $this->getUser($mid);
			/* Then we can do the sync */
			\Drupal::service('rusa_user.manager')->syncMemberData($uid);
		}
	}
	
	/**
	 * Return Drupal used Id given RUSA member Id
	 **/
	protected function getUser($mid) {
	 	$this->logger->notice('Sync member data for RUSA #' . $mid);
		$query = $this->users->getQuery();
		$uids = $query
			->condition('field_rusa_member_id', $mid)
			->execute();
		return $uids[0];
	}

} //EoC
