<?php

render('layout', [
  'title' => 'Aktifitas',
  'sidenav' => render('shiftnet/sidenav', true),
  'content' => render('shiftnet/activity/list', [
    
  ], true)
]);