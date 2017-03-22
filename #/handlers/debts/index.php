<?php

ensure_current_user_can('open-debts-app');

$data = [];

render('debts/index', $data);