<?php
/**
 * Class World
 *
 */

require_once( DATA."model/world_model.class.php" );

class World extends World_Model {

  // CUSTOM

  protected $territories = null;
  protected $_generation_method = 'voronoi';

  const TILE_DIR = 'cache/world/';

  public function __construct($id = null) {
    $this->_generation_parameters = serialize(array());
    parent::__construct($id);
  }

  public function get_generation_parameters( $all = false ) {
    $simple_defaults = array(
      'minVertexDist' => 100,
      'maxVertexDist' => 110,
      'relationNumber' => 2,
      'maxEdgeDist' => 150,
      'mountainDensity' => 10,
      'lakeDensity' => 5
    );
    $voronoi_defaults = array(
      'distFromEdges' => 50,
      'territoryDensityDelta' => 0,
      'mountainDensity' => 10,
      'lakeDensity' => 5
    );
    if( $all ) {
      $defaults = array_merge( $simple_defaults, $voronoi_defaults );
    }else {
      switch( $this->generation_method ) {
        case 'simple' : {
          $defaults = $simple_defaults;
          break;
        }
        case 'voronoi' : {
          $defaults = $voronoi_defaults;
          break;
        }
      }
    }

    $options = unserialize($this->_generation_parameters);

    foreach( $defaults as $key => $value ) {
      if( !isset($options[$key] ) ) $options[ $key ] = $value;
    }
    return $options;
  }
  public function set_generation_parameters($params) { $this->_generation_parameters = serialize($params);}

  public function get_territory_count() {
    return Territory::db_get_count_by_world_id($this->id);
  }

  public function get_territories() {
    if( is_null( $this->territories )) {
      $this->territories = Territory::db_get_by_world_id($this->id);
    }
    return $this->territories;
  }

  public function initialize_territories() {
    $return = true;

    if( is_null( $this->id ) ) {
      $return = $this->save();
    }

    if( $return ) {
      foreach (glob(DIR_ROOT . self::TILE_DIR . '/tile_'.$this->id.'*') as $removeFile) {
        unlink($removeFile);
      }
      foreach (glob(DIR_ROOT . self::TILE_DIR . '/'.$this->id.'/*') as $removeFile) {
        if(is_file($removeFile))
          unlink($removeFile);
      }

      $options = $this->generation_parameters;

      $method = 'territory_generation_'.$this->generation_method;
      $territories = $this->$method( $options );

      $this->territories = $territories;

      Territory::db_remove_by_world_id( $this->id );

      $sea_nb = 1;
      foreach( $this->get_territories() as $key => $territory ) {
        $territory_areas[ 'territory_'.$key ] = $territory->area;
        foreach( $territory->vertices as $vertex ) {
          if( $vertex->x == 0 || $vertex->y == 1 || $vertex->x == $this->size_x - 1 || $vertex->y == $this->size_y ) {
            $sea_territories[] = 'territory_'.$key;
            unset( $territory_areas[ 'territory_'.$key ] );
            break;
          }
        }
      }
      $total_territories = count( $territory_areas );
      asort( $territory_areas );
      $territory_areas = array_keys( $territory_areas );

      $margin_percent_lakes = max( 0, min( $options['lakeDensity'], 50)) / 100;
      $margin_percent_moutains = max( 0, min( $options['mountainDensity'], 50)) / 100;
      $margin_count = floor( $total_territories * $margin_percent_moutains );
      $moutain_territories = array_slice($territory_areas, 0, $margin_count);
      $margin_count = floor( $total_territories * $margin_percent_lakes );
      $lake_territories = $margin_count == 0?array():array_slice( $territory_areas, - $margin_count );

      $mountain_nb = 1;
      $lake_nb = 1;

      foreach( $this->get_territories() as $key => $territory ) {
        if(array_search('territory_'.$key, $sea_territories) !== false) {
          $territory->name = 'Sea '.$sea_nb++;
          $territory->capital_name = 'Sea '.$sea_nb;
          $territory->passable = 1;
          $territory->capturable = 0;
          $territory->background = 'sea';
        }elseif (array_search('territory_'.$key, $lake_territories) !== false) {
          $territory->name = 'Lake '.$lake_nb++;
          $territory->capital_name = 'Lake '.$lake_nb;
          $territory->passable = 1;
          $territory->capturable = 0;
          $territory->background = 'sea';
        }elseif( array_search('territory_'.$key, $moutain_territories) !== false ) {
          $territory->name = 'Mountain '.$mountain_nb++;
          $territory->capital_name = 'Mountain '.$mountain_nb;
          $territory->passable = 0;
          $territory->capturable = 0;
          $territory->background = 'mountain';
        }elseif( $territory->name === null ) {
          $territory->name = Territory::get_random_country_name();
          $territory->capital_name = Territory::get_random_capital_name();
          $territory->passable = 1;
          $territory->capturable = 1;
        }
        $territory->world_id = $this->id;
        $territory->save();
      }

      $this->generate_neighbour_data();
    }
    return $return;
  }

  public function generate_neighbour_data() {
    $neighbour_array = array();
    foreach( $this->get_territories() as $territory ) {
      $vertices = $territory->vertices;
      $currentVertex = array_shift( $vertices );
      $vertices[] = $currentVertex;
      foreach( $vertices as $vertex ) {
        $neighbour_array[ $currentVertex->guid ][ $vertex->guid ][] = $territory;
        $neighbour_array[ $vertex->guid ][ $currentVertex->guid ][] = $territory;
        $currentVertex = $vertex;
      }
    }

    foreach( $neighbour_array as $startVertexGuid => $array ) {
      foreach( $array as $endVertexGuid => $neighbours ) {
        if( count( $neighbours ) == 2 && $neighbours[1]->id != $neighbours[0]->id ) {
          $neighbours[0]->set_territory_neighbour( $neighbours[1]->id, $startVertexGuid, $endVertexGuid );
          $neighbours[1]->set_territory_neighbour( $neighbours[0]->id, $startVertexGuid, $endVertexGuid );
        }
      }
    }
  }

  public function territory_generation_simple( array $options = null) {
    $return = null;

    if( $options === null ) {
      $options = $this->generation_parameters;
    }

    $graph = new Graph();
    $graph->randomVertexGenerationDisk( $this->size_x, $minVertexDist, $maxVertexDist );
    $graph->randomNoncrossingEdgeGeneration( $relationNumber, $maxEdgeDist );

    $return = Territory::find_in_graph( $graph );

    unset( $graph );

    return $return;
  }

  public function territory_generation_voronoi( array $options = null ) {
    $return = null;

    if( $options === null ) {
      $options = $this->generation_parameters;
    }

    require_once LIB.'php-voronoi/library/Nurbs/Voronoi.php';
    require_once LIB.'php-voronoi/library/Nurbs/Point.php';

    $bbox = new stdClass();
    $bbox->xl = 0;
    $bbox->xr = $this->size_x - 1;
    $bbox->yt = 1;
    $bbox->yb = $this->size_y;

    $xo = $options['distFromEdges'];
    $dx = $this->size_x - $options['distFromEdges'];
    $yo = $options['distFromEdges'];
    $dy = $this->size_y - $options['distFromEdges'];

    $territoryDensityDelta = (max( -20, min( 20, $options['territoryDensityDelta'])) + 70) / 100;
    $n = floor( $territoryDensityDelta * $this->size_x * $this->size_y / 10000 );
    $sites = array();

    //$im = imagecreatetruecolor($width, $height);
    //$white = imagecolorallocate($im, 255, 255, 255);
    //$red = imagecolorallocate($im, 255, 0, 0);
    //$green = imagecolorallocate($im, 0, 100, 0);
    //$black = imagecolorallocate($im, 0, 0, 0);
    //imagefill($im, 0, 0, $white);
    if( 1 == 2 ) {
      $graph = new Graph();
      $graph->randomVertexGenerationDisk( $this->size_x, $options['minVertexDist'], $options['maxVertexDist'] );
      foreach( $graph->vertices as $vertex ) {
        $sites[] = new Nurbs_Point($vertex->x, $vertex->y);
      }
    }

    for ($i=0; $i < $n; $i++) {
      $point = new Nurbs_Point(rand($xo, $dx), rand($yo, $dy));
      $sites[] = $point;
    }

    $voronoi = new Voronoi();
    $diagram = $voronoi->compute($sites, $bbox);
    $territory_list = array();

    foreach ($diagram['cells'] as $cell) {
      // On va agreger les points du polygone.
      $points = array();
      $vertices = array();

      if (count($cell->_halfedges) > 0) {
        $v = $cell->_halfedges[0]->getStartPoint();
        if ($v) {
          $points[] = $v->x;
          $points[] = $v->y;
          $vertices[] = new Vertex(round($v->x), round($v->y));
        } else {
          var_dump($j.': no start point');
        }

        for ($i = 0; $i < count($cell->_halfedges); $i++) {
          $halfedge = $cell->_halfedges[$i];
          $edge = $halfedge->edge;

          if ($edge->va && $edge->vb) {
            //imageline($im, $edge->va->x, $edge->va->y, $edge->vb->x, $edge->vb->y, $red);
          }

          $v = $halfedge->getEndPoint();
          if ($v) {
            $points[] = $v->x;
            $points[] = $v->y;
            $vertices[] = new Vertex(round($v->x), round($v->y));
          } else {
            var_dump($j.': no end point #'.$i);
          }
        }
      }

      // On construit le polygone
      //$color = imagecolorallocatealpha($im, rand(0, 255), rand(0, 255), rand(0, 255), 50);
      //imagefilledpolygon($im, $points, count($points) / 2, $color);

      if( count( $vertices ) > 3 ) {
        $territory = new Territory();
        $territory->vertices = $vertices;
        $territory_list[] = $territory;
      }
    }
    $return = $territory_list;

    return $return;
  }

  function get_territories_distances_from( Territory $source_territory ) {
    $dijkstra = $this->get_shortest_paths_from($source_territory);

    $territories = array();
    foreach( $this->get_territories() as $territory ) {
      if( $territory->is_passable() && $territory != $source_territory ) {
        $territories[ $territory->id ] = $territory;
      }
    }

    $return = array();
    foreach( array_keys( $territories ) as $territory_id ) {
      $current_territory_id = $territory_id;
      $distance = 0;
      do {
        $previous_territory_id = $dijkstra[ $current_territory_id ];
        $current_territory_id = $previous_territory_id;
        $distance++;
      }while( $current_territory_id != $source_territory->id );
      $return[ $territory_id ] = $distance;
    }

    return $return;
  }

  function get_shortest_paths_from( Territory $sourceTerritory ) {
    $territories = array();
    $links = array();

    foreach( $this->get_territories() as $territory ) {
      if( $territory->is_passable() ) {
        $territories[ $territory->id ] = $territory;
        foreach( $territory->get_territory_neighbour_list() as $territory_neighbour_row ) {
          $neighbour = Territory::instance( $territory_neighbour_row['neighbour_id'] );
          if( $neighbour->is_passable() ) {
            $distance = Vertex::distance( $territory->get_centroid(), $neighbour->get_centroid() );
            $links[ $territory->id ][ $neighbour->id ] = $distance + 10000;
            $links[ $neighbour->id ][ $territory->id ] = $distance + 10000;
          }
        }
      }
    }

    return $sourceTerritory->get_shortest_paths_to($territories, $links);
  }

  private function cache_draw( $filepath, $options = array(), $direct_output = false, $force = false ) {
    if( !is_file( DIR_ROOT . $filepath ) || $force ) {
      $image = $this->draw( $options );
      imagepng($image, DIR_ROOT . $filepath );
      $last_modified = time();
    }else {
      $last_modified = filemtime(DIR_ROOT . $filepath);
    }

    if( $direct_output ) {
      header('Pragma: cache');
      header('Cache-Control: public');
      header('Last-Modified: '. gmdate('D, d M Y H:i:s', $last_modified) .' GMT');
      //header('Expires: '. gmdate('D, d M Y H:i:s', time() + 60*60*24*30) .' GMT');
      header('Content-type: image/png');
      readfile( DIR_ROOT . $dir . $filename );
    }
  }

  private function draw( $options = array() ) {
    extract( $options );

    $font = DIR_ROOT.'/style/arial.ttf';

    $img = imagecreatetruecolor( ($size_x - $offset_x) * $ratio, ($size_y - $offset_y) * $ratio);

    // Fill the image with transparent color
    $transparent = imagecolorallocatealpha($img,0x00,0x00,0x00,127);
    $veil = imagecolorallocatealpha($img,0x00,0x00,0x00,40);

    $white = imagecolorallocate($img, 255, 255, 255);
    $red = imagecolorallocate($img, 255, 0, 0);
    $green = imagecolorallocate($img, 0, 255, 0);
    $blue = imagecolorallocate($img, 0, 0, 255);
    $black = imagecolorallocatealpha($img, 0, 0, 0, 0);
    $grey = imagecolorallocate($img, 211, 211, 211);
    $darkgrey = imagecolorallocate($img, 17, 17, 17);

    $sand = imagecolorallocate($img, 255, 200, 0);
    //$earth = imagecolorallocate($img, 50, 21, 12);
    $earth = imagecolorallocate($img, 101, 159, 0);

    $marine = imagecolorallocatealpha($img, 0, 185, 255, 100);
    $sea = imagecolorallocate($img, 0, 161, 185);

    $player_colors = array(
        imagecolorallocate($img,   0, 255, 255),
        imagecolorallocate($img, 255,   0, 138),
        imagecolorallocate($img, 126, 255,   0),
        imagecolorallocate($img, 255, 108,   0),
        imagecolorallocate($img, 120,   0, 255),
        imagecolorallocate($img, 255, 240,   0),
        imagecolorallocate($img, 255,  42,   0),
        imagecolorallocate($img,   0, 102, 255),
        imagecolorallocate($img, 255, 107, 187),
        imagecolorallocate($img, 252, 156,  85),
        imagecolorallocate($img, 184, 123, 254),
        imagecolorallocate($img, 255,  92,  62),
        imagecolorallocate($img, 104, 164, 255),
    );
    $players = array();
    if( $game_id !== null ) {
      /* @var $game Game */
      $game = Game::instance($game_id);
      foreach( $game->get_game_player_list() as $key => $game_player_row ) {
        $player = Player::instance($game_player_row['player_id']);
        $player_color_index[ $player->id ] = $key;
        $players[] = $player;
      }
    }

    imagefill($img, 0, 0, $darkgrey);

    $territory_owner = array();
    foreach( $territories as $key => $area ) {
      $polygon = array();
      foreach( $area->vertices as $pointAire ) {
        $polygon[] = ($pointAire->x - $offset_x) * $ratio;
        $polygon[] = self::y( $size_y, $offset_y, $pointAire->y ) * $ratio;
      }

      if( $game_id !== null ) {
        $owner = $area->get_owner( $game, $turn );
        $territory_owner[ $area->id ] = $owner->id;
      }

      // Territory fill
      if( $area->background === null ) {
        $color = $black;
        if( $game_id !== null) {
          $territory_status = $area->get_territory_status( $game, $turn );

          $is_contested = $territory_status['contested'];
          $is_conflict = $territory_status['conflict'];
          $is_capital = $territory_status['capital'];


          if( $owner->id ) {
            if( $is_capital ) {
              $color = imagecolortoalpha( $img, $player_colors[ $player_color_index[ $owner->id ] ], 42);
            }else {
              $color = imagecolortoalpha( $img, $player_colors[ $player_color_index[ $owner->id ] ], 85);
            }

          }
          imagefilledpolygon( $img, $polygon, count( $area->vertices ), $color );
          if( $is_conflict ) {
            $tile_conflict = imagecreatefrompng( DIR_ROOT.'img/img_css/conflict.png');
            imagesettile($img, $tile_conflict);
            imagefilledpolygon( $img, $polygon, count( $area->vertices ), IMG_COLOR_TILED );
          }elseif( $is_contested ) {
            // Conflict overlay
            if( $owner->id ) {
              $tile_contested = imagecreatefrompng( DIR_ROOT.'img/img_css/contested.png');
            }else {
              imagefilledpolygon( $img, $polygon, count( $area->vertices ), $color );
              $tile_contested = imagecreatefrompng( DIR_ROOT.'img/img_css/contested_inverse.png');
            }
            imagesettile($img, $tile_contested);
            imagefilledpolygon( $img, $polygon, count( $area->vertices ), IMG_COLOR_TILED );
          }
        }else {
          imagefilledpolygon( $img, $polygon, count( $area->vertices ), $color );
        }
      }else {
        $tile_background = imagecreatefrompng( DIR_ROOT.'img/img_css/'.$area->background.'.png');
        imagesettile($img, $tile_background);
        imagefilledpolygon( $img, $polygon, count( $area->vertices ), IMG_COLOR_TILED );
        imagepolygon( $img, $polygon, count( $area->vertices ), $marine );
      }
    }

    // Territory borders
    foreach( $territories as $key => $area ) {
      if( $area->background !== 'sea' ) {
        $color = $white;
        if( $game_id !== null ) {
          $owner->id = $territory_owner[ $area->id ];
          if( $owner->id ) {
            $color = $player_colors[ $player_color_index[ $owner->id ] ];
          }
        }

        $lastPoint = $area->vertices[ count( $area->vertices ) - 1 ];
        foreach( $area->vertices as $point ) {
          $neighbour = $area->get_neighbour( $lastPoint->guid, $point->guid );
          $glow = 5;
          if( $game_id !== null && $neighbour && $neighbour->id && array_key_exists( $neighbour->id, $territory_owner ) && $territory_owner[ $area->id ] == $territory_owner[ $neighbour->id ] ) {
            $glow = 0;
            //imageline($img, ($lastPoint->x - $offset_x) * $ratio, self::y($size_y, $offset_y, $lastPoint->y) * $ratio, ($point->x - $offset_x) * $ratio, self::y($size_y, $offset_y, $point->y) * $ratio, $color);
          }

          imagelineglow($img, ($lastPoint->x - $offset_x) * $ratio, self::y($size_y, $offset_y, $lastPoint->y) * $ratio, ($point->x - $offset_x) * $ratio, self::y($size_y, $offset_y, $point->y) * $ratio, $color, $glow);
          $lastPoint = $point;
        }
      }else {
        //imagepolygon( $img, $polygon, count( $area->vertices ), $marine );
      }
    }

    // Territory names
    if( !$no_names ) {
      foreach( $territories as $key => $area ) {
        $centroid = $area->get_centroid();

        $name = $area->name;

        $font_size = round( min( max( 7, 10 * $ratio), 12 ) );

        $bbox = imagettfbbox( $font_size, 0, $font, $name );
        $textwidth = $bbox[2] - $bbox[0];
        $textheight = $bbox[5] - $bbox[1];

        $bot_left_x = ($centroid->x - $offset_x) * $ratio - $textwidth / 2;
        $bot_left_y = self::y($size_y, $offset_y, $centroid->y) * $ratio;

        // Ajout d'ombres au texte
        //imagettftext($img, $font_size, 0, (($centroid->x - $offset_x) * $ratio - $textwidth / 2) + 1, self::y($size_y, $offset_y, $centroid->y ) * $ratio + 1, $grey, $font, $name);
        imagefilledrectangle($img, $bot_left_x, $bot_left_y + 2, $bot_left_x + $textwidth, $bot_left_y + $textheight + 2, $veil);

        // Ajout du texte
        imagettftext($img, $font_size, 0, $bot_left_x, $bot_left_y, $white, $font, $name);
      }
    }

    if( $game_id != null ) {
      foreach( $players as $key => $player ) {
        imagefilledpolygon( $img,
          array(
              5, $key * 15 + 5,
              25, $key * 15 + 5,
              25, $key * 15 + 15,
              5, $key * 15 + 15
          ),
          4,
          imagecolortoalpha($img, $player_colors[ $player_color_index[ $player->id ] ], 30)
        );

        // Ajout d'ombres au texte
        //imagettftext($img, 10, 0, 30 + 1, $key * 15 + 15 +1, $grey, $font, $player->name);

        // Ajout du texte
        imagettftext($img, 10, 0, 30, $key * 15 + 15, $white, $font, $player->name);
      }
    }

    imagefilter($img, IMG_FILTER_SMOOTH, 10);

    return $img;
  }

  public static function y($size_y, $offset_y, $y) {
    return $size_y - $y;
  }

  public function getImgUrl( &$options = array() ) {
    $defaults = array(
      'with_map' => false,
      'territories' => null,
      'game_id' => null,
      'turn' => null,
      'size_x' => $this->size_x,
      'size_y' => $this->size_y,
      'offset_x' => 0,
      'offset_y' => 0,
      'ratio' => 1,
      'force' => false,
      'no_names' => false
    );

    foreach( $defaults as $key => $value ) {
      if( !isset($options[$key] ) ) $options[ $key ] = $value;
    }

    if( $options['game_id'] !== null ) {
      $game = Game::instance($options['game_id']);
      if( $options['turn'] === null ) $options['turn'] = $game->current_turn;
    }

    if( $options['territories'] === null ) {
      $options['territories'] = $this->get_territories();

      if( $options['game_id'] !== null ) {
        $dir = 'cache/world/'.$this->id.'/game/'.$options['game_id'].'/';
        $filename = 'map_turn_'.$options['turn'].'_'.$options['size_x'].'x'.$options['size_y'].'_ratio_'.$options['ratio'].'.png';
      }else {
        $dir = 'cache/world/'.$this->id.'/';
        $filename = 'map_'.$options['size_x'].'_'.$options['size_y'].'.png';
      }
      if( !is_dir( DIR_ROOT . $dir ) ) mkdir($dir, 0700, true);

      $this->cache_draw($dir . $filename, $options, false, $options['force']);
      $image_src = URL_ROOT . $dir . $filename;
    }else {
      $options['size_x'] = 0;
      $options['size_y'] = 0;
      $options['offset_x'] = $this->size_x;
      $options['offset_y'] = $this->size_y;
      foreach( $options['territories'] as $key => $area ) {
        foreach( $area->vertices as $pointAire ) {
          $options['size_x'] = max( $options['size_x'], $pointAire->x );
          $options['size_y'] = max( $options['size_y'], $pointAire->y );
          $options['offset_x'] = min( $options['offset_x'], $pointAire->x );
          $options['offset_y'] = min( $options['offset_y'], $pointAire->y );
        }
      }
      $options['size_x'] = $options['size_x'] + 10;
      $options['size_y'] = $options['size_y'] + 10;
      $options['offset_x'] -= 10;
      $options['offset_y'] -= 10;

      $image = $this->draw( $options );
      ob_start();
      imagepng($image);
      $image_src = 'data:image/png;base64,'.base64_encode( ob_get_clean() );

      $options['size_x'] = $options['size_x'] - $options['offset_x'] + 10;
      $options['size_y'] = $options['size_y'] - $options['offset_y'] + 10;
    }

    return $image_src;
  }

  public function drawImg( $options = array() ) {
    $image_src = $this->getImgUrl($options);

    extract( $options );
    if( $with_map ) {
      echo '
      <map name="world">';
      foreach( $territories as $territory ) {
        $coords = array();
        foreach( $territory->vertices as $vertex ) {
          $coords[] = round(( $vertex->x - $offset_x ) * $ratio ).','. round(($size_y - ($vertex->y - $offset_y)) * $ratio);
        }
        if( $game_id ) {
          $game = Game::instance($options['game_id']);
          $owner = $territory->get_owner( $game, $turn );
          echo '
        <area
          data-territory-id="'.$territory->id.'"
          shape="polygon"
          coords="'.implode(',', $coords).'"
          href="'.Page::get_url('show_territory', array('game_id' => $game_id, 'id' => $territory->id)).'"
          title="'.$territory->name.' ('.($owner->id?$owner->name:__('Nobody')).')'.
                  ($territory->is_capital( $game, $turn )?' ['.__('Capital').']':'').
                  ($territory->is_contested( $game, $turn )?' <'.__('Contested').'>':'').
                '" />';
        }else {
          echo '
        <area
          data-territory-id="'.$territory->id.'"
          shape="polygon"
          coords="'.implode(',', $coords).'"
          href="'.Page::get_url('show_territory', array('id' => $territory->id)).'"
          title="'.$territory->name.'" />';
        }
      }
      echo '
      </map>
      <img usemap="#world" src="'.$image_src.'" class="map">';
    }else {
      echo '<img src="'.$image_src.'"/>';
    }
  }

  public function html_creation_form() {
    $generation_types = array(
      'simple' => 'Simple',
      'voronoi' => 'Voronoi'
    );

    $territory_density_delta = array(
      '-20' => 'Sparse',
      '-10' => 'Slightly Sparse',
      '0' => 'Normal',
      '+10' => 'Slightly Dense',
      '+20' => 'Dense'
    );

    $options = $this->get_generation_parameters(true);

    $return = '
    <script>
    domReadyQueue.push(function($){
      $options = $(".options");

      $("#select_generation_method").change(function(){
        show_options( $(this).val() )
      });

      show_options( $("#select_generation_method").val() );

      function show_options( type ) {
        $fieldset = $(".options." + type);
        $options.hide();
        $options.find("input,select,textarea").attr("disabled", "disabled");
        $fieldset.find("input,select,textarea").removeAttr("disabled");
        $fieldset.show();
      }
    });
    </script>
    <fieldset>
      <legend>'.__('Create a world !').'</legend>
      '.HTMLHelper::genererInputHidden('id', $this->get_id()).'
      <p class="field">'.HTMLHelper::genererInputText('name', $this->get_name(), array(), __('Name').'*').'</p>
      <p class="field">'.HTMLHelper::genererInputText('size_x', $this->get_size_x(), array(), __('Width').'*').'</p>
      <p class="field">'.HTMLHelper::genererInputText('size_y', $this->get_size_y(), array(), __('Height').'*').'</p>
      <p class="field">'.HTMLHelper::genererSelect('generation_method', $generation_types, $this->get_generation_method(), array('id' => 'select_generation_method'), __('Generation Method')).'</p>
    </fieldset>
    <fieldset class="options simple">
      <legend>'.__('Simple Generator options').'</legend>
      <p class="field">'.HTMLHelper::genererInputText('generation_parameters[minVertexDist]', $options['minVertexDist'], array(), __('Minimum point distance')).'</p>
      <p class="field">'.HTMLHelper::genererInputText('generation_parameters[maxVertexDist]', $options['maxVertexDist'], array(), __('Maximum point distance')).'</p>
      <p class="field">'.HTMLHelper::genererInputText('generation_parameters[relationNumber]', $options['relationNumber'], array(), __('Territory density')).'</p>
      <p class="field">'.HTMLHelper::genererInputText('generation_parameters[maxEdgeDist]', $options['maxEdgeDist'], array(), __('Maximum border length')).'</p>
    </fieldset>
    <fieldset class="options voronoi">
      <legend>'.__('Voronoi Generator options').'</legend>
      <p class="field">'.HTMLHelper::genererInputText('generation_parameters[distFromEdges]', $options['distFromEdges'], array(), __('Margin from map edges')).'</p>
      <p class="field">'.HTMLHelper::genererSelect('generation_parameters[territoryDensityDelta]', $territory_density_delta, $options['territoryDensityDelta'], array(), __('Territory Density')).'</p>
      <p class="field">'.HTMLHelper::genererInputText('generation_parameters[lakeDensity]', $options['lakeDensity'], array(), __('Lake Density (0-50%%)')).'</p>
      <p class="field">'.HTMLHelper::genererInputText('generation_parameters[mountainDensity]', $options['mountainDensity'], array(), __('Moutain density (0-50%%)')).'</p>
    </fieldset>';
    return $return;
  }

  // /CUSTOM

}