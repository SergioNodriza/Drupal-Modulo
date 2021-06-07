<?php

namespace Drupal\fichaje_module\Service;

class QueryService {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  public function queryLastFichaje($connection, $user) {
    $query = sprintf("select nftm.entity_id as id, nftm.field_type_mark_value as type, nfd.title as empresa,
                                field_date_mark_value as date, nftdm.field_time_diff_mark_value as time
                            from node__field_date_mark nfdm
                            join node__field_type_mark nftm on nfdm.entity_id = nftm.entity_id
                            join node__field_user_mark nfum on nfdm.entity_id = nfum.entity_id
                            join node__field_empresa_mark nfem on nfdm.entity_id = nfem.entity_id
                            join node_field_data nfd on nfd.nid = nfem.field_empresa_mark_target_id
                            join node__field_time_diff_mark nftdm on nfdm.entity_id = nftdm.entity_id
                            where nfum.field_user_mark_target_id like '%s'
                            order by nfdm.field_date_mark_value desc limit 1", $user->id());
    return $connection->query($query)->fetch(\PDO::FETCH_ASSOC);
  }
  public function queryFichajesUsuario($connection, $params = []) {
    $query = sprintf("select nfdm.field_date_mark_value as date, nftm.field_type_mark_value as type,
                                nfd.title as empresa, coalesce(nftdm.field_time_diff_mark_value, '') as time
                            from node__field_user_mark nfum
                                     join node__field_date_mark nfdm on nfum.entity_id = nfdm.entity_id
                                     join node__field_type_mark nftm on nfdm.entity_id = nftm.entity_id
                                     join node__field_empresa_mark nfem on nfdm.entity_id = nfem.entity_id
                                     join node_field_data nfd on nfd.nid = nfem.field_empresa_mark_target_id
                                     left join node__field_time_diff_mark nftdm on nfdm.entity_id = nftdm.entity_id
                            where nfum.field_user_mark_target_id like '%s'", $params['userId']);

    if ($params['empresaName']) {
      $query .= sprintf(" and nfd.title like '%s'", $params['empresaName']);
    }

    if ($params['date_filter']) {
      $query .= sprintf(" and nfdm.field_date_mark_value like '%s'", $params['date_filter'] . '%');
    }

    if ($params['week_filter']) {
      $query .= sprintf(" and extract(week from nfdm.field_date_mark_value) = '%s'", $params['week_filter']);
      $query .= sprintf(" and extract(year from nfdm.field_date_mark_value) = '%s'", $params['year_filter']);
    }

    $query .= " order by nfdm.field_date_mark_value desc";

    return $connection->query($query)->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function queryIdEmpresa($connection, $empresaName) {
    $query = sprintf("select nid from node_field_data where title like '%s'", $empresaName);
    return $connection->query($query)->fetch(\PDO::FETCH_COLUMN);
  }
  public function queryEmpresasIds($connection, $user) {
    $query = sprintf("select field_empresas_user_target_id from user__field_empresas_user where entity_id like '%s'", $user->id());
    return $connection->query($query)->fetchAll(\PDO::FETCH_COLUMN);
  }

  public function queryUsersIds($connection) {
    $queryUser = "select uid from users where uid not like 0 and uid not like 30";
    return $connection->query($queryUser)->fetchAll(\PDO::FETCH_COLUMN);
  }
  public function queryUserHours($connection, $userId) {
    $query = sprintf("select field_hours_day_value from user__field_hours_day where entity_id like '%s'", $userId);
    return $connection->query($query)->fetch(\PDO::FETCH_COLUMN);
  }
}
