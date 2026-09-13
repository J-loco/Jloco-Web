<!DOCTYPE html>
<html lang="fr">

<head>
	<!-- Meta -->
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0; user-scalable=0;">

	<title><?= e(TITLE) ?></title>
	
    <!-- Favicon -->
    <link rel="shortcut icon" href="img/favicon.ico">
	
	<!-- Core CSS -->
    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
	<link href="css/themes/blue.css" rel="stylesheet" id="themes" />
	
	<!-- Plugins -->
    <link href="plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
    <link href="plugins/ionicons/css/ionicons.min.css" rel="stylesheet" />
	<link href="plugins/animate/animate.min.css" rel="stylesheet" >
	<link href="plugins/bxslider/bxslider.css" rel="stylesheet"  />
	<link href="plugins/notification/css/ns-default.css" rel="stylesheet" />
	<link href="plugins/notification/css/ns-style-other.css" rel="stylesheet" />
	<script src="plugins/twitter/twitter.js"></script>
				
	<!-- Demo -->
	<link rel="stylesheet" href="css/demo.css">
</head>

<body>
	<header>
		<!-- top -->
		<div id="top">
			<div class="container">
				<ul>
					<li><a href="<?= e(url()) ?>" class="active"> <i class="fa fa-home"></i> Accueil</a></li>
					<li><a href="<?= e(URL_FORUM) ?>">Forum</a></li>
					<li><a href="<?= e(URL_BARBOK) ?>">Barbok</a></li>
					<li><a href="<?= e(URL_TEAMSPEAK) ?>">Teamspeak</a></li>
					<?php if(is_admin()) { ?>
						<li><a href="<?= e(url('administration')) ?>">Administration</a></li>
					<?php } ?>
				</ul>
				
				<?php
				if(is_logged_in()) { ?>
					<div class="btn-group pull-right hidden-xs">
						<a href="<?= e(url('profile')) ?>" class="btn" ><i class="fa fa-user"></i> Mon compte</a>
						<a href="<?= e(url('logout', ['token' => csrf_token()])) ?>" class="btn" ><i class="fa fa-user"></i> Déconnexion</a>
					</div><?php
				} else { ?>
					<div class="btn-group pull-right hidden-xs">
						<a href="#signin" data-toggle="modal" class="btn"><i class="fa fa-user"></i> Connexion</a>
						<a href="#register" data-toggle="modal" class="btn"><i class="fa fa-plus"></i> Inscription</a>
					</div>
				<?php
				} ?>
			</div>
		</div>
		<!-- ./top -->
		
		<!-- header -->
		<div class="header">
			<div class="container">
				<span class="bar hide"></span>
				<a href="<?= e(url()) ?>" class="logo pull-left"><i class="fa fa-bolt"></i> <?= e(TITLE) ?></a>
				
				<ul class="list-inline pull-right hidden-xs">
					<li><a href="<?= e(URL_TWITTER) ?>" class="btn btn-social-icon btn-circle" data-toggle="tooltip" data-placement="bottom" title="Twitter"><i class="fa fa-twitter"></i></a></li>
					<li><a href="<?= e(URL_FACEBOOK) ?>" class="btn btn-social-icon btn-circle" data-toggle="tooltip" data-placement="bottom"  title="Facebook"><i class="fa fa-facebook"></i></a></li>
					<li><a href="<?= e(URL_GOOGLE) ?>" class="btn btn-social-icon btn-circle" data-toggle="tooltip" data-placement="bottom"  title="Google"><i class="fa fa-google-plus"></i></a></li>
				</ul>
			</div>
		</div>
		<!-- ./header -->
		
		<!-- navigation -->
		<nav>
			<div class="container">
				<ul>
					<li><a href="<?= e(url()) ?>">Accueil</a></li>
					<li><a href="<?= e(URL_FORUM) ?>">Forum</a></li>
					<li><a href="<?= e(url('join')) ?>">Nous rejoindre</a></li>
					<?php if(is_logged_in()) { ?>
						<li><a href="<?= e(url('shop')) ?>">Boutique</a></li>
					<?php } ?>
					<li class="dropdown">
						<a href="#">Autres<i class="ion-chevron-down"></i></a>
						<!-- dropdown menu -->
						<ul class="dropdown-menu default">
							<li><a href="<?= e(url('ladder')) ?>">Classement</a></li>
							<li><a href="<?= e(url('viewdrop')) ?>">Visualisateur de drop</a></li>
						</ul>
					</li>
				</ul>
				<div class="pull-right">
					<ul>
						<?php
						if(is_logged_in()) { ?>
							<li><a href="<?= e(url('profile')) ?>">Mon compte </a></li>
							<li><a href="<?= e(url('logout', ['token' => csrf_token()])) ?>">Déconnexion</a></li>
							<?php
						} else { ?>
							<li><a href="<?= e(url('signin')) ?>">Connexion</a></li>
							<li><a href="<?= e(url('register')) ?>">Inscription</a></li>
						<?php
						} ?>
					</ul>
				</div>
			</div>
		</nav>
		<!-- ./navigation -->
	</header>
	
	<div class="container">
		<!-- wrapper-->
		<div id="wrapper">
			<?php foreach(take_flashes() as $flash) echo alert($flash['type'], $flash['message']); ?>
