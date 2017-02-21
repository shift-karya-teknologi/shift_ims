<?php

render('layout', [
  'title'   => 'Shift Apps',
  'sidenav' => render('sidenav', true),
  'content' => render('home', true),
]);
