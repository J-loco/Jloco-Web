<?php
const VOTE_COOLDOWN_SECONDS = 3 * 3600;

if(isset($_POST['vote']) && is_logged_in()) {
	$now = time();
	$ip = client_ip();

	$query = $login -> prepare("SELECT MAX(`date`) FROM `website_users_votes` WHERE ip = ?;");
	$query -> execute([$ip]);
	$ipLastVote = (int) $query -> fetchColumn();
	$query -> closeCursor();

	$query = $login -> prepare("SELECT heurevote FROM world_accounts WHERE guid = ?;");
	$query -> execute([(int) $_SESSION['id']]);
	$accountLastVote = (int) $query -> fetchColumn();
	$query -> closeCursor();

	$wait = max($accountLastVote, $ipLastVote) + VOTE_COOLDOWN_SECONDS - $now;
	if($wait > 0) {
		flash('danger', 'Il te faut attendre encore ' . (int) ceil($wait / 60) . ' minute(s) avant de pouvoir voter !');
		redirect(url('vote'));
	}

	// Atomic: only credits if no other request voted for this account in the meantime.
	$credit = $login -> prepare("UPDATE world_accounts SET votes = votes + 1, totalVotes = totalVotes + 1, points = points + ?, heurevote = ? WHERE guid = ? AND heurevote <= ?;");
	$credit -> execute([PTS_PER_VOTE, $now, (int) $_SESSION['id'], $now - VOTE_COOLDOWN_SECONDS]);

	if($credit -> rowCount() === 1) {
		$login -> prepare("DELETE FROM `website_users_votes` WHERE ip = ?;") -> execute([$ip]);
		$login -> prepare("INSERT INTO `website_users_votes` (ip, date) VALUES (?, ?);") -> execute([$ip, $now]);
	}
	redirect(URL_RPG);
}
?>
			<div class="leftside">
				<ol class="breadcrumb">
					<li><a href="<?= e(url()) ?>">Accueil</a></li>
					<li class="active">Vote</li>
				</ol>
				<?php if(is_logged_in()) { ?>
					<form method="post" action="<?= e(url('vote')) ?>">
						<?= csrf_field() ?>
						<center><button type="submit" name="vote" style="border: none; padding: 0; background: none;"><img style="border: 1px solid gray;" src="<?= e(URL_SITE . 'img/rpg.jpg') ?>" alt="Voter"/></button></center><br>
					</form>
					<div class="alert alert-info no-border-radius" role="alert">
						<center>Afin de rendre utile les votes, nous vous offrons <?= e(PTS_PER_VOTE) ?> points de vote pour vous remerciez des votes effectués !<br>
						Après avoir cliqué sur l'image ci-dessus, vous serez créditer des points indiqué.</center>
					</div>
				<?php } else { ?>
					<center><a href="<?= e(URL_RPG) ?>"><img style="border: 1px solid gray;" src="<?= e(URL_SITE . 'img/rpg.jpg') ?>" alt="Voter"/></a></center><br>
					<div class="alert alert-info no-border-radius" role="alert">
						<center>Afin de rendre utile les votes, nous vous offrons <?= e(PTS_PER_VOTE) ?> points de vote pour vous remerciez des votes effectués !
						Pour que vos votes soient comptabiliser, il faut que vous soyez connecter sur le site.
						Nous vous invitons donc à vous connectez en <strong><a href="<?= e(url('signin')) ?>"> cliquant ici.</a></strong><br><br>

						Sinon, vous pouvez directement voter pour nous en cliquant sur l'image ci-dessus.</center>
					</div>
				<?php } ?>
			</div>
			<!-- ./leftside -->
