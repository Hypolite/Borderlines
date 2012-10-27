<?php
  $member = Member::instance( Member::get_current_user_id() );

  // TODO : Create player page
  $player_list = Player::db_get_by_member_id( $member->id );
  $current_player = array_shift( $player_list );

  $game_id = getValue('game_id');
  $turn = getValue('turn');
  $world_id = getValue('id');
  $current_game = Game::instance( $game_id );

  if( ! $world_id ) {
    if( $game_id ) {
      $world_id = $current_game->world_id;


      if( $turn === null ) {
        $turn = $current_game->current_turn;
      }

      $params = array('game_id' => $current_game->id, 'turn' => $turn);
    }else {
      Page::redirect('game_list');
    }
  }else {
    if( $world_id == 1 ) {
      Page::add_message(__('Unable to show this world : This is a test world without territories'), Page::PAGE_MESSAGE_WARNING);
      Page::redirect('world_list');
    }
    $params = array('id' => $world_id);
  }

  $world = World::instance( $world_id );

  if( !$world->id ) {
    Page::add_message(__('Unknown world'), Page::PAGE_MESSAGE_ERROR);
    Page::redirect('world_list');
  }

  $sort_field = getValue('sort_field', 'name');
  $sort_direction = getValue('sort_direction', 1);

  $params['sort_field'] = $sort_field;
  $params['sort_direction'] = $sort_direction;

  $territory_list = Territory::get_by_world($world, $current_game, $turn, $sort_field, $sort_direction);