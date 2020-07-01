<?php

namespace Drupal\rusa_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for RUSA User account form.
 */
class RusaUserSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'rusa_user_settings_form';
    }

    /**
    * {@inheritdoc}
    */
    protected function getEditableConfigNames() {
        return ['rusa_user.settings'];
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

		$config = $this->config('rusa_user.settings');

        $form['settings'] = [
            '#markup' => $this->t('Settings form for RUSA User accounts.'),
        ];
        
        // RUSA account manager
        $form['acctmgr'] = [
            '#type'          => 'textfield',
            '#title'         => $this->t("Account manager email"),
            '#description'   => $this->t("Enter an email address for the account manager."),
            '#size'          => 40,
            '#default_value' => $config->get('acctmgr'),
        ];
        

        // Instructions for the top of the form
        $form['instructions'] = [
            '#type'          => 'textarea',
            '#title'         => $this->t('Instructions'),
            '#description'   => $this->t('This is the text that appears on the form for creating website accounts.'),
            '#rows'          => 4,
            '#cols'          => 60,
            '#default_value' => $config->get('instructions'),
        ];

        // Email notification
        $form['notify'] = [
            '#type'          => 'textarea',
            '#title'         => $this->t('Notification email message'),
            '#description'   => $this->t('This is the email message that people recive with the link to change their password'),
            '#rows'          => 4,
            '#cols'          => 60,
            '#default_value' => $config->get('notify'),
        ];

        // Status message
        $form['message'] = [
            '#type'          => 'textarea',
            '#title'         => $this->t('Status message'),
            '#description'   => $this->t('This is the message displays in the status area when the account is created'),
            '#rows'          => 4,
            '#cols'          => 60,
            '#default_value' => $config->get('message'),
        ];
        
        
        // Actions
        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $values = $form_state->getValues();

        $this->config('rusa_user.settings')
            ->set('acctmgr', $values['acctmgr'])
            ->set('instructions', $values['instructions'])
            ->set('notify', $values['notify'])
            ->set('message', $values['message'])
            ->save();

        parent::submitForm($form, $form_state);
    }

}
