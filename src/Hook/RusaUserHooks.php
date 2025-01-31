<?php
declare(strict_types=1);

namespace Drupal\rusa_user\Hook;

use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rusa_user\RusaUserManager;

/**
 * Implement hooks per Drupal 11 specs
 *
 */
class RusaUserHooks
{

    /**
     * Implements hook_help().
     *
     */
    #[Hook('help')]
    public function help($route_name, RouteMatchInterface $route_match)
    {
        switch ($route_name) {
        	case 'help.page.rusa_imports':
      			$output = '';
			    $output .= '<h3>' . t('About') . '</h3>';
      			$output .= '<p>' . t('Create Drupal accounts for RUSA members.') . '</p>';
      			return $output;
      		
		}
	}
	
	/**
	 * Implements hook_entity_update()
	 *
	 * Auto fill the user dispaly name with first + last
	 *
	 */
	#[Hook('entity_presave')] 
	function entity_presave(EntityInterface $entity)
	{

		if ($entity instanceof UserInterface) {
			$fields = $entity->getFields(FALSE);
			if ($fields['field_display_name']->isEmpty()) {
				$fname = $fields['field_first_name']->getValue();
				$lname = $fields['field_last_name']->getValue();
				$display_name = $fname[0]['value'] . ' ' . $lname[0]['value'];
				$fields['field_display_name']->setValue([$display_name]);
			}
		}

	}

	/* 
	 * Implements hook_form_FORM_ID_alter
	 *
	 * Add a custom login validation to check for expired RUSA membership
	 */
	#[Hook('form_user_login_form_alter')]
	function form_user_login_form_alter(&$form, FormStateInterface $form_state) 
	{
		$form['#validate'][] = 'Drupal\rusa_user\RusaUserManager::user_login_form_validate';
	}
	
	/**
	 * Implements hook_user_login
	 *
	 * Sync user data at login
	 *
	 */
	#[Hook('user_login')]
	function user_login(UserInterface $account) { 
		// Sync member data from GDBM
		\Drupal::service('rusa_user.manager')->syncMemberData($account->id());
	}

	/**
	 * hook_mail
	 *
	 * Provides the template for email notifications
	 *
	 */
	#[Hook('mail')] 
	function mail($key, &$message, $params) {

		// Will figure out how to use and replace tokens in messages
		$token_service = \Drupal::token();

		$body  = $params['message'] . "\n\n" . $params['url'] ;
		$message['subject'] = $params['subj'];
		$message['body'][]  = Drupal\Core\Mail\MailFormatHelper::htmlToText($body);
	}

	/**
	 * Implements hook_menu_links_discovered_alter().
	 *
	 * Point to our menu plugin to change the log in link text
	 */
	#[Hook('menu_links_discovered_alter')]
	function menu_links_discovered_alter(&$links) {
		$links['user.logout']['class'] = 'Drupal\rusa_user\Plugin\Menu\RusaAccountMenu';
	}

	/**
	 * Implements hook_cron
	 *
	 * Update users email from members database
	 */
	#[Hook('cron')]
	function cron() {
		 \Drupal::service('rusa_user.manager')->syncMemberData();
	}

	
	
} //end of class	