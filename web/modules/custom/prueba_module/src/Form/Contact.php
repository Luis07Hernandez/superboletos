<?php

namespace Drupal\prueba_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default form.
 */
class Contact extends FormBase
{

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new MyCustomService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, AccountProxyInterface $current_user, MailManagerInterface $mail_manager)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
    $this->messenger();
  }

  /**
   * Creates an instance of MyCustomService.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return \Drupal\your_module\MyCustomService
   *   A new instance of MyCustomService.
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'prueba_module_contact';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
    ];

    $form['promotora'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Promoter Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['Address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
    ];

    // Obtiene las taxonomías de tipo "country" en el idioma actual.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => 'country',
      'langcode' => $langcode,
    ]);

    $options = [];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }

    $form['countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Country'),
      '#options' => $options,
      '#ajax' => [
        'callback' => '::updateStatesCallback',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'edit-country_states',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading...'),
        ],
      ],
      '#required' => TRUE,
    ];

    $form['country_states'] = [
      '#type' => 'select',
      '#disabled' => TRUE,
      '#options' => [$this->t('Select a Country')],
      '#prefix' => '<div id="edit-country_states">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ];

    // If a country has been chooice
    if ($country_tid = $form_state->getValue('countries')) {

      $states = $this->getStatesByCountry($country_tid);

      $form['country_states']['#options'] =  $states;
      $form['country_states']['#disabled'] = FALSE;
    }

    // Create the submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    $values = $form_state->getValues();
    $body = $this->t('Form Submission Details:') . "\n\n";

    foreach ($values as $key => $value) {
      if (!in_array($key, ['submit', 'form_build_id', 'form_token', 'form_id'])) {
        $body .= $this->t('@key: @value', ['@key' => $key, '@value' => is_array($value) ? implode(', ', $value) : $value]) . "\n";
      }
    }

    $body .= "\n" . $this->t('This message was sent from the form ID: @form_id', ['@form_id' => 'prueba_module_contact']);

    $email_address = $form_state->getValue('email');
    $params['subject'] = $this->t('Drupal Superboletos Playful - Luis Hernandez');
    $params['body'] = $this->t('If you receive this message it means your site is capable of using SMTP to send e-mail.') . $body;

    $mailManager = \Drupal::service('plugin.manager.mail');
    $currentUser = \Drupal::service('current_user');

    if ($mailManager->mail('prueba_module', 'general_mail', $email_address, $currentUser->getPreferredLangcode(), $params)) {
      \Drupal::messenger()->addMessage($this->t('Success. The e-mail has been sent to @email via SMTP.', ['@email' => $email_address]));
    }
  }

  public function updateStatesCallback(array &$form, FormStateInterface $form_state)
  {
    // Return the prepared textfield.
    return $form['country_states'];
  }

  protected function getStatesByCountry($country_tid)
  {
    $states = [];
    if ($country_tid) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($country_tid);
      if ($term && $term->hasField('field_states')) {
        foreach ($term->get('field_states') as $item) {
          $states[$item->value] = $item->value;
        }
      }
    }

    return $states;
  }
}
