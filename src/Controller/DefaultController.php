<?php

/**
 * @file
 * Contains \Drupal\balance_tracker\Controller\DefaultController.
 */

namespace Drupal\balance_tracker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Default controller for the balance_tracker module.
 */
class DefaultController extends ControllerBase {

  public function allBalancesPage() {
    $output = [
      '#cache' => [
        'tags' => ['balance_tracker'],
      ],
      'header1' => [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('Here you can view the balances for all users.'),
      ],
      'header2' => [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('The balance column is sortable. Click on a user\'s name to get their balance sheet.'),
      ],
    ];

    $header = array(
      'uid' => array('data' => $this->t('User'), 'sort' => 'uid'),
      'balance' => array(
        'data' => $this->t('Balance'),
        'sort' => 'balance',
        'sort' => 'desc',
      ),
    );

    $query = db_select('balance_items', 'b1')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->addField('b1', 'uid', 'uid');
    $query->addExpression('(SELECT b.balance FROM {balance_items} b
                            WHERE b.uid = b1.uid
                            ORDER BY b.bid DESC LIMIT 0,1)', 'balance');
    $query->groupBy('b1.uid');
    $query->limit(25);
    $results = $query->execute();

    $rows = array();
    foreach ($results as $result) {
      // Swap the UID result for a fully formatted link to the user's balance.
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load($result->uid);
      $row['user'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $user->getDisplayname(),
          '#url' => new Url('balance_tracker.user_balance', ['user' => $user->id()]),
        ],
      ];
      $row['balance'] = balance_tracker_format_currency($result->balance);
      $rows[] = $row;
    }

    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('You don\'t appear to have any users with balances.'),
    ];
    $output['pager'] = [
      '#type' => 'pager',
      '#tags' => [],
    ];
    return $output;
  }

}
