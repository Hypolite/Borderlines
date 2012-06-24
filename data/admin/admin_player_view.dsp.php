<?php
  include_once('data/static/html_functions.php');

  $tab_visible = array('0' => 'Non', '1' => 'Oui');

  $form_url = get_page_url($PAGE_CODE).'&id='.$player->id;
  $PAGE_TITRE = 'Player : Showing "'.$player->name.'"';
?>
<div class="texte_contenu">
<?php echo admin_menu(PAGE_CODE);?>
  <div class="texte_texte">
    <h3>Showing "<?php echo $player->name?>"</h3>
    <div class="informations formulaire">

<?php
      $option_list = array();
      $member_list = Member::db_get_all();
      foreach( $member_list as $member)
        $option_list[ $member->id ] = $member->name;
?>
      <p class="field">
        <span class="libelle">Member Id</span>
        <span class="value"><a href="<?php echo get_page_url('admin_member_view', true, array('id' => $player->member_id ) )?>"><?php echo $option_list[ $player->member_id ]?></a></span>
      </p>

            <p class="field">
              <span class="libelle">Active</span>
              <span class="value"><?php echo $tab_visible[$player->active]?></span>
            </p>    </div>
    <p><a href="<?php echo get_page_url('admin_player_mod', true, array('id' => $player->id))?>">Modifier cet objet Player</a></p>
    <h4>Game Player</h4>
<?php

  $game_player_list = $player->get_game_player_list();

  if(count($game_player_list)) {
?>
    <table>
      <thead>
        <tr>
          <th>Game Id</th>
          <th>Turn Ready</th>          <th>Action</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="3"><?php echo count( $game_player_list )?> lignes</td>
        </tr>
      </tfoot>
      <tbody>
<?php
      foreach( $game_player_list as $game_player ) {

 
        $game_id_game = Game::instance( $game_player['game_id'] );        echo '
        <tr>
        <td><a href="'.get_page_url('admin_game_view', true, array('id' => $game_id_game->id)).'">'.$game_id_game->name.'</a></td>
        <td>'.$game_player['turn_ready'].'</td>          <td>
            <form action="'.get_page_url(PAGE_CODE, true, array('id' => $player->id)).'" method="post">
              '.HTMLHelper::genererInputHidden('id', $player->id).'

              '.HTMLHelper::genererInputHidden('game_id', $game_id_game->id).'              '.HTMLHelper::genererButton('action',  'del_game_player', array('type' => 'submit'), 'Supprimer').'
            </form>
          </td>
        </tr>';
      }
?>
      </tbody>
    </table>
<?php
  }else {
    echo '<p>Il n\'y a pas d\'éléments à afficher</p>';
  }

  $liste_valeurs_game = Game::db_get_select_list();?>
    <form action="<?php echo get_page_url(PAGE_CODE, true, array('id' => $player->id))?>" method="post" class="formulaire">
      <?php echo HTMLHelper::genererInputHidden('id', $player->id )?>
      <fieldset>
        <legend>Ajouter un élément</legend>
        <p class="field">
          <?php echo HTMLHelper::genererSelect('game_id', $liste_valeurs_game, null, array(), 'Game' )?><a href="<?php echo get_page_url('admin_game_mod')?>">Créer un objet Game</a>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('turn_ready', null, array(), 'Turn Ready' )?>
        </p>
        <p><?php echo HTMLHelper::genererButton('action',  'set_game_player', array('type' => 'submit'), 'Ajouter un élément')?></p>
      </fieldset>
    </form>
    <h4>Player Resource History</h4>
<?php

  $player_resource_history_list = $player->get_player_resource_history_list();

  if(count($player_resource_history_list)) {
?>
    <table>
      <thead>
        <tr>
          <th>Game Id</th>
          <th>Resource Id</th>
          <th>Turn</th>
          <th>Datetime</th>
          <th>Delta</th>
          <th>Reason</th>
          <th>Player Order Id</th>          <th>Action</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="8"><?php echo count( $player_resource_history_list )?> lignes</td>
        </tr>
      </tfoot>
      <tbody>
<?php
      foreach( $player_resource_history_list as $player_resource_history ) {

 
        $game_id_game = Game::instance( $player_resource_history['game_id'] );
        $resource_id_resource = Resource::instance( $player_resource_history['resource_id'] );
        $player_order_id_player_order = Player_Order::instance( $player_resource_history['player_order_id'] );        echo '
        <tr>
        <td><a href="'.get_page_url('admin_game_view', true, array('id' => $game_id_game->id)).'">'.$game_id_game->name.'</a></td>
        <td><a href="'.get_page_url('admin_resource_view', true, array('id' => $resource_id_resource->id)).'">'.$resource_id_resource->name.'</a></td>
        <td>'.$player_resource_history['turn'].'</td>
        <td>'.$player_resource_history['datetime'].'</td>
        <td>'.$player_resource_history['delta'].'</td>
        <td>'.$player_resource_history['reason'].'</td>
        <td><a href="'.get_page_url('admin_player_order_view', true, array('id' => $player_order_id_player_order->id)).'">'.$player_order_id_player_order->id.'</a></td>          <td>
            <form action="'.get_page_url(PAGE_CODE, true, array('id' => $player->id)).'" method="post">
              '.HTMLHelper::genererInputHidden('id', $player->id).'

              '.HTMLHelper::genererInputHidden('game_id', $game_id_game->id).'
              '.HTMLHelper::genererInputHidden('resource_id', $resource_id_resource->id).'
              '.HTMLHelper::genererInputHidden('player_order_id', $player_order_id_player_order->id).'              '.HTMLHelper::genererButton('action',  'del_player_resource_history', array('type' => 'submit'), 'Supprimer').'
            </form>
          </td>
        </tr>';
      }
?>
      </tbody>
    </table>
<?php
  }else {
    echo '<p>Il n\'y a pas d\'éléments à afficher</p>';
  }

  $liste_valeurs_game = Game::db_get_select_list();
  $liste_valeurs_resource = Resource::db_get_select_list();
  $liste_valeurs_player_order = Player_Order::db_get_select_list();?>
    <form action="<?php echo get_page_url(PAGE_CODE, true, array('id' => $player->id))?>" method="post" class="formulaire">
      <?php echo HTMLHelper::genererInputHidden('id', $player->id )?>
      <fieldset>
        <legend>Ajouter un élément</legend>
        <p class="field">
          <?php echo HTMLHelper::genererSelect('game_id', $liste_valeurs_game, null, array(), 'Game' )?><a href="<?php echo get_page_url('admin_game_mod')?>">Créer un objet Game</a>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererSelect('resource_id', $liste_valeurs_resource, null, array(), 'Resource' )?><a href="<?php echo get_page_url('admin_resource_mod')?>">Créer un objet Resource</a>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('turn', null, array(), 'Turn' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('datetime', null, array(), 'Datetime' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('delta', null, array(), 'Delta' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('reason', null, array(), 'Reason' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererSelect('player_order_id', $liste_valeurs_player_order, null, array(), 'Player Order' )?><a href="<?php echo get_page_url('admin_player_order_mod')?>">Créer un objet Player Order</a>
        </p>
        <p><?php echo HTMLHelper::genererButton('action',  'set_player_resource_history', array('type' => 'submit'), 'Ajouter un élément')?></p>
      </fieldset>
    </form>
    <h4>Player Spygame Value</h4>
<?php

  $player_spygame_value_list = $player->get_player_spygame_value_list();

  if(count($player_spygame_value_list)) {
?>
    <table>
      <thead>
        <tr>
          <th>Game Id</th>
          <th>Value Guid</th>
          <th>Turn</th>
          <th>Datetime</th>
          <th>Real Value</th>
          <th>Masked Value</th>          <th>Action</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="7"><?php echo count( $player_spygame_value_list )?> lignes</td>
        </tr>
      </tfoot>
      <tbody>
<?php
      foreach( $player_spygame_value_list as $player_spygame_value ) {

 
        $game_id_game = Game::instance( $player_spygame_value['game_id'] );        echo '
        <tr>
        <td><a href="'.get_page_url('admin_game_view', true, array('id' => $game_id_game->id)).'">'.$game_id_game->name.'</a></td>
        <td>'.$player_spygame_value['value_guid'].'</td>
        <td>'.$player_spygame_value['turn'].'</td>
        <td>'.$player_spygame_value['datetime'].'</td>
        <td>'.$player_spygame_value['real_value'].'</td>
        <td>'.$player_spygame_value['masked_value'].'</td>          <td>
            <form action="'.get_page_url(PAGE_CODE, true, array('id' => $player->id)).'" method="post">
              '.HTMLHelper::genererInputHidden('id', $player->id).'

              '.HTMLHelper::genererInputHidden('game_id', $game_id_game->id).'
              '.HTMLHelper::genererInputHidden('value_guid', $player_spygame_value['value_guid']).'
              '.HTMLHelper::genererInputHidden('turn', $player_spygame_value['turn']).'              '.HTMLHelper::genererButton('action',  'del_player_spygame_value', array('type' => 'submit'), 'Supprimer').'
            </form>
          </td>
        </tr>';
      }
?>
      </tbody>
    </table>
<?php
  }else {
    echo '<p>Il n\'y a pas d\'éléments à afficher</p>';
  }

  $liste_valeurs_game = Game::db_get_select_list();?>
    <form action="<?php echo get_page_url(PAGE_CODE, true, array('id' => $player->id))?>" method="post" class="formulaire">
      <?php echo HTMLHelper::genererInputHidden('id', $player->id )?>
      <fieldset>
        <legend>Ajouter un élément</legend>
        <p class="field">
          <?php echo HTMLHelper::genererSelect('game_id', $liste_valeurs_game, null, array(), 'Game' )?><a href="<?php echo get_page_url('admin_game_mod')?>">Créer un objet Game</a>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('value_guid', null, array(), 'Value Guid' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('turn', null, array(), 'Turn' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('datetime', null, array(), 'Datetime' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('real_value', null, array(), 'Real Value' )?>
        </p>
        <p class="field">
          <?php echo HTMLHelper::genererInputText('masked_value', null, array(), 'Masked Value' )?>
        </p>
        <p><?php echo HTMLHelper::genererButton('action',  'set_player_spygame_value', array('type' => 'submit'), 'Ajouter un élément')?></p>
      </fieldset>
    </form>
<?php
  // CUSTOM

  //Custom content

  // /CUSTOM
?>
    <p><a href="<?php echo get_page_url('admin_player')?>">Revenir à la liste des objets Player</a></p>
  </div>
</div>