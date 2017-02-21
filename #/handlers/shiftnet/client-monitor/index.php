<?php

render('layout', [
  'title' => 'Client Monitor',
  'sidenav' => render('shiftnet/sidenav', true),
  'content' => render('shiftnet/client-monitor/list', [
    
  ], true)
]);