<?php

if ($_SESSION['CURRENT_USER']) {
  // TODO: log user activity
}

session_destroy();

header('Location: ' . LOGIN_URL);