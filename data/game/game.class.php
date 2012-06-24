<?php
/**
 * Class Game
 *
 */

require_once( DATA."model/game_model.class.php" );

class Game extends Game_Model {

  // CUSTOM
  
  public function get_status_string() {
    $return = "Waiting for players";
    if( $this->has_ended() ) {
      $return = "Ended";
    }elseif( $this->started ) {
      $return = "Running";
    }

    return $return;
  }
  
  public function has_ended() {
    return ($this->current_turn >= $this->turn_limit);
  }
  
  public function reset() {
    $this->current_turn = 0;
    $this->ended = null;
    $this->updated = null;
    
    $this->save();
    
    Player_Order::db_truncate_by_game( $this->id );
    $this->del_player_resource_history();
    $this->del_game_player();
  }
  
  public function start() {
    $this->started = time();
    $this->updated = time();
    
    $this->save();
  
    $game_player_list = $this->get_game_player_list( );
    
    $resources = Resource::db_get_select_list();
    foreach( $game_player_list as $game_player ) {
      $player = Player::instance( $game_player['player_id'] );

      foreach( $resources as $resource_id => $resource_name ) {
        $this->set_player_resource_history( $player->id, $resource_id, $this->current_turn, guess_time( time(), GUESS_DATE_MYSQL), 1000, "Init ($resource_name)", null );
      }
      
      // TODO : Send notification
    }
  }

  public function compute_auto() {
    if(
      $this->current_turn < $this->turn_limit && 
      $this->updated + $this->turn_interval < time()
    ) {
      $this->compute();
    }
  }
  
  public function compute() {
    $return = false;
    if( !$this->has_ended() ) {
      $this->current_turn++;
    
      $game_player_list = $this->get_game_player_list( );
      foreach( $game_player_list as $game_player ) {
        $player = Player::instance( $game_player['player_id'] );
        $territory_gain = $player->get_resource_sum( 4 ) * 0.1;
        $message = "Territory gain";
        $this->set_player_resource_history( $player->id, 5, $this->current_turn, guess_time( mktime(), GUESS_DATE_MYSQL ), $territory_gain, $message, null );
      }
      
      $player_order_list = Player_Order::get_ready_orders( $this->id );
      
      foreach( $player_order_list as $order ) {
        $order_type = Order_Type::instance( $order->get_order_type_id() );
        $class = $order_type->get_class_name();
        require_once ('data/order_type/'.strtolower( $class ).'.class.php');
        $order = $class::instance( $order->get_id() );
        $order->execute();
      }
      
      $this->updated = time();
      
      if( $this->current_turn == $this->turn_limit ) {
        $this->ended = time();
      }
    
      $return = $this->save();
    }
    return $return;
  }
  
  public function db_get_ready_game_list() {
    $sql = "
SELECT *
FROM `".self::get_table_name()."`
WHERE `updated` IS NOT NULL
AND `current_turn` < `turn_limit`
AND `updated` + `turn_interval` < NOW()";

    return self::sql_to_list( $sql );
  }
  
  public function html_get_game_list_form() {
    $turn_interval_list = array(
      600 => "Crazy short - 10 min",
      3600 => "Short - Hourly",
      86400 => "Medium - Daily",
      604800 => "Long - Weekly",
    );
    $return = '
    <fieldset>
      <legend>Create a game !</legend>
      '.HTMLHelper::genererInputHidden('id', $this->get_id()).'
      <p class="field">'.HTMLHelper::genererInputText('name', $this->get_name(), array(), "Name*").'</p>
      <p class="field">'.HTMLHelper::genererSelect('turn_interval', $turn_interval_list, $this->get_turn_interval(), array(), "Turn Interval*").'</p>
      <p class="field">'.HTMLHelper::genererInputText('turn_limit', $this->get_turn_limit(), array('title' => 'Game will stop after a fixed amount of turns'), "Turn Limit*").'</p>
      <p class="field">'.HTMLHelper::genererInputText('min_players', $this->get_min_players(), array('title' => 'Number of players required to automatically launch the game'), "Minimum nb of players").'</p>
      <p class="field">'.HTMLHelper::genererInputText('max_players', $this->get_max_players(), array(), "Maximum nb of players").'</p>
    </fieldset>';

    return $return;
  }
  
  public function add_player( $player ) {
    $return = false;

    if( !$this->started ) {
      if( !$player->get_current_game() ) {
        if( !$this->max_players || count( $this->get_game_player_list() ) < $this->max_players ) {
          $this->set_game_player( $player->id, -1 );

          $return = true;
        }else {
          Page::add_message('Game is already complete', Page::PAGE_MESSAGE_ERROR);
        }
      }else {
        Page::add_message('You are already in a game !', Page::PAGE_MESSAGE_ERROR);
      }
    }else {
      Page::add_message('Game already started', Page::PAGE_MESSAGE_ERROR);
    }
    
    return $return;
  }

  // /CUSTOM

}