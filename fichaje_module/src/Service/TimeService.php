<?php

namespace Drupal\fichaje_module\Service;

use DateTime;

class TimeService {

  const diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

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

      [$hour,$minute,$second] = explode(':', $time);
      $totalSeconds += $hour*3600;
      $totalSeconds += $minute*60;
      $totalSeconds += $second*1;

    }
    return $totalSeconds;
  }

  public function doubleValue($time)
  {
    if (strlen($time) <2) {
      $time = '0' . $time;
    }

    return $time;
  }

  public function timeDiff($lastTime, $date = FALSE) {

    $newLastTime = new DateTime($lastTime);
    if ($date) {

      $newDate = new DateTime($date);
      if ($newLastTime > $newDate) {
        return FALSE;
      }
      $diff = $newLastTime->diff($newDate);

    }
    else {
      $diff = $newLastTime->diff(new DateTime());
    }

    $daysInSecs = $diff->format('%r%a') * 24 * 60 * 60;
    $hoursInSecs = $diff->h * 60 * 60;
    $minsInSecs = $diff->i * 60;

    $totalSeconds = ($daysInSecs + $hoursInSecs + $minsInSecs + $diff->s);

    $hours = floor($totalSeconds / 3600);
    $hours = $this->doubleValue($hours);

    $totalSeconds -= $hours * 3600;
    $minutes = floor($totalSeconds / 60);
    $minutes = $this->doubleValue($minutes);

    $totalSeconds -= $minutes * 60;
    $totalSeconds = $this->doubleValue($totalSeconds);

    return "$hours:$minutes:$totalSeconds";
  }
}
