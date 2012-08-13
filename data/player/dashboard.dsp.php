<?php
  $resource_list = Resource::db_get_all();

  /* @var $current_player Player */
  /* @var $current_game Game */
?>
<h2>Dashboard</h2>
<p>Welcome <?php echo $current_player->get_name()?> !</p>
<h3>Current Game</h3>
<ul>
  <li>Name : <a href="<?php echo Page::get_page_url('show_game', false, array('id' => $current_game->id))?>"><?php echo $current_game->name ?></a></li>
  <li>Turn : <?php echo $current_game->current_turn.'/'.$current_game->turn_limit ?></li>
  <li>Turn interval : <?php echo $current_game->turn_interval ?> seconds</li>
  <li>Status : <?php echo $current_game->status_string ?></li>
  <li>Created : <?php echo guess_time( $current_game->created, GUESS_TIME_LOCALE ) ?></li>
<?php if( $current_game->started ) { ?>
  <li>Started : <?php echo guess_time( $current_game->started, GUESS_TIME_LOCALE ) ?></li>
<?php } ?>
<?php if( $current_game->updated ) { ?>
  <li>Last turn : <?php echo guess_time( $current_game->updated, GUESS_TIME_LOCALE ) ?></li>
  <?php } ?>
<?php if( $current_game->ended ) { ?>
  <li>Ended : <?php echo guess_time( $current_game->ended, GUESS_TIME_LOCALE ) ?></li>
<?php }elseif( $current_game->updated ) { ?>
  <li>Next turn : <?php echo guess_time( $current_game->updated + $current_game->turn_interval, GUESS_TIME_LOCALE ) ?></li>
<?php }?>
</ul>
<?php if( $current_game->has_ended() ) {?>
<p>This game is over, check <a href="<?php echo Page::get_page_url('player_list', false, array('game_id' => $current_game->id))?>">the final scoreboard</a> !</p>
<?php } ?>
<?php
  if( $current_game->started ) {
?>
<p><a href="<?php echo Page::get_page_url('player_list')?>">Player list</a></p>
<h4>Wall</h4>
<form action="<?php echo Page::get_url('shout', array('game_id' => $current_game->id ))?>" method="post">
  <p><input type="text" name="text" value=""/><button type="submit" name="action" value="shout">Say</button></p>
</form>
<div id="shoutwall">
<?php
  $shouts = Shout::db_get_by_game_id( $current_game->id );
  foreach( array_reverse( $shouts ) as $shout ) {
    $player = Player::instance($shout->shouter_id);
    echo '
  <div class="shout"><strong>'.wash_utf8($player->name).'</strong>: '.wash_utf8($shout->text).'</div>';
  }
?>
</div>
<h3>Territories</h3>
<?php
  $player_territories = $current_player->get_territory_player_troops_list($current_game->id, $current_game->current_turn );
?>
<table>
  <tr>
    <th>Territories</th>
    <th>Troupes</th>
    <th>Status</th>
  </tr>
<?php
  foreach( $player_territories as $player_territory ) {
    $territory = Territory::instance( $player_territory['territory_id'] );
    echo '
  <tr>
    <td><a href="'.Page::get_url('show_territory', array('id' => $territory->id)).'">'.$territory->name.'</a></td>
    <td class="num">'.$player_territory['quantity'].'</td>
    <td>'.($territory->get_current_owner($current_game->id, $current_game->current_turn) == $current_player->id?'Stable':'Contested').'</td>
  </tr>';
  }
?>
</table>
<h3>Resources</h3>
<?php
    $sums = $current_player->get_resource_sum_list( $current_game->id );
?>
<ul>
<?php
    foreach( $sums as $sum ) {
      $resource = Resource::instance($sum['id']);
      echo '
  <li>'.$resource->get_name().' : '.$sum['sum'].'</li>';
    }
?>
</ul>
<h3>Resource history</h3>
<table>
  <thead>
    <tr>
      <th rowspan="2">Turn</th>
      <th rowspan="2">Event</th>
      <th colspan="<?php echo count( $resource_list )?>">Resource</th>
    </tr>
    <tr>
<?php
    foreach( $resource_list as $resource ) {
      echo '
      <th>'.$resource->get_name().'</th>';
    }
?>
    </tr>
  </thead>
  <tbody>
    <tr>
<?php
    $history = $current_player->get_resource_history( $current_game->id );
    $current_player_order_id = null;
    $flag_first = true;
    $resource_delta = array();
    foreach( $resource_list as $resource ) {
      $resource_delta[ $resource->id ] = 0;
    }
    $event_list = array();
    $key = -1;
    foreach( $history as $history_item ) {
      $key = $history_item['turn'].'-'.$history_item['player_order_id'];

      $event_list[ $key ]['reason'] = $history_item['reason'];
      $event_list[ $key ]['turn'] = $history_item['turn'];
      $event_list[ $key ]['datetime'] = $history_item['datetime'];
      $event_list[ $key ]['resource_delta'][ $history_item['resource_id'] ] = $history_item['delta'];
    }

    foreach( $event_list as $event ) {
      echo '
    <tr>
      <td class="date"><abbr title="'.$event['datetime'].'">'.$event['turn'].'</abbr></td>
      <td>'.$event['reason'].'</td>';
      foreach( $resource_list as $resource ) {
        if( isset( $event['resource_delta'][ $resource->id ] ) ) {
          $delta = $event['resource_delta'][ $resource->id ];
          echo '
      <td class="num">'. ($delta > 0?'+':'') . $delta .'</td>';
        }else {
          echo '
      <td></td>';
        }
      }
      echo '
    </tr>';
    }
?>
</table>

<?php
    if( ! $current_game->has_ended() ) {
?>
<h3>Orders</h3>
<h4>Orders planned</h4>
<?php
      $orders = Player_Order::db_get_planned_by_player_id( $current_player->id, $current_game->id );
?>
<table>
  <tr>
    <th>Order Type</th>
    <th>Order</th>
    <th>Scheduled</th>
    <th>Parameters</th>
    <th>Action</th>
  </tr>
<?php
      foreach( $orders as $player_order ) {
        $order_type = Order_Type::instance( $player_order->order_type_id );
        $parameters = $player_order->parameters;
        $param_string = '';
        foreach( $parameters as $key => $value ) {
          if( $key == 'player_id' ) {
            $player = Player::instance( $value );
            $value = $player->name;
          }
          $param_string[] = ucfirst( $key ).' : '.$value;
        }
        $param_string = implode('<br/>', $param_string);
        echo '
  <tr>
    <td>'.$order_type->name .'</td>
    <td>'.guess_time( $player_order->datetime_order, GUESS_TIME_LOCALE ) .'</td>
    <td>'.guess_time( $player_order->datetime_scheduled, GUESS_TIME_LOCALE ) .'</td>
    <td>'.$param_string.'</td>
    <td>
      <form action="'.Page::get_page_url('order').'" method="post">
        '.HTMLHelper::genererInputHidden('url_return', Page::get_page_url( PAGE_CODE ) ).'
        '.HTMLHelper::genererInputHidden('id', $player_order->get_id() ).'
        <button type="submit" name="action" value="cancel">Cancel</button>
      </form>
    </td>
  </tr>';
      }
?>
</table>
<?php
  $turn_ready = array_shift( $current_player->get_game_player_list( $current_game->id ) );
  if( $turn_ready['turn_ready'] <= $current_game->current_turn ) {
    echo '<p><a href="'.Page::get_url(PAGE_CODE, array('action' => 'ready')).'">I\'m ready for the next turn</a></p>';
  }else {
    echo '<p><a href="'.Page::get_url(PAGE_CODE, array('action' => 'ready')).'">I\'m not ready for the next turn yet</a></p>';
  }
?>
<h4>New order</h4>
<?php
      foreach( Order_Type::db_get_all() as $order_type ) {
        $class = $order_type->class_name;

        require_once(DATA.'order_type/'.$order_type->class_name.'.class.php');

        echo $class::get_html_form( array('page_code' => PAGE_CODE, 'current_player' => $current_player ) );
      }
    }
  }
?>