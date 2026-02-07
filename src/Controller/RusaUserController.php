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
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Rusa User Controller
 *
 */
class RusaUserController extends ControllerBase {

  protected $currentUser;
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $currentUser, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provide current user uid and mid via a JSON response
   * This is used in cgi-bin/RUSA/Common.pm
   */
  public function getCurrentUser() {
    $uid = $this->currentUser->id();
    $user = User::load($uid);
    $mid = $user->get('field_rusa_member_id')->getValue()[0]['value'];

    return new JsonResponse([
      'data' => ['uid' => $uid, 'mid' => $mid],
    ]);
  }


  /**
   * Check to see if there is a user with given RUSA #
   *
   * @VAR $mid string containing RUSA#
   * 
   * @Return JSON response with Drupal uid or null
   *
   */
  public function userExists($mid) {
    $query = $this->entityTypeManager->getStorage('user')->getQuery();
    $uids = $query
      ->condition('status', '1')
      ->condition('field_rusa_member_id', $mid)
      ->accessCheck(TRUE)
      ->execute();

    return new JsonResponse([
      'data' => ['uid' => array_key_first($uids)],
    ]);

  }


} //EoC

