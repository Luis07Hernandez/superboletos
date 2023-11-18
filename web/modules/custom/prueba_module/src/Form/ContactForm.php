<?php

declare(strict_types=1);

namespace Drupal\prueba_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Prueba Module form.
 */
final class ContactForm extends FormBase
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
   * The entity repostory service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, AccountProxyInterface $current_user, MailManagerInterface $mail_manager, EntityRepositoryInterface $entityRepository)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
    $this->messenger();
    $this->entityRepository = $entityRepository;
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
      $container->get('plugin.manager.mail'),
      $container->get('entity.repository'),
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
    $custom_icon = 'background-image: url(/modules/custom/prueba_module/images/keyboard-solid.svg); background-repeat: no-repeat; background-position: right 10px center; padding-right: 30px;';

    $form['name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['customInput'],
        'placeholder' => $this->t('Full Name'),
        'style' => [$custom_icon],
      ],
    ];

    $form['promotora'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['customInput'],
        'placeholder' => $this->t('Promoter Name'),
        'style' => [$custom_icon],
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['customInput'],
        'placeholder' => $this->t('Email'),
        'style' => [$custom_icon],
      ],
    ];

    $form['Address'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['customInput'],
        'placeholder' => $this->t('Address'),
        'style' => [$custom_icon],
      ],
    ];

    // Obtiene las taxonomías de tipo "country" en el idioma actual.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => 'country',
    ]);

    $options = [];
    foreach ($terms as $term) {
      $translated_term = $this->entityRepository->getTranslationFromContext($term, $langcode);
      $options[$translated_term->id()] = $translated_term->label();
    }

    $form['countries'] = [
      '#type' => 'select',
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
      '#attributes' => [
        'class' => ['customInput'],
        'placeholder' => $this->t('Address'),
      ],
    ];

    $form['country_states'] = [
      '#type' => 'select',
      '#disabled' => TRUE,
      '#options' => [$this->t('Select a Country')],
      '#prefix' => '<div id="edit-country_states">',
      '#suffix' => '</div>',
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['customInput'],
        'placeholder' => $this->t('Address'),
      ],
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
      '#attributes' => [
        'class' => ['customButton'],
      ],
    ];

    $form['#theme'] = 'contact_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $values = $form_state->getValues();
    $body = $this->t('Form Submission Details:') . "\n\n";

    foreach ($values as $key => $value) {
      if (!in_array($key, ['submit', 'form_build_id', 'form_token', 'form_id'])) {

        // Get fiel label
        $field_label = isset($form[$key]['#title']) ? $form[$key]['#title'] : $key;

        if ($key == 'countries'){
          $langcode = $this->languageManager->getCurrentLanguage()->getId();
          $translated_term = $this->entityRepository->getTranslationFromContext($this->entityTypeManager->getStorage('taxonomy_term')->load($value), $langcode);
          $value = $translated_term->label();
        }

        // Build the message body with the field labels
        $body .= $this->t('@label: @value', ['@label' => $field_label, '@value' => $value]) . "\n";
      }
    }

    $body .= "\n" . $this->t('This message was sent from the form ID: @form_id', ['@form_id' => 'prueba_module_contact']);

    // change here, if you want the emails to be directed to an account, for testing purposes I send the email to the one entered by the form.
    $email_address = $form_state->getValue('email');

    $params['subject'] = $this->t('Drupal Superboletos Playful - Luis Hernandez');
    $params['body'] = $this->t('If you receive this message it means your site is capable of using SMTP to send e-mail.') . $body;

    if ($this->mailManager->mail('prueba_module', 'general_mail', $email_address, $this->currentUser->getPreferredLangcode(), $params)) {
      $this->messenger()->addStatus($this->t('The message has been sent.'));
      $this->messenger->addMessage($this->t('Success. The e-mail has been sent to @email via SMTP.', ['@email' => $email_address]));
    }

    $form_state->setRedirect('<front>');
  }

  /**
   * Callback function to update states dropdown.
   *
   * This function is typically used as an AJAX callback to update a part of the form.
   * It returns the form element that contains states, which is usually updated
   * based on a selection made in another form element, like a country dropdown.
   *
   * @param array &$form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated form element for states.
   */
  public function updateStatesCallback(array &$form, FormStateInterface $form_state)
  {
    // Return the prepared textfield.
    return $form['country_states'];
  }

  /**
   * Retrieves states based on the provided country taxonomy term ID.
   *
   * This function loads the taxonomy term for the given country ID and then
   * retrieves the states associated with it. It's commonly used to populate
   * a states dropdown based on the selected country.
   *
   * @param mixed $country_tid
   *   The taxonomy term ID of the country.
   *
   * @return array
   *   An associative array of states, where the keys and values are the state names.
   *   Returns an empty array if no states are found or if the country ID is not provided.
   */
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
