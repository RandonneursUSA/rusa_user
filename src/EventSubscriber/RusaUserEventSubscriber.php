<?php
/**
 * @file RusaUserEventSubscriober.php
 *
 * Created: 2020-Aug-3
 * Author: Paul Lieberman
 *
 * Subscribe to login events and do stuff
 *
 * See: https://www.drupal.org/docs/creating-custom-modules/subscribe-to-and-dispatch-events
 *
 */

namespace Drupal\rusa_user\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class RusaUserEventSubscriber implements EventSubscriberInterface {
    
    protected $user;
    
    
    /**
     * {@inheritdoc}
     *
     * @return array
     *   The event names to listen for, and the methods that should be executed.
     */
    public static function getSubscribedEvents() {
        // We need to find the login event, if it exists
        return [ ];
    }
    

    
} // End of Class    