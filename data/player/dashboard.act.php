<?php
  $member = Member::instance( Member::get_current_user_id() );

  $player_list = Player::db_get_by_member_id( $member->get_id() );
  if( count( $player_list ) ) {
    $current_player = array_shift( $player_list );

    // Game retrival
    if( $current_game = $current_player->last_game ) {
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
        }
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