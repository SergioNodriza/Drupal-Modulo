<?php

namespace Drupal\fichaje_module\Service;

class QueryService {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';
  private $timeService;

  public function __construct()
  {
    $this->timeService = \Drupal::service('fichaje_module.time_service');
  }

  public function queryLastFichaje($connection, $user)
  {
    $query = sprintf("select nftm.field_type_mark_value as type, nfd.title as name
                            from node__field_date_mark nfdm
                            join node__field_type_mark nftm on nfdm.entity_id = nftm.entity_id
                            join node__field_user_mark nfum on nfdm.entity_id = nfum.entity_id
                            join node__field_empresa_mark nfem on nfdm.entity_id = nfem.entity_id
                            join node_field_data nfd on nfd.nid = nfem.field_empresa_mark_target_id
                            where nfum.field_user_mark_target_id like '%s'
                            order by nfdm.field_date_mark_value desc limit 1", $user->id());
    return $connection->query($query)->fetch(\PDO::FETCH_ASSOC);
  }

  public function queryIdEmpresa($connection, $empresaName)
  {
    $query = sprintf("select nid from node_field_data where title like '%s'", $empresaName);
    return $connection->query($query)->fetch(\PDO::FETCH_COLUMN);
  }

  public function queryTimeDiff($connection, $user)
  {
    $query = sprintf("select nfdm.field_date_mark_value as date
                              from node__field_date_mark nfdm
                                join node__field_user_mark nfum on nfum.entity_id = nfdm.entity_id
                                join node__field_type_mark nftm on nfdm.entity_id = nftm.entity_id
                              where nfum.field_user_mark_target_id like '%s' and nftm.field_type_mark_value like '%s'
                              order by nfdm.field_date_mark_value desc limit 1;", $user->id(), self::typeOpen);

    $lastTime = new \DateTime($connection->query($query)->fetch(\PDO::FETCH_COLUMN));
    $diff = $lastTime->diff(new \DateTime());

    $daysInSecs = $diff->format('%r%a') * 24 * 60 * 60;
    $hoursInSecs = $diff->h * 60 * 60;
    $minsInSecs = $diff->i * 60;

    $totalSeconds = ($daysInSecs + $hoursInSecs + $minsInSecs + $diff->s);

    $hours = floor($totalSeconds/3600);
    $hours = $this->timeService->doubleValue($hours);

    $totalSeconds -= $hours*3600;
    $minutes  = floor($totalSeconds/60);
    $minutes = $this->timeService->doubleValue($minutes);

    $totalSeconds -= $minutes*60;
    $totalSeconds = $this->timeService->doubleValue($totalSeconds);

    return "$hours:$minutes:$totalSeconds";
  }

  public function queryFichajesUsuario($connection, $user, $empresaName, $dateFilter)
  {
    $query = sprintf("select nfdm.field_date_mark_value as date, nftm.field_type_mark_value as type,
                                nfd.title as empresa, coalesce(nftdm.field_time_diff_mark_value, '') as time
                            from node__field_user_mark nfum
                                     join node__field_date_mark nfdm on nfum.entity_id = nfdm.entity_id
                                     join node__field_type_mark nftm on nfdm.entity_id = nftm.entity_id
                                     join node__field_empresa_mark nfem on nfdm.entity_id = nfem.entity_id
                                     join node_field_data nfd on nfd.nid = nfem.field_empresa_mark_target_id
                                     left join node__field_time_diff_mark nftdm on nfdm.entity_id = nftdm.entity_id
                            where nfum.field_user_mark_target_id like '%s' and nfd.title like '%s' and nfdm.field_date_mark_value like '%s'
                            order by nfdm.field_date_mark_value desc", $user->id(), $empresaName, $dateFilter);

    return $connection->query($query)->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function queryEmpresasIds($connection)
  {
    $query = "select nid from node where type like 'empresa'";
    return $connection->query($query)->fetchAll(\PDO::FETCH_COLUMN);
  }
}
