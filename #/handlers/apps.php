<?php

render('layout', [
  'title'   => 'Apps',
  'sidenav' => render('sidenav', true),
  'content' => render('apps', true),
]);
