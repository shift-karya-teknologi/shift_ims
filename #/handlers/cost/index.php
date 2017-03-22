<?php

ensure_current_user_can('open-operational-cost-app');

$data = [];

render('cost/index', $data);