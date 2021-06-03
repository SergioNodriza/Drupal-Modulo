<?php

namespace Drupal\fichaje_module\Service;

class TimeService {

  const diasSemana = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sáb', 'Dom'];

  public function formatDate($date)
  {
    $numDia = date('N', strtotime($date));
    $dia = self::diasSemana[$numDia-1];

    return $dia . ', ' . date('d/m/Y H:i:s', strtotime($date));
  }

  public function timeToSeconds($time)
  {
    $totalSeconds = 0;

    if ($time !== '') {

      list($hour,$minute,$second) = explode(':', $time);
      $totalSeconds += $hour*3600;
      $totalSeconds += $minute*60;
      $totalSeconds += $second*1;

    }
    return $totalSeconds;
  }
}
