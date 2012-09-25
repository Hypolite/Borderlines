<?php
/**
 * Class Conversation
 *
 */

require_once( DATA."conversation/conversation.class.php" );

class Conversation_Player extends Conversation {

  protected $_conversation_id = null;
  protected $_player_id = null;
  protected $_game_id = null;
  protected $_subject = null;
  protected $_created = null;
  // Coming from conversation_player
  protected $_archived = null;
  protected $_left = null;


  public function db_get_by_game($player_id, $game_id = null, $archived = false) {
    $where = '';
    if( $game_id !== null ) {
      $where .= '
AND c.`game_id` = '.mysql_ureal_escape_string($game_id);
    }else {
      $where .= '
AND c.`game_id` IS NULL';
    }
    if( $archived ) {
      $where .= '
AND c_p.`archived` IS NOT NULL';
    }else {
      $where .= '
AND c_p.`archived` IS NULL';
    }

    $sql = '
SELECT *
FROM `'.self::get_table_name().'` c
JOIN `conversation_player` c_p ON c_p.`conversation_id` = c.`id`
WHERE c_p.`player_id` = '.mysql_ureal_escape_string($player_id).$where;

    return self::sql_to_list($sql);
  }

  protected static function sql_to_list($sql) {
    $res = mysql_uquery($sql);

    if($res) {
      $return = array();
      while($data = mysql_fetch_assoc($res)) {
        $new_conversation_player = new Conversation_Player();
        foreach( $data as $field => $value ) {
          try {
            $new_conversation_player->$field = $value;
          }catch(Exception $e){}
        }
        $return[] = $new_conversation_player;
      }
      mysql_free_result($res);
    }else {
      $return = false;
    }

    return $return;
  }

}