<?php
/**
 * Classe Object_Template
 *
 */

class Object_Template_Model extends DBObject {
  // Champs BD
  protected $_object_type_id = null;
  protected $_name = null;
  protected $_attack = null;
  protected $_protection = null;
  protected $_durability = null;
  protected $_heal = null;

  public function __construct($id = null) {
    parent::__construct($id);
  }

  /* ACCESSEURS */
  public static function get_table_name() { return "object_template"; }


  /* MUTATEURS */
  public function set_id($id) {
    if( is_numeric($id) && (int)$id == $id) $data = intval($id); else $data = null; $this->_id = $data;
  }
  public function set_object_type_id($object_type_id) {
    if( is_numeric($object_type_id) && (int)$object_type_id == $object_type_id) $data = intval($object_type_id); else $data = null; $this->_object_type_id = $data;
  }
  public function set_attack($attack) {
    if( is_numeric($attack) && (int)$attack == $attack) $data = intval($attack); else $data = null; $this->_attack = $data;
  }
  public function set_protection($protection) {
    if( is_numeric($protection) && (int)$protection == $protection) $data = intval($protection); else $data = null; $this->_protection = $data;
  }
  public function set_durability($durability) {
    if( is_numeric($durability) && (int)$durability == $durability) $data = intval($durability); else $data = null; $this->_durability = $data;
  }
  public function set_heal($heal) {
    if( is_numeric($heal) && (int)$heal == $heal) $data = intval($heal); else $data = null; $this->_heal = $data;
  }

  /* FONCTIONS SQL */

  public static function db_exists ($id) { return self::db_exists_class($id, get_class());}
  public static function db_get_by_id($id) { return self::db_get_by_id_class($id, get_class());}

  public static function db_get_all($page = null, $limit = NB_PER_PAGE) {
    $sql = 'SELECT `id` FROM `'.self::get_table_name().'` ORDER BY `id`';

    if(!is_null($page) && is_numeric($page)) {
      $start = ($page - 1) * $limit;
      $sql .= ' LIMIT '.$start.','.$limit;
    }

    return self::sql_to_list($sql, get_class());
  }

  public static function db_count_all() {
    $sql = "SELECT COUNT(`id`) FROM `".self::get_table_name().'`';
    $res = mysql_uquery($sql);
    return array_pop(mysql_fetch_row($res));
  }

  public static function db_get_select_list() {
    $return = array();

    $object_list = Object_Template_Model::db_get_all();
    foreach( $object_list as $object ) $return[ $object->get_id() ] = $object->get_name();

    return $return;
  }

  /* FONCTIONS HTML */

  public static function manage_errors($tab_error, &$html_msg) { return self::manage_errors_class($tab_error, $html_msg, get_class());}

  /**
   * Formulaire d'édition partie Administration
   *
   * @param string $form_url URL de la page action
   * @return string
   */
  public function html_get_form($form_url) {
    $return = '
    <fieldset>
      <legend>Text fields</legend>
        '.HTMLHelper::genererInputHidden('id', $this->get_id()).'';
      $option_list = array();
      $object_type_list = Object_Type::db_get_all();
      foreach( $object_type_list as $object_type)
        $option_list[ $object_type->id ] = $object_type->name;

      $return .= '
      <p class="field">'.HTMLHelper::genererSelect('object_type_id', $option_list, $this->get_object_type_id(), array(), "Object Type Id *").'<a href="'.get_page_url('admin_object_type_mod').'">Créer un objet Object Type</a></p>
        <p class="field">'.HTMLHelper::genererInputText('name', $this->get_name(), array(), "Name").'</p>
        <p class="field">'.HTMLHelper::genererInputText('attack', $this->get_attack(), array(), "Attack").'</p>
        <p class="field">'.HTMLHelper::genererInputText('protection', $this->get_protection(), array(), "Protection").'</p>
        <p class="field">'.HTMLHelper::genererInputText('durability', $this->get_durability(), array(), "Durability").'</p>
        <p class="field">'.HTMLHelper::genererInputText('heal', $this->get_heal(), array(), "Heal").'</p>
    </fieldset>';

    return $return;
  }

/**
 * Retourne la chaîne de caractère d'erreur en fonction du code correspondant
 *
 * @see Member->check_valid
 * @param int $num_error Code d'erreur
 * @return string
 */
  public static function get_message_erreur($num_error) {
    switch($num_error) { 
      case 1 : $return = "Le champ <strong>Object Type Id</strong> est obligatoire."; break;
      default: $return = "Erreur de saisie, veuillez vérifier les champs.";
    }
    return $return;
  }

  /**
   * Effectue les vérifications basiques pour mettre à jour les champs
   * Retourne true si pas d'erreur, une liste de codes d'erreur sinon :
   *
   * @param int $flags Flags augmentant l'étendue des tests
   * @return true | array
   */
  public function check_valid($flags = 0) {
    $return = array();

    $return[] = Member::check_compulsory($this->get_object_type_id(), 1);

    $return = array_unique($return);
    if(($true_key = array_search(true, $return, true)) !== false) {
      unset($return[$true_key]);
    }
    if(count($return) == 0) $return = true;
    return $return;
  }





  // CUSTOM

  //Custom content

  // /CUSTOM

}