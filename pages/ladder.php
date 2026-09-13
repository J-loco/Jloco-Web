<?php
// Normal players only: groupe = -1 excludes staff characters.
$pvmPlayers = $login -> query("SELECT name, class, sexe, level, xp, alignement FROM world_players WHERE groupe = -1 ORDER BY xp DESC LIMIT 50;") -> fetchAll(PDO::FETCH_OBJ);
$pvpPlayers = $login -> query("SELECT name, class, sexe, level, alignement, honor FROM world_players WHERE groupe = -1 ORDER BY honor DESC LIMIT 50;") -> fetchAll(PDO::FETCH_OBJ);
$guilds = $login -> query("SELECT name, emblem, lvl, xp FROM `world_guilds` ORDER BY xp DESC LIMIT 50;") -> fetchAll(PDO::FETCH_OBJ);
$voters = $login -> query("SELECT pseudo, votes FROM world_accounts ORDER BY votes DESC LIMIT 50;") -> fetchAll(PDO::FETCH_OBJ);

$jobsName = [];
foreach($jiva -> query("SELECT id, name FROM jobs_data WHERE tools != '';") -> fetchAll(PDO::FETCH_OBJ) as $job)
	$jobsName[(int) $job -> id] = $job -> name;

$selectedJob = isset($_GET['job']) && ctype_digit((string) $_GET['job']) && isset($jobsName[(int) $_GET['job']]) ? (int) $_GET['job'] : null;

// world_players.jobs format: "jobId,xp;jobId,xp;..."
$jobPlayers = [];
if($selectedJob !== null) {
	$query = $login -> prepare("SELECT name, jobs FROM world_players WHERE CONCAT(';', jobs) LIKE ?;");
	$query -> execute(['%;' . $selectedJob . ',%']);

	foreach($query -> fetchAll(PDO::FETCH_OBJ) as $player) {
		$xp = 0;
		$otherJobs = [];
		foreach(explode(';', (string) $player -> jobs) as $entry) {
			[$jobId, $jobXp] = array_pad(explode(',', $entry), 2, 0);
			if((int) $jobId === $selectedJob)
				$xp = (int) $jobXp;
			else if($jobId !== '')
				$otherJobs[] = (int) $jobId;
		}
		$level = Experience::levelFromXp(Experience::JOB, Experience::MAX_JOB_LEVEL, $xp);
		$jobPlayers[] = [
			'name' => $player -> name,
			'xp' => $xp,
			'level' => $level,
			'progress' => Experience::format(Experience::progress(Experience::JOB, Experience::MAX_JOB_LEVEL, $level, $xp)),
			'otherJobs' => $otherJobs,
		];
	}
	usort($jobPlayers, fn($a, $b) => [$b['level'], $b['xp']] <=> [$a['level'], $a['xp']]);
	$jobPlayers = array_slice($jobPlayers, 0, 50);
}

$activeTab = $selectedJob !== null ? 'jobs' : 'pvm';

function class_image(object $player): string {
	return e(URL_SITE . 'img/dofus/img/class/' . ((int) $player -> class * 10 + (int) $player -> sexe) . '.png');
}

function align_image(object $player): string {
	return e(URL_SITE . 'img/dofus/img/align/' . (int) $player -> alignement . '.jpg');
}
?>
			<div class="leftside">
				<ol class="breadcrumb">
					<li><a href="<?= e(url()) ?>">Accueil</a></li>
					<li class="active">Classement</li>
				</ol>

				<section class="section section-default padding-20">
					<div class="default-tab box">
						<ul id="myTab4" class="nav nav-tabs" role="tablist">
							<li role="presentation" class="<?= $activeTab === 'pvm' ? 'active' : '' ?>"><a href="#pvm" id="pvm-tab" role="tab" data-toggle="tab" aria-controls="pvm">PvM</a></li>
							<li role="presentation"><a href="#pvp" role="tab" id="pvp-tab" data-toggle="tab" aria-controls="pvp">PvP</a></li>
							<li role="presentation"><a href="#guilds" role="tab" id="guilds-tab" data-toggle="tab" aria-controls="guilds">Guildes</a></li>
							<li role="presentation" class="<?= $activeTab === 'jobs' ? 'active' : '' ?>"><a href="#jobs" role="tab" id="jobs-tab" data-toggle="tab" aria-controls="jobs">Métiers</a></li>
							<li role="presentation"><a href="#votes" role="tab" id="votes-tab" data-toggle="tab" aria-controls="votes">Votes</a></li>
						</ul>

						<div id="myTabContent4" class="tab-content padding-20">
							<div role="tabpanel" class="tab-pane fade <?= $activeTab === 'pvm' ? 'active in' : '' ?>" id="pvm" aria-labelledby="pvm-tab">
								<?php if($pvmPlayers) { ?>
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
									<?php foreach($pvmPlayers as $i => $player) { ?>
										<tr>
											<td><?= $i + 1 ?></td>
											<td><?= e($player -> name) ?></td>
											<td class="hidden-sm"><img src="<?= class_image($player) ?>" /></td>
											<td><?= e($player -> level) ?></td>
											<td><?= e(Experience::format(Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, (int) $player -> level, (int) $player -> xp))) ?></td>
											<td class="hidden-sm"><img style="border-radius: 15px;" src="<?= align_image($player) ?>" /></td>
										</tr>
									<?php } ?>
									</tbody>
									</table>
									</div>
									</section>
								<?php } else { ?>
									<div class="alert alert-info no-border-radius" role="alert">
										<strong>Oh shit!</strong> Désolé, il n'y a encore aucun personnage.
									</div>
								<?php } ?>
							</div>

							<div role="tabpanel" class="tab-pane fade" id="pvp" aria-labelledby="pvp-tab">
								<?php if($pvpPlayers) { ?>
									<section class="section section-white no-border no-padding-top">
									<div class="box no-border-radius padding-20">
									<table class="table table-striped no-margin">
									<thead>
										<tr>
											<th>#</th>
											<th>Nom</th>
											<th class="hidden-sm">Classe</th>
											<th>Niveau</th>
											<th>Alignement</th>
											<th>Honneur</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach($pvpPlayers as $i => $player) { ?>
										<tr>
											<td><?= $i + 1 ?></td>
											<td><?= e($player -> name) ?></td>
											<td class="hidden-sm"><img src="<?= class_image($player) ?>" /></td>
											<td><?= e($player -> level) ?></td>
											<td class="hidden-sm"><img style="border-radius: 15px;" src="<?= align_image($player) ?>" /></td>
											<td><?= e($player -> honor) ?></td>
										</tr>
									<?php } ?>
									</tbody>
									</table>
									</div>
									</section>
								<?php } else { ?>
									<div class="alert alert-info no-border-radius" role="alert">
										<strong>Oh shit!</strong> Désolé, il n'y a encore aucun personnage.
									</div>
								<?php } ?>
							</div>

							<div role="tabpanel" class="tab-pane fade" id="guilds" aria-labelledby="guilds-tab">
								<?php if($guilds) { ?>
									<section class="section section-white no-border no-padding-top">
									<div class="box no-border-radius padding-20">
									<table class="table table-striped no-margin">
									<thead>
										<tr>
											<th>#</th>
											<th>Nom</th>
											<th>Niveau</th>
											<th>Expérience</th>
											<th>Emblème</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach($guilds as $i => $guild) {
										// emblem: "background,backgroundColor,logo,logoColor" in base 36
										$emblem = array_map(fn($part) => (int) base_convert($part, 36, 10), array_pad(explode(',', (string) $guild -> emblem), 4, '0'));
										$flashvars = e(http_build_query(['bcgSrc' => $emblem[0], 'bcgColor' => $emblem[1], 'frtSrc' => $emblem[2], 'frtColor' => $emblem[3]]));
										$swf = e(URL_SITE . 'img/dofus/swf/guilds/DofusGuildes.swf'); ?>
										<tr>
											<td><?= $i + 1 ?></td>
											<td><?= e($guild -> name) ?></td>
											<td><?= e($guild -> lvl) ?></td>
											<td><?= e($guild -> xp) ?></td>
											<td>
												<object type="application/x-shockwave-flash" data="<?= $swf ?>" width="50" height="50">
													<param name="movie" value="<?= $swf ?>" />
													<param name="flashvars" value="<?= $flashvars ?>" />
													<param name="quality" value="high" />
													<param name="wmode" value="transparent" />
												</object>
											</td>
										</tr>
									<?php } ?>
									</tbody>
									</table>
									</div>
									</section>
								<?php } else { ?>
									<div class="alert alert-info no-border-radius" role="alert">
										<strong>Oh shit!</strong> Désolé, il n'y a encore aucune guilde.
									</div>
								<?php } ?>
							</div>

							<div role="tabpanel" class="tab-pane fade <?= $activeTab === 'jobs' ? 'active in' : '' ?>" id="jobs" aria-labelledby="jobs-tab">
								<form class="form-inline" role="form" method="get" action="<?= e(url('ladder')) ?>" style="margin-bottom: 10px;">
									<?= route_fields('ladder') ?>
									<select class="form-control" style="width: 90%!important; height: 35px; padding: 6px 12px!important;" name="job">
										<?php foreach($jobsName as $id => $name) { ?>
											<option value="<?= $id ?>" <?= $id === $selectedJob ? 'selected' : '' ?>><?= e($name) ?></option>
										<?php } ?>
									</select>
									<button type="submit" class="btn btn-info">Ok</button>
								</form>

								<div class="panel panel-primary">
									<div class="panel-heading">Légende <small>( nom du métier au survol )</small></div>
									<div class="panel-body">
										<?php foreach($jobsName as $id => $name) { ?>
											<img style="margin:1px;" src="<?= e(URL_SITE . 'img/dofus/img/job/' . $id . '.png') ?>" data-toggle="tooltip" title="" data-original-title="<?= e($name) ?>"/>
										<?php } ?>
									</div>
								</div>

								<?php if($selectedJob !== null) {
									if($jobPlayers) { ?>
										<section class="section section-white no-border no-padding-top">
										<div class="box no-border-radius padding-20">
										<table class="table table-striped no-margin">
										<thead>
											<tr>
												<th>#</th>
												<th>Nom</th>
												<th>Métier</th>
												<th>Niveau</th>
												<th>Expérience</th>
												<th>Autre(s)</th>
											</tr>
										</thead>
										<tbody>
										<?php foreach($jobPlayers as $i => $player) { ?>
											<tr>
												<td><?= $i + 1 ?></td>
												<td><?= e($player['name']) ?></td>
												<td><img src="<?= e(URL_SITE . 'img/dofus/img/job/' . $selectedJob . '.png') ?>"/></td>
												<td><?= $player['level'] ?></td>
												<td><?= e($player['progress']) ?></td>
												<td>
												<?php foreach($player['otherJobs'] as $id) { ?>
													<img style="margin-right: 5px;" src="<?= e(URL_SITE . 'img/dofus/img/job/' . $id . '.png') ?>" data-toggle="tooltip" title="" data-original-title="<?= e($jobsName[$id] ?? '') ?>"/>
												<?php } ?>
												</td>
											</tr>
										<?php } ?>
										</tbody>
										</table>
										</div>
										</section>
									<?php } else { ?>
										<div class="alert alert-info no-border-radius" role="alert">
											<strong>Oh shit!</strong> Désolé, il n'y a encore aucun personnage possédant ce métier.
										</div>
									<?php }
								} ?>
							</div>

							<div role="tabpanel" class="tab-pane fade" id="votes" aria-labelledby="votes-tab">
								<?php if($voters) { ?>
									<section class="section section-white no-border no-padding-top">
									<div class="box no-border-radius padding-20">
									<table class="table table-striped no-margin">
									<thead>
										<tr>
											<th>#</th>
											<th>Pseudo</th>
											<th>Nombre de vote</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach($voters as $i => $voter) { ?>
										<tr>
											<td><?= $i + 1 ?></td>
											<td><?= e($voter -> pseudo) ?></td>
											<td><?= e($voter -> votes) ?></td>
										</tr>
									<?php } ?>
									</tbody>
									</table>
									</div>
									</section>
								<?php } else { ?>
									<div class="alert alert-info no-border-radius" role="alert">
										<strong>Oh shit!</strong> Désolé, il n'y a encore aucun vote.
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</section>
			</div>
			<!-- ./leftside -->
