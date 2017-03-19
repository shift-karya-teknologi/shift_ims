<?php

render('layout', [
  'title'   => 'Apps',
  'sidenav' => render('sidenav', true),
  'content' => render('index', true),
]);
