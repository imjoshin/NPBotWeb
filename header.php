<?php session_start(); ?>
<html>
<head>
  <title>NP Slackbot</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">
  <link rel="icon" href="dist/img/icon.png">
  <link href="dist/css/font-awesome.min.css" rel="stylesheet" />
  <link href="dist/css/foundation.min.css" rel="stylesheet" />
  <link href="dist/css/app.css" rel="stylesheet" media="screen" />
  <script src="dist/js/jquery-3.2.1.min.js"></script>
  <script src="dist/js/foundation.min.js"></script>
  <script src="dist/js/app.js"></script>

</head>

<body>
<div id="header"></div>
<div id="wrapper" <?php echo isset($_SESSION['username']) ? "" : "class='wrapper-login'"; ?>>
