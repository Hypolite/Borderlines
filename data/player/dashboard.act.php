<?php
  $member = Member::get_current_user();
  $current_player = Player::get_current( $member );

  if( $current_player ) {
    // Game retrival
    if( $current_game = $current_player->last_game ) {
      // In game OR game ended
      $game_player = array_pop( $current_game->get_game_player_list( $current_player->id) );

      if( !$game_player['turn_leave'] ) {
        // In game OR game ended
        if( $current_game->has_ended() ) {
        }else {
          if( $action = getValue('action') ) {
            switch( $action ) {
              case 'ready' : {
                $current_player->set_game_player( $current_game->id, $current_game->current_turn + 1 );
                break;
              }
              case 'notready' : {
                $current_player->set_game_player( $current_game->id, $current_game->current_turn );
                break;
              }
              case 'change_diplomacy_status' : {
                $current_player->set_player_diplomacy( $current_game->id, $current_game->current_turn, getValue('to_player_id'), getValue('new_status'));
                break;
              }
            }
            Page::redirect(PAGE_CODE);
          }
        }
      }else {
        Page::set_message(__('You quit during your last game, please join another one.'), Page::PAGE_MESSAGE_WARNING);
        // Left the game
        Page::redirect( 'game_list' );
      }
    }else {
      // No game ever played
      Page::redirect( 'game_list' );
    }
  }else {
    // No player created
    Page::redirect( 'create_player' );
  }
?>