<?php

/**
 * @file
 * Contains \Drupal\balance_tracker\Form\BalanceTrackerDateForm.
 */

namespace Drupal\balance_tracker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Builds the date selection form at the top of the balance page.
 */
class BalanceTrackerDateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'balance_tracker_date_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user\Entity\User $user */
    $user = User::load($this->currentUser()->id());
    $output = '';

    // Preset $to and $from based on form variables if available, or on sensible
    // defaults if not. 86400 added to $to since to set the time to the end of the
    // selected day.
    if (!$form_state->get('date_to')) {
      $to = strtotime($form_state->get('date_to')) + 86400;
    }
    else {
      $to = $_SERVER['REQUEST_TIME'];
    }

    // Use value from form.
    if (!$form_state->get('date_from')) {
      $from = strtotime($form_state->get('date_from'));
    }
    // Use viewed user (looking at someone else's account).
    elseif (($uid = $this->getRequest()->get('user')) && $uid != $user->id()) {
      /** @var \Drupal\user\Entity\User $viewed_user */
      $viewed_user = User::load($uid);
      $from = $viewed_user->getCreatedTime();
      $output .= '<p>' . t("This is @user's balance sheet.", ['@user' => $viewed_user->getAccountName()]) . '</p>';
    }
    // Looking at own account.
    else {
      $from = $user->getCreatedTime();
      $output .= '<p>' . t('This is your balance sheet.') . '</p>';
    }

    $output .= '<p>' . t("This shows recent credits and debits to your account. Entries from a specific date period may be viewed by selecting a date range using the boxes below labelled 'From' and 'To'") . '</p>';

    $form['helptext'] = ['#markup' => $output];

    $form_state->disableRedirect();

    $format = 'Y-m-d H:i:s';

    /*
  $form['date_from'] = array(
    '#type' => 'date_popup',
    '#title' => t('From'),
    '#default_value' => date($format, $user->created),
    '#date_format' => $format,
    '#date_label_position' => 'within',
    '#date_timezone' => 'America/Chicago',
    '#date_increment' => 15,
    '#date_year_range' => '-3:+3',
  );
  $form['date_to'] = array(
    '#type' => 'date_popup',
    '#title' => t('To'),
    '#default_value' => date($format, $_SERVER['REQUEST_TIME']),
    '#date_format' => $format,
    '#date_label_position' => 'within',
    '#date_timezone' => 'America/Chicago',
    '#date_increment' => 15,
    '#date_year_range' => '-3:+3',
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Display'),
  );*/

    $form['accounts'] = balance_tracker_balance_table(0, REQUEST_TIME, isset($viewed_user) ? $viewed_user->id() : $user->id());
    $form['pager'] = [
      '#type' => 'pager',
      '#tags' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strtotime($form_state->getValue('date_from')) === FALSE) {
      $form_state->setErrorByName('date_from', $this->t('From date was not in a recognizable date format. <em>mm/dd/YYYY</em> should be recognized.'));
    }
    else {
      $form_state->set('date_from', $form_state->getValue('date_from'));
    }

    if (strtotime($form_state->getValue('date_to')) === FALSE) {
      $form_state->setErrorByName('date_to', $this->t('To date was not in a recognizable date format. <em>mm/dd/YYYY</em> should be recognized.'));
    }
    else {
      $form_state->set('date_to', $form_state->getValue('date_to'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Make the form rebuild with the new from and to dates.
    $form_state->setRebuild(TRUE);
  }

}
