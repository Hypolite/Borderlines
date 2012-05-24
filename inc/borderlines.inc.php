<?php

function spygame( $spy1, $spy2, $value ) {
  $return = null;
  
  $diff = $spy1 - $spy2;
  $diffPercent = 100 * $spy1 / $spy2;
  $diffAddPercent = $spy1 / ($spy1 + $spy2);
  
  $uncoveringValueChance = $diffAddPercent;
  
  $missionResult = mt_gaussrand() * 0.25 + 0.5;

  if( $missionResult < $uncoveringValueChance ) {
    if( $missionResult < 0 ) $missionResult = 0; // R�ussite parfaite
    $factor = mt_gaussrand() * 0.3;
    $deviation = $factor * $missionResult / $uncoveringValueChance;
    
    $return = round( $value * (1 + $deviation) );
  }
  
  return $return;
}