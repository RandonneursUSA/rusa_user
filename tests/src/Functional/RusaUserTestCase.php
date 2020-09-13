<?php

namespace Drupal\Tests\rusa_user\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality
 *
 * @group rusa_user
 */
class RusaUserTestCase extends BrowserTestBase {

    /**
     * {@inheritdoc}
     */
    public static $modules = [
        // Modules for core functionality.
        'node',
        'views',
        'rusa_api',
        'rusa_user',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
        // Make sure to complete the normal setup steps first.
        parent::setUp();

        // Set the front page to "/node".
        \Drupal::configFactory()
          ->getEditable('system.site')
          ->set('page.front', '/node')
          ->save(TRUE);
      }


    /**
     * Make sure the site still works. For now just check the front page.
     */
    public function testTheSiteStillWorks() {
        // Load the front page.
        $this->drupalGet('<front>');

        // Confirm that the site didn't throw a server error or something else.
        $this->assertSession()->statusCodeEquals(200);

        // Confirm that the front page contains the standard text.
        $this->assertText('Welcome to Drupal');
    }
}