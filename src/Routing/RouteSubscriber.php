<?php
/**
 * @file
 * Contains \Drupal\rusa_user\Routing\RouteSubscriber.
 */

namespace Drupal\rusa_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection) {
      // register form
      if ($route = $collection->get('user.register')) {
        $route->setDefault('_form', '\Drupal\rusa_user\Form\RusaUserForm');
      }

    }
  }

