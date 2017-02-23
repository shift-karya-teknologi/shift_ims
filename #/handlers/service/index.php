<?php

render('layout', [
  'title'   => 'Dasbor Servis',
  'sidenav' => render('service/sidenav', true),
  'content' => render('service/home', [
  ], true),
]);
