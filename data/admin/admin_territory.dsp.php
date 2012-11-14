<?php
  $PAGE_TITRE = "Administration des Territorys";

  $page_no = getValue('p', 1);
  $nb_per_page = NB_PER_PAGE;
  $tab = Territory::db_get_all($page_no, $nb_per_page, true);
  $nb_total = Territory::db_count_all(true);

    echo '
<div class="texte_contenu">
  <div class="texte_texte">
    <h3>Liste des Territorys</h3>
    '.nav_page(PAGE_CODE, $nb_total, $page_no, $nb_per_page).'
    <form action="'.Page::get_url(PAGE_CODE).'" method="post">
    <table class="table table-condensed table-striped table-hover">
      <thead>
        <tr>
          <th>Sel.</th>
          <th>Name</th>
          <th>Capital Name</th>
          <th>World Id</th>
          <th>Vertices</th>
          <th>Passable</th>
          <th>Capturable</th>
          <th>Background</th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <td colspan="6">'.$nb_total.' éléments | <a href="'.Page::get_url('admin_territory_mod').'">Ajouter manuellement un objet Territory</a></td>
        </tr>
      </tfoot>
      <tbody>';
    $tab_visible = array('0' => 'Non', '1' => 'Oui');
    foreach($tab as $territory) {
      echo '
        <tr>
          <td><input type="checkbox" name="territory_id[]" value="'.$territory->id.'"/></td>
          <td><a href="'.htmlentities_utf8(Page::get_url('admin_territory_view', array('id' => $territory->id))).'">'.$territory->get_name().'</a></td>

          <td>'.(is_array($territory->capital_name)?nl2br(parameters_to_string($territory->capital_name)):$territory->capital_name).'</td>';
      $world_temp = World::instance( $territory->world_id);
      echo '
          <td>'.$world_temp->name.'</td>
          <td>'.(is_array($territory->vertices)?nl2br(parameters_to_string($territory->vertices)):$territory->vertices).'</td>
          <td>'.$tab_visible[$territory->passable].'</td>
          <td>'.$tab_visible[$territory->capturable].'</td>
          <td>'.(is_array($territory->background)?nl2br(parameters_to_string($territory->background)):$territory->background).'</td>
          <td><a href="'.htmlentities_utf8(Page::get_url('admin_territory_mod', array('id' => $territory->id))).'"><img src="'.IMG.'img_html/pencil.png" alt="Modifier" title="Modifier"/></a></td>
        </tr>';
    }
    echo '
      </tbody>
    </table>
    <p>Pour les objets Territory sélectionnés :
      <select name="action">
        <option value="delete">Delete</option>
      </select>
      <input type="submit" name="submit" value="Valider"/>
    </p>
    </form>
    '.nav_page(PAGE_CODE, $nb_total, $page_no, $nb_per_page).'
  </div>
</div>';