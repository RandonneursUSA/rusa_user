<?php
declare(strict_types=1);

namespace Drupal\rusa_user\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rusa_user\RusaUserManager;
use Psr\Log\LoggerInterface;

/**
 * Implement hooks per Drupal 11 specs
 *
 */
class UserLoginFormAlter
{

  /**
   * {@inheritdoc}
   */
  public function __construct(protected RusaUserManager $rusaUserService, protected LoggerInterface $logger){
  	$this->logger->notice("Instantiated class UserLoginFormAlter");  
  }

  
	/* 
	 * Implements hook_form_FORM_ID_alter
	 *
	 * Add a custom login validation to check for expired RUSA membership
	 */ 
	#[Hook('form_user_login_form_alter')]
	function formUserLoginFormAlter(&$form, FormStateInterface $form_state) 
	{
		$this->logger->notice("Entered form_user_login_form_alter");  
		dpm($form);

/*	
			
	    // Custom Validate
		array_unshift($form['actions']['submit']['#submit'], [
		  $this->rusaUserService, 'userLoginFormValidate'
		]);
		
		// Custom submit handler
		array_unshift($form['actions']['submit']['#submit'], [
		  $this->rusaUserService, 'userLoginFormSubmit'
		]);
*/


 		$form['#validate'][] = [$this->rusaUserService, 'userLoginFormValidate'];
		$form['#submit'][]   = [$this->rusaUserService, 'userLoginFormSubmit'];		
	}
	
	
	
	
} //end of class	