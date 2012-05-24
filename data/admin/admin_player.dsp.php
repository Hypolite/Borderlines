<?php
  $PAGE_TITRE = "Administration des Players";
  include_once('data/static/html_functions.php');

  $page_no = getValue('p', 1);
  $nb_per_page = NB_PER_PAGE;
  $tab = Player::db_get_all($page_no, $nb_per_page);
  $nb_total = Player::db_count_all();

    echo '
<div class="texte_contenu">';

	admin_menu(PAGE_CODE);

	echo '
  <div class="texte_texte">
    <h3>Liste des Players</h3>
    '.nav_page(PAGE_CODE, $nb_total, $page_no, $nb_per_page).'
    <form action="'.get_page_url(PAGE_CODE).'" method="post">
    <table>
      <thead>
        <tr>
          <th>Sel.</th>
          <th>Name</th>
          <th>Member Id</th>        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="6">'.$nb_total.' éléments | <a href="'.get_page_url('admin_player_mod').'">Ajouter manuellement un objet Player</a></td>
        </tr>
      </tfoot>
      <tbody>';
    $tab_visible = array('0' => 'Non', '1' => 'Oui');
    foreach($tab as $player) {
      echo '
        <tr>
          <td><input type="checkbox" name="player_id[]" value="'.$player->get_id().'"/></td>
          <td><a href="'.htmlentities_utf8(get_page_url('admin_player_view', true, array('id' => $player->get_id()))).'">'.$player->get_name().'</a></td>

          <td>'.$player->get_member_id().'</td>
          <td><a href="'.htmlentities_utf8(get_page_url('admin_player_mod', true, array('id' => $player->get_id()))).'"><img src="'.IMG.'img_html/pencil.png" alt="Modifier" title="Modifier"/></a></td>
        </tr>';
    }
    echo '
      </tbody>
    </table>
    <p>Pour les objets Player sélectionnés :
      <select name="action">
        <option value="delete">Delete</option>
      </select>
      <input type="submit" name="submit" value="Valider"/>
    </p>
    </form>
    '.nav_page(PAGE_CODE, $nb_total, $page_no, $nb_per_page).'
  </div>
</div>';