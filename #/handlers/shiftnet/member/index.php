<?php

$members = $db->query('select'
  . ' id, username, active, remainingDuration'
  . ' from shiftnet_members'
  . ' order by username asc')
  ->fetchAll(PDO::FETCH_OBJ);

render('layout', [
  'title'   => 'Daftar Member',
  'sidenav' => render('shiftnet/sidenav', true),
  'headnav' => '
    <a href="./add" class="mdl-button mdl-button--icon mdl-js-button mdl-js-ripple-effect">
      <i class="material-icons">add</i>
    </a>',
  'content' => render('shiftnet/member/list', [
    'members' => $members
  ], true)
]);