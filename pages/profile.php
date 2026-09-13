<?php
require_login();

$accountId = (int) $_SESSION['id'];

/* ---------------------------------------------------------------- actions (POST, then redirect) */

// Privacy toggles: armory visibility and position shown in the sidebar.
foreach(['toggle-armory' => 'showOrHide', 'toggle-position' => 'showOrHidePos'] as $action => $column) {
	if(isset($_POST[$action])) {
		// $column comes from the fixed list above, never from the request.
		$login -> prepare("UPDATE world_accounts SET `$column` = 1 - `$column` WHERE guid = ?;") -> execute([$accountId]);
		redirect(url('profile'));
	}
}

if(isset($_POST['change-pass'])) {
	$answer = (string) ($_POST['answer'] ?? '');
	$newPass = (string) ($_POST['password'] ?? '');
	$newPassConf = (string) ($_POST['password-repeat'] ?? '');

	if($answer === '' || $newPass === '' || $newPassConf === '') {
		flash('danger', 'Un des champs est invalide !');
	} else if(throttle_blocked($login, 'secret_answer')) {
		flash('danger', throttle_message());
	} else {
		$query = $login -> prepare("SELECT 1 FROM `world_accounts` WHERE `guid` = ? AND `reponse` = ?;");
		$query -> execute([$accountId, $answer]);
		$answerOk = (bool) $query -> fetchColumn();
		$query -> closeCursor();

		if(!$answerOk) {
			throttle_fail($login, 'secret_answer');
			flash('danger', 'La réponse secrète est incorrecte !');
		} else if(!hash_equals($newPass, $newPassConf)) {
			flash('danger', 'Les mots de passe ne sont pas identique !');
		} else {
			throttle_clear($login, 'secret_answer');
			$login -> prepare("UPDATE `world_accounts` SET `pass` = ? WHERE `guid` = ?;") -> execute([legacy_password_hash($newPass), $accountId]);
			flash('success', 'Vous avez désormais changé votre mot de passe !');
		}
	}
	redirect(url('profile', ['tab' => 'settings']));
}

// Dedipass: the widget posts code + rate; the code is checked against the Dedipass API.
if(DEDIPASS_PUBLIC_KEY !== '' && isset($_POST['code'], $_POST['rate'])) {
	$code = preg_replace('/[^a-zA-Z0-9]+/', '', (string) $_POST['code']);
	$rate = preg_replace('/[^a-zA-Z0-9\-]+/', '', (string) $_POST['rate']);

	if($code === '' || $rate === '') {
		flash('danger', 'Vous devez définir un code et choisir un palier.');
	} else {
		$context = stream_context_create(['http' => ['timeout' => 10]]);
		$response = @file_get_contents('https://api.dedipass.com/v1/pay/?' . http_build_query(['key' => DEDIPASS_PUBLIC_KEY, 'rate' => $rate, 'code' => $code]), false, $context);
		$dedipass = $response === false ? null : json_decode($response);

		if($dedipass && ($dedipass -> status ?? '') === 'success') {
			$points = (int) $dedipass -> virtual_currency;
			$login -> prepare("UPDATE `world_accounts` SET `points` = `points` + ? WHERE `guid` = ?;") -> execute([$points, $accountId]);

			$rateParts = array_pad(explode('-', (string) $dedipass -> rate), 4, '');
			$login -> prepare("INSERT INTO `website_shop_points_purchases` (account, points, code, pays, type, date) VALUES (?, ?, ?, ?, ?, NOW());")
				-> execute([$_SESSION['user'], $points, $dedipass -> code, $rateParts[0] . '-' . $rateParts[1], $rateParts[2] . '-' . $rateParts[3]]);

			flash('success', 'Vous avez été crédité de ' . $points . ' points !');
		} else {
			flash('danger', 'Le code ' . $code . ' est invalide ou déjà utilisé !');
		}
	}
	redirect(url('profile', ['tab' => 'reload']));
}

/* ---------------------------------------------------------------- data */

$query = $login -> prepare("SELECT * FROM world_accounts WHERE guid = ?;");
$query -> execute([$accountId]);
$row = $query -> fetch(PDO::FETCH_OBJ);
$query -> closeCursor();

$tab = in_array($_GET['tab'] ?? '', ['players', 'settings', 'reload'], true) ? $_GET['tab'] : 'players';
?>
			<div class="leftside">
				<ol class="breadcrumb">
					<li><a href="<?= e(url()) ?>">Accueil</a></li>
					<li class="active">Profile</li>
				</ol>

				<div class="title">
					<h2 class="headline margin-bottom-10">Bonjour, <?= e($row -> pseudo) ?> !</h2>
					<h2 class="page-header text-center no-margin-top"></h2>
				</div>
				<div class="section section-default padding-25" >
					<div style="display:inline-flex;">
						<img class="img-thumbnail" alt="140x140" src="<?= e(URL_SITE . 'img/avatar.jpg') ?>" style="width: 140px; height: 140px;">
						<div style="margin-left: 25px; ">
							<strong>Informations :</strong><br><br><br>
							Pseudo : <?= e($row -> pseudo) ?><br>
							Date d'inscription : <?= e($row -> dateRegister) ?><br>
							Dernière connexion : <?= e(empty($row -> lastConnectionDate) ? "aucune." : parseDate($row -> lastConnectionDate)) ?><br>
							Nombre de vote(s) : <?= e($row -> totalVotes) ?><br>
							Point(s) de boutique : <?= e($row -> points) ?><br>
						</div>
					</div>
				</div>

				<form class="form-inline" method="post" action="<?= e(url('profile')) ?>" role="form">
					<?= csrf_field() ?>
					<div class="alert alert-warning no-border-radius" style="width: 58%; margin-right: 5px;">
						Tu es actuellement <b><?= $row -> showOrHide ? 'visible' : 'invisible' ?></b> dans l'armurerie !
					</div>
					<button type="submit" class="btn btn-info btn-icon-right" name="toggle-armory" style="height: 46px; width: 40%; font-size: 15px;"><?= $row -> showOrHide ? "<i class='ion-eye-disabled'></i> Être invisible" : "<i class='ion-eye'></i> Être visible" ?></button>
				</form>
				<form class="form-inline" method="post" action="<?= e(url('profile')) ?>" role="form">
					<?= csrf_field() ?>
					<div class="alert alert-warning no-border-radius" style="width: 58%; margin-right: 5px;">
						La position de tes joueurs est <b><?= $row -> showOrHidePos ? 'visible' : 'invisible' ?></b> sur le site !
					</div>
					<button type="submit" class="btn btn-info btn-icon-right" name="toggle-position" style="height: 46px; width: 40%; font-size: 15px;"><?= $row -> showOrHidePos ? "<i class='ion-eye-disabled'></i> Être invisible" : "<i class='ion-eye'></i> Être visible" ?></button>
				</form>

				<div class="default-tab">
					<ul id="myTab" class="nav nav-tabs" role="tablist">
						<li role="presentation" class="<?= $tab === 'players' ? 'active' : '' ?>"><a href="#players" style="color: #363636;!important" id="players-tab" role="tab" data-toggle="tab" aria-controls="players"><i class="ion-person-stalker"></i> Personnage(s)</a></li>
						<li role="presentation" class="<?= $tab === 'settings' ? 'active' : '' ?>"><a href="#settings" role="tab" style="color: #363636;!important" id="settings-tab" data-toggle="tab" aria-controls="settings"><i class="ion-settings"></i> Gestion</a></li>
						<?php if(DEDIPASS_PUBLIC_KEY !== '') { ?>
							<li role="presentation" class="<?= $tab === 'reload' ? 'active' : '' ?>"><a href="#reload" role="tab" style="color: #363636;!important" id="reload-tab" data-toggle="tab" aria-controls="reload"><i class="ion-card"></i> Rechargement de points</a></li>
						<?php } ?>
					</ul>

					<div id="myTabContent" class="tab-content">
						<div role="tabpanel" class="tab-pane fade <?= $tab === 'players' ? 'active in' : '' ?>" id="players" aria-labelledby="players-tab">
							<div class="row">
							<div class="col-md-12">
								<section class="section margin-top-20 margin-bottom-20 no-border">
								<?php
								$query = $login -> prepare("SELECT name, class, xp, level, sexe, alignement FROM world_players WHERE account = ?;");
								$query -> execute([$accountId]);
								$players = $query -> fetchAll(PDO::FETCH_OBJ);
								$query -> closeCursor();

								if($players) { ?>
									<section class="section section-white no-border no-padding-top">
									<div class="box no-border-radius padding-20">
									<table class="table table-striped no-margin">
									<thead>
										<tr>
											<th>#</th>
											<th>Nom</th>
											<th class="hidden-sm">Classe</th>
											<th>Niveau</th>
											<th>Expérience</th>
											<th>Alignement</th>
										</tr>
									</thead>

									<tbody>
									<?php foreach($players as $i => $player) { ?>
										<tr>
											<td><?= $i + 1 ?></td>
											<td><?= e($player -> name) ?></td>
											<td class="hidden-sm"><img src="<?= e(URL_SITE . 'img/dofus/img/class/' . ($player -> class * 10 + $player -> sexe) . '.png') ?>" /></td>
											<td><?= e($player -> level) ?></td>
											<td><?= e(Experience::format(Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, (int) $player -> level, (int) $player -> xp))) ?></td>
											<td class="hidden-sm"><img style="border-radius: 15px; -moz-border-radius: 15px; -webkit-border-radius: 15px;" src="<?= e(URL_SITE . 'img/dofus/img/align/' . (int) $player -> alignement . '.jpg') ?>" /></td>
										</tr>
									<?php } ?>
									</tbody>
									</table>
									</div>
									</section>
									<?php
								} else { ?>
									<div class="alert alert-info no-border-radius" style="text-align: center;">
										<strong>Oh shit!</strong> Désolé, il n'y a encore aucun personnage correspondant à votre compte.
									</div>
								<?php
								} ?>
								</section>
							</div>
							</div>
						</div>

						<div role="tabpanel" class="tab-pane fade <?= $tab === 'settings' ? 'active in' : '' ?>" id="settings" aria-labelledby="settings-tab">
							<div class="row">
								<div class="col-md-12">
									<section class="section margin-top-20 margin-bottom-20 no-border">
										<h4 class="page-header no-margin-top">Changement de mot de passe</h4>
										<form role="form" method="post" action="<?= e(url('profile')) ?>">
											<?= csrf_field() ?>
											<div class="form-group">
												<label for="answer">Question : <?= e($row -> question) ?></label>
												<input type="text" class="form-control margin-top-5" id="answer" name="answer" placeholder="La réponse est..">
											</div>
											<div class="form-group">
												<label for="password">Nouveau mot de passe</label>
												<input type="password" class="form-control margin-top-5" id="password" name="password" placeholder="">
											</div>
											<div class="form-group">
												<label for="password-repeat">Confirmation du nouveau mot de passe</label>
												<input type="password" class="form-control margin-top-5" id="password-repeat" name="password-repeat" placeholder="">
											</div>
											<button type="submit" name="change-pass" class="btn btn-success">Confirmer</button>
										</form>
									</section>
								</div>
							</div>
						</div>

						<?php if(DEDIPASS_PUBLIC_KEY !== '') { ?>
						<div role="tabpanel" class="tab-pane fade <?= $tab === 'reload' ? 'active in' : '' ?>" id="reload" aria-labelledby="reload-tab">
							<div class="row">
								<div class="col-md-12">
									<section class="section margin-top-10 margin-bottom-20 no-border">
										<div class='alert alert-warning no-border-radius no-margin' style='text-align: center!important;' role='alert'>
											<strong>Oh wait !</strong> Toutes les transactions sont enregistrés ainsi nous pouvons vérifier le statut d'un code, ceci dit, nous ne sommes en aucun cas responsable des problèmes de code incorrecte venant de DEDIPASS. Il est préférable que vous les contactiez en cas problème lié au système. Merci.
										</div><br />
										<div data-dedipass="<?= e(DEDIPASS_PUBLIC_KEY) ?>"></div>
									</section>
								</div>
							</div>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>
			<!-- ./leftside -->
			<?php if(DEDIPASS_PUBLIC_KEY !== '') { ?>
				<script src="https://api.dedipass.com/v1/pay.js"></script>
			<?php } ?>
